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

header('Content-Type: application/json');

// Check if request is properly formatted (must be POST with ids)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Only POST is supported.'
    ]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or missing ids parameter'
    ]);
    exit;
}

$ids = array_map('intval', $data['ids']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

error_log("Bulk delete requested for IDs: " . implode(', ', $ids));

try {
    $con->beginTransaction();
    
    // First get the record details for logging and to find corresponding purchase logs
    $select = $con->prepare("SELECT * FROM medtrack WHERE id IN ($placeholders)");
    $select->execute($ids);
    $records = $select->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($records)) {
        error_log("Found " . count($records) . " records to delete");
        
        // Get the medication names for finding corresponding purchase logs
        $medNames = array_unique(array_column($records, 'medname'));
        
        // Try to delete corresponding purchase_logs entries in mcgkxyz_timer_app database
        try {
            // Connect to mcgkxyz_timer_app database
            $timer_db = new PDO("mysql:host=localhost;dbname=mcgkxyz_timer_app", "mcgkxyz_masterpop", "aA0109587045");
            $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Build placeholders for medication names
            $namesPH = implode(',', array_fill(0, count($medNames), '?'));
            
            // Delete all purchase_logs entries that match any of the medication names
            $delete_logs = $timer_db->prepare("DELETE FROM purchase_logs WHERE item_name_snapshot IN ($namesPH)");
            $logs_result = $delete_logs->execute(array_values($medNames));
            
            $affected_logs = $delete_logs->rowCount();
            error_log("Deleted $affected_logs purchase log entries");
            
        } catch (PDOException $e) {
            // Log error but continue with deletion of medtrack records
            error_log("Error deleting purchase logs: " . $e->getMessage());
        }
        
        // Now delete all the medtrack records in a single query
        $delete = $con->prepare("DELETE FROM medtrack WHERE id IN ($placeholders)");
        $result = $delete->execute($ids);
        
        $affected_rows = $delete->rowCount();
        
        if ($affected_rows > 0) {
            $con->commit();
            error_log("Successfully deleted $affected_rows medication records");
            
            // Get the updated bank balance
            $updatedBalance = getTotalBankBalance();
            $formattedBalance = number_format($updatedBalance, 2, '.', '');
            
            // Return success with updated balance
            echo json_encode([
                'status' => 'success',
                'deleted_count' => $affected_rows,
                'balance' => $updatedBalance,
                'formatted_balance' => $formattedBalance,
                'deleted_ids' => $ids
            ]);
        } else {
            $con->rollBack();
            error_log("Failed to delete records, no rows affected.");
            echo json_encode([
                'status' => 'error',
                'message' => 'No rows were affected by the delete operation.'
            ]);
        }
    } else {
        $con->rollBack();
        error_log("No records found with the provided IDs");
        echo json_encode([
            'status' => 'error',
            'message' => 'No records found with the provided IDs'
        ]);
    }
} catch (PDOException $e) {
    $con->rollBack();
    error_log("Database error deleting records: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
