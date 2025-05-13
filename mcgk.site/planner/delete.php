<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_POST['name'])) {
    $dateNow = date('d-M-Y h:i a');
    $TaskName = $_POST['name'];
    $getID = $_POST['id'];

    $updateLastdose = $con->prepare("DELETE FROM list where id=?");
    $updateLastdose->execute([$getID]);

}
