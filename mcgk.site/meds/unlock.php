<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $lock = $con->prepare("UPDATE medlist set status=? where name=?");
    $lock->execute(['open', $name]);

}
