<?php
session_start();
include '../checkSession.php';
include 'db.php';
include 'med_functions.php'; // Include med_functions for getting bank balance

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

// Get the updated bank balance
try {
    $totalBankBalance = getTotalBankBalance();
    $formatted_balance = number_format($totalBankBalance, 2, '.', '');
    
    // Return the bank balance as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'balance' => $totalBankBalance,
        'formatted_balance' => $formatted_balance
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error getting bank balance: " . $e->getMessage());
    
    // Return error message
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving bank balance'
    ]);
}
?> 