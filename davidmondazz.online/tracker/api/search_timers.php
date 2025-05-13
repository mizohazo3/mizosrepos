<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Get database connection
$conn = getDbConnection();

// Get search term
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search_term)) {
    echo json_encode([
        'success' => false,
        'error' => 'Search term is required'
    ]);
    exit;
}

// Prepare the SQL query to search ALL timers, including idle ones
try {
    // Split search term into individual words/characters
    $search_words = preg_split('/\s+/', $search_term);
    
    // Start building SQL query
    $sql = "
        SELECT t.*, c.name as category_name, 
        lr.rank_name, lr.time_format
        FROM timers t 
        INNER JOIN categories c ON t.category_id = c.id
        LEFT JOIN levels_ranks lr ON t.level = lr.level
        WHERE 1=1 
        AND (t.manage_status IS NULL 
            OR t.manage_status != 'lock' 
            AND t.manage_status != 'lock&special')
    ";
    
    // Add conditions for each word
    $params = [];
    $types = "";
    
    foreach ($search_words as $index => $word) {
        $sql .= " AND t.name LIKE ?";
        $params[] = "%" . $word . "%";
        $types .= "s";
    }
    
    // Add sorting
    $sql .= "
        ORDER BY t.status = 'running' DESC, 
                 t.status = 'paused' DESC, 
                 t.total_time DESC
    ";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Bind parameters dynamically
    if (!empty($params)) {
        $bind_params = array($types);
        foreach($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    }

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $timers = [];
    
    // Process each timer
    while ($row = $result->fetch_assoc()) {
        $timer = [
            'id' => $row['id'],
            'name' => $row['name'],
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'status' => $row['status'],
            'manage_status' => $row['manage_status'],
            'is_sticky' => (bool)$row['is_sticky'],
            'total_time' => (int)$row['total_time'],
            'total_time_formatted' => formatTime((int)$row['total_time']),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'experience' => (int)$row['experience'],
            'level' => (int)$row['level'],
            'rank_name' => $row['rank_name'],
            'time_format' => $row['time_format']
        ];
        
        // Calculate current elapsed time for running and paused timers
        if ($row['status'] === 'running') {
            $start_time_ts = strtotime($row['start_time']);
            $current_time_ts = time();
            $time_since_start = $current_time_ts - $start_time_ts;
            $elapsed_seconds = (int)$row['pause_time'] + $time_since_start;
            $elapsed_seconds = max(0, $elapsed_seconds);
            
            $timer['current_elapsed'] = $elapsed_seconds;
            $timer['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);
        } else if ($row['status'] === 'paused') {
            $elapsed_seconds = (int)$row['pause_time'];
            $timer['current_elapsed'] = $elapsed_seconds;
            $timer['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);
        } else {
            // For idle/stopped timers
            $timer['current_elapsed'] = 0;
            $timer['current_elapsed_formatted'] = formatElapsedTime(0);
        }
        
        $timers[] = $timer;
    }
    
    $stmt->close();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'timers' => $timers,
        'search_term' => $search_term
    ]);

} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn)) {
        $conn->close();
    }
}
?> 