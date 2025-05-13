<?php
ini_set('display_errors', 1); error_reporting(E_ALL); // Keep for debugging API calls

require_once 'db.php'; // Use require_once

header('Content-Type: application/json'); // Set header early

$input = json_decode(file_get_contents('php://input'), true);
$name = isset($input['name']) ? trim($input['name']) : '';
$response = ['status' => 'error']; // Default response

if (empty($name)) {
    $response['message'] = 'Timer name cannot be empty.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    // 1. Get difficulty multiplier
    $stmt_difficulty = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'difficulty_multiplier'");
    $stmt_difficulty->execute();
    // Default to 1.0 if setting is missing or invalid
    $difficulty_value = $stmt_difficulty->fetchColumn();
    $difficulty_multiplier = (is_numeric($difficulty_value) && $difficulty_value > 0) ? (float)$difficulty_value : 1.0;

    // 2. Insert new timer (defaults to level 1)
    $stmt_insert = $pdo->prepare("INSERT INTO timers (name, current_level) VALUES (:name, 1)");
    $stmt_insert->execute([':name' => $name]);
    $new_id = $pdo->lastInsertId();

    if (!$new_id) {
        throw new Exception("Failed to get last insert ID after adding timer.");
    }

    // 3. Fetch new timer data including BASE level hours
    $stmt_fetch = $pdo->prepare("
        SELECT
            t.id, t.name, t.accumulated_seconds, t.start_time, t.is_running,
            t.current_level,
            l_curr.rank_name,
            l_curr.reward_rate_per_hour,
            l_curr.hours_required AS base_current_level_hours_required,
            l_next.hours_required AS base_next_level_hours_required
        FROM timers t
        JOIN levels l_curr ON t.current_level = l_curr.level
        LEFT JOIN levels l_next ON l_next.level = t.current_level + 1
        WHERE t.id = :id
    ");
    $stmt_fetch->execute([':id' => $new_id]);
    $new_timer_raw = $stmt_fetch->fetch();

    // 4. Adjust hours based on difficulty before returning
    if($new_timer_raw) {
         $new_timer_adjusted = $new_timer_raw;
         // Level 1 always requires 0 effective hours
         $new_timer_adjusted['current_level_hours_required'] = ($new_timer_adjusted['current_level'] == 1)
            ? 0.0
            : round($new_timer_adjusted['base_current_level_hours_required'] * $difficulty_multiplier, 4);

         // Adjust next level if it exists
         if ($new_timer_adjusted['base_next_level_hours_required'] !== null) {
            $new_timer_adjusted['next_level_hours_required'] = round($new_timer_adjusted['base_next_level_hours_required'] * $difficulty_multiplier, 4);
        } else {
             $new_timer_adjusted['next_level_hours_required'] = null; // Keep null if max level
        }
        // Remove base hours from response
        unset($new_timer_adjusted['base_current_level_hours_required']);
        unset($new_timer_adjusted['base_next_level_hours_required']);

        $response = ['status' => 'success', 'timer' => $new_timer_adjusted]; // Return adjusted data
    } else {
        // This case should ideally not happen if insertion succeeded
        throw new Exception("Failed to retrieve newly inserted timer data (ID: $new_id).");
    }

} catch (Exception $e) { // Catch PDO and generic exceptions
    http_response_code(500);
    $response['message'] = 'Failed to add timer: ' . $e->getMessage();
    error_log("add_timer.php Error: " . $e->getMessage()); // Log error server-side
}

echo json_encode($response);
?>