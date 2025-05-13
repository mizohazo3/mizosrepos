<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$q = $_REQUEST["q"];

$sql = "SELECT keyword FROM side_effects WHERE keyword LIKE ? group by keyword";
$stmt = $con->prepare($sql);
$stmt->execute(["%$q%"]);

$results = [];
if ($stmt->rowCount() > 0) {
  // Output data of each row
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = $row["keyword"];
  }
} 

echo json_encode($results);
?>
