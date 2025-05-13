<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
require 'BankConfig.php';
// Include the shared functions without recursive dependency
require_once 'med_functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable error logging to file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Create purchase_logs table if it doesn't exist
try {
    $tableCheck = $con->query("SHOW TABLES LIKE 'purchase_logs'");
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `purchase_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `item_name_snapshot` varchar(255) NOT NULL,
            `price_paid` decimal(10,2) NOT NULL,
            `purchase_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $con->exec($createTableSQL);
        error_log("Created purchase_logs table");
    }
} catch (Exception $e) {
    error_log("Error checking/creating purchase_logs table: " . $e->getMessage());
}

// Debug log
error_log("Submit.php accessed at " . date('Y-m-d H:i:s'));
if (isset($_GET['name'])) {
    error_log("Medication name: " . $_GET['name']);
    if(isset($_GET['mastNote'])) {
        error_log("mastNote: " . $_GET['mastNote']);
    }
}

// Function to get medication price per dose from med_prices table
if (!function_exists('getMedicationPricePerDose')) {
    function getMedicationPricePerDose($medId, $con, $isUSD = false) {
        $priceQuery = $con->prepare("SELECT price_per_dose, custom_price FROM med_prices WHERE med_id = ? ORDER BY update_date DESC LIMIT 1");
        $priceQuery->execute([$medId]);
        $priceData = $priceQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($priceData) {
            // If custom price exists, use that instead of calculated price_per_dose
            return !empty($priceData['custom_price']) ? $priceData['custom_price'] : $priceData['price_per_dose'];
        }
        
        // Fallback to calculating from medlist table if not in med_prices
        $priceQuery = $con->prepare("SELECT price, doses_per_package FROM medlist WHERE id = ?");
        $priceQuery->execute([$medId]);
        $priceData = $priceQuery->fetch(PDO::FETCH_ASSOC);
        
        // Calculate price per dose if data is available
        if ($priceData && isset($priceData['price']) && isset($priceData['doses_per_package']) && $priceData['doses_per_package'] > 0) {
            $pricePerDose = $priceData['price'] / $priceData['doses_per_package'];
            return round($pricePerDose, 2); // Round to 2 decimal places
        }
        
        // Default to 0 if no price data is available
        return 0;
    }
}

// Initialize Bank if not already initialized
BankConfig::initialize();

if (isset($_GET['name'])) {
    $datenow = date("d M, Y h:i a");
    $thisMed = $_GET['name'];
    error_log("Processing medication: " . $thisMed);
    
    if(isset($_GET['mastNote'])){
        $mastNote = $_GET['mastNote'];
    }else{
        $mastNote = '';
    }
    
    // Verify the medication exists in the database
    try {
        $checkMed = $con->prepare("SELECT * FROM medlist WHERE name = ?");
        $checkMed->execute([$thisMed]);
        if ($checkMed->rowCount() == 0) {
            error_log("ERROR: Medication not found in database: " . $thisMed);
            echo "Error: Medication not found in database";
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error checking medication: " . $e->getMessage());
        echo "Error: Database error checking medication";
        exit;
    }

    // Get user ID from session - adjust this according to your auth system
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '1'; // Default to '1' if not set

    // Get half life and med_id
    $selectHalf = $con->prepare("SELECT * FROM medlist WHERE name = ?");
    $selectHalf->execute([$thisMed]); 
    $fetch_halflife = $selectHalf->fetch();
    $default_half_life = $fetch_halflife['default_half_life'];
    $medId = $fetch_halflife['id'];

    // Get medication price per dose if available
    $medCost = 0;
    $medCostUSD = 0;
    if ($medId) {
        // Get price in EGP
        $medCost = getMedicationPricePerDose($medId, $con);
        // Get price in USD - always use dollar_dose_price
        $medCostUSD = getMedicationPricePerDose($medId, $con, true);
    }

    try {
        $insertDose = $con->prepare("INSERT INTO medtrack (medname, dose_date, details, default_half_life) VALUES (?, ?, ?, ?)");
        $insertDose->execute([$thisMed, $datenow, $mastNote, $default_half_life]);
        error_log("Successfully inserted medication dose into medtrack: " . $thisMed);
    } catch (PDOException $e) {
        error_log("Database error inserting into medtrack: " . $e->getMessage());
        echo "Error: Database error inserting medication record";
        exit;
    }
    $medid = $con->lastInsertId(); // Get the ID of the inserted record

    try {
        $updateLastdose = $con->prepare("UPDATE medlist set lastdose=?, email_half=?, email_fivehalf=? where name=?");
        $updateLastdose->execute([$datenow, null, null, $thisMed]);
        error_log("Successfully updated medication lastdose in medlist: " . $thisMed);
    } catch (PDOException $e) {
        error_log("Database error updating medlist: " . $e->getMessage());
        echo "Error: Database error updating medication lastdose";
        exit;
    }

    // If we have a medication cost, update the balance and create purchase log
    if ($medCost && $medCost > 0) {
        // Store original EGP value for log and display
        $medCostEGP = $medCost;

        try {
            // Create purchase log entry first - use price_paid in EGP for local purchase_logs
            $purchaseQuery = $con->prepare("INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) VALUES (?, ?, ?, NOW())");
            $purchaseQuery->execute([$medId, $thisMed, $medCost]);
            error_log("Successfully created purchase log for medication: " . $thisMed . " with cost: " . $medCost . " EGP");

            // Update balance in timer8 database using BankConfig - use dollar_dose_price for USD
            $updateResult = BankConfig::updateBalance($userId, $medCostEGP, $thisMed, $medCostUSD);
            
            if ($updateResult) {
                error_log("Successfully updated balance for medication: " . $thisMed . " with cost: " . $medCostEGP . " EGP / $" . $medCostUSD . " USD");
            } else {
                error_log("ERROR: Failed to update balance for medication: " . $thisMed . " with cost: " . $medCostEGP . " EGP / $" . $medCostUSD . " USD");
            }
        } catch (PDOException $e) {
            error_log("Database error creating purchase log: " . $e->getMessage());
            // Continue execution as the dose was already recorded
        }
    }

    // Generate HTML for the new entry
    if ($medid) {
        $timeonly = date('h:i a', strtotime($datenow));
        $time_spent_string = "just now"; // For instant appearance

        $medname_html = htmlspecialchars($thisMed, ENT_QUOTES, 'UTF-8');
        $firstWord = preg_replace('/\\s*\\d+\\D*$/', '', $thisMed);
        $firstWord_html = htmlspecialchars($firstWord, ENT_QUOTES, 'UTF-8');
        
        $details_html_part = '';
        if (!empty($mastNote)) {
            $details_html_part = ', <b>[' . htmlspecialchars($mastNote, ENT_QUOTES, 'UTF-8') . ']</b>';
        }

        $html_output = '- <font class="medTitle"><b><a href="possible_sides.php?name=' . $firstWord_html . '" style="text-decoration: none;color: inherit;">' . $medname_html . '</a></b></font>, ' . $timeonly . ' ( ' . $time_spent_string . ' )' . $details_html_part . ' <button class="deleteRecord btn btn-danger btn-sm" data-id="' . $medid . '" style="margin-left: 5px; font-size: 10px; padding: 1px 5px;">Delete</button><br>';
        echo $html_output;
    } else {
        // Fallback or error if lastInsertId failed or something went wrong
        echo "Error: Could not retrieve new entry ID."; // Or echo "N/A" or nothing
    }
    exit; // Ensure no other output
}
// If $_GET['name'] is not set, this script will output nothing, which is fine.
?>
