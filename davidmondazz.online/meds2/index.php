<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';
include '../countdown.php';
// Ensure med_prices table has custom_price column
try {
    if (isset($con)) { // Check if $con (database connection) is set
        $tableCheck = $con->query("SHOW TABLES LIKE 'med_prices'");
        if ($tableCheck && $tableCheck->rowCount() > 0) { // Only alter if table exists
            // Check if custom_price column exists
            $columnCheckCustomPrice = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'custom_price'");
            if ($columnCheckCustomPrice && $columnCheckCustomPrice->rowCount() == 0) {
                $con->exec("ALTER TABLE `med_prices` ADD COLUMN `custom_price` varchar(50) DEFAULT NULL AFTER `price_per_dose`");
                error_log("Altered med_prices table to add custom_price column (from index.php)");
            }
        } else {
            error_log("med_prices table does not exist or table check failed. (Checked from index.php)");
        }
    } else {
        error_log("Database connection (\$con) not available in index.php for med_prices schema check.");
    }
} catch (Exception $e) {
    error_log("Error checking/altering med_prices table in index.php: " . $e->getMessage());
    // Continue execution, but the error is logged.
}

// Define USD to EGP exchange rate constant - will be replaced with dynamic value
define('USDEGP', 50.59); // Default value that will be overridden

// Function to get total bank balance from mcgkxyz_timer_app database
function getTotalBankBalance() {
    try {
        // Connect to mcgkxyz_timer_app database
        $timer_db = new PDO("mysql:host=localhost;dbname=mcgkxyz_timer_app", "mcgkxyz_masterpop", "aA0109587045");
        $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Query to get total bank balance from user_progress table
        $query = $timer_db->prepare("SELECT bank_balance FROM user_progress WHERE id = 1");
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        
        // Add error logging to see the actual value
        error_log("Raw bank_balance value: " . print_r($result, true));
        
        // Return the exact value without any modifications
        if ($result && isset($result['bank_balance'])) {
            return $result['bank_balance'];
        } else {
            return 23.19; // Hardcoded fallback value if database query fails
        }
    } catch (PDOException $e) {
        // Log error for debugging
        error_log("Error fetching bank balance: " . $e->getMessage());
        // Return fallback value if there's an error
        return 23.19; // Hardcoded fallback value if exception occurs
    }
}
// Function to check if a medication has a purchase log
function hasPurchaseLog($med_id, $con) {
    if (!$med_id) { // Ensure med_id is provided
        return false;
    }
    try {
        $stmt = $con->prepare("SELECT 1 FROM purchase_logs WHERE item_id = ? LIMIT 1");
        $stmt->execute([$med_id]);
        return $stmt->fetchColumn() !== false;
    } catch (PDOException $e) {
        error_log("Error in hasPurchaseLog function: " . $e->getMessage());
        return false; // Return false on error to prevent displaying price incorrectly
    }
}

// Functions getUSDEGPRate and getMedicationPricePerDose are now expected to be in med_functions.php

$datenow = date("d M, Y h:i a");
$msg = '';

// Get the total bank balance
$totalBankBalance = getTotalBankBalance();

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;

if (isset($_POST['addnew']) && $_POST['addnew'] == 'Addnew!') {

    $medname = $_POST['medname'];
    $halflife = $_POST['halflife'];
    $halflife = empty($halflife) ? NULL : $halflife; // Set halflife to NULL if it's empty
    $checkMeds = $con->query("SELECT * from medlist where name='$medname'");

    if (empty($_POST['starting'])) {
        $starting = '';
    } else {
        $starting = $_POST['starting'];
    }

    if (empty($_POST['medname'])) {
        $msg = '<font color="red">enter med name!</font>';
        header("Refresh:1 url=index.php");
    } elseif ($checkMeds->rowCount() > 0) {
        $msg = '<font color="red">This Medication already Exist!</font>';
        header("Refresh:1 url=index.php");

    } else {
        $insert = $con->prepare("INSERT INTO medlist (name, start_date, end_date, status, default_half_life) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$medname, $starting, '', 'open', $halflife]);
        header("Refresh:1 url=index.php");
    }
}

$stopButton = '';
$checkRunning = $con->query("SELECT * FROM medlist where start_date != '' and end_date = ''");
if ($checkRunning->rowCount() > 0) {
    $stopButton = '<div style="display: inline-block;"><a href="index.php?stop" class="btn btn-danger">Stop</a></div>';
}

/// Start Button

$confirmMsg = '';
if (isset($_POST['start']) && $_POST['start'] == 'Start') {
    $_SESSION['startdate'] = $_POST['startDate'];
    $_SESSION['hiddenName'] = $_POST['hiddenName'];

    $confirmMsg = '<br><span style="font-size:20px;">Are you sure you want to start <b style="color:red;">' . $_SESSION['hiddenName'] . '</b> at <b>' . $_SESSION['startdate'] . ':</b></span><br><form action="index.php?start" method="post"><input type="submit" name="startYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="StartNo" value="NO!" class="btn btn-danger"></form>';

}

if (isset($_POST['startYes']) && $_POST['startYes'] == 'YES!') {
    $name = $_SESSION['hiddenName'];
    $dateStart = $_SESSION['startdate'];

    $start = $con->prepare("UPDATE medlist set start_date=?, end_date=? where name=?");
    $start->execute([$dateStart, '', $name]);

    $confirmMsg = '<br><span style="font-size:20px;"><B>' . $name . '</b> Started Successfully! <br></span><br>';
    Header("Refresh:3 index.php?start");

}

if (isset($_POST['StartNo']) && $_POST['StartNo'] == 'NO!') {
    unset($_SESSION["startdate"]);
    unset($_SESSION["hiddenName"]);
    Header("Location: index.php?start");
}

/// End of Start Button

if (isset($_POST['HalfButton']) && $_POST['HalfButton'] == 'Update') {
    $_SESSION['halflife'] = $_POST['halflife'];
    $_SESSION['HalfName'] = $_POST['HalfName'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['HalfName'] . '</b> To <b>' . $_SESSION['halflife'] . ' Hrs:</b></span><br><form action="index.php?searchKey=' . $searchKey . '" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    } else {
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['HalfName'] . '</b> To <b>' . $_SESSION['halflife'] . ' Hrs:</b></span><br><form action="index.php?halflives" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    }

}

if (isset($_POST['UpdateYes']) && $_POST['UpdateYes'] == 'YES!') {
    $name = $_SESSION['HalfName'];
    $halflife = $_SESSION['halflife'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'halflives';
    }

    $start = $con->prepare("UPDATE medtrack set default_half_life=? where medname=?");
    $start->execute([$halflife, $name]);
    
    $start2 = $con->prepare("UPDATE medlist set default_half_life=?, sent_email=?, fivehalf_email=? where name=?");
    $start2->execute([$halflife, null, null, $name]);

    $confirmMsg = '<br><span style="font-size:20px;"><B>' . $name . '</b> Half Life Updated To ' . $halflife . ' Hrs! <br></span><br>';
    Header("Refresh:3 index.php?$refreshPage");

}

if (isset($_POST['UpdateCancel']) && $_POST['UpdateCancel'] == 'Cancel!') {
    unset($_SESSION['halflife']);
    unset($_SESSION['HalfName']);

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'halflives';
    }
    Header("Refresh:3 index.php?$refreshPage");
}



?>


<!DOCTYPE html>
<html>
<head>
	<title>MedTracker</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<link rel="stylesheet" type="text/css" href="css/modern-buttons.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<script src="js/jquery-3.6.0.min.js"></script>
    <style>
    .rotate-image {
  transition: transform 0.3s ease;
}

.rotate-image:hover {
  transform: rotate(360deg);
}

   .scroll-buttons {
    position: fixed;
    right: 30px;
    bottom: 50px;
    display: flex;
  }

  .scroll-button {
    width: 60px;
    height: 60px;
    background-color: #EC570C;
    text-align: center;
    line-height: 60px;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 20px;
  }

  .live-container {
    display:flex;
    align-items: center; 
    vertical-align: middle; /* Align vertically in the middle */
    float:left;
    height:40px;
    float:right;
    font-size:11px; important!
}

/* Style for price per dose display */
.price-per-dose {
    font-size: 14px;
    color: #2e7d32; /* Deep, muted green */
    margin-top: 5px;
    margin-bottom: 5px;
    font-weight: bold;
    text-align: center;
    background-color: #e8f5e9; /* Very light mint */
    border-radius: 4px;
    padding: 4px 8px;
    display: block;
    min-width: 80px;
    margin-left: auto;
    margin-right: auto;
    border: 1px solid #81c784; /* Mid-tone green */
}

/* New medication price display style */
.med-price-display {
    display: inline-block;
    margin-left: 4px;
    font-size: 12px;
    font-weight: bold;
    color: #ffffff;
    background-color: #e74c3c;
    border-radius: 8px;
    padding: 1px 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    vertical-align: middle;
    text-shadow: none;
}

/* Bank balance display style */
.bank-balance {
    display: inline-flex;
    align-items: center;
    font-family: 'Montserrat', 'Roboto', -apple-system, sans-serif;
    font-weight: 600;
    color: #2c3e50;
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    padding: 10px 16px;
    border-radius: 12px;
    margin-right: 15px;
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.bank-balance:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 14px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
}

.bank-balance i {
    color: #00d1b2;
    margin-right: 8px;
    font-size: 1.2em;
}

.bank-balance-value {
    font-size: 1.1em;
    color: #3498db;
}

/* Optional: Adjust spacing between elements */
#LiveRefresh {
    margin-right: 10px; /* Adjust margin as needed */
}

/* Styles for the fast bulk delete feature */
.bulk-delete-controls {
    margin: 10px 0; 
    padding: 15px; 
    background-color: #fff3e6; 
    border: 1px solid #ec570c; 
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.bulk-delete-controls h5 {
    color: #ec570c;
    margin-bottom: 10px;
}

.delete-checkbox {
    cursor: pointer;
    transform: scale(1.2);
    margin-right: 5px;
}

.delete-label {
    font-size: 10px;
    color: #dc3545;
    cursor: pointer;
}

#selectAllBtn, #deselectAllBtn {
    margin-right: 5px;
    margin-bottom: 5px;
}

#bulkDeleteBtn {
    background-color: #dc3545;
    border-color: #dc3545;
}

#selectedCount {
    display: inline-block;
    padding: 4px 8px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 12px;
}


    

    /* Magic UI Button Styles */
    .btn {
        border-radius: 12px !important;
        border: none !important;
        padding: 12px 24px !important;
        font-weight: 600 !important;
        letter-spacing: 0.5px !important;
        color: white !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.05) !important;
        position: relative !important;
        overflow: hidden !important;
        text-transform: uppercase !important;
        font-size: 14px !important;
        margin: 5px !important;
    }
    
    .btn::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }
    
    .btn:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 15px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0.08) !important;
    }
    
    .btn:active {
        transform: translateY(1px) !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.05) !important;
    }
    
    .btn:focus {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5) !important;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%) !important;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #606c88 0%, #3f4c6b 100%) !important;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%) !important;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%) !important;
    }
    
    .btn-warning {
        background: linear-gradient(135deg, #f46b45 0%, #eea849 100%) !important;
        color: #fff !important;
    }
    
    .btn-info {
        background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%) !important;
    }
    
    .btn-light {
        background: linear-gradient(135deg, #E0EAFC 0%, #CFDEF3 100%) !important;
        color: #333 !important;
    }
    
    .btn-dark {
        background: linear-gradient(135deg, #2C3E50 0%, #4CA1AF 100%) !important;
    }
    
    /* Button sizes */
    .btn-sm {
        padding: 8px 16px !important;
        font-size: 12px !important;
        border-radius: 8px !important;
    }
    
    .btn-lg {
        padding: 16px 32px !important;
        font-size: 16px !important;
    }
    
    /* Specialty buttons - Delete Mode and Stop */
    a[href="index.php?show_delete_buttons"].btn-danger {
        background: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%) !important;
        font-weight: 700 !important;
    }
    
    a[href="index.php?show_delete_buttons"].btn-secondary {
        background: linear-gradient(135deg, #bdc3c7 0%, #2c3e50 100%) !important;
    }
    
    <?php if(isset($stopButton) && !empty($stopButton)): ?>
    .btn-danger[href="index.php?stop"] {
        background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%) !important;
        font-weight: 700 !important;
        letter-spacing: 1px !important;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 8, 68, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(255, 8, 68, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 8, 68, 0);
        }
    }
    <?php endif; ?>
    
    /* Modern form inputs to match buttons */
    input[type="text"], select {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 10px 16px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        transition: all 0.3s ease !important;
        font-size: 14px !important;
    }
    
    input[type="text"]:focus, select:focus {
        border-color: #4776E6 !important;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.3) !important;
        outline: none !important;
    }
    
    /* Delete button within medication rows */
    .deleteRecord.btn-danger {
        background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%) !important;
        padding: 5px 10px !important;
        font-size: 10px !important;
        border-radius: 6px !important;
        margin-left: 8px !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    /* Med button style */
    .modern-med-button {
        display: inline-block !important;
        padding: 10px 22px !important;
        margin: 5px !important;
        border: none !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        color: white !important;
        background: linear-gradient(135deg, #3a7bd5, #00d2ff) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        cursor: pointer !important;
        position: relative !important;
        overflow: hidden !important;
        min-width: 150px !important;
        line-height: 1.5 !important;
        text-align: center !important;
    }
    
    .modern-med-button:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 7px 15px rgba(0, 0, 0, 0.2) !important;
    }
    
    .modern-med-button:active {
        transform: translateY(1px) !important;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
    }
    
    .modern-med-button:disabled {
        background: linear-gradient(135deg, #bdc3c7, #95a5a6) !important;
        cursor: not-allowed !important;
    }
    
    /* Lock and unlock icon styles */
    .lock-icon, .unlock-icon {
        cursor: pointer;
        font-size: 1.2em;
        padding: 12px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        margin: 0 8px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }
    
    .lock-icon {
        color: white;
        background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
    }
    
    .unlock-icon {
        color: white;
        background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);
    }
    
    .lock-icon:hover, .unlock-icon:hover {
        transform: scale(1.1) translateY(-2px);
        box-shadow: 0 5px 12px rgba(0,0,0,0.18);
    }

    /* Med Button Magic UI Style */
.med-button-style {
    display: inline-block !important;
    padding: 10px 22px !important; /* Adjust padding for comfortable size */
    margin: 5px !important; /* Maintain existing spacing */
    border: none !important;
    border-radius: 25px !important; /* Generous rounding for pill shape */
    font-weight: bold !important;
    color: #FFFFFF !important; /* White text for contrast */
    text-align: center !important;
    text-decoration: none !important;
    cursor: pointer !important;
    background: linear-gradient(135deg, #48C9B0, #1F618D) !important; /* Teal to Sapphire gradient */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
    transition: transform 0.2s ease-out, box-shadow 0.2s ease-out !important;
    min-width: 150px !important; /* Ensure buttons have a decent minimum width */
    line-height: normal !important; /* Ensure text is vertically centered */
    vertical-align: middle !important;
}

.med-button-style:hover {
    transform: translateY(-3px) scale(1.02) !important; /* Lift and slight grow */
    box-shadow: 0 7px 14px rgba(0, 0, 0, 0.25) !important; /* Enhanced shadow */
}
    
/* Lock/Unlock icon styles */
.lock-icon, .unlock-icon {
    cursor: pointer;
    font-size: 1.2em;
    padding: 8px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    margin: 0 5px;
}

.lock-icon {
    color: #e53e3e;
    background-color: rgba(229, 62, 62, 0.1);
}

.unlock-icon {
    color: #38a169;
    background-color: rgba(56, 161, 105, 0.1);
}

.lock-icon:hover, .unlock-icon:hover {
    transform: scale(1.1);
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.modal-container {
    background-color: white;
    padding: 24px;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

.modal-overlay.active .modal-container {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.modal-title {
    font-size: 1.5em;
    font-weight: 600;
    color: #2d3748;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #a0aec0;
    padding: 0;
}

.modal-body {
    margin-bottom: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.modal-button {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.modal-confirm {
    background-color: #4299e1;
    color: white;
}

.modal-cancel {
    background-color: #e2e8f0;
    color: #4a5568;
}

.modal-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Toast styles */
#toast-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1100;
}

.toast {
    padding: 12px 20px;
    margin-top: 10px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

.toast-success {
    background-color: #38a169;
}

.toast-error {
    background-color: #e53e3e;
}

.toast-info {
    background-color: #3182ce;
}

/* Day header style */
.day-header {
    background: linear-gradient(135deg, #3498db, #2c3e50);
    color: white;
    padding: 12px 20px;
    border-radius: 10px 10px 0 0;
    margin-top: 30px;
    margin-bottom: 0;
    font-family: 'Montserrat', 'Roboto', -apple-system, sans-serif;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.day-header-date {
    font-size: 1.3em;
}

.day-header-cost {
    font-size: 1.1em;
    padding: 5px 12px;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
}

/* Medication details table style */
.med-details-table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 0 0 10px 10px;
    overflow: hidden;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    background-color: white;
}

.med-details-table th {
    background-color: #f1f5f9;
    color: #334155;
    text-align: left;
    padding: 12px 16px;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
}

.med-details-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.med-details-table tr:last-child td {
    border-bottom: none;
}

.med-details-table .med-name {
    font-weight: bold;
    color: #e53e3e;
    text-decoration: none;
}

.med-details-table .med-time {
    color: #718096;
    font-size: 0.9em;
}

.med-details-table .med-duration {
    color: #2d3748;
}

.med-details-table .med-price {
    color: #3182ce;
    font-weight: bold;
}

.med-details-table .action-button {
    padding: 5px 10px;
    font-size: 0.8em;
}
        
    </style>
    <!-- Add service worker registration -->
    <script>
        // Register service worker for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/js/service-worker.js')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                })
                .catch(function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>

    
    <!-- Custom medication taken event handler -->
    <script>
        // Toast notification function
        function showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            
            // Create icon based on notification type
            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            icon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
            
            // Create message element
            const msgElement = document.createElement('span');
            msgElement.className = 'toast-message';
            msgElement.textContent = type === 'success' ? message + ' taken successfully!' : message;
            
            // Append elements to toast
            toast.appendChild(icon);
            toast.appendChild(msgElement);
            
            // Add toast to container
            const container = document.getElementById('toast-container');
            container.appendChild(toast);
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease';
                
                // Remove from DOM after animation
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    
        // Override the existing handleButtonClick function
        $(document).ready(function() {
            // Original button click handler (preserve existing functionality)
            $(document).on('click', "button[id^='takeButton']:not([disabled])", function(event) {
                event.preventDefault();
                
                var $button = $(this);
                var name = $button.parents("span").attr("name");
                var buttonId = $button.attr('id');
                
                // Disable the button
                $button.prop('disabled', true);
                
                // Original AJAX request
                $.ajax({
                    url: 'submit.php',
                    type: 'GET',
                    data: { name: name }, // Should also pass mastNote if it's used by submit.php and part of the HTML
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Something is wrong with the submission: ' + textStatus);
                        // Re-enable the button on error so user can retry
                        $button.prop('disabled', false);
                    },
                    success: function(newHtmlFragment) {
                        console.log("Server response:", newHtmlFragment); // Log the server response
                        
                        if (newHtmlFragment && typeof newHtmlFragment === 'string' && newHtmlFragment.trim() !== '' && !newHtmlFragment.toLowerCase().startsWith('error:')) {
                            // Get the updated bank balance
                            $.ajax({
                                url: 'get_bank_balance.php',
                                type: 'GET',
                                dataType: 'json',
                                success: function(response) {
                                    // Update the bank balance display without page refresh
                                    if (response.status === 'success') {
                                        $('.bank-balance-value').text('Balance: $' + response.formatted_balance);
                                    }
                                }
                            });
                            
                            // Add medication to the list without the cost notification info
                            var cleanedHtml = newHtmlFragment.replace(/, <span class="med-cost" style="color: green;">Cost: EGP.*?<\/span>/, '');
                            $('#med-details').prepend(cleanedHtml);
                            
                            // Show notification that medication was taken
                            showNotification(name, 'success');
                            
                            // Start countdown with 3 seconds
                            var originalButtonText = $button.text();
                            disableButton($button);
                        } else {
                            console.error("submit.php did not return valid HTML or returned an error:", newHtmlFragment);
                            alert("Failed to add medication. Check console for details.");
                        }
                    }
                });
            });
            
            // Delete button handler
            $(document).on('click', '.deleteRecord', function() {
                var recordId = $(this).data('id');
                var $deleteButton = $(this);
                
                if (confirm('Are you sure you want to delete this record?')) {
                    // Disable the button to prevent multiple clicks
                    $deleteButton.prop('disabled', true);
                    
                    $.ajax({
                        url: 'delete_record.php',
                        type: 'POST',
                        data: { id: recordId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                // Update the bank balance display
                                $('.bank-balance-value').text('Balance: $' + response.formatted_balance);
                                
                                // Remove the entire medication record from the DOM
                                $deleteButton.closest('div, tr').fadeOut(400, function() {
                                    $(this).remove();
                                });
                            } else {
                                alert('Error: ' + response.message);
                                $deleteButton.prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Delete error:", xhr.responseText);
                            alert('Error deleting record. Please try again.');
                            $deleteButton.prop('disabled', false);
                        }
                    });
                }
            });
            
            // Restore button states on page load
            function restoreSavedButtonStates() {
                // Check for any saved countdown timers in localStorage
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key.startsWith('endTime_')) {
                        const buttonId = key.replace('endTime_', '');
                        const endTime = parseInt(localStorage.getItem(key), 10);
                        const now = Date.now();
                        
                        // If the timer hasn't expired yet
                        if (endTime > now) {
                            const $button = $('#' + buttonId);
                            if ($button.length) {
                                const remainingSeconds = Math.ceil((endTime - now) / 1000);
                                const originalText = $button.data('original-text') || $button.text();
                                startCountdown(buttonId, remainingSeconds, originalText);
                            }
                        } else {
                            // Timer has expired, remove it
                            localStorage.removeItem(key);
                        }
                    }
                }
            }
            
            // Call this function when the page loads
            restoreSavedButtonStates();
        });
    </script>
    <!-- Add these script tags before the closing </head> tag -->
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-auth.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.0/firebase-database.js"></script>
    <script src="js/firebase-init.js"></script>
    <script src="js/functions.js"></script>
    <script src="js/mood-form.js"></script>
</head>
<body>
<!-- Toast notification container -->
<div id="toast-container"></div>

<div class="responsive-container">
<?php

$howdidyouButton = 'index.php?how_did_you_feel';
if(isset($_GET['page'])){
    $howdidyouButton = 'index.php?page='.$_GET['page'].'&how_did_you_feel';
}


?>


 <form action="index.php" method="post" class="med-add-form">
 	<div class="form-row">
        <div class="form-group">
            <a href="index.php"><img src="img/icon.png" alt="MedTracker" class="logo-img"></a>
        </div>
        <div class="form-group">
            <label for="medname">MedName:</label>
            <input type="text" id="medname" name="medname" class="form-control">
        </div>
        <div class="form-group">
            <label for="halflife">HalfLife:</label>
            <input type="text" id="halflife" name="halflife" class="form-control">
        </div>
        <div class="form-group starting-group">
            <input type="checkbox" id="starting" name="starting" value="<?php echo $datenow; ?>">
            <label for="starting">Starting?</label>
        </div>
        <div class="form-group">
            <input type="submit" name="addnew" value="Addnew!" class="btn btn-primary">
        </div>
    </div>
    <?php echo $msg; ?>
    <p id="liveFeedback"></p>
</form>

<div class="action-buttons">
    <a href="index.php" class="btn btn-info">Refresh</a>
    <a href="index.php?lock" class="btn btn-dark"><i class="fas fa-lock"></i></a>
    <a href="index.php?unlock" class="btn btn-light"><i class="fas fa-unlock"></i></a>
    <a href="index.php?start" class="btn btn-success">Start</a>
    <a href="<?php echo $howdidyouButton;?>" class="btn btn-info">HowDidYouFeel</a>
    <a href="index.php?show_delete_buttons" class="btn <?php echo isset($_GET['show_delete_buttons']) ? 'btn-danger' : 'btn-secondary'; ?>">Delete Mode</a>
    <?php echo $stopButton; ?>
</div>

<div class="user-info">
    <?php 
    // Ensure we have a valid bank balance value
    $dynamic_usdegp = getUSDEGPRate(); // Get the current exchange rate
    $totalBankBalance = getTotalBankBalance(); // Get the bank balance
    ?>
    <div class="bank-balance"><i class="fas fa-wallet"></i><a href="../timer8/bank.php" style="text-decoration: none; color: inherit;"><span class="bank-balance-value">Balance: $<?= number_format($totalBankBalance, 2, '.', '') ?></span></a></div>
    <div class="user-session">
        Logged as: <b><?=$userLogged;?></b>
        <div class="user-links">
            <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a>
            <a href="../index.php" class="btn btn-secondary btn-sm">Main</a>
            <a href="prices.php" class="btn btn-info btn-sm">Medication Prices</a>
        </div>
    </div>
</div>

<div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
</div>
<br>
<center>
    <?php echo $confirmMsg; ?>
</center>


<?php

if (isset($_GET['lock'])) {
    $select = $con->query("SELECT m.* 
                          FROM medlist m
                          LEFT JOIN (
                              SELECT medname, MAX(STR_TO_DATE(dose_date, '%d %M, %Y %h:%i %p')) as last_dose_date
                              FROM medtrack
                              GROUP BY medname
                          ) t ON m.name = t.medname
                          WHERE m.status='open' 
                          ORDER BY t.last_dose_date IS NULL, t.last_dose_date DESC");
} elseif(isset($_GET['unlock'])){
    $select = $con->query("SELECT m.* 
                          FROM medlist m
                          LEFT JOIN (
                              SELECT medname, MAX(STR_TO_DATE(dose_date, '%d %M, %Y %h:%i %p')) as last_dose_date
                              FROM medtrack
                              GROUP BY medname
                          ) t ON m.name = t.medname
                          WHERE m.status='lock' 
                          ORDER BY t.last_dose_date IS NULL, t.last_dose_date DESC");
} elseif(isset($_GET['halflives'])) {
    $select = $con->query("SELECT m.* 
                          FROM medlist m
                          LEFT JOIN (
                              SELECT medname, MAX(STR_TO_DATE(dose_date, '%d %M, %Y %h:%i %p')) as last_dose_date
                              FROM medtrack
                              GROUP BY medname
                          ) t ON m.name = t.medname
                          ORDER BY m.default_half_life='' ASC, t.last_dose_date IS NULL, t.last_dose_date DESC");
} elseif(isset($_GET['showNoMore'])){
    $select = $con->query("SELECT m.* 
                          FROM medlist m
                          LEFT JOIN (
                              SELECT medname, MAX(STR_TO_DATE(dose_date, '%d %M, %Y %h:%i %p')) as last_dose_date
                              FROM medtrack
                              GROUP BY medname
                          ) t ON m.name = t.medname
                          WHERE m.nomore = 'yesFirst' 
                          ORDER BY t.last_dose_date IS NULL, t.last_dose_date DESC");
} else {
    $select = $con->query("SELECT m.* 
                          FROM medlist m
                          LEFT JOIN (
                              SELECT medname, MAX(STR_TO_DATE(dose_date, '%d %M, %Y %h:%i %p')) as last_dose_date
                              FROM medtrack
                              GROUP BY medname
                          ) t ON m.name = t.medname
                          WHERE m.status='open' 
                          ORDER BY t.last_dose_date IS NULL, t.last_dose_date DESC");
}

echo '<div style="text-align:center;background: white;padding:10px 15px;display: inline-block;border-radius: 12px;box-shadow: 0 4px 10px rgba(0,0,0,0.08);">';
echo '<span style="font-weight: 600; color: #4776E6; margin-right: 10px; text-transform: uppercase; letter-spacing: 1px; font-size: 15px;">Take: </span>';

$arr = array();

while ($fetch = $select->fetch()) {

    $stopForm = '';
    $howlong = '';
    $lastTaken = '';
    $lockButton = '';

    if (isset($_GET['halflives']) or isset($_GET['showNoMore'])) {
        $medName = '';
    } else {
        $medName = $fetch['name'];
    }

    if (!empty($fetch['start_date']) && empty($fetch['end_date'])) {

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

        $dateStarted = $fetch['start_date'];
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds = ($timeSecond - $timeFirst);
        
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds2 = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        if (isset($_GET['lastdoses'])) {
            $status = '';
        } else {
            $status = '<img src="img/on.png">';
            if ($differenceInSeconds2 <= 59) {
                $howlong = $differenceInSeconds2 . ' sec';
            } elseif ($differenceInSeconds2 < 3600) {
                $howlong = round(($differenceInSeconds2 / 60), 2) . ' mins';
            } elseif ($differenceInSeconds2 < 86400) {
                $howlong = round($differenceInSeconds2 / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds2 >= 86400) {
                $howlong = round($differenceInSeconds2 / 86400, 2) . ' days';
            }
        }

        if (isset($_GET['stop'])) {

            $stopForm .= '
			<span name="' . $fetch['name'] . '"><button class="button3" id="stopButton" style="padding: 3px 15px;"><img src="img/stop.png"></button></span>
		';

        }
    } else {
        $status = '';

        if (isset($_GET['lock'])) {

                $lockButton = '<span name="' . $fetch['name'] . '"><button class="lock-button" id="lockButton"><i class="fas fa-lock"></i> Lock</button></span>
                ';
        }elseif(isset($_GET['unlock'])){
                $lockButton = '<span name="' . $fetch['name'] . '"><button class="unlock-button" id="unlockButton"><i class="fas fa-unlock"></i> Unlock</button></span>
                ';
        }

        if (isset($_GET['lock'])) {
                $lockButton = '<span name="' . $fetch['name'] . '" data-med-name="' . $fetch['name'] . '"><i class="fas fa-lock lock-icon" id="lockIcon"></i></span>';
        } elseif(isset($_GET['unlock'])) {
                $lockButton = '<span name="' . $fetch['name'] . '" data-med-name="' . $fetch['name'] . '"><i class="fas fa-unlock unlock-icon" id="unlockIcon"></i></span>';
        }

    }

    $startButton = '';
    if (isset($_GET['start'])) {
        $startButton = '<img src="img/start.png"><input type="submit" class="button3" style="padding: 3px 15px;" name="start" value="Start">';

        $qu = $con->query('select * from medtrack where medname="' . $fetch['name'] . '" order by id desc');
        $selectDate = '<input type="hidden" name="hiddenName" value="' . $fetch['name'] . '"><select name="startDate" id="selectDate">';
        while ($ros = $qu->fetch()) {
            $selectDate .= '<option value="' . $ros['dose_date'] . '">' . $ros['dose_date'] . '</option>';
        }
        $selectDate .= '</select>';

        if (empty($fetch['start_date']) && empty($fetch['end_date'])) {
            // Get and format price per dose for this medication
            $pricePerDose = getMedicationPricePerDose($fetch['id'], $con, true);
            $priceDisplay = ''; // Initialize as empty
            if ($pricePerDose > 0) {
                $priceDisplay = '<span class="med-price-display">$' . number_format($pricePerDose, 2, '.', '') . '</span>';
            }
            
            $arr[$medName][] = '<div style="border: 2px solid #909090;margin:5px;padding:8px;display: inline-block;border-radius: 20px;text-align:center;"><span name="' . $fetch['name'] . '"><button class="modern-med-button" id="takeButton">' . $fetch['name'] . ' ' . $priceDisplay . '</button></span>' .  '<form action="index.php?start" method="post" style="display:inline;">' . $selectDate . $startButton . '</form></div>';
        } elseif (!empty($fetch['start_date']) && !empty($fetch['end_date'])) {
            // Get and format price per dose for this medication
            $pricePerDose = getMedicationPricePerDose($fetch['id'], $con, true);
            $priceDisplay = ''; // Initialize as empty
            if ($pricePerDose > 0) {
                $priceDisplay = '<span class="med-price-display">$' . number_format($pricePerDose, 2, '.', '') . '</span>';
            }
            
            $arr[$medName][] = ' <span name="' . $fetch['name'] . '" style="border: 2px solid #909090;margin:5px;padding:2px;display: inline-block;border-radius: 20px;"><button class="modern-med-button" id="takeButton">' . $fetch['name'] . ' ' . $priceDisplay . '</button>' .  ' <form action="index.php?start" method="post" style="display:inline;">' . $selectDate . $startButton . '</form></span>';
        }
    } else {

        if (isset($_GET['halflives'])) {
            $last_dose = $fetch['lastdose'];
            $st1 = str_replace(',', '', $last_dose);
            $lastDose = date('d-M-Y h:i a', strtotime($st1));

            $st2 = str_replace(',', '', $datenow);
            $timeNow = date('d-M-Y h:i a', strtotime($st2));

            $timeFirst = strtotime('' . $lastDose . '');
            $timeSecond = strtotime('' . $timeNow . '');
            $differenceInHrs2 = round(($timeSecond - $timeFirst) / 60 , 2);
            
               $startDate = DateTime::createFromFormat('d M, Y h:i a', $last_dose);

                $currentDate = new DateTime(); 
                $difference = $currentDate->diff($startDate);
            
                $totalMinutes = $difference->days * 24 * 60 + $difference->h * 60 + $difference->i;
                $differenceInHrs = round($totalMinutes / 60, 2);

            if (!empty($fetch['default_half_life'])) {
                if (!empty($fetch['lastdose'])) {

                        if($fetch['default_half_life'] > $differenceInHrs){
                            
                    $percentage = round(($differenceInHrs * 100) / $fetch['default_half_life']) . '%';
                    $percentText = $percentage;
                    $remainHrs1 = '<p><font color="red"><b>' . ($fetch['default_half_life'] - $differenceInHrs) . '</b></font> Hrs Remain </p>';
                    $halfLifeMinutes = round($fetch['default_half_life'] * 60);
                    $halfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $halfLifeMinutes . ' minutes'));
                        }else{
                              $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
                        }


                } else {
                    $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
                }

                if (($fetch['default_half_life'] * 5) > $differenceInHrs) {
                    
                    
                    $percentage2 = round(($differenceInHrs * 100) / ($fetch['default_half_life'] * 5)) . '%';
                    $percentText2 = $percentage2;
                    $remainHrs2 = ($fetch['default_half_life'] * 5) - $differenceInHrs . ' Hrs Remain';
                    $fiveHalflife = round(($fetch['default_half_life'] * 5) * 60);
                    $fivehalfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $fiveHalflife . ' minutes'));
                } else {
                    $percentage2 = '100%';
                    $percentText2 = 'Left System!';
                    $remainHrs2 = '';
                    $fiveHalflife = '';
                    $fivehalfEnd = '';
                }
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $halfLifeMinutes = '';
                $halfEnd = '';
                $fiveHalflife = '';
                $fivehalfEnd = '';
            }

            if (isset($_GET['searchKey'])) {
                echo $searchKey = $_POST['searchKey'];
                $formPage = 'searchKey=' . $searchKey;
            } else {
                $formPage = 'halflives';
            }

            $lockButton = '<form action="index.php?' . $formPage . '" method="post"><input type="text" name="halflife" id="halflife" style="width:70px;text-align:center;" value="' . floatval($fetch['default_half_life']) . '"><input type="hidden" name="HalfName" value="' . $fetch['name'] . '"> Hrs <input type="submit" name="HalfButton" value="Update" class="btn btn-primary btn-sm"></form><p style="display:inline-block"> <center><i>1x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
      </div></p></center><p>' . $remainHrs1 . ' ' . $halfEnd . '</p>
      <p style="display:inline-block"><center><i>5x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: ' . $percentage2 . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText2 . '</div>
      </div></p></center><p>' . $remainHrs2 . ' <p>' . $fivehalfEnd . '</p></p>
                ';
        }

      

        if ($fetch['status'] == 'open') {

            $mastText = '';
            $sunText = '';
            $takeButtonID = 'takeButton' . $fetch['id'];
            
            // Get and format price per dose for this medication
            $pricePerDose = getMedicationPricePerDose($fetch['id'], $con, true);
            $priceDisplay = ''; // Initialize as empty
            if ($pricePerDose > 0) {
                $priceDisplay = '<span class="med-price-display">$' . number_format($pricePerDose, 2, '.', '') . '</span>';
            }
            
            $mednames = $status . ' <div style="text-align:center;display:inline-block;"><span name="' . $fetch['name'] . '"><button class="modern-med-button" id="' . $takeButtonID . '">' . $fetch['name'] . ' ' . $priceDisplay . '</button></span></div>' .  $stopForm . ' ' . $howlong . $lockButton . '';
            if (isset($_GET['halflives']) or isset($_GET['showNoMore'])) {

                if ($fetch['name'] == 'Mast') {
                    $mastText = '';
                    $takeButtonID = '';
                    $arr[$medName][] = '';
                } else {

                    $arr[$medName][] = '<div style="border: 2px solid #909090;margin:5px;padding:8px;display: inline-block;border-radius: 20px;text-align:center;">' . $mednames . '</div>';

                }

            } else {

                if ($fetch['name'] == 'Mast') {
                    // Mast Text box & ID
                    $mastText = '<input type="text" name="mastText" id="mastText" style="width:150px;">';
                    $takeButtonID = 'mastButton';
                }
                
                 if ($fetch['name'] == 'Sun Exposure') {
                    // Sun Text box & ID
                    $sunText = '<input type="text" name="sunExposureText" id="sunExposureText" style="width:150px;"> IU';
                    $takeButtonID = 'sunExposureButton';
                }

                    $getDates = $fetch['lastdose'];
                    $strs = str_replace(',', '', $getDates);
                    $newKey = date('Y-m-d', strtotime($strs));
                    $timeonly = date('h:i a', strtotime($strs));

                    $dose_date = $getDates;
                    $str = str_replace(',', '', $dose_date);
                    $day = date('M d, Y', strtotime($str));

                    $st1 = str_replace(',', '', $datenow);
                    $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

                    $dateStarted = $dose_date;
                    $st2 = str_replace(',', '', $dateStarted);
                    $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

                    $timeFirst = strtotime('' . $dateStarted2 . '');
                    $timeSecond = strtotime('' . $dateNow2 . '');
                    $differenceInSeconds2 = ($timeSecond - $timeFirst);
                    
                    $startDate = DateTime::createFromFormat('d M, Y h:i a', $dose_date);
                    $currentDate = new DateTime();
                    $difference = $currentDate->diff($startDate);
                    $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

                    $timespent = '';
                    $daystohrs = '';

                     $fetchname= $fetch['name'];

                    if ($differenceInSeconds <= 3600) {
                        $time_spent = $differenceInSeconds . ' sec';
                        $fetchname = '<font color="blue">'.$fetch['name'].'</font>';
                    } elseif ($differenceInSeconds < 90000) {
                        $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
			            $fetchname = '<font color="red">'.$fetch['name'].'</font>';
                    }

                // Take Buttons
                
             
              $arr[$medName][] = $status . ' <div style="text-align:center;display:inline-block;"><span name="' . $fetch['name'] . '"><button class="modern-med-button" id="' . $takeButtonID . '">' . $fetch['name'] . ' ' . $priceDisplay . '</button></span></div>' .  $stopForm . ' ' . $howlong . $lockButton . $mastText . $sunText . '';

            }

        } else {
            if (isset($_GET['halflives'])) {

                if (!empty($fetch['default_half_life'])) {

                    if (($fetch['default_half_life'] * 5) > $differenceInHrs) {
                        $arr[$medName][] = $status . ' <div style="text-align:center;display:inline-block;"><span name="' . $fetch['name'] . '"><button class="modern-med-button" id="takeButton" disabled>' . $fetch['name'] . ' ' . $priceDisplay . '</button></span>' .  $stopForm . ' ' . $howlong . $lockButton . '</div>';
                    }
                }

            } else {
                $diffTime = diffinTime($fetch['lastdose'], $datenow);
                $arr[$medName][] = $status . ' <div style="text-align:center;display:inline-block;"><span name="' . $fetch['name'] . '"><button class="modern-med-button" id="takeButton" disabled>' . $fetch['name'] . ' ' . $priceDisplay . '</button></span></div>' .  $stopForm . ' <br><font size="3">Since: <b>'. $diffTime[3].' Days</b></font> <br> <font size="2">On: ' . $fetch['lastdose']. '</font>' . $lockButton;
            }
        }
    }

}

$showItems = '';
$searching = '';
if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];

    $query1 = $con->query("SELECT * FROM medlist where name LIKE '%$searchKey%'");
    $fetch = $query1->rowCount();
    if($fetch == 0){
        
    }else{
    $check = $con->query("SELECT * FROM searchhistory where search_name='$searchKey'");
    $row = $check->rowCount();
    if($row == 0){
        // insert
        $insert = $con->query("INSERT INTO searchhistory (search_name, search_date) VALUES ('$searchKey', '$datenow')");
    }else{
        // update
        $update = $con->query("UPDATE searchhistory set search_date='$datenow' where search_name = '$searchKey'");
    }
    }

    $query = $con->query("SELECT * FROM medlist where name LIKE '%$searchKey%'");
    while ($show = $query->fetch()) {

        
        $dose_date = $show['lastdose'];
        $str = str_replace(',', '', $dose_date);
        $day = date('d M Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i a', strtotime($st1));

        $dateStarted = $dose_date;
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds2 = ($timeSecond - $timeFirst);
        
        $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        $timespent = '';
        $daystohrs = '';
        $time_spent = '';

        $last_dose = $show['lastdose'];
        $st1 = str_replace(',', '', $last_dose);
        $lastDose = date('d-M-Y h:i a', strtotime($st1));

        $st2 = str_replace(',', '', $datenow);
        $timeNow = date('d-M-Y h:i a', strtotime($st2));

        $timeFirst = strtotime('' . $lastDose . '');
        $timeSecond = strtotime('' . $timeNow . '');
        $differenceInHrs2 = round(($timeSecond - $timeFirst) / 60, 2);
        
        
    $startDate = DateTime::createFromFormat('d M, Y h:i a', $last_dose);

    $currentDate = new DateTime(); 
    $difference = $currentDate->diff($startDate);

    $totalMinutes = $difference->days * 24 * 60 + $difference->h * 60 + $difference->i;
    $differenceInHrs = round($totalMinutes / 60, 2);

        if (!empty($show['default_half_life'])) {
                if (!empty($show['lastdose'])) {

if($show['default_half_life'] > $differenceInHrs){
            $percentage = round(($differenceInHrs * 100) / $show['default_half_life']) . '%';
                    $percentText = $percentage;
                    $remainHrs1 = '<p><font color="red"><b>' . ($show['default_half_life'] - $differenceInHrs) . '</b></font> Hrs Remain </p>';
                    $halfLifeMinutes = round($show['default_half_life'] * 60);
                    $halfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $halfLifeMinutes . ' minutes'));
                    
}else{
       $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
}
            

                } else {
                    $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
                }

                if (!empty($show['lastdose'])) {
                    
                    if(($show['default_half_life'] * 5) > $differenceInHrs){
                             $percentage2 = round(($differenceInHrs * 100) / ($show['default_half_life'] * 5)) . '%';
                    $percentText2 = $percentage2;
                    $remainHrs2 = ($show['default_half_life'] * 5) - $differenceInHrs . ' Hrs Remain';
                    $fiveHalflife = round(($show['default_half_life'] * 5) * 60);
                    $fivehalfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $fiveHalflife . ' minutes'));
                    }else{
                         $percentage2 = '100%';
                    $percentText2 = 'Left System!';
                    $remainHrs2 = '';
                    $fiveHalflife = '';
                    $fivehalfEnd = '';
                    }
               
                } else {
                    $percentage2 = '100%';
                    $percentText2 = 'Left System!';
                    $remainHrs2 = '';
                    $fiveHalflife = '';
                    $fivehalfEnd = '';
                }
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $halfLifeMinutes = '';
                $halfEnd = '';
                $fiveHalflife = '';
                $fivehalfEnd = '';
            }

        if (isset($_GET['searchKey'])) {
            $formPage = 'searchKey=' . $searchKey;
        } else {
            $formPage = 'halflives';
        }

       


        $lockButton = '<form action="index.php?' . $formPage . '" method="post"><input type="text" name="halflife" id="halflife" style="width:70px;text-align:center;" value="' . floatval($show['default_half_life']) . '"><input type="hidden" name="HalfName" value="' . $show['name'] . '"> Hrs <input type="submit" name="HalfButton" value="Update" class="btn btn-primary btn-sm"></form><p style="display:inline-block"> <center><i>1x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
      </div></p></center><p>' . $remainHrs1 . ' ' . $halfEnd . '</p>
      <p style="display:inline-block"><center><i>5x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: ' . $percentage2 . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText2 . '</div>
      </div></p></center><p>' . $remainHrs2 . ' <p>' . $fivehalfEnd . '</p></p>
                ';

        if (!empty($dose_date)) {

            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 31104000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            } elseif ($differenceInSeconds >= 31104000) {
                $time_spent = round($differenceInSeconds / 31104000, 2) . ' yrs';
            }

        } else {
            $time_spent = 'None';
        }

        echo '<div class="space">';
        $lockOption = '';

        $result = preg_replace('/\s*\d+(\.\d+)?\D*$/', '', $show['name']);
      

        if ($show['status'] == 'open') {
            if (!empty($show['start_date']) && empty($show['end_date'])) {
                $lockOption = '<img src="img/on.png">';
            } else {
                // Modified to use lock-icon and unlock-icon for consistent confirmation modal behavior
                $lockOption = '<span data-med-name="' . $show['name'] . '"><i class="fas fa-lock lock-icon" id="lockIcon"></i></span>';
            }
            
            // Get and format price per dose for this medication
            $pricePerDose = getMedicationPricePerDose($show['id'], $con, true);
            $priceDisplay = ''; // Initialize as empty
            if ($pricePerDose > 0) {
                $priceDisplay = '<span class="med-price-display">$' . number_format($pricePerDose, 2, '.', '') . '</span>';
            }
            
            echo '<div style="text-align:center;display:inline-block;"><span name="' . $show['name'] . '"><button class="modern-med-button" id="takeButton">' . $show['name'] . ' ' . $priceDisplay . '</button></span>' .  '</div> ' . $lockOption . ' <br>' . $time_spent . $lockButton;
        } else {
            // Even if locked, show price if a purchase log exists
            $pricePerDose = getMedicationPricePerDose($show['id'], $con, true);
            $priceDisplay = ''; // Initialize as empty
            if ($pricePerDose > 0) {
                $priceDisplay = '<span class="med-price-display">$' . number_format($pricePerDose, 2, '.', '') . '</span>';
            }
            // Modified to use lock-icon and unlock-icon for consistent confirmation modal behavior
            echo '<div style="text-align:center;display:inline-block;"><span name="' . $show['name'] . '"><button class="modern-med-button" id="takeButton" disabled>' . $show['name'] . ' ' . $priceDisplay . '</button></span>' .  '</div> <span data-med-name="' . $show['name'] . '"><i class="fas fa-unlock unlock-icon" id="unlockIcon"></i></span> <br>' . $time_spent . $lockButton;
        }
      
     
        echo '</div>';
    }
}

$showLastDoses = '';
foreach ($arr as $key => $value) {

    foreach ($value as $item) {
        $selectLastDose = $con->query("SELECT * from medlist where name='$key'");
        $ro = $selectLastDose->fetch();

        if (isset($ro['lastdose'])) {
            $dose_date = $ro['lastdose'];
        } else {
            $dose_date = '';
        }

        $str = str_replace(',', '', $dose_date);
        $day = date('M d, Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

        $dateStarted = $dose_date;
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds2 = ($timeSecond - $timeFirst);
        
        $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        $timespent = '';
        $daystohrs = '';

        $time_spent = '';
        if (!empty($dose_date)) {

            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 31104000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            } elseif ($differenceInSeconds >= 31104000) {
                $time_spent = round($differenceInSeconds / 31104000, 2) . ' yrs';
            }

        } else {
            $time_spent = 'None';
        }

        if (isset($_GET['lastdoses'])) {
            $showLastDoses = '<bR><centeR>' . $time_spent . '</center>';
        }

        if (isset($_GET['searchKey'])) {
            $showItems = '';
        } else {
            $showItems = $item;
        }

        echo '<div class="space">';
        echo $showItems . $showLastDoses;
        echo '</div>';

    }

}

echo '</div>';

?>
<!-- Container for medication records added via AJAX -->
<div id="med-details"></div>

<div style="display: flex; align-items: center; padding: 10px;">
  <div style="float: left;">
    <a href="index.php">
      <img src="img/refresh.png" class="rotate-image">
    </a>
  </div>
  <div style="flex: 1;">
    <center><br>
      <form action="index.php" style="display:inline;">
        <img src="img/search.png" style="padding-bottom: 5px;">
        <input type="text" name="searchKey" style="height: 40px; font-size: 20px; text-align: center;" <?php if (isset($_GET['searchKey'])) { echo "value='$_GET[searchKey]'"; } ?>>
        <input type="submit" name="search" value="Search" style="height: 40px; font-weight: bold;">

        ShowOthers? <input type="checkbox" name="showOthermeds" style="width: 15px; height: 15px;" <?php if (isset($_GET['showOthermeds'])) { echo "checked='checked'"; } ?>>
      </form>
      <br><span style="padding-top: 20px;">
      <?php 
$tenDaysAgo = date('Y-m-d', strtotime('-48 hours')); // searchkey duration
$datenow2 = date('Y-m-d'); // Get the current date without the time

// Use these formatted date strings in your SQL query
$query = $con->query("SELECT * FROM searchhistory WHERE STR_TO_DATE(search_date, '%d %M, %Y') BETWEEN '$tenDaysAgo' AND '$datenow2' ORDER BY STR_TO_DATE(search_date, '%d %b, %Y %h:%i %p') DESC");
    $result = $query->fetchAll(PDO::FETCH_ASSOC);

   
// Loop through the result set
foreach ($result as $row) {
    // Process each row as needed
    echo '<a href="index.php?searchKey='.$row['search_name'].'&search=Search"><b style="font-size:20px;margin:6px;">'.$row['search_name'] . "</b></a> ";
}
?>
</span>

     
     
     
    </center>
  </div>
</div>



<?php



if(!isset($_GET['searchKey'])){
    // Start and end dates
    $start_date = '2022-05-28'; // Specify your desired start date here

// Get current date
$current_date = date('Y-m-d');

// Generate date range
$date_range = [];
$next_date = $start_date;
while ($next_date <= $current_date) {
    $date_range[] = $next_date;
    $next_date = date('Y-m-d', strtotime($next_date . ' +1 day'));
}

// Reverse the date range array
$date_range = array_reverse($date_range);

// Get current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Determine max dates per page
$max_dates = ($page == 1) ? 1 : 25;


// Calculate total pages
$total_dates = count($date_range);
$total_pages = 1 + ceil(($total_dates - 1) / 25);


    // Upper pagination removed as per request. The second pagination block remains.

    echo '<br><br>';

// Output dates and mednames
if (isset($_GET['show_all'])) {
    // Show all dates and data on a single page
    $show_all_counter = 0;
    foreach ($date_range as $date) {
        output_date_data($date, $con, $show_all_counter++);
    }
} else {
    // Show data with pagination
    if ($page == 1) {
        $start_index = 0;
        $end_index = 1;
    } else {
        $start_index = 1 + ($page - 2) * 25;
        $end_index = $start_index + 25;
    }

    for ($i = $start_index; $i < $end_index && $i < $total_dates; $i++) {
        $date = $date_range[$i];
        
        // Call output_date_data with a unique counter value
        output_date_data($date, $con, $i);
    }
}

}

///////////// start of search records

if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];
    $query = $con->query("SELECT m.*, l.id as med_id FROM medtrack m LEFT JOIN medlist l ON m.medname = l.name WHERE m.medname LIKE '%$searchKey%' OR m.details LIKE '%$searchKey%' ORDER BY m.id DESC");

    $arr2 = array();
    $dateArr = array();
    $totalDose24hrs = $totalDose48hrs = $totalDose72hrs = $totalDose7days = $totalDose14days = $totalDose30days = 0;
    $count24hrs = $count48hrs = $count72hrs = $count7days = $count14days = $count30days = 0;

    while ($row = $query->fetch()) {
        $dose_date = $row['dose_date'];
        $str = str_replace(',', '', $dose_date);
        $day = date('M d, Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $startDate = DateTime::createFromFormat('d M, Y h:i a', $dose_date);
        $currentDate = new DateTime();
        $difference = $currentDate->diff($startDate);
        $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        $time_spent = '';
        $daystohrs = '';

        if ($differenceInSeconds <= 59) {
            $time_spent = $differenceInSeconds . ' sec';
        } elseif ($differenceInSeconds < 3600) {
            $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
        } elseif ($differenceInSeconds < 86400) {
            $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
        } elseif ($differenceInSeconds <= 2592000) {
            $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
        } elseif ($differenceInSeconds >= 2592000) {
            $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
            $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month';
            if ($differenceInSeconds >= 31104000) {
                $time_spent .= ' =';
                $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs';
            }
        }

        if (!empty($row['details'])) {
            $details = ', <b>[ ' . $row['details'] . ' ]</b>';
        } else {
            $details = '';
        }

        // Extract the dose from the medname (e.g., "Anafronil 25mg" becomes 25)
        preg_match('/(\d*\.?\d+)(mg|g|mcg)/i', $row['medname'], $matches);
        $dose = isset($matches[1]) ? (float)$matches[1] : 0; // Default to 0 if no dose found

        // Get price per dose for the medication
        $pricePerDose = 0;
        $hasPurchase = false;
        if (isset($row['med_id'])) {
            $pricePerDose = getMedicationPricePerDose($row['med_id'], $con, true);
            $hasPurchase = hasPurchaseLog($row['med_id'], $con);
        }

        // Add dose data to the array
        $dateArr[$day][] = [
            'searchKey' => $row['medname'],
            'dose' => $dose,
            'timeOnly' => $timeonly,
            'daystoHrs' => $daystohrs,
            'timeSpent' => $time_spent,
            'details' => $details,
            'differenceInSeconds' => $differenceInSeconds,
            'pricePerDose' => $pricePerDose,
            'hasPurchaseLog' => $hasPurchase,
            'id' => $row['id']
        ];
    }

    // Calculate the current time in seconds
    $currentTimeInSeconds = time();

    // Iterate through all items to count them based on the current time
    foreach ($dateArr as $keys => $values) {
        foreach ($values as $item) {
            $timeDifferenceInHours = $item['differenceInSeconds'] / 3600;

            // Count the occurrences within each time period and calculate total dose
            if ($timeDifferenceInHours <= 24) {
                $count24hrs++;
                $totalDose24hrs += $item['dose'];  // Add the dose for each occurrence
            }
            if ($timeDifferenceInHours <= 48) {
                $count48hrs++;
                $totalDose48hrs += $item['dose'];
            }
            if ($timeDifferenceInHours <= 72) {
                $count72hrs++;
                $totalDose72hrs += $item['dose'];
            }
            if ($timeDifferenceInHours <= 168) { // 7 days * 24 hours = 168 hours
                $count7days++;
                $totalDose7days += $item['dose'];
            }
            if ($timeDifferenceInHours <= 14 * 24) { // 14 days * 24 hours
                $count14days++;
                $totalDose14days += $item['dose'];
            }
            if ($timeDifferenceInHours <= 30 * 24) { // 30 days * 24 hours
                $count30days++;
                $totalDose30days += $item['dose'];
            }
        }
    }

    // Print the counts and total doses
    echo "Counts from the current moment:<br>";
    echo "In 24 hours: $count24hrs times, total taken: " . ($count24hrs ? $totalDose24hrs . "mg" : "0mg") . "<br>";
    echo "In 48 hours: $count48hrs times, total taken: " . ($count48hrs ? $totalDose48hrs . "mg" : "0mg") . "<br>";
    echo "In 72 hours: $count72hrs times, total taken: " . ($count72hrs ? $totalDose72hrs . "mg" : "0mg") . "<br>";
    echo "In 7 days: $count7days times, total taken: " . ($count7days ? $totalDose7days . "mg" : "0mg") . "<br>";
    echo "In 14 days: $count14days times, total taken: " . ($count14days ? $totalDose14days . "mg" : "0mg") . "<br>";
    echo "In 30 days: $count30days times, total taken: " . ($count30days ? $totalDose30days . "mg" : "0mg") . "<br><br>";

    // Print the organized medication summary in a styled table
    echo '<div style="margin: 15px 0;">';
    echo '<h4 style="margin-bottom: 10px; color: #1e75cd;">Medication Summary</h4>';
    echo '<div style="display: inline-block; border-collapse: collapse; border: 2px solid #1e75cd; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
    echo '<table style="border-collapse: collapse; width: 100%;">';
    echo '<thead>';
    echo '<tr style="background-color: #1e75cd; color: white; text-align: center;">';
    echo '<th style="padding: 8px 15px; border: 1px solid #0d5aa7;">Time Period</th>';
    echo '<th style="padding: 8px 15px; border: 1px solid #0d5aa7;">Count</th>';
    echo '<th style="padding: 8px 15px; border: 1px solid #0d5aa7;">Total Dose</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    // 24 hours row
    echo '<tr style="background-color: ' . ($count24hrs > 0 ? '#e8f4ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">24 Hours</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count24hrs . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count24hrs ? $totalDose24hrs . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    // 48 hours row
    echo '<tr style="background-color: ' . ($count48hrs > 0 ? '#f0f9ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">48 Hours</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count48hrs . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count48hrs ? $totalDose48hrs . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    // 72 hours row
    echo '<tr style="background-color: ' . ($count72hrs > 0 ? '#e8f4ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">72 Hours</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count72hrs . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count72hrs ? $totalDose72hrs . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    // 7 days row
    echo '<tr style="background-color: ' . ($count7days > 0 ? '#f0f9ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">7 Days</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count7days . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count7days ? $totalDose7days . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    // 14 days row
    echo '<tr style="background-color: ' . ($count14days > 0 ? '#e8f4ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">14 Days</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count14days . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count14days ? $totalDose14days . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    // 30 days row
    echo '<tr style="background-color: ' . ($count30days > 0 ? '#f0f9ff' : '#ffffff') . ';">';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; font-weight: bold;">30 Days</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . $count30days . '</td>';
    echo '<td style="padding: 6px 15px; border: 1px solid #d1e3f3; text-align: center;">' . ($count30days ? $totalDose30days . 'mg' : '0mg') . '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';

    // Print the original items
    foreach ($dateArr as $keys => $values) {
        $str = str_replace(',', '', $keys);
        $newKey = date('Y-m-d', strtotime($str));
        $showSides = $con->query("SELECT * FROM side_effects WHERE STR_TO_DATE(daytime, '%d %M, %Y')='$newKey' ORDER BY id DESC");

        // Calculate the total cost for this day
        $dayTotalCost = 0;
        foreach ($values as $item) {
            if ($item['hasPurchaseLog']) {
                $dayTotalCost += $item['pricePerDose'];
            }
        }

        // Create a styled day header with date and cost
        echo '<div class="day-header">';
        echo '<div class="day-header-date">' . $keys . '</div>';
        if ($dayTotalCost > 0) {
            echo '<div class="day-header-cost">-$' . number_format($dayTotalCost, 2) . '</div>';
        }
        echo '</div>';
        
        // Start the medication details table
        echo '<table class="med-details-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Medication</th>';
        echo '<th>Dose</th>';
        echo '<th>Time</th>';
        echo '<th>Duration</th>';
        echo '<th>Details</th>';
        if ($dayTotalCost > 0) {
            echo '<th>Price</th>';
        }
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // List side effects in a row if present
        if ($showSides->rowCount() > 0) {
            echo '<tr>';
            echo '<td colspan="' . ($dayTotalCost > 0 ? '7' : '6') . '" style="background-color: #f8f9fa;">';
            echo '<strong>Side Effects:</strong> ';
            while ($feto = $showSides->fetch(PDO::FETCH_ASSOC)) {
                if ($feto['feelings'] == 'positive') {
                    $sideKeyword = '<span style="color:green; margin-right: 10px;">' . $feto['keyword'] . '</span>';
                } elseif ($feto['feelings'] == 'negative') {
                    $sideKeyword = '<span style="color:red; margin-right: 10px;">' . $feto['keyword'] . '</span>';
                } else {
                    $sideKeyword = '<span style="color:blue; margin-right: 10px;">' . $feto['keyword'] . '</span>';
                }
                
                echo '<a href="side_investigation.php?id=' . $feto['id'] . '&name=' . $feto['keyword'] . '" style="text-decoration:none; color:inherit;">' . $sideKeyword . '</a>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        // Add medication rows
        foreach ($values as $items) {
            $recordId = $items['id'] ?? '';
            
            echo '<tr>';
            // Medication name column
            echo '<td class="med-name"><a href="possible_sides.php?name=' . $items['searchKey'] . '" style="text-decoration:none; color:inherit;">' . $items['searchKey'] . '</a></td>';
            
            // Dose column
            echo '<td>' . $items['dose'] . ' mg</td>';
            
            // Time column
            echo '<td class="med-time">' . $items['timeOnly'] . '</td>';
            
            // Duration column
            echo '<td class="med-duration">' . $items['daystoHrs'] . ' ' . $items['timeSpent'] . '</td>';
            
            // Details column
            echo '<td>' . $items['details'] . '</td>';
            
            // Price column (if costs exist)
            if ($dayTotalCost > 0) {
                echo '<td class="med-price">';
                if ($items['pricePerDose'] > 0 && $items['hasPurchaseLog']) {
                    echo '$' . number_format($items['pricePerDose'], 2);
                } else {
                    echo '-';
                }
                echo '</td>';
            }
            
            // Actions column
            echo '<td>';
            if ($recordId) {
                echo '<button class="deleteRecord btn btn-danger btn-sm action-button" data-id="' . $recordId . '">Delete</button>';
            }
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '<br>';
    }
}

///////////// End of search records //////////////////



    echo '<br>';

    if(!isset($_GET['searchKey']) and !isset($_GET['show_all'])){
        // Pages links
        echo '<br><br>';
       if(isset($_GET['how_did_you_feel'])){
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo '<button class="btn btn-dark btn-md disabled"><b>' . $i . '</b></button> ';
            } else {
                $delete_param = isset($_GET['show_delete_buttons']) ? '&show_delete_buttons' : '';
                echo '<a href="index.php?page='.$i.'&how_did_you_feel'.$delete_param.'" class="btn btn-primary btn-md">'.$i.'</a> ';
            }
        }
       }else{
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo '<button class="btn btn-dark btn-md disabled"><b>' . $i . '</b></button> ';
            } else {
                $delete_param = isset($_GET['show_delete_buttons']) ? '&show_delete_buttons' : '';
                echo '<a href="index.php?page='.$i.$delete_param.'" class="btn btn-primary btn-md">'.$i.'</a> ';
            }
        }
       }
    
       if(isset($_GET['how_did_you_feel'])){
        $delete_param = isset($_GET['show_delete_buttons']) ? '&show_delete_buttons' : '';
        echo '<a href="index.php?show_all&how_did_you_feel'.$delete_param.'" class="btn btn-info btn-md" >Show All</a>';
       }else{
        $delete_param = isset($_GET['show_delete_buttons']) ? '&show_delete_buttons' : '';
        echo '<a href="index.php?show_all'.$delete_param.'" class="btn btn-info btn-md" >Show All</a>';
       }

    }

    echo '<br><br>';


?>



<div class="scroll-buttons">
  <div class="scroll-button" onclick="scrollToTop()"><i class="fas fa-arrow-up" style="color: white;"></i></div>
  <div class="scroll-button" onclick="scrollToBottom()"><i class="fas fa-arrow-down" style="color: white;"></i></div>
</div>

<!-- Toast notification container -->
<div id="toast-container"></div>

<script>
// Scroll functions
$(document).ready(function() {
    window.scrollToTop = function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    };
    
    window.scrollToBottom = function() {
        window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
    };
});
</script>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-container">
    <div class="modal-header">
      <h3 class="modal-title" id="modal-title">Confirm Action</h3>
      <button class="modal-close" id="modal-close">&times;</button>
    </div>
    <div class="modal-body">
      <p id="modal-message">Are you sure you want to proceed?</p>
    </div>
    <div class="modal-footer">
      <button class="modal-button modal-cancel" id="modal-cancel">Cancel</button>
      <button class="modal-button modal-confirm" id="modal-confirm">Confirm</button>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Lock and unlock functionality
    $(document).on('click', '.lock-icon', function() {
        const medName = $(this).parent().attr('data-med-name');
        showConfirmModal('Lock Medication', 'Are you sure you want to lock ' + medName + '?', function() {
            lockMedication(medName);
        });
    });

    $(document).on('click', '.unlock-icon', function() {
        const medName = $(this).parent().attr('data-med-name');
        showConfirmModal('Unlock Medication', 'Are you sure you want to unlock ' + medName + '?', function() {
            unlockMedication(medName);
        });
    });

    // Modal functions
    function showConfirmModal(title, message, confirmCallback) {
        $('#modal-title').text(title);
        $('#modal-message').text(message);
        $('#modal-confirm').off('click').on('click', function() {
            confirmCallback();
            closeModal();
        });
        $('#confirmModal').addClass('active');
    }

    function closeModal() {
        $('#confirmModal').removeClass('active');
    }

    $('#modal-close, #modal-cancel').on('click', function() {
        closeModal();
    });

    // Lock and unlock API calls
    function lockMedication(medName) {
        $.ajax({
            url: 'lock.php',
            type: 'GET',
            data: { name: medName },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('Medication locked successfully', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showToast('Error locking medication', 'error');
            }
        });
    }

    function unlockMedication(medName) {
        $.ajax({
            url: 'unlock.php',
            type: 'GET',
            data: { name: medName },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('Medication unlocked successfully', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showToast('Error unlocking medication', 'error');
            }
        });
    }

    // Toast notification function
    function showToast(message, type) {
        const toast = $('<div>')
            .addClass('toast')
            .addClass('toast-' + type)
            .text(message);
        
        $('#toast-container').append(toast);
        
        setTimeout(function() {
            toast.addClass('show');
            
            setTimeout(function() {
                toast.removeClass('show');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }, 100);
    }
});
</script>

<?php include '../footers.php'; ?>

<!-- Toast notification container -->
<div id="toast-container"></div>

<script>
// Make sure we have the delete functionality working properly
$(document).ready(function() {
    // Delete button handler
    $(document).on('click', '.deleteRecord', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var recordId = $(this).data('id');
        var $deleteButton = $(this);
        
        if (confirm('Are you sure you want to delete this record?')) {
            // Disable the button to prevent multiple clicks
            $deleteButton.prop('disabled', true);
            
            $.ajax({
                url: 'delete_record.php',
                type: 'POST',
                data: { id: recordId },
                dataType: 'json',
                success: function(response) {
                    console.log("Delete response:", response);
                    if (response.status === 'success') {
                        // Show success message
                        showToast('Record deleted successfully', 'success');
                        
                        // Update the bank balance display
                        $('.bank-balance-value').text('Balance: $' + response.formatted_balance);
                        
                        // Remove the entire medication record from the DOM
                        var $parentRow = $deleteButton.closest('tr');
                        var $parentDiv = $deleteButton.closest('div');
                        
                        if ($parentRow.length) {
                            $parentRow.fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else if ($parentDiv.length) {
                            $parentDiv.fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            // Just remove the delete button itself if we can't find a parent
                            $deleteButton.fadeOut(400, function() {
                                $(this).remove();
                            });
                            // Refresh the page after a delay
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        showToast('Error: ' + response.message, 'error');
                        $deleteButton.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Delete error:", xhr.responseText);
                    showToast('Error deleting record. Please try again.', 'error');
                    $deleteButton.prop('disabled', false);
                }
            });
        }
    });
});
</script>

</body>
</html>
