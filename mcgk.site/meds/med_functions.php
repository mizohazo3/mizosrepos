<html>
    <head>
        
        <style>
            
.table-wrapper {
    border-collapse: separate;
    border-radius: 10px;
    overflow: hidden;
}

.highlight {
    background-color: #EAE9E9; /* Use your preferred color for missing data */
    padding: 5px 15px 5px 15px;
    border: 1px solid #ccc;
}

.normal-date {
    background-color: #d3d3d3; /* Use your preferred color for missing data */
    padding: 5px 15px 5px 15px;
}

.dayTitle{
    font-size: 21px;
}
.medTitle{
    font-size: 17px;
}

/* Larger font size for phone screens */
@media screen and (max-width: 768px) {
    .dayTitle{
        font-size: 25px;
    }
    .medTitle{
        font-size: 17px;
    }

}

.hiddenText {
    display: none;
}

        </style>
    </head>
</html>
<?php
date_default_timezone_set("Africa/Cairo");



function output_date_data($date, $con) {
    if(!isset($_GET['showNoMore'])){
$datenow = date("d M, Y h:i a");
    $formatted_date = date("d M Y", strtotime($date));
    $select = $con->query("SELECT * FROM medtrack WHERE STR_TO_DATE(dose_date, '%d %b, %Y') = '$date' order by id desc");

// Check if there are any mednames data for the current date
$has_mednames_data = $select->rowCount() > 0;

// Apply the 'highlight' class if there's no mednames data, otherwise use 'normal-date'
$date_class = !$has_mednames_data ? 'highlight' : 'normal-date';


    if ($select->rowCount() > 0) {

        echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#AC2F20;">' . $formatted_date . ': </b></td></tr></table>';
        while ($fetch = $select->fetch()) {


            $dose_date = $fetch['dose_date'];
            $str = str_replace(',', '', $dose_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $dose_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds = ($timeSecond - $timeFirst);
    
            $timespent = '';
            $daystohrs = '';
    
            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
                $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
            } elseif ($differenceInSeconds >= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
                $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month';
                if ($differenceInSeconds >= 31104000) {
                    $time_spent .= ' =';
                    $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs';
                }
            }
    
            if (!empty($fetch['details'])) {
        
                $details = ', <b>[ ' . $fetch['details'] . ' ]</b>';
                
            } else {
                $details = '';
            }
        
         
            echo (!empty($fetch['medname']) ? '- <font class="medTitle"><b>' . $fetch['medname'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' ago)' . $details . '  <br>' : 'N/A');
        }
    } else {
        echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#1E93C1;">' . $formatted_date . ': </b><br>';
        echo "- <i style='color:#757A6F;'><b>N/A</b></i><br></td></tr></table>";
    }
    echo '<br>';
    }else{
        

 $select = $con->query("SELECT * FROM medlist WHERE nomore='yesFirst' ORDER BY STR_TO_DATE(lastdose, '%d %b, %Y %h:%i %p') DESC");
while ($fetch = $select->fetch()) {


            $dose_date = $fetch['lastdose'];
            $str = str_replace(',', '', $dose_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $datenow = date("d M, Y h:i a");
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $dose_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds = ($timeSecond - $timeFirst);
    
            $timespent = '';
            $daystohrs = '';
    
            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
                $daystohrs = round($differenceInSeconds / 3600, 2) . ' hrs = ';
            } elseif ($differenceInSeconds >= 2592000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days = ';
                $time_spent .= round($differenceInSeconds / 2592000, 2) . ' month';
                if ($differenceInSeconds >= 31104000) {
                    $time_spent .= ' =';
                    $time_spent .= round($differenceInSeconds / 31104000, 2) . ' yrs';
                }
            }
    
            if (!empty($fetch['details'])) {
                $details = ', <b>[ ' . $fetch['details'] . ' ]</b>';
            } else {
                $details = '';
            }
    


            echo '- <font class="medTitle"><b>' . $fetch['name'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' ago)' . $details . ' <br>';
        }
    }

}

?>

