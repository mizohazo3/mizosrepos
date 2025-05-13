<?php
// test_purchase_log.php - Test script to verify purchase_logs functionality
// This script tests the direct insertion into purchase_logs table

date_default_timezone_set("Africa/Cairo");
require 'db.php';
require 'BankConfig.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

echo "<h1>Testing Purchase Log Functionality</h1>";
echo "<pre>";

// Initialize BankConfig
echo "Initializing BankConfig...\n";
if (BankConfig::initialize()) {
    echo "✅ BankConfig initialized successfully\n";
} else {
    echo "❌ BankConfig initialization failed\n";
    exit;
}

// Test user ID and medication cost
$userId = 1;
$medCost = 9.60; // Test cost in EGP
$testMedName = "Test Medication";
$testMedId = 999; // This is just for reference, we'll need to use a valid marketplace_items id

// Get current USD conversion rate
$rate = BankConfig::getExchangeRate();
$medCostUSD = BankConfig::convertToUSD($medCost);

echo "\nTesting updateBalance method with:\n";
echo "User ID: $userId\n";
echo "Medication: $testMedName (ID: $testMedId)\n";
echo "Cost: $medCost EGP (= $medCostUSD USD at rate 1 USD = $rate EGP)\n\n";

// Test direct updateBalance method
$result = BankConfig::updateBalance($userId, $medCost, $testMedName);

if ($result) {
    echo "✅ BankConfig::updateBalance completed successfully\n";
} else {
    echo "❌ BankConfig::updateBalance failed\n";
}

echo "\nTesting direct database connection approach:\n";

// Test direct database connection to timer_app database
try {
    $timerDbConfig = [
        'host' => 'localhost',
        'dbname' => 'mcgkxyz_timer_app',
        'username' => 'mcgkxyz_masterpop',
        'password' => 'aA0109587045'
    ];
    
    echo "Connecting to mcgkxyz_timer_app database...\n";
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
    echo "✅ Connected to timer_app database\n";
    
    // Check if purchase_logs table exists
    echo "Checking if purchase_logs table exists...\n";
    $checkTable = $timerDb->query("SHOW TABLES LIKE 'purchase_logs'");
    
    if ($checkTable->rowCount() > 0) {
        echo "✅ purchase_logs table exists\n";
        
        // Get the structure of purchase_logs table
        echo "Checking purchase_logs table structure...\n";
        $columns = $timerDb->query("DESCRIBE purchase_logs");
        echo "Column list for purchase_logs:\n";
        while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$column['Field']} ({$column['Type']}, Null: {$column['Null']})\n";
        }
        
        // Check for foreign key constraints
        echo "\nChecking for foreign key constraints on purchase_logs table...\n";
        $constraints = $timerDb->query("
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_NAME = 'purchase_logs'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        while ($constraint = $constraints->fetch(PDO::FETCH_ASSOC)) {
            echo "- Foreign key constraint: {$constraint['CONSTRAINT_NAME']} references {$constraint['REFERENCED_TABLE_NAME']}({$constraint['REFERENCED_COLUMN_NAME']})\n";
        }
        
        // Check marketplace_items table for valid IDs we can use
        echo "\nChecking marketplace_items table for valid IDs...\n";
        $validItems = $timerDb->query("SELECT id, name FROM marketplace_items LIMIT 10");
        
        if ($validItems->rowCount() > 0) {
            echo "Found valid marketplace items:\n";
            $validItemIds = [];
            while ($item = $validItems->fetch(PDO::FETCH_ASSOC)) {
                echo "- ID: {$item['id']}, Name: {$item['name']}\n";
                $validItemIds[] = $item['id'];
            }
            
            if (count($validItemIds) > 0) {
                $validItemId = $validItemIds[0]; // Use the first valid ID
                echo "\nUsing item_id = {$validItemId} for test insertion\n";
                
                // Try to insert a test record
                $now = date('Y-m-d H:i:s');
                
                echo "\nInserting test record into purchase_logs...\n";
                $sql = "INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) 
                        VALUES (?, ?, ?, ?)";
                
                $itemName = $testMedName; // Use actual medication name
                
                $insertPurchase = $timerDb->prepare($sql);
                $insertPurchase->execute([
                    $validItemId, // Use valid item ID from marketplace_items
                    $itemName,
                    $medCostUSD, // Use USD price
                    $now
                ]);
                
                $insertedRows = $insertPurchase->rowCount();
                if ($insertedRows > 0) {
                    echo "✅ Successfully inserted test record into purchase_logs\n";
                } else {
                    echo "⚠️ Insert statement executed but no rows were affected\n";
                }
                
                // Verify the insertion by querying the table
                echo "\nVerifying insertion by querying purchase_logs...\n";
                $verifyQuery = $timerDb->prepare("SELECT * FROM purchase_logs WHERE item_name_snapshot = ? ORDER BY id DESC LIMIT 1");
                $verifyQuery->execute([$itemName]);
                
                if ($verifyQuery->rowCount() > 0) {
                    $record = $verifyQuery->fetch();
                    echo "✅ Found record in database:\n";
                    print_r($record);
                } else {
                    echo "❌ Could not find test record in database\n";
                }
            } else {
                echo "❌ No valid marketplace items found\n";
                
                // Create a new marketplace item
                echo "\nCreating a new marketplace item for testing...\n";
                $createItem = $timerDb->prepare("
                    INSERT INTO marketplace_items 
                    (name, description, price, is_active, stock) 
                    VALUES 
                    ('Test Medication Item', 'Test item for medication purchases', 0, 1, -1)
                ");
                $createItem->execute();
                $newItemId = $timerDb->lastInsertId();
                
                if ($newItemId) {
                    echo "✅ Created new marketplace item with ID: {$newItemId}\n";
                    
                    // Now try the insert again with the new item ID
                    echo "\nRetrying insert with new marketplace item ID...\n";
                    $now = date('Y-m-d H:i:s');
                    $sql = "INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) 
                            VALUES (?, ?, ?, ?)";
                    
                    $insertPurchase = $timerDb->prepare($sql);
                    $insertPurchase->execute([
                        $newItemId,
                        $itemName,
                        $medCostUSD,
                        $now
                    ]);
                    
                    $insertedRows = $insertPurchase->rowCount();
                    if ($insertedRows > 0) {
                        echo "✅ Successfully inserted test record into purchase_logs\n";
                    } else {
                        echo "⚠️ Insert statement executed but no rows were affected\n";
                    }
                } else {
                    echo "❌ Failed to create new marketplace item\n";
                }
            }
        } else {
            echo "❌ No marketplace items found. Cannot insert into purchase_logs due to foreign key constraint.\n";
        }
    } else {
        echo "❌ purchase_logs table does not exist\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<p>Check php_errors.log for detailed error messages.</p>";
?> 