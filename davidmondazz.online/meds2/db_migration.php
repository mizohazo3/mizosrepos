<?php
/**
 * Database Migration Script
 * This script will update the database schema to rename columns in the medlist table.
 */

require_once 'db.php';

try {
    // Rename columns in medlist table
    $con->exec("ALTER TABLE medlist CHANGE sent_email email_half VARCHAR(255) DEFAULT NULL");
    $con->exec("ALTER TABLE medlist CHANGE fivehalf_email email_fivehalf VARCHAR(255) DEFAULT NULL");
    
    echo "Database migration completed successfully. Columns renamed to email_half and email_fivehalf.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
