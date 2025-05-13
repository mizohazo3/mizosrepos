<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
include '../func.php';
include '../db.php';

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    
    // Use timestamp format for database
    $timestamp = date('Y-m-d H:i:s');
    // Keep formatted date for display purposes
    $dateNow = date('d M, Y h:i:s a');
    
    $name = $_GET['name'];
    $notes = $_GET['notes'];

    $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
    $row2 = $select2->fetch();

    // Calculate time difference properly using UNIX timestamps
    $start_timestamp = strtotime($row2['start_date']);
    $end_timestamp = time();
    $differenceInSeconds = $end_timestamp - $start_timestamp;

    $id = $row2['id'];
    $finish = $con->prepare("UPDATE details set end_date=?, total_time=?, current_status=?, notes=? where id=?");
    $finish->execute([$timestamp, $differenceInSeconds, 'off', $notes, $id]);
    
    $update = $con->prepare("UPDATE activity set status=?, `time_spent`=`time_spent`+? where id=?");
    $update->execute(['off', $differenceInSeconds, $getid]);

    $catid = $row2['cat_name'];
    $catupdate = $con->prepare("UPDATE categories set `total_time`=`total_time`+? where name=?");
    $catupdate->execute([$differenceInSeconds, $catid]);
}
