<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Report all errors except notices for cleaner output, but log notices.
// error_reporting(E_ALL & ~E_NOTICE);
// OR keep reporting all errors including notices:
error_reporting(E_ALL);

// Allow script to run for a potentially long time for large files
set_time_limit(0);
// Increase memory limit if needed for large files/data sets
ini_set('memory_limit', '512M');
// Increase max file upload size and post size (adjust as needed, ensure php.ini allows this too)
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '110M');


// --- Configuration ---
// Destination Database (timers table)
$dest_db_host = 'localhost'; // Or the correct host IP/name
$dest_db_user = 'mcgkxyz_masterpop';
$dest_db_pass = 'aA0109587045'; // Your destination DB password
$dest_db_name = 'mcgkxyz_timer_app';
$dest_table   = 'timers';

// --- Helper Functions ---
/**
 * Converts HH:MM:SS time format string to total seconds.
 * Also handles direct numeric values (already in seconds).
 *
 * @param string|null $timeString The time string in HH:MM:SS format or numeric seconds.
 * @return int Total seconds, or 0 if format is invalid or input is null.
 */
function timeToSeconds(?string $timeString): int {
    // Handle null or empty string
    if ($timeString === null || $timeString === '') {
        return 0;
    }
    
    // If it's already a numeric value (seconds), return it directly
    if (is_numeric($timeString)) {
        return (int)$timeString;
    }
    
    // No need to trim quotes here anymore, parseSqlValue handles it.
    $parts = explode(':', $timeString);
    if (count($parts) === 3) {
        $hours = (int)$parts[0];
        $minutes = (int)$parts[1];
        $seconds = (int)$parts[2];
        // Basic sanity check
        if ($hours >= 0 && $minutes >= 0 && $minutes < 60 && $seconds >= 0 && $seconds < 60) {
             return ($hours * 3600) + ($minutes * 60) + $seconds;
        } else {
             error_log("Invalid time components encountered for conversion: " . $timeString);
             return 0;
        }
    }
    error_log("Invalid time format encountered for conversion (expected HH:MM:SS): " . $timeString);
    return 0;
}

/**
 * Converts date formats like "26 Mar, 2025 06:43 am" to MySQL format "2025-03-26 06:43:00"
 * 
 * @param string|null $dateString The date string to convert
 * @return string|null Formatted date for MySQL or null if conversion fails
 */
function formatDateForMySQL(?string $dateString): ?string {
    if ($dateString === null || trim($dateString) === '') {
        return null;
    }
    
    // If it's already in MySQL format (YYYY-MM-DD HH:MM:SS), return as is
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateString)) {
        return $dateString;
    }
    
    // Try to parse the specific format "d M, Y H:i a"
    $dateTime = DateTime::createFromFormat('d M, Y h:i a', $dateString);
    
    if ($dateTime === false) {
        // Fallback: Try strtotime as a last resort for other potential formats
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            error_log("Could not parse date using createFromFormat or strtotime: " . $dateString);
            return null;
        }
        // Format from strtotime timestamp
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    // Format from DateTime object
    return $dateTime->format('Y-m-d H:i:s');
}

/**
 * Basic SQL value parser (handles simple strings, numbers, NULL).
 * Corrected version to handle empty strings safely.
 *
 * @param string|null $valueString The raw value string from the VALUES clause. Can be null.
 * @return string|int|null The parsed value.
 */
function parseSqlValue(?string $valueString) {
    // 1. Handle explicit null input first
    if ($valueString === null) {
        return null;
    }

    // 2. Trim whitespace from the input string
    $valueString = trim($valueString);

    // 3. Handle empty string *after* trimming
    // If it's empty now, we can't access [0], return empty string.
    if ($valueString === '') {
        return '';
    }

    // 4. Check for the literal string 'NULL' (case-insensitive) *after* trimming
    if (strtoupper($valueString) === 'NULL') {
        return null;
    }

    // 5. NOW it's safe to check the first character because we know
    //    $valueString is not null and not empty.
    // Check if it's quoted (string)
    if (($valueString[0] === "'" && substr($valueString, -1) === "'") || ($valueString[0] === '"' && substr($valueString, -1) === '"')) {
        // Handle escaped quotes within the string
        if ($valueString[0] === "'") {
             // Replace MySQL's escaped single quote ('') with a literal single quote (')
             return str_replace("''", "'", substr($valueString, 1, -1));
        }
        if ($valueString[0] === '"') {
             // Replace potential escaped double quote ("") with a literal double quote (")
             return str_replace('""', '"', substr($valueString, 1, -1));
        }
        // Fallback if quote types mismatch somehow (shouldn't happen in valid SQL)
        // Just remove the outer characters
        return substr($valueString, 1, -1);
    }

    // 6. Assume number (int or float) if it wasn't quoted and isn't 'NULL'
    if (is_numeric($valueString)) {
        // Return it as is; let the database handle the specific numeric type (int, decimal, etc.)
        // during insertion, or PHP's type juggling if used numerically later.
        return $valueString;
    }

    // 7. Fallback - return as is. This might be an unquoted string literal,
    // a keyword, a function call, or something else MySQL understands.
    return $valueString;
}


// --- Main Logic ---

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["sqlfile"])) {

    // --- File Upload Handling ---
    $upload_error_code = $_FILES['sqlfile']['error'];
    $uploaded_file_path = $_FILES['sqlfile']['tmp_name'];
    $original_filename = basename($_FILES['sqlfile']['name']);

    if ($upload_error_code !== UPLOAD_ERR_OK) {
        // Handle Upload Error (HTML output for user)
        $error_message = 'File upload error: ';
        switch ($upload_error_code) {
            case UPLOAD_ERR_INI_SIZE: $error_message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini."; break;
            case UPLOAD_ERR_FORM_SIZE: $error_message .= "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form."; break;
            case UPLOAD_ERR_PARTIAL: $error_message .= "The uploaded file was only partially uploaded."; break;
            case UPLOAD_ERR_NO_FILE: $error_message .= "No file was uploaded."; break;
            case UPLOAD_ERR_NO_TMP_DIR: $error_message .= "Missing a temporary folder."; break;
            case UPLOAD_ERR_CANT_WRITE: $error_message .= "Failed to write file to disk."; break;
            case UPLOAD_ERR_EXTENSION: $error_message .= "A PHP extension stopped the file upload."; break;
            default: $error_message .= "Unknown upload error code: {$upload_error_code}."; break;
        }
        echo "<h2>Error</h2><p style='color:red;'>{$error_message}</p>";
        echo '<p><a href="">Try Again</a></p>';
        exit;
    }

    // Basic check for file extension
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    if ($file_extension !== 'sql') {
        echo "<h2>Error</h2><p style='color:red;'>Invalid file type. Please upload a .sql file.</p>";
        echo '<p><a href="">Try Again</a></p>';
        exit;
    }

     // --- Start Processing ---
    echo "<h2>Processing Uploaded File: " . htmlspecialchars($original_filename) . "</h2>";

    // Initialize resources to null for finally block
    $dest_conn = null;
    $stmt = null;
    $file_handle = null;
    $imported_count = 0; // Initialize counters
    $error_count = 0;

    try {
        // --- Database Connection ---
        $dest_conn = new mysqli($dest_db_host, $dest_db_user, $dest_db_pass, $dest_db_name);
        if ($dest_conn->connect_error) {
            throw new Exception("Destination Connection Failed: " . $dest_conn->connect_error);
        }
        $dest_conn->set_charset("utf8mb4");
        echo "<p>Connected to destination database ({$dest_db_name}) successfully.</p>";
        echo "<p>Starting import process (this might take a while for large files)...</p>";
        ob_flush(); flush();

        // --- Prepare Insert Statement ---
        $sql_insert = "INSERT INTO {$dest_table} (
                            name, accumulated_seconds, created_at, categories,
                            is_running, current_level, notified_level
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $dest_conn->prepare($sql_insert);
        if ($stmt === false) {
            throw new Exception("Error preparing insert statement: " . $dest_conn->error);
        }

        // Define variables for binding (will be set in processInsertStatement)
        $insert_name = ''; $insert_accumulated_seconds = 0; $insert_created_at = ''; $insert_categories = '';
        $insert_is_running = 0; $insert_current_level = 1; $insert_notified_level = 1;

        $stmt->bind_param("sisssii", // s=string, i=integer - Corrected 4th param to 's'
        $insert_name, $insert_accumulated_seconds, $insert_created_at, $insert_categories,
        $insert_is_running, $insert_current_level, $insert_notified_level
    );

        // --- File Processing ---
        $file_handle = @fopen($uploaded_file_path, 'r');
        if (!$file_handle) {
             throw new Exception("Error: Could not open uploaded SQL file: " . htmlspecialchars($uploaded_file_path));
        }

        $line_number = 0;
        $current_insert_statement = '';
        $parsing_insert = false;
        $column_map = null;

        // --- Transaction Start ---
        $dest_conn->begin_transaction();

        // --- Read File Line by Line ---
        while (($line = fgets($file_handle)) !== false) {
            $line_number++;
            $trimmed_line = trim($line);

            // Basic comment/empty line skipping
            if (empty($trimmed_line) || strpos($trimmed_line, '--') === 0 || strpos($trimmed_line, '#') === 0) continue; // Added '#' comment style
            if (strpos($trimmed_line, '/*') === 0) { // Handle multi-line /* */ comments
                 while (strpos($trimmed_line, '*/') === false && ($line = fgets($file_handle)) !== false) {
                     $line_number++; $trimmed_line = trim($line);
                 }
                 continue;
            }

            // Detect start of relevant INSERT statement
            if (!$parsing_insert && preg_match('/^INSERT INTO\s+[`\'"]?activity[`\'"]?\s*(\([^)]+\))?\s*VALUES/i', $trimmed_line, $matches)) {
                $parsing_insert = true;
                $current_insert_statement = $trimmed_line; // Start accumulating

                // Determine column order from INSERT statement itself if provided
                $column_map = null;
                if (isset($matches[1]) && !empty($matches[1])) {
                    $column_list_str = trim($matches[1], '() ');
                    $declared_columns = str_getcsv($column_list_str, ',', '`'); // Handles `col, name`, etc.
                    $column_map = [];
                    foreach ($declared_columns as $index => $col) {
                        $column_map[trim(trim($col), ' `\'"')] = $index; // Trim spaces and quotes
                    }
                } else { // Fallback if columns are not explicitly listed in INSERT
                    error_log("Warning: INSERT statement near line {$line_number} does not explicitly list columns. Assuming default order (id, name, added_date, cat_name, status, time_spent, ...). Verify this!");
                    // **IMPORTANT**: This assumed order MUST match the actual table structure *at the time the dump was made*
                    $column_map = [
                        'id' => 0, 'name' => 1, 'added_date' => 2, 'cat_name' => 3, 'status' => 4,
                        'time_spent' => 5, 'last_started' => 6, 'message_status' => 7, 'colorCode' => 8,
                        'links' => 9, 'text' => 10
                    ];
                }

                // Check if statement ends on this line (check before accumulating more)
                if (substr(rtrim($trimmed_line), -1) === ';') {
                    processInsertStatement($current_insert_statement, $column_map, $stmt, $line_number, $imported_count, $error_count);
                    $parsing_insert = false; $current_insert_statement = ''; $column_map = null; // Reset
                }
                continue; // Go to next line (either processed or need more lines)
            }

            // Accumulate lines for multi-line INSERT statement
            if ($parsing_insert) {
                $current_insert_statement .= $line; // Append raw line with newline
                // Check if the statement ends now
                if (substr(rtrim($trimmed_line), -1) === ';') {
                    processInsertStatement($current_insert_statement, $column_map, $stmt, $line_number, $imported_count, $error_count);
                    $parsing_insert = false; $current_insert_statement = ''; $column_map = null; // Reset
                }
            }
            // Ignore lines that are not part of a relevant INSERT statement
        } // end while fgets

        // --- Transaction End ---
        $dest_conn->commit();
        echo "<p style='color:green; font-weight:bold;'>Transaction committed successfully.</p>";

    } catch (Exception $e) {
        // --- Error Handling & Rollback ---
        if ($dest_conn && $dest_conn->ping()) { // Check if connection still valid
             $dest_conn->rollback();
             echo "<p style='color:red; font-weight:bold;'>An error occurred. Transaction rolled back.</p>";
        } else {
             echo "<p style='color:red; font-weight:bold;'>An error occurred. Database connection might have been lost. Rollback attempted if connection was valid.</p>";
        }
        echo "<p style='color:red;'>Error Details: " . htmlspecialchars($e->getMessage()) . "</p>";
        error_log("Import script failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        $error_count++; // Count the exception as an error

    } finally {
        // --- Cleanup ---
        if ($file_handle) { fclose($file_handle); }
        if ($stmt) { $stmt->close(); }
        if ($dest_conn) { $dest_conn->close(); }

        // --- Display Final Summary ---
        echo "<hr>";
        echo "<h3>Import Summary</h3>";
        echo "Successfully executed insert statements for rows: " . $imported_count . "<br>";
        echo "Errors encountered (parsing, inserting, or other exceptions): " . $error_count . "<br>";
        if ($error_count > 0) {
             echo "<p style='color:orange;'>Check PHP error log for detailed error messages.</p>";
        }
        echo "<p>Database connection closed.</p>";
        echo '<p><a href="">Import Another File</a></p>';
    }


} else {
    // --- Display the HTML Upload Form ---
    // (HTML part remains the same as previous version)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Activity Data to Timers</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.5; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { margin-bottom: 15px; }
        input[type="submit"] { padding: 10px 15px; cursor: pointer; background-color: #007bff; color: white; border: none; border-radius: 4px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .warning { color: #856404; border: 1px solid #ffeeba; background-color: #fff3cd; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .warning ul { margin-top: 0; margin-bottom: 0; padding-left: 20px; }
        hr { border: 0; border-top: 1px solid #eee; margin: 20px 0; }
        h1, h2, h3 { margin-top: 0; }
        p { margin-bottom: 10px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Import `activity` Data from SQL File to `timers` Table</h1>
    <p>This script will parse an SQL dump file containing `INSERT INTO activity...` statements, transform the data according to the defined mapping, and insert it into the `timers` table in the `<?php echo htmlspecialchars($dest_db_name); ?>` database.</p>

    <div class="warning">
        <strong>Warning & Instructions:</strong>
        <ul>
            <li>Ensure the destination database (<code><?php echo htmlspecialchars($dest_db_name); ?></code>) and table (<code><?php echo htmlspecialchars($dest_table); ?></code>) exist and the script has correct database credentials configured within its PHP code.</li>
            <li><strong>Backup your `<?php echo htmlspecialchars($dest_db_name); ?>` database before proceeding!</strong> This script modifies data directly.</li>
            <li>This script assumes standard MySQL `INSERT` syntax for the `activity` table. Complex SQL constructs (e.g., subqueries in values, non-standard quoting) might cause parsing errors.</li>
            <li>Large files may take a significant amount of time to process. Check your server's PHP settings for `max_execution_time`, `memory_limit`, `upload_max_filesize`, and `post_max_size` if you encounter timeouts or memory issues. The script attempts to set these, but server configuration may override them.</li>
            <li>Processing happens server-side. Do not close this browser tab until the "Import Summary" appears.</li>
            <li>Check PHP's error log file for detailed error messages if the import fails unexpectedly.</li>
        </ul>
    </div>

    <form action="" method="post" enctype="multipart/form-data">
        <label for="sqlfile">Select SQL File (.sql):</label>
        <input type="file" name="sqlfile" id="sqlfile" accept=".sql,application/sql,text/plain" required>
        <br>
        <input type="submit" value="Upload and Import Data">
    </form>
</body>
</html>
<?php
} // End of the else block (display form)


/**
 * Processes a single complete INSERT statement string.
 * Parses the VALUES clause and inserts data using the prepared statement.
 *
 * @param string $insert_sql The full INSERT INTO...VALUES...; string.
 * @param array|null $column_map Map of column names to their index in the VALUES list.
 * @param mysqli_stmt $stmt The prepared mysqli statement for insertion.
 * @param int $line_number The approximate line number in the file where the statement ends.
 * @param int &$imported_count Reference to the counter for successful inserts.
 * @param int &$error_count Reference to the counter for errors.
 */
function processInsertStatement(string $insert_sql, ?array $column_map, mysqli_stmt $stmt, int $line_number, int &$imported_count, int &$error_count): void
{
    // Access global vars needed for binding values to the prepared statement
    global $insert_name, $insert_accumulated_seconds, $insert_created_at, $insert_categories;

    // Extract the VALUES(...) part more carefully
    $values_part_match = preg_match('/\sVALUES\s*(.*)/is', $insert_sql, $values_matches);
    if (!$values_part_match || !isset($values_matches[1])) {
        error_log("Could not parse VALUES part of INSERT statement near line {$line_number}. SQL: " . substr($insert_sql, 0, 200));
        $error_count++; return;
    }
    $values_string = rtrim(trim($values_matches[1]), ';');

    // Split into individual row value sets: (...), (...)
    $row_values_sets = preg_split('/\)\s*,\s*\(/', $values_string);
    $num_sets = count($row_values_sets);
    if ($num_sets === 1 && trim($row_values_sets[0]) === '') { // Handle empty VALUES clause
        error_log("Empty or malformed VALUES clause found near line {$line_number}. SQL: " . substr($insert_sql, 0, 200));
        $error_count++; return;
    }

    foreach ($row_values_sets as $row_set_index => $row_set) {
        $row_set = trim($row_set);
        if ($row_set_index === 0) $row_set = ltrim($row_set, '('); // Trim leading ( from first set
        if ($row_set_index === $num_sets - 1) $row_set = rtrim($row_set, ')'); // Trim trailing ) from last set
        $row_set = trim($row_set); // Trim again
        if (empty($row_set)) continue; // Skip empty sets

        // Use str_getcsv for robust value splitting within the row set
        $values = str_getcsv($row_set, ',', "'");

        // Check column map exists (should always unless logic error above)
        if ($column_map === null) {
             error_log("Critical Error: Column map is null within processInsertStatement near line {$line_number}. Skipping row.");
             $error_count++; continue;
        }

        // --- Extract data based on column map ---
        try {
            $name_idx = $column_map['name'] ?? null;
            $time_spent_idx = $column_map['time_spent'] ?? null;
            $added_date_idx = $column_map['added_date'] ?? null;
            $cat_name_idx = $column_map['cat_name'] ?? null;

            // Check required columns exist in the map
            if ($name_idx === null || $time_spent_idx === null || $added_date_idx === null || $cat_name_idx === null) {
                error_log("Error: Missing required columns (name, time_spent, added_date, cat_name) in INSERT statement definition near line {$line_number}. Available columns: " . implode(', ', array_keys($column_map)) . ". Skipping row.");
                $error_count++; continue;
            }

            // Extract raw values safely checking isset
            $raw_name = isset($values[$name_idx]) ? $values[$name_idx] : null;
            $raw_time_spent = isset($values[$time_spent_idx]) ? $values[$time_spent_idx] : null;
            $raw_added_date = isset($values[$added_date_idx]) ? $values[$added_date_idx] : null;
            $raw_cat_name = isset($values[$cat_name_idx]) ? $values[$cat_name_idx] : null;

            // --- Assign data and perform transformations using parseSqlValue ---
            $insert_name = parseSqlValue($raw_name);
            $insert_accumulated_seconds = timeToSeconds(parseSqlValue($raw_time_spent)); // timeToSeconds handles null/empty string
            $insert_created_at = formatDateForMySQL(parseSqlValue($raw_added_date)); // Format date for MySQL
            $insert_categories = parseSqlValue($raw_cat_name);

            // --- Post-parsing Validation ---
            if ($insert_name === null || $insert_name === '') { // Example: Skip if name is empty
                 error_log("Warning: Skipping row near line {$line_number} due to empty name.");
                 $error_count++; continue;
            }
            if ($insert_created_at === null || trim($insert_created_at) === '') { // Skip if date is invalid/missing
                 error_log("Warning: Skipping row near line {$line_number} (Name: '{$insert_name}') due to invalid or NULL 'added_date'.");
                 $error_count++; continue;
            }
            // Add other validation as needed (e.g., category format)

            // --- Execute the prepared statement (values are bound via globals) ---
            if ($stmt->execute()) {
                $imported_count++;
                 if ($imported_count % 500 == 0) { // Progress indicator
                      echo "."; if ($imported_count % 20000 == 0) echo "<br>";
                      ob_flush(); flush();
                 }
            } else {
                $error_count++;
                 error_log("Error inserting row (parsed near line {$line_number}) for name '{$insert_name}': " . $stmt->errno . " - " . $stmt->error);
                 // Optional: Log data that failed
                 // error_log("Failed Data: Name={$insert_name}, Seconds={$insert_accumulated_seconds}, Created={$insert_created_at}, Cat={$insert_categories}");
            }
        } catch (Throwable $e) { // Catch Errors and Exceptions (PHP 7+)
            $error_count++;
            error_log("Exception/Error processing row data near line {$line_number}: " . $e->getMessage() . " | Row data approx: " . $row_set);
            error_log($e->getTraceAsString());
        }
    } // end foreach row_values_sets
} // end function processInsertStatement

?>