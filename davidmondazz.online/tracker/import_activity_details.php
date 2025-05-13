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
        'd M, Y g:i a',       // 29 May, 2022 12:48 am
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
        // If we cannot parse the date, return null
        return null;
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
            
            $importResults[] = ["info" => "Processing SQL file for timer logs..."];
            
            // Handle multi-row INSERT statements for activity logs
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
                    
                    // Check if this is the right table/has the needed columns
                    // Update required columns based on actual data structure shown in error messages
                    $requiredColumns = ['id', 'activity_name', 'start_date', 'end_date', 'total_time', 'notes', 'activity_id'];
                    
                    // Map the column names to the fields we're looking for
                    $columnMapping = [
                        'timer_id' => 'activity_id',
                        'name' => 'activity_name',
                        'start_time' => 'start_date',
                        'stop_time' => 'end_date',
                        'duration' => 'total_time',
                        'note' => 'notes'
                    ];
                    
                    $hasAllRequired = true;
                    
                    // Don't strictly require all fields, just check what's available
                    $actualColumns = [];
                    foreach ($columnMapping as $targetCol => $sourceCol) {
                        if (in_array($sourceCol, $columnList)) {
                            $actualColumns[$targetCol] = $sourceCol;
                        }
                    }
                    
                    if (empty($actualColumns)) {
                        $importResults[] = ["warning" => "None of the expected columns found in INSERT statement"];
                        $importResults[] = ["warning" => "Skipping this INSERT statement as it doesn't have any required columns"];
                        continue;
                    }
                    
                    // Let user know which columns were found
                    $importResults[] = ["info" => "Found these usable columns: " . implode(', ', array_values($actualColumns))];
                    
                    // Handle multiple value sets: (val1, val2),(val3, val4)
                    $valueSets = preg_split('/\),\s*\(/', $valuesSection);
                    
                    // Clean up first and last item's parentheses
                    $valueSets[0] = preg_replace('/^\s*\(\s*/', '', $valueSets[0]);
                    $lastIndex = count($valueSets) - 1;
                    $valueSets[$lastIndex] = preg_replace('/\s*\)\s*$/', '', $valueSets[$lastIndex]);
                    
                    $importResults[] = ["info" => "Found " . count($valueSets) . " value sets (activity logs) to process"];
                    
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
                            $importResults[] = ["warning" => "Column count (" . count($columnList) . ") does not match value count (" . count($values) . ") - Skipping activity log"];
                            continue;
                        }
                        
                        // Create associative array of column names and values
                        $data = array_combine($columnList, $values);
                        
                        // Get the timer_id by looking up the activity name in timers table
                        $timerId = null;
                        
                        // Extract activity name from data
                        $activityName = null;
                        if (isset($data['activity_name'])) {
                            $activityName = $data['activity_name'];
                        }
                        
                        if (empty($activityName)) {
                            $importResults[] = ["warning" => "Activity name is missing - Skipping activity log"];
                            continue;
                        }
                        
                        // Get timer ID by searching the name in timers table
                        $escapedName = $conn->real_escape_string($activityName);
                        $timerQuery = "SELECT id FROM timers WHERE name = '$escapedName' LIMIT 1";
                        $timerResult = $conn->query($timerQuery);
                        
                        if ($timerResult->num_rows === 0) {
                            $importResults[] = ["warning" => "No timer found with name '$activityName' - Skipping activity log"];
                            continue;
                        }
                        
                        $timerRow = $timerResult->fetch_assoc();
                        $timerId = $timerRow['id'];
                        $importResults[] = ["info" => "Found timer ID: $timerId for activity name: '$activityName'"];
                        
                        // 2. Convert dates
                        $startTime = isset($data['start_date']) ? convertToMySQLDateTime($data['start_date']) : null;
                        $stopTime = isset($data['end_date']) ? convertToMySQLDateTime($data['end_date']) : null;
                        
                        // 3. Get duration
                        $duration = 0;
                        if (isset($data['total_time']) && !empty($data['total_time'])) {
                            $duration = (int)preg_replace('/[^0-9]/', '', $data['total_time']);
                        }
                        
                        // 4. Get note
                        $note = $data['notes'] ?? null;
                        
                        // Prepare data for insertion
                        $noteSQL = $note !== null ? "'" . $conn->real_escape_string($note) . "'" : "NULL";
                        $startTimeSQL = $startTime !== null ? "'" . $conn->real_escape_string($startTime) . "'" : "NULL";
                        $stopTimeSQL = $stopTime !== null ? "'" . $conn->real_escape_string($stopTime) . "'" : "NULL";
                        
                        // Debug output of data
                        $importResults[] = ["info" => "Log data: timer_id=$timerId, start=$startTimeSQL, stop=$stopTimeSQL, duration=$duration, note=" . ($note ?? 'NULL')];
                        
                        // 5. First check if the timer ID exists in the timers table
                        $checkTimerSQL = "SELECT id FROM timers WHERE id = $timerId";
                        $checkResult = $conn->query($checkTimerSQL);
                        
                        if ($checkResult->num_rows === 0) {
                            $importResults[] = ["error" => "Timer ID $timerId does not exist in timers table - Foreign key constraint would fail"];
                            continue;
                        }
                        
                        // 6. Insert into timer_logs
                        $insertSQL = "INSERT INTO timer_logs (timer_id, start_time, stop_time, duration, note, created_at) 
                                    VALUES ($timerId, $startTimeSQL, $stopTimeSQL, $duration, $noteSQL, NOW())";
                        
                        $importResults[] = ["info" => "Executing timer_logs SQL: " . $insertSQL];
                        
                        if ($conn->query($insertSQL)) {
                            $importResults[] = ["success" => "Imported log for activity ID: $timerId"];
                        } else {
                            $importResults[] = ["error" => "Failed to import log for activity ID: $timerId - Error: " . $conn->error];
                        }
                    }
                }
            } else {
                $importResults[] = ["warning" => "No valid multi-row INSERT statements found in SQL file. Looking for individual INSERT statements..."];
                
                // Split content into individual INSERT statements
                $statements = preg_split('/;\s*($|--|#)/m', $sql_content, -1, PREG_SPLIT_NO_EMPTY);
                $importResults[] = ["info" => "Found " . count($statements) . " SQL statements to process"];
                
                // Process individual statements (add processing logic here if needed)
                // This is a fallback in case the file contains individual INSERT statements instead of multi-row
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
    <title>Import Activity Logs - Timer System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Import Activity Logs</h1>
        
        <div class="alert alert-info mb-4">
            <p><strong>Instructions:</strong> Upload a SQL file containing activity logs data with these expected columns:</p>
            <ul>
                <li><strong>id:</strong> The activity log ID</li>
                <li><strong>name:</strong> The activity name (must match an existing timer name)</li>
                <li><strong>start_date:</strong> The start time of the activity</li>
                <li><strong>end_date:</strong> The end time of the activity</li>
                <li><strong>total_time:</strong> Duration in seconds</li>
                <li><strong>notes:</strong> Optional notes for the log entry</li>
            </ul>
            <p>The script will map these to the timer_logs table fields.</p>
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
                <label for="sql_file" class="form-label">Choose SQL File with Activity Logs:</label>
                <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql">
                <div class="form-text">Select a SQL file containing INSERT statements for the activity logs table.</div>
            </div>
            <button type="submit" class="btn btn-primary">Import Activity Logs</button>
            <a href="index.php" class="btn btn-secondary">Back to Timer</a>
        </form>
    </div>
</body>
</html> 