<?php
// Set default timezone
date_default_timezone_set("Africa/Cairo");

// Log file setup
$log_file = __DIR__ . '/timer_sync_log.txt';
$log_handle = fopen($log_file, 'a');

function log_message($message) {
    global $log_handle;
    $timestamp = date('[Y-m-d H:i:s] ');
    fwrite($log_handle, $timestamp . $message . PHP_EOL);
}

log_message("-------- Sync process started --------");
log_message("FIELD MAPPING: accumulated_seconds → time_spent, start_time → last_started");

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
    log_message("Connected to source database ($source_db) successfully");
} catch(PDOException $e) {
    log_message("Connection to source database failed: " . $e->getMessage());
    fclose($log_handle);
    exit;
}

// Connect to tracker database
try {
    $tracker_db = new PDO("mysql:host=$host;dbname=$target_db", $user, $password);
    $tracker_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_message("Connected to target database ($target_db) successfully");
} catch(PDOException $e) {
    log_message("Connection to target database failed: " . $e->getMessage());
    fclose($log_handle);
    exit;
}

// Fetch all timers from source database
try {
    $timer_query = $timer_db->query("SELECT * FROM timers");
    $timers = $timer_query->fetchAll(PDO::FETCH_ASSOC);
    log_message("Retrieved " . count($timers) . " timers from source database");
} catch(PDOException $e) {
    log_message("Error fetching timers: " . $e->getMessage());
    fclose($log_handle);
    exit;
}

// Process each timer and update or insert into activity table
$updated = 0;
$inserted = 0;
$errors = 0;

// Optional: Verify schema to ensure fields exist in activity table
try {
    $schema_check = $tracker_db->query("DESCRIBE activity");
    $fields = $schema_check->fetchAll(PDO::FETCH_COLUMN);
    $required_fields = ['time_spent', 'last_started'];
    
    foreach ($required_fields as $field) {
        if (!in_array($field, $fields)) {
            log_message("WARNING: '$field' field not found in activity table. Sync may fail.");
        } else {
            log_message("Verified: '$field' field exists in activity table");
        }
    }
} catch(PDOException $e) {
    log_message("Warning: Could not verify schema: " . $e->getMessage());
}

foreach ($timers as $timer) {
    try {
        // Check if activity with same name exists
        $check_query = $tracker_db->prepare("SELECT * FROM activity WHERE name = ?");
        $check_query->execute([$timer['name']]);
        $activity = $check_query->fetch(PDO::FETCH_ASSOC);
        
        // Map status from is_running
        $status = ($timer['is_running'] > 0) ? 'on' : 'off';
        
        // Default color code if needed
        $colorCode = rand_color();
        
        // Log current values
        log_message("Processing '{$timer['name']}':");
        log_message("  - accumulated_seconds: {$timer['accumulated_seconds']}");
        log_message("  - start_time: " . ($timer['start_time'] ? $timer['start_time'] : 'NULL'));
        
        if ($activity) {
            // Log current activity values for comparison
            log_message("  Current values in activity table:");
            log_message("  - time_spent: {$activity['time_spent']}");
            log_message("  - last_started: " . ($activity['last_started'] ? $activity['last_started'] : 'NULL'));
            
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
            log_message("Updated activity: {$timer['name']}");
            log_message("  - time_spent ← {$timer['accumulated_seconds']}");
            log_message("  - last_started ← " . ($timer['start_time'] ? $timer['start_time'] : 'NULL'));
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
            log_message("Inserted new activity: {$timer['name']}");
            log_message("  - time_spent ← {$timer['accumulated_seconds']}");
            log_message("  - last_started ← " . ($timer['start_time'] ? $timer['start_time'] : 'NULL'));
        }
        
        // Verify the update/insert
        $verify_query = $tracker_db->prepare("SELECT time_spent, last_started FROM activity WHERE name = ?");
        $verify_query->execute([$timer['name']]);
        $result = $verify_query->fetch(PDO::FETCH_ASSOC);
        
        $time_spent_match = ($result && $result['time_spent'] == $timer['accumulated_seconds']);
        $last_started_match = ($result && $result['last_started'] == $timer['start_time']);
        
        if ($time_spent_match && $last_started_match) {
            log_message("  ✓ Verified: Both fields successfully mapped");
        } else {
            if (!$time_spent_match) {
                log_message("  ⚠ Warning: time_spent ({$result['time_spent']}) does not match accumulated_seconds ({$timer['accumulated_seconds']})");
            }
            if (!$last_started_match) {
                log_message("  ⚠ Warning: last_started ({$result['last_started']}) does not match start_time ({$timer['start_time']})");
            }
        }
        
    } catch(PDOException $e) {
        log_message("Error processing timer '{$timer['name']}': " . $e->getMessage());
        $errors++;
        continue;
    }
}

// Function to generate random color code
function rand_color() {
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

log_message("Sync completed: $updated activities updated, $inserted activities inserted, $errors errors");
log_message("SUMMARY: Successfully mapped fields between databases:");
log_message("  • timers.accumulated_seconds → activity.time_spent");
log_message("  • timers.start_time → activity.last_started");
log_message("-------- Sync process finished --------");

fclose($log_handle);
?> 