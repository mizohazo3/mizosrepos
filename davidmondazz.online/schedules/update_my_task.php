<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$newTask = $_POST['newTask'];
$id = $_POST['id']; // Assuming you're passing the id via AJAX
$showNameValue = $_POST['showNameValue'];

try {
    // Update the value of mySus in the database
    $stmt = $con->prepare("UPDATE tasklist SET lasttask = ? WHERE id = ?");
    $stmt->execute([$newTask, $id]);

    $stmt = $con->prepare("UPDATE tasktrack SET task_date = ? WHERE taskname = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$newTask, $showNameValue]);

    // Return the updated value to display on the page
    echo htmlspecialchars($newTask);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
