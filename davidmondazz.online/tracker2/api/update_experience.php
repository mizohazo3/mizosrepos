<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get and validate input data
$timer_id = isset($_POST['timer_id']) ? (int)$_POST['timer_id'] : 0;

if ($timer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid timer ID is required']);
    exit;
}

// Check if the timer exists and is running
$stmt = $conn->prepare("SELECT id, status, level, experience FROM timers WHERE id = ? AND status = 'running'");
$stmt->bind_param("i", $timer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Timer not found or not running']);
    $stmt->close();
    $conn->close();
    exit;
}

$timer = $result->fetch_assoc();
$stmt->close();

// Generate random experience points (1-5 per second)
$exp_gained = rand(1, 5);

// Update the timer's experience
$new_experience = $timer['experience'] + $exp_gained;

// Get the next level's requirements
$stmt = $conn->prepare("
    SELECT level FROM levels_ranks 
    WHERE level > ? 
    ORDER BY level ASC 
    LIMIT 1
");
$stmt->bind_param("i", $timer['level']);
$stmt->execute();
$next_level_result = $stmt->get_result();
$next_level = ($next_level_result->num_rows > 0) ? $next_level_result->fetch_assoc()['level'] : $timer['level'];
$stmt->close();

// Get the experience needed for the next level (use hours directly)
$stmt = $conn->prepare("
    SELECT hours_required AS exp_needed
    FROM levels_ranks 
    WHERE level = ?
");
$stmt->bind_param("i", $next_level);
$stmt->execute();
$exp_result = $stmt->get_result();
$exp_needed = ($exp_result->num_rows > 0) ? $exp_result->fetch_assoc()['exp_needed'] : 0;
$stmt->close();

// Convert experience from seconds to hours for comparison
$new_experience_hours = floor($new_experience / 3600);

// Check if the user should level up
$level_up = false;
$new_level = $timer['level'];

if ($exp_needed > 0 && $new_experience_hours >= $exp_needed && $timer['level'] < 100) {
    $new_level = $next_level;
    $level_up = true;
}

// Update the timer's experience and level
$update_stmt = $conn->prepare("
    UPDATE timers 
    SET experience = ?, 
        level = ?,
        last_xp_update = NOW()
    WHERE id = ?
");
$update_stmt->bind_param("iii", $new_experience, $new_level, $timer_id);
$update_stmt->execute();
$update_stmt->close();

// If level up occurred, log it
if ($level_up) {
    $log_stmt = $conn->prepare("
        INSERT INTO timer_experience_logs 
        (timer_id, experience_gained, total_experience, level_before, level_after) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $level_before = $timer['level'];
    $log_stmt->bind_param("iiiii", $timer_id, $exp_gained, $new_experience, $level_before, $new_level);
    $log_stmt->execute();
    $log_stmt->close();
}

// Get the rank information for the response
if ($level_up) {
    $rank_stmt = $conn->prepare("
        SELECT rank_name, time_format 
        FROM levels_ranks 
        WHERE level = ?
    ");
    $rank_stmt->bind_param("i", $new_level);
    $rank_stmt->execute();
    $rank_result = $rank_stmt->get_result();
    $rank_info = $rank_result->fetch_assoc();
    $rank_stmt->close();
}

// Format the response
$response = [
    'success' => true,
    'message' => 'Experience updated successfully',
    'timer_id' => $timer_id,
    'experience' => $new_experience,
    'level' => $new_level,
    'exp_gained' => $exp_gained,
    'level_up' => $level_up
];

if ($level_up && isset($rank_info)) {
    $response['rank_name'] = $rank_info['rank_name'];
    $response['rank_description'] = $rank_info['time_format'];
}

echo json_encode($response);
$conn->close();
?> 