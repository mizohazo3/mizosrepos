<?php

include_once '../timezone_config.php';
// api/get_bank_data.php (Updated Sections)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging function
function log_bank_action($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

require_once 'db.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Failed to retrieve bank data.'];
$log_file = __DIR__ . '/../logs/bank_api.log';
$transaction_limit = 50; // Maybe show more history now

try {
    log_bank_action("Fetching bank data (combined)...", $log_file);

    // --- Calculate Bank Balance using the shared function ---
    $current_balance = calculateDynamicBalance($pdo);
    log_bank_action("Calculated dynamic balance using function: " . $current_balance, $log_file);

    // --- Fetch Combined Transaction History for Display ---
    $sql_combined = "
        (
            SELECT
                tl.id as log_id,
                'earn' as type,
                t.name as details, -- Timer Name
                tl.timer_id as related_id,
                tl.session_end_time as timestamp,
                tl.duration_seconds as duration,
                tl.earned_amount as amount -- Positive value
            FROM timer_logs tl
            JOIN timers t ON tl.timer_id = t.id
        )
        UNION ALL
        (
            SELECT
                pl.id as log_id,
                'purchase' as type,
                pl.item_name_snapshot as details, -- Item Name
                pl.item_id as related_id,
                pl.purchase_time as timestamp,
                NULL as duration, -- No duration for purchases
                -pl.price_paid as amount -- NEGATIVE value
            FROM purchase_logs pl
        )
        UNION ALL
        (
            SELECT
                otn.id as log_id,
                'note' as type,
                CONCAT('Note Payment (', JSON_LENGTH(otn.items_list), ' items)') as details,
                NULL as related_id,
                otn.paid_at as timestamp,
                NULL as duration,
                -otn.total_amount as amount -- NEGATIVE value
            FROM on_the_note otn
            WHERE otn.is_paid = 1 
        )
        ORDER BY timestamp DESC
        LIMIT :limit
    ";

    // Add debug logging
    log_bank_action("SQL query for combined transactions: " . $sql_combined, $log_file);

    $stmt_combined = $pdo->prepare($sql_combined);
    $stmt_combined->bindValue(':limit', $transaction_limit, PDO::PARAM_INT);
    $stmt_combined->execute();
    $transactions = $stmt_combined->fetchAll(PDO::FETCH_ASSOC);
    log_bank_action("Fetched " . count($transactions) . " combined transactions.", $log_file);

    // Debug log
    $note_query = "SELECT COUNT(*) as note_count FROM on_the_note WHERE is_paid = 1";
    $note_stmt = $pdo->query($note_query);
    $note_count = $note_stmt->fetch(PDO::FETCH_ASSOC)['note_count'];
    log_bank_action("Found {$note_count} paid notes in the database.", $log_file);


    // --- Calculate Total Earned (Optional - Less meaningful now with purchases) ---
    // This calculation might be misleading as it only sums earnings.
    // Maybe calculate net change or remove this.
    $stmt_total_earned = $pdo->query("SELECT SUM(earned_amount) as total_earned FROM timer_logs");
    $total_earned_row = $stmt_total_earned->fetch();
    $total_earned_ever = $total_earned_row ? (float)$total_earned_row['total_earned'] : 0.0;
    log_bank_action("Calculated total earned (from timers only): " . $total_earned_ever, $log_file);

    // --- Prepare Response ---
    $response = [
        'status' => 'success',
        'current_balance' => $current_balance,
        'total_earned_timers' => $total_earned_ever, // Clarify this is timer earnings
        'transactions' => $transactions, // Changed key name
        'transaction_limit_applied' => $transaction_limit
    ];

} catch (Exception $e) {
    // ... (Keep error handling as is) ...
}

echo json_encode($response);
?>