<?php
ini_set('session.save_path', '/home/mcgkxyz/mcgk.site/temp');
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';
include '../checkWorking.php';
$datenow = date("d M, Y h:i a");


$date = $_GET['date'];
$next_date = date('Y-m-d', strtotime($date));
$select = $con->query("SELECT * FROM medtrack WHERE STR_TO_DATE(dose_date, '%d %b, %Y') = '$next_date' order by id desc");
$has_mednames_data = $select->rowCount() > 0;
$date_class = !$has_mednames_data ? 'highlight' : 'normal-date';

echo '<table class="table-wrapper"><tr><td class="' . $date_class . '"><b class="dayTitle" style="color:#AC2F20;">' . $date . ':</b></table>';

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



            echo (!empty($fetch['medname']) ? '- <font class="medTitle"><b>' . $fetch['medname'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' ago)' . $details . ' <br>' : 'N/A');
        }


        echo '<bR><br><br>';


        // Connect to MySQL
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$database = "mcgkxyz_meds2";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


        if($next_date == date('Y-m-d')){
     $side_date = date('Y-m-d h:i:s a'); // sides date
    echo '<br><bR>';

    // Select all medications
    $sql = "SELECT * FROM medtrack ORDER BY id DESC";
    $result = $conn->query($sql) or die($con);

    function remainingDrugAmount($initialAmount, $hours, $halfLife) {
        $remainingAmount = $initialAmount * pow(0.5, ($hours / $halfLife));
        return $remainingAmount;
    }

        echo "<b>Medications were still in the system:</b><br>";

        // Loop through each row of the result
        $remainingAmounts = [];
        while ($row = $result->fetch_assoc()) {
            // Calculate date after 5 half-lives
            $half_life_hours = $row["default_half_life"] * 60 * 60; // Convert half-life to seconds
            $dose2 = $row["dose_date"];
            $st3 = str_replace(',', '', $dose2);
            $dose_date = date('Y-m-d h:i:s a', strtotime($st3)); // meds date
            $date_after_5_half_lives = date("Y-m-d h:i:s a", strtotime($st3) + (5 * $half_life_hours));
            

          if(strtotime($dose_date) <= strtotime($side_date)){
              // Compare date after 5 half-lives with current date
              if (strtotime($date_after_5_half_lives) >= strtotime($side_date)) {
                $dose_timestamp = strtotime($side_date);
                $dose_date_timestamp = strtotime($dose_date);
                $time_difference_hours = abs($dose_timestamp - $dose_date_timestamp) / (60 * 60);
                
                $timeDiff = '';
                if(round($time_difference_hours, 2) <= $row["default_half_life"]){
                    $timeDiff = '<font color="green">'.round($time_difference_hours, 2).'</font>';
                }else{
                    $timeDiff = '<font color="red">'.round($time_difference_hours, 2).'</font>';
                }

                preg_match('/\d+/', $row["medname"], $matches);

                if (!empty($matches)) {
                    $initialAmount = intval($matches[0]);
                    // Your code to handle $initialAmount goes here
                    $remainingAmount = remainingDrugAmount($initialAmount, $time_difference_hours, $row["default_half_life"]);
                 
                    // Get the drug name
                $words = explode(" ", $row["medname"]);
                $drugName = $words[0];

                // Add remaining amount to the array for this drug
                if (!isset($remainingAmounts[$drugName])) {
                    $remainingAmounts[$drugName] = 0;
                }
                $remainingAmounts[$drugName] += $remainingAmount;

                } else {
                    // Handle the case where no matches were found
                    echo "No matches found!";
                }

                

                $words = explode(" ", $row["medname"]);
                // Get the first word
                $firstWord = $words[0];
                echo '<a href="possible_sides.php?name='.$firstWord.'" style="text-decoration: none;color: inherit;">'.$row["medname"] .'</a> = '.$timeDiff. " hrs (Half: ".$row["default_half_life"].") (Remain: ".round($remainingAmount,2)." mg)<br>"; // Output medication name
             
            }
          }
      
        }
        echo '<br><br>';
        asort($remainingAmounts);

        foreach ($remainingAmounts as $drugName => $totalRemainingAmount) {
            echo $drugName . " (Total Remain: " . round($totalRemainingAmount, 2) . " mg)<br>";
        }
        }



        if($next_date == date('Y-m-d')){
            $pageTitle = 'Meds Today: '. $date;
        }else{
            $pageTitle = 'Meds: '. $date;
        }



?>


<script>
           // Store the scroll position before navigating away
           function storeScrollPosition() {
            sessionStorage.setItem('scrollPosition', window.scrollY);
        }

        // Restore the scroll position when navigating back
        function restoreScrollPosition() {
            var scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition !== null) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition');
            }
        }

        // Function to navigate back
        function goBack() {
            restoreScrollPosition(); // Restore the scroll position
            window.history.back(); // Navigate back
        }
    </script>
<br><br><br><br><br>
<title><?php echo $pageTitle;?></title>
<body onload="restoreScrollPosition()" onunload="storeScrollPosition()">
    <button onclick="goBack()"><img src="img/back.png"></button>
</body>