<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $datenow = date("d M, Y h:i a");
    $thisMed = $_GET['name'];
    if(isset($_GET['mastNote'])){
        $mastNote = $_GET['mastNote'];
    }else{
        $mastNote = '';
    }

    $insertDose = $con->prepare("INSERT INTO medtrack (medname, dose_date, details) VALUES (?, ?, ?)");
    $insertDose->execute([$thisMed, $datenow, $mastNote]);

    $updateLastdose = $con->prepare("UPDATE medlist set lastdose=?, sent_email=?, fivehalf_email=? where name=?");
    $updateLastdose->execute([$datenow, null, null, $thisMed]);

}
