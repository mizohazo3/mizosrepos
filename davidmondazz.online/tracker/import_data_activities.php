<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once 'config.php';

// Initialize variables
$importResults = [];

// Function to convert various date formats to MySQL datetime
function convertToMySQLDateTime($dateStr) {
    if (empty($dateStr) || $dateStr === 'NULL') return null;
    
    // Clean the input string
    $dateStr = trim($dateStr);
    
    $formats = [
        'd M, Y g:i a',       // 29 May, 2022 12:48 am (Added g for 12-hour format)
        'd M, Y h:i a',       // 29 May, 2022 12:48 am 
        'd M, Y H:i a',       // 29 May, 2022 12:48 am
        'd M, Y h:i:s a',     // 29 May, 2022 12:48:00 am
        'd M, Y H:i:s a',     // 29 May, 2022 12:48:00 am
        'd M Y g:i a',        // 29 May 2022 12:48 am
        'd M Y h:i a',        // 29 May 2022 12:48 am
        'd M Y H:i a',        // 29 May 2022 12:48 am
        'Y-m-d H:i:s',        // MySQL format
        'M d, Y h:i:s A'      // Common log format (e.g., Jul 26, 2023 11:26:11 PM)
    ];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date) {
            return $date->format('Y-m-d H:i:s');
        }
    }
    
    // Try PHP's default date parsing as last resort
    try {
        $date = new DateTime($dateStr);
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // If we cannot parse the date, use the current date/time
        $importResults[] = ["warning" => "Could not parse date: '$dateStr', using current date instead."];
        return date('Y-m-d H:i:s');
    }
}

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
            
            $importResults[] = ["info" => "Processing SQL file..."];
            
            // Handle multi-row INSERT statements
            // Look for INSERT statements with multiple value sets like: INSERT INTO `table` (...) VALUES (...),(...),(...);
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
                    // Split on '),(' pattern but preserve the parentheses
                    $valueSets = preg_split('/\),\s*\(/', $valuesSection);
                    
                    // Clean up first and last item's parentheses
                    $valueSets[0] = preg_replace('/^\s*\(\s*/', '', $valueSets[0]);
                    $lastIndex = count($valueSets) - 1;
                    $valueSets[$lastIndex] = preg_replace('/\s*\)\s*$/', '', $valueSets[$lastIndex]);
                    
                    $importResults[] = ["info" => "Found " . count($valueSets) . " value sets in INSERT statement"];
                    
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
                            $importResults[] = ["warning" => "Column count (" . count($columnList) . ") does not match value count (" . count($values) . ") - Skipping: " . substr($valueSet, 0, 100) . "..."];
                            continue;
                        }
                        
                        // Create associative array of column names and values
                        $data = array_combine($columnList, $values);
                        $importResults[] = ["info" => "Processing row: " . json_encode($data)];
                        
                        // Get category name
                        $categoryName = $data['cat_name'] ?? '';
                        if (empty($categoryName)) {
                            $importResults[] = ["error" => "Category name missing - Skipping this row"];
                            continue;
                        }
                        
                        // Check if category exists or create it
                        $categoryId = null;
                        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                        $stmt->bind_param("s", $categoryName);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $categoryId = $row['id'];
                            $importResults[] = ["info" => "Using existing category: '" . $categoryName . "' (ID: $categoryId)"];
                        } else {
                            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                            $stmt->bind_param("s", $categoryName);
                            if (!$stmt->execute()) {
                                $importResults[] = ["error" => "Failed to create category: '" . $categoryName . "' - " . $stmt->error];
                                $stmt->close();
                                continue;
                            } else {
                                $categoryId = $conn->insert_id;
                                $importResults[] = ["success" => "Created new category: '" . $categoryName . "' (ID: $categoryId)"];
                            }
                        }
                        $stmt->close();
                        
                        // Convert the date
                        $addedDateValue = $data['added_date'] ?? null;
                        $createdAt = convertToMySQLDateTime($addedDateValue);
                        
                        // Convert time_spent to integer (seconds)
                        $timeSpent = 0;
                        if (isset($data['time_spent']) && !empty($data['time_spent'])) {
                            $timeSpent = (int)preg_replace('/[^0-9]/', '', $data['time_spent']);
                        }
                        
                        // Prepare other data for insertion
                        $name = $data['name'] ?? 'Untitled Timer';
                        $status = strtolower($data['status'] ?? 'idle');
                        if (!in_array($status, ['idle', 'running', 'paused'])) {
                            $status = 'idle'; // Default to idle if invalid
                        }
                        $manageStatus = $data['manage_status'] ?? null;
                        $links = $data['links'] ?? null;
                        
                        // Use direct SQL
                        $name = $conn->real_escape_string($name);
                        $status = $conn->real_escape_string($status);
                        $manageStatus = $manageStatus !== null ? "'" . $conn->real_escape_string($manageStatus) . "'" : "NULL";
                        $links = $links !== null ? "'" . $conn->real_escape_string($links) . "'" : "NULL";
                        
                        // If we couldn't parse the date, use the current timestamp
                        if ($createdAt === null) {
                            $createdAt = date('Y-m-d H:i:s');
                            $importResults[] = ["warning" => "Using current timestamp for '$name' as couldn't parse date: " . ($data['added_date'] ?? 'NULL')];
                        }
                        
                        $createdAtSql = "'" . $conn->real_escape_string($createdAt) . "'";
                        
                        $sql = "INSERT INTO timers 
                                (name, category_id, status, start_time, pause_time, total_time, manage_status, links, created_at) 
                                VALUES 
                                ('$name', $categoryId, '$status', NULL, 0, $timeSpent, $manageStatus, $links, $createdAtSql)";
                        
                        $importResults[] = ["info" => "Executing SQL: " . $sql];
                        
                        if ($conn->query($sql)) {
                            $importResults[] = ["success" => "Imported activity: '" . $name . "' into timers table."];
                        } else {
                            $importResults[] = ["error" => "Failed to import activity: '" . $name . "' - Error: " . $conn->error];
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
                        
                        // Clean up values (handle NULL and trim whitespace)
                        $values = array_map(function($val) {
                            $val = trim($val);
                            if (strtoupper($val) === 'NULL') return null;
                            return $val;
                        }, $values);
                        
                        $importResults[] = ["info" => "Found columns: " . implode(', ', $columnList)];
                        $importResults[] = ["info" => "Parsed values: " . implode(', ', array_map(function($v) { return $v === null ? 'NULL' : "'" . $v . "'"; }, $values))];
                        
                        // Check for column/value count mismatch
                        if (count($columnList) !== count($values)) {
                            $importResults[] = ["error" => "Column count (" . count($columnList) . ") does not match value count (" . count($values) . ") for statement in table `$tableName`: " . substr($statement, 0, 100) . "..."];
                            continue;
                        }
                        
                        // Create associative array of column names and values
                        $data = array_combine($columnList, $values);
                        
                        // --- Data Mapping and Insertion Logic ---
                        // Get category name
                        $categoryName = $data['cat_name'] ?? '';
                        if (empty($categoryName)) {
                            $importResults[] = ["error" => "Category name (cat_name) is missing or empty in data: " . json_encode($data)];
                            continue;
                        }
                        
                        // Check if category exists or create it
                        $categoryId = null;
                        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                        $stmt->bind_param("s", $categoryName);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $categoryId = $row['id'];
                            $importResults[] = ["info" => "Using existing category: '" . $categoryName . "' (ID: $categoryId)"];
                        } else {
                            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                            $stmt->bind_param("s", $categoryName);
                            if (!$stmt->execute()) {
                                 $importResults[] = ["error" => "Failed to create category: '" . $categoryName . "' - " . $stmt->error];
                                 continue;
                            } else {
                                $categoryId = $conn->insert_id;
                                $importResults[] = ["success" => "Created new category: '" . $categoryName . "' (ID: $categoryId)"];
                            }
                        }
                        $stmt->close(); // Close statement after use

                        // Convert the date
                        $addedDateValue = $data['added_date'] ?? null;
                        $createdAt = convertToMySQLDateTime($addedDateValue);
                        
                        // Stricter check for valid date conversion
                        if ($addedDateValue !== null && ($createdAt === null || !is_string($createdAt))) {
                            $importResults[] = ["error" => "Invalid or unparseable date format for 'added_date': " . ($addedDateValue ?? 'NULL')];
                            continue;
                        }
                        
                        // Convert time_spent to integer (seconds)
                        $timeSpent = 0;
                        if (isset($data['time_spent']) && !empty($data['time_spent'])) {
                            // Simple conversion assuming value is already in seconds
                            $timeSpent = (int)preg_replace('/[^0-9]/', '', $data['time_spent']);
                        }
                        
                        // Prepare other data for insertion
                        $name = $data['name'] ?? 'Untitled Timer';
                        $status = strtolower($data['status'] ?? 'idle');
                        if (!in_array($status, ['idle', 'running', 'paused'])) {
                            $status = 'idle'; // Default to idle if invalid
                        }
                        $manageStatus = $data['manage_status'] ?? null;
                        $links = $data['links'] ?? null;
                        
                        // Debug: Show data before insertion
                        $debugData = ['Name' => $name, 'CatID' => $categoryId, 'Status' => $status, 'TimeSpent' => $timeSpent, 'Manage' => $manageStatus, 'Links' => $links, 'CreatedAt' => $createdAt];
                        $importResults[] = ["info" => "Preparing to insert: " . json_encode($debugData)];

                        // Add final debug before bind
                        $importResults[] = ["info" => "Binding values: Name='$name', CatID=$categoryId, Status='$status', TimeSpent=$timeSpent, Manage='$manageStatus', Links='$links', CreatedAt='$createdAt'"];
                        
                        // Rather than using prepared statements for the timestamp, let's use direct SQL
                        // to avoid any parameter binding issues with the datetime format
                        $name = $conn->real_escape_string($name);
                        $status = $conn->real_escape_string($status);
                        $manageStatus = $manageStatus !== null ? "'" . $conn->real_escape_string($manageStatus) . "'" : "NULL";
                        $links = $links !== null ? "'" . $conn->real_escape_string($links) . "'" : "NULL";
                        
                        // If we couldn't parse the date, use the current timestamp
                        if ($createdAt === null) {
                            $createdAt = date('Y-m-d H:i:s');
                            $importResults[] = ["warning" => "Using current timestamp for '$name' as couldn't parse date: " . ($data['added_date'] ?? 'NULL')];
                        }
                        
                        $createdAtSql = "'" . $conn->real_escape_string($createdAt) . "'";
                        
                        $sql = "INSERT INTO timers 
                                (name, category_id, status, start_time, pause_time, total_time, manage_status, links, created_at) 
                                VALUES 
                                ('$name', $categoryId, '$status', NULL, 0, $timeSpent, $manageStatus, $links, $createdAtSql)";
                        
                        $importResults[] = ["info" => "Executing SQL: " . $sql];
                        
                        if ($conn->query($sql)) {
                            $importResults[] = ["success" => "Imported activity: '" . $name . "' into timers table."];
                        } else {
                            $importResults[] = ["error" => "Failed to import activity: '" . $name . "' - Error: " . $conn->error];
                        }
                        
                        // Comment out old prepared statement code
                        /*
                        // Correct bind_param types: s(name), i(category_id), s(status), i(total_time), s(manage_status), s(links), s(created_at)
                        $bindSuccess = $stmt->bind_param("sisissi", 
                            $name,
                            $categoryId,
                            $status,
                            $timeSpent,
                            $manageStatus,
                            $links,
                            $createdAt
                        );

                        if ($bindSuccess === false) {
                            $importResults[] = ["error" => "Failed to bind parameters: " . $stmt->error];
                            $stmt->close();
                            continue;
                        }
                        
                        if ($stmt->execute()) {
                            $importResults[] = ["success" => "Imported activity: '" . $name . "' into timers table."];
                        } else {
                            $importResults[] = ["error" => "Failed to import activity: '" . $name . "' - Error: " . $stmt->error];
                        }
                        $stmt->close(); // Close statement after execution
                        */

                    } else {
                         $importResults[] = ["error" => "Could not parse INSERT statement structure: " . substr($statement, 0, 100) . "..."];
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
    <title>Import Activities - Timer System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Import Activities</h1>
        
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
                    ?>
                    <div class="alert <?php echo $alertClass; ?>"><?php echo $message; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="mb-4" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="sql_file" class="form-label">Choose SQL File:</label>
                <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql">
                <div class="form-text">Select a SQL file containing INSERT statements for the 'activity' table.</div>
            </div>
            <button type="submit" class="btn btn-primary">Import Activities</button>
            <a href="index.php" class="btn btn-secondary">Back to Timer</a>
        </form>
    </div>
</body>
</html> 