<?php
// Include database connection
require_once '../includes/db_connect.php';

// Headers for JSON response
header('Content-Type: application/json');

// Get database connection
$conn = getDbConnection();

// Check if category filter is provided
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

// Prepare the SQL query
try {
    if ($category_id) {
        // Filter by specific category
        $sql = "SELECT t.*, c.name as category_name, 
                t.is_sticky, 
                (SELECT COUNT(*) FROM timers WHERE status = 'running') as running_count,
                lr.rank_name, lr.time_format
                FROM timers t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN levels_ranks lr ON t.level = lr.level
                WHERE t.category_id = ? 
                AND (t.manage_status IS NULL OR (t.manage_status != 'lock' AND t.manage_status != 'lock&special'))
                AND (t.status != 'idle' OR t.is_sticky = 1)
                ORDER BY t.is_sticky DESC, t.status = 'idle', t.total_time DESC";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $category_id);
    } else {
        // Get all timers
        $sql = "SELECT t.*, c.name as category_name, 
                t.is_sticky, 
                (SELECT COUNT(*) FROM timers WHERE status = 'running') as running_count,
                lr.rank_name, lr.time_format
                FROM timers t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN levels_ranks lr ON t.level = lr.level
                WHERE (t.manage_status IS NULL OR (t.manage_status != 'lock' AND t.manage_status != 'lock&special'))
                AND (t.status != 'idle' OR t.is_sticky = 1)
                ORDER BY t.is_sticky DESC, t.status = 'idle', t.total_time DESC";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
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
            'running_count' => (int)$row['running_count'],
            'rank_name' => $row['rank_name'],
            'time_format' => $row['time_format'],
            'experience' => isset($row['experience']) ? (int)$row['experience'] : 0,
            'level' => isset($row['level']) ? (int)$row['level'] : 1
        ];
        
        // Calculate current elapsed time for running and paused timers
     // Calculate current elapsed time for running and paused timers
     if ($row['status'] === 'running') {
        // For running timers: accumulated pause_time + (current_time - start_time)
        $start_time_ts = strtotime($row['start_time']);
        $current_time_ts = time(); // Use time() for consistency with typical DB timestamps
        $time_since_start = $current_time_ts - $start_time_ts;
        $elapsed_seconds = (int)$row['pause_time'] + $time_since_start;

        // Ensure we don't have negative values (e.g., clock sync issues)
        $elapsed_seconds = max(0, $elapsed_seconds);

        $timer['current_elapsed'] = $elapsed_seconds;
        // Ensure formatElapsedTime function is available (e.g., via require_once)
        $timer['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);

    } else if ($row['status'] === 'paused') {
        // For paused timers: current_elapsed IS the pause_time
        $elapsed_seconds = (int)$row['pause_time'];
        $timer['current_elapsed'] = $elapsed_seconds;
        // Ensure formatElapsedTime function is available
        $timer['current_elapsed_formatted'] = formatElapsedTime($elapsed_seconds);
    } else {
         // For idle/stopped timers
         $timer['current_elapsed'] = 0;
         $timer['current_elapsed_formatted'] = formatElapsedTime(0);
    }
        
        $timers[] = $timer;
    }
    
    $stmt->close();
    
    // Get category counts
    $categoryCountsSql = "
        SELECT 
            c.id, 
            c.name, 
            SUM(CASE WHEN t.status = 'running' THEN 1 ELSE 0 END) as running_count,
            SUM(CASE WHEN t.status = 'paused' THEN 1 ELSE 0 END) as paused_count,
            SUM(CASE WHEN t.status = 'idle' AND t.is_sticky = 1 THEN 1 ELSE 0 END) as idle_count
        FROM 
            categories c
        LEFT JOIN 
            timers t ON c.id = t.category_id AND (t.is_sticky = 1 OR t.status != 'idle')
        GROUP BY 
            c.id, c.name
        ORDER BY 
            c.name ASC";
    
    $categoryResult = $conn->query($categoryCountsSql);
    
    if ($categoryResult === false) {
        throw new Exception("Failed to get categories: " . $conn->error);
    }
    
    $categories = [];
    
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'running_count' => (int)$row['running_count'],
            'paused_count' => (int)$row['paused_count'],
            'idle_count' => (int)$row['idle_count']
        ];
    }
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'timers' => $timers,
        'categories' => $categories
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