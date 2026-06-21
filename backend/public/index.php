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
// REPORTS ROUTES (Maintenance Requests)
// ==========================================

$app->get('/reports', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM reports ORDER BY id DESC");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response->getBody()->write(json_encode($reports));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/reports', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO reports (title, description, room, studentName, dateSubmitted, assignedStaff, deadline, status, category, priority)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['title'] ?? '',
        $data['description'] ?? '',
        $data['room'] ?? '',
        $data['studentName'] ?? '',
        $data['dateSubmitted'] ?? date('Y-m-d'),
        $data['assignedStaff'] ?? 'Unassigned',
        $data['deadline'] ?? null,
        $data['status'] ?? 'Pending',
        $data['category'] ?? null,
        $data['priority'] ?? null
    ]);
    
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->patch('/reports/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $data = json_decode($request->getBody()->getContents(), true);
    
    $fields = [];
    $params = [];
    foreach ($data as $key => $val) {
        if (in_array($key, ['title', 'description', 'room', 'studentName', 'dateSubmitted', 'assignedStaff', 'deadline', 'status', 'category', 'priority'])) {
            $fields[] = "$key = ?";
            $params[] = $val;
        }
    }
    
    if (!empty($fields)) {
        $params[] = $args['id'];
        $stmt = $pdo->prepare("UPDATE reports SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
    $stmt->execute([$args['id']]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($report));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/reports/{id}', function (Request $request, Response $response, $args) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$args['id']]);
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});


// ==========================================
// BOOKINGS ROUTES (Facility Bookings)
// ==========================================

$app->get('/bookings', function (Request $request, Response $response) {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM bookings ORDER BY id DESC");
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