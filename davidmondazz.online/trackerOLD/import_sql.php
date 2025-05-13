<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600); // Increase time limit
ini_set('memory_limit', '256M'); // Increase memory limit

// --- Database Connection ---
// Ensure this path is correct and $con is established
require 'db.php';

if (!isset($con) || !$con) {
    echo "<p class=\"error\">Database connection failed. Check db.php.</p>";
    // Optionally exit here if you can't proceed without DB,
    // but for debugging the file processing, we might want to continue.
    // exit;
} else {
    echo "<p>Database connection appears successful (db.php included).</p>";
}

?>
<html>
<head>
    <title>Import Modified SQL</title>
    <style>
        body { font-family: sans-serif; padding: 20px; font-size: 14px; }
        .error { color: red; font-weight: bold; border: 1px solid red; padding: 5px; margin: 5px 0; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; border: 1px solid orange; padding: 10px; margin-bottom: 15px; }
        .info { color: blue; margin: 5px 0; }
        .debug { color: #555; margin-left: 20px; font-size: 0.9em; border-left: 2px solid #ccc; padding-left: 10px; margin-top: 2px; margin-bottom: 2px; }
        pre { background-color: #f4f4f4; border: 1px solid #ddd; padding: 10px; white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto; }
        details > summary { cursor: pointer; }
    </style>
</head>
<body>

<h1>Import SQL File (with Date Conversion)</h1>

<div class="warning">
    <strong>Security Warning:</strong> Executing SQL from uploaded files is risky. Only upload SQL files that you have generated yourself and trust completely. Do not use this with files from untrusted sources.
</div>

<form action="import_sql.php" method="post" enctype="multipart/form-data">
    <p>
        <label for="sqlfile">Select SQL File to Import:</label><br>
        <input type="file" name="sqlfile" id="sqlfile" accept=".sql" required>
    </p>
    <p>
        <input type="submit" value="Upload and Import">
    </p>
</form>

<hr>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlfile'])) {

    echo "<h2>Import Results:</h2>";

    // --- File Upload Handling ---
    if ($_FILES['sqlfile']['error'] !== UPLOAD_ERR_OK) {
        echo "<p class=\"error\">File upload error: Code " . $_FILES['sqlfile']['error'] . "</p>";
        exit;
    }

    $uploadedFilePath = $_FILES['sqlfile']['tmp_name'];
    $originalFileName = basename($_FILES['sqlfile']['name']);

    // Basic validation
    if (strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION)) !== 'sql') {
         echo "<p class=\"error\">Invalid file type. Please upload a .sql file.</p>";
         exit;
    }

    echo "<p class='info'>Processing file: " . htmlspecialchars($originalFileName) . "</p>";

    // --- Read SQL File Content ---
    $sqlContent = file_get_contents($uploadedFilePath);
    if ($sqlContent === false) {
        echo "<p class=\"error\">Failed to read the uploaded SQL file.</p>";
        exit;
    }
    echo "<p class='info'>Successfully read SQL file content.</p>";

    // --- Modify SQL Content ---
    $source_date_format = 'j M, Y h:i a'; // Format like '29 May, 2022 04:08 am'
    $modifiedSqlContent = $sqlContent; // Start with original content
    $conversionErrors = 0;
    $conversionsAttempted = 0;

    try {
        echo "<p class='info'>Attempting to modify SQL content for date conversion...</p>";
        // UPDATED Regex:
        // - Assumes ID is an unquoted integer: (\d+)
        // - Allows more flexible whitespace: \s*
        // - Matches the specific date format within quotes.
        $pattern = "/\(\s*(\d+)\s*,\s*'(\d{1,2}\s+[A-Za-z]{3,},\s+\d{4}\s+\d{1,2}:\d{2}\s+(?:am|pm))'\s*,\s*'(\d{1,2}\s+[A-Za-z]{3,},\s+\d{4}\s+\d{1,2}:\d{2}\s+(?:am|pm))'\s*,\s*'([^']+)'\s*,\s*'([^']+)'\s*,\s*'([^']+)'\s*,\s*(\d+)\s*,\s*'([^']+)'\s*,\s*'([^']*)'\s*\)/i";

        // --- DEBUG: Check if regex matches ---
        $matches_found = preg_match_all($pattern, $sqlContent, $all_matches);
        echo "<p class='info'>Regex check: Found " . $matches_found . " potential data rows matching the pattern.</p>";
        if ($matches_found > 0) {
            echo "<details><summary>Show First Matched Row Details (for debugging)</summary><pre>";
            echo "Full Match String:\n" . htmlspecialchars($all_matches[0][0]) . "\n\n";
            echo "Captured Groups:\n";
            for ($i = 1; $i < count($all_matches); $i++) {
                echo "  Group $i: " . htmlspecialchars($all_matches[$i][0]) . "\n";
            }
            echo "</pre></details>";
        } else {
            echo "<p class='warning'>Warning: The regex pattern did not match any lines expected to contain data rows. The script might not modify any dates. Please check the pattern against the actual file content below.</p>";
            echo "<h3>SQL Content Snippet (for Regex Debugging):</h3><pre>" . htmlspecialchars(substr($sqlContent, 0, 1500)) . "...</pre>";
        }
        // --- END DEBUG ---

        $modifiedSqlContent = preg_replace_callback(
            $pattern,
            function ($matches) use ($source_date_format, &$conversionErrors, &$conversionsAttempted) {
                $conversionsAttempted++;
                $id = $matches[1];
                $startDateStr = $matches[2]; // Original start date string
                $endDateStr = $matches[3];   // Original end date string
                $duration = $matches[4];
                $activity = $matches[5];
                $category = $matches[6];
                $categoryId = $matches[7];
                $status = $matches[8];
                $notes = $matches[9];

                echo "<div class='debug'>Processing row ID=$id: Start='$startDateStr', End='$endDateStr'</div>";

                // Parse the dates using DateTime::createFromFormat
                $startDateTime = DateTime::createFromFormat($source_date_format, $startDateStr);
                $endDateTime = DateTime::createFromFormat($source_date_format, $endDateStr);

                // Check if parsing failed OR if there were warnings/errors even if an object was returned
                $startErrors = DateTime::getLastErrors(); // Check errors *after* attempting creation
                $endErrors = DateTime::getLastErrors();   // Check errors *after* attempting creation

                $startHasIssues = ($startDateTime === false || $startErrors['warning_count'] > 0 || $startErrors['error_count'] > 0);
                $endHasIssues = ($endDateTime === false || $endErrors['warning_count'] > 0 || $endErrors['error_count'] > 0);

                if (!$startHasIssues && !$endHasIssues) {
                    // Success
                    $formattedStartDate = $startDateTime->format('Y-m-d H:i:s');
                    $formattedEndDate = $endDateTime->format('Y-m-d H:i:s');
                    echo "<div class='debug success' style='margin-left: 40px;'> -> Parsed OK: '$formattedStartDate', '$formattedEndDate'</div>";

                    // Return the modified tuple with converted dates, ensuring strings are quoted and escaped
                    return sprintf(
                        "(%d, '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s')",
                        $id,
                        $formattedStartDate, // Already formatted correctly
                        $formattedEndDate,   // Already formatted correctly
                        addslashes($duration),
                        addslashes($activity),
                        addslashes($category),
                        $categoryId,
                        addslashes($status),
                        addslashes($notes)
                    );
                } else {
                    // Failure
                    $conversionErrors++;
                    echo "<div class='debug error' style='margin-left: 40px;'> -> FAILED to parse dates. Returning original row.</div>";
                     if ($startHasIssues) {
                         echo "<div class='debug error' style='margin-left: 60px;'>Start Date Parse Errors/Warnings ('$startDateStr'): <pre>" . print_r($startErrors, true) . "</pre></div>";
                     }
                     if ($endHasIssues) {
                         echo "<div class='debug error' style='margin-left: 60px;'>End Date Parse Errors/Warnings ('$endDateStr'): <pre>" . print_r($endErrors, true) . "</pre></div>";
                     }
                    // Return the original matched string to avoid breaking the SQL structure
                    return $matches[0];
                }
            },
            $sqlContent // Process the original content
        );

        // Check for preg_replace_callback errors
        if ($modifiedSqlContent === null && preg_last_error() !== PREG_NO_ERROR) {
             echo "<p class=\"error\">Fatal error during regex processing: " . preg_last_error_msg() . "</p>";
             exit;
        }

        echo "<p class='info'>SQL content modification attempted. Processed $conversionsAttempted rows. Encountered $conversionErrors date conversion errors.</p>";
        if ($conversionErrors > 0) {
            echo "<p class='warning'>Rows with conversion errors were left unmodified in the SQL below.</p>";
        }

        // Display modified SQL for review (ensure it looks correct)
        echo "<h3>Modified SQL (Preview - first 5000 chars):</h3><pre>" . htmlspecialchars(substr($modifiedSqlContent, 0, 5000)) . (strlen($modifiedSqlContent) > 5000 ? "..." : "") . "</pre>";

    } catch (Exception $e) {
        echo "<p class=\"error\">An unexpected error occurred during SQL modification: " . htmlspecialchars($e->getMessage()) . "</p>";
        // Optionally display stack trace for deeper debugging
        // echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        exit;
    }

    // --- Remove CREATE TABLE details statement ---
    $patternCreate = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?details`?\s*\(.*?\)\s*(?:ENGINE=\w+\s*)?(?:DEFAULT\s+CHARSET=\w+\s*)?(?:COLLATE=\w+\s*)?;/is';
    $countRemovedCreate = 0;
    $modifiedSqlContent = preg_replace($patternCreate, '', $modifiedSqlContent, -1, $countRemovedCreate);
    if ($countRemovedCreate > 0) {
        echo "<p class='info'>Removed $countRemovedCreate 'CREATE TABLE details' statement(s).</p>";
    } else {
        echo "<p class='info'>No 'CREATE TABLE details' statement found or removed.</p>";
    }


    // --- Remove ALTER TABLE statements adding keys/indexes for 'details' ---
    $patternsToRemoveKeys = [
        '/ALTER\s+TABLE\s+`?details`?\s+ADD\s+PRIMARY\s+KEY\s*\(.*?\)\s*;/is',
        '/ALTER\s+TABLE\s+`?details`?\s+ADD\s+CONSTRAINT\s+`?\w+`?\s+PRIMARY\s+KEY\s*\(.*?\)\s*;/is',
        '/ALTER\s+TABLE\s+`?details`?\s+ADD\s+UNIQUE\s+KEY\s+`?\w+`?\s*\(.*?\)\s*;/is',
        '/ALTER\s+TABLE\s+`?details`?\s+ADD\s+KEY\s+`?\w+`?\s*\(.*?\)\s*;/is'
    ];
    $totalKeysRemoved = 0;
    foreach ($patternsToRemoveKeys as $keyPattern) {
        $countRemovedKeys = 0;
        $modifiedSqlContent = preg_replace($keyPattern, '', $modifiedSqlContent, -1, $countRemovedKeys);
        if ($countRemovedKeys > 0) {
            // echo "<p class='info'>Removed $countRemovedKeys statement(s) matching key/index pattern: " . htmlspecialchars($keyPattern) . "</p>";
            $totalKeysRemoved += $countRemovedKeys;
        }
    }
     if ($totalKeysRemoved > 0) {
         echo "<p class='info'>Total key/index definition statements removed for 'details': $totalKeysRemoved</p>";
     } else {
         echo "<p class='info'>No key/index ALTER TABLE statements found or removed for 'details'.</p>";
     }

    // --- Execute Modified SQL ---
    if (!isset($con) || !$con) {
         echo "<p class=\"error\">Database connection lost or not established before execution attempt. Cannot proceed.</p>";
         exit;
    }

    echo "<p class='info'>Attempting to execute the modified SQL against the database...</p>";

    try {
        // Test a date conversion locally again to ensure PHP side is okay
        $testDate = '29 May, 2022 12:23 pm';
        $parsedDate = DateTime::createFromFormat($source_date_format, $testDate);
        $testDateErrors = DateTime::getLastErrors(); // Check after attempting
        if ($parsedDate !== false && $testDateErrors['warning_count'] === 0 && $testDateErrors['error_count'] === 0) {
            $mysqlDateFormat = $parsedDate->format('Y-m-d H:i:s');
            echo "<p class=\"success\">PHP Test date conversion OK: '$testDate' -> '$mysqlDateFormat'</p>";
        } else {
            echo "<p class=\"error\">PHP Test date conversion FAILED for: '$testDate'. Errors/Warnings: <pre>" . print_r($testDateErrors, true) . "</pre></p>";
        }

        $con->beginTransaction();
        echo "<p class='info'>Executing SQL within a transaction...</p>";

        // Execute the entire modified SQL string
        $result = $con->exec($modifiedSqlContent);

        if ($result === false) {
            // Execution failed
            $errorInfo = $con->errorInfo();
            echo "<p class=\"error\">SQL execution failed: SQLSTATE[" . $errorInfo[0] . "] Driver Error [" . $errorInfo[1] . "] " . htmlspecialchars($errorInfo[2]) . "</p>";
            echo "<p class='info'>Attempting to roll back transaction...</p>";
            $con->rollBack();
            echo "<p class='info'>Transaction rolled back due to execution error.</p>";
        } else {
            // Execution succeeded (or at least didn't return false)
            $con->commit();
            echo "<p class=\"success\">SQL file import process completed successfully! (PDO::exec returned: " . var_export($result, true) . " - Note: this might be 0 even on success for multi-statement queries)</p>";
        }

    } catch (PDOException $e) {
        echo "<p class=\"error\">Database error during execution: " . htmlspecialchars($e->getMessage()) . "</p>";
        // Check if in transaction before rolling back
        if ($con->inTransaction()) {
             echo "<p class='info'>Attempting to roll back transaction due to PDOException...</p>";
             $con->rollBack();
             echo "<p class='info'>Transaction rolled back.</p>";
        }
    } catch (Exception $e) {
         echo "<p class=\"error\">An unexpected PHP error occurred during execution: " . htmlspecialchars($e->getMessage()) . "</p>";
         if ($con->inTransaction()) {
             echo "<p class='info'>Attempting to roll back transaction due to general Exception...</p>";
             $con->rollBack();
             echo "<p class='info'>Transaction rolled back.</p>";
         }
    }

    echo "<p class='info'>Import process finished.</p>";

} // end if POST

?>

</body>
</html>