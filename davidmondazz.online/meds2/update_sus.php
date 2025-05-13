<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['id'])) {
    $timestamp = time(); // Store timestamp in seconds
    $id = $_GET['id'];
    
    try {
        // Update the last_checked timestamp in the database
        $stmt = $con->prepare("UPDATE side_effects SET last_checked = ? WHERE id = ?");
        $stmt->execute([$timestamp, $id]);
        
        // Return the timestamp
        echo $timestamp;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?> 