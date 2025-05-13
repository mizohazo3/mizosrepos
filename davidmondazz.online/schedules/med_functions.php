<html>
    <head>
        
        <style>


  #sidesText {
    width: 150px; /* Adjust the width as needed */
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    outline: none;
  }
 
            
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

.results {
    border: 1px solid #ccc;
    display: none;
    background-color: white; /* Set the background color to white */
}

.results div {
    padding: 10px;
    cursor: pointer;
    color: black; /* Set the font color to black */
    font-weight: bold; /* Make the font bold */
    
}

.results div:hover {
    background-color: yellow;
}

/* CSS for the modal */
.modal {
  display: none;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border: 1px solid #888;
  max-width: 400px; /* Adjust the max-width as needed */
  width: 100%; /* Ensure the modal content takes the full width of its container */
}



.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}

/* Style for positive option */
.positive {
    color: green;
}

/* Style for negative option */
.negative {
    color: red;
}

/* Style for neutral option */
.neutral {
    color: grey;
}



        </style>
      <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    </head>
</html>
<?php
date_default_timezone_set("Africa/Cairo");



function output_date_data($date, $con, $counter) {
    if(!isset($_GET['showNoMore'])){
$datenow = date("d M, Y h:i a");
    $formatted_date = date("d M Y", strtotime($date));
    $select = $con->query("SELECT * FROM tasktrack WHERE STR_TO_DATE(task_date, '%d %b, %Y') = '$date' order by id desc");

// Check if there are any tasknames data for the current date
$has_tasknames_data = $select->rowCount() > 0;

// Apply the 'highlight' class if there's no tasknames data, otherwise use 'normal-date'
$date_class = !$has_tasknames_data ? 'highlight' : 'normal-date';


    if ($select->rowCount() > 0) {



        echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#AC2F20;"><a href="daypage.php?date='.$formatted_date.'" style="text-decoration: none;color: inherit; /* Remove color */">' . $formatted_date . ':</a> </b>
        </td></tr></table>';
        while ($fetch = $select->fetch()) {


            $task_date = $fetch['task_date'];
            $str = str_replace(',', '', $task_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $task_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds2 = ($timeSecond - $timeFirst);
            
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;
    
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


            $words = explode(" ", $fetch['taskname']);
            // Get the first word
            $firstWord = $words[0];
            echo (!empty($fetch['taskname']) ? '- <font class="medTitle"><b><a href="possible_sides.php?name='.$firstWord.'" style="text-decoration: none;color: inherit;">' . $fetch['taskname'] . '</a></b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' ago)' . $details . ' <br>' : 'N/A');
        }
    } else {


        echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#1E93C1;"><a href="daypage.php?date='.$formatted_date.'" style="text-decoration: none;color: inherit; /* Remove color */">' . $formatted_date . ':</a> </b><br>';
        echo "- <i style='color:#757A6F;'><b>N/A</b></i><br></td></tr></table>";
    }
    echo '<br>';
    }else{
        

 $select = $con->query("SELECT * FROM tasklist WHERE nomore='yesFirst' ORDER BY STR_TO_DATE(lasttask, '%d %b, %Y %h:%i %p') DESC");
while ($fetch = $select->fetch()) {


            $task_date = $fetch['lasttask'];
            $str = str_replace(',', '', $task_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));
    
            $datenow = date("d M, Y h:i a");
            $st1 = str_replace(',', '', $datenow);
            $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));
    
            $dateStarted = $task_date;
            $st2 = str_replace(',', '', $dateStarted);
            $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));
    
            $timeFirst = strtotime('' . $dateStarted2 . '');
            $timeSecond = strtotime('' . $dateNow2 . '');
            $differenceInSeconds2 = ($timeSecond - $timeFirst);
            
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;
    
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
