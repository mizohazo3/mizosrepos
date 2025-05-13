<?php
// Test script to verify the FastMeds prefix functionality

date_default_timezone_set("Africa/Cairo");
require 'db.php';
require 'BankConfig.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing FastMeds Prefix in Purchase Logs</h1>";
echo "<pre>";

// Initialize BankConfig
echo "Initializing BankConfig...\n";
if (BankConfig::initialize()) {
    echo "✅ BankConfig initialized successfully\n";
} else {
    echo "❌ BankConfig initialization failed\n";
    exit;
}

// Test data
$userId = 1;
$medCost = 1.00; // Small amount for testing
$testMedName = "TestMedication_" . date('YmdHis'); // Unique name for easy identification

// Update balance with the test medication
echo "\nTesting updateBalance method with:\n";
echo "User ID: $userId\n";
echo "Medication: $testMedName\n";
echo "Cost: $medCost EGP\n\n";

// Use the BankConfig::updateBalance to add with FastMeds prefix
$result = BankConfig::updateBalance($userId, $medCost, $testMedName);

if ($result) {
    echo "✅ BankConfig::updateBalance completed successfully\n";
} else {
    echo "❌ BankConfig::updateBalance failed\n";
    exit;
}

// Verify the entry was added with the FastMeds prefix
try {
    // Connect to mcgkxyz_timer_app database
    $timerDb = new PDO(
        "mysql:host=localhost;dbname=mcgkxyz_timer_app",
        "mcgkxyz_masterpop",
        "aA0109587045",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "✅ Connected to timer_app database\n";
    
    // Search for the entry with the test medication name
    $query = $timerDb->prepare("SELECT * FROM purchase_logs WHERE item_name_snapshot LIKE ? ORDER BY id DESC LIMIT 1");
    $query->execute(['%' . $testMedName . '%']);
    
    if ($query->rowCount() > 0) {
        $entry = $query->fetch();
        echo "\n✅ Found entry in purchase_logs table:\n";
        
        // Check if it has the FastMeds prefix
        if (strpos($entry['item_name_snapshot'], "FastMeds: ") === 0) {
            echo "✅ Entry has the FastMeds prefix correctly added\n";
        } else {
            echo "❌ Entry does NOT have the FastMeds prefix\n";
        }
        
        echo "\nEntry details:\n";
        print_r($entry);
    } else {
        echo "❌ Entry not found in purchase_logs table\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?> 