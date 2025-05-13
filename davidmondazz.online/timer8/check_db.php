<?php

include_once 'timezone_config.php';
require_once 'api/db.php';

try {
    // Check levels table
    $stmt = $pdo->prepare("SELECT * FROM levels WHERE level = 1");
    $stmt->execute();
    $level = $stmt->fetch();

    if ($level) {
    } else {
        echo "Error: Level 1 not found in the levels table!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 