<?php
// Include database connection file
require_once 'db.php';
require_once 'BankConfig.php';

// Make sure we have a valid database connection
if (!isset($con) || !($con instanceof PDO)) {
    die("Database connection is not available. Please check your configuration.");
}

try {
    // Check if the column already exists
    $columnCheck = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'dollar_dose_price'");
    
    if ($columnCheck->rowCount() == 0) {
        // Column doesn't exist, add it
        $con->exec("ALTER TABLE `med_prices` ADD COLUMN `dollar_dose_price` decimal(10,2) DEFAULT NULL AFTER `custom_price`");
        
        // Update existing records with calculated USD values
        $records = $con->query("SELECT id, price_per_dose, custom_price FROM med_prices");
        
        // Initialize BankConfig to use exchange rate functions
        BankConfig::initialize();
        
        while ($row = $records->fetch(PDO::FETCH_ASSOC)) {
            $egpPrice = !empty($row['custom_price']) ? $row['custom_price'] : $row['price_per_dose'];
            
            if ($egpPrice > 0) {
                $usdPrice = BankConfig::convertToUSD($egpPrice);
                
                $updateStmt = $con->prepare("UPDATE med_prices SET dollar_dose_price = ? WHERE id = ?");
                $updateStmt->execute([$usdPrice, $row['id']]);
            }
        }
        
        echo "The dollar_dose_price column has been added to med_prices table and existing records have been updated.";
    } else {
        echo "The dollar_dose_price column already exists in med_prices table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 