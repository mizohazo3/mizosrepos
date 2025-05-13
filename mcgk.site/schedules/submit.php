<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $datenow = date("d M, Y h:i a");
    $thisMed = $_GET['name'];
  

    // get half life
    $selectHalf = $con->prepare("SELECT * FROM tasklist WHERE name = ?");
    $selectHalf->execute([$thisMed]); // Execute the query
    $fetch = $selectHalf->fetch(); // Fetch the data
    $default_duration = $fetch['default_duration'];

    $insertDose = $con->prepare("INSERT INTO tasktrack (taskname, task_date, default_duration) VALUES (?, ?, ?)");
    $insertDose->execute([$thisMed, $datenow, $default_duration]);

    $updatelasttask = $con->prepare("UPDATE tasklist set lasttask=?, sent_email=? where name=?");
    $updatelasttask->execute([$datenow, null, $thisMed]);

}
