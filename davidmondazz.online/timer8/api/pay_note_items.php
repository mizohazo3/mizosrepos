<?php
// --- Force Error Reporting & Logging ---
ini_set('display_errors', 0); // Keep errors hidden from client for security
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log'); // Explicitly set log path relative to this script
error_reporting(E_ALL);
error_log("--- pay_note_items.php started ---"); // Log start immediately
// --- End Force Error Reporting ---

require_once 'db.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// --- New Debugging Line ---
error_log("Pay note script reached before try block.");
// --- End New Debugging Line ---

try {
    // --- Debugging Start ---
    $raw_input = file_get_contents('php://input');
    error_log("Pay note raw input: " . $raw_input);
    $input = json_decode($raw_input, true);
    error_log("Pay note decoded input: " . print_r($input, true));
    // --- Debugging End ---

    // Get user_session_id from input
    $user_session_id = $input['user_session_id'] ?? null;

    if (!$user_session_id) {
        throw new Exception('User session ID is required');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get current bank balance
    $stmt = $pdo->query("SELECT bank_balance FROM user_progress WHERE id = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_balance = $result ? (float)$result['bank_balance'] : 0;

    // Get all pending note items with their details
    $stmt = $pdo->prepare("
        SELECT n.*, m.name, m.price
        FROM note_items n
        JOIN marketplace_items m ON n.item_id = m.id
        WHERE n.user_session_id = :user_session_id
        AND n.status = 'pending'
    ");
    $stmt->execute([':user_session_id' => $user_session_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception('No pending items found in note');
    }

    // Calculate total cost
    $total_cost = 0;
    $item_names = [];
    foreach ($items as $item) {
        $total_cost += (float)$item['price'];
        $item_names[] = $item['name'];
    }

    // Validate frontend cost against backend calculation (allow small tolerance)
    // Removed frontend cost validation as backend is source of truth

    if ($total_cost > $current_balance) {
        throw new Exception('Insufficient funds. Need ' . number_format($total_cost, 2) . ' but only have ' . number_format($current_balance, 2));
    }

    // Update bank balance
    $new_balance = $current_balance - $total_cost;
    $stmt = $pdo->prepare("UPDATE user_progress SET bank_balance = :new_balance WHERE id = 1");
    if (!$stmt->execute([':new_balance' => $new_balance])) {
        throw new Exception('Failed to update bank balance');
    }

    // Update note items to paid status
    $stmt = $pdo->prepare("
        UPDATE note_items 
        SET status = 'paid' 
        WHERE user_session_id = :user_session_id 
        AND status = 'pending'
    ");
    if (!$stmt->execute([':user_session_id' => $user_session_id])) {
        throw new Exception('Failed to update note items status');
    }

    // Record the transaction in timer_logs
    $items_summary = implode(', ', $item_names);
    $stmt = $pdo->prepare("
        INSERT INTO timer_logs 
        (timer_id, session_start_time, session_end_time, duration_seconds, earned_amount, transaction_type, transaction_details) 
        VALUES 
        (0, NOW(), NOW(), 0, :amount, 'note_payment', :details)
    ");
    
    if (!$stmt->execute([
        ':amount' => -$total_cost, // Negative amount since it's a payment
        ':details' => "Paid note items: " . $items_summary
    ])) {
        throw new Exception('Failed to record transaction');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Note items paid successfully',
        'total_paid' => $total_cost,
        'new_balance' => $new_balance,
        'items_paid' => $items_summary
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Pay note error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
