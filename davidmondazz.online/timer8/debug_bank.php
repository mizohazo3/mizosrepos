<?php

include_once 'timezone_config.php';
// Debug script to view all bank transactions including notes

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background: #f0f0f0; padding: 15px; border: 1px solid #ccc;'>";
echo "Bank Transactions Debug\n";
echo "=====================\n\n";

// Database connection
require_once 'api/db.php';

try {
    // 1. Get bank balance
    $balanceQuery = "SELECT bank_balance FROM user_progress WHERE id = 1";
    $balanceStmt = $pdo->query($balanceQuery);
    $balance = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    echo "Current balance: $" . number_format($balance['bank_balance'], 2) . "\n\n";
    
    // 2. Check for paid notes
    $noteQuery = "SELECT * FROM on_the_note WHERE is_paid = 1 ORDER BY paid_at DESC";
    $noteStmt = $pdo->query($noteQuery);
    $notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Paid Notes (" . count($notes) . " found):\n";
    echo "ID\tAmount\t\tPaid At\t\t\tItems\n";
    echo "-------------------------------------------------------\n";
    
    if (count($notes) > 0) {
        foreach ($notes as $note) {
            $itemCount = json_decode($note['items_list'], true);
            $itemCount = is_array($itemCount) ? count($itemCount) : 0;
            
            echo "{$note['id']}\t\${$note['total_amount']}\t{$note['paid_at']}\t{$itemCount} items\n";
        }
    } else {
        echo "No paid notes found.\n";
    }
    
    echo "\n";
    
    // 3. Get raw transaction data (same as in get_bank_data.php)
    $sql_combined = "
        (
            SELECT
                tl.id as log_id,
                'earn' as type,
                t.name as details,
                tl.timer_id as related_id,
                tl.session_end_time as timestamp,
                tl.duration_seconds as duration,
                tl.earned_amount as amount
            FROM timer_logs tl
            JOIN timers t ON tl.timer_id = t.id
        )
        UNION ALL
        (
            SELECT
                pl.id as log_id,
                'purchase' as type,
                pl.item_name_snapshot as details,
                pl.item_id as related_id,
                pl.purchase_time as timestamp,
                NULL as duration,
                -pl.price_paid as amount
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
                -otn.total_amount as amount
            FROM on_the_note otn
            WHERE otn.is_paid = 1
        )
        ORDER BY timestamp DESC
        LIMIT 50
    ";
    
    $transStmt = $pdo->query($sql_combined);
    $transactions = $transStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "All Transactions (" . count($transactions) . " found):\n";
    echo "Type\tID\tDetails\t\t\t\tTimestamp\t\t\tAmount\n";
    echo "------------------------------------------------------------------------------\n";
    
    if (count($transactions) > 0) {
        foreach ($transactions as $tx) {
            $amount = $tx['type'] === 'earn' ? "+$" . $tx['amount'] : "-$" . abs($tx['amount']);
            echo "{$tx['type']}\t{$tx['log_id']}\t" . str_pad(substr($tx['details'], 0, 20), 20) . "\t{$tx['timestamp']}\t{$amount}\n";
        }
    } else {
        echo "No transactions found.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='bank.php'>Go to Bank</a></p>"; 