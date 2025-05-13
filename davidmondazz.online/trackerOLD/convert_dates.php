<?php

echo "<pre>"; // Use preformatted text for better output readability
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // Increase max execution time for potentially long updates

require 'db.php'; // Ensure this path is correct

$source_date_format = '%d %b, %Y %h:%i %p'; // Format of the old VARCHAR dates

echo "Starting date conversion process...\n";

try {
    $con->beginTransaction(); // Start a transaction

    // --- Update 'details' table ---
    echo "\nProcessing 'details' table...\n";

    // Update start_date
    $sql_details_start = "UPDATE details
                          SET start_date = STR_TO_DATE(start_date, :source_format)
                          WHERE start_date IS NOT NULL
                            AND start_date != ''
                            AND start_date NOT LIKE '%-%-% %:%:%'"; // Avoid converting already formatted dates
    $stmt_details_start = $con->prepare($sql_details_start);
    $stmt_details_start->bindParam(':source_format', $source_date_format, PDO::PARAM_STR);
    $stmt_details_start->execute();
    $count_details_start = $stmt_details_start->rowCount();
    echo "- Converted $count_details_start rows for 'details.start_date'.\n";

    // Update end_date
    $sql_details_end = "UPDATE details
                        SET end_date = STR_TO_DATE(end_date, :source_format)
                        WHERE end_date IS NOT NULL
                          AND end_date != ''
                          AND end_date NOT LIKE '%-%-% %:%:%'";
    $stmt_details_end = $con->prepare($sql_details_end);
    $stmt_details_end->bindParam(':source_format', $source_date_format, PDO::PARAM_STR);
    $stmt_details_end->execute();
    $count_details_end = $stmt_details_end->rowCount();
    echo "- Converted $count_details_end rows for 'details.end_date'.\n";


    // --- Update 'activity' table ---
    echo "\nProcessing 'activity' table...\n";

    // Update added_date
    $sql_activity_added = "UPDATE activity
                           SET added_date = STR_TO_DATE(added_date, :source_format)
                           WHERE added_date IS NOT NULL
                             AND added_date != ''
                             AND added_date NOT LIKE '%-%-% %:%:%'";
    $stmt_activity_added = $con->prepare($sql_activity_added);
    $stmt_activity_added->bindParam(':source_format', $source_date_format, PDO::PARAM_STR);
    $stmt_activity_added->execute();
    $count_activity_added = $stmt_activity_added->rowCount();
    echo "- Converted $count_activity_added rows for 'activity.added_date'.\n";

    // Update last_started
    $sql_activity_last = "UPDATE activity
                          SET last_started = STR_TO_DATE(last_started, :source_format)
                          WHERE last_started IS NOT NULL
                            AND last_started != ''
                            AND last_started NOT LIKE '%-%-% %:%:%'";
    $stmt_activity_last = $con->prepare($sql_activity_last);
    $stmt_activity_last->bindParam(':source_format', $source_date_format, PDO::PARAM_STR);
    $stmt_activity_last->execute();
    $count_activity_last = $stmt_activity_last->rowCount();
    echo "- Converted $count_activity_last rows for 'activity.last_started'.\n";


    $con->commit(); // Commit the transaction if all updates were successful
    echo "\nDate conversion completed successfully!\n";

} catch (PDOException $e) {
    $con->rollBack(); // Roll back changes on error
    echo "\nAn error occurred during conversion: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes were saved.\n";
} catch (Exception $e) {
     $con->rollBack(); // Roll back changes on error
     echo "\nAn unexpected error occurred: " . $e->getMessage() . "\n";
     echo "Transaction rolled back. No changes were saved.\n";
}

echo "</pre>";

?>