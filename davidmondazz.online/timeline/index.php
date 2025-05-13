<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include 'trackerDB.php';
include 'medDB.php';
include 'bankDB.php';
include '../func.php';
include '../checkSession.php';


$dateToday = date('d-M-Y');
$starting_date = '28-May-2022';
$diff = strtotime($dateToday) - strtotime($starting_date);
round($diff / (60 * 60 * 24));

$start = new DateTime($starting_date);
$end = (new DateTime($dateToday))->modify('+1 day');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start, $interval, $end);

?>

<html>
    <head>
        <title>System TimeLine</title>
        	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<script src="js/jquery-3.6.0.min.js"></script>
        <style>
        body{
			padding: 30px;
            text-align: center;
		}
        table{
            border-radius:12px;
            vertical-align:top;
            display:inline-block;
            border: 2px solid #717171;
            margin: 10px;
            overflow: hidden;
            border-spacing: 0px;
        }

        table th{
            border: 2px solid #717171;
            padding: 5px;
            background: #717171;
            color: #717171;
            border-left: none;
        }
        
 .live-container {
    display: flex; /* Use flexbox layout */
    align-items: center; /* Align items vertically */
    padding-left: 900px; /* Add padding to the left side */
}

/* Optional: Adjust spacing between elements */
#LiveRefresh {
    margin-right: 10px; /* Adjust margin as needed */
}

        </style>
<script type="text/javascript" src="js/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([

         <?php

$selectCats = $con->query("SELECT * FROM categories");
$counter = 0;
while ($fetch = $selectCats->fetch()) {
    $array[] = $fetch['name'];
    $counter++;
}

echo "['Day', ";

foreach ($array as $key) {
    echo "{label: '$key', type: 'number'},";
}

echo "],";

?>


  <?php

$select = $con->query("SELECT * FROM details group by STR_TO_DATE(start_date, '%d %M, %Y')");
while ($row = $select->fetch()) {
    $st1 = str_replace(',', '', $row['start_date']);
    $Alt_date = date('Y-m-d', strtotime($st1));

    echo "['$Alt_date',";

    for ($i = 0; $i < $counter; $i++) {
        $catname = $array[$i];

        ${'select' . $i} = $con->query("SELECT *,SUM(total_time) as totTime FROM details where cat_name='$catname' and STR_TO_DATE(start_date, '%d %M, %Y')='$Alt_date'  ");
        ${'fetch' . $i} = ${'select' . $i}->fetch();
        echo ${'time_spent' . $i} = round((${'fetch' . $i}['totTime'] / 60) / 60, 2) . ',';
    }

    echo "],";

}

?>

        ]);

        var options = {
          title: 'System Performance',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>

    </head>
    <body>
        <span style=" width: 100%;text-align: center;">Logged as: <b><?=$userLogged; ?></b> <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a> <div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div></span><br><br>

<?php 

$selectCats = $con->query("SELECT * FROM categories");
while($fetch = $selectCats->fetch()){
    echo '<div style="display:inline-block; padding-left:10px; font-size:20px; "><a href="cat_line.php?name='.$fetch['name'].'" target="_blank">'.$fetch['name'].'</a></div> ';
}


?>

<div id="curve_chart" style="width: 1900px; height: 700px"></div>

<?php

$layerCount = count(array_reverse(iterator_to_array($period)));
foreach (array_reverse(iterator_to_array($period)) as $dt) {
    $dayslist = $dt->format("d-m-Y, D");
    $day = $dt->format('Y-m-d');

    /////// Tracker Data
    $getTracker = $con->query("SELECT activity_name, cat_name, SUM(total_time) as tottTime FROM details where STR_TO_DATE(start_date, '%d %M, %Y')='$day' group by activity_name order by id");

    $zIndex = $layerCount--;
    echo '<table>';
    echo '<tr><th><font size="5" style="color:white;"><b> ' . $dayslist . "</b> ($zIndex)</font><br></th></tr>";
    if ($getTracker->rowCount() > 0) {

        $arr = array();
        while ($fetch = $getTracker->fetch()) {
            $totalTime = '['.detailTime($fetch['tottTime']).']';
            $arr[$fetch['cat_name']][] = $fetch['activity_name'] . ' ' . $totalTime . '<br>';
        }

        foreach ($arr as $key => $value) {
            $catsTotalTime = $con->query("SELECT SUM(total_time) as catTime from details where STR_TO_DATE(start_date, '%d %M, %Y')='$day' and cat_name='$key'");
            $cat = $catsTotalTime->fetch();

            echo '<tr><td><b style="color:#959928;text-decoration: underline;">' . $key . ': </b>(<span style="font-size:24px;">' . detailTime($cat['catTime']) . '</span>)<br>';
            foreach ($value as $item) {
                echo $item;
            }
            echo '<br>';
        }

    }

    /////// Med Data
    $getMedData = $con2->query("SELECT * FROM medtrack where STR_TO_DATE(dose_date, '%d %M, %Y')='$day' order by id desc");
    if ($getMedData->rowCount() > 0) {
        echo '<tr><td><br><b style="color:red;">Meds:</b><br>';
    }
    while ($fetch = $getMedData->fetch()) {
        $st1 = str_replace(',', '', $fetch['dose_date']);
        $dose_date = date("h:i a", strtotime($st1));
        echo $fetch['medname'] . ', '.$dose_date.'<br>';
    }

    /////////Bank Data
    $getBank = $con3->query("SELECT * FROM transactions where STR_TO_DATE(thedate, '%d %M, %Y')='$day'");
    if ($getBank->rowCount() > 0) {
        echo '<tr><td><br><b style="color:blue;">Bank:</b><br>';
        while ($fetch = $getBank->fetch()) {
            echo $fetch['storname'] . '<br>';
        }

    }

    echo '<br>';
    echo '</table>';

}

?>

<script>
   // Define the AJAX function
function loadContent() {
    $.ajax({
        url: '../checkWorking.php', // URL of your PHP script
        type: 'GET',
        success: function(data) {
            // Update only the specific parts of your page
            $('#LiveRefresh').html(data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
        }
    });
}

// Call the function immediately when the page loads
loadContent();

// Then call the function every second
setInterval(loadContent, 1000);


var lastData = null; // Variable to store the last received data

function loadNotif() {
    $.ajax({
        url: '../notifications.php',
        type: 'GET',
        success: function(data) {
            // Only update the content if the data has changed
            if (data !== lastData) {
                $('#LiveNotifications').html(data);
                lastData = data; // Update the last received data
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
        }
    });
}

// Call the function immediately when the page loads
loadNotif();

// Then call the function every second
setInterval(loadNotif, 1000);
</script>
    </body>
</html>
