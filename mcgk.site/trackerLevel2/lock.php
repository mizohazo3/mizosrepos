<?php

date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $lock = $con->prepare("UPDATE activity set manage_status=? where name=?");
    $lock->execute(['lock', $name]);

}


?>