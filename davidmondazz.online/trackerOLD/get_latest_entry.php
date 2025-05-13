<?php
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    
    // Fetch the most recent entry for this activity
    $latestEntry = $con->prepare("
        SELECT * FROM details 
        WHERE activity_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $latestEntry->execute([$getid]);
    
    if ($row = $latestEntry->fetch(PDO::FETCH_ASSOC)) {
        // Get the date information
        $start_date = $row['start_date'];
        $str = str_replace(',', '', $start_date);
        $day = date('d M Y', strtotime($str));
        $time = date('h:i a', strtotime($str));
        
        // Get the end date information
        $End_date = $row['end_date'];
        $str2 = str_replace(',', '', $End_date);
        $timeEnd = date('h:i a', strtotime($str2));
        
        // Calculate duration
        $duration_seconds = 0;
        if (!empty($row['start_date']) && !empty($row['end_date'])) {
            $start_timestamp = strtotime($row['start_date']);
            $end_timestamp = strtotime($row['end_date']);
            if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp >= $start_timestamp) {
                $duration_seconds = $end_timestamp - $start_timestamp;
            }
        }
        
        // Format notes with links
        $notes = '';
        if (!empty($row['notes'])) {
            preg_match_all("/https?:\/\/\S+/", $row['notes'], $matches);
            $notes_text = $row['notes'];
            foreach ($matches[0] as $link) {
                $notes_text = str_replace($link, "<a href=\"$link\" target=\"_blank\">$link</a>", $notes_text);
            }
            $notes = ' - <img src="img/note.png"> <b style="color:#5b5c5b">' . $notes_text . ' <button style="margin-left:10px;border: 1px solid grey;">
                <a href="show.php?id='.$getid.'&editnotes='.$row['id'].'" style="text-decoration:none;color: inherit;">EditNote</a>
            </button></b>';
        }
        
        // Generate HTML for the new entry
        echo '<tr><td><div style="padding:10px;"><img src="img/dot.png"> From ' . $time . ' To ' . $timeEnd . ' (' . ConvertSeconds($duration_seconds) . ')' . $notes . '<br></div></td></tr>';
    }
}
?> 