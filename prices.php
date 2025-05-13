<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';

$datenow = date("d M, Y h:i a");
$msg = '';

// Handle AJAX request to save dose price
if (isset($_POST['action']) && $_POST['action'] == 'save_dose_price') {
    header('Content-Type: application/json');
    
    try {
        $medId = isset($_POST['med_id']) ? intval($_POST['med_id']) : 0;
        $mgAmount = isset($_POST['mg_amount']) ? floatval($_POST['mg_amount']) : 0;
        $doseAmount = isset($_POST['dose_amount']) ? floatval($_POST['dose_amount']) : 0;
        $priceEgp = isset($_POST['price_egp']) ? floatval($_POST['price_egp']) : 0;
        $pricePerDose = isset($_POST['price_per_dose']) ? floatval($_POST['price_per_dose']) : 0;
        
        // Validate data
        if ($medId <= 0 || $mgAmount <= 0 || $doseAmount <= 0 || $priceEgp <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
            exit;
        }
        
        // Insert into database
        $insertQuery = $con->prepare("INSERT INTO med_prices (med_id, mg_amount, dose_amount, price_egp, price_per_dose) VALUES (?, ?, ?, ?, ?)");
        $insertQuery->execute([$medId, $mgAmount, $doseAmount, $priceEgp, $pricePerDose]);
        
        echo json_encode(['success' => true, 'message' => 'Price saved successfully']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

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
            PRIMARY KEY (`id`),
            KEY `med_id` (`med_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
        $con->exec($createTableSQL);
    } else {
        // Check if we need to add the new columns
        $columnCheckDose = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'dose_amount'");
        $columnCheckPricePerDose = $con->query("SHOW COLUMNS FROM `med_prices` LIKE 'price_per_dose'");
        
        if ($columnCheckDose->rowCount() == 0 || $columnCheckPricePerDose->rowCount() == 0) {
            // Add missing columns
            if ($columnCheckDose->rowCount() == 0) {
                $con->exec("ALTER TABLE `med_prices` ADD COLUMN `dose_amount` decimal(10,2) DEFAULT NULL AFTER `mg_amount`");
            }
            if ($columnCheckPricePerDose->rowCount() == 0) {
                $con->exec("ALTER TABLE `med_prices` ADD COLUMN `price_per_dose` decimal(10,2) DEFAULT NULL AFTER `price_egp`");
            }
        }
    }
} catch (Exception $e) {
    // Silently fail - we'll try again next time
    $msg = '<div class="alert alert-warning">Could not verify database table. Error: ' . $e->getMessage() . '</div>';
}

// Get all open medications
$query = $con->query("SELECT * FROM medlist WHERE status='open' ORDER BY name ASC");
$medications = $query->fetchAll(PDO::FETCH_ASSOC);

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
    } else {
        $med['mg_amount'] = '';
        $med['dose_amount'] = '';
        $med['price_egp'] = '';
        $med['price_per_dose'] = '';
    }
}

// Handle price submission
if (isset($_POST['submit_prices'])) {
    $success = true;
    $con->beginTransaction();
    
    try {
        foreach ($medications as $med) {
            $medId = $med['id'];
            $mgAmount = isset($_POST['mg_'.$medId]) ? $_POST['mg_'.$medId] : null;
            $priceEgp = isset($_POST['price_'.$medId]) ? $_POST['price_'.$medId] : null;
            
            // Get dose amount and calculate price per dose
            $doseAmount = isset($_POST['dose_'.$medId]) ? $_POST['dose_'.$medId] : null;
            $pricePerDose = null;
            
            // Calculate price per dose if we have all the necessary data
            if (!empty($mgAmount) && !empty($doseAmount) && !empty($priceEgp)) {
                $pricePerDose = ($doseAmount / $mgAmount) * $priceEgp;
            }
            
            // Only insert if required values are provided
            if (!empty($mgAmount) && !empty($priceEgp)) {
                $insertQuery = $con->prepare("INSERT INTO med_prices (med_id, mg_amount, dose_amount, price_egp, price_per_dose) VALUES (?, ?, ?, ?, ?)");
                $insertQuery->execute([$medId, $mgAmount, $doseAmount, $priceEgp, $pricePerDose]);
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
        }
        .med-card h5 {
            margin-bottom: 15px;
            color: #007bff;
        }
        .form-row {
            margin-bottom: 10px;
        }
        .input-group-text {
            width: 50px;
        }
        .submit-container {
            margin-top: 20px;
            margin-bottom: 40px;
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
    
    <form method="post" action="">
        <div class="row">
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
                                    <span class="input-group-text">Dose</span>
                                </div>
                                <input type="number" step="0.01" class="form-control" 
                                    name="dose_<?php echo $med['id']; ?>" 
                                    value="<?php echo htmlspecialchars($med['dose_amount']); ?>" 
                                    placeholder="Milligrams per dose">
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
                                Price per dose: <strong><span class="price-per-dose">0.00</span> EGP</strong>
                                <button type="button" class="btn btn-sm btn-primary save-dose-price-btn ml-2">Save</button>
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
            
            <?php if (count($medications) === 0): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No open medications found. Add medications from the home page.
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($medications) > 0): ?>
        <div class="submit-container text-center">
            <button type="submit" name="submit_prices" class="btn btn-primary btn-lg">Save Prices</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    // Calculate price per mg and price per dose when fields are filled
    function calculatePrices() {
        const cards = document.querySelectorAll('.med-card');
        
        cards.forEach(card => {
            const mgInput = card.querySelector('input[name^="mg_"]');
            const doseInput = card.querySelector('input[name^="dose_"]');
            const priceInput = card.querySelector('input[name^="price_"]');
            const pricePerMgSpan = card.querySelector('.price-per-mg');
            const pricePerMgContainer = card.querySelector('.price-per-mg-container');
            const pricePerDoseSpan = card.querySelector('.price-per-dose');
            const pricePerDoseContainer = card.querySelector('.price-per-dose-container');
            
            const updatePrices = () => {
                const mg = parseFloat(mgInput.value);
                const dose = parseFloat(doseInput.value);
                const price = parseFloat(priceInput.value);
                
                // Calculate price per mg
                if (mg > 0 && price > 0) {
                    const pricePerMg = price / mg;
                    pricePerMgSpan.textContent = pricePerMg.toFixed(2);
                    pricePerMgContainer.style.display = 'block';
                } else {
                    pricePerMgContainer.style.display = 'none';
                }
                
                // Calculate price per dose
                if (mg > 0 && dose > 0 && price > 0) {
                    const pricePerDose = (dose / mg) * price;
                    pricePerDoseSpan.textContent = pricePerDose.toFixed(2);
                    pricePerDoseContainer.style.display = 'block';
                    
                    // Show save button
                    const saveBtn = card.querySelector('.save-dose-price-btn');
                    if (saveBtn) {
                        saveBtn.style.display = 'inline-block';
                        saveBtn.dataset.medId = card.dataset.medId;
                        saveBtn.dataset.mg = mg;
                        saveBtn.dataset.dose = dose;
                        saveBtn.dataset.price = price;
                        saveBtn.dataset.pricePerDose = pricePerDose.toFixed(2);
                    }
                } else {
                    pricePerDoseContainer.style.display = 'none';
                    
                    // Hide save button when values are invalid
                    const saveBtn = card.querySelector('.save-dose-price-btn');
                    if (saveBtn) {
                        saveBtn.style.display = 'none';
                    }
                }
            };
            
            mgInput.addEventListener('input', updatePrices);
            doseInput.addEventListener('input', updatePrices);
            priceInput.addEventListener('input', updatePrices);
            
            // Initial calculation
            updatePrices();
        });
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculatePrices();
        initSaveButtons();
    });
    
    // Initialize save buttons
    function initSaveButtons() {
        document.querySelectorAll('.save-dose-price-btn').forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.card');
                const medId = card.dataset.medId;
                const mgInput = card.querySelector('.mg-input');
                const doseInput = card.querySelector('.dose-input');
                const priceInput = card.querySelector('.price-input');
                const saveStatus = card.querySelector('.save-status');
                
                // Get values
                const mg = parseFloat(mgInput.value);
                const dose = parseFloat(doseInput.value);
                const price = parseFloat(priceInput.value);
                const pricePerDose = (dose / mg) * price;
                
                // Disable button during save
                this.disabled = true;
                saveStatus.textContent = 'Saving...';
                
                // Save to database via AJAX
                const formData = new FormData();
                formData.append('action', 'save_dose_price');
                formData.append('med_id', medId);
                formData.append('mg_amount', mg);
                formData.append('dose_amount', dose);
                formData.append('price_egp', price);
                formData.append('price_per_dose', pricePerDose.toFixed(2));
                
                fetch('prices.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        saveStatus.textContent = 'Saved!';
                        saveStatus.className = 'save-status ml-2 text-success';
                        setTimeout(() => {
                            saveStatus.textContent = '';
                        }, 3000);
                    } else {
                        saveStatus.textContent = 'Error: ' + data.message;
                        saveStatus.className = 'save-status ml-2 text-danger';
                    }
                    this.disabled = false;
                })
                .catch(error => {
                    saveStatus.textContent = 'Error: ' + error.message;
                    saveStatus.className = 'save-status ml-2 text-danger';
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
        window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
    }
</script>
</body>
</html>

<?php include '../footers.php'; ?>

<div class="scroll-buttons">
  <div class="scroll-button up" onclick="scrollToTop()"><img src="img/arrow_up.png"></div>
  <div class="scroll-button" onclick="scrollToBottom()"><img src="img/arrow_down.png"></div>
</div>