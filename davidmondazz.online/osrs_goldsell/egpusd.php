<?php
// Database connection setup
$host = 'localhost'; // Your database host, typically 'localhost'
$db = 'mcgkxyz_osrs_sellgold'; // Replace with your database name
$user = 'mcgkxyz_masterpop'; // Replace with your database username
$pass = 'aA0109587045'; // Replace with your database password

$conn = new mysqli($host, $user, $pass, $db);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update the EGP to USD rate if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_rate = $_POST['egp_to_usd'];
    if (is_numeric($new_rate)) {
        $stmt = $conn->prepare("UPDATE settings SET egp_to_usd = ? WHERE id = 1");
        $stmt->bind_param("d", $new_rate);
        if ($stmt->execute()) {
            echo "<p style='color:green;'>Exchange rate updated successfully!</p><br><br><h2><a href='index.php'><-Back</a></h2>";
        } else {
            echo "<p style='color:red;'>Failed to update the rate.</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:red;'>Please enter a valid numeric value.</p>";
    }
}

// Fetch the current EGP to USD rate from the database
$result = $conn->query("SELECT egp_to_usd FROM settings LIMIT 1");

// Check if the query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch the rate as an associative array
$row = $result->fetch_assoc();
$egp_to_usd = $row['egp_to_usd'] ?? 'Rate not found';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGP to USD Rate</title>
</head>
<body>
    <h1>EGP to USD Exchange Rate</h1>
    <p>Current Rate: <?= htmlspecialchars($egp_to_usd) ?></p>
    
    <h2>Quick Change</h2>
    <form method="post">
        <label for="egp_to_usd">New Rate:</label>
        <input type="number" step="0.01" id="egp_to_usd" name="egp_to_usd" value="<?= htmlspecialchars($egp_to_usd) ?>" required>
        <button type="submit">Update Rate</button>
    </form>
</body>
</html>
