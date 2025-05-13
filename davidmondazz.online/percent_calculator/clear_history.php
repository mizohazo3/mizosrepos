<?php
// Database connection setup
$db_host = 'localhost';
$db_user = 'mcgkxyz_masterpop';  // Change to your MySQL username
$db_pass = 'aA0109587045';      // Change to your MySQL password
$db_name = 'mcgkxyz_percent_calculator';  // Database name

// Always return JSON for AJAX compatibility
header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Connect to MySQL database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete all records from calculations table
    $conn->exec("DELETE FROM calculations");
    $response['success'] = true;
    
} catch(PDOException $e) {
    // Handle errors
    $response['error'] = 'Database error';
}

// Return JSON response
echo json_encode($response);
exit;
?> 