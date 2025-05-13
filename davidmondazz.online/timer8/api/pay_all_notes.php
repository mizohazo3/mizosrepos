<?php

include_once '../timezone_config.php';
// api/pay_all_notes.php - Endpoint to pay all items on the note

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging Setup
$pay_notes_log_file = __DIR__ . '/_pay_notes_debug.log';
function log_pay_notes($message) {
    global $pay_notes_log_file; // Use the global log file variable
    // Attempt to create the directory if it doesn't exist (though 'api' should exist)
    if (!is_dir(dirname($pay_notes_log_file))) {
        @mkdir(dirname($pay_notes_log_file), 0777, true); // Use @ to suppress warning if dir exists
    }
    file_put_contents($pay_notes_log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}
// End Logging Setup

// Required files
require_once 'db.php'; // Make sure this path is correct and db.php includes calculateDynamicBalance if used below

// Function calculateDynamicBalance needs to be available if used
// Assuming it's in db.php or included elsewhere. If not, it needs to be defined or included.
if (!function_exists('calculateDynamicBalance')) {
    // Fallback or include statement needed if calculateDynamicBalance is not globally available
    // For now, we'll assume it's available via db.php
    // function calculateDynamicBalance($pdo) { /* ... implementation ... */ return 0.0; } // Placeholder if needed
}


// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Default response
$response = [
    'status' => 'error',
    'message' => 'An unexpected error occurred.'
];

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Only POST requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
}

try {
    log_pay_notes("Starting transaction for pay_all_notes.");
    $pdo->beginTransaction();

    // 1. Get the latest notes that are not paid
    $sql = "SELECT * FROM on_the_note WHERE is_paid = 0 ORDER BY created_at DESC LIMIT 1";
    log_pay_notes("Executing query: " . $sql);
    $stmt = $pdo->query($sql);
    $noteRow = $stmt->fetch(PDO::FETCH_ASSOC);
    log_pay_notes("Query for unpaid note executed. Result: " . json_encode($noteRow));

    if (!$noteRow) {
        log_pay_notes("No unpaid notes found. Exiting.");
        $response['message'] = 'No items on your note to pay.';
        $response['status'] = 'success'; // Treat as success with no action needed
        // Note: No commit needed if no transaction was effectively started by modification queries
        echo json_encode($response);
    }

    log_pay_notes("Found unpaid note with ID: " . $noteRow['id']);

    // 2. Get current bank balance
    // Using calculateDynamicBalance for consistency if it's the standard way
    $currentBalance = calculateDynamicBalance($pdo);
    log_pay_notes("Current dynamic bank balance fetched: " . $currentBalance);

    // 3. Check if there's enough balance
    $noteTotal = (float)$noteRow['total_amount'];
    log_pay_notes("Note total amount: " . $noteTotal);

    if ($currentBalance < $noteTotal) {
        log_pay_notes("Insufficient funds. Required: " . $noteTotal . ", Available: " . $currentBalance);
        $response['message'] = 'Insufficient funds. Current balance: $' . number_format($currentBalance, 2);
        $response['current_balance'] = $currentBalance;
        $response['required_amount'] = $noteTotal;
        // Need to commit/rollback depending on PDO behavior without modifying queries, safer to just rollback if transaction was started.
        if ($pdo->inTransaction()) {
             log_pay_notes("Rolling back transaction due to insufficient funds.");
             $pdo->rollBack();
        }
        echo json_encode($response);
    }

    // 4. Mark the note as paid (This script handles paid_at now)
    // Use PHP's DateTime for consistent paid_at timestamp
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');
    log_pay_notes("Generated paid_at timestamp: " . $now_str);

    $updateSql = "UPDATE on_the_note SET is_paid = 1, paid_at = :paid_at WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    log_pay_notes("Executing update query: " . $updateSql . " with id=" . $noteRow['id'] . " and paid_at=" . $now_str);

    $updateExecuteResult = $updateStmt->execute([':id' => $noteRow['id'], ':paid_at' => $now_str]);
    log_pay_notes("Update execute result: " . ($updateExecuteResult ? 'true' : 'false'));
    log_pay_notes("Rows affected by update: " . $updateStmt->rowCount());

    if (!$updateExecuteResult) {
         // This catch block might not be triggered if execute returns false instead of throwing
         $errorInfo = $updateStmt->errorInfo();
         throw new Exception("Failed to update note as paid."); // Force exception to rollback
    }

    // 5. Bank balance update is handled by the after_note_payment trigger.
    // The trigger uses NEW.total_amount which should be correct.

    // Commit the transaction
    log_pay_notes("Committing transaction.");
    $commitResult = $pdo->commit();
    log_pay_notes("Commit result: " . ($commitResult ? 'true' : 'false'));

    if (!$commitResult) {
        // If commit fails, the DB might be in an inconsistent state or the connection lost.
        log_pay_notes("!!! CRITICAL: Transaction commit failed!");
        throw new Exception("Transaction commit failed.");
    }

    log_pay_notes("Transaction committed successfully.");

    // Build response with details of what was purchased
    // Re-fetch the row after commit to see final state, including paid_at
    $reFetchStmt = $pdo->prepare("SELECT * FROM on_the_note WHERE id = :id");
    $reFetchStmt->execute([':id' => $noteRow['id']]);
    $updatedNoteRow = $reFetchStmt->fetch(PDO::FETCH_ASSOC);
    log_pay_notes("Re-fetched updated note row: " . json_encode($updatedNoteRow));

    // Calculate the new balance *after* the trigger has run and commit succeeded
    $finalBalance = calculateDynamicBalance($pdo);
    log_pay_notes("Final dynamic balance calculated after commit: " . $finalBalance);


    $itemsList = json_decode($noteRow['items_list'], true); // Use original items list from before update
    $itemCount = is_array($itemsList) ? count($itemsList) : 0;

    $response = [
        'status' => 'success',
        'message' => 'Successfully paid for ' . $itemCount . ' items.',
        'paid_amount' => $noteTotal, // Still the total amount of the note paid
        'new_balance' => $finalBalance, // Report the balance after trigger
        'items' => $itemsList, // Items list remains the same
        'paid_at_recorded' => $updatedNoteRow['paid_at'] ?? null // Report the actual value saved
    ];
    log_pay_notes("Response prepared: " . json_encode($response));

} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    log_pay_notes("CAUGHT PDOException: " . $error_message);
    if ($pdo->inTransaction()) {
        log_pay_notes("Rolling back transaction due to PDOException.");
        $pdo->rollBack();
    }
    $response['message'] = $error_message;
    http_response_code(500);
    error_log("Pay All Notes PDO Error: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = 'Error: ' . $e->getMessage();
    log_pay_notes("CAUGHT Exception: " . $error_message);
    if ($pdo->inTransaction()) {
        log_pay_notes("Rolling back transaction due to Exception.");
        $pdo->rollBack();
    }
    $response['message'] = $error_message;
    http_response_code(500);
    error_log("Pay All Notes Error: " . $e->getMessage());
}

// Return JSON response
log_pay_notes("Returning JSON response.");
echo json_encode($response);

?>