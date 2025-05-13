<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

header('Content-Type: application/json');

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    try {
        $lock = $con->prepare("UPDATE medlist set status=? where name=?");
        $lock->execute(['open', $name]);
        
        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Medication unlocked successfully']);
    } catch (Exception $e) {
        // Return error response
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error unlocking medication: ' . $e->getMessage()]);
    }
} else {
    // Return error response for missing name parameter
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing medication name parameter']);
}
