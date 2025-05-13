<?php
// Database connection parameters
$host = 'localhost';
$user = 'mcgkxyz_masterpop';
$password = 'aA0109587045';
$db_name = 'mcgkxyz_meds2';

echo "Starting script to add dollar_dose_price column...\n";

try {
    // Connect to database directly
    echo "Connecting to database: $db_name\n";
    $con = new PDO("mysql:host=$host;dbname=$db_name", $user, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully.\n";
    
    // Check if the med_prices table exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'med_prices'");
    if ($tableCheck->rowCount() == 0) {
        echo "ERROR: The med_prices table does not exist in the database.\n";
        exit;
    }
    echo "The med_prices table exists.\n";
    
    // Check if the custom_price column exists
    echo "Checking if custom_price column exists...\n";
    $customPriceCheck = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'custom_price'");
    
    if ($customPriceCheck->rowCount() == 0) {
        echo "The custom_price column does not exist. Adding it now...\n";
        $con->exec("ALTER TABLE `med_prices` ADD COLUMN `custom_price` varchar(50) DEFAULT NULL AFTER `price_per_dose`");
        echo "Custom_price column added successfully.\n";
    } else {
        echo "The custom_price column already exists.\n";
    }
    
    // Check if the dollar_dose_price column already exists
    echo "Checking if dollar_dose_price column exists...\n";
    $columnCheck = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'dollar_dose_price'");
    
    if ($columnCheck->rowCount() == 0) {
        echo "Column does not exist. Adding it now...\n";
        // Column doesn't exist, add it
        $con->exec("ALTER TABLE `med_prices` ADD COLUMN `dollar_dose_price` decimal(10,2) DEFAULT NULL AFTER `custom_price`");
        echo "Column added successfully.\n";
        
        // Update existing records with calculated USD values
        echo "Retrieving existing records to update...\n";
        $records = $con->query("SELECT id, price_per_dose FROM med_prices");
        $totalRecords = $records->rowCount();
        echo "Found $totalRecords records to process.\n";
        
        // Get the exchange rate from user_progress table in timer app database
        $exchangeRate = 50.59; // Default exchange rate
        echo "Getting current exchange rate...\n";
        try {
            $timerDb = new PDO(
                "mysql:host=localhost;dbname=mcgkxyz_timer_app",
                "mcgkxyz_masterpop",
                "aA0109587045",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $rateQuery = $timerDb->query("SELECT USDEGP FROM user_progress WHERE id = 1");
            $rateData = $rateQuery->fetch(PDO::FETCH_ASSOC);
            if ($rateData && isset($rateData['USDEGP'])) {
                $exchangeRate = (float)$rateData['USDEGP'];
                echo "Retrieved exchange rate: $exchangeRate\n";
            } else {
                echo "Could not find exchange rate in database, using default: $exchangeRate\n";
            }
        } catch (PDOException $e) {
            echo "Warning: Could not get exchange rate from timer database: " . $e->getMessage() . "\n";
            echo "Using default exchange rate of " . $exchangeRate . "\n";
        }
        
        // Function to convert EGP to USD
        function convertToUSD($egpAmount, $rate) {
            return round($egpAmount / $rate, 2);
        }
        
        // Update each record with USD value
        $updateCount = 0;
        echo "Updating records with USD values...\n";
        while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
            $egpPrice = $row['price_per_dose'];
            
            if ($egpPrice > 0) {
                $usdPrice = convertToUSD($egpPrice, $exchangeRate);
                
                $updateStmt = $con->prepare("UPDATE med_prices SET dollar_dose_price = ? WHERE id = ?");
                $updateStmt->execute([$usdPrice, $row['id']]);
                $updateCount++;
                echo "Updated record ID {$row['id']} - EGP: $egpPrice -> USD: $usdPrice\n";
            } else {
                echo "Skipped record ID {$row['id']} - No valid price found\n";
            }
        }
        
        echo "The dollar_dose_price column has been added to med_prices table.\n";
        echo "Updated $updateCount out of $totalRecords records with USD values using exchange rate: $exchangeRate\n";
    } else {
        echo "The dollar_dose_price column already exists in med_prices table.\n";
        
        // Check if we need to update any NULL values
        $nullRecords = $con->query("SELECT COUNT(*) FROM med_prices WHERE dollar_dose_price IS NULL AND price_per_dose > 0");
        $nullCount = $nullRecords->fetchColumn();
        
        if ($nullCount > 0) {
            echo "Found $nullCount records with NULL dollar_dose_price values. Updating them automatically.\n";
            
            // Get the exchange rate
            $exchangeRate = 50.59; // Default exchange rate
            try {
                $timerDb = new PDO(
                    "mysql:host=localhost;dbname=mcgkxyz_timer_app",
                    "mcgkxyz_masterpop",
                    "aA0109587045",
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                $rateQuery = $timerDb->query("SELECT USDEGP FROM user_progress WHERE id = 1");
                $rateData = $rateQuery->fetch(PDO::FETCH_ASSOC);
                if ($rateData && isset($rateData['USDEGP'])) {
                    $exchangeRate = (float)$rateData['USDEGP'];
                    echo "Retrieved exchange rate: $exchangeRate\n";
                }
            } catch (PDOException $e) {
                echo "Warning: Could not get exchange rate from timer database: " . $e->getMessage() . "\n";
                echo "Using default exchange rate of " . $exchangeRate . "\n";
            }
            
            // Function to convert EGP to USD
            if (!function_exists('convertToUSD')) {
                function convertToUSD($egpAmount, $rate) {
                    return round($egpAmount / $rate, 2);
                }
            }
            
            // Update NULL values
            $nullRecords = $con->query("SELECT id, price_per_dose FROM med_prices WHERE dollar_dose_price IS NULL AND price_per_dose > 0");
            $updateCount = 0;
            
            while ($row = $nullRecords->fetch(PDO::FETCH_ASSOC)) {
                $egpPrice = $row['price_per_dose'];
                
                if ($egpPrice > 0) {
                    $usdPrice = convertToUSD($egpPrice, $exchangeRate);
                    
                    $updateStmt = $con->prepare("UPDATE med_prices SET dollar_dose_price = ? WHERE id = ?");
                    $updateStmt->execute([$usdPrice, $row['id']]);
                    $updateCount++;
                    echo "Updated record ID {$row['id']} - EGP: $egpPrice -> USD: $usdPrice\n";
                }
            }
            
            echo "Updated $updateCount records with USD values.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "Script completed.\n";
?> 