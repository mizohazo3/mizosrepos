<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Only POST method is allowed']);
    exit;
}

try {
    // Get database connection
    $conn = getDbConnection();
    
    // Get and validate input data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || trim($data['name']) === '') {
        echo json_encode(['success' => false, 'error' => 'Timer name is required']);
        exit;
    }
    
    if (!isset($data['category_id']) || !is_numeric($data['category_id'])) {
        echo json_encode(['success' => false, 'error' => 'Valid category ID is required']);
        exit;
    }
    
    // Sanitize inputs
    $name = trim($data['name']);
    $category_id = (int)$data['category_id'];
    
    // Verify the category exists
    $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $category_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Selected category does not exist']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Insert the new timer
    $stmt = $conn->prepare("INSERT INTO timers (name, category_id) VALUES (?, ?)");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", $name, $category_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $timer_id = $conn->insert_id;
    
    // Get the newly created timer with category name
    $stmt = $conn->prepare("
        SELECT t.*, c.name as category_name 
        FROM timers t 
        INNER JOIN categories c ON t.category_id = c.id 
        WHERE t.id = ?
    ");
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $timer_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $timer = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'success' => true,
        'message' => 'Timer created successfully',
        'timer' => [
            'id' => $timer['id'],
            'name' => $timer['name'],
            'category_id' => $timer['category_id'],
            'category_name' => $timer['category_name'],
            'status' => $timer['status'],
            'total_time' => (int)$timer['total_time'],
            'total_time_formatted' => formatTime((int)$timer['total_time']),
            'created_at' => $timer['created_at'],
            'updated_at' => $timer['updated_at']
        ]
    ];
    
    echo json_encode($response);
    $stmt->close();

} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}
?> 