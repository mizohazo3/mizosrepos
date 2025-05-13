<?php
date_default_timezone_set("Africa/Cairo");

// Connect to MySQL
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$database = "mcgkxyz_meds2";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
$response = array('combinations' => '', 'meds_frequency' => array(), 'mutual_meds' => array());

// Get the specific side effect record using the ID parameter
$sql_side = "SELECT * FROM side_effects WHERE id = ?";
$stmt_side = $conn->prepare($sql_side);
$stmt_side->bind_param("i", $id);
$stmt_side->execute();
$result_side = $stmt_side->get_result();

if ($row_side = $result_side->fetch_assoc()) {
    // If no keyword was provided, use the one from the record
    if ($keyword === null) {
        $keyword = $row_side["keyword"];
    }
    
    // Get the side effect date from this specific record
    $side_date_str = str_replace(',', '', $row_side["daytime"]);
    $sideEffectDate = date('Y-m-d h:i:s a', strtotime($side_date_str));
    
    // Get all medications that were in half-life at the time of side effect
    $sql = "SELECT * FROM medtrack ORDER BY id DESC";
    $result_halflife = $conn->query($sql);
    
    $medsInHalfLife = array();
    
    while ($row = $result_halflife->fetch_assoc()) {
        $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
        $dose_date = $row["dose_date"];
        $st3 = str_replace(',', '', $dose_date);
        $med_date = date('Y-m-d h:i:s a', strtotime($st3));
        
        // If medication was taken before the side effect
        if (strtotime($med_date) <= strtotime($sideEffectDate)) {
            // Calculate time difference in hours
            $time_diff_hours = (strtotime($sideEffectDate) - strtotime($med_date)) / 3600;
            
            // Check if medication is still in its half-life (not past it)
            if ($time_diff_hours <= $row["default_half_life"]) {
                // Extract medication name with dose
                $med_name = $row["medname"];
                
                // Extract base name and dose separately
                preg_match('/^(.*?)(\d+\.?\d*)(.*)$/i', $med_name, $matches);
                if (count($matches) >= 3) {
                    $base_name = trim($matches[1]);
                    $dose = $matches[2]; // This captures the decimal value
                    $unit = trim($matches[3]);
                    
                    // Format the medication with dose
                    $med_with_dose = $base_name . ' ' . $dose . $unit;
                    
                    // Add to array if not already present with this exact dose
                    if (!in_array($med_with_dose, $medsInHalfLife)) {
                        $medsInHalfLife[] = $med_with_dose;
                    }
                } else {
                    // If the regex didn't match, just use the full name
                    if (!in_array($med_name, $medsInHalfLife)) {
                        $medsInHalfLife[] = $med_name;
                    }
                }
            }
        }
    }
    
    // Sort the medications alphabetically
    sort($medsInHalfLife);
    
    // Get frequency data for each medication
    $medsFrequency = array();
    foreach ($medsInHalfLife as $med) {
        // Extract base name (without dosage)
        $baseName = preg_replace('/\s*\d+\D*$/', '', $med);
        
        // Get count of side effects with this medication
        $sqlCount = "SELECT COUNT(DISTINCT se.id) as count 
                    FROM side_effects se, medtrack mt 
                    WHERE STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') >= 
                          STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y')
                          AND STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') <= 
                          DATE_ADD(STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y'), 
                                 INTERVAL (mt.default_half_life) HOUR)
                          AND mt.medname LIKE ?";
        
        $stmtCount = $conn->prepare($sqlCount);
        $likeParam = "%" . $baseName . "%";
        $stmtCount->bind_param("s", $likeParam);
        $stmtCount->execute();
        $resultCount = $stmtCount->get_result();
        $rowCount = $resultCount->fetch_assoc();
        
        $medsFrequency[$med] = intval($rowCount['count']);
    }
    
    // Find mutual medications across all instances of this side effect keyword
    $mutualMeds = array();
    
    // First get all other instances of this side effect
    $sql_other_sides = "SELECT id FROM side_effects WHERE keyword = ? AND id != ?";
    $stmt_other = $conn->prepare($sql_other_sides);
    $stmt_other->bind_param("si", $keyword, $id);
    $stmt_other->execute();
    $result_other = $stmt_other->get_result();
    
    $otherSideIds = array();
    while ($row_other = $result_other->fetch_assoc()) {
        $otherSideIds[] = $row_other['id'];
    }
    
    // If there are other instances of the same side effect
    if (count($otherSideIds) > 0) {
        $otherMedCombinations = array();
        
        // For each other instance, get the medications that were active
        foreach ($otherSideIds as $otherId) {
            // Get the side effect date
            $sql_date = "SELECT daytime FROM side_effects WHERE id = ?";
            $stmt_date = $conn->prepare($sql_date);
            $stmt_date->bind_param("i", $otherId);
            $stmt_date->execute();
            $result_date = $stmt_date->get_result();
            $row_date = $result_date->fetch_assoc();
            
            $other_date_str = str_replace(',', '', $row_date["daytime"]);
            $otherDate = date('Y-m-d h:i:s a', strtotime($other_date_str));
            
            // Get medications that were active at this time
            $otherMeds = array();
            $result_halflife->data_seek(0); // Reset the pointer
            
            while ($row = $result_halflife->fetch_assoc()) {
                $half_life_hours = $row["default_half_life"] * 60 * 60;
                $dose_date = $row["dose_date"];
                $st3 = str_replace(',', '', $dose_date);
                $med_date = date('Y-m-d h:i:s a', strtotime($st3));
                
                if (strtotime($med_date) <= strtotime($otherDate)) {
                    $time_diff_hours = (strtotime($otherDate) - strtotime($med_date)) / 3600;
                    
                    if ($time_diff_hours <= $row["default_half_life"]) {
                        $med_name = $row["medname"];
                        
                        preg_match('/^(.*?)(\d+\.?\d*)(.*)$/i', $med_name, $matches);
                        if (count($matches) >= 3) {
                            $base_name = trim($matches[1]);
                            $dose = $matches[2];
                            $unit = trim($matches[3]);
                            $med_with_dose = $base_name . ' ' . $dose . $unit;
                            
                            if (!in_array($med_with_dose, $otherMeds)) {
                                $otherMeds[] = $med_with_dose;
                            }
                        } else {
                            if (!in_array($med_name, $otherMeds)) {
                                $otherMeds[] = $med_name;
                            }
                        }
                    }
                }
            }
            
            $otherMedCombinations[] = $otherMeds;
        }
        
        // Find medications that are present in all combinations (current and other instances)
        $allCombinations = $otherMedCombinations;
        $allCombinations[] = $medsInHalfLife; // Add current combination
        
        // Get base names for comparison
        $baseNameSets = array();
        foreach ($allCombinations as $index => $combo) {
            $baseNameSets[$index] = array();
            foreach ($combo as $med) {
                $baseName = preg_replace('/\s*\d+\D*$/', '', $med);
                $baseNameSets[$index][$baseName][] = $med;
            }
        }
        
        // Find mutual base names across all combinations
        $mutualBaseNames = array();
        if (!empty($baseNameSets)) {
            $firstSet = array_keys($baseNameSets[0]);
            foreach ($firstSet as $baseName) {
                $isInAll = true;
                for ($i = 1; $i < count($baseNameSets); $i++) {
                    if (!array_key_exists($baseName, $baseNameSets[$i])) {
                        $isInAll = false;
                        break;
                    }
                }
                if ($isInAll) {
                    $mutualBaseNames[] = $baseName;
                }
            }
        }
        
        // Mark mutual medications in the current combination
        foreach ($medsInHalfLife as $med) {
            $baseName = preg_replace('/\s*\d+\D*$/', '', $med);
            $response['mutual_meds'][$med] = in_array($baseName, $mutualBaseNames);
        }
    }
    
    // Format the combinations with frequency and mutual information
    if (count($medsInHalfLife) > 1) {
        $combinationsWithFreq = array();
        foreach ($medsInHalfLife as $med) {
            $freq = $medsFrequency[$med];
            $isMutual = isset($response['mutual_meds'][$med]) && $response['mutual_meds'][$med];
            $mutualClass = $isMutual ? ' mutual-highlight' : '';
            $combinationsWithFreq[] = '<span class="med-item' . $mutualClass . '" data-freq="' . $freq . '" data-mutual="' . ($isMutual ? 'true' : 'false') . '">' . $med . '</span>';
        }
        $response['combinations'] = implode(" + ", $combinationsWithFreq);
    } elseif (count($medsInHalfLife) == 1) {
        $med = $medsInHalfLife[0];
        $freq = $medsFrequency[$med];
        $isMutual = isset($response['mutual_meds'][$med]) && $response['mutual_meds'][$med];
        $mutualClass = $isMutual ? ' mutual-highlight' : '';
        $response['combinations'] = '<span class="med-item' . $mutualClass . '" data-freq="' . $freq . '" data-mutual="' . ($isMutual ? 'true' : 'false') . '">' . $med . '</span>';
    }
    
    // Add raw frequency data to response
    $response['meds_frequency'] = $medsFrequency;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close MySQL connection
$conn->close();
?> 