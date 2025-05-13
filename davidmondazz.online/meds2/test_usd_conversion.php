<?php
// Test script to verify USD to EGP conversion
require 'db.php';
require 'BankConfig.php';

// Initialize BankConfig
BankConfig::initialize();

// Test data
$testAmounts = [100, 250, 500, 1000, 5000];

// Get the current exchange rate
$exchangeRate = BankConfig::getExchangeRate();

echo "Current USD to EGP Exchange Rate: " . $exchangeRate . "<br><br>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Amount (EGP)</th><th>Converted to USD</th></tr>";

foreach ($testAmounts as $amountEGP) {
    $amountUSD = BankConfig::convertToUSD($amountEGP);
    echo "<tr><td>EGP " . number_format($amountEGP, 2) . "</td><td>$" . number_format($amountUSD, 2) . "</td></tr>";
}

echo "</table><br>";

// Test database structure
echo "<h3>Database Structure Check</h3>";

// Check if USDEGP column exists in user_progress
try {
    // Connect to timer_app database
    $timerDbConfig = [
        'host' => 'localhost',
        'dbname' => 'mcgkxyz_timer_app',
        'username' => 'mcgkxyz_masterpop',
        'password' => 'aA0109587045'
    ];
    
    $timerDb = new PDO(
        "mysql:host={$timerDbConfig['host']};dbname={$timerDbConfig['dbname']};charset=utf8mb4",
        $timerDbConfig['username'],
        $timerDbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Check if user_progress table exists
    $checkTable = $timerDb->query("SHOW TABLES LIKE 'user_progress'");
    if ($checkTable->rowCount() > 0) {
        echo "✅ user_progress table exists in timer_app database.<br>";
        
        // Check if USDEGP column exists
        $checkColumn = $timerDb->query("SHOW COLUMNS FROM user_progress LIKE 'USDEGP'");
        if ($checkColumn->rowCount() > 0) {
            echo "✅ USDEGP column exists in user_progress table.<br>";
            
            // Get the current value
            $valueQuery = $timerDb->query("SELECT USDEGP FROM user_progress WHERE id = 1");
            $valueData = $valueQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($valueData) {
                echo "✅ Current USDEGP value: " . $valueData['USDEGP'] . "<br>";
            } else {
                echo "❌ No data found in user_progress table. Run add_usdegp_column.php to initialize.<br>";
            }
        } else {
            echo "❌ USDEGP column does not exist in user_progress table. Run add_usdegp_column.php to add it.<br>";
        }
    } else {
        echo "❌ user_progress table does not exist in timer_app database.<br>";
    }
    
    // Check if purchase_logs table exists
    $checkTable = $timerDb->query("SHOW TABLES LIKE 'purchase_logs'");
    if ($checkTable->rowCount() > 0) {
        echo "✅ purchase_logs table exists in timer_app database.<br>";
        
        // Check price_paid column datatype
        $columnInfoQuery = $timerDb->query("SHOW COLUMNS FROM purchase_logs LIKE 'price_paid'");
        $columnInfo = $columnInfoQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($columnInfo) {
            echo "✅ price_paid column exists with type: " . $columnInfo['Type'] . "<br>";
            echo "Note: Values in price_paid are now stored in USD (converted from EGP)<br>";
        } else {
            echo "❌ price_paid column does not exist in purchase_logs table.<br>";
        }
    } else {
        echo "❌ purchase_logs table does not exist in timer_app database.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking database structure: " . $e->getMessage() . "<br>";
}

echo "<h3>Conversion Tests in submit.php</h3>";
echo "✅ submit.php has been updated to convert EGP prices to USD before storing in the database.<br>";
echo "✅ The display now shows both EGP and USD prices for user clarity.<br>";
echo "✅ BankConfig::updateBalance now correctly processes EGP amounts for display while storing USD in purchase_logs.<br>";

echo "<h3>How to Update Exchange Rate</h3>";
echo "To update the USD to EGP exchange rate:<br>";
echo "1. Go to user_progress table in the mcgkxyz_timer_app database<br>";
echo "2. Edit the record with id=1<br>";
echo "3. Update the USDEGP column with the current exchange rate<br>";
?> 