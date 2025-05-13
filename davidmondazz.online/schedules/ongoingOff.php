<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['id'])) {
    $datenow = date("d M, Y h:i a");
    $id = $_GET['id'];
    $this_Side = $_GET['keyword'];
    $my_sus = $_GET['susText'];

    $updateLastdose = $con->prepare("UPDATE side_effects set ongoing=?, ended=?, my_sus=? where id=? and keyword=?");
    $updateLastdose->execute(['no', $datenow, $my_sus, $id, $this_Side]);

}
