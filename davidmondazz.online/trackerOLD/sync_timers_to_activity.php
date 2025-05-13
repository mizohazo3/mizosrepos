<?php
// Set default timezone
date_default_timezone_set("Africa/Cairo");

// Database connection details
$host = 'localhost';
$user = 'mcgkxyz_masterpop';
$password = 'aA0109587045';

// Source database (timer_app)
$source_db = 'mcgkxyz_timer_app';
$timer_db = null;

// Target database (tracker)
$target_db = 'mcgkxyz_tracker';
$tracker_db = null;

// Connect to timer_app database
try {
    $timer_db = new PDO("mysql:host=$host;dbname=$source_db", $user, $password);
    $timer_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to source database ($source_db) successfully<br>";
} catch(PDOException $e) {
    die("Connection to source database failed: " . $e->getMessage());
}

// Connect to tracker database
try {
    $tracker_db = new PDO("mysql:host=$host;dbname=$target_db", $user, $password);
    $tracker_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to target database ($target_db) successfully<br>";
} catch(PDOException $e) {
    die("Connection to target database failed: " . $e->getMessage());
}

// Fetch all timers from source database
try {
    $timer_query = $timer_db->query("SELECT * FROM timers");
    $timers = $timer_query->fetchAll(PDO::FETCH_ASSOC);
    echo "Retrieved " . count($timers) . " timers from source database<br>";
} catch(PDOException $e) {
    die("Error fetching timers: " . $e->getMessage());
}

// Process each timer and update or insert into activity table
$updated = 0;
$inserted = 0;
$errors = 0;

echo "<h3>Mapping Fields Between Databases</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Timer Name</th><th>accumulated_seconds</th><th>start_time</th><th>Status</th><th>Action</th></tr>";

foreach ($timers as $timer) {
    try {
        // Check if activity with same name exists
        $check_query = $tracker_db->prepare("SELECT * FROM activity WHERE name = ?");
        $check_query->execute([$timer['name']]);
        $activity = $check_query->fetch(PDO::FETCH_ASSOC);
        
        // Map status from is_running
        $status = ($timer['is_running'] > 0) ? 'on' : 'off';
        
        // Current timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Default color code if needed
        $colorCode = rand_color();
        
        // Display mapping information
        echo "<tr>";
        echo "<td>" . htmlspecialchars($timer['name']) . "</td>";
        echo "<td>" . $timer['accumulated_seconds'] . "</td>";
        echo "<td>" . $timer['start_time'] . "</td>";
        echo "<td>" . $status . "</td>";
        
        if ($activity) {
            // Update existing activity
            // IMPORTANT: Mapping fields:
            // - accumulated_seconds → time_spent
            // - start_time → last_started
            $update_query = $tracker_db->prepare(
                "UPDATE activity SET 
                cat_name = ?, 
                status = ?, 
                time_spent = ?, 
                last_started = ? 
                WHERE name = ?"
            );
            
            $update_query->execute([
                $timer['categories'],
                $status,
                $timer['accumulated_seconds'], // Map accumulated_seconds to time_spent
                $timer['start_time'],          // Map start_time to last_started
                $timer['name']
            ]);
            
            $updated++;
            echo "<td>Updated (time_spent = " . $timer['accumulated_seconds'] . ", last_started = " . $timer['start_time'] . ")</td>";
        } else {
            // Insert new activity
            // IMPORTANT: Mapping fields:
            // - accumulated_seconds → time_spent
            // - start_time → last_started
            $insert_query = $tracker_db->prepare(
                "INSERT INTO activity 
                (name, cat_name, status, time_spent, colorCode, added_date, last_started) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            $insert_query->execute([
                $timer['name'],
                $timer['categories'],
                $status,
                $timer['accumulated_seconds'], // Map accumulated_seconds to time_spent
                $colorCode,
                $timer['created_at'],
                $timer['start_time']           // Map start_time to last_started
            ]);
            
            $inserted++;
            echo "<td>Inserted (time_spent = " . $timer['accumulated_seconds'] . ", last_started = " . $timer['start_time'] . ")</td>";
        }
        echo "</tr>";
    } catch(PDOException $e) {
        echo "<tr><td colspan='5'>Error processing timer '" . htmlspecialchars($timer['name']) . "': " . $e->getMessage() . "</td></tr>";
        $errors++;
        continue;
    }
}

echo "</table>";

// Function to generate random color code (from show.php)
function rand_color() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

echo "<br>Sync completed: $updated activities updated, $inserted activities inserted, $errors errors<br>";
echo "Process completed at " . date('Y-m-d H:i:s');
?> 