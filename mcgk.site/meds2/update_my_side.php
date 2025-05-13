
<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$newSide = $_POST['newSide'];
$thisSideName = $_POST['thisSideName'];
$selectedOption = $_POST['selectedOption'];
$thisID = $_POST['id'];

try {
    // Update the value of mySus in the database
    $stmt = $con->prepare("UPDATE side_effects SET keyword = ? WHERE keyword = ?");
    $stmt->execute([$newSide, $thisSideName]);

    $stmt = $con->prepare("UPDATE side_effects SET feelings = ? WHERE id = ?");
    $stmt->execute([$selectedOption, $thisID]);

    // Return the updated value to display on the page
    echo htmlspecialchars($newSide);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
