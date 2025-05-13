<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
include '../func.php';
include '../db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['name'])) {
    $allid = $_GET['id'];
    $arr = explode(', ', $allid);

    foreach ($arr as $keys) {
        $getid = $keys;

        $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
        $row2 = $select2->fetch();

        // Use timestamp format for database
        $timestamp = date('Y-m-d H:i:s');
        // Keep formatted date for display purposes
        $dateNow = date('d M, Y h:i:s a');
        
        // Calculate time difference properly using UNIX timestamps
        $start_timestamp = strtotime($row2['start_date']);
        $end_timestamp = time();
        $differenceInSeconds = $end_timestamp - $start_timestamp;

        $id = $row2['id'];
        $finish = $con->prepare("UPDATE details set end_date=? , total_time=?, current_status=? where id=?");
        $finish->execute([$timestamp, $differenceInSeconds, 'off', $id]);

        $update = $con->prepare("UPDATE activity set status=?, time_spent=time_spent+? where id=?");
        $update->execute(['off', $differenceInSeconds, $getid]);

        $subject2 = $row2['activity_name'].' ('.TimeLeft($row2['start_date'], $dateNow).')';
        $showID = 'show.php?id='.$getid.'&name';
        $notification = $connect->query("INSERT INTO notifications (message, date_time, notif_type, notif_cat, title, page, activity_id) VALUES ('$subject2', '$timestamp', 'Tracker', '$row2[cat_name]', '$row2[activity_name]', '$showID', '$getid')");

        $catid = $row2['cat_name'];
        $catupdate = $con->prepare("UPDATE categories set total_time=total_time+? where name=?");
        $catupdate->execute([$differenceInSeconds, $catid]);
        header("Refresh:0; url=index.php");
    }
}
