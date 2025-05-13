<?php

include_once 'timezone_config.php';
// fix_missing_timestamps.php - One-time fix for paid notes missing timestamps

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background: #f0f0f0; padding: 15px; border: 1px solid #ccc;'>";
echo "Fixing Missing Timestamps for Paid Notes\n";
echo "--------------------------------------\n\n";

// Database connection
require_once 'api/db.php';

try {
    // Find paid notes with missing timestamps
    $query = "SELECT id FROM on_the_note WHERE is_paid = 1 AND (paid_at IS NULL OR paid_at = '0000-00-00 00:00:00')";
    $stmt = $pdo->query($query);
    $missingTimestamps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($missingTimestamps);
    echo "Found {$count} paid notes with missing timestamps.\n";
    
    if ($count > 0) {
        // Fix missing timestamps
        $pdo->beginTransaction();
        
        $updateQuery = "UPDATE on_the_note SET paid_at = NOW() WHERE is_paid = 1 AND (paid_at IS NULL OR paid_at = '0000-00-00 00:00:00')";
        $pdo->exec($updateQuery);
        
        $pdo->commit();
        echo "Successfully updated timestamps for all paid notes.\n";
        
        // List the updated notes
        $query = "SELECT id, total_amount, paid_at FROM on_the_note WHERE is_paid = 1 ORDER BY paid_at DESC";
        $stmt = $pdo->query($query);
        $updatedNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCurrent paid notes:\n";
        echo "ID\tAmount\t\tPaid At\n";
        echo "----------------------------\n";
        foreach ($updatedNotes as $note) {
            echo "{$note['id']}\t{$note['total_amount']}\t{$note['paid_at']}\n";
        }
    }
    
    echo "\nChecking triggers...\n";
    $triggerQuery = "SHOW TRIGGERS LIKE 'on_the_note'";
    $stmt = $pdo->query($triggerQuery);
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) > 0) {
        echo "Found " . count($triggers) . " triggers for the on_the_note table:\n";
        foreach ($triggers as $trigger) {
            echo "- {$trigger['Trigger']}: {$trigger['Timing']} {$trigger['Event']}\n";
        }
    } else {
        echo "No triggers found for the on_the_note table. Please run setup_notes.php to create the required triggers.\n";
    }
    
    echo "\nDone!\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p><a href='bank.php'>Go to Bank</a></p>"; 