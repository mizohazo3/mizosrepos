<?php
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

    // --- Get Bank Balance --- (Keep as is)
    $stmt_balance = $pdo->prepare("SELECT bank_balance FROM user_progress WHERE id = 1");
    $stmt_balance->execute();
    $balance_row = $stmt_balance->fetch();
    $current_balance = $balance_row ? (float)$balance_row['bank_balance'] : 0.0;
    log_bank_action("Fetched balance: " . $current_balance, $log_file);


    // --- Combine Timer Earnings and Purchases ---
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
        ORDER BY timestamp DESC
        LIMIT :limit
    ";

    $stmt_combined = $pdo->prepare($sql_combined);
    $stmt_combined->bindValue(':limit', $transaction_limit, PDO::PARAM_INT);
    $stmt_combined->execute();
    $transactions = $stmt_combined->fetchAll(PDO::FETCH_ASSOC);
    log_bank_action("Fetched " . count($transactions) . " combined transactions.", $log_file);


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