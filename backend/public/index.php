<?php
date_default_timezone_set('UTC');

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

function generateUUID() {
    return bin2hex(random_bytes(16));
}

$app = AppFactory::create();
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("API is running");
    return $response;
});

$app->get('/users', function (Request $request, Response $response) {
    $pdo = getDBConnection();

    $stmt = $pdo->query("SELECT * FROM users");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($users));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/register', function (Request $request, Response $response) {
    $pdo = getDBConnection();

    $data = json_decode($request->getBody()->getContents(), true);

    // validation
    if (!$data['email'] || !$data['password'] || !$data['name']) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // check existing user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);

    if ($stmt->fetch()) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]));
        return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
    }

    // hash password
    $hashed = password_hash($data['password'], PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO users (uuid, name, email, password_hash, phone, role, matrix_number, hostel_block, room_number)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        generateUUID(),
        $data['name'],
        $data['email'],
        $hashed,
        $data['phone'] ?? null,
        $data['role'] ?? 'student',
        $data['matrixNumber'] ?? null,
        $data['hostelBlock'] ?? null,
        $data['roomNumber'] ?? null
    ]);

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'User registered successfully'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/login', function (Request $request, Response $response) {
    $pdo = getDBConnection();

    $data = json_decode($request->getBody()->getContents(), true);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]));

        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    unset($user['password_hash']);

    $response->getBody()->write(json_encode([
        'success' => true,
        'user' => $user
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/forgot-password', function ($request, $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);

    $email = $data['email'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // IMPORTANT: always return success (security best practice)
    if (!$user) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'If email exists, reset link sent'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $pdo->prepare("
        UPDATE users 
        SET reset_token = ?, reset_expires = ?
        WHERE email = ?
    ");

    $stmt->execute([$token, $expires, $email]);

    // For now: return link (instead of email)
    $resetLink = "http://localhost:8080/reset-password?token=$token";

    $response->getBody()->write(json_encode([
        'success' => true,
        'resetLink' => $resetLink
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/reset-password', function ($request, $response) {

    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);

    $token = $data['token'];
    $password = $data['password'];

    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE reset_token = ?
        AND reset_expires > ?
    ");

    $stmt->execute([
        $token,
        date('Y-m-d H:i:s')
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Invalid or expired token'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE users
        SET password_hash = ?, reset_token = NULL, reset_expires = NULL
        WHERE id = ?
    ");

    $stmt->execute([$hashedPassword, $user['id']]);

    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/users/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$args['id']]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response->getBody()->write(json_encode([
            'error' => 'User not found'
        ]));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    unset($user['password_hash']);

    $response->getBody()->write(json_encode($user));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->patch('/users/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();

    $data = json_decode($request->getBody()->getContents(), true);

    $stmt = $pdo->prepare("
        UPDATE users
        SET name = ?,
            email = ?,
            phone = ?,
            matrix_number = ?,
            hostel_block = ?,
            room_number = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $data['name'],
        $data['email'],
        $data['phone'] ?? null,
        $data['matrixNumber'] ?? null,
        $data['hostelBlock'] ?? null,
        $data['roomNumber'] ?? null,
        $args['id']
    ]);

    // return updated user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$args['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    unset($user['password_hash']);

    $response->getBody()->write(json_encode($user));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();

?>