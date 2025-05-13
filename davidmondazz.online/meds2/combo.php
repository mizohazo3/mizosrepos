<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';
include '../countdown.php';
$datenow = date("d M, Y h:i a");

// Initialize the combo session variable if it doesn't exist
if (!isset($_SESSION['med_combo'])) {
    $_SESSION['med_combo'] = array();
}

// Add a medication to the combo
if (isset($_GET['add_med']) && !empty($_GET['add_med'])) {
    $med_name = $_GET['add_med'];
    
    // Check if medication exists in the database
    $check = $con->prepare("SELECT * FROM medlist WHERE name = ?");
    $check->execute([$med_name]);
    
    if ($check->rowCount() > 0) {
        // Add to combo if not already in the list
        if (!in_array($med_name, $_SESSION['med_combo'])) {
            $_SESSION['med_combo'][] = $med_name;
        }
        
        // If redirected from search, go back to search
        if (isset($_GET['from_search']) && isset($_GET['searchKey'])) {
            header("Location: index.php?searchKey=" . urlencode($_GET['searchKey']) . "&search=Search&combo_added=1");
            exit;
        } else {
            header("Location: combo.php?added=1");
            exit;
        }
    }
}

// Remove a medication from the combo
if (isset($_GET['remove_med']) && !empty($_GET['remove_med'])) {
    $med_name = $_GET['remove_med'];
    $key = array_search($med_name, $_SESSION['med_combo']);
    
    if ($key !== false) {
        unset($_SESSION['med_combo'][$key]);
        // Re-index the array
        $_SESSION['med_combo'] = array_values($_SESSION['med_combo']);
    }
    
    header("Location: combo.php?removed=1");
    exit;
}

// Take all medications in the combo at once
if (isset($_GET['take_all'])) {
    // Require the BankConfig class
    require 'BankConfig.php';
    
    // Initialize BankConfig
    BankConfig::initialize();
    
    // Get user ID from session - adjust this according to your auth system
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '1'; // Default to '1' if not set
    
    // Track total medication cost for this combo
    $totalCost = 0;
    
    foreach ($_SESSION['med_combo'] as $med) {
        // Get medication ID to find its price
        $medQuery = $con->prepare("SELECT id FROM medlist WHERE name = ?");
        $medQuery->execute([$med]);
        $medData = $medQuery->fetch(PDO::FETCH_ASSOC);
        $medId = isset($medData['id']) ? $medData['id'] : null;
        
        // If we have a med ID, get its price
        if ($medId) {
            $medCost = getMedicationPricePerDose($medId, $con, true);
            if ($medCost && $medCost > 0) {
                $totalCost += floatval($medCost);
            }
        }
        
        // Insert each medication into the database as taken
        $insert = $con->prepare("INSERT INTO medtrack (medname, dose_date, details) VALUES (?, ?, ?)");
        $insert->execute([$med, $datenow, 'Taken as part of combo']);
        
        // Update the lastdose in the medlist table
        $update = $con->prepare("UPDATE medlist SET lastdose = ? WHERE name = ?");
        $update->execute([$datenow, $med]);
    }
    
    // If there's a total cost, update the balance in timer8 database
    if ($totalCost > 0) {
        // Add the combo name to track in purchase_logs
        $comboName = "Combo: " . implode(" + ", $_SESSION['med_combo']);
        BankConfig::updateBalance($userId, $totalCost, $comboName);
    }
    
    header("Location: combo.php?taken=1&cost=" . $totalCost);
    exit;
}

// Clear the entire combo
if (isset($_GET['clear_combo'])) {
    $_SESSION['med_combo'] = array();
    header("Location: combo.php?cleared=1");
    exit;
}

// Save the combo with a name
if (isset($_POST['save_combo']) && !empty($_POST['combo_name'])) {
    $combo_name = $_POST['combo_name'];
    $combo_meds = implode(',', $_SESSION['med_combo']);
    
    // Check if combo name already exists
    $check = $con->prepare("SELECT * FROM med_combos WHERE combo_name = ?");
    $check->execute([$combo_name]);
    
    if ($check->rowCount() > 0) {
        // Update existing combo
        $update = $con->prepare("UPDATE med_combos SET medications = ? WHERE combo_name = ?");
        $update->execute([$combo_meds, $combo_name]);
    } else {
        // Create new combo
        $insert = $con->prepare("INSERT INTO med_combos (combo_name, medications, created_date) VALUES (?, ?, ?)");
        $insert->execute([$combo_name, $combo_meds, $datenow]);
    }
    
    header("Location: combo.php?saved=1");
    exit;
}

// Load a saved combo
if (isset($_GET['load_combo']) && !empty($_GET['load_combo'])) {
    $combo_name = $_GET['load_combo'];
    
    $load = $con->prepare("SELECT * FROM med_combos WHERE combo_name = ?");
    $load->execute([$combo_name]);
    
    if ($load->rowCount() > 0) {
        $combo = $load->fetch();
        $_SESSION['med_combo'] = explode(',', $combo['medications']);
    }
    
    header("Location: combo.php?loaded=1");
    exit;
}

// Delete a saved combo
if (isset($_GET['delete_combo']) && !empty($_GET['delete_combo'])) {
    $combo_name = $_GET['delete_combo'];
    
    $delete = $con->prepare("DELETE FROM med_combos WHERE combo_name = ?");
    $delete->execute([$combo_name]);
    
    header("Location: combo.php?deleted=1");
    exit;
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$mainDomainURL = $protocol . "://" . $host;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medication Combo Manager</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script src="js/jquery-3.6.0.min.js"></script>
    <style>
        .combo-list {
            margin-top: 20px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 10px;
        }
        .combo-item {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .combo-actions {
            margin-top: 20px;
        }
        .combo-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .saved-combos {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 10px;
        }
        .combo-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border-radius: 15px;
            margin: 5px;
            cursor: pointer;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<a href="index.php"><img src="img/icon.png"></a>
<h2>Medication Combo Manager</h2>

<?php
// Display notification messages
if (isset($_GET['added'])) {
    echo '<div class="combo-message success-message">Medication added to combo successfully!</div>';
} elseif (isset($_GET['removed'])) {
    echo '<div class="combo-message success-message">Medication removed from combo successfully!</div>';
} elseif (isset($_GET['taken'])) {
    $costMessage = '';
    if (isset($_GET['cost']) && $_GET['cost'] > 0) {
        $cost = floatval($_GET['cost']);
        $costMessage = ' Total cost: $' . number_format($cost, 2) . ' deducted from your balance.';
    }
    echo '<div class="combo-message success-message">All medications taken successfully!' . $costMessage . '</div>';
} elseif (isset($_GET['cleared'])) {
    echo '<div class="combo-message success-message">Combo cleared successfully!</div>';
} elseif (isset($_GET['saved'])) {
    echo '<div class="combo-message success-message">Combo saved successfully!</div>';
} elseif (isset($_GET['loaded'])) {
    echo '<div class="combo-message success-message">Combo loaded successfully!</div>';
} elseif (isset($_GET['deleted'])) {
    echo '<div class="combo-message success-message">Combo deleted successfully!</div>';
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="combo-list">
                <h3>Current Combo</h3>
                <?php
                if (empty($_SESSION['med_combo'])) {
                    echo '<p>No medications added to combo yet. Add medications from the search page or select a saved combo.</p>';
                } else {
                    echo '<ul class="list-group">';
                    foreach ($_SESSION['med_combo'] as $med) {
                        // Get medication details from the database
                        $med_details = $con->prepare("SELECT * FROM medlist WHERE name = ?");
                        $med_details->execute([$med]);
                        $med_info = $med_details->fetch();
                        
                        echo '<li class="combo-item">
                                <div>
                                    <strong>' . htmlspecialchars($med) . '</strong>
                                </div>
                                <div>
                                    <a href="combo.php?remove_med=' . urlencode($med) . '" class="btn btn-sm btn-danger">Remove</a>
                                </div>
                              </li>';
                    }
                    echo '</ul>';
                }
                ?>
                
                <div class="combo-actions">
                    <?php if (!empty($_SESSION['med_combo'])): ?>
                        <a href="combo.php?take_all=1" class="btn btn-success" onclick="return confirm('Take all medications in this combo now?')">Take All Medications</a>
                        <a href="combo.php?clear_combo=1" class="btn btn-warning" onclick="return confirm('Clear all medications from this combo?')">Clear Combo</a>
                        
                        <div class="mt-3">
                            <form action="combo.php" method="post">
                                <div class="input-group">
                                    <input type="text" name="combo_name" class="form-control" placeholder="Enter a name for this combo">
                                    <div class="input-group-append">
                                        <button type="submit" name="save_combo" class="btn btn-primary">Save Combo</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="index.php" class="btn btn-info">Back to Medications</a>
                <a href="index.php?searchKey=&search=Search" class="btn btn-primary">Search Medications</a>
            </div>
            
            <!-- Add medicine from search section -->
            <div class="mt-4">
                <h4>Quick Add from Search</h4>
                <form action="" method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search_term" class="form-control" placeholder="Search for medications to add" value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                
                <?php
                // Display search results if a search term is provided
                if (isset($_GET['search_term']) && !empty($_GET['search_term'])) {
                    $search_term = $_GET['search_term'];
                    $search_query = $con->prepare("SELECT * FROM medlist WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
                    $search_query->execute(['%' . $search_term . '%']);
                    
                    if ($search_query->rowCount() > 0) {
                        echo '<div class="list-group">';
                        while ($result = $search_query->fetch()) {
                            $is_in_combo = in_array($result['name'], $_SESSION['med_combo']);
                            echo '<div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>' . htmlspecialchars($result['name']) . '</span>';
                            
                            if ($is_in_combo) {
                                echo '<span class="badge badge-success">In combo</span>';
                            } else {
                                echo '<a href="combo.php?add_med=' . urlencode($result['name']) . '" class="btn btn-sm btn-success">Add to Combo</a>';
                            }
                            
                            echo '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p>No medications found matching "' . htmlspecialchars($search_term) . '"</p>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="saved-combos">
                <h4>Saved Combos</h4>
                <?php
                $combos = $con->query("SELECT * FROM med_combos ORDER BY created_date DESC");
                if ($combos->rowCount() > 0) {
                    echo '<ul class="list-group">';
                    while ($combo = $combos->fetch()) {
                        // Get medication count
                        $med_count = count(explode(',', $combo['medications']));
                        
                        echo '<li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>' . htmlspecialchars($combo['combo_name']) . '</strong>
                                    <span class="badge badge-info">' . $med_count . ' meds</span>
                                </div>
                                <div class="small text-muted mb-2">' . htmlspecialchars($combo['created_date']) . '</div>
                                <div class="btn-group btn-group-sm">
                                    <a href="combo.php?load_combo=' . urlencode($combo['combo_name']) . '" class="btn btn-info">Load</a>
                                    <a href="combo.php?delete_combo=' . urlencode($combo['combo_name']) . '" class="btn btn-danger" onclick="return confirm(\'Delete this combo?\')">Delete</a>
                                </div>
                              </li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>No saved combos found.</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Safely check for elements before accessing them
    function safelyCheckElement(selector) {
        return $(selector).length > 0;
    }

    // Enable tooltips if Bootstrap JS is loaded
    if(typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Add confirmation dialogs to action buttons
    $('.btn-danger').click(function(e) {
        if (!confirm('Are you sure you want to perform this action?')) {
            e.preventDefault();
        }
    });
    
    // Highlight newly added medications
    if (window.location.search.includes('added=1')) {
        setTimeout(function() {
            $('.combo-message').fadeOut(500);
        }, 3000);
    }
});
</script>
</body>
</html> 