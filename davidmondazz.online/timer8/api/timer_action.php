
<?php

include_once '../timezone_config.php';
// api/timer_action.php (FIXED reset_all and stop_all)

ini_set('display_errors', 0); // Keep 0 for production, check logs
error_reporting(E_ALL);
ini_set('log_errors', 1); // Ensure errors are logged

// Logging Setup
$action_log_file = __DIR__ . '/_timer_action_debug.log';
function log_action($message, $filename) {
    if (empty($filename) || !is_string($filename)) {
    }
    file_put_contents($filename, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}
// End Logging Setup

require_once 'db.php'; // Ensure db connection is robust
require_once __DIR__ . '/../includes/level_functions.php'; // For level calculations

// Set header before any potential output/errors
if (!headers_sent()) {
    header('Content-Type: application/json');
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$timer_id = isset($input['id']) ? (int)$input['id'] : null;


$response = ['status' => 'error', 'message' => 'Invalid action or missing data.'];

// Validation
if (!$action || (!in_array($action, ['start', 'stop', 'stop_all', 'reset_all', 'pin', 'toggle_pin'])) || (!in_array($action, ['stop_all', 'reset_all']) && $timer_id === null)) {
    http_response_code(400);
    echo json_encode($response); // Output JSON error
    exit;
}
// End Validation

try {
    $now_dt = new DateTime();
    $now_str = $now_dt->format('Y-m-d H:i:s');
    $response = ['status' => 'success']; // Default success

    // Fetch Global Settings Needed for Stop/Reset/StopAll
    $difficulty_multiplier = 1.0;
    $levels_config_map = [];
    if (in_array($action, ['stop', 'stop_all', 'reset_all'])) {
        // Get difficulty
        $stmt_difficulty = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
        $stmt_difficulty->execute();
        $difficulty_value = $stmt_difficulty->fetchColumn();
        $difficulty_multiplier = (is_numeric($difficulty_value) && $difficulty_value > 0) ? (float)$difficulty_value : 1.0;

        // Get levels config
        $stmt_levels = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");
        if ($stmt_levels === false) throw new Exception("Failed to query level definitions.");
        $levels_data_raw = $stmt_levels->fetchAll(PDO::FETCH_ASSOC);
        if (empty($levels_data_raw)) { throw new Exception("Failed to load level definitions (empty)."); }
        foreach ($levels_data_raw as $level_data) {
            $levels_config_map[$level_data['level']] = $level_data;
        }
    }
    // End Fetch Global Settings

    // Prepare log insert statement (used in stop/stop_all)
    $stmt_insert_log = $pdo->prepare("INSERT INTO timer_logs (timer_id, session_start_time, session_end_time, duration_seconds, earned_amount) VALUES (:timer_id, :start_time, :end_time, :duration, :earned)");


    // --- Action Logic ---

    if ($action === 'start' && $timer_id) {
        // ... (Start logic - verified in previous step, should be OK) ...
        $stmt = $pdo->prepare("UPDATE timers SET is_running = 1, start_time = :now WHERE id = :id AND is_running = 0");
        $utc_now_str = $now_dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'); // Ensure UTC
        $stmt->execute([':now' => $utc_now_str, ':id' => $timer_id]);
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
             $response['start_time'] = $utc_now_str; // Return UTC timestamp string
        } else {
             $stmt_check = $pdo->prepare("SELECT is_running, start_time FROM timers WHERE id = :id");
             $stmt_check->execute([':id' => $timer_id]);
             $current_state = $stmt_check->fetch();
             if ($current_state && $current_state['is_running'] == 1) {
                 $response['status'] = 'warning'; $response['message'] = 'Timer already running.';
                 // Return the existing start_time (assuming it's stored as UTC)
                 $response['start_time'] = $current_state['start_time']; // Use consistent key
             } else {
                 $response['status'] = 'error'; $response['message'] = 'Timer not found or could not be started.';
                 http_response_code(404);
             }
        }

    } elseif ($action === 'stop' && $timer_id) {
        $pdo->beginTransaction();
        $committed = false;
        try {
            // Get timer data first
            $stmt_timer = $pdo->prepare("SELECT id, start_time, accumulated_seconds, current_level FROM timers WHERE id = :id AND is_running = 1 FOR UPDATE");
            $stmt_timer->execute([':id' => $timer_id]);
            $timer = $stmt_timer->fetch(PDO::FETCH_ASSOC);
            
            if (!$timer) {
                throw new Exception("Timer not found or not running.");
            }

            // Calculate elapsed time and rewards (Bank balance is no longer fetched/updated here)
            $startTime = new DateTime($timer['start_time'], new DateTimeZone('UTC')); // Specify UTC timezone
            $elapsedSeconds = max(0, $now_dt->getTimestamp() - $startTime->getTimestamp());
            $newAccumulatedSeconds = (float)$timer['accumulated_seconds'] + $elapsedSeconds;
            $totalHours = $newAccumulatedSeconds / 3600.0;
            $currentLevel = (int)$timer['current_level'];
            
            // Calculate new level
            $newLevel = getLevelForHours($totalHours, $levels_config_map, $difficulty_multiplier);
            $levelChanged = ($newLevel > $currentLevel);
            
            // Calculate reward
            $rewardRate = isset($levels_config_map[$currentLevel]) ? (float)$levels_config_map[$currentLevel]['reward_rate_per_hour'] : 0.0;
            $rewardEarned = ($elapsedSeconds / 3600.0) * $rewardRate;
            
            
            // Update timer
            if ($levelChanged) {
                $stmt_update = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated, current_level = :new_level, notified_level = :notified_level WHERE id = :id");
                $updateParams = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':new_level' => $newLevel,
                    ':notified_level' => $newLevel,
                    ':id' => $timer_id
                ];
            } else {
                $stmt_update = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated WHERE id = :id");
                $updateParams = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':id' => $timer_id
                ];
            }
            
            if (!$stmt_update->execute($updateParams)) {
                $errorInfo = $stmt_update->errorInfo();
                throw new Exception("Failed to update timer state.");
            }
            
            // Convert start_time from UTC to local timezone for logging
            $startTimeLocal = clone $startTime;
            $startTimeLocal->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $startTimeLocalStr = $startTimeLocal->format('Y-m-d H:i:s');
            
            // Log the session
            $logParams = [
                ':timer_id' => $timer_id,
                ':start_time' => $startTimeLocalStr,
                ':end_time' => $now_str,
                ':duration' => $elapsedSeconds,
                ':earned' => $rewardEarned
            ];
            
            if (!$stmt_insert_log->execute($logParams)) {
                $errorInfo = $stmt_insert_log->errorInfo();
                throw new Exception("Failed to log timer session.");
            }
            
            // Commit transaction (Bank balance is no longer updated here)
            if ($pdo->commit()) {
                $committed = true;
                
                // Prepare success response
                $response['accumulated_seconds'] = $newAccumulatedSeconds;
                $response['bank_balance'] = number_format(calculateDynamicBalance($pdo), 2, '.', ''); // Calculate final balance
                if ($levelChanged) {
                    $response['level_up'] = true;
                    $response['new_level'] = $newLevel;
                    $response['new_rank'] = $levels_config_map[$newLevel]['rank_name'] ?? 'Unknown';
                }
            } else {
                throw new Exception("Failed to commit transaction.");
            }
            
        } catch (Exception $e) {
            if ($pdo->inTransaction() && !$committed) {
                $pdo->rollBack();
            }
            throw $e;
        }

    } elseif ($action === 'stop_all') {
        $pdo->beginTransaction();
        $stopped_timers_data = [];
        $total_reward_earned_cycle = 0;
        $final_bank_balance = 0;
        $committed = false;

        try {
            // Select and lock running timers (Bank balance is no longer fetched/updated here)
            $stmt_select = $pdo->query("SELECT id, start_time, accumulated_seconds, current_level FROM timers WHERE is_running = 1 FOR UPDATE");
            if ($stmt_select === false) throw new Exception("Failed to query running timers for stop_all.");
            $running_timers = $stmt_select->fetchAll();

            // Prepare update statements outside the loop
            $stmt_update_timer_no_level = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated WHERE id = :id");
            $stmt_update_timer_level = $pdo->prepare("UPDATE timers SET is_running = 0, start_time = NULL, accumulated_seconds = :accumulated, current_level = :new_level, notified_level = :notified_level WHERE id = :id");

            foreach ($running_timers as $timer) {
                $timer_id_loop = $timer['id'];
                // Calculations
                $startTime = new DateTime($timer['start_time'], new DateTimeZone('UTC')); // Specify UTC timezone
                $elapsedSeconds = max(0, $now_dt->getTimestamp() - $startTime->getTimestamp());
                $newAccumulatedSeconds = (float)$timer['accumulated_seconds'] + $elapsedSeconds;
                $totalHours = $newAccumulatedSeconds / 3600.0;
                $currentLevel = (int)$timer['current_level'];
                $newLevel = getLevelForHours($totalHours, $levels_config_map, $difficulty_multiplier);
                $levelChanged = ($newLevel > $currentLevel);
                $rewardRate = isset($levels_config_map[$currentLevel]) ? (float)$levels_config_map[$currentLevel]['reward_rate_per_hour'] : 0.0;
                $rewardEarned = ($elapsedSeconds / 3600.0) * $rewardRate;
                $total_reward_earned_cycle += $rewardEarned;
                
                // Convert start_time from UTC to local timezone for logging
                $startTimeLocal = clone $startTime;
                $startTimeLocal->setTimezone(new DateTimeZone(date_default_timezone_get()));
                $startTimeLocalStr = $startTimeLocal->format('Y-m-d H:i:s');

                // Timer Update
                $params = [
                    ':accumulated' => $newAccumulatedSeconds,
                    ':id' => $timer_id_loop
                ];

                if ($levelChanged) {
                    $params[':new_level'] = $newLevel;
                    $params[':notified_level'] = $newLevel;
                    $timerUpdateSuccess = $stmt_update_timer_level->execute($params);
                } else {
                    $timerUpdateSuccess = $stmt_update_timer_no_level->execute($params);
                }

                if (!$timerUpdateSuccess) {
                    $errorInfo = $levelChanged ? $stmt_update_timer_level->errorInfo() : $stmt_update_timer_no_level->errorInfo();
                    throw new Exception("Failed to update timer state for ID $timer_id_loop during stop_all.");
                }

                // Log the Session
                $logParams = [
                    ':timer_id' => $timer_id_loop,
                    ':start_time' => $startTimeLocalStr,
                    ':end_time' => $now_str,
                    ':duration' => $elapsedSeconds,
                    ':earned' => $rewardEarned
                ];
                
                if (!$stmt_insert_log->execute($logParams)) {
                    $errorInfo = $stmt_insert_log->errorInfo();
                    throw new Exception("Failed to insert timer log for ID $timer_id_loop.");
                }

                // Store data for response
                $stopped_data = [
                    'id' => $timer_id_loop,
                    'accumulated_seconds' => $newAccumulatedSeconds
                ];
                if ($levelChanged) {
                    $stopped_data['level_up'] = true;
                    $stopped_data['new_level'] = $newLevel;
                    $stopped_data['new_rank'] = $levels_config_map[$newLevel]['rank_name'] ?? 'Unknown';
                }
                $stopped_timers_data[] = $stopped_data;
            }

            // Commit transaction (Bank balance is no longer updated here)
            if ($pdo->commit()) {
                $committed = true;
                $response['stopped_timers'] = $stopped_timers_data;
                $response['bank_balance'] = number_format(calculateDynamicBalance($pdo), 2, '.', ''); // Calculate final balance
                $response['total_reward_earned_cycle'] = number_format($total_reward_earned_cycle, 2, '.', '');
            } else {
                throw new Exception("Failed to commit transaction during stop_all.");
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction() && !$committed) {
                $pdo->rollBack();
            }
            throw $e;
        }

    } elseif ($action === 'reset_all') {
        // --- RESET ALL ---
        $pdo->beginTransaction(); // Start transaction
        $reset_timers_data = [];
        $committed = false; // Commit flag

        try {
            // First, get a count of all timers for validation
            $stmt_count = $pdo->query("SELECT COUNT(*) FROM timers");
            if ($stmt_count === false) {
                $errorInfo = $pdo->errorInfo();
                throw new Exception("Failed to count timers during reset_all. DB Error: " . ($errorInfo[2] ?? 'Unknown'));
           }
           $total_timers = (int)$stmt_count->fetchColumn();

            // Reset all timers to initial state
            $sql_reset_timers = "
                UPDATE timers 
                SET accumulated_seconds = 0,
                    is_running = 0,
                    start_time = NULL,
                    current_level = 1,
                    notified_level = 1
            ";
            $stmt_update = $pdo->prepare($sql_reset_timers);
            
            // Execute the reset
            $updateSuccess = $stmt_update->execute();

            if (!$updateSuccess) {
                $errorInfo = $stmt_update->errorInfo();
                throw new Exception("Failed to reset timers. DB Error: " . ($errorInfo[2] ?? 'Unknown'));
            }

            $rowCount = $stmt_update->rowCount();

            // Clear all timer logs (Bank balance is no longer reset here, it's implicitly 0 after logs are cleared)
            $stmt_clear_logs = $pdo->prepare("DELETE FROM timer_logs");
            $clearLogsSuccess = $stmt_clear_logs->execute();
            
            if (!$clearLogsSuccess) {
                $errorInfo = $stmt_clear_logs->errorInfo();
                $response['warning'] = "Timer logs could not be cleared.";
            }

            // Get all timer IDs for response
            $stmt_select_ids = $pdo->query("SELECT id FROM timers ORDER BY id ASC");
            if ($stmt_select_ids === false) {
                $errorInfo = $pdo->errorInfo();
                throw new Exception("Failed to query timer IDs after reset.");
           }
           
           $all_timer_ids = $stmt_select_ids->fetchAll(PDO::FETCH_COLUMN);
           foreach ($all_timer_ids as $id) {
               $reset_timers_data[] = [
                   'id' => $id,
                   'accumulated_seconds' => 0,
                   'current_level' => 1,
                   'is_running' => 0
               ];
           }
           
           // Commit transaction
           if ($pdo->commit()) {
               $committed = true;
               
               // Prepare success response
               $response['reset_timers'] = $reset_timers_data;
               $response['bank_balance'] = number_format(calculateDynamicBalance($pdo), 2, '.', ''); // Calculate final balance (should be 0)
               
               if (isset($response['warning'])) {
               }
           } else {
               throw new Exception("Failed to commit transaction.");
           }
           
       } catch (Exception $e) {
           if ($pdo->inTransaction() && !$committed) {
               $pdo->rollBack();
           }
           throw $e;
       }

    } elseif ($action === 'pin' && $timer_id) {
        // Extract the pinned status from the request
        $is_pinned = isset($input['is_pinned']) ? (int)$input['is_pinned'] : 0;
        
        // Update the pinned status in the database
        $stmt = $pdo->prepare("UPDATE timers SET is_pinned = :is_pinned WHERE id = :id");
        $stmt->execute([':is_pinned' => $is_pinned, ':id' => $timer_id]);
        $rowCount = $stmt->rowCount();
        
        
        if ($rowCount > 0) {
            $response['status'] = 'success';
            $response['message'] = $is_pinned ? 'Timer pinned successfully.' : 'Timer unpinned successfully.';
            $response['is_pinned'] = $is_pinned;
        } else {
            // If no rows affected, check the current status in DB
            $stmt_check = $pdo->prepare("SELECT is_pinned FROM timers WHERE id = :id");
            $stmt_check->execute([':id' => $timer_id]);
            $current_db_status_raw = $stmt_check->fetchColumn();
            
            if ($current_db_status_raw !== false) { // Timer exists
                $current_db_status = (int)$current_db_status_raw;
                // Check if the DB status *already* matches the requested status
                if ($current_db_status === $is_pinned) {
                    $response['status'] = 'warning';
                    $response['message'] = 'Timer pin status already set to ' . ($is_pinned ? 'pinned' : 'unpinned') . '.';
                    $response['is_pinned'] = $is_pinned; // Return the status anyway
                } else {
                    // This case means timer exists, status is different, but update failed? Should be rare.
                    $response['status'] = 'error';
                    $response['message'] = 'Failed to update pin status despite different values.';
                    http_response_code(500);
                }
            } else { // Timer doesn't exist
                $response['status'] = 'error';
                $response['message'] = 'Timer not found.';
                http_response_code(404);
            }
        }
    } elseif ($action === 'toggle_pin' && $timer_id) {
        
        // Get current pin status
        $stmt = $pdo->prepare("SELECT is_pinned FROM timers WHERE id = :id");
        $stmt->execute([':id' => $timer_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current === false) {
            $response = ['status' => 'error', 'message' => 'Timer not found'];
            http_response_code(404);
        } else {
            // Toggle the pin status
            $new_pin_status = !$current['is_pinned'];
            $stmt = $pdo->prepare("UPDATE timers SET is_pinned = :pin_status WHERE id = :id");
            $result = $stmt->execute([
                ':pin_status' => $new_pin_status ? 1 : 0,
                ':id' => $timer_id
            ]);
            
            if ($result) {
                // Get updated timer data
                $stmt = $pdo->prepare("SELECT * FROM timers WHERE id = :id");
                $stmt->execute([':id' => $timer_id]);
                $timer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $response = [
                    'status' => 'success',
                    'message' => $new_pin_status ? 'Timer pinned successfully' : 'Timer unpinned successfully',
                    'timer' => $timer
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to update pin status'];
                http_response_code(500);
            }
        }
    } else {
         $response = ['status' => 'error', 'message' => 'Unknown action.']; http_response_code(400);
     }

} catch (Exception $e) {
        // Ensure response status reflects the error if not already set
        if ($response['status'] !== 'error' && $response['status'] !== 'warning') {
             $response['status'] = 'error';
        }
         // Add exception message if not already present or more specific
         $response['message'] = 'Action failed: ' . $e->getMessage();
        http_response_code(500); // Set 500 for server errors
    }
    
    // Final Output
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($response);
    exit; // Explicitly exit
   ?>
