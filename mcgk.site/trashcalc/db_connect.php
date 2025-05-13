<?php

$host = 'localhost'; // Update with your database host
$dbname = 'mcgkxyz_trashcalc'; // Update with your database name
$username = 'mcgkxyz_masterpop'; // Update with your database username
$password = 'aA0109587045'; // Update with your database password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
