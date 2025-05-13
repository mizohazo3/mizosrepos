<?php
// api/stop_all_timers.php
// API endpoint to stop all running timers

// Disable error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Prevent any output before our JSON
ob_start();

// Include database connection and timezone config
include_once '../timezone_config.php';
require_once 'db.php';

// Set content type
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff'); // Prevent MIME type sniffing

// Check HTTP method - accept both GET and POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Only GET and POST methods are allowed'
    ]);
    exit;
}

try {
    // Clear any output buffer before starting
    ob_clean();
    
    // Start a transaction
    $pdo->beginTransaction();
    
    // Get all running timers
    $stmt = $pdo->prepare("SELECT id, name, start_time, accumulated_seconds, current_level FROM timers WHERE is_running = 1");
    $stmt->execute();
    $runningTimers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Stop each timer and calculate earnings
    $stoppedTimers = [];
    $bankAddition = 0;
    $totalStopped = 0;
    
    // Get reward rates for different levels
    $levelStmt = $pdo->prepare("SELECT level, reward_rate_per_hour FROM levels");
    $levelStmt->execute();
    $levelsData = $levelStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $currentTime = date('Y-m-d H:i:s');
    
    foreach ($runningTimers as $timer) {
        // Calculate earned amount
        $startTime = new DateTime($timer['start_time']);
        $endTime = new DateTime();
        $seconds = $endTime->getTimestamp() - $startTime->getTimestamp();
        
        // Get reward rate for this timer's level
        $rewardRate = isset($levelsData[$timer['current_level']]) ? 
            floatval($levelsData[$timer['current_level']]) : 0;
        
        // Calculate earnings for this session
        $earned = ($seconds / 3600) * $rewardRate;
        $bankAddition += $earned;
        
        // Update timer accumulated seconds and running status
        $newAccumulatedSeconds = floatval($timer['accumulated_seconds']) + $seconds;
        
        $updateStmt = $pdo->prepare("
            UPDATE timers 
            SET is_running = 0, 
                start_time = NULL, 
                accumulated_seconds = :accumulated_seconds,
                last_stopped = :last_stopped
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            ':accumulated_seconds' => $newAccumulatedSeconds,
            ':last_stopped' => $currentTime,
            ':id' => $timer['id']
        ]);
        
        // Log timer stop with earnings
        $logStmt = $pdo->prepare("
            INSERT INTO timer_logs (timer_id, action, action_time, earned_amount)
            VALUES (:timer_id, 'stop', :action_time, :earned_amount)
        ");
        
        $logStmt->execute([
            ':timer_id' => $timer['id'],
            ':action_time' => $currentTime,
            ':earned_amount' => $earned
        ]);
        
        // Check for level up - this is a simplified version. You may need to adjust based on your level system
        $levelUpInfo = checkForLevelUp($pdo, $timer['id'], $newAccumulatedSeconds, $timer['current_level']);
        
        // Add to stopped timers list
        $stoppedTimers[] = [
            'id' => $timer['id'],
            'name' => $timer['name'],
            'accumulated_seconds' => $newAccumulatedSeconds,
            'earned' => $earned,
            'level_up' => $levelUpInfo['level_up'],
            'new_level' => $levelUpInfo['new_level'],
            'new_rank' => $levelUpInfo['new_rank']
        ];
        
        $totalStopped++;
    }
    
    // Update bank balance if needed
    if ($bankAddition > 0) {
        $bankStmt = $pdo->prepare("UPDATE bank SET balance = balance + :amount");
        $bankStmt->execute([':amount' => $bankAddition]);
    }
    
    // Get the current bank balance
    $balanceStmt = $pdo->query("SELECT balance FROM bank LIMIT 1");
    $bankData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    $currentBalance = isset($bankData['balance']) ? floatval($bankData['balance']) : 0;
    
    // Commit the transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'All timers stopped successfully',
        'count' => $totalStopped,
        'stopped_timers' => $stoppedTimers,
        'bank_addition' => $bankAddition,
        'bank_balance' => $currentBalance
    ]);
    
} catch (Exception $e) {
    // Rollback the transaction in case of errors
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log("Stop all timers error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Error stopping timers: ' . $e->getMessage()
    ]);
}

// End output buffering and send response
ob_end_flush();

/**
 * Check if a timer should level up based on accumulated seconds
 * 
 * @param PDO $pdo The database connection
 * @param int $timerId The timer ID
 * @param float $accumulatedSeconds Total accumulated seconds
 * @param int $currentLevel Current timer level
 * @return array Level up info
 */
function checkForLevelUp($pdo, $timerId, $accumulatedSeconds, $currentLevel) {
    $result = [
        'level_up' => false,
        'new_level' => $currentLevel,
        'new_rank' => ''
    ];
    
    try {
        // Get the next level requirements
        $nextLevel = $currentLevel + 1;
        $stmt = $pdo->prepare("SELECT level, hours_required, rank_name FROM levels WHERE level = :level");
        $stmt->execute([':level' => $nextLevel]);
        $nextLevelData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if next level exists and if timer has enough hours
        if ($nextLevelData) {
            $hoursRequired = floatval($nextLevelData['hours_required']);
            $accumulatedHours = $accumulatedSeconds / 3600;
            
            if ($accumulatedHours >= $hoursRequired) {
                // Level up the timer
                $updateStmt = $pdo->prepare("
                    UPDATE timers 
                    SET current_level = :new_level,
                        notified_level = :new_level
                    WHERE id = :id
                ");
                
                $updateStmt->execute([
                    ':new_level' => $nextLevel,
                    ':id' => $timerId
                ]);
                
                // Log the level up
                $logStmt = $pdo->prepare("
                    INSERT INTO timer_logs (timer_id, action, action_time, details)
                    VALUES (:timer_id, 'level_up', NOW(), :details)
                ");
                
                $details = json_encode([
                    'old_level' => $currentLevel,
                    'new_level' => $nextLevel,
                    'rank_name' => $nextLevelData['rank_name']
                ]);
                
                $logStmt->execute([
                    ':timer_id' => $timerId,
                    ':details' => $details
                ]);
                
                // Update result
                $result['level_up'] = true;
                $result['new_level'] = $nextLevel;
                $result['new_rank'] = $nextLevelData['rank_name'];
            }
        }
    } catch (Exception $e) {
        error_log("Level up check error: " . $e->getMessage());
    }
    
    return $result;
} 