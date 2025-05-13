<?php
// update_balance_cron.php
//
// This script updates the balance in user_progress table based on the total earnings,
// purchases, and notes from the timer_app database.
//
// It should be scheduled to run via a cron job.
// Example cron entry (runs every 5 minutes):
// */5 * * * * /usr/bin/php /path/to/update_balance_cron.php >> /path/to/cron_log.txt 2>&1

// Set timezone
date_default_timezone_set("Africa/Cairo");

// Start time for performance tracking
$startTime = microtime(true);

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Path to log file - adjust this as needed
$logFile = __DIR__ . '/logs/balance_cron.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] {$message}\n";
    
    // Create logs directory if it doesn't exist
    $logsDir = dirname($logFile);
    if (!is_dir($logsDir)) {
        mkdir($logsDir, 0755, true);
    }
    
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    
    // If running from command line, output to console too
    if (php_sapi_name() === 'cli') {
        echo $formattedMessage;
    }
}

// Log start of script
logMessage("Starting balance update cron job");

try {
    // Timer app database configuration
    $host = 'localhost';
    $dbname = 'mcgkxyz_timer_app';
    $username = 'mcgkxyz_masterpop';
    $password = 'aA0109587045';
    $db_charset = 'utf8mb4';
    
    // Establish PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$db_charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Begin transaction to ensure data consistency
    $pdo->beginTransaction();
    
    // 1. Calculate Total Income from Timers
    $stmt_income = $pdo->query("SELECT SUM(earned_amount) as total_income FROM timer_logs");
    $income_row = $stmt_income->fetch();
    $total_income = $income_row && !is_null($income_row['total_income']) ? (float)$income_row['total_income'] : 0.0;

    // 2. Calculate Total Purchases
    $stmt_purchases = $pdo->query("SELECT SUM(price_paid) as total_purchases FROM purchase_logs");
    $purchases_row = $stmt_purchases->fetch();
    $total_purchases = $purchases_row && !is_null($purchases_row['total_purchases']) ? (float)$purchases_row['total_purchases'] : 0.0;

    // 3. Calculate Total Paid Notes
    $stmt_notes = $pdo->query("SELECT SUM(total_amount) as total_notes FROM on_the_note WHERE is_paid = 1");
    $notes_row = $stmt_notes->fetch();
    $total_paid_notes = $notes_row && !is_null($notes_row['total_notes']) ? (float)$notes_row['total_notes'] : 0.0;

    // 4. Calculate Current Balance
    $current_balance = $total_income - $total_purchases - $total_paid_notes;
    
    // Round to 2 decimal places
    $current_balance = round($current_balance, 2);
    
    // Log the calculated balance components
    logMessage("Calculated balance components: Income: $total_income, Purchases: $total_purchases, Notes: $total_paid_notes");
    logMessage("Calculated balance: $current_balance");
    
    // Check if user_progress table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'user_progress'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the user_progress table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_progress` (
                `id` int(10) UNSIGNED NOT NULL DEFAULT 1,
                `bank_balance` decimal(15,4) NOT NULL DEFAULT 0.0000,
                PRIMARY KEY (`id`)
            )
        ");
        logMessage("Created user_progress table as it did not exist");
    }
    
    // Check if we have a record in user_progress table
    $stmt = $pdo->query("SELECT bank_balance FROM user_progress WHERE id = 1");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        // Get the existing balance for logging
        $row = $stmt->fetch();
        $old_balance = (float)$row['bank_balance'];
        
        // Update existing record
        $updateStmt = $pdo->prepare("UPDATE user_progress SET bank_balance = ? WHERE id = 1");
        $updateStmt->execute([$current_balance]);
        
        logMessage("Updated balance: Old = $old_balance, New = $current_balance, Difference = " . ($current_balance - $old_balance));
    } else {
        // Insert new record
        $insertStmt = $pdo->prepare("INSERT INTO user_progress (id, bank_balance) VALUES (1, ?)");
        $insertStmt->execute([$current_balance]);
        
        logMessage("Inserted new balance record: $current_balance");
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Calculate execution time
    $executionTime = round(microtime(true) - $startTime, 4);
    logMessage("Balance update completed successfully in {$executionTime} seconds");
    
} catch (PDOException $e) {
    // Rollback transaction on database error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logMessage("Database Error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    logMessage("Error: " . $e->getMessage());
    exit(1);
}
?> 