<?php
// Include database connection
require_once 'includes/db_connect.php';

// Get database connection
$conn = getDbConnection();

// Set the title
echo "<h1>Notes from Timer Logs</h1>";

try {
    // Query to fetch all notes from timer_logs that are not empty
    $sql = "SELECT tl.id, t.name as timer_name, tl.start_time, tl.stop_time, tl.note 
            FROM timer_logs tl
            JOIN timers t ON tl.timer_id = t.id
            WHERE tl.note IS NOT NULL AND tl.note != ''
            ORDER BY tl.start_time DESC";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<div style='font-family: monospace; margin: 20px;'>";
        echo "<pre style='background-color: #1e1e2d; color: #e9ecef; padding: 20px; border-radius: 5px;'>";
        echo "ID | Timer Name | Start Time | End Time | Note\n";
        echo str_repeat("-", 100) . "\n";
        
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $timerName = $row['timer_name'];
            $startTime = date('M d, Y g:i A', strtotime($row['start_time']));
            $endTime = $row['stop_time'] ? date('M d, Y g:i A', strtotime($row['stop_time'])) : 'In progress';
            $note = htmlspecialchars($row['note']);
            
            echo sprintf("%-4s | %-20s | %-18s | %-18s | %s\n", 
                $id, 
                substr($timerName, 0, 20), 
                $startTime, 
                $endTime, 
                $note
            );
        }
        
        echo "</pre>";
        echo "</div>";
    } else {
        echo "<p>No notes found in the timer logs.</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Close the connection
$conn->close();
?> 