<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $datenow = date("d M, Y h:i a");
    $medname = $_GET['name'];
    $selectall = $con->query("SELECT * FROM medlist where name='$medname'");
    $row = $selectall->fetch();
    $startDate = $row['start_date'];

    $updateList = $con->prepare("UPDATE medlist set end_date=? where name=? ");
    $updateList->execute([$datenow, $medname]);

    $insertHistory = $con->prepare("INSERT INTO history (name, from_date, to_date) VALUES (?, ?, ?)");
    $insertHistory->execute([$medname, $startDate, $datenow]);

}
