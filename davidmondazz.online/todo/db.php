<?php
$host = 'localhost';
$username = 'mcgkxyz_masterpop';
$password = 'aA0109587045';
$database = 'mcgkxyz_todo';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
