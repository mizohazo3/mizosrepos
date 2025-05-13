<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

// Simple debugging tool to check if a record exists
header('Content-Type: application/json');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Check if record exists
        $checkRecord = $con->prepare("SELECT * FROM medtrack WHERE id = ?");
        $checkRecord->execute([$id]);
        
        if ($checkRecord->rowCount() > 0) {
            $record = $checkRecord->fetch(PDO::FETCH_ASSOC);
            // Return record details
            echo json_encode([
                'status' => 'success',
                'found' => true,
                'record' => $record
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'found' => false,
                'message' => 'Record not found with ID: ' . $id
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'No ID parameter provided'
    ]);
}
?> 