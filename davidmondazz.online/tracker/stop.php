<?php
date_default_timezone_set("Africa/Cairo");
// Get the path of the current script
$scriptPath = $_SERVER['SCRIPT_NAME'];

// Get the directory name of the script path
// This will give you something like /trackerOLD or / if in the root
$baseDirectory = dirname($scriptPath);

// Ensure the base directory ends with a slash if it's not the root
if ($baseDirectory !== '/' && substr($baseDirectory, -1) !== '/') {
    $baseDirectory .= '/';
}
require $_SERVER['DOCUMENT_ROOT'] . $baseDirectory . 'db.php';
include $_SERVER['DOCUMENT_ROOT'] . '/func.php';
include $_SERVER['DOCUMENT_ROOT'] . '/db.php';

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    $dateNow = date('Y-m-d H:i:s');
    $name = $_GET['name'];
    $notes = $_GET['notes'];

    $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
    $row2 = $select2->fetch();

    $dateStarted = $row2['start_date'];
    
    $timeFirst = strtotime($dateStarted);
    $timeSecond = strtotime($dateNow);
    $differenceInSeconds = ($timeSecond - $timeFirst);

    $id = $row2['id'];
    $finish = $con->prepare("UPDATE details set end_date=? , total_time=?, current_status=?, notes=? where id=?");
    $finish->execute([$dateNow, $differenceInSeconds, 'off', $notes, $id]);
    
    
 /*
    $subject2 = $row2['activity_name'].' ('.TimeLeft($dateStarted, $dateNow).')';
          $showID = 'show.php?id='.$getid.'&name';
         $notification = $connect->query("INSERT INTO notifications (message, date_time, notif_type, notif_cat, title, page, activity_id) VALUES ('$subject2', '$dateNow', 'Tracker', '$row2[cat_name]', '$row2[activity_name]', '$showID', '$getid')");
 */



    $update = $con->prepare("UPDATE activity set status=?, `time_spent`=`time_spent`+? where id=?");
    $update->execute(['off', $differenceInSeconds, $getid]);

    $catid = $row2['cat_name'];
    $catupdate = $con->prepare("UPDATE categories set `total_time`=`total_time`+? where name=?");
    $catupdate->execute([$differenceInSeconds, $catid]);
}
