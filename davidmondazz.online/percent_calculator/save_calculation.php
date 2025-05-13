<?php
// Database connection setup
$db_host = 'localhost';
$db_user = 'mcgkxyz_masterpop';  // Change to your MySQL username
$db_pass = 'aA0109587045';      // Change to your MySQL password
$db_name = 'mcgkxyz_percent_calculator';  // Database name

// Initialize response
$response = ['success' => false];

try {
    // Connect to MySQL database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get input values
    $likes = isset($_POST['likes']) ? $_POST['likes'] : null;
    $views = isset($_POST['views']) ? $_POST['views'] : null;
    
    // Validate inputs
    if (!empty($likes) && !empty($views) && is_numeric($likes) && is_numeric($views) && $views != 0) {
        // Calculate percentage
        $percentage = ($likes / $views) * 100;
        
        // Check for duplicate calculation before saving
        $isDuplicate = false;
        $stmt = $conn->prepare("SELECT * FROM calculations WHERE likes = :likes AND views = :views ORDER BY timestamp DESC LIMIT 1");
        $stmt->bindParam(':likes', $likes);
        $stmt->bindParam(':views', $views);
        $stmt->execute();
        $lastCalc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If we found a record with the same values and it's recent (within last 5 minutes), skip saving
        if ($lastCalc && (time() - $lastCalc['timestamp'] < 300)) {
            $isDuplicate = true;
        }
        
        // Save to database if not a duplicate
        if (!$isDuplicate) {
            $stmt = $conn->prepare("INSERT INTO calculations (likes, views, percentage, timestamp) 
                                  VALUES (:likes, :views, :percentage, :timestamp)");
            $timestamp = time();
            $stmt->bindParam(':likes', $likes);
            $stmt->bindParam(':views', $views);
            $stmt->bindParam(':percentage', $percentage);
            $stmt->bindParam(':timestamp', $timestamp);
            $stmt->execute();
            $response['success'] = true;
        } else {
            // It's a duplicate but we'll consider this a success
            $response['success'] = true;
            $response['duplicate'] = true;
        }
    }
} catch(PDOException $e) {
    $response['error'] = 'Database error';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>