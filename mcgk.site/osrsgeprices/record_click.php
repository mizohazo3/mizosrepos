<?php
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$dbname = "mcgkxyz_link_tracker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);
$linkId = $data['id'];

// Update the click count for the link
$stmt = $conn->prepare("UPDATE links SET clicks = clicks + 1 WHERE id = ?");
$stmt->bind_param("i", $linkId);
$stmt->execute();
$stmt->close();

$conn->close();
?>
