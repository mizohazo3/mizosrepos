<?php
date_default_timezone_set("Africa/Cairo");

// Connect to MySQL
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$database = "mcgkxyz_meds2";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$med_name = $_GET['name'];
echo '<a href="index.php"><button style="margin-right: 10px;vertical-align: middle;"><img src="img/home.png" width="30px;" height="30px;"></button></a> ';
echo '<b style="font-size:20px;">' . $med_name . '</b><br><br>';

// Modify the med_name variable to only contain the part before the space
$med_name = explode(" ", $med_name)[0];

$stmt = $conn->prepare("SELECT COUNT(keyword) as kcount, keyword, feelings, id FROM side_effects WHERE my_sus LIKE ? OR my_sus = ? GROUP BY keyword ORDER BY CASE feelings WHEN 'positive' THEN 1 WHEN 'neutral' THEN 2 WHEN 'negative' THEN 3 END, kcount DESC");

if ($stmt) {
    // Bind the parameter
    $med_name_like = "%$med_name%";
    $stmt->bind_param("ss", $med_name_like, $med_name);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Initialize counters for the sums
    $totalGoodSides = 0;
    $totalBadSides = 0;
    $totalNeutralSides = 0;

    // Check if any row is returned
    if ($result->num_rows > 0) {
        $goodSides = [];
        $badSides = [];
        $neutralSides = [];

        while ($row = $result->fetch_assoc()) {
            if ($row['feelings'] == 'positive') {
                $goodSides[] = "<font color='green'><a href='side_investigation.php?id=" . $row['id'] . "&name=" . $row['keyword'] . "'>" . $row['keyword'] . "</a></font>(" . $row['kcount'] . ")";
                $totalGoodSides += $row['kcount'];
            } elseif ($row['feelings'] == 'negative') {
                $badSides[] = "<font color='red'><a href='side_investigation.php?id=" . $row['id'] . "&name=" . $row['keyword'] . "'>" . $row['keyword'] . "</a></font>(" . $row['kcount'] . ")";
                $totalBadSides += $row['kcount'];
            } else {
                $neutralSides[] = "<font color='blue'><a href='side_investigation.php?id=" . $row['id'] . "&name=" . $row['keyword'] . "'>" . $row['keyword'] . "</a></font>(" . $row['kcount'] . ")";
                $totalNeutralSides += $row['kcount'];
            }
        }

        // Display good sides
        if (!empty($goodSides)) {
            echo "Good Sides: <br>";
            echo implode(", ", $goodSides);
            echo "<br>Total Good Sides: $totalGoodSides<br><br>";
        }

        // Display bad sides
        if (!empty($badSides)) {
            echo "Bad Sides: <br>";
            echo implode(", ", $badSides);
            echo "<br>Total Bad Sides: $totalBadSides<br><br>";
        }

        // Display neutral sides
        if (!empty($neutralSides)) {
            echo "Neutral Sides: <br>";
            echo implode(", ", $neutralSides);
            echo "<br>Total Neutral Sides: $totalNeutralSides<br><br>";
        }

        // Display total of all sides
        $totalSides = $totalGoodSides + $totalBadSides + $totalNeutralSides;
        echo "Overall Total Sides: $totalSides<br><br>";
    } else {
        echo "No matching keyword found for $med_name";
    }

    // Close the statement
    $stmt->close();
}
?>
