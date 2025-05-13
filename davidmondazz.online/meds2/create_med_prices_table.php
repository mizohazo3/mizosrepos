<?php
include 'db.php';

try {
    // Read the SQL file
    $sql = file_get_contents('create_med_prices_table.sql');
    
    // Execute the SQL
    $con->exec($sql);
    
    echo "Medicine prices table created successfully!";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>