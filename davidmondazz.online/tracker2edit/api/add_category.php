<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name']) || trim($data['name']) === '') {
    echo json_encode(['error' => 'Category name is required']);
    exit;
}

// Sanitize input
$name = trim($data['name']);

// Check if category already exists
$stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['error' => 'A category with this name already exists']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert the new category
$stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
$stmt->bind_param("s", $name);

if ($stmt->execute()) {
    $category_id = $conn->insert_id;
    
    // Return the newly created category
    echo json_encode([
        'success' => true,
        'message' => 'Category created successfully',
        'category' => [
            'id' => $category_id,
            'name' => $name,
            'running_count' => 0,
            'paused_count' => 0
        ]
    ]);
} else {
    handleDbError($conn, 'Failed to create category');
}

$stmt->close();
$conn->close();
?> 