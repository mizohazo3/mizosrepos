<?php
require_once '../includes/db.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check for required action parameter
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];
$response = ['success' => false, 'message' => 'Unknown action'];

$conn = getDbConnection();

switch ($action) {
    case 'add_timer':
        // Required parameters: name, category_id
        if (!isset($_POST['name']) || !isset($_POST['category_id'])) {
            $response = ['success' => false, 'message' => 'Missing required parameters'];
            break;
        }
        
        $name = $_POST['name'];
        $category_id = intval($_POST['category_id']);
        
        $stmt = $conn->prepare("INSERT INTO timers (name, category_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $category_id);
        
        if ($stmt->execute()) {
            $timer_id = $conn->insert_id;
            
            // Get the newly created timer data
            $stmt = $conn->prepare("SELECT t.*, c.name as category_name FROM timers t 
                                   JOIN categories c ON t.category_id = c.id 
                                   WHERE t.id = ?");
            $stmt->bind_param("i", $timer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $timer = $result->fetch_assoc();
            
            $response = [
                'success' => true, 
                'message' => 'Timer added successfully',
                'timer' => $timer
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to add timer: ' . $conn->error];
        }
        break;
        
    case 'delete_timer':
        // Required parameter: timer_id
        if (!isset($_POST['timer_id'])) {
            $response = ['success' => false, 'message' => 'Missing timer_id parameter'];
            break;
        }
        
        $timer_id = intval($_POST['timer_id']);
        
        $stmt = $conn->prepare("DELETE FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Timer deleted successfully'];
        } else {
            $response = ['success' => false, 'message' => 'Failed to delete timer: ' . $conn->error];
        }
        break;
        
    case 'start_timer':
        // Required parameter: timer_id
        if (!isset($_POST['timer_id'])) {
            $response = ['success' => false, 'message' => 'Missing timer_id parameter'];
            break;
        }
        
        $timer_id = intval($_POST['timer_id']);
        $now = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("UPDATE timers SET status = 'running', start_time = ?, last_paused_time = NULL WHERE id = ?");
        $stmt->bind_param("si", $now, $timer_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => 'Timer started successfully',
                'start_time' => $now
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to start timer: ' . $conn->error];
        }
        break;
        
    case 'pause_timer':
        // Required parameter: timer_id
        if (!isset($_POST['timer_id'])) {
            $response = ['success' => false, 'message' => 'Missing timer_id parameter'];
            break;
        }
        
        $timer_id = intval($_POST['timer_id']);
        $now = date('Y-m-d H:i:s');
        
        // Get the current timer data
        $stmt = $conn->prepare("SELECT * FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timer = $result->fetch_assoc();
        
        if (!$timer) {
            $response = ['success' => false, 'message' => 'Timer not found'];
            break;
        }
        
        // Calculate elapsed time since start_time
        $start_time = new DateTime($timer['start_time']);
        $current_time = new DateTime($now);
        $interval = $start_time->diff($current_time);
        $elapsed_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        
        // Update timer status and store elapsed time
        $stmt = $conn->prepare("UPDATE timers SET status = 'paused', last_paused_time = ? WHERE id = ?");
        $stmt->bind_param("si", $now, $timer_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => 'Timer paused successfully',
                'elapsed_seconds' => $elapsed_seconds,
                'pause_time' => $now
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to pause timer: ' . $conn->error];
        }
        break;
        
    case 'resume_timer':
        // Required parameter: timer_id
        if (!isset($_POST['timer_id'])) {
            $response = ['success' => false, 'message' => 'Missing timer_id parameter'];
            break;
        }
        
        $timer_id = intval($_POST['timer_id']);
        $now = date('Y-m-d H:i:s');
        
        // Get the current timer data
        $stmt = $conn->prepare("SELECT * FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timer = $result->fetch_assoc();
        
        if (!$timer || $timer['status'] !== 'paused') {
            $response = ['success' => false, 'message' => 'Timer not found or not paused'];
            break;
        }
        
        // Calculate paused duration
        $pause_time = new DateTime($timer['last_paused_time']);
        $current_time = new DateTime($now);
        $interval = $pause_time->diff($current_time);
        $paused_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        
        // Add to total_paused_duration
        $total_paused = $timer['total_paused_duration'] + $paused_seconds;
        
        // Update timer status, reset last_paused_time, and update total_paused_duration
        $stmt = $conn->prepare("UPDATE timers SET status = 'running', last_paused_time = NULL, total_paused_duration = ? WHERE id = ?");
        $stmt->bind_param("ii", $total_paused, $timer_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => 'Timer resumed successfully',
                'total_paused_duration' => $total_paused,
                'resume_time' => $now
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to resume timer: ' . $conn->error];
        }
        break;
        
    case 'stop_timer':
        // Required parameter: timer_id
        if (!isset($_POST['timer_id'])) {
            $response = ['success' => false, 'message' => 'Missing timer_id parameter'];
            break;
        }
        
        $timer_id = intval($_POST['timer_id']);
        $now = date('Y-m-d H:i:s');
        
        // Get the current timer data
        $stmt = $conn->prepare("SELECT * FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timer = $result->fetch_assoc();
        
        if (!$timer) {
            $response = ['success' => false, 'message' => 'Timer not found'];
            break;
        }
        
        $session_duration = 0;
        
        if ($timer['status'] === 'running') {
            // Calculate elapsed time since start_time
            $start_time = new DateTime($timer['start_time']);
            $current_time = new DateTime($now);
            $interval = $start_time->diff($current_time);
            $elapsed_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
            
            // Subtract paused duration if any
            $session_duration = $elapsed_seconds - $timer['total_paused_duration'];
        }
        else if ($timer['status'] === 'paused') {
            // For paused timers, calculate elapsed time up to the pause point
            if ($timer['start_time'] && $timer['last_paused_time']) {
                $start_time = new DateTime($timer['start_time']);
                $pause_time = new DateTime($timer['last_paused_time']);
                $interval = $start_time->diff($pause_time);
                $elapsed_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
                
                // Subtract total_paused_duration
                $session_duration = $elapsed_seconds - $timer['total_paused_duration'];
            }
        }
        
        // Update total_elapsed_time by adding session_duration
        $total_elapsed = $timer['total_elapsed_time'] + $session_duration;
        
        // Update timer status, reset fields
        $stmt = $conn->prepare("UPDATE timers SET status = 'stopped', start_time = NULL, 
                               last_paused_time = NULL, total_paused_duration = 0, 
                               total_elapsed_time = ? WHERE id = ?");
        $stmt->bind_param("ii", $total_elapsed, $timer_id);
        
        if ($stmt->execute()) {
            $response = [
                'success' => true, 
                'message' => 'Timer stopped successfully',
                'session_duration' => $session_duration,
                'total_elapsed_time' => $total_elapsed
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to stop timer: ' . $conn->error];
        }
        break;
        
    case 'get_timers':
        // Optional parameter: category_id
        $category_filter = "";
        $params = [];
        $types = "";
        
        // Only apply category filter if category_id is set and greater than 0
        if (isset($_POST['category_id']) && intval($_POST['category_id']) > 0) {
            $category_id = intval($_POST['category_id']);
            $category_filter = "WHERE t.category_id = ?";
            $params[] = $category_id;
            $types = "i";
        }
        
        $query = "SELECT t.*, c.name as category_name FROM timers t 
                 JOIN categories c ON t.category_id = c.id 
                 $category_filter
                 ORDER BY t.id DESC";
                 
        $stmt = $conn->prepare($query);
        
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $timers = [];
        
        while ($row = $result->fetch_assoc()) {
            // Calculate current elapsed time for running timers
            if ($row['status'] === 'running' && $row['start_time']) {
                $start_time = new DateTime($row['start_time']);
                $current_time = new DateTime();
                $interval = $start_time->diff($current_time);
                $elapsed_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
                
                // Subtract total_paused_duration
                $current_elapsed = $elapsed_seconds - $row['total_paused_duration'];
                $row['current_elapsed'] = $current_elapsed;
            } else if ($row['status'] === 'paused') {
                // For paused timers, calculate elapsed time up to the pause point
                if ($row['start_time'] && $row['last_paused_time']) {
                    $start_time = new DateTime($row['start_time']);
                    $pause_time = new DateTime($row['last_paused_time']);
                    $interval = $start_time->diff($pause_time);
                    $elapsed_seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
                    
                    // Subtract total_paused_duration
                    $current_elapsed = $elapsed_seconds - $row['total_paused_duration'];
                    $row['current_elapsed'] = $current_elapsed;
                }
            } else {
                $row['current_elapsed'] = 0;
            }
            
            $timers[] = $row;
        }
        
        $response = [
            'success' => true,
            'timers' => $timers
        ];
        break;
        
    case 'get_categories':
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $response = [
            'success' => true,
            'categories' => $categories
        ];
        break;
        
    case 'add_category':
        // Required parameter: name
        if (!isset($_POST['name'])) {
            $response = ['success' => false, 'message' => 'Missing name parameter'];
            break;
        }
        
        $name = trim($_POST['name']);
        
        // Check if the name is not empty
        if (empty($name)) {
            $response = ['success' => false, 'message' => 'Category name cannot be empty'];
            break;
        }
        
        // Check if category already exists
        $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $response = ['success' => false, 'message' => 'Category already exists'];
            break;
        }
        
        // Insert new category
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        
        if ($stmt->execute()) {
            $category_id = $conn->insert_id;
            
            $response = [
                'success' => true,
                'message' => 'Category added successfully',
                'category' => [
                    'id' => $category_id,
                    'name' => $name
                ]
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to add category: ' . $conn->error];
        }
        break;
        
    case 'save_preference':
        // Required parameters: preference_key, preference_value
        if (!isset($_POST['preference_key']) || !isset($_POST['preference_value'])) {
            $response = ['success' => false, 'message' => 'Missing required parameters'];
            break;
        }
        
        $preference_key = $_POST['preference_key'];
        $preference_value = $_POST['preference_value'];
        
        // Check if the preference exists
        $stmt = $conn->prepare("SELECT * FROM user_preferences WHERE preference_key = ?");
        $stmt->bind_param("s", $preference_key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing preference
            $stmt = $conn->prepare("UPDATE user_preferences SET preference_value = ? WHERE preference_key = ?");
            $stmt->bind_param("ss", $preference_value, $preference_key);
        } else {
            // Insert new preference
            $stmt = $conn->prepare("INSERT INTO user_preferences (preference_key, preference_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $preference_key, $preference_value);
        }
        
        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Preference saved successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to save preference: ' . $conn->error
            ];
        }
        break;
        
    case 'get_preferences':
        // Get all user preferences
        $stmt = $conn->prepare("SELECT preference_key, preference_value FROM user_preferences");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $preferences = [];
        while ($row = $result->fetch_assoc()) {
            $preferences[$row['preference_key']] = $row['preference_value'];
        }
        
        $response = [
            'success' => true,
            'preferences' => $preferences
        ];
        break;
}

$conn->close();
echo json_encode($response); 