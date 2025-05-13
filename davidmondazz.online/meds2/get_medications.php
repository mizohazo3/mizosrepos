<?php
// Include database connection
include 'db.php';

// Set response header to JSON
header('Content-Type: application/json');

// Get all active medications with their half-life information
try {
    // First, get a list of distinct base medication names (without dosage)
    $baseNamesQuery = $con->query("
        SELECT 
            TRIM(REGEXP_REPLACE(name, '[0-9].*$', '')) as base_name,
            MAX(STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p')) as latest_dose
        FROM 
            medlist 
        WHERE 
            end_date = '' AND 
            default_half_life IS NOT NULL
        GROUP BY 
            TRIM(REGEXP_REPLACE(name, '[0-9].*$', ''))
    ");
    
    $baseNames = [];
    while ($row = $baseNamesQuery->fetch(PDO::FETCH_ASSOC)) {
        $baseNames[$row['base_name']] = $row['latest_dose'];
    }
    
    $medications = [];
    
    // For each base name, get the most recent dose
    foreach ($baseNames as $baseName => $latestDose) {
        $stmt = $con->prepare("
            SELECT 
                id, name, start_date, lastdose, default_half_life, sent_email, fivehalf_email
            FROM 
                medlist
            WHERE 
                end_date = '' AND
                default_half_life IS NOT NULL AND
                TRIM(REGEXP_REPLACE(name, '[0-9].*$', '')) = ? AND
                STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') = ?
            LIMIT 1
        ");
        $stmt->execute([$baseName, $latestDose]);
        
        if ($med = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Add base name for reference
            $med['base_name'] = $baseName;
            $medications[] = $med;
        }
    }
    
    // Return medications as JSON
    echo json_encode($medications);
} 
catch (PDOException $e) {
    // Return error
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 