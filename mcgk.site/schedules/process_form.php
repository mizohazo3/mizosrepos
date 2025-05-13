<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $taskid = $_POST['taskid'];
    // Check if the checkbox is checked
    if (isset($_POST["email_notify"]) && $_POST["email_notify"] == "yes") {
       
        // User opted in for email notifications
        $updatelasttask = $con->prepare("UPDATE tasklist set email_notify=? where id=?");
        $updatelasttask->execute(['yes', $taskid]);
    } else {
        // User did not opt in for email notifications
        $updatelasttask = $con->prepare("UPDATE tasklist set email_notify=? where id=?");
        $updatelasttask->execute(['no', $taskid]);
    }
}
?>
