<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
$datenow = date("d M, Y h:i a");

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $start = $con->prepare("UPDATE medlist set start_date=?, end_date=? where name=?");
    $start->execute([$datenow, '', $name]);

}
