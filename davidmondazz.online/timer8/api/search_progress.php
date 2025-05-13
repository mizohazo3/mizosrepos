<?php

include_once '../timezone_config.php';
// api/search_progress.php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// In a real implementation, this would track real backend progress
// For now, we'll simulate progress with session variables
session_start();

$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'start':
        // Start a new search process
        $searchTerm = $_GET['term'] ?? '';
        if (empty($searchTerm)) {
            echo json_encode(['status' => 'error', 'message' => 'Search term is required']);
            exit;
        }
        
        // Initialize search progress in session
        $_SESSION['search_progress'] = [
            'term' => $searchTerm,
            'progress' => 0,
            'total' => 100,
            'status' => 'searching',
            'start_time' => microtime(true)
        ];
        
        echo json_encode([
            'status' => 'success', 
            'progress' => 0,
            'message' => 'Search initiated'
        ]);
        break;
        
    case 'check':
        // Return current progress
        if (!isset($_SESSION['search_progress'])) {
            echo json_encode(['status' => 'error', 'message' => 'No search in progress']);
            exit;
        }
        
        // Calculate simulated progress based on time elapsed
        // In a real implementation, this would be based on actual search progress
        $progress = $_SESSION['search_progress'];
        $elapsedTime = microtime(true) - $progress['start_time'];
        
        // Simulate progress: complete in about 2 seconds
        $calculatedProgress = min(100, floor($elapsedTime * 50));
        
        // Update session with new progress
        $_SESSION['search_progress']['progress'] = $calculatedProgress;
        
        // If progress is 100%, mark as complete
        if ($calculatedProgress >= 100) {
            $_SESSION['search_progress']['status'] = 'complete';
        }
        
        echo json_encode([
            'status' => 'success',
            'progress' => $calculatedProgress,
            'search_status' => $_SESSION['search_progress']['status'],
            'term' => $_SESSION['search_progress']['term']
        ]);
        break;
        
    case 'cancel':
        // Cancel the search
        if (isset($_SESSION['search_progress'])) {
            unset($_SESSION['search_progress']);
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Search canceled'
        ]);
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?> 