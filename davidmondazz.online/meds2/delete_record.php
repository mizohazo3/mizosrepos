<?php
session_start();
include '../checkSession.php';
include 'db.php';
include 'med_functions.php'; // Include med_functions for getting bank balance

// Check if deletion_logs table exists, create if not
try {
    $tableCheck = $con->query("SHOW TABLES LIKE 'deletion_logs'");
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, create it
        $con->exec("CREATE TABLE `deletion_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user` varchar(50) NOT NULL,
            `record_id` int(11) NOT NULL,
            `medname` varchar(255) NOT NULL,
            `dose_date` varchar(50) NOT NULL,
            `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        error_log("Created deletion_logs table automatically");
    }
} catch (Exception $e) {
    error_log("Error checking/creating deletion_logs table: " . $e->getMessage());
    // Continue execution, the table might be created later
}

// Check if the request is POST and the ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    try {
        // First, get record details for logging purposes
        $getRecord = $con->prepare("SELECT * FROM medtrack WHERE id = ?");
        $getRecord->execute([$id]);
        $record = $getRecord->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            $medname = $record['medname'];
            $dose_date = $record['dose_date'];
            
            // Delete the record
            $stmt = $con->prepare("DELETE FROM medtrack WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                // Try to log the deletion, but don't fail if it doesn't work
                try {
                    $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
                    $timestamp = date("Y-m-d H:i:s");
                    $log = $con->prepare("INSERT INTO deletion_logs (user, record_id, medname, dose_date, deleted_at) VALUES (?, ?, ?, ?, ?)");
                    $log->execute([$user, $id, $medname, $dose_date, $timestamp]);
                } catch (Exception $e) {
                    error_log("Warning: Couldn't log deletion: " . $e->getMessage());
                    // Continue execution, logging is secondary
                }
                
                // Get the updated bank balance
                $totalBankBalance = getTotalBankBalance();
                $formatted_balance = number_format($totalBankBalance, 2, '.', '');
                
                // Return success with updated balance
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Record deleted successfully',
                    'formatted_balance' => $formatted_balance
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'No record found with that ID'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error', 
                'message' => 'No record found with that ID'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error deleting record: " . $e->getMessage());
        echo json_encode([
            'status' => 'error', 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method or missing ID'
    ]);
}
?> 