<?php
date_default_timezone_set("Africa/Cairo");
include 'db.php';
include '../func.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    $select = $con->query("SELECT * FROM activity where id='$getid'");
    $row = $select->fetch();

    $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
    $row2 = $select2->fetch();

    $selectcat = $con->query("SELECT * FROM categories where name='$row[cat_name]'");
    $row3 = $selectcat->fetch();

    $avgDay = $con->query("SELECT COUNT(start_date) as totDay, DATE(start_date) as strdate, id FROM details where activity_id='$getid' group by strdate");

    $TotalDays = 0;
    while ($rw = $avgDay->fetch()) {
        $TotalDays++;
    }

    if (isset($_POST['start']) && $_POST['start'] == 'Start') {
        $dateNow = date('d M, Y h:i:s a');
        $timestamp = date('Y-m-d H:i:s');
        
        $activity_name = $row['name'];
        $cat_name = $row['cat_name'];
        $colorCode = rand_color();
        $starting = $con->prepare("INSERT INTO details (start_date, end_date, total_time, activity_name, cat_name, activity_id, current_status) VALUES (? , ? , ? , ?, ?, ?, ?)");
        $starting->execute([$timestamp, '', '', $activity_name, $cat_name, $getid, 'on']);
        $update = $con->prepare("UPDATE activity set status=?, last_started=?, colorCode=? where id=?");
        $update->execute(['on', $timestamp, $colorCode, $getid]);

    }

    if (isset($_POST['stop']) && $_POST['stop'] == 'Stop') {
        $notes = $_POST['notes'];
        $dateNow = date('d M, Y h:i:s a');
        $timestamp = date('Y-m-d H:i:s');
        
        $dateStarted = $row2['start_date'];
        $start_timestamp = strtotime($dateStarted);
        $end_timestamp = time();
        $differenceInSeconds = $end_timestamp - $start_timestamp;

        $id = $row2['id'];
        $finish = $con->prepare("UPDATE details set end_date=? , total_time=?, current_status=?, notes=? where id=?");
        $finish->execute([$timestamp, $differenceInSeconds, 'off', $notes, $id]);
        
        $update = $con->prepare("UPDATE activity set status=?, `time_spent`=`time_spent`+? where id=?");
        $update->execute(['off', $differenceInSeconds, $getid]);

        $catid = $row2['cat_name'];
        $catupdate = $con->prepare("UPDATE categories set `total_time`=`total_time`+? where name=?");
        $catupdate->execute([$differenceInSeconds, $catid]);

    }

} else {
    header("Location: index.php");
}


$errmsg= '';
if(isset($_POST['add']) && $_POST['add'] == 'Add!'){
    $links = $_POST['addlinks'];
    $getid = $_GET['id'];
    if(empty($links)){
        $errmsg = 'Enter a new link to add!';
    }else{
        $update = $con->prepare("UPDATE activity set links=? where id=?");
        $update->execute([$links, $getid]);
        header("Location: show.php?id=$getid");
    }
}


// Calculate overall total time dynamically
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
// Assign the calculated total to a variable for display later
$overallTotTimeDisplay = $overall_total_seconds;

// Use the calculated total for the average calculation
$AvgperDay = ($TotalDays > 0) ? $overall_total_seconds / $TotalDays : 0;

?>

 <!DOCTYPE html>
 <html>
 <head>
 	<title>Tracking: <?php echo $row['name']; ?></title>
 	 	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
		<script src="js/jquery-3.6.0.min.js"></script>
        <link rel="stylesheet" href="css/fontawesome.css">
 	<style type="text/css">


            form{
			padding-top: 70px;
    		padding-left: 20px;
    		padding-bottom: 35px;
		}

		body{
			padding: 30px;
		}
		.showdetails{
			padding-top: 10px;
		}


        .timertable{
            border-radius:12px;
            vertical-align:top;
            display:inline-block;
            border: 2px solid #717171;
            margin: 10px;
            overflow: hidden;
            border-spacing: 0px;

        }

        .timertable th{
            border: 2px solid #717171;
            padding: 5px;
            background: #717171;
            color: #717171;
            border-left: none;
        }
		.mytable{
			display:inline-block;
			vertical-align:middle;
		}

        .imgCalendar{
            position: relative;
            text-align: center;
            display:inline;
        }
        .imgCalendar img{
            display:inline-block;
            vertical-align:middle;
        }
        .imgCalendar div{
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            color:#24929b;
        }
		#timeCounter{
			position: relative;
		}
		#timeCounter span{
		position: absolute;
        top: -38px;
        right: 0px;
		}
        .locked{
            padding-top: 70px;
    		padding-left: 20px;
    		padding-bottom: 35px;
            font-weight: bold;
            text-align: center;
            font-size: 20px;
        }

        .container_path{

            border:2px black solid;
            margin:5px;
            padding:3px;
            border-radius: 1em;
            background: #ddd;
            padding: 3em;
            border: 2px solid white;
            height: auto;
        }


        .right_path{
            padding-left:20px;
            float:right;
        }

        .flexButtons {
        display: inline-block;
        }

        .flexButtons + .flexButtons {
        margin-left: 3px;
        }



@media only screen and (max-width: 767px) {
    /* apply margin to the second button to create spacing between the buttons */

	.mytable{
			margin-top:10px;
	}

        .right_path{
            padding-top:20px;
        }
}

 .live-container {
    display:flex;
    align-items: center; 
    vertical-align: middle; /* Align vertically in the middle */
    float:left;
    height:40px;
    float:right;
    font-size:11px; important!
}

/* Optional: Adjust spacing between elements */
#LiveRefresh {
    margin-right: 10px; /* Adjust margin as needed */
}


 	</style>
 </head>
 <body>

 <?php
$getid = $_GET['id'];
$check = $con->query("SELECT * FROM activity where id='$getid'");
$rows = $check->fetch();

$statusCheck = '';
$lockButton = '';
if ($rows['manage_status'] == 'lock') {
    $statusCheck = '<b style="color:red;"><img src="img/lock.png"> Locked</b>';
    $lockButton = '<span name="' . $rows['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
} elseif ($rows['manage_status'] == 'lock&special') {
    $statusCheck = '<b style="color:red;"><img src="img/star.png"> Locked&Special</b>';
    $lockButton = '<span name="' . $rows['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
} else {
    $statusCheck = '<b style="color:green;"><img src="img/unlock.png"> Unlocked</b>';

    if ($rows['status'] == 'on') {
        $lockButton = '';
    } else {
        $lockButton = '<table class="mytable">
  <tbody>
    <tr>
      <td><span name="' . $rows['name'] . '"><button class="btn btn-dark btn-sm flexButtons" id="lockButton"><i class="fas fa-lock"></i> Lock</button></span></td>
      <td><span name="' . $rows['name'] . '" ><button class="btn btn-dark btn-sm flexButtons" id="lockspecialButton"><i class="fas fa-star"></i> Lock&Special</button></span></td>
    </tr>
  </tbody>
</table>
 ';
    }

}

if (stripos($row['links'], "f95zone") !== false) {
    $imglink = 'img/f95zone.png';
} else {
    $imglink = 'img/linkicon2.png';
}

if (!empty($row['links'])) {
    $showlinks = '<a href="' . $row['links'] . '" target="_blank"><img src="' . $imglink . '"></a>';

} else {
    if(isset($_GET['addLink'])){
        $showlinks = '<form action="show.php?id='.$_GET['id'].'" method="post" style="display:inline;"><input type="text" name="addlinks"><input type="submit" name="add" value="Add!"></form>';
    }else{
        $showlinks = ' <a href="show.php?id='.$_GET['id'].'&addLink"><font size="1">(AddLink)</font></a>';
    }
}

?>


 <div class="container_path clearfix"><a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a> <a href="index.php" class="btn btn-warning btn-sm"><img src="img/home.png">Home</a> | (<?php echo '<a href="index.php?manage&select_cat=' . $row['cat_name'] . '" style="text-decoration:none;"><font color="' . $row3['colorCode'] . '" style="-webkit-text-stroke: 0.2px #B7B7B7;
  text-stroke: 1px black;font-weight:bold;">' . $row['cat_name'] . '</font></a>'; ?>)-> <b><?php echo '<a href="show.php?id='.$_GET['id'].'" style="color: inherit;">'.$row['name'] . '</a> ' . $showlinks; 
  ?></b><div class="right_path"><em>Activity Hours:</em> <?php echo number_format($row['time_spent'] / 3600, 2); ?> hours<br><i>Total Day: <?php echo $TotalDays; ?> & AvgDay: <?php echo ConvertSeconds($AvgperDay); ?></i><br>Status: <?php echo $statusCheck . ' ' . $lockButton; ?></div></div>


<?php
if ($showId == '23') {
    echo '<br>';
    $TimeSpent = $row['time_spent'];
    echo '<font color="green"><b>Gained:</font><font color="#816a1b"> ' . number_format(vitDAmount('' . $TimeSpent . '')) . ' IU</font> | <font color="#9c4747">LEVEL: ' . SunLevels('' . $TimeSpent . '') . '</font></b>';
    if ($row['status'] == 'on') {
        echo '<br><font color="#185f87"><b>Active:</b></font> +' . (TimerNow($row2['start_date']) * 8.33);
    }

}
?>


<?php

if ($row['status'] == 'off') {

    if ($row['manage_status'] == '') {
        ?>


<span id="<?php echo $row['id']; ?>" name="<?php echo $row['name']; ?>"><button id="startbutton" class="btn btn-primary" style="margin-left:10px;margin-top:60px;">Start</button> </span><br><br>

<?php
} else {
        echo '<div class="locked">LOCKED!</div>';
    }

} else {
    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    ?>


<br><br><br><div style="margin-bottom:20px;"><span id="<?php echo $row['id']; ?>" name="<?php echo $row['start_date']; ?>" style="margin-left:10px;margin-top:80px;margin-bottom:80px;"><button id="stopbutton" class="btn btn-danger">Stop</button><i>Working...</i> <?php 
// Calculate elapsed time using timestamp
$elapsedTime = TimerNow($row2['start_date']);
// Use a special data attribute to store elapsed seconds for JavaScript
echo '<font size="4"> <img src="img/left_arrow.png"><span style="border:2px black solid;margin:5px;padding:3px;"><b id="timerBox" data-start-time="' . time() . '" data-elapsed="' . $elapsedTime . '">' . gmdate("H:i:s", $elapsedTime) . '</b></font></span>'; 
?> <img src="img/right_arrow.png"> <div style="display:inline;padding-left: 20px;"><img src="img/note.png"> <input type="text" id="notesinput" placeholder="Enter notes"> <button id="pasteButton">Paste</button> <button id="enterButton">Submit</button> </div></div>
 </span>


<div class="showdetails">
			<?php
}
$details = $con->query("SELECT * FROM details where activity_id=$getid order by id desc");
if ($details->rowCount() > 0) {
    $arr = array();
    $backColor = '';
    while ($rows = $details->fetch()) {
        // Started date
        $start_date = $rows['start_date'];
        $str = str_replace(',', '', $start_date);
        $day = date('d M Y', strtotime($str));
        $time = date('h:i a', strtotime($str));

        // Ended Date
        $End_date = $rows['end_date'];
        $str2 = str_replace(',', '', $End_date);
        $dayEnd = date('d M Y', strtotime($str2));
        $timeEnd = date('h:i a', strtotime($str2));

        if ($rows['current_status'] == 'on') {
            $arr[$day][] = '<tr><td style="min-width:323px"><div style="padding:10px;background-color:#d33a3a;border:2px solid #116a12;"><b style="color:#ededed;">-> Started at: ' . $time . '</b><br>';
        } else {
            if ($showId == '23') {
                $TimeSpent = $rows['total_time'];
                $approxIU = '~' . number_format(vitDAmount('' . $TimeSpent . '')) . ' IU';} else {
                $approxIU = '';
            }

            if (!empty($rows['notes'])) {
                preg_match_all("/https?:\/\/\S+/", $rows['notes'], $matches);
                
               
                if(isset($_GET['editnotes'])){
                    if($_GET['editnotes'] == $rows['id']){
                        $showNotes = ' - <img src="img/note.png"> <b style="color:#5b5c5b"><body onload="adjustWidth()"><span id="'.$_GET['id'].'" name="'.$_GET['editnotes'].'"><textarea id="myInput" name="editNote" rows="8" cols="50">' . htmlspecialchars($rows['notes']) . '</textarea> <button id="saveButton">Save!</button></span></body></b>';
                    }else{
                        foreach ($matches[0] as $link) {
                            $rows['notes'] = str_replace($link, "<a href=\"$link\" target=\"_blank\">$link</a>", $rows['notes']);
                        }
                        $showNotes = ' - <img src="img/note.png"> <b style="color:#5b5c5b">' . $rows['notes'] . '</b>';
                    }
                }else{
                    foreach ($matches[0] as $link) {
                        $rows['notes'] = str_replace($link, "<a href=\"$link\" target=\"_blank\">$link</a>", $rows['notes']);
                    }
                    $showNotes = ' - <img src="img/note.png"> <b style="color:#5b5c5b">' . $rows['notes'] . ' <button style="margin-left:10px;border: 1px solid grey;">
                    <a href="show.php?id='.$_GET['id'].'&editnotes='.$rows['id'].'" style="text-decoration:none;color: inherit;">EditNote</a>
                </button></b>';
                }

            } else {
                $showNotes = '';
            }

            // Calculate duration dynamically
            $duration_seconds = 0; // Default to 0
            if (!empty($rows['start_date']) && !empty($rows['end_date'])) {
                $start_timestamp = strtotime($rows['start_date']); // Use direct timestamp conversion for DATETIME
                $end_timestamp = strtotime($rows['end_date']);     // Use direct timestamp conversion for DATETIME
                if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp >= $start_timestamp) {
                    $duration_seconds = $end_timestamp - $start_timestamp;
                }
            }
            $arr[$day][] = '<tr><td><div style="padding:10px;"><img src="img/dot.png"> From ' . $time . ' To ' . $timeEnd . ' (' . ConvertSeconds($duration_seconds) . ')' . $approxIU . $showNotes . '<br>';

        }
    }

    $layerCount = count($arr);
    foreach ($arr as $key => $value) {
        $str = date('Y-m-d', strtotime($key)); // Format the date key consistently
        $gId = $_GET['id'];

        // Fetch individual start and end dates for the specific day and activity
        // Assuming start_date and end_date are DATETIME or similar SQL types
        $selectDailyEntries = $con->prepare("
            SELECT start_date, end_date
            FROM details
            WHERE DATE(start_date) = ?
              AND activity_id = ?
              AND (current_status = 'off' OR current_status IS NULL) -- Include NULL status for completed entries
              AND end_date IS NOT NULL
              AND end_date != ''
        ");
        $selectDailyEntries->execute([$str, $gId]);

        $daily_total_seconds = 0;
        // Loop through each entry for the day and sum the calculated duration
        while ($entry = $selectDailyEntries->fetch(PDO::FETCH_ASSOC)) {
             if (!empty($entry['start_date']) && !empty($entry['end_date'])) {
                // Get timestamps directly from DATETIME values without str_replace
                $start_timestamp = strtotime($entry['start_date']);
                $end_timestamp = strtotime($entry['end_date']);
                // Ensure timestamps are valid and end is not before start
                if ($start_timestamp !== false && $end_timestamp !== false && $end_timestamp >= $start_timestamp) {
                    $daily_total_seconds += ($end_timestamp - $start_timestamp);
                }
            }
        }

        $zIndex = $layerCount--;
        
        // Display the day with total time directly in the header
        echo '<table class="timertable">';
        echo '<tr><th><div class="imgCalendar"><img src="img/calendar.png"><div>' . $zIndex . '</div></div>';
        echo '<font size="5" style="color:#00f3ff;font-weight: 650;vertical-align:middle;"> ' . $key . '</font>';
        
        // Display the daily total if greater than zero
        if ($daily_total_seconds > 0) {
            echo '<span style="color:#f0a803;font-weight:bold;margin-left:10px;"> (' . ConvertSeconds($daily_total_seconds) . ')</span>';
        }
        
        echo '</th></tr>';

        foreach ($value as $item) {
            echo $item;
        }

        echo '</div></td></tr></table>';
    }

} else {
    echo 'No data!';
}


?>
</div>
<script>


 $(document).delegate(".FastStart", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Call the AJAX request without page reload
   $.ajax({
    type: 'GET',
    url: 'faststart.php',
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        // Instead of reloading, update the UI dynamically
        var startTime = Math.floor(Date.now() / 1000); // Current timestamp in seconds
        var html = '<br><br><br><div style="margin-bottom:20px;"><span id="' + id + '" name="' + startTime + '" style="margin-left:10px;margin-top:80px;margin-bottom:80px;">' +
                  '<button id="stopbutton" class="btn btn-danger">Stop</button><i>Working...</i> ' +
                  '<font size="4"> <img src="img/left_arrow.png"><span style="border:2px black solid;margin:5px;padding:3px;">' +
                  '<b id="timerBox" data-start-time="' + startTime + '" data-elapsed="0">00:00:00</b></font></span> ' +
                  '<img src="img/right_arrow.png"> ' +
                  '<div style="display:inline;padding-left: 20px;"><img src="img/note.png"> ' +
                  '<input type="text" id="notesinput" placeholder="Enter notes"> ' +
                  '<button id="pasteButton">Paste</button> ' +
                  '<button id="enterButton">Submit</button> </div></div></span>';
        
        // Replace the start button with the timer display
        $('span#' + id).parent().html(html);
        
        // Start the timer
        startTimer();
    }
});
});

 $(document).delegate(".FastStop", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");
    var notes = ""; // No notes for fast stop

    // Call the AJAX request without page reload
   $.ajax({
    type: 'GET',
    url: 'stop.php',
    data: { name: name, id: id, notes: notes },
    beforeSend: function () {},
    success: function (response) {
        // Instead of reloading, update the UI dynamically
        var html = '<span id="' + id + '" name="' + name + '"><button id="startbutton" class="btn btn-primary" style="margin-left:10px;margin-top:60px;">Start</button> </span><br><br>';
        
        // Replace the timer display with the start button
        $('span#' + id).parent().html(html);
        
        // Add the new entry to the activity log without reloading
        $.ajax({
          url: 'get_latest_entry.php',
          type: 'GET',
          data: {id: id},
          success: function(entryData) {
              // Prepend the new entry to the first .timertable
              if(entryData) {
                  $('.showdetails .timertable:first').prepend(entryData);
              }
          }
        });
        
        // Update activity hours and other stats
        updateStats(id);
    }
});
});

// Remove live refresh function
$(document).ready(function () {
  // Update timer every second
  var timerInterval = setInterval(function() {
    var timerBox = $("#timerBox");
    if (timerBox.length) {
      var elapsed = parseInt(timerBox.attr("data-elapsed"));
      elapsed += 1; // Increment by 1 second
      timerBox.attr("data-elapsed", elapsed);
      
      // Format time as H:i:s
      var hours = Math.floor(elapsed / 3600);
      var minutes = Math.floor((elapsed % 3600) / 60);
      var seconds = elapsed % 60;
      
      // Add leading zeros
      var timeString = 
        (hours < 10 ? "0" : "") + hours + ":" +
        (minutes < 10 ? "0" : "") + minutes + ":" +
        (seconds < 10 ? "0" : "") + seconds;
      
      timerBox.text(timeString);
    } else {
      clearInterval(timerInterval); // Stop updating if element is gone
    }
  }, 1000);
});

$(document).on('click', "#unlockButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to Unlock '+ name +' ?'))
        {
            $.ajax({
               url: 'unlock.php',
               type: 'GET',
               data: {name: name},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });

    $(document).on('click', "#lockButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to Lock '+ name +' ?'))
        {
            $.ajax({
               url: 'lock.php',
               type: 'GET',
               data: {name: name},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });

    $(document).on('click', "#lockspecialButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to Lock & Special '+ name +' ?'))
        {
            $.ajax({
               url: 'lock&special.php',
               type: 'GET',
               data: {name: name},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });

    $(document).on('click', "#startbutton",function(){
        var name = $(this).parents("span").attr("name");
        var id = $(this).parents("span").attr("id");

        $.ajax({
           url: 'start.php',
           type: 'GET',
           data: {id: id, name: name},
           error: function() {
              alert('Something is wrong');
           },
           success: function(data) {
              // Instead of reloading, update the UI dynamically
              var startTime = Math.floor(Date.now() / 1000); // Current timestamp in seconds
              var html = '<br><br><br><div style="margin-bottom:20px;"><span id="' + id + '" name="' + startTime + '" style="margin-left:10px;margin-top:80px;margin-bottom:80px;">' +
                        '<button id="stopbutton" class="btn btn-danger">Stop</button><i>Working...</i> ' +
                        '<font size="4"> <img src="img/left_arrow.png"><span style="border:2px black solid;margin:5px;padding:3px;">' +
                        '<b id="timerBox" data-start-time="' + startTime + '" data-elapsed="0">00:00:00</b></font></span> ' +
                        '<img src="img/right_arrow.png"> ' +
                        '<div style="display:inline;padding-left: 20px;"><img src="img/note.png"> ' +
                        '<input type="text" id="notesinput" placeholder="Enter notes"> ' +
                        '<button id="pasteButton">Paste</button> ' +
                        '<button id="enterButton">Submit</button> </div></div></span>';
              
              // Replace the start button with the timer display
              $('span#' + id).parent().html(html);
              
              // Start the timer
              startTimer();
           }
        });
    });

    $(document).on('click', "#stopbutton",function(){
        var name = $(this).parents("span").attr("name");
        var id = $(this).parents("span").attr("id");
        var notes = $("#notesinput").val();

        $.ajax({
           url: 'stop.php',
           type: 'GET',
           data: {id: id, name: name, notes: notes},
           error: function() {
              alert('Something is wrong');
           },
           success: function(data) {
              // Instead of reloading, update the UI dynamically
              var html = '<span id="' + id + '" name="' + name + '"><button id="startbutton" class="btn btn-primary" style="margin-left:10px;margin-top:60px;">Start</button> </span><br><br>';
              
              // Replace the timer display with the start button
              $('span#' + id).parent().html(html);
              
              // Add the new entry to the activity log without reloading
              // You need to retrieve the latest entry details and prepend to the activity log
              $.ajax({
                url: 'get_latest_entry.php',
                type: 'GET',
                data: {id: id},
                success: function(entryData) {
                    // Prepend the new entry to the first .timertable
                    if(entryData) {
                        $('.showdetails .timertable:first').prepend(entryData);
                    }
                }
              });
              
              // Update activity hours and other stats
              updateStats(id);
           }
        });
    });

    // Function to start the timer
    function startTimer() {
      // Update timer every second
      var timerInterval = setInterval(function() {
        var timerBox = $("#timerBox");
        if (timerBox.length) {
          var elapsed = parseInt(timerBox.attr("data-elapsed"));
          elapsed += 1; // Increment by 1 second
          timerBox.attr("data-elapsed", elapsed);
          
          // Format time as H:i:s
          var hours = Math.floor(elapsed / 3600);
          var minutes = Math.floor((elapsed % 3600) / 60);
          var seconds = elapsed % 60;
          
          // Add leading zeros
          var timeString = 
            (hours < 10 ? "0" : "") + hours + ":" +
            (minutes < 10 ? "0" : "") + minutes + ":" +
            (seconds < 10 ? "0" : "") + seconds;
          
          timerBox.text(timeString);
        } else {
          clearInterval(timerInterval); // Stop updating if element is gone
        }
      }, 1000);
    }
    
    // Function to update stats
    function updateStats(id) {
      $.ajax({
        url: 'get_activity_stats.php',
        type: 'GET',
        data: {id: id},
        success: function(stats) {
          if(stats) {
            // Update the activity hours and other stats
            $('.right_path').html(stats);
          }
        }
      });
    }

    $(document).on('keypress', "#notesinput", function(e){
         if (e.which == 13){ // Check if the Enter key was pressed
            $("#stopbutton").click(); // Trigger click event on the stop button
         }
      });

      $("#myInput").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#saveButton").click();
    }
    });

      
// textbox width
function adjustWidth() {
    const textElement = document.getElementById('myInput');
    const textWidth = textElement.scrollWidth + 25; // Add 5 pixels to the width
    textElement.style.width = textWidth + 'px';
    }



// Submit Editnote
$(document).on('click', "#saveButton",function(){
        var id = $(this).parents("span").attr("id"); // page id
        var name = $(this).parents("span").attr("name"); // note id
        var editnote = $("textarea[name='editNote']").val(); // note value

       
            $.ajax({
               url: 'editnotes.php',
               type: 'GET',
               data: {id: id, name: name, editnote: editnote},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
               // Get the current URL
var currentURL = window.location.href;

// Remove the editnotes parameter and its value (editnotes=30)
var newURL = currentURL.replace(/&editnotes=\d+/, '');

// Redirect to the new URL
window.location.href = newURL;
               }
            });
       

    });

// Get references to the buttons and the input field
const pasteButton = document.getElementById("pasteButton");
if (pasteButton) {
  pasteButton.addEventListener("click", function () {
    // Get the copied text from the clipboard
    navigator.clipboard.readText().then(function (clipboardText) {
      // Paste the copied text into the input field
      document.getElementById("notesinput").value = clipboardText;
    });
  });
}

const enterButton = document.getElementById("enterButton");
if (enterButton) {
  enterButton.addEventListener("click", function () {
    // Simulate pressing the Enter key on the input field
    var inputElement = document.getElementById("notesinput");
    if (inputElement) {
      var event = new KeyboardEvent("keypress", {
        key: "Enter",
        keyCode: 13,
        which: 13,
        bubbles: true,
        cancelable: true,
      });
      inputElement.dispatchEvent(event);
    }
  });
}



</script>
 </body>
 </html>