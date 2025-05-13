<?php

date_default_timezone_set("Africa/Cairo");

require 'db.php';


$q = $_REQUEST["q"];
$keywords = explode(' ', $q);

// Construct the base SQL query
$sql = "SELECT * FROM side_effects WHERE ";

// Create an array to hold individual conditions
$conditions = [];

// Create an array to hold the corresponding parameters
$params = [];

foreach ($keywords as $keyword) {
    $conditions[] = "my_sus LIKE ?";
    $params[] = "%$keyword%";
}

// Join the conditions with AND to ensure all keywords are matched
$sql .= implode(' AND ', $conditions) . " ORDER BY LENGTH(my_sus), CASE feelings
WHEN 'positive' THEN 1
WHEN 'negative' THEN 2
ELSE 3
END desc";

$stmt = $con->prepare($sql);

// Execute the query with the parameters
$stmt->execute($params);

$results = [];

if ($stmt->rowCount() > 0) {
    // Output data of each row
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determine color based on feelings
        $color = $row['feelings'] == 'positive' ? 'green' : 'red';

        $results[] = [
            'id' => $row["id"],
            'my_sus' => $row["my_sus"],
            'keyword' => $row["keyword"],
            'color' => $color // Include color in the response
        ];
    }
}

echo json_encode($results);


?>

