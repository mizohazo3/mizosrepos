<?php
// Include database connection
include 'db.php';

// Set response header to JSON
header('Content-Type: application/json');

// Get JSON data from POST request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate input
if (!isset($data['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter: name']);
    exit;
}

// Extract data
$medicationName = $data['name'];
$doseTime = isset($data['doseTime']) ? $data['doseTime'] : date("d M, Y h:i a");
$halfLife = isset($data['halfLife']) ? $data['halfLife'] : null;

try {
    // Update the lastdose timestamp and reset notification flags
    $stmt = $con->prepare("UPDATE medlist SET 
                           lastdose = ?, 
                           sent_email = NULL, 
                           fivehalf_email = NULL 
                           WHERE name = ?");
    
    $stmt->execute([$doseTime, $medicationName]);
    
    // If halfLife is provided, update it as well
    if ($halfLife !== null) {
        $stmtHalfLife = $con->prepare("UPDATE medlist SET default_half_life = ? WHERE name = ?");
        $stmtHalfLife->execute([$halfLife, $medicationName]);
    }
    
    // Check if update was successful
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Medication data updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Medication not found or no update needed']);
    }
} 
catch (PDOException $e) {
    // Return error
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 