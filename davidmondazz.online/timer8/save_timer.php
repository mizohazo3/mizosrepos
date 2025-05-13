<?php

include_once 'timezone_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || !isset($data['duration'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and duration are required']);
    exit;
}

// Database connection
$host = 'localhost';      // Or your DB host
$dbname = 'mcgkxyz_timer_app';    // Your database name
$username = 'mcgkxyz_masterpop';       // Your DB username
$password = 'aA0109587045';           // Your DB password
$db_charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Insert the new timer
    $stmt = $pdo->prepare('INSERT INTO timers (name, duration) VALUES (?, ?)');
    $stmt->execute([$data['name'], $data['duration']]);

    // Get the ID of the newly inserted timer
    $newId = $pdo->lastInsertId();

    // Return the new timer data
    echo json_encode([
        'id' => $newId,
        'name' => $data['name'],
        'duration' => $data['duration'],
        'is_pinned' => 0
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 