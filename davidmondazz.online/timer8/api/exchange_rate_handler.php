<?php
// exchange_rate_handler.php - Handles getting and setting USD to EGP exchange rate

// Include database connection
require_once 'db.php';

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

// Function to return standardized JSON response
function sendResponse($status, $message = '', $data = []) {
    $response = ['status' => $status];
    if (!empty($message)) $response['message'] = $message;
    if (!empty($data)) $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

try {
    // Check if user_progress table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_progress'");
    if ($stmt->rowCount() == 0) {
        // Create user_progress table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_progress` (
                `id` int(10) UNSIGNED NOT NULL DEFAULT 1,
                `bank_balance` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `USDEGP` decimal(10,2) NOT NULL DEFAULT 50.70,
                PRIMARY KEY (`id`)
            )
        ");
        
        // Insert default record
        $pdo->exec("INSERT INTO user_progress (id, bank_balance, USDEGP) VALUES (1, 0.0000, 50.70)");
    } else {
        // Check if USDEGP column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM user_progress LIKE 'USDEGP'");
        if ($stmt->rowCount() == 0) {
            // Add USDEGP column if it doesn't exist
            $pdo->exec("ALTER TABLE user_progress ADD COLUMN USDEGP DECIMAL(10,2) NOT NULL DEFAULT 50.70");
        }
    }
    
    // Get action from request
    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
    
    switch ($action) {
        case 'get_rate':
            // Fetch current exchange rate
            $stmt = $pdo->query("SELECT USDEGP FROM user_progress WHERE id = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                sendResponse('success', '', ['rate' => (float)$result['USDEGP']]);
            } else {
                // Return default if not found
                sendResponse('success', '', ['rate' => 50.70]);
            }
            break;
            
        case 'set_rate':
            // Validate rate parameter
            if (!isset($_POST['rate'])) {
                sendResponse('error', 'Missing rate parameter');
            }
            
            $rate = (float)$_POST['rate'];
            if ($rate <= 0) {
                sendResponse('error', 'Exchange rate must be greater than zero');
            }
            
            // Update exchange rate in database
            $stmt = $pdo->prepare("UPDATE user_progress SET USDEGP = ? WHERE id = 1");
            $success = $stmt->execute([$rate]);
            
            if ($success && $stmt->rowCount() > 0) {
                sendResponse('success', 'Exchange rate updated successfully');
            } else {
                // Try inserting if update failed (no rows affected)
                $stmt = $pdo->prepare("INSERT INTO user_progress (id, USDEGP) VALUES (1, ?) ON DUPLICATE KEY UPDATE USDEGP = VALUES(USDEGP)");
                $success = $stmt->execute([$rate]);
                
                if ($success) {
                    sendResponse('success', 'Exchange rate saved successfully');
                } else {
                    sendResponse('error', 'Failed to save exchange rate');
                }
            }
            break;
            
        default:
            sendResponse('error', 'Invalid action');
            break;
    }
} catch (PDOException $e) {
    // Log error and return error response
    error_log('Exchange rate handler error: ' . $e->getMessage());
    sendResponse('error', 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    // Log error and return error response
    error_log('Exchange rate handler error: ' . $e->getMessage());
    sendResponse('error', 'An error occurred: ' . $e->getMessage());
}
?> 