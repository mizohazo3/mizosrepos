<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
include '../func.php';
$dateNow = date('d M, Y h:i:s a');

if (isset($_GET['id'])) {
    $getid = $_GET['id']; // page id
    $note_id = $_GET['name']; // note id
    $note_value = $_GET['editnote'];
    $update = $con->prepare("UPDATE details set notes=? where id=? and activity_id=?");
    $update->execute([$note_value, $note_id, $getid]);
}
