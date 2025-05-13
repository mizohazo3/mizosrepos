<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    $result = $_GET['result'];
    $lockFirst = $con->prepare("UPDATE medlist set nomore=?, status=? where name=?");
    $lockFirst->execute(['yesFirst','lock', $name]);
    $lockAll = $con->prepare("UPDATE medlist SET nomore =?, status=? WHERE name LIKE ? AND name != ?");
    $lockAll->execute(['yes','lock', '%' . $result . '%', $name]);
}
