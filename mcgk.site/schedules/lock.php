<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
$datenow = date("d M, Y h:i a");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $lock = $con->prepare("UPDATE tasklist set status=?, last_lock=?, lasttask=?, sent_email=? where id=?");
    $lock->execute(['lock', $datenow, '', null, $id]);

}
