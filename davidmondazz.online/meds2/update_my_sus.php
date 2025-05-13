<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$newSus = $_POST['newSus'];
$id = $_POST['id']; // Assuming you're passing the id via AJAX

try {
    // Update the value of mySus in the database
    $stmt = $con->prepare("UPDATE side_effects SET my_sus = ? WHERE id = ?");
    $stmt->execute([$newSus, $id]);

    // Return the updated value to display on the page
    echo htmlspecialchars($newSus);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
