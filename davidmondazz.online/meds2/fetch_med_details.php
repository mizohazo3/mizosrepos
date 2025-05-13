<?php

error_reporting(E_ALL);  // Report all errors
ini_set('display_errors', 1);  // Display errors directly in the browser
include 'db.php';  // Make sure your db.php file correctly establishes a PDO connection
date_default_timezone_set("Africa/Cairo");
$datenow = date("d M, Y h:i a");

try {
    // The dose_date format appears to be "d M, Y h:i a" based on code review
    // Extract today's date in the same format without the time part
    $today_date_part = date("d M, Y");
    
    // Prepare the query to fetch the most recent medication record for today
    $query = "SELECT * FROM medtrack WHERE dose_date LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $con->prepare($query);  // Prepare the query using PDO
    $stmt->execute([$today_date_part . '%']);  // Execute the query with today's date as prefix
    
    // Fetch the data from the database
    $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fetch && !empty($fetch['medname'])) {
        $dose_date = $fetch['dose_date'];
        $str = str_replace(',', '', $dose_date);
        $day = date('d M Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

        $dateStarted = $dose_date;
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds2 = ($timeSecond - $timeFirst);

        $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
        $currentDate = new DateTime();
        $difference = $currentDate->diff($startDate);
        $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        $time_spent = '';
        $daystohrs = '';

        if ($differenceInSeconds <= 59) {
            $time_spent = $differenceInSeconds . ' sec';
        } elseif ($differenceInSeconds < 3600) {
            $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
        } elseif ($differenceInSeconds < 86400) {
            $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
        } elseif ($differenceInSeconds <= 2592000) {
            $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
        } elseif ($differenceInSeconds >= 2592000) {
            $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
            $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month';
            if ($differenceInSeconds >= 31104000) {
                $time_spent .= ' =';
                $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs';
            }
        }

        // Sanitize the fetched data to ensure it's safe for HTML output
        $medname = htmlspecialchars($fetch['medname']);
        $medid = (int)$fetch['id']; // Get the ID for the delete button, ensuring it's an integer
        
        // Extract first word from medname (similar to how it's done in med_functions.php)
        $words = $fetch['medname'];
        $firstWord = preg_replace('/\s*\d+\D*$/', '', $words);
        
        // Check if details exists and format accordingly
        $details = !empty($fetch['details']) ? ', <b>[ ' . htmlspecialchars($fetch['details']) . ' ]</b>' : '';

        // Create a medication entry matching the format in med_functions.php
        echo '- <font class="medTitle"><b><a href="possible_sides.php?name=' . $firstWord . '" style="text-decoration: none;color: inherit;">' . $medname . '</a></b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ')' . $details . ' <button class="deleteRecord btn btn-danger btn-sm" data-id="' . $medid . '" style="margin-left: 5px; font-size: 10px; padding: 1px 5px;">Delete</button>' .' <br>';
    } else {
        echo 'N/A';
    }

} catch (PDOException $e) {
    // Handle any errors that occur during the query execution
    echo 'Error: ' . $e->getMessage();  // Display the PDO error message
}
?>
