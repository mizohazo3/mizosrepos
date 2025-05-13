<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $datenow = date("d M, Y h:i a");
    $thisMed = $_GET['name'];
    if(isset($_GET['amount'])){
        $amount = $_GET['amount'];
    }else{
        $amount = '';
    }

    $medplusAmount = $thisMed.' '.$amount.'IU';

    // get half life
    $selectHalf = $con->prepare("SELECT * FROM medlist WHERE name = ?");
    $selectHalf->execute([$thisMed]); // Execute the query
    $fetch = $selectHalf->fetch(); // Fetch the data
    $default_half_life = $fetch['default_half_life'];

    $insertDose = $con->prepare("INSERT INTO medtrack (medname, dose_date, details, default_half_life) VALUES (?, ?, ?, ?)");
    $insertDose->execute([$medplusAmount, $datenow, $amount, $default_half_life]);

    $updateLastdose = $con->prepare("UPDATE medlist set lastdose=?, email_half=?, email_fivehalf=? where name=?");
    $updateLastdose->execute([$datenow, null, null, $thisMed]);

}
