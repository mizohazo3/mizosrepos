<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_POST['name'])) {
    $catName = $_POST['name'];
    $catID = $_POST['id'];

    $deleteCat = $con->prepare("DELETE FROM categories where id=?");
    $deleteCat->execute([$catID]);

    $deleteTasks = $con->prepare("DELETE FROM list where category=?");
    $deleteTasks->execute([$catName]);
}
