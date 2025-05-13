<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_POST['name'])) {
    $TaskID = $_POST['id'];
    $TaskName = $_POST['name'];

    $updateLastdose = $con->prepare("UPDATE list set name=? where id=?");
    $updateLastdose->execute([$TaskName, $TaskID]);

}
