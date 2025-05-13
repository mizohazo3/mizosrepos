<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

$q = $_REQUEST["q"];
$keywords = explode(' ', $q);

// Array to store results
$medications = array();

// Get all medications that match the search query
$medQuery = "SELECT DISTINCT medname FROM medtrack WHERE ";
$medConditions = array();
$medParams = array();

foreach ($keywords as $keyword) {
    $medConditions[] = "medname LIKE ?";
    $medParams[] = "%$keyword%";
}

$medQuery .= implode(' AND ', $medConditions) . " ORDER BY medname";
$medStmt = $con->prepare($medQuery);
$medStmt->execute($medParams);

// Store all found medications for later reference
$allFoundMedications = array();

// Process each medication and find associated side effects
while ($medRow = $medStmt->fetch(PDO::FETCH_ASSOC)) {
    $med_name = $medRow["medname"];
    
    // Extract the base name of the medication (without dosage)
    $baseName = preg_replace('/\s*\d+\D*$/', '', $med_name);
    $allFoundMedications[] = $baseName;
    
    // Find side effects that occurred when this medication was active
    $sideEffectsQuery = "
    SELECT DISTINCT se.id, se.keyword, se.feelings
    FROM side_effects se, medtrack mt
    WHERE 
        STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') >= 
        STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y')
        AND STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') <= 
        DATE_ADD(STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y'), 
                INTERVAL (mt.default_half_life * 5) HOUR)
        AND mt.medname LIKE ?
    ORDER BY se.feelings, se.keyword
    ";
    
    $sideStmt = $con->prepare($sideEffectsQuery);
    $sideStmt->execute(array("%$baseName%"));
    
    $sideEffects = array();
    while ($sideRow = $sideStmt->fetch(PDO::FETCH_ASSOC)) {
        // For each side effect, get other medications active at the same time
        $comboQuery = "
        SELECT DISTINCT mt.medname
        FROM medtrack mt, side_effects se
        WHERE se.id = ?
        AND STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') >= 
            STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y')
        AND STR_TO_DATE(REPLACE(se.daytime, ',', ''), '%d %M %Y') <= 
            DATE_ADD(STR_TO_DATE(REPLACE(mt.dose_date, ',', ''), '%d %M %Y'), 
                    INTERVAL (mt.default_half_life) HOUR)
        AND mt.medname NOT LIKE ?
        ";
        $comboStmt = $con->prepare($comboQuery);
        $comboStmt->execute(array($sideRow['id'], "%$baseName%"));
        
        $combos = array();
        while ($comboRow = $comboStmt->fetch(PDO::FETCH_ASSOC)) {
            $comboBaseName = preg_replace('/\s*\d+\D*$/', '', $comboRow['medname']);
            $combos[] = array(
                'name' => $comboRow['medname'],
                'base_name' => $comboBaseName,
                'is_mutual' => in_array($comboBaseName, $allFoundMedications)
            );
        }
        
        // Get frequency data for this side effect
        $freqQuery = "
        SELECT COUNT(*) as count
        FROM side_effects 
        WHERE keyword = ?
        ";
        $freqStmt = $con->prepare($freqQuery);
        $freqStmt->execute(array($sideRow['keyword']));
        $freqRow = $freqStmt->fetch(PDO::FETCH_ASSOC);
        $frequency = intval($freqRow['count']);
        
        $sideEffects[] = array(
            'id' => $sideRow['id'],
            'keyword' => $sideRow['keyword'],
            'feelings' => $sideRow['feelings'],
            'frequency' => $frequency,
            'combinations' => $combos
        );
    }
    
    // Count occurrences of this medication
    $countQuery = "
    SELECT COUNT(*) as count
    FROM medtrack
    WHERE medname LIKE ?
    ";
    $countStmt = $con->prepare($countQuery);
    $countStmt->execute(array("%$baseName%"));
    $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
    $medicationCount = intval($countRow['count']);
    
    // Only add medications that have side effects
    if (count($sideEffects) > 0) {
        $medications[] = array(
            'medication' => $med_name,
            'base_name' => $baseName,
            'frequency' => $medicationCount,
            'side_effects' => $sideEffects
        );
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($medications);
?> 