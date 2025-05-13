<?php

require 'db.php';

if (isset($_GET['name'])) {
    $catName = $_GET['name'];
    $deleteCat = $con->query("DELETE FROM categories WHERE name='$catName'");
    $deleteActivities = $con->query("DELETE FROM activity WHERE cat_name='$catName'");
    $deleteDetailes = $con->query("DELETE FROM details where cat_name='$catName'");

    echo 'Deleted successfully.';

}
