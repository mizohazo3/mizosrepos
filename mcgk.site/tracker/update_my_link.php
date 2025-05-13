<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$newLink = $_POST['newLink'];
$thisLinkName = $_POST['thisLinkName'];
$thisID = $_POST['id'];

try {
    // Update the value of mySus in the database
    $stmt = $con->prepare("UPDATE activity SET links = ? WHERE id = ?");
    $stmt->execute([$newLink, $thisID]);

    // Return the updated value to display on the page
    echo htmlspecialchars($newLink);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
