<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include 'db.php';
include $_SERVER['DOCUMENT_ROOT'] . '/func.php';
// Get the path of the current script
$scriptPath = $_SERVER['SCRIPT_NAME']; // e.g., /trackerOLD/show.php

// Get the directory name of the script path
$baseDirectory = dirname($scriptPath); // e.g., /trackerOLD

// Ensure the base directory ends with a slash if it's not the root
// And handle the case where the script is in the root directory
if ($baseDirectory === '/' || $baseDirectory === '\\') {
    // If script is in the root, base directory is just '/'
    $baseDirectory = '/';
} elseif (substr($baseDirectory, -1) !== '/') {
    // Otherwise, ensure it ends with a slash
    $baseDirectory .= '/'; // e.g., /trackerOLD/
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Construct the base URL including the directory
$baseURL = $protocol . "://" . $host . $baseDirectory; // e.g., https://davidmondazz.online/trackerOLD/

?>

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;
$baseImgPath = $mainDomainURL . $baseDirectory;

// Helper function to fix image paths
function imgPath($imgName) {
    global $baseImgPath;
    return $baseImgPath . 'img/' . $imgName;
}

// Replace all instances of src="img/ with src="' . imgPath(' in the output buffer

if (isset($_GET['id'])) {
    $getid = $_GET['id'];
    $select = $con->query("SELECT * FROM activity where id='$getid'");
    $row = $select->fetch();

    $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
    $row2 = $select2->fetch();

    $selectcat = $con->query("SELECT * FROM categories where name='$row[cat_name]'");
    $row3 = $selectcat->fetch();

    $avgDay = $con->query("SELECT COUNT(id) as totDay, DATE(start_date) as strdate, id FROM details where activity_id='$getid' group by DATE(start_date)");

    $TotalDays = 0;
    while ($rw = $avgDay->fetch()) {
        $TotalDays++;
    }

    if (isset($_POST['start']) && $_POST['start'] == 'Start') {
        $dateNow = date('Y-m-d H:i:s');
        $activity_name = $row['name'];
        $cat_name = $row['cat_name'];
        $colorCode = rand_color();
        $starting = $con->prepare("INSERT INTO details (start_date, end_date, total_time, activity_name, cat_name, activity_id, current_status) VALUES (? , ? , ? , ?, ?, ?, ?)");
        $starting->execute([$dateNow, NULL, '', $activity_name, $cat_name, $getid, 'on']);
        $update = $con->prepare("UPDATE activity set status=?, last_started=?, colorCode=? where id=?");
        $update->execute(['on', $dateNow, $colorCode, $getid]);

    }

    if (isset($_POST['stop']) && $_POST['stop'] == 'Stop') {
        $notes = $_POST['notes'];
        $dateNow = date('Y-m-d H:i:s');

        $dateStarted = $row2['start_date'];
        
        $timeFirst = strtotime($dateStarted);
        $timeSecond = strtotime($dateNow);
        $differenceInSeconds = ($timeSecond - $timeFirst);

        $id = $row2['id'];
        $finish = $con->prepare("UPDATE details set end_date=? , total_time=?, current_status=?, notes=? where id=?");
        $finish->execute([$dateNow, $differenceInSeconds, 'off', $notes, $id]);
        
        

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


$selectTotal = $con->query("SELECT SUM(total_time) as totTime FROM details where activity_id='$getid'");
$rws = $selectTotal->fetch();

$AvgperDay = $rws['totTime'] / $TotalDays;

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
 <body class="realtime();">

 <?php
$getid = $_GET['id'];
$check = $con->query("SELECT * FROM activity where id='$getid'");
$rows = $check->fetch();

$statusCheck = '';
$lockButton = '';
if ($rows['manage_status'] == 'lock') {
    $statusCheck = '<b style="color:red;"><img src="' . $baseImgPath . '/img/lock.png"> Locked</b>';
    $lockButton = '<span name="' . $rows['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
} elseif ($rows['manage_status'] == 'lock&special') {
    $statusCheck = '<b style="color:red;"><img src="' . $baseImgPath . '/img/star.png"> Locked&Special</b>';
    $lockButton = '<span name="' . $rows['name'] . '" ><button class="btn btn-primary btn-sm" id="unlockButton">Unlock</button></span>';
} else {
    $statusCheck = '<b style="color:green;"><img src="' . $baseImgPath . '/img/unlock.png"> Unlocked</b>';

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
    $imglink = $baseImgPath . '/img/linkicon2.png';
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
  ?></b><div class="right_path"><em>Total Time:</em> <?php echo detailTime($rws['totTime']); ?><br><i>Total Day: <?php echo $TotalDays; ?> & AvgDay: <?php echo ConvertSeconds($AvgperDay); ?></i><br>Status: <?php echo $statusCheck . ' ' . $lockButton; ?> <div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div></div></div>


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


<br><br><br><div style="margin-bottom:20px;"><span id="<?php echo $row['id']; ?>" name="<?php echo $row['start_date']; ?>" style="margin-left:10px;margin-top:80px;margin-bottom:80px;"><button id="stopbutton" class="btn btn-danger">Stop</button><i>Working...</i> <?php echo '<font size="4"> <img src="' . $baseImgPath . '/img/left_arrow.png"><span style="border:2px black solid;margin:5px;padding:3px;"><b id="timerBox">' . gmdate("H:i:s", TimerNow($row2['start_date'])) . '</b></font></span>'; ?> <img src="<?php echo $baseImgPath; ?>/img/right_arrow.png"> <a href="<?php echo $actual_link; ?>" class="btn btn-info">Refresh</a><div style="display:inline;padding-left: 20px;"><img src="<?php echo $baseImgPath; ?>/img/note.png"> <input type="text" id="notesinput" placeholder="Enter notes"> <button id="pasteButton">Paste</button> <button id="enterButton">Submit</button> </div></div>
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
                        $showNotes = ' - <img src="' . $baseImgPath . '/img/note.png"> <b style="color:#5b5c5b"><body onload="adjustWidth()"><span id="'.$_GET['id'].'" name="'.$_GET['editnotes'].'"><textarea id="myInput" name="editNote" rows="8" cols="50">' . htmlspecialchars($rows['notes']) . '</textarea> <button id="saveButton">Save!</button></span></body></b>';
                    }else{
                        foreach ($matches[0] as $link) {
                            $rows['notes'] = str_replace($link, "<a href=\"$link\" target=\"_blank\">$link</a>", $rows['notes']);
                        }
                        $showNotes = ' - <img src="' . $baseImgPath . '/img/note.png"> <b style="color:#5b5c5b">' . $rows['notes'] . '</b>';
                    }
                }else{
                    foreach ($matches[0] as $link) {
                        $rows['notes'] = str_replace($link, "<a href=\"$link\" target=\"_blank\">$link</a>", $rows['notes']);
                    }
                    $showNotes = ' - <img src="' . $baseImgPath . '/img/note.png"> <b style="color:#5b5c5b">' . $rows['notes'] . ' <button style="margin-left:10px;border: 1px solid grey;">
                    <a href="show.php?id='.$_GET['id'].'&editnotes='.$rows['id'].'" style="text-decoration:none;color: inherit;">EditNote</a>
                </button></b>';
                }

            } else {
                $showNotes = '';
            }

            $arr[$day][] = '<tr><td><div style="padding:10px;"><img src="' . $baseImgPath . '/img/dot.png"> From ' . $time . ' To ' . $timeEnd . ' (' . ConvertSeconds($rows['total_time']) . ')' . $approxIU . $showNotes . '<br>';

        }

    }

    $layerCount = count($arr);
    foreach ($arr as $key => $value) {
        $str = date('Y-m-d', strtotime($key));
        $gId = $_GET['id'];
        $selectTotal = $con->query("SELECT COUNT(*) as totDay,SUM(total_time) as totTime,start_date FROM details where STR_TO_DATE(start_date, '%d %M, %Y')='$str' and activity_id='$gId' and current_status='off'");
        $rws = $selectTotal->fetch();
        $tottime = $rws['totTime'];
        $timeColor = '#f0a803';
        if ($tottime <= 59) {
            $TimeConv = ' <div id="timeCounter"><span style="color:' . $timeColor . ';border: 2px solid ' . $timeColor . ';border-radius:20px;padding:6px 6px;"> ' . $tottime . ' sec </span></div>';
            if (empty($tottime)) {
                $TimeConv = '';
            }
        } elseif ($tottime < 3600) {
            $TimeConv = ' <div id="timeCounter"><span style="color:' . $timeColor . ';border: 2px solid ' . $timeColor . ';border-radius:20px;padding:6px 6px;"> ' . round(($tottime / 60), 2) . ' min </span></div>';
        } elseif ($tottime >= 3600) {
            $TimeConv = ' <div id="timeCounter"><span style="color:' . $timeColor . ';border: 2px solid ' . $timeColor . ';border-radius:20px;padding:6px 6px;"> ' . round(($tottime / 3600), 2) . ' hrs </span></div>';
        }

        $zIndex = $layerCount--;

        echo '<table class="timertable">';
        echo '<tr><th><div class="imgCalendar"><img src="' . $baseImgPath . '/img/calendar.png"><div>' . $zIndex . '</div></div><font size="5" style="color:#00f3ff;font-weight: 650;vertical-align:middle;"> ' . $key . ':</font>' . $TimeConv . '</th></tr>';

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

    // Directly call the AJAX request without showing the confirmation
   $.ajax({
    type: 'GET',
    url: '<?php echo $mainDomainURL . $baseDirectory; ?>faststart.php', // Absolute URL based on main domain
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        location.reload();
    }
});
});

 $(document).delegate(".FastStop", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Directly call the AJAX request without showing the confirmation
   $.ajax({
    type: 'GET',
    url: '<?php echo $mainDomainURL . $baseDirectory; ?>stop.php', // Absolute URL based on main domain
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        location.reload();
    }
});
});


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
        url: '<?php echo $mainDomainURL;?>/notifications.php',
        type: 'GET',
        success: function(data) {
            // Only update the content if the data has changed
            if (data !== lastData) {
                // Temporarily store the current hovered status of notifications
                var hoveredNotifications = document.querySelectorAll('.notification.hovered');
                $('#LiveNotifications').html(data);
                // Restore the hovered status of notifications
                hoveredNotifications.forEach(notification => {
                    notification.classList.add('hovered');
                });
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

$(document).ready(function () {

setInterval( function() {
	$("#timerBox").load(location.href + " #timerBox");
 }, 1000 );

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
                window.location=window.location;
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
                window.location=window.location;
               }
            });


    });

    function realtime(){
      const xhttp = new XMLHttpRequest();
      xhttp.onload = function(){
         document.getElementsByClassName("realtime")[0].innerHTML = xhttp.responseText;
      }

      // Include the id parameter in the URL of the buttons.php script
      xhttp.open("GET",`show.php`);
      xhttp.send();
      }


         function updateRealtime(){
         realtime();
         setTimeout(updateRealtime, 1000);
         }

         updateRealtime();


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
document.getElementById("pasteButton").addEventListener("click", function () {
  // Get the copied text from the clipboard
  navigator.clipboard.readText().then(function (clipboardText) {
    // Paste the copied text into the input field
    document.getElementById("notesinput").value = clipboardText;
  });
});

document.getElementById("enterButton").addEventListener("click", function () {
  // Simulate pressing the Enter key on the input field
  var inputElement = document.getElementById("notesinput");
  var event = new KeyboardEvent("keypress", {
    key: "Enter",
    keyCode: 13,
    which: 13,
    bubbles: true,
    cancelable: true,
  });
  inputElement.dispatchEvent(event);
});



</script>

<?php
// End the output buffer to apply the image path fix
?>
</body>
</html>