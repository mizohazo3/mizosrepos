<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
include '../func.php';
$dateNow = date('d M, Y h:i:s a');

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    $activity_name = $_GET['name'];
    $select = $con->query("SELECT * FROM activity where id='$getid'");
    $row = $select->fetch();
    $cat_name = $row['cat_name'];
    $colorCode = rand_color();

    $starting = $con->prepare("INSERT INTO details (start_date, end_date, total_time, activity_name, cat_name, activity_id, current_status) VALUES (? , ? , ? , ?, ?, ?, ?)");
    $starting->execute([$dateNow, '', '', $activity_name, $cat_name, $getid, 'on']);
    $update = $con->prepare("UPDATE activity set status=?, last_started=?, colorCode=? where id=?");
    $update->execute(['on', $dateNow, $colorCode, $getid]);
}
