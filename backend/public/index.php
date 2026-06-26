<?php
date_default_timezone_set('UTC');

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

function generateUUID() {
    return bin2hex(random_bytes(16));
}

function jwtSecret() {
    return getenv('JWT_SECRET') ?: 'webtech_25262_local_jwt_secret_change_me';
}

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;

    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($data, '-_', '+/'), true);
}

function generateJwt($user) {
    $now = time();
    $expiresIn = 60 * 60 * 2;

    $header = [
        'typ' => 'JWT',
        'alg' => 'HS256'
    ];

    $payload = [
        'iss' => 'webtech-25262-api',
        'sub' => (string)$user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'iat' => $now,
        'exp' => $now + $expiresIn
    ];

    $encodedHeader = base64UrlEncode(json_encode($header));
    $encodedPayload = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', "$encodedHeader.$encodedPayload", jwtSecret(), true);

    return "$encodedHeader.$encodedPayload." . base64UrlEncode($signature);
}

function validateJwt($token) {
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return null;
    }

    [$encodedHeader, $encodedPayload, $signature] = $parts;
    $headerJson = base64UrlDecode($encodedHeader);
    $payloadJson = base64UrlDecode($encodedPayload);

    if ($headerJson === false || $payloadJson === false) {
        return null;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);

    if (($header['alg'] ?? '') !== 'HS256' || !$payload) {
        return null;
    }

    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', "$encodedHeader.$encodedPayload", jwtSecret(), true)
    );

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    if (($payload['iss'] ?? '') !== 'webtech-25262-api' || ($payload['exp'] ?? 0) < time()) {
        return null;
    }

    return $payload;
}

function authErrorResponse($message) {
    $response = new SlimResponse(401);
    $response->getBody()->write(json_encode([
        'success' => false,
        'message' => $message
    ]));

    return $response->withHeader('Content-Type', 'application/json');
}

$app = AppFactory::create();
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
        INSERT INTO users (uuid, name, email, password_hash, phone, role, matrixNumber, hostelBlock, roomNumber)
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

    $token = generateJwt($user);
    unset($user['password_hash']);

    $response->getBody()->write(json_encode([
        'success' => true,
        'user' => $user,
        'token' => $token,
        'tokenType' => 'Bearer',
        'expiresIn' => 60 * 60 * 2
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
            matrixNumber = ?,
            hostelBlock = ?,
            roomNumber = ?
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

// ==========================================
// BOOKINGS ROUTES (Facility Bookings)
// ==========================================

$app->get('/bookings', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT b.*,
               COALESCE(u.name, b.userName, b.studentName) AS userName,
               COALESCE(u.name, b.studentName, b.userName) AS studentName
        FROM bookings b
        LEFT JOIN users u ON b.userId = u.id
        ORDER BY b.id DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($bookings));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/bookings', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO bookings (userId, userName, studentName, facilityId, facilityName, date, startTime, endTime, purpose, status, rejectionReason, createdAt, approvedAt, rejectedAt)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['userId'] ?? '',
        $data['userName'] ?? ($data['studentName'] ?? ''),
        $data['studentName'] ?? ($data['userName'] ?? ''),
        $data['facilityId'] ?? '',
        $data['facilityName'] ?? '',
        $data['date'] ?? '',
        $data['startTime'] ?? '',
        $data['endTime'] ?? '',
        $data['purpose'] ?? '',
        $data['status'] ?? 'Pending',
        $data['rejectionReason'] ?? null,
        $data['createdAt'] ?? date('Y-m-d H:i:s'),
        $data['approvedAt'] ?? null,
        $data['rejectedAt'] ?? null
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($booking));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->patch('/bookings/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $fields = [];
    $params = [];
    foreach ($data as $key => $val) {
        if (in_array($key, ['userId', 'userName', 'studentName', 'facilityId', 'facilityName', 'date', 'startTime', 'endTime', 'purpose', 'status', 'rejectionReason', 'createdAt', 'approvedAt', 'rejectedAt'])) {
            $fields[] = "$key = ?";
            $params[] = $val;
        }
    }
    
    if (!empty($fields)) {
        $params[] = $args['id'];
        $stmt = $pdo->prepare("UPDATE bookings SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$args['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($booking));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/bookings/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$args['id']]);
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// FACILITIES ROUTES
// ==========================================

$app->get('/facilities', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM facilities ORDER BY id DESC");
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse json columns
    foreach ($facilities as &$f) {
        $f['amenities'] = json_decode($f['amenities'] ?? '[]', true);
        $f['authorizedRoles'] = json_decode($f['authorizedRoles'] ?? '[]', true);
        $f['availability'] = (bool)$f['availability'];
        $f['restricted'] = (bool)$f['restricted'];
        $f['capacity'] = (int)$f['capacity'];
    }
    
    $response->getBody()->write(json_encode($facilities));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/facilities', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO facilities (name, category, description, capacity, amenities, availability, status, restricted, authorizedRoles)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'] ?? '',
        $data['category'] ?? '',
        $data['description'] ?? '',
        $data['capacity'] ?? 0,
        json_encode($data['amenities'] ?? []),
        isset($data['availability']) ? ($data['availability'] ? 1 : 0) : 1,
        $data['status'] ?? 'Available',
        isset($data['restricted']) ? ($data['restricted'] ? 1 : 0) : 0,
        json_encode($data['authorizedRoles'] ?? [])
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = ?");
    $stmt->execute([$id]);
    $facility = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $facility['amenities'] = json_decode($facility['amenities'] ?? '[]', true);
    $facility['authorizedRoles'] = json_decode($facility['authorizedRoles'] ?? '[]', true);
    $facility['availability'] = (bool)$facility['availability'];
    $facility['restricted'] = (bool)$facility['restricted'];
    $facility['capacity'] = (int)$facility['capacity'];
    
    $response->getBody()->write(json_encode($facility));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->patch('/facilities/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $fields = [];
    $params = [];
    foreach ($data as $key => $val) {
        if (in_array($key, ['name', 'category', 'description', 'capacity', 'availability', 'status', 'restricted'])) {
            $fields[] = "$key = ?";
            $params[] = ($key === 'availability' || $key === 'restricted') ? ($val ? 1 : 0) : $val;
        } elseif (in_array($key, ['amenities', 'authorizedRoles'])) {
            $fields[] = "$key = ?";
            $params[] = json_encode($val);
        }
    }
    
    if (!empty($fields)) {
        $params[] = $args['id'];
        $stmt = $pdo->prepare("UPDATE facilities SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM facilities WHERE id = ?");
    $stmt->execute([$args['id']]);
    $facility = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $facility['amenities'] = json_decode($facility['amenities'] ?? '[]', true);
    $facility['authorizedRoles'] = json_decode($facility['authorizedRoles'] ?? '[]', true);
    $facility['availability'] = (bool)$facility['availability'];
    $facility['restricted'] = (bool)$facility['restricted'];
    $facility['capacity'] = (int)$facility['capacity'];
    
    $response->getBody()->write(json_encode($facility));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/facilities/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->execute([$args['id']]);
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// ROOMS ROUTES
// ==========================================

$app->get('/rooms', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY block, number");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rooms as &$r) {
        $r['capacity'] = (int)$r['capacity'];
        $r['occupied'] = (int)$r['occupied'];
    }
    
    $response->getBody()->write(json_encode($rooms));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// ANALYTICS REPORTS ROUTES
// ==========================================

$app->get('/analyticsReports', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM analytics_reports ORDER BY createdAt DESC");
    $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($snapshots as &$s) {
        $s['filters'] = json_decode($s['filters'] ?? '{}', true);
        $s['snapshot'] = json_decode($s['snapshot'] ?? '{}', true);
    }
    
    $response->getBody()->write(json_encode($snapshots));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/analyticsReports', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO analytics_reports (name, summary, filterText, date, createdAt, createdBy, filters, snapshot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'] ?? '',
        $data['summary'] ?? '',
        $data['filterText'] ?? '',
        $data['date'] ?? date('Y-m-d'),
        $data['createdAt'] ?? date('Y-m-d H:i:s'),
        $data['createdBy'] ?? '',
        json_encode($data['filters'] ?? '{}'),
        json_encode($data['snapshot'] ?? '{}')
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM analytics_reports WHERE id = ?");
    $stmt->execute([$id]);
    $snapshot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $snapshot['filters'] = json_decode($snapshot['filters'] ?? '{}', true);
    $snapshot['snapshot'] = json_decode($snapshot['snapshot'] ?? '{}', true);
    
    $response->getBody()->write(json_encode($snapshot));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// BLOCKED SLOTS ROUTES
// ==========================================

$app->get('/blockedSlots', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM blocked_slots ORDER BY date, startTime");
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($slots));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/blockedSlots', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO blocked_slots (facilityId, facilityName, date, startTime, endTime, reason, status, createdAt, createdBy)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['facilityId'] ?? '',
        $data['facilityName'] ?? '',
        $data['date'] ?? '',
        $data['startTime'] ?? '',
        $data['endTime'] ?? '',
        $data['reason'] ?? '',
        $data['status'] ?? 'Blocked',
        $data['createdAt'] ?? date('Y-m-d H:i:s'),
        $data['createdBy'] ?? ''
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM blocked_slots WHERE id = ?");
    $stmt->execute([$id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($slot));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->patch('/blockedSlots/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $fields = [];
    $params = [];
    foreach ($data as $key => $val) {
        if (in_array($key, ['facilityId', 'facilityName', 'date', 'startTime', 'endTime', 'reason', 'status', 'createdAt', 'createdBy'])) {
            $fields[] = "$key = ?";
            $params[] = $val;
        }
    }
    
    if (!empty($fields)) {
        $params[] = $args['id'];
        $stmt = $pdo->prepare("UPDATE blocked_slots SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM blocked_slots WHERE id = ?");
    $stmt->execute([$args['id']]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($slot));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// BOOKING LOGS ROUTES
// ==========================================

$app->get('/bookingLogs', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM booking_logs ORDER BY timestamp DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($logs));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/bookingLogs', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO booking_logs (timestamp, userId, userName, facilityId, facilityName, action)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['timestamp'] ?? date('Y-m-d H:i:s'),
        $data['userId'] ?? '',
        $data['userName'] ?? '',
        $data['facilityId'] ?? '',
        $data['facilityName'] ?? '',
        $data['action'] ?? ''
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM booking_logs WHERE id = ?");
    $stmt->execute([$id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($log));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// NOTIFICATIONS ROUTES
// ==========================================

$app->get('/notifications', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY createdAt DESC");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($notifications as &$n) {
        $n['read'] = (bool)$n['read'];
    }
    
    $response->getBody()->write(json_encode($notifications));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/notifications', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (userId, message, type, `read`, createdAt)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['userId'] ?? '',
        $data['message'] ?? '',
        $data['type'] ?? '',
        isset($data['read']) ? ($data['read'] ? 1 : 0) : 0,
        $data['createdAt'] ?? date('Y-m-d H:i:s')
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$id]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $notification['read'] = (bool)$notification['read'];
    
    $response->getBody()->write(json_encode($notification));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// MAINTENANCE MODULE ROUTES
// ==========================================

$app->get('/maintenance', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        ORDER BY r.id DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($reports));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/maintenance/stats', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as totalReports,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pendingReports,
            SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) as assignedReports,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as inProgressReports,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolvedReports
        FROM reports
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stats['totalReports'] = (int)($stats['totalReports'] ?? 0);
    $stats['pendingReports'] = (int)($stats['pendingReports'] ?? 0);
    $stats['assignedReports'] = (int)($stats['assignedReports'] ?? 0);
    $stats['inProgressReports'] = (int)($stats['inProgressReports'] ?? 0);
    $stats['resolvedReports'] = (int)($stats['resolvedReports'] ?? 0);
    
    $response->getBody()->write(json_encode($stats));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/maintenance/student/{userId}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.user_id = ?
        ORDER BY r.id DESC
    ");
    $stmt->execute([$args['userId']]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($reports));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/maintenance/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$args['id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        $response->getBody()->write(json_encode(['error' => 'Report not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/maintenance', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$data['user_id']]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentName = $userRow['name'] ?? 'Unknown Student';
    
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM reports");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextId = ($row['max_id'] ?? 0) + 1;
    $report_code = 'MR-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    
    $room = ($data['hostel_block'] ?? '') . '-' . ($data['room_number'] ?? '');
    $deadline = date('Y-m-d', strtotime('+7 days'));
    
    $stmt = $pdo->prepare("
        INSERT INTO reports (user_id, report_code, title, description, room, hostel_block, room_number, studentName, dateSubmitted, status, category, priority, student_remarks, deadline)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['user_id'],
        $report_code,
        $data['title'] ?? '',
        $data['description'] ?? '',
        $room,
        $data['hostel_block'] ?? '',
        $data['room_number'] ?? '',
        $studentName,
        date('Y-m-d'),
        'Pending',
        $data['category'] ?? null,
        $data['priority'] ?? 'Medium',
        $data['student_remarks'] ?? null,
        $deadline
    ]);
    
    $id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (userId, message, type, `read`, createdAt)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['user_id'],
        "New maintenance report submitted.",
        "Maintenance",
        0,
        date('Y-m-d H:i:s')
    ]);
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/maintenance/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $fields = [];
    $params = [];
    $allowedFields = ['title', 'description', 'room', 'hostel_block', 'room_number', 'status', 'category', 'priority', 'student_remarks', 'staff_remarks', 'deadline'];
    
    foreach ($data as $key => $val) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $params[] = $val;
        }
    }
    
    if (!empty($fields)) {
        $params[] = $args['id'];
        $stmt = $pdo->prepare("UPDATE reports SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$args['id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/maintenance/{id}/assign', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    $staffId = $data['assigned_staff_id'] ?? null;
    
    $staffName = 'Unassigned';
    if ($staffId) {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$staffId]);
        $staffRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($staffRow) {
            $staffName = $staffRow['name'];
        }
    }
    
    $stmt = $pdo->prepare("
        UPDATE reports 
        SET assigned_staff_id = ?, 
            assignedStaff = ?, 
            status = 'Assigned',
            assigned_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$staffId, $staffName, $args['id']]);
    
    $stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
    $stmt->execute([$args['id']]);
    $reportRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $reportRow['user_id'] ?? null;
    
    if ($studentId) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (userId, message, type, `read`, createdAt)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            "Your maintenance report has been assigned.",
            "Maintenance",
            0,
            date('Y-m-d H:i:s')
        ]);
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$args['id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->put('/maintenance/{id}/status', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    $status = $data['status'] ?? 'Pending';
    $staffRemarks = $data['staff_remarks'] ?? null;
    
    $resolvedAt = ($status === 'Resolved') ? date('Y-m-d H:i:s') : null;
    
    if ($resolvedAt) {
        $stmt = $pdo->prepare("
            UPDATE reports 
            SET status = ?, 
                staff_remarks = COALESCE(?, staff_remarks),
                resolved_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $staffRemarks, $resolvedAt, $args['id']]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE reports 
            SET status = ?, 
                staff_remarks = COALESCE(?, staff_remarks)
            WHERE id = ?
        ");
        $stmt->execute([$status, $staffRemarks, $args['id']]);
    }
    
    $stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
    $stmt->execute([$args['id']]);
    $reportRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $studentId = $reportRow['user_id'] ?? null;
    
    if ($studentId) {
        $message = "Maintenance report status updated to " . $status . ".";
        if ($status === 'In Progress') {
            $message = "Maintenance report status updated to In Progress.";
        } else if ($status === 'Resolved') {
            $message = "Your maintenance report has been resolved.";
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (userId, message, type, `read`, createdAt)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studentId,
            $message,
            "Maintenance",
            0,
            date('Y-m-d H:i:s')
        ]);
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(u.name, r.studentName) AS studentName,
               COALESCE(s.name, r.assignedStaff) AS assignedStaff
        FROM reports r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users s ON r.assigned_staff_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$args['id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/maintenance/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$args['id']]);
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$publicRoutes = ['/', '/register', '/login', '/forgot-password', '/reset-password'];

$app->add(function (Request $request, $handler) use ($publicRoutes) {
    $path = rtrim($request->getUri()->getPath(), '/') ?: '/';

    if ($request->getMethod() === 'OPTIONS' || in_array($path, $publicRoutes, true)) {
        return $handler->handle($request);
    }

    $authHeader = $request->getHeaderLine('Authorization');

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return authErrorResponse('Missing Authorization bearer token');
    }

    $jwtPayload = validateJwt($matches[1]);

    if (!$jwtPayload) {
        return authErrorResponse('Invalid or expired token');
    }

    return $handler->handle($request->withAttribute('auth_user', $jwtPayload));
});

$app->addErrorMiddleware(true, true, true);

// CORS Middleware added after ErrorMiddleware so that it executes outermost 
// and adds CORS headers to error/exception responses too.
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->run();

?>
