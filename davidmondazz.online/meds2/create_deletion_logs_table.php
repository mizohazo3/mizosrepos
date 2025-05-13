<?php
require 'db.php';

try {
    // Check if table already exists
    $tableCheck = $con->query("SHOW TABLES LIKE 'deletion_logs'");
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, create it
        $createTable = $con->exec("CREATE TABLE `deletion_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user` varchar(50) NOT NULL,
            `record_id` int(11) NOT NULL,
            `medname` varchar(255) NOT NULL,
            `dose_date` varchar(50) NOT NULL,
            `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        echo "deletion_logs table created successfully!";
    } else {
        echo "deletion_logs table already exists.";
    }
} catch (PDOException $e) {
    echo "Error creating deletion_logs table: " . $e->getMessage();
}
?> 