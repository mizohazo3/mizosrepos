<?php
require_once 'db.php';

// Add price fields to medlist table if they don't exist
try {
    echo "<h2>Firebase Integration Installation</h2>";
    
    // Check if price column exists in medlist
    $priceColumnCheck = $con->query("SHOW COLUMNS FROM `medlist` LIKE 'price'");
    if ($priceColumnCheck->rowCount() == 0) {
        $con->exec("ALTER TABLE `medlist` ADD COLUMN `price` decimal(10,2) DEFAULT NULL COMMENT 'Medication package price'");
        echo "<p>✅ Added 'price' column to medlist table</p>";
    } else {
        echo "<p>ℹ️ 'price' column already exists in medlist table</p>";
    }
    
    // Check if doses_per_package column exists
    $dosesColumnCheck = $con->query("SHOW COLUMNS FROM `medlist` LIKE 'doses_per_package'");
    if ($dosesColumnCheck->rowCount() == 0) {
        $con->exec("ALTER TABLE `medlist` ADD COLUMN `doses_per_package` int(11) DEFAULT NULL COMMENT 'Number of doses in a package'");
        echo "<p>✅ Added 'doses_per_package' column to medlist table</p>";
    } else {
        echo "<p>ℹ️ 'doses_per_package' column already exists in medlist table</p>";
    }
    
    // Check if Firebase configuration file exists
    if (file_exists('firebase_config.php')) {
        echo "<p>✅ Firebase configuration file exists</p>";
    } else {
        echo "<p>❌ Firebase configuration file is missing! Please make sure firebase_config.php exists in the root directory.</p>";
    }
    
    // Check if service account file exists
    if (file_exists('secret/firebase-service-account.json')) {
        echo "<p>✅ Firebase service account file exists</p>";
    } else {
        echo "<p>❌ Firebase service account file is missing! Please make sure secret/firebase-service-account.json is configured with your credentials.</p>";
    }
    
    // Check if composer dependencies are installed
    if (file_exists('vendor/autoload.php')) {
        echo "<p>✅ Firebase PHP SDK is installed</p>";
    } else {
        echo "<p>❌ Firebase PHP SDK is not installed! Please run 'composer install' in the project directory.</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure you have valid Firebase service account credentials in secret/firebase-service-account.json</li>";
    echo "<li>Update the price and doses_per_package in your medication list</li>";
    echo "<li>Try taking a medication to see the cost deduction in Firebase</li>";
    echo "<li>Visit the <a href='balance.php'>Balance Management</a> page to add funds or check transactions</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>Installation Error</h2>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
}
?> 