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
        // Return the HTML for a start button
        echo '<button><img src="img/starticon22.png" class="FastStart" style="opacity:0.6;border:2px solid #A7A7A7;border-radius:10px;padding:3px;"></button>';
    }
}
?> 