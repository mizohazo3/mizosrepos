<?php

include_once 'timezone_config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the timer ID from the URL
$timerId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$timerId) {
    http_response_code(400);
    echo json_encode(['error' => 'Timer ID is required']);
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

    // Prepare and execute the delete statement
    $stmt = $pdo->prepare('DELETE FROM timers WHERE id = ?');
    $stmt->execute([$timerId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Timer deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Timer not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 