<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_POST['name'])) {
    $dateNow = date('d-M-Y h:i a');
    $TaskName = $_POST['name'];
    $getID = $_POST['id'];

    $updateLastdose = $con->prepare("UPDATE list set status=?, done_date=? where id=?");
    $updateLastdose->execute(['canceled', $dateNow, $getID]);

}
