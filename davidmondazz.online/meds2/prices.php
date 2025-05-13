<?php
session_start();
date_default_timezone_set("Africa/Cairo");

// Handle AJAX request to save dose price - MOVED TO TOP BEFORE INCLUDES
if (isset($_POST['action']) && $_POST['action'] == 'save_dose_price') {
    // Ensure we're using the database
    include '../checkSession.php';
    include '../func.php';
    include 'db.php';
    
    // Ensure no output before this point
    if (ob_get_length()) ob_clean();
    
    // Start fresh output buffer
    ob_start();
    
    // Set the correct content type
    header('Content-Type: application/json');
    
    // Create med_prices table if it doesn't exist
    try {
        $tableCheck = $con->query("SHOW TABLES LIKE 'med_prices'");
        if ($tableCheck->rowCount() == 0) {
            // Table doesn't exist, create it
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `med_prices` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `med_id` int(11) NOT NULL,
                `mg_amount` decimal(10,2) DEFAULT NULL,
                `dose_amount` decimal(10,2) DEFAULT NULL,
                `price_egp` decimal(10,2) DEFAULT NULL,
                `price_per_dose` decimal(10,2) DEFAULT NULL,
                `update_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `custom_price` varchar(50) DEFAULT NULL,
                `dollar_dose_price` decimal(10,2) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `med_id` (`med_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
            $con->exec($createTableSQL);
        } else {
            // Check if we need to add the new columns
            $columnCheckDose = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'dose_amount'");
            $columnCheckPricePerDose = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'price_per_dose'");
            $columnCheckCustomPrice = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'custom_price'");
            $columnCheckDollarPrice = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'dollar_dose_price'");
            
            if ($columnCheckDose->rowCount() == 0 || $columnCheckPricePerDose->rowCount() == 0 || 
                $columnCheckCustomPrice->rowCount() == 0 || $columnCheckDollarPrice->rowCount() == 0) {
                // Add missing columns
                if ($columnCheckDose->rowCount() == 0) {
                    $con->exec("ALTER TABLE `med_prices` ADD COLUMN `dose_amount` decimal(10,2) DEFAULT NULL AFTER `mg_amount`");
                }
                if ($columnCheckPricePerDose->rowCount() == 0) {
                    $con->exec("ALTER TABLE `med_prices` ADD COLUMN `price_per_dose` decimal(10,2) DEFAULT NULL AFTER `price_egp`");
                }
                if ($columnCheckCustomPrice->rowCount() == 0) {
                    $con->exec("ALTER TABLE `med_prices` ADD COLUMN `custom_price` varchar(50) DEFAULT NULL AFTER `price_per_dose`");
                }
                if ($columnCheckDollarPrice->rowCount() == 0) {
                    $con->exec("ALTER TABLE `med_prices` ADD COLUMN `dollar_dose_price` decimal(10,2) DEFAULT NULL AFTER `custom_price`");
                }
            }
        }
    } catch (Exception $e) {
        // Log the error and return a JSON response, as this is critical for the AJAX handler
        error_log("Error during table schema check in AJAX handler: " . $e->getMessage());
        ob_end_clean(); // Clean buffer before sending JSON
        echo json_encode(['success' => false, 'message' => 'Database schema error: ' . $e->getMessage()]);
        exit;
    }

    try {
        $medId = isset($_POST['med_id']) ? intval($_POST['med_id']) : 0;
        $mgAmount = isset($_POST['mg_amount']) ? floatval($_POST['mg_amount']) : 0;
        $doseAmount = isset($_POST['dose_amount']) ? floatval($_POST['dose_amount']) : 0;
        $priceEgp = isset($_POST['price_egp']) ? floatval($_POST['price_egp']) : 0;
        $pricePerDose = isset($_POST['price_per_dose']) ? floatval($_POST['price_per_dose']) : 0;
        $customPrice = isset($_POST['custom_price']) ? $_POST['custom_price'] : null;
        
        // Debug info
        error_log("Saving med price: ID=$medId, mg=$mgAmount, dose=$doseAmount, price=$priceEgp, custom=$customPrice");
        
        // Validate data - only require custom price OR (mg amount AND regular price)
        if ($medId <= 0 || 
            (empty($customPrice) && ($mgAmount <= 0 || $doseAmount <= 0 || $priceEgp <= 0))) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
            exit;
        }
        
        // Calculate dollar price
        $pricePaid = !empty($customPrice) ? $customPrice : $pricePerDose;
        $dollarPrice = 0;
        
        if ($pricePaid > 0) {
            // Include BankConfig if not already included
            if (!class_exists('BankConfig')) {
                require_once 'BankConfig.php';
                BankConfig::initialize();
            }
            $dollarPrice = BankConfig::convertToUSD($pricePaid);
        }
        
        // Start transaction
        $con->beginTransaction();
        try {
            // Insert into med_prices table
            $insertQuery = $con->prepare("INSERT INTO med_prices (med_id, mg_amount, dose_amount, price_egp, price_per_dose, custom_price, dollar_dose_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertQuery->execute([$medId, $mgAmount, $doseAmount, $priceEgp, $pricePerDose, $customPrice, $dollarPrice]);
            
            // Get medication name for the log
            $medQuery = $con->prepare("SELECT name FROM medlist WHERE id = ?");
            $medQuery->execute([$medId]);
            $medName = $medQuery->fetchColumn();
            
            // Create purchase log entry
            $purchaseQuery = $con->prepare("INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) VALUES (?, ?, ?, NOW())");
            $purchaseQuery->execute([$medId, $medName, $customPrice ?: $pricePerDose]);
            
            $con->commit();
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
        
        // Clear any potential output
        ob_end_clean();
        
        // Return success JSON
        echo json_encode(['success' => true, 'message' => 'Price saved successfully']);
        exit;
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Error saving med price: " . $e->getMessage());
        
        // Clear any potential output
        ob_end_clean();
        
        // Return error JSON
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    
    // In case we didn't exit properly
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unknown error occurred']);
    exit;
}

// For non-AJAX requests, include all required files
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';

$datenow = date("d M, Y h:i a");
$msg = '';

// Helper function to highlight search term in text
function highlightSearchTerm($text, $search) {
    if (empty($search)) return htmlspecialchars($text);
    
    $search = preg_quote($search, '/');
    return preg_replace('/(' . $search . ')/i', '<span class="highlight-text">$1</span>', htmlspecialchars($text));
}

// Get all open medications (for regular view)
$query_base = "SELECT * FROM medlist WHERE status='open'";

// Base query for all medications when searching
$search_query_base = "SELECT * FROM medlist";

// Apply search filter if present
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    
    // When searching, look through ALL medications (not just open ones)
    $stmt = $con->prepare($search_query_base . " WHERE name LIKE ? ORDER BY name ASC");
    $stmt->execute([$search]);
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // For regular view (no search), show only open medications
    $medications = $con->query($query_base . " ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// For each medication, get its price info if it exists
foreach ($medications as &$med) {
    $priceQuery = $con->prepare("SELECT * FROM med_prices WHERE med_id = ? ORDER BY update_date DESC LIMIT 1");
    $priceQuery->execute([$med['id']]);
    $priceData = $priceQuery->fetch(PDO::FETCH_ASSOC);
    
    if ($priceData) {
        $med['mg_amount'] = $priceData['mg_amount'];
        $med['dose_amount'] = $priceData['dose_amount'];
        $med['price_egp'] = $priceData['price_egp'];
        $med['price_per_dose'] = $priceData['price_per_dose'];
        $med['custom_price'] = $priceData['custom_price'] ?? '';
    } else {
        $med['mg_amount'] = '';
        $med['dose_amount'] = '';
        $med['price_egp'] = '';
        $med['price_per_dose'] = '';
        $med['custom_price'] = '';
    }
}
unset($med); // Unset the reference to the last element

// Handle price submission
if (isset($_POST['submit_prices'])) {
    $success = true;
    $con->beginTransaction();
    
    try {
        foreach ($medications as $med) {
            $medId = $med['id'];
            $mgAmount = isset($_POST['mg_'.$medId]) ? $_POST['mg_'.$medId] : null;
            $priceEgp = isset($_POST['price_'.$medId]) ? $_POST['price_'.$medId] : null;
            $customPrice = isset($_POST['custom_price_'.$medId]) ? $_POST['custom_price_'.$medId] : null;
            
            // Get dose amount and calculate price per dose
            $doseAmount = isset($_POST['dose_'.$medId]) ? $_POST['dose_'.$medId] : null;
            $pricePerDose = null;
            
            // Calculate price per dose if we have all the necessary data
            if (!empty($mgAmount) && !empty($doseAmount) && !empty($priceEgp)) {
                $pricePerDose = ($doseAmount / $mgAmount) * $priceEgp;
            }
            
            // Insert if either custom price is provided OR we have required values
            if (!empty($customPrice) || (!empty($mgAmount) && !empty($priceEgp))) {
                // Calculate dollar price
                $pricePaid = !empty($customPrice) ? $customPrice : $pricePerDose;
                $dollarPrice = 0;
                
                if ($pricePaid > 0) {
                    // Include BankConfig if not already included
                    if (!class_exists('BankConfig')) {
                        require_once 'BankConfig.php';
                        BankConfig::initialize();
                    }
                    $dollarPrice = BankConfig::convertToUSD($pricePaid);
                }
                
                // Insert price record
                $insertQuery = $con->prepare("INSERT INTO med_prices (med_id, mg_amount, dose_amount, price_egp, price_per_dose, custom_price, dollar_dose_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insertQuery->execute([$medId, $mgAmount, $doseAmount, $priceEgp, $pricePerDose, $customPrice, $dollarPrice]);

                // Create purchase log entry
                $medName = $med['name'];
                if ($pricePaid > 0) {
                    $purchaseQuery = $con->prepare("INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) VALUES (?, ?, ?, NOW())");
                    $purchaseQuery->execute([$medId, $medName, $pricePaid]);
                }
            }
        }
        
        $con->commit();
        $msg = '<div class="alert alert-success">Medication prices updated successfully!</div>';
        
        // Reload the page to show the updated values
        header("Location: prices.php?updated=1");
        exit;
    } catch (Exception $e) {
        $con->rollBack();
        $msg = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        $success = false;
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Medication Prices</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Prevent any automatic inclusion of other files -->
    <meta name="no-footers" content="true">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .med-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%; /* Ensure cards take full width */
        }
        .med-card h5 {
            margin-bottom: 15px;
            color: #007bff;
        }
        .form-row {
            margin-bottom: 10px;
            width: 100%; /* Ensure form rows take full width */
        }
        .input-group-text {
            width: 50px;
        }
        .submit-container {
            margin-top: 20px;
            margin-bottom: 40px;
        }
        /* Search box styling */
        .search-container {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            width: 100%; /* Full width search container */
        }
        .search-container .input-group {
            max-width: 100%; /* Allow search input to be wider */
            width: 100%;
        }
        .search-shortcut {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        /* Highlight search term in results */
        .highlight-text {
            background-color: #ffff99;
            padding: 2px;
            border-radius: 2px;
        }
        /* Ensure search results take full width */
        .medication-results {
            width: 100%;
        }
        .medication-results .row {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
        /* Fix for col-md-6 to ensure proper width */
        @media (min-width: 768px) {
            .medication-results .col-md-6 {
                width: 50%;
                padding: 0 15px;
            }
        }
        /* Display as table for search results */
        .table-layout {
            display: block;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-row {
            margin-bottom: 15px;
            display: block;
            width: 100%;
        }
        .table-cell {
            display: block;
            width: 100%;
            padding: 0;
        }
        
        /* Make search results more prominent */
        .table-layout .med-card {
            border: 1px solid #007bff;
            border-left: 5px solid #007bff;
            background-color: #f8f9fa;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }
        
        .table-layout .med-card h5 {
            font-size: 1.25rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        /* Add "Search Results" header */
        .search-results-header {
            background-color: #e3f2fd;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 5px solid #007bff;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        /* Styling for closed medications */
        .closed-med {
            background-color: #f8f9fa;
            border-left: 5px solid #6c757d !important;
        }
        
        .closed-med h5 {
            color: #6c757d !important;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Medication prices were updated successfully!
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>
    <h2>Medication Prices</h2>
    <p>Manage prices for your open medications</p>
    
    <?php echo $msg; ?>
    
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">Back to Home</a>
    </div>
    
    <div class="mb-3">
        <p class="small text-muted">
            By default, only open medications are shown. Use the search to find any medication (including closed ones). All medications can be edited regardless of status.
        </p>
    </div>
    
    <!-- Add search functionality -->
    <div class="search-container">
        <form method="get" action="" class="form-inline">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search all medications (open & closed)..." 
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                    autocomplete="off">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                        <a href="prices.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="search-shortcut">
                Press <kbd>/</kbd> or <kbd>Ctrl</kbd>+<kbd>F</kbd> to search
            </div>
        </form>
    </div>
    
    <!-- FIXED STRUCTURE: ONE SINGLE FORM -->
    <form method="post" action="" id="medicationForm">
        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
        <!-- Table layout for search results -->
        <div class="search-results-header">
            Search Results for "<?php echo htmlspecialchars($_GET['search']); ?>" 
            (<?php echo count($medications); ?> medication<?php echo count($medications) !== 1 ? 's' : ''; ?> found)
            <small class="d-block mt-1">Includes both open and closed medications - all can be edited</small>
        </div>
        <div class="table-layout">
            <?php if (count($medications) === 0): ?>
            <div class="alert alert-info">
                No medications found matching "<?php echo htmlspecialchars($_GET['search']); ?>".
                <a href="prices.php" class="alert-link">Clear search</a>
            </div>
            <?php else: ?>
                <?php foreach ($medications as $med): ?>
                <div class="table-row">
                    <div class="table-cell" style="width: 100%;">
                        <div class="med-card <?php echo $med['status'] !== 'open' ? 'closed-med' : ''; ?>">
                            <h5>
                                <?php echo highlightSearchTerm($med['name'], $_GET['search']); ?>
                                <?php if($med['status'] !== 'open'): ?>
                                    <span class="badge badge-secondary ml-2" title="This medication is not active">Closed</span>
                                <?php else: ?>
                                    <span class="badge badge-success ml-2">Open</span>
                                <?php endif; ?>
                            </h5>
                            
                            <div class="form-row">
                                <div class="col-12">
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">mg</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control" 
                                            name="mg_<?php echo $med['id']; ?>" 
                                            value="<?php echo htmlspecialchars($med['mg_amount']); ?>" 
                                            placeholder="Total milligrams in package">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="col-12">
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">EGP</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control" 
                                            name="price_<?php echo $med['id']; ?>" 
                                            value="<?php echo htmlspecialchars($med['price_egp']); ?>" 
                                            placeholder="Price in EGP">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add custom price field -->
                            <div class="form-row">
                                <div class="col-12">
                                    <div class="input-group mb-2">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" title="Custom price that won't be auto-calculated">Custom</span>
                                        </div>
                                        <input type="number" step="0.01" class="form-control custom-price" 
                                            name="custom_price_<?php echo $med['id']; ?>" 
                                            value="<?php echo htmlspecialchars($med['custom_price']); ?>"
                                            placeholder="Custom price (optional)">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row price-per-mg-container" style="display: none;">
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        Price per mg: <strong><span class="price-per-mg">0.00</span> EGP</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row price-per-dose-container" style="display: none;">
                                <div class="col-12">
                                    <div class="alert alert-success text-center">
                                        <span class="price-label">Price per dose:</span> <strong><span class="price-per-dose">0.00</span> EGP</strong>
                                        <span class="save-status ml-2"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row mt-3">
                                <div class="col-12 text-center">
                                    <button type="button" class="btn btn-success save-med-btn" data-med-id="<?php echo $med['id']; ?>">
                                        <i class="fas fa-save"></i> Save Medication
                                    </button>
                                    <span class="med-save-status ml-2"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Regular grid layout for all medications -->
        <div class="row">
            <?php if (count($medications) === 0): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No open medications found. Add medications from the home page.
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($medications as $med): ?>
                <div class="col-md-6">
                    <div class="med-card">
                        <h5><?php echo htmlspecialchars($med['name']); ?></h5>
                        
                        <div class="form-row">
                            <div class="col-12">
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">mg</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" 
                                        name="mg_<?php echo $med['id']; ?>" 
                                        value="<?php echo htmlspecialchars($med['mg_amount']); ?>" 
                                        placeholder="Total milligrams in package">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="col-12">
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">EGP</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" 
                                        name="price_<?php echo $med['id']; ?>" 
                                        value="<?php echo htmlspecialchars($med['price_egp']); ?>" 
                                        placeholder="Price in EGP">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Add custom price field -->
                        <div class="form-row">
                            <div class="col-12">
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" title="Custom price that won't be auto-calculated">Custom</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control custom-price" 
                                        name="custom_price_<?php echo $med['id']; ?>" 
                                        value="<?php echo htmlspecialchars($med['custom_price']); ?>"
                                        placeholder="Custom price (optional)">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row price-per-mg-container" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    Price per mg: <strong><span class="price-per-mg">0.00</span> EGP</strong>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row price-per-dose-container" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-success text-center">
                                    <span class="price-label">Price per dose:</span> <strong><span class="price-per-dose">0.00</span> EGP</strong>
                                    <span class="save-status ml-2"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row mt-3">
                            <div class="col-12 text-center">
                                <button type="button" class="btn btn-success save-med-btn" data-med-id="<?php echo $med['id']; ?>">
                                    <i class="fas fa-save"></i> Save Medication
                                </button>
                                <span class="med-save-status ml-2"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    // Function to extract dose from medication name
    function extractDoseFromMedName(medName) {
        // First try to match patterns like 12.5mg, 1.25mg, 50mg, etc. (without space)
        const doseRegexNoSpace = /(\d+(?:\.\d+)?)\s*mg\b/i;
        let match = medName.match(doseRegexNoSpace);
        
        if (match && match[1]) {
            return parseFloat(match[1]);
        }
        
        // If no match, try to find just a number at the end of the name (like "moda 5")
        // This regex looks for a number at the end of the string or followed by space
        const doseRegexNumberOnly = /\s(\d+(?:\.\d+)?)(?:\s|$)/;
        match = medName.match(doseRegexNumberOnly);
        
        if (match && match[1]) {
            return parseFloat(match[1]);
        }
        
        // If no match found, return a default value of 1 (assuming 1mg as default)
        return 1;
    }
    
    // Focus search box on keyboard shortcut
    document.addEventListener('keydown', function(e) {
        // Focus search box on '/' key or Ctrl+F
        if ((e.key === '/' || (e.ctrlKey && e.key === 'f')) && 
            !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
    });
    
    // Calculate price per mg and price per dose when fields are filled
    function calculatePrices() {
        const cards = document.querySelectorAll('.med-card');
        
        cards.forEach(card => {
            const medNameElement = card.querySelector('h5');
            const medName = medNameElement ? medNameElement.textContent.trim() : '';
            const mgInput = card.querySelector('input[name^="mg_"]');
            const priceInput = card.querySelector('input[name^="price_"]');
            const customPriceInput = card.querySelector('input[name^="custom_price_"]');
            const pricePerMgSpan = card.querySelector('.price-per-mg');
            const pricePerMgContainer = card.querySelector('.price-per-mg-container');
            const pricePerDoseSpan = card.querySelector('.price-per-dose');
            const pricePerDoseContainer = card.querySelector('.price-per-dose-container');
            
            // Extract dose from medication name
            const dose = extractDoseFromMedName(medName);
            
            // Skip processing if this is a closed medication (no input fields)
            if (!mgInput || !priceInput) {
                return; // Skip this card if it's a closed medication with no input fields
            }
            
            const updatePrices = () => {
                const mg = parseFloat(mgInput.value);
                const price = parseFloat(priceInput.value);
                const customPrice = customPriceInput ? customPriceInput.value : '';
                const hasCustomPrice = customPrice !== '' && !isNaN(parseFloat(customPrice));
                
                // Calculate price per mg
                if (mg > 0 && price > 0) {
                    const pricePerMg = price / mg;
                    pricePerMgSpan.textContent = pricePerMg.toFixed(2);
                    pricePerMgContainer.style.display = 'block';
                } else {
                    pricePerMgContainer.style.display = 'none';
                }
                
                // Calculate price per dose
                if ((mg > 0 && dose > 0 && price > 0) || hasCustomPrice) {
                    let pricePerDose;
                    
                    // If custom price is set, use that directly as the price per dose
                    if (hasCustomPrice) {
                        pricePerDose = parseFloat(customPrice);
                        pricePerDoseSpan.textContent = pricePerDose.toFixed(2);
                        pricePerDoseContainer.style.display = 'block';
                        
                        // Change the label to indicate it's a custom price
                        const priceLabel = pricePerDoseContainer.querySelector('.price-label');
                        if (priceLabel) {
                            priceLabel.textContent = 'Custom Price:';
                        }
                        
                        // Add alert styling for custom price
                        const alertDiv = pricePerDoseContainer.querySelector('.alert');
                        if (alertDiv) {
                            alertDiv.className = 'alert alert-warning text-center';
                        }
                    } else {
                        // Use calculated price if no custom price
                        pricePerDose = (dose / mg) * price;
                        pricePerDoseSpan.textContent = pricePerDose.toFixed(2);
                        pricePerDoseContainer.style.display = 'block';
                        
                        // Restore original label for calculated price
                        const priceLabel = pricePerDoseContainer.querySelector('.price-label');
                        if (priceLabel) {
                            priceLabel.textContent = 'Price per dose:';
                        }
                        
                        // Restore original alert styling
                        const alertDiv = pricePerDoseContainer.querySelector('.alert');
                        if (alertDiv) {
                            alertDiv.className = 'alert alert-success text-center';
                        }
                    }
                    
                    // Show save button
                    const saveBtn = card.querySelector('.save-med-btn');
                    if (saveBtn) {
                        saveBtn.style.display = 'inline-block';
                    }
                } else {
                    pricePerDoseContainer.style.display = 'none';
                }
            };
            
            // Only add event listeners if elements exist
            if (mgInput) {
                mgInput.addEventListener('input', updatePrices);
            }
            
            if (priceInput) {
                priceInput.addEventListener('input', updatePrices);
            }
            
            if (customPriceInput) {
                customPriceInput.addEventListener('input', updatePrices);
            }
            
            // Initial calculation
            updatePrices();
        });
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculatePrices();
        initSaveButtons();
        
        // Create and add scroll buttons - but first check if they already exist
        if (!document.querySelector('.scroll-buttons')) {
            const scrollButtonsDiv = document.createElement('div');
            scrollButtonsDiv.className = 'scroll-buttons';
            
            const upButton = document.createElement('div');
            upButton.className = 'scroll-button up';
            upButton.onclick = function() { scrollToTop(); };
            const upImg = document.createElement('img');
            upImg.src = 'img/arrow_up.png';
            upButton.appendChild(upImg);
            
            const downButton = document.createElement('div');
            downButton.className = 'scroll-button';
            downButton.onclick = function() { scrollToBottom(); };
            const downImg = document.createElement('img');
            downImg.src = 'img/arrow_down.png';
            downButton.appendChild(downImg);
            
            scrollButtonsDiv.appendChild(upButton);
            scrollButtonsDiv.appendChild(downButton);
            
            document.body.appendChild(scrollButtonsDiv);
        }
    });
    
    // Initialize save buttons
    function initSaveButtons() {
        // Initialize medication save buttons
        document.querySelectorAll('.save-med-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Get parent elements and inputs with better error handling
                const medCard = this.closest('.med-card') || this.closest('.form-row')?.closest('.med-card');
                if (!medCard) {
                    console.error('Could not find parent med-card element');
                    return;
                }
                
                const medId = this.getAttribute('data-med-id');
                if (!medId) {
                    console.error('No med-id attribute found on button');
                    return;
                }
                
                const medNameElement = medCard.querySelector('h5');
                const medName = medNameElement ? medNameElement.textContent.trim() : '';
                
                // More flexible parent element selection
                const parentEl = this.closest('.col-md-6') || medCard;
                const mgInput = parentEl.querySelector('input[name^="mg_"]');
                const priceInput = parentEl.querySelector('input[name^="price_"]');
                const customPriceInput = parentEl.querySelector('input[name^="custom_price_"]');
                
                if (!mgInput || !priceInput) {
                    console.error('Could not find required input fields');
                    return;
                }
                
                const saveStatus = this.nextElementSibling;
                
                // Get values
                const mg = parseFloat(mgInput.value);
                const dose = extractDoseFromMedName(medName);
                const price = parseFloat(priceInput.value);
                const customPrice = customPriceInput ? customPriceInput.value : '';
                const pricePerDose = mg > 0 && dose > 0 ? (dose / mg) * price : 0;
                const hasCustomPrice = customPrice !== '' && !isNaN(parseFloat(customPrice));
                
                // Validate inputs - allow either price or custom price
                if ((customPrice === '' || customPrice === null) && 
                    (isNaN(mg) || mg <= 0 || isNaN(price) || price <= 0)) {
                    saveStatus.textContent = 'Error: You must provide either a custom price or both milligrams and regular price';
                    saveStatus.className = 'med-save-status ml-2 text-danger';
                    return;
                }
                
                // Disable button during save
                this.disabled = true;
                saveStatus.textContent = 'Saving...';
                saveStatus.className = 'med-save-status ml-2 text-warning';
                
                // Save to database via AJAX
                const formData = new FormData();
                formData.append('action', 'save_dose_price');
                formData.append('med_id', medId);
                formData.append('mg_amount', mg);
                formData.append('dose_amount', dose); // Dose extracted from medication name
                formData.append('price_egp', price);
                formData.append('price_per_dose', hasCustomPrice ? parseFloat(customPrice).toFixed(2) : pricePerDose.toFixed(2));
                formData.append('custom_price', customPrice);
                
                fetch('prices.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    // First check if the response is OK
                    if (!response.ok) {
                        throw new Error(`Server responded with status ${response.status}`);
                    }
                    
                    // Try to parse as JSON, but gracefully handle errors
                    return response.text().then(text => {
                        try {
                            // First check if the response starts with HTML
                            if (text.trim().toLowerCase().startsWith('<!doctype html>') || 
                                text.trim().toLowerCase().startsWith('<html')) {
                                console.error('Server returned HTML instead of JSON:', text.substring(0, 100));
                                throw new Error('Server returned HTML instead of JSON. Check server configuration.');
                            }
                            
                            // Try to parse the response as JSON
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Failed to parse response as JSON:', text.substring(0, 300));
                            throw new Error('Server response was not valid JSON. Check the server logs.');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        saveStatus.textContent = 'Medication saved!';
                        saveStatus.className = 'med-save-status ml-2 text-success';
                        setTimeout(() => {
                            saveStatus.textContent = '';
                        }, 3000);
                    } else {
                        saveStatus.textContent = 'Error: ' + data.message;
                        saveStatus.className = 'med-save-status ml-2 text-danger';
                    }
                    this.disabled = false;
                })
                .catch(error => {
                    console.error('Save error:', error);
                    saveStatus.textContent = 'Error: ' + error.message;
                    saveStatus.className = 'med-save-status ml-2 text-danger';
                    this.disabled = false;
                });
            });
        });
    }
    
    // Functions for scroll buttons
    function scrollToTop() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    }
    
    function scrollToBottom() {
        if (document && document.body) {
            window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
        }
    }
    
    // Debug duplicate tables issue
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Script execution completed in prices.php');
        
        // Count how many table elements exist on the page
        const tableLayouts = document.querySelectorAll('.table-layout');
        console.log('Number of table-layout elements:', tableLayouts.length);
        
        const rows = document.querySelectorAll('.row');
        console.log('Number of row elements:', rows.length);
        
        // Check for any potential duplicate HTML elements
        const bodyChildren = document.body.children;
        console.log('Body has', bodyChildren.length, 'direct children');
    });
</script>
</body>
</html>
<?php 
// We don't need anything after the HTML - this was causing duplication
// The exit prevents any other file from being included after this point
exit();
?>
