<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Initialize variables
$importResults = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to database
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Process uploaded file
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $sql_content = file_get_contents($_FILES['sql_file']['tmp_name']);
            
            $importResults[] = ["info" => "Processing SQL file for categories..."];
            
            // Handle multi-row INSERT statements for categories
            if (preg_match_all('/INSERT\s+INTO\s+`?[\w.]+`?\s*\((.*?)\)\s*VALUES\s*(.*?);/is', $sql_content, $multiInsertMatches)) {
                $importResults[] = ["info" => "Found " . count($multiInsertMatches[0]) . " INSERT statements"];
                
                for ($i = 0; $i < count($multiInsertMatches[0]); $i++) {
                    $columns = $multiInsertMatches[1][$i];
                    $valuesSection = $multiInsertMatches[2][$i];
                    
                    // Extract column names
                    $columnList = array_map(function($col) {
                        return trim(str_replace(['`', "'"], '', $col));
                    }, explode(',', $columns));
                    
                    $importResults[] = ["info" => "Found columns: " . implode(', ', $columnList)];
                    
                    // Handle multiple value sets: (val1, val2),(val3, val4)
                    $valueSets = preg_split('/\),\s*\(/', $valuesSection);
                    
                    // Clean up first and last item's parentheses
                    $valueSets[0] = preg_replace('/^\s*\(\s*/', '', $valueSets[0]);
                    $lastIndex = count($valueSets) - 1;
                    $valueSets[$lastIndex] = preg_replace('/\s*\)\s*$/', '', $valueSets[$lastIndex]);
                    
                    $importResults[] = ["info" => "Found " . count($valueSets) . " value sets (categories) to process"];
                    
                    foreach ($valueSets as $valueSet) {
                        // Use str_getcsv to properly parse the comma-separated values
                        $values = str_getcsv($valueSet, ',', "'");
                        
                        // Clean up values
                        $values = array_map(function($val) {
                            $val = trim($val);
                            if (strtoupper($val) === 'NULL') return null;
                            return $val;
                        }, $values);
                        
                        // Check for column/value count mismatch
                        if (count($columnList) !== count($values)) {
                            $importResults[] = ["warning" => "Column count (" . count($columnList) . ") does not match value count (" . count($values) . ") - Skipping category"];
                            continue;
                        }
                        
                        // Create associative array of column names and values
                        $data = array_combine($columnList, $values);
                        
                        // Get category name from the data
                        $categoryName = null;
                        if (isset($data['name'])) {
                            $categoryName = $data['name'];
                        } else {
                            // Try to find any column that might contain the name
                            foreach ($data as $key => $value) {
                                if (strpos(strtolower($key), 'name') !== false) {
                                    $categoryName = $value;
                                    break;
                                }
                            }
                        }
                        
                        if (empty($categoryName)) {
                            $importResults[] = ["warning" => "Category name is missing - Skipping category"];
                            continue;
                        }
                        
                        // Check if the category already exists to avoid duplicates
                        $escapedName = $conn->real_escape_string($categoryName);
                        $checkQuery = "SELECT id FROM categories WHERE name = '$escapedName' LIMIT 1";
                        $checkResult = $conn->query($checkQuery);
                        
                        if ($checkResult->num_rows > 0) {
                            $importResults[] = ["info" => "Category '$categoryName' already exists - Skipping"];
                            continue;
                        }
                        
                        // Insert the category with name = name and created_at = NOW()
                        $insertSQL = "INSERT INTO categories (name, created_at) VALUES ('$escapedName', NOW())";
                        
                        $importResults[] = ["info" => "Executing SQL: " . $insertSQL];
                        
                        if ($conn->query($insertSQL)) {
                            $importResults[] = ["success" => "Imported category: '$categoryName'"];
                        } else {
                            $importResults[] = ["error" => "Failed to import category: '$categoryName' - Error: " . $conn->error];
                        }
                    }
                }
            } else {
                // Split content into individual INSERT statements
                $statements = preg_split('/;\s*($|--|#)/m', $sql_content, -1, PREG_SPLIT_NO_EMPTY);
                $importResults[] = ["info" => "Found " . count($statements) . " SQL statements to process"];
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (empty($statement)) continue;
                    
                    // Ignore comments or non-INSERT lines
                    if (!preg_match('/^INSERT/i', $statement)) {
                        $importResults[] = ["info" => "Skipping non-INSERT line: " . substr($statement, 0, 50) . "..."];
                        continue;
                    }
                    
                    // Parse each INSERT statement
                    if (preg_match("/INSERT\s+INTO\s+`?([\w.]+)`?\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is", $statement, $matches)) {
                        $tableName = $matches[1];
                        
                        // Clean up the columns
                        $columnList = array_map(function($col) {
                            return trim(str_replace(['`', "'"], '', $col));
                        }, explode(',', $matches[2]));
                        
                        // Use str_getcsv to parse the values string
                        $valuesStr = $matches[3];
                        $values = str_getcsv($valuesStr, ',', "'");
                        
                        // Clean up values
                        $values = array_map(function($val) {
                            $val = trim($val);
                            if (strtoupper($val) === 'NULL') return null;
                            return $val;
                        }, $values);
                        
                        // Check for column/value count mismatch
                        if (count($columnList) !== count($values)) {
                            $importResults[] = ["warning" => "Column count (" . count($columnList) . ") does not match value count (" . count($values) . ") - Skipping category"];
                            continue;
                        }
                        
                        // Create associative array of column names and values
                        $data = array_combine($columnList, $values);
                        
                        // Get category name from the data
                        $categoryName = null;
                        if (isset($data['name'])) {
                            $categoryName = $data['name'];
                        } else {
                            // Try to find any column that might contain the name
                            foreach ($data as $key => $value) {
                                if (strpos(strtolower($key), 'name') !== false) {
                                    $categoryName = $value;
                                    break;
                                }
                            }
                        }
                        
                        if (empty($categoryName)) {
                            $importResults[] = ["warning" => "Category name is missing - Skipping category"];
                            continue;
                        }
                        
                        // Check if the category already exists to avoid duplicates
                        $escapedName = $conn->real_escape_string($categoryName);
                        $checkQuery = "SELECT id FROM categories WHERE name = '$escapedName' LIMIT 1";
                        $checkResult = $conn->query($checkQuery);
                        
                        if ($checkResult->num_rows > 0) {
                            $importResults[] = ["info" => "Category '$categoryName' already exists - Skipping"];
                            continue;
                        }
                        
                        // Insert the category with name = name and created_at = NOW()
                        $insertSQL = "INSERT INTO categories (name, created_at) VALUES ('$escapedName', NOW())";
                        
                        $importResults[] = ["info" => "Executing SQL: " . $insertSQL];
                        
                        if ($conn->query($insertSQL)) {
                            $importResults[] = ["success" => "Imported category: '$categoryName'"];
                        } else {
                            $importResults[] = ["error" => "Failed to import category: '$categoryName' - Error: " . $conn->error];
                        }
                    }
                }
            }
        } else if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error code: " . $_FILES['sql_file']['error']);
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $importResults[] = ["error" => "Error: " . $e->getMessage() . " at line " . $e->getLine()];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Categories - Timer System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Import Categories</h1>
        
        <div class="alert alert-info mb-4">
            <p><strong>Instructions:</strong> Upload a SQL file containing category data with these expected columns:</p>
            <ul>
                <li><strong>name:</strong> The category name</li>
            </ul>
            <p>The script will map the name column directly to categories.name and set created_at to the current time.</p>
        </div>
        
        <?php if (!empty($importResults)): ?>
            <div class="mb-4">
                <h3>Import Results:</h3>
                <?php foreach ($importResults as $result): ?>
                    <?php 
                        $type = key($result);
                        $message = htmlspecialchars($result[$type]);
                        $alertClass = 'alert-secondary';
                        if ($type === 'success') $alertClass = 'alert-success';
                        elseif ($type === 'error') $alertClass = 'alert-danger';
                        elseif ($type === 'info') $alertClass = 'alert-info';
                        elseif ($type === 'warning') $alertClass = 'alert-warning';
                    ?>
                    <div class="alert <?php echo $alertClass; ?>"><?php echo $message; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="mb-4" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="sql_file" class="form-label">Choose SQL File with Categories:</label>
                <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql">
                <div class="form-text">Select a SQL file containing INSERT statements for categories.</div>
            </div>
            <button type="submit" class="btn btn-primary">Import Categories</button>
            <a href="index.php" class="btn btn-secondary">Back to Timer</a>
        </form>
    </div>
</body>
</html> 