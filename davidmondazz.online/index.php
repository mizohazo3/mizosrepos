<?php
session_start();
include 'checkSession.php';
include 'countdown.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

// Get the path of the current script
$scriptPath = $_SERVER['SCRIPT_NAME'];
$baseDirectory = dirname($scriptPath);
if ($baseDirectory === '/' || $baseDirectory === '\\') {
    $baseDirectory = '/';
} elseif (substr($baseDirectory, -1) !== '/') {
    $baseDirectory .= '/';
}

// Construct the base URL including the directory
$baseURL = $protocol . "://" . $host . $baseDirectory;
$baseImgPath = rtrim($baseURL, '/');

$mainDomainURL = $protocol . "://" . $host;

if(isset($_SESSION['username']) || isset($_COOKIE['username'])){ ?>

<!DOCTYPE html>
<html>
<head>
 	<title>Center Base</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
 	<script src="js/jquery.min.js"></script>
	<style type="text/css">
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        background-image: url('<?php echo $baseImgPath; ?>/img/stats.jpg');
        background-attachment: fixed;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    body {
        margin: 0;
        padding: 0;
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

    .img-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        padding: 20px;
    }

    .img-container a {
        margin: 10px; /* Adjust margin between images */
    }

    #myImage:hover, #myImage2:hover {
        opacity: 0.8;
    }

    #myImage, #myImage2 {
        padding: 20px;
    }
</style>
</head>
<body>
    <br>
    <center>
        <font color="white">Hello <b><?=$userLogged; ?></b></font> 
        <a href="leave.php" class="btn btn-warning btn-sm" style="margin: 5px; padding: 5px;">Leave!</a>
    </center>
    <div class="live-container">
        <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
        <span id="LiveNotifications"></span>
    </div>
    <div class="img-container">
        <a href="https://davidmondazz.online/timer8"><img id="myImage" src="<?php echo $baseImgPath; ?>/img/advanced_timer.png" style="border-radius: 60px;"></a>
       <!--<a href="https://davidmondazz.online/timeline"><img id="myImage" src="img/timeline.png"></a> -->
        <a href="https://davidmondazz.online/bank"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/bank1.png"></a>
        <a href="https://davidmondazz.online/meds2"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/health.png"></a>
        <a href="https://davidmondazz.online/notes"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/notes.png"></a>
        <a href="https://davidmondazz.online/planner"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/planner.png"></a>
        <a href="https://davidmondazz.online/schedules"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/schedules.png"></a>
        <a href="https://davidmondazz.online/todo"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/todo.png"></a>
        <a href="https://davidmondazz.online/trashcalc"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/trashcalc.png"></a>
        <a href="https://davidmondazz.online/osrscalc"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/osrscalc.png"></a>
        <a href="https://davidmondazz.online/uploads"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/uploads.png"></a>
        <a href="https://davidmondazz.online/osrsgeprices"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/osrsgeprices.png"></a>
        <a href="https://davidmondazz.online/osrs_goldsell"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/osrsicon.jpg"></a>
        <a href="https://davidmondazz.online/meds_rating"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/meds_rating.png"></a>
        <a href="https://davidmondazz.online/ai_icons"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/ai_icons.png"></a>
        <a href="https://davidmondazz.online/percent_calculator"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/percent_calculator.png"></a>
        <a href="https://davidmondazz.online/trackerOLD"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/Timing.png"></a>
        <a href="https://davidmondazz.online/ai_commands.html"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/ai_commands.png"></a>
        <a href="https://davidmondazz.online/Neurotransmissions.html"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/neuro.png"></a>
        <a href="https://davidmondazz.online/todo2"><img id="myImage2" src="<?php echo $baseImgPath; ?>/img/todo2.png"></a>
    </div>
</body>

 </html>

 <?php }else{
    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $_SESSION['referLink'] = $actual_link;
 	header("Location: auth.php");
 }


  ?>
  
 <script>

   // Define the AJAX function
function loadContent() {
    $.ajax({
        url: 'checkWorking.php', // URL of your PHP script
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
var failedAttempts = 0; // Track failed attempts
var notifHTML = '<div class="notification-container"><img id="custom-icon" src="' + '<?php echo $baseImgPath; ?>/img/notif_off.png' + '" alt="Notification Icon"><div class="dropdown-content"><a href="#" class="notification">Notification system offline</a></div></div>';

function loadNotif() {
    // If we've failed 3 times already, just show static content
    if (failedAttempts >= 3) {
        // Only update once to avoid constant DOM updates
        if (!$('#LiveNotifications').data('static-loaded')) {
            $('#LiveNotifications').html(notifHTML);
            $('#LiveNotifications').data('static-loaded', true); 
        }
        return;
    }

    $.ajax({
        url: 'notifications.php',
        type: 'GET',
        success: function(data) {
            // Only update the content if the data has changed
            if (data !== lastData) {
                $('#LiveNotifications').html(data);
                lastData = data; // Update the last received data
                failedAttempts = 0; // Reset failed attempts on success
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
            failedAttempts++; // Increment failed attempts
            
            // After 3 failures, update with static content
            if (failedAttempts >= 3) {
                $('#LiveNotifications').html(notifHTML);
                $('#LiveNotifications').data('static-loaded', true);
            }
        }
    });
}

// Call the function immediately when the page loads
loadNotif();

// Then call the function less frequently (every 3 seconds) to reduce server load
setInterval(loadNotif, 3000);

   $(document).delegate(".FastStart", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Directly call the AJAX request without showing the confirmation
    $.ajax({
        type: 'GET',
    url: '<?php echo $mainDomainURL; ?>/tracker/faststart.php', // Absolute URL based on main domain
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
    url: '<?php echo $mainDomainURL; ?>/tracker/stop.php', // Absolute URL based on main domain
    data: { name: name, id: id },
    beforeSend: function () {},
    success: function (response) {
        location.reload();
    }
});
});

 </script>

