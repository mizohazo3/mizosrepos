<?php

include_once '../timezone_config.php';
// api/search_timers.php

ini_set('display_errors', 1); // Enable errors for debugging
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Adjust path if needed
header('Content-Type: application/json');

// Initialize response
$response = ['status' => 'error', 'message' => 'Invalid request'];

// Get search term from request - accept both 'term' and 'query' parameters
$search_term = isset($_GET['query']) ? trim($_GET['query']) : (isset($_GET['term']) ? trim($_GET['term']) : '');

// Log the search term for debugging
error_log("Search term: " . $search_term);

try {
    // Base query to get timer data with joins for level info and total earned
    $sql = "
        SELECT
            t.id, t.name, t.accumulated_seconds, t.start_time, t.is_running,
            t.current_level, t.notified_level, t.is_pinned, l_curr.rank_name, l_curr.reward_rate_per_hour as hourly_rate,
            COALESCE(tl.total_earned, 0.000000) AS total_earned,
            CASE 
                WHEN t.current_level > 0 THEN (t.accumulated_seconds / (3600 * t.current_level)) * 100
                ELSE 0
            END AS progress_percent
        FROM timers t
        LEFT JOIN levels l_curr ON t.current_level = l_curr.level
        LEFT JOIN (
            SELECT timer_id, SUM(earned_amount) as total_earned
            FROM timer_logs
            GROUP BY timer_id
        ) tl ON t.id = tl.timer_id
    ";
    
    $params = [];
    $where_clauses = [];
    
    // Add WHERE clause if search term is provided
    if (!empty($search_term)) {
        // Check if we should handle multi-word search
        $search_words = preg_split('/\s+/', $search_term);
        
        if (count($search_words) > 1) {
            // Multi-word search
            $wordIndex = 0;
            foreach ($search_words as $word) {
                if (trim($word) === '') continue;
                
                // Add a LIKE clause for each word
                $paramName = ":search_word_$wordIndex";
                $where_clauses[] = "LOWER(t.name) LIKE LOWER($paramName)";
                $params[$paramName] = '%' . strtolower(trim($word)) . '%';
                $wordIndex++;
            }
        } else {
            // Single word search
            $where_clauses[] = "LOWER(t.name) LIKE LOWER(:search_term)";
            $params[':search_term'] = '%' . strtolower($search_term) . '%';
        }
        
        // If search term is numeric, also search by ID
        if (is_numeric($search_term)) {
            $where_clauses[] = "t.id = :search_id";
            $params[':search_id'] = (int)$search_term;
        }
    }
    
    // Combine WHERE clauses if we have any
    if (!empty($where_clauses)) {
        $sql .= " WHERE (" . implode(' AND ', $where_clauses) . ")";
    }
    
    // Add sorting - prioritize running timers, then pinned timers, then by name
    $sql .= " ORDER BY t.is_running DESC, t.is_pinned DESC, t.name ASC";
    
    // Limit results for performance
    $sql .= " LIMIT 50";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Fetch results
    $timers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the SQL and result count for debugging
    error_log("Search SQL: " . $sql);
    error_log("Search params: " . json_encode($params));
    error_log("Result count: " . count($timers));
    
    // Build response
    $response = [
        'status' => 'success',
        'timers' => $timers,
        'count' => count($timers),
        'search_term' => $search_term,
        'search_words' => $search_words ?? [$search_term],
        'sql' => $sql, // Include SQL for debugging
        'params' => $params // Include params for debugging
    ];
    
} catch (Exception $e) {
    // Log error
    error_log("Search error: " . $e->getMessage());
    
    // Return error response
    $response = [
        'status' => 'error',
        'message' => 'Error performing search: ' . $e->getMessage(),
        'search_term' => $search_term
    ];
}

echo json_encode($response);
?>
