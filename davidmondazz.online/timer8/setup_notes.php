<?php

include_once 'timezone_config.php';
// Display errors for debugging during setup
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background: #f0f0f0; padding: 15px; border: 1px solid #ccc;'>";
echo "Notes System Setup Script\n";
echo "------------------------\n\n";

// Include the database connection file
require_once 'api/db.php';

try {
    // $pdo is already available from api/db.php
    echo "Connected to database successfully.\n\n";

    // Start transaction
    $pdo->beginTransaction();

    // Create on_the_note table with simplified structure
    echo "Creating on_the_note table... ";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS on_the_note (
            id INT AUTO_INCREMENT PRIMARY KEY,
            items_list JSON NOT NULL,
            total_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
            is_paid TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT current_timestamp(),
            paid_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Done.\n";

    // Create indexes
    echo "Creating indexes... ";
    try {
        $pdo->exec("CREATE INDEX idx_note_status ON on_the_note(is_paid);");
        $pdo->exec("CREATE INDEX idx_note_created ON on_the_note(created_at);");
        $pdo->exec("CREATE INDEX idx_note_paid ON on_the_note(paid_at);");
        echo "Done.\n";
    } catch (PDOException $e) {
        // Ignore if indexes already exist
        echo "Indexes already exist or couldn't be created (non-critical).\n";
    }

    // First drop any existing triggers
    echo "Dropping existing triggers... ";
    $pdo->exec("DROP TRIGGER IF EXISTS before_note_payment");
    $pdo->exec("DROP TRIGGER IF EXISTS after_note_payment"); // Keep dropping it in case it exists from old setup
    echo "Done.\n";

    // Create the BEFORE UPDATE trigger (Sets paid_at timestamp)
    echo "Creating BEFORE UPDATE trigger... ";
    $pdo->exec("
        CREATE TRIGGER before_note_payment 
        BEFORE UPDATE ON on_the_note
        FOR EACH ROW
        BEGIN
            IF NEW.is_paid = 1 AND OLD.is_paid = 0 THEN
                SET NEW.paid_at = NOW();
            END IF;
        END
    ");
    echo "Done.\n";

    // NOTE: The AFTER UPDATE trigger (after_note_payment) that previously updated user_progress.bank_balance
    // has been intentionally removed. The balance is now calculated dynamically.

    // Verify the existing triggers
    echo "Verifying triggers...\n";
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'on_the_note'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($triggers as $trigger) {
        echo "- " . $trigger['Trigger'] . ": " . $trigger['Timing'] . " " . $trigger['Event'] . "\n";
    }
    
    // Commit transaction
    $pdo->commit();

    echo "\nSetup completed successfully!\n";
    echo "The notes system is now ready to use.\n";

} catch (PDOException $e) {
    // If anything goes wrong, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "\nError during setup:\n";
    echo $e->getMessage() . "\n";
    echo "\nSetup failed. Please check the error message above.\n";
} finally {
    echo "</pre>";
    echo "<p><a href='fix_missing_timestamps.php'>Fix missing timestamps</a> | <a href='bank.php'>Go to Bank</a></p>";
} 