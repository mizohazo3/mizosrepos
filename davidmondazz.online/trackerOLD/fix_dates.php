<?php
// Set error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db.php';
$con = getDB();

echo "<h1>Date Format Analysis & Repair</h1>";
echo "<pre>";

// First, let's see if we still have the backup columns
$checkBackup = $con->query("SHOW COLUMNS FROM details LIKE 'start_date_old'");
$hasBackup = $checkBackup->rowCount() > 0;

if (!$hasBackup) {
    // Step 1: Create backup of original columns before making changes
    echo "Creating backup columns...\n";
    $con->exec("ALTER TABLE details 
                ADD COLUMN start_date_old VARCHAR(255) AFTER start_date,
                ADD COLUMN end_date_old VARCHAR(255) AFTER end_date");
    
    // Copy existing data to backup columns
    $con->exec("UPDATE details SET 
                start_date_old = start_date, 
                end_date_old = end_date");
    
    echo "Backup columns created and data copied.\n";
} else {
    echo "Backup columns already exist.\n";
}

// Show sample of original data
$query = "SELECT id, start_date_old, end_date_old FROM details WHERE start_date_old IS NOT NULL LIMIT 10";
$result = $con->query($query);

echo "\n---- Sample of original date formats ----\n";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Start Date: " . $row['start_date_old'] . " | End Date: " . $row['end_date_old'] . "\n";
}

// Based on the sample, we'll create a fix script
echo "\n\nPress the button below to apply the fix with the correct format:\n";
echo "</pre>";

if (isset($_POST['fix'])) {
    echo "<pre>";
    echo "Attempting to fix dates...\n";
    
    try {
        $con->beginTransaction();
        
        // Reset datetime columns to null first to ensure clean conversion
        $con->exec("UPDATE details SET start_date = NULL, end_date = NULL");
        
        // Try multiple date formats to handle different possibilities
        $formats = [
            '%d %b, %Y %h:%i:%s %p',
            '%d %b, %Y %h:%i:%s',
            '%d %b, %Y %H:%i:%s',
            '%d %b, %Y %H:%i',
            '%d %M, %Y %h:%i:%s %p',
            '%d %M, %Y %h:%i:%s',
            '%e %b %Y %H:%i:%s',
            '%Y-%m-%d %H:%i:%s'
        ];
        
        $successCount = 0;
        
        foreach ($formats as $format) {
            echo "Trying format: $format\n";
            
            // Convert start_date
            $stmt = $con->prepare("UPDATE details 
                              SET start_date = STR_TO_DATE(start_date_old, :format)
                              WHERE start_date IS NULL 
                              AND start_date_old IS NOT NULL 
                              AND start_date_old != ''");
            $stmt->bindParam(':format', $format);
            $stmt->execute();
            $startCount = $stmt->rowCount();
            
            // Convert end_date
            $stmt = $con->prepare("UPDATE details 
                              SET end_date = STR_TO_DATE(end_date_old, :format)
                              WHERE end_date IS NULL 
                              AND end_date_old IS NOT NULL 
                              AND end_date_old != ''");
            $stmt->bindParam(':format', $format);
            $stmt->execute();
            $endCount = $stmt->rowCount();
            
            echo "- Format '$format' converted $startCount start dates and $endCount end dates\n";
            $successCount += $startCount + $endCount;
        }
        
        $con->commit();
        echo "\nFixed a total of $successCount date values.\n";
    } catch (Exception $e) {
        $con->rollBack();
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    // Show results after fix
    $afterQuery = "SELECT id, start_date_old, start_date, end_date_old, end_date 
                  FROM details 
                  WHERE start_date_old IS NOT NULL 
                  LIMIT 10";
    $afterResult = $con->query($afterQuery);
    
    echo "\n---- Results after fix attempt ----\n";
    echo "ID | Original Start | New Start | Original End | New End\n";
    echo "------------------------------------------------------\n";
    
    while ($row = $afterResult->fetch(PDO::FETCH_ASSOC)) {
        echo $row['id'] . " | " . 
             $row['start_date_old'] . " | " . 
             $row['start_date'] . " | " . 
             $row['end_date_old'] . " | " . 
             $row['end_date'] . "\n";
    }
    
    echo "</pre>";
} else {
    // Show the fix button
    echo '<form method="post">
            <button type="submit" name="fix" style="padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">
                Fix Dates
            </button>
          </form>';
}
?> 