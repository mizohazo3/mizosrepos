<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$q = $_REQUEST["q"];
$keywords = explode(' ', $q);

// Construct the base SQL query
$sql = "SELECT keyword FROM side_effects WHERE ";

// Create an array to hold individual conditions
$conditions = [];

// Create an array to hold the corresponding parameters
$params = [];

foreach ($keywords as $keyword) {
    $conditions[] = "keyword LIKE ?";
    $params[] = "%$keyword%";
}

// Join the conditions with AND to ensure all keywords are matched
$sql .= implode(' AND ', $conditions) . " GROUP BY keyword ORDER BY LENGTH(keyword)";

$stmt = $con->prepare($sql);

// Execute the query with the parameters
$stmt->execute($params);

$results = [];

if ($stmt->rowCount() > 0) {
    // Output data of each row
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = $row["keyword"];
    }
}

echo json_encode($results);
?>
