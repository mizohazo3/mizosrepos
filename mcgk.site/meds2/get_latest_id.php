<?php
date_default_timezone_set("Africa/Cairo");

// Connect to MySQL
$host = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$dbname = "mcgkxyz_meds2";

// Attempt to establish the database connection
try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle database connection error
    echo "Connection failed: " . $e->getMessage();
    exit(); // Terminate script execution
}

// Get the name parameter from the GET request
$name = $_GET['name'];

// Query to fetch the latest ID for the given keyword
$sql = "SELECT id FROM side_effects WHERE keyword = :keyword ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':keyword', $name);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if a row is found
if ($row) {
    // Return the latest ID as JSON
    echo json_encode($row['id']);
} else {
    // Return an error message if no row is found
    echo json_encode(null);
}
?>
