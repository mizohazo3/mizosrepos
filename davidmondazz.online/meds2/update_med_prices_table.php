<?php
include 'db.php';

try {
    // Read the SQL file
    $sql = file_get_contents('update_med_prices_table.sql');
    
    // Execute the SQL
    $con->exec($sql);
    
    echo "Medicine prices table updated successfully with price per dose column!";
} catch(PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>