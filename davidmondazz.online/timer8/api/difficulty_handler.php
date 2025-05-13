<?php

include_once '../timezone_config.php';
ini_set('display_errors', 0); // Keep errors hidden from users
ini_set('log_errors', 1);     // Log errors to the server log
error_reporting(E_ALL);

// Unified logging function
function log_message($message, $type = 'INFO', $log_file = null) {
    $timestamp = date('[Y-m-d H:i:s]');
    $formatted_message = sprintf("%s [%s] %s\n", $timestamp, strtoupper($type), $message);
    
    // Always log to PHP error log for critical issues
    if ($type === 'ERROR') {
    }
    
    // If a specific log file is provided, log there too
    if ($log_file) {
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_file, $formatted_message, FILE_APPEND);
    }
}

// --- Debug Logging Setup ---
$diff_log_file = __DIR__ . '/_difficulty_handler_debug.log';
if (!file_exists($diff_log_file) || filesize($diff_log_file) > 1 * 1024 * 1024) {
    file_put_contents($diff_log_file, "=== Difficulty Log Reset: " . date('Y-m-d H:i:s') . " ===\n");
}
function log_diff($message, $log_file) { file_put_contents($log_file, date('[H:i:s] ') . $message . "\n", FILE_APPEND); }
log_diff("\n--- Request Received ---", $diff_log_file);
log_diff("Request Method: " . $_SERVER['REQUEST_METHOD'], $diff_log_file);
// --- End Debug Logging Setup ---


require_once 'db.php';
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid request'];
$action_log_file = __DIR__ . '/../logs/difficulty_handler.log';
$debug_log_file = __DIR__ . '/../logs/difficulty_debug.log';

try {
    // Start request logging
    log_message("=== New Request ===", 'INFO', $debug_log_file);
    log_message("Request Method: {$_SERVER['REQUEST_METHOD']}", 'INFO', $debug_log_file);
    log_message("Remote IP: {$_SERVER['REMOTE_ADDR']}", 'INFO', $debug_log_file);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_difficulty') {
        log_message("Processing GET difficulty request", 'INFO', $debug_log_file);
        
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
        if (!$stmt->execute()) {
            throw new PDOException("Failed to execute difficulty select query");
        }
        
        $value = $stmt->fetchColumn();
        if ($value !== false) {
            log_message("Retrieved difficulty value: $value", 'INFO', $debug_log_file);
            $response = ['status' => 'success', 'multiplier' => $value];
        } else {
            log_message("Difficulty setting not found, creating default", 'INFO', $debug_log_file);
            
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('difficulty_multiplier', '1.0')");
                if (!$stmt->execute()) {
                    throw new PDOException("Failed to insert default difficulty value");
                }
                
                $pdo->commit();
                $response = ['status' => 'success', 'multiplier' => '1.0'];
                log_message("Default difficulty (1.0) created successfully", 'INFO', $action_log_file);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception("Failed to create default difficulty setting: " . $e->getMessage());
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        log_message("Received POST data: " . json_encode($input), 'INFO', $debug_log_file);
        
        $action = $input['action'] ?? null;
        $multiplier = $input['multiplier'] ?? null;
        
        if ($action === 'set_difficulty' && $multiplier !== null) {
            $allowed_multipliers = [
                '0.00012', '0.00028',
                '0.001', '0.005', '0.01', '0.03', '0.05',
                '0.10', '0.25', '0.50', '0.75', '1.0', '1.25', '1.5'
            ];
            
            log_message("Validating multiplier: $multiplier", 'INFO', $debug_log_file);
            
            if (!in_array((string)$multiplier, $allowed_multipliers, true)) {
                log_message("Invalid difficulty value attempted: $multiplier", 'WARNING', $action_log_file);
                http_response_code(400);
                $response['message'] = 'Invalid difficulty value selected.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Check if setting exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'difficulty_multiplier'");
                    if (!$stmt->execute()) {
                        throw new PDOException("Failed to check existing difficulty setting");
                    }
                    
                    $exists = $stmt->fetchColumn() > 0;
                    log_message("Setting exists check: " . ($exists ? 'Yes' : 'No'), 'INFO', $debug_log_file);
                    
                    if ($exists) {
                        $stmt = $pdo->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = 'difficulty_multiplier'");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('difficulty_multiplier', :value)");
                    }
                    
                    $stmt->bindValue(':value', (string)$multiplier, PDO::PARAM_STR);
                    
                    if (!$stmt->execute()) {
                        throw new PDOException("Failed to " . ($exists ? "update" : "insert") . " difficulty value");
                    }
                    
                    $pdo->commit();
                    $response = ['status' => 'success'];
                    log_message("Difficulty updated to: $multiplier", 'INFO', $action_log_file);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw new Exception("Database operation failed: " . $e->getMessage());
                }
            }
        } else {
            http_response_code(400);
            $response['message'] = 'Missing action or multiplier value.';
            log_message("Invalid POST request - missing parameters", 'WARNING', $debug_log_file);
        }
    } else {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        log_message("Invalid request method: {$_SERVER['REQUEST_METHOD']}", 'WARNING', $debug_log_file);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    $response['message'] = 'Database error occurred.';
    log_message("Database error: " . $e->getMessage(), 'ERROR', $action_log_file);
    log_message("Stack trace: " . $e->getTraceAsString(), 'ERROR', $debug_log_file);
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'An unexpected error occurred.';
    log_message("General error: " . $e->getMessage(), 'ERROR', $action_log_file);
    log_message("Stack trace: " . $e->getTraceAsString(), 'ERROR', $debug_log_file);
}

log_message("Response: " . json_encode($response), 'INFO', $debug_log_file);
echo json_encode($response);
?>