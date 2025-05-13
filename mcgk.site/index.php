<?php 
session_start();
include 'checkSession.php';
include 'countdown.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

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
        background-image: url(img/stats.jpg);
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
        <a href="https://mcgk.site/tracker"><img id="myImage" src="img/Timing.png"></a>
       <!--<a href="https://mcgk.site/timeline"><img id="myImage" src="img/timeline.png"></a> -->
        <a href="https://mcgk.site/bank"><img id="myImage2" src="img/bank1.png"></a>
        <a href="https://mcgk.site/meds2"><img id="myImage2" src="img/health.png"></a>
        <a href="https://mcgk.site/notes"><img id="myImage2" src="img/notes.png"></a>
        <a href="https://mcgk.site/planner"><img id="myImage2" src="img/planner.png"></a>
        <a href="https://mcgk.site/schedules"><img id="myImage2" src="img/schedules.png"></a>
        <a href="https://mcgk.site/todo"><img id="myImage2" src="img/todo.png"></a>
        <a href="https://mcgk.site/trashcalc"><img id="myImage2" src="img/trashcalc.png"></a>
        <a href="https://mcgk.site/osrscalc"><img id="myImage2" src="img/osrscalc.png"></a>
        <a href="https://mcgk.site/uploads"><img id="myImage2" src="img/uploads.png"></a>
        <a href="https://mcgk.site/osrsgeprices"><img id="myImage2" src="img/osrsgeprices.png"></a>
        <a href="https://mcgk.site/osrs_goldsell"><img id="myImage2" src="img/osrsicon.jpg"></a>
        <a href="https://mcgk.site/meds_rating"><img id="myImage2" src="img/meds_rating.png"></a>
        <a href="https://mcgk.site/ai_icons"><img id="myImage2" src="img/aiicons.png"></a>
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

function loadNotif() {
    $.ajax({
        url: 'notifications.php',
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

