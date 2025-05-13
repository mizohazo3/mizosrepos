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

// Query to fetch all IDs and keywords for the given keyword
$sql = "SELECT id, keyword FROM side_effects WHERE my_sus = :my_sus";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':my_sus', $name);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the IDs and keywords as JSON
echo json_encode($rows);

?>
