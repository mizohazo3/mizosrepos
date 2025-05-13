<?php
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    
    // Fetch activity data
    $select = $con->query("SELECT * FROM activity where id='$getid'");
    $row = $select->fetch();
    
    // Fetch category data
    $selectcat = $con->query("SELECT * FROM categories where name='$row[cat_name]'");
    $row3 = $selectcat->fetch();
    
    // Calculate total days
    $avgDay = $con->query("SELECT COUNT(start_date) as totDay, DATE(start_date) as strdate, id FROM details where activity_id='$getid' group by strdate");
    $TotalDays = 0;
    while ($rw = $avgDay->fetch()) {
        $TotalDays++;
    }
    
    // Calculate overall total time
    $selectEntries = $con->prepare("
        SELECT start_date, end_date
        FROM details
        WHERE activity_id = ?
          AND current_status = 'off'
          AND end_date IS NOT NULL
          AND end_date != ''
    ");
    $selectEntries->execute([$getid]);
    
    $overall_total_seconds = 0;
    while ($entry = $selectEntries->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($entry['start_date']) && !empty($entry['end_date'])) {
            $start_timestamp = strtotime($entry['start_date']);
            $end_timestamp = strtotime($entry['end_date']);
            if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp >= $start_timestamp) {
                $overall_total_seconds += ($end_timestamp - $start_timestamp);
            }
        }
    }
    
    // Calculate average per day
    $AvgperDay = ($TotalDays > 0) ? $overall_total_seconds / $TotalDays : 0;
    
    // Get lock status
    $statusCheck = '';
    $lockButton = '';
    if ($row['manage_status'] == 'lock') {
        $statusCheck = '<b style="color:red;"><img src="img/lock.png"> Locked</b>';
        $lockButton = '<span name="' . $row['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
    } elseif ($row['manage_status'] == 'lock&special') {
        $statusCheck = '<b style="color:red;"><img src="img/star.png"> Locked&Special</b>';
        $lockButton = '<span name="' . $row['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
    } else {
        $statusCheck = '<b style="color:green;"><img src="img/unlock.png"> Unlocked</b>';
        
        if ($row['status'] == 'on') {
            $lockButton = '';
        } else {
            $lockButton = '<table class="mytable">
            <tbody>
              <tr>
                <td><span name="' . $row['name'] . '"><button class="btn btn-dark btn-sm flexButtons" id="lockButton"><i class="fas fa-lock"></i> Lock</button></span></td>
                <td><span name="' . $row['name'] . '" ><button class="btn btn-dark btn-sm flexButtons" id="lockspecialButton"><i class="fas fa-star"></i> Lock&Special</button></span></td>
              </tr>
            </tbody>
          </table>';
        }
    }
    
    // Generate HTML for stats
    echo '<em>Activity Hours:</em> ' . number_format($row['time_spent'] / 3600, 2) . ' hours<br>';
    echo '<i>Total Day: ' . $TotalDays . ' & AvgDay: ' . ConvertSeconds($AvgperDay) . '</i><br>';
    echo 'Status: ' . $statusCheck . ' ' . $lockButton;
}
?> 