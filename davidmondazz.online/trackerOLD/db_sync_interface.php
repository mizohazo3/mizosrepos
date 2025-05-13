<?php
// Set default timezone
date_default_timezone_set("Africa/Cairo");

// Initialize session for storing form data between submissions
session_start();

// Define default mapping pairs if needed
$default_mappings = [
    ['source' => 'accumulated_seconds', 'target' => 'time_spent'],
    ['source' => 'start_time', 'target' => 'last_started'],
    ['source' => 'name', 'target' => 'name'],
    ['source' => 'categories', 'target' => 'cat_name'],
    ['source' => 'is_running', 'target' => 'status', 'transform' => 'status_transform']
];

// Helper functions for transformations
function status_transform($value) {
    return ($value > 0) ? 'on' : 'off';
}

function rand_color() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

// Initialize variables
$source_db_conn = null;
$target_db_conn = null;
$source_tables = [];
$target_tables = [];
$source_columns = [];
$error_message = '';
$success_message = '';
$mappings = $_SESSION['mappings'] ?? $default_mappings;
$key_mapping_index = $_SESSION['key_mapping_index'] ?? null; // Store selected key mapping index

// Process form submission for database connections
if (isset($_POST['connect_db'])) {
    // Store connection details in session
    $_SESSION['source_host'] = $_POST['source_host'];
    $_SESSION['source_user'] = $_POST['source_user'];
    $_SESSION['source_pass'] = $_POST['source_pass'];
    $_SESSION['source_dbname'] = $_POST['source_dbname'];
    
    $_SESSION['target_host'] = $_POST['target_host'];
    $_SESSION['target_user'] = $_POST['target_user'];
    $_SESSION['target_pass'] = $_POST['target_pass'];
    $_SESSION['target_dbname'] = $_POST['target_dbname'];
    
    // Connect to source database
    try {
        $source_db_conn = new PDO("mysql:host={$_POST['source_host']};dbname={$_POST['source_dbname']}", 
                              $_POST['source_user'], 
                              $_POST['source_pass']);
        $source_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get list of tables
        $stmt = $source_db_conn->query("SHOW TABLES");
        $source_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch(PDOException $e) {
        $error_message = "Source database connection failed: " . $e->getMessage();
    }
    
    // Connect to target database
    try {
        $target_db_conn = new PDO("mysql:host={$_POST['target_host']};dbname={$_POST['target_dbname']}", 
                              $_POST['target_user'], 
                              $_POST['target_pass']);
        $target_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get list of tables
        $stmt = $target_db_conn->query("SHOW TABLES");
        $target_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch(PDOException $e) {
        $error_message = "Target database connection failed: " . $e->getMessage();
    }
}

// Process table selection
if (isset($_POST['select_tables'])) {
    $_SESSION['source_table'] = $_POST['source_table'];
    $_SESSION['target_table'] = $_POST['target_table'];
    
    // Connect to databases again
    try {
        $source_db_conn = new PDO("mysql:host={$_SESSION['source_host']};dbname={$_SESSION['source_dbname']}", 
                              $_SESSION['source_user'], 
                              $_SESSION['source_pass']);
        $source_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get source columns
        $stmt = $source_db_conn->query("DESCRIBE {$_POST['source_table']}");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $source_columns[] = $row['Field'];
        }
        
        // Get source tables for dropdown
        $stmt = $source_db_conn->query("SHOW TABLES");
        $source_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch(PDOException $e) {
        $error_message = "Failed to get source table structure: " . $e->getMessage();
    }
    
    try {
        $target_db_conn = new PDO("mysql:host={$_SESSION['target_host']};dbname={$_SESSION['target_dbname']}", 
                              $_SESSION['target_user'], 
                              $_SESSION['target_pass']);
        $target_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get target columns
        $stmt = $target_db_conn->query("DESCRIBE {$_POST['target_table']}");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $target_columns[] = $row['Field'];
        }
        
        // Get target tables for dropdown
        $stmt = $target_db_conn->query("SHOW TABLES");
        $target_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } catch(PDOException $e) {
        $error_message = "Failed to get target table structure: " . $e->getMessage();
    }
}

// Process mapping updates
if (isset($_POST['update_mappings'])) {
    // Check if a key mapping was selected
    if (!isset($_POST['key_mapping_index']) || $_POST['key_mapping_index'] === '') {
        $error_message = "Error: Please select one mapping row to use as the unique key for matching records.";
    } else {
        $selected_key_index = intval($_POST['key_mapping_index']);
        $mappings = [];
        $valid_key_index_found = false;

        for ($i = 0; $i < count($_POST['source_column']); $i++) {
            if (!empty($_POST['source_column'][$i]) && !empty($_POST['target_column'][$i])) {
                $mapping = [
                    'source' => $_POST['source_column'][$i],
                    'target' => $_POST['target_column'][$i]
                ];

                if (!empty($_POST['transform'][$i])) {
                    $mapping['transform'] = $_POST['transform'][$i];
                }

                $mappings[] = $mapping;

                // Check if the current index matches the selected key index
                if ($i === $selected_key_index) {
                    $valid_key_index_found = true;
                }
            }
        }

        // Ensure the selected index corresponds to a valid mapping
        if (!$valid_key_index_found && !empty($mappings)) {
             $error_message = "Error: The selected key mapping is invalid or does not correspond to a complete mapping row.";
             // Reset key index if invalid
             $_SESSION['key_mapping_index'] = null;
             $key_mapping_index = null;
        } else if (empty($mappings)) {
            $_SESSION['key_mapping_index'] = null; // Clear key if no mappings exist
            $key_mapping_index = null;
            $_SESSION['mappings'] = [];
            $success_message = "Mappings cleared.";
        } else {
            $_SESSION['mappings'] = $mappings;
            $_SESSION['key_mapping_index'] = $selected_key_index; // Store the valid index
            $key_mapping_index = $selected_key_index; // Update local variable
            $success_message = "Mapping configuration updated.";
        }
    }
}

// Process sync operation
if (isset($_POST['run_sync'])) {
    // Ensure key mapping index is set and valid before syncing
    if (!isset($_SESSION['key_mapping_index']) || !isset($mappings[$_SESSION['key_mapping_index']])) {
        $error_message = "Sync failed: Key mapping is not configured or is invalid. Please go to Step 3 and select a key mapping.";
    } else {
        $key_mapping_index = $_SESSION['key_mapping_index'];
        $key_mapping = $mappings[$key_mapping_index];
        $source_key_col = $key_mapping['source'];
        $target_key_col = $key_mapping['target'];

        try {
            // Connect to source database
            $source_db_conn = new PDO("mysql:host={$_SESSION['source_host']};dbname={$_SESSION['source_dbname']}",
                            $_SESSION['source_user'], 
                            $_SESSION['source_pass']);
        $source_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Connect to target database
        $target_db_conn = new PDO("mysql:host={$_SESSION['target_host']};dbname={$_SESSION['target_dbname']}", 
                            $_SESSION['target_user'], 
                            $_SESSION['target_pass']);
        $target_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get source data - ensure backticks around table name
        $stmt = $source_db_conn->query("SELECT * FROM `{$_SESSION['source_table']}`");
        $source_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process data
        $updated = 0;
        $inserted = 0;
        $errors = 0;
        
        foreach ($source_data as $row) {
            // Build data array based on mappings
            $data = [];
            $placeholders = [];
            $update_values = [];
            
            foreach ($mappings as $mapping) {
                $source_col = $mapping['source'];
                $target_col = $mapping['target'];
                
                // Skip if source column doesn't exist in current row
                if (!isset($row[$source_col])) continue;
                
                // Apply transformation if specified
                $value = $row[$source_col];
                if (isset($mapping['transform']) && function_exists($mapping['transform'])) {
                    $transform_func = $mapping['transform'];
                    $value = $transform_func($value);
                }
                
                $data[$target_col] = $value;
                $placeholders[] = "?";
                $update_values[] = "`$target_col` = ?";
            }
            
            // Get the key value from the source row using the designated source key column
            if (!isset($row[$source_key_col])) {
                $errors++;
                $error_message .= "Error: Source key column '{$source_key_col}' not found in source data row. Skipping record.<br>";
                continue; // Skip this record
            }
            $key_field_value = $row[$source_key_col];

            // Ensure the target key column is included in the data for inserts
            // If it wasn't explicitly mapped, add it now using the source key value
            if (!isset($data[$target_key_col])) {
                $data[$target_key_col] = $key_field_value;
                // Recalculate placeholders if key was added
                $placeholders = array_fill(0, count($data), '?');
            }

            try {
                // Check if record exists using the designated target key column
                // Ensure backticks around table and column names
                $check_sql = "SELECT 1 FROM `{$_SESSION['target_table']}` WHERE `{$target_key_col}` = ?";
                $check_stmt = $target_db_conn->prepare($check_sql);
                $check_stmt->execute([$key_field_value]);
                $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($exists) {
                    // Update - Ensure backticks
                    // Exclude the key column itself from the SET clause if it exists there
                    $update_cols_temp = [];
                    foreach ($data as $col => $val) {
                        if ($col !== $target_key_col) { // Don't try to update the key column itself
                            $update_cols_temp[] = "`$col` = ?";
                        }
                    }

                    if (!empty($update_cols_temp)) { // Only update if there are other columns to update
                        $update_sql = "UPDATE `{$_SESSION['target_table']}` SET " . implode(", ", $update_cols_temp) . " WHERE `{$target_key_col}` = ?";
                        $update_stmt = $target_db_conn->prepare($update_sql);

                        // Prepare values, excluding the key column value if it was in $data
                        $values = [];
                        foreach ($data as $col => $val) {
                            if ($col !== $target_key_col) {
                                $values[] = $val;
                            }
                        }
                        $values[] = $key_field_value; // Add key value for WHERE clause

                        $update_stmt->execute($values);
                        $updated++;
                    } else {
                        // Optionally log that only key column was mapped and no update occurred
                    }

                } else {
                    // Insert - Ensure backticks
                    $cols = array_keys($data);
                    $insert_sql = "INSERT INTO `{$_SESSION['target_table']}` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $placeholders) . ")";
                    $insert_stmt = $target_db_conn->prepare($insert_sql);

                    $insert_stmt->execute(array_values($data));
                    $inserted++;
                }

            } catch(PDOException $e) {
                $errors++;
                $error_message .= "Error processing record with {$target_key_col}={$key_field_value}: " . $e->getMessage() . "<br>";
            }
        }

        $success_message = "Sync completed: $updated records updated, $inserted records inserted, $errors errors.";

    } catch(PDOException $e) {
        $error_message = "Sync failed: " . $e->getMessage();
    }
  } // End of check for key mapping index validity
} // End of run_sync block

// If not connected yet, try to connect using session data
if (!$source_db_conn && isset($_SESSION['source_host'])) {
    try {
        $source_db_conn = new PDO("mysql:host={$_SESSION['source_host']};dbname={$_SESSION['source_dbname']}", 
                              $_SESSION['source_user'], 
                              $_SESSION['source_pass']);
        $source_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get list of tables
        $stmt = $source_db_conn->query("SHOW TABLES");
        $source_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If we have a selected table, get its columns
        if (isset($_SESSION['source_table'])) {
            $stmt = $source_db_conn->query("DESCRIBE {$_SESSION['source_table']}");
            $source_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $source_columns[] = $row['Field'];
            }
        }
        
    } catch(PDOException $e) {
        // Silent failure - we'll show connection form
    }
}

if (!$target_db_conn && isset($_SESSION['target_host'])) {
    try {
        $target_db_conn = new PDO("mysql:host={$_SESSION['target_host']};dbname={$_SESSION['target_dbname']}", 
                              $_SESSION['target_user'], 
                              $_SESSION['target_pass']);
        $target_db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get list of tables
        $stmt = $target_db_conn->query("SHOW TABLES");
        $target_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If we have a selected table, get its columns
        if (isset($_SESSION['target_table'])) {
            $stmt = $target_db_conn->query("DESCRIBE {$_SESSION['target_table']}");
            $target_columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $target_columns[] = $row['Field'];
            }
        }
        
    } catch(PDOException $e) {
        // Silent failure - we'll show connection form
    }
}

// Available transformations for dropdown
$transformations = [
    '' => 'None',
    'status_transform' => 'Status Transform (is_running to on/off)',
    'intval' => 'Convert to Integer',
    'strval' => 'Convert to String'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Sync Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #333;
        }
        .card {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button, input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover, input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: #f44336;
            margin-bottom: 15px;
        }
        .success {
            color: #4CAF50;
            margin-bottom: 15px;
        }
        .row {
            display: flex;
            margin-bottom: 10px;
        }
        .col {
            flex: 1;
            padding: 0 10px;
        }
        .mapping-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
        }
        .mapping-col {
            flex: 1;
            padding: 0 5px;
        }
        .remove-btn {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .add-btn {
            background-color: #2196F3;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .step {
            background-color: #e7f3ff;
            border-left: 5px solid #2196F3;
            padding: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Sync Tool</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="step">
            <h2>Step 1: Connect to Databases</h2>
            
            <form method="post" action="">
                <div class="row">
                    <div class="col">
                        <h3>Source Database</h3>
                        <div class="form-group">
                            <label>Host:</label>
                            <input type="text" name="source_host" value="<?php echo $_SESSION['source_host'] ?? 'localhost'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="source_user" value="<?php echo $_SESSION['source_user'] ?? 'mcgkxyz_masterpop'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="source_pass" value="<?php echo $_SESSION['source_pass'] ?? 'aA0109587045'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name:</label>
                            <input type="text" name="source_dbname" value="<?php echo $_SESSION['source_dbname'] ?? 'mcgkxyz_timer_app'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="col">
                        <h3>Target Database</h3>
                        <div class="form-group">
                            <label>Host:</label>
                            <input type="text" name="target_host" value="<?php echo $_SESSION['target_host'] ?? 'localhost'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username:</label>
                            <input type="text" name="target_user" value="<?php echo $_SESSION['target_user'] ?? 'mcgkxyz_masterpop'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="target_pass" value="<?php echo $_SESSION['target_pass'] ?? 'aA0109587045'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name:</label>
                            <input type="text" name="target_dbname" value="<?php echo $_SESSION['target_dbname'] ?? 'mcgkxyz_tracker'; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <input type="submit" name="connect_db" value="Connect to Databases">
                </div>
            </form>
        </div>
        
        <?php if ($source_db_conn && $target_db_conn): ?>
        <div class="step">
            <h2>Step 2: Select Tables</h2>
            
            <form method="post" action="">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Source Table:</label>
                            <select name="source_table" required>
                                <option value="">-- Select Source Table --</option>
                                <?php foreach ($source_tables as $table): ?>
                                <option value="<?php echo $table; ?>" <?php echo (isset($_SESSION['source_table']) && $_SESSION['source_table'] == $table) ? 'selected' : ''; ?>>
                                    <?php echo $table; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="form-group">
                            <label>Target Table:</label>
                            <select name="target_table" required>
                                <option value="">-- Select Target Table --</option>
                                <?php foreach ($target_tables as $table): ?>
                                <option value="<?php echo $table; ?>" <?php echo (isset($_SESSION['target_table']) && $_SESSION['target_table'] == $table) ? 'selected' : ''; ?>>
                                    <?php echo $table; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <input type="submit" name="select_tables" value="Select Tables">
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($source_columns) && isset($_SESSION['source_table']) && isset($_SESSION['target_table'])): ?>
        <div class="step">
            <h2>Step 3: Configure Column Mappings</h2>
            
            <form method="post" action="" id="mappingForm">
                <div id="mappings-container">
                    <?php foreach ($mappings as $i => $mapping): ?>
                    <div class="mapping-row">
                        <div class="mapping-col" style="flex: 0 0 40px; text-align: center;">
                            <input type="radio" name="key_mapping_index" value="<?php echo $i; ?>" title="Select as Key Mapping" required <?php echo (isset($key_mapping_index) && $key_mapping_index === $i) ? 'checked' : ''; ?>>
                        </div>
                        <div class="mapping-col">
                            <label style="display:block; font-size: 0.8em; margin-bottom: 2px;">Source Column</label>
                            <select name="source_column[]" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($source_columns as $column): ?>
                                <option value="<?php echo $column; ?>" <?php echo ($mapping['source'] == $column) ? 'selected' : ''; ?>>
                                    <?php echo $column; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mapping-col" style="text-align: center;">
                            <span>➡️</span>
                        </div>
                        
                        <div class="mapping-col">
                            <label style="display:block; font-size: 0.8em; margin-bottom: 2px;">Target Column</label>
                            <select name="target_column[]" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($target_columns as $column): ?>
                                <option value="<?php echo $column; ?>" <?php echo ($mapping['target'] == $column) ? 'selected' : ''; ?>>
                                    <?php echo $column; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mapping-col">
                            <label style="display:block; font-size: 0.8em; margin-bottom: 2px;">Transformation</label>
                            <select name="transform[]">
                                <?php foreach ($transformations as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($mapping['transform']) && $mapping['transform'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mapping-col" style="flex: 0 0 50px;">
                            <button type="button" class="remove-btn" onclick="removeMapping(this)">X</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="add-btn" onclick="addMapping()">+ Add Mapping</button>
                
                <div class="form-group">
                    <input type="submit" name="update_mappings" value="Update Mappings">
                </div>
            </form>
        </div>
        
        <div class="step">
            <h2>Step 4: Run Sync Operation</h2>
            
            <form method="post" action="">
                <div class="form-group">
                    <input type="submit" name="run_sync" value="Run Sync Now">
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function addMapping() {
            const container = document.getElementById('mappings-container');
            const mappingRows = container.getElementsByClassName('mapping-row');
            const newRow = mappingRows[0].cloneNode(true);
            
            // Clear selections in the cloned row
            const selects = newRow.querySelectorAll('select');
            selects.forEach(select => select.selectedIndex = 0);

            // Clear radio button selection in the cloned row and update its value
            const radio = newRow.querySelector('input[type="radio"]');
            radio.checked = false;
            // Assign a temporary unique value or handle index assignment server-side upon submission
            // For simplicity, we'll let the server handle index assignment based on order on submit.
            // radio.value = Date.now(); // Or some other temporary unique ID if needed client-side

            // Ensure the remove button works for the new row
            const removeBtn = newRow.querySelector('.remove-btn');
            removeBtn.onclick = function() { removeMapping(this); }; // Re-attach listener

            container.appendChild(newRow);
        }
        
        function removeMapping(button) {
            const container = document.getElementById('mappings-container');
            const mappingRows = container.getElementsByClassName('mapping-row');
            
            if (mappingRows.length > 1) {
                const row = button.closest('.mapping-row');
                container.removeChild(row);
            } else {
                alert('You must have at least one mapping.');
            }
        }
    </script>
</body>
</html> 