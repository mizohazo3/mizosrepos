<?php
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get the activity information
    $activity = $con->prepare("SELECT * FROM activity WHERE id = ?");
    $activity->execute([$id]);
    
    if ($row = $activity->fetch(PDO::FETCH_ASSOC)) {
        // Get the details information (the currently running session)
        $details = $con->prepare("SELECT * FROM details WHERE activity_id = ? AND current_status = 'on'");
        $details->execute([$id]);
        $detailsRow = $details->fetch(PDO::FETCH_ASSOC);
        
        // Format the output for a working activity
        $lastStarted = '<span class="timerLive" data-id="' . $row['last_started'] . '" style="border:2px #0AAD9B solid;border-radius:7px;margin:5px;padding: 5px;vertical-align: middle;background:black;color:white;"></span>';
        $stopButton = '<form action="index.php" method="post" style="display:inline;">
            <input type="hidden" name="StopId" value="' . $row['id'] . '">
            <input type="submit" name="stop" value="Stop" class="btn btn-danger btn-sm" style="opacity: 0.8;border-radius:10px;margin-left:3px;">
        </form>';
        
        echo $stopButton . ' ' . $lastStarted . '<img src="img/on3.png" style="padding-left:10px;"> <b style="color:' . $row['colorCode'] . ';font-size:16px;"><a href="show.php?id=' . $row['id'] . '" style="color: inherit; " class="stroked-text">' . $row['name'] . '</a></b> ';
    }
}
?> 