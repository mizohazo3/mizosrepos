<?php

include_once '../timezone_config.php';
// api/fix_levels.php

require_once 'db.php'; // Include database connection

try {
    // Prepare the update statement
    $stmt = $pdo->prepare("UPDATE levels SET reward_rate_per_hour = 0.00 WHERE reward_rate_per_hour < 0");

    // Execute the update
    $stmt->execute();

    // Get the number of affected rows
    $rowCount = $stmt->rowCount();

    echo "Successfully updated levels table. Set reward_rate_per_hour to 0.00 for $rowCount rows where it was negative.\n";

} catch (PDOException $e) {
    error_log('Error in fix_levels.php: ' . $e->getMessage());
    echo "An error occurred: " . $e->getMessage() . "\n";
    exit(1); // Indicate failure
}

exit(0); // Indicate success
?>