<?php

error_log("start.php: Script execution started."); // Log start

date_default_timezone_set("Africa/Cairo");

error_log("start.php: Including db.php.");
require 'db.php'; // Ensure this path is correct relative to start.php
error_log("start.php: db.php included.");


// Check if func.php exists and include it
$func_path = $_SERVER['DOCUMENT_ROOT'] . '/func.php';
error_log("start.php: Checking for func.php at " . $func_path);
if (file_exists($func_path)) {
    error_log("start.php: func.php found. Including...");
    include $func_path;
    error_log("start.php: func.php included.");
} else {
    // Log error or die - func.php is essential
    error_log("Fatal Error: func.php not found at " . $func_path);
    http_response_code(500);
    die("Server configuration error: Required function file missing.");
}


if (isset($_GET['id']) && isset($_GET['name'])) { // Also check if name is set
    error_log("start.php: GET parameters 'id' and 'name' are set.");
    $timestamp = date('Y-m-d H:i:s');
    $activity_name = $_GET['name'];
    $getid = $_GET['id'];
    error_log("start.php: id=$getid, name=$activity_name");


    // Validate id (basic example, enhance as needed)
    if (!ctype_digit($getid)) {
         error_log("start.php: Invalid activity ID received: $getid");
         http_response_code(400); // Bad Request
         die("Invalid activity ID.");
    }


    try { // Use try-catch for PDO operations
        error_log("start.php: Preparing SELECT query for activity id: $getid");
        $select = $con->prepare("SELECT cat_name FROM activity where id = ?"); // Prepare statement
        $select->execute([$getid]);
        error_log("start.php: SELECT query executed.");
        $row = $select->fetch(PDO::FETCH_ASSOC); // Use FETCH_ASSOC

        if ($row) {
            error_log("start.php: Activity found. cat_name: " . $row['cat_name']);
            $cat_name = $row['cat_name'];

            // Check if rand_color function exists
            error_log("start.php: Checking if function 'rand_color' exists.");
            if (!function_exists('rand_color')) {
                 error_log("Fatal Error: rand_color() function not defined.");
                 http_response_code(500);
                 die("Server error: Undefined function.");
            }
            error_log("start.php: Function 'rand_color' exists. Calling it...");
            $colorCode = rand_color();
            error_log("start.php: rand_color() returned: " . $colorCode);


            // Use prepared statements consistently for security
            error_log("start.php: Preparing INSERT into details.");
            $starting = $con->prepare("INSERT INTO details (start_date, end_date, total_time, activity_name, cat_name, activity_id, current_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $starting->execute([$timestamp, NULL, '', $activity_name, $cat_name, $getid, 'on']);
            error_log("start.php: INSERT into details executed.");


            error_log("start.php: Preparing UPDATE activity.");
            $update = $con->prepare("UPDATE activity set status=?, last_started=?, colorCode=? where id=?");
            $update->execute(['on', $timestamp, $colorCode, $getid]);
            error_log("start.php: UPDATE activity executed.");


            // Optional: Send a success response back to AJAX if needed
            // echo json_encode(['status' => 'success', 'message' => 'Activity started.']);
            error_log("start.php: Activity started successfully for id: $getid");


        } else {
            // Handle case where activity ID doesn't exist
            error_log("Error: Activity ID '$getid' not found in database.");
            http_response_code(404); // Not Found
            die("Activity not found.");
        }
    } catch (PDOException $e) {
        // Log the detailed PDO error (don't expose details to the user)
        error_log("Database Error in start.php: " . $e->getMessage());
        http_response_code(500);
        die("Database error occurred.");
    }

} else {
    // Handle missing parameters
    error_log("start.php: Missing required GET parameters (id or name).");
    http_response_code(400); // Bad Request
    die("Missing required parameters (id or name).");
}

error_log("start.php: Script execution finished normally."); // Log end
?>
