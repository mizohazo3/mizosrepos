<?php
date_default_timezone_set("Africa/Cairo");
include '../func.php';

$ids = $_GET['id']; // Retrieve the IDs from the 'ids' parameter in the URL

echo gmdate("H:i:s", TimerNow($ids));
