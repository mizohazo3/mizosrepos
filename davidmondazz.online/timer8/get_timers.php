<?php

include_once 'timezone_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

    // Get all timers, ordered by is_pinned DESC (pinned first) and then by id DESC (newest first)
    $stmt = $pdo->query('SELECT * FROM timers ORDER BY is_pinned DESC, id DESC');
    $timers = $stmt->fetchAll();

    echo json_encode($timers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 