<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Function to get total bank balance from mcgkxyz_timer_app database
function getTotalBankBalance() {
    try {
        // Connect to mcgkxyz_timer_app database
        $timer_db = new PDO("mysql:host=localhost;dbname=mcgkxyz_timer_app", "mcgkxyz_masterpop", "aA0109587045");
        $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query to get total bank balance from user_progress table
        $query = $timer_db->prepare("SELECT bank_balance FROM user_progress WHERE id = 1");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        // Return the exact value without any modifications
        if ($result && isset($result['bank_balance'])) {
            return $result['bank_balance'];
        } else {
            return 0; // Default to 0 if no balance found
        }
    } catch (PDOException $e) {
        // Log error for debugging
        error_log("Error fetching bank balance: " . $e->getMessage());
        // Return fallback value if there's an error
        return 0;
    }
}

// Log the delete attempt
error_log("Delete medication record requested at " . date('Y-m-d H:i:s'));

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    error_log("Attempting to delete medication record with ID: " . $id);
    
    try {
        // First get the record details for logging
        $select = $con->prepare("SELECT * FROM medtrack WHERE id = ?");
        $select->execute([$id]);
        $record = $select->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            error_log("Found record: " . print_r($record, true));
            
            // Get the medication name and date for finding corresponding purchase logs
            $medName = $record['medname'];
            $doseDate = $record['dose_date'];
            
            // Try to delete corresponding purchase_logs entries in mcgkxyz_timer_app database
            try {
                // Connect to mcgkxyz_timer_app database
                $timer_db = new PDO("mysql:host=localhost;dbname=mcgkxyz_timer_app", "mcgkxyz_masterpop", "aA0109587045");
                $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Find and delete purchase_logs entries that match the medication name
                // Use the item_name_snapshot field which stores the medication name
                $delete_logs = $timer_db->prepare("DELETE FROM purchase_logs WHERE item_name_snapshot = ?");
                $logs_result = $delete_logs->execute([$medName]);
                
                $affected_logs = $delete_logs->rowCount();
                if ($affected_logs > 0) {
                    error_log("Successfully deleted {$affected_logs} purchase log entries for medication: {$medName}");
                } else {
                    error_log("No purchase log entries found for medication: {$medName}");
                }
            } catch (PDOException $e) {
                // Log error but continue with deletion of medtrack record
                error_log("Error deleting purchase logs: " . $e->getMessage());
            }
            
            // Now delete the medtrack record
            $delete = $con->prepare("DELETE FROM medtrack WHERE id = ?");
            $result = $delete->execute([$id]);
            
            if ($result) {
                error_log("Successfully deleted medication record with ID: " . $id);
                
                // Get the updated bank balance
                $updatedBalance = getTotalBankBalance();
                $formattedBalance = number_format($updatedBalance, 2, '.', '');
                
                // Return success with updated balance
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'balance' => $updatedBalance,
                    'formatted_balance' => $formattedBalance
                ]);
            } else {
                error_log("Failed to delete record, no rows affected. ID: " . $id);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No rows were affected by the delete operation.'
                ]);
            }
        } else {
            error_log("No record found with ID: " . $id);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Record not found with ID: ' . $id
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error deleting record: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid or missing ID parameter");
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or missing ID parameter'
    ]);
}
?>