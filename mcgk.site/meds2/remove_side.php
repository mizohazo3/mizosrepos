<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
$datenow = date("d M, Y h:i a");

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $id = $_GET['id'];
    $remove = $con->prepare("DELETE from side_effects where keyword=? and id=?");
    $remove->execute([$name, $id]);

}
