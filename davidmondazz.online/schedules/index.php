
<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';
include '../func.php';
include 'med_functions.php';
include 'db.php';
$datenow = date("d M, Y h:i a");
$msg = '';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;

if (isset($_POST['addnew']) && $_POST['addnew'] == 'Addnew!') {

    $taskname = $_POST['taskname'];
    $duration = $_POST['duration'];
    $duration = empty($duration) ? NULL : $duration; // Set duration to NULL if it's empty
    $checkMeds = $con->query("SELECT * from tasklist where name='$taskname'");


    if (empty($_POST['taskname'])) {
        $msg = '<font color="red">enter med name!</font>';
        header("Refresh:1 url=index.php");
    } elseif ($checkMeds->rowCount() > 0) {
        $msg = '<font color="red">This Task already Exist!</font>';
        header("Refresh:1 url=index.php");

    } else {
        $insert = $con->prepare("INSERT INTO tasklist (name, status, default_duration, email_notify) VALUES (?, ?, ?, ?)");
        $insert->execute([$taskname, 'open', $duration, 'yes']);
        header("Refresh:1 url=index.php");
    }
}


/// Start Button

$confirmMsg = '';
if (isset($_POST['start']) && $_POST['start'] == 'Start') {
    $_SESSION['startdate'] = $_POST['startDate'];
    $_SESSION['hiddenName'] = $_POST['hiddenName'];

    $confirmMsg = '<br><span style="font-size:20px;">Are you sure you want to start <b style="color:red;">' . $_SESSION['hiddenName'] . '</b> at <b>' . $_SESSION['startdate'] . ':</b></span><br><form action="index.php?start" method="post"><input type="submit" name="startYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="StartNo" value="NO!" class="btn btn-danger"></form>';

}

/// End of Start Button

if (isset($_POST['durationButton']) && $_POST['durationButton'] == 'Update') {
    $_SESSION['duration'] = $_POST['duration'];
    $_SESSION['durationName'] = $_POST['durationName'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['durationName'] . '</b> To <b>' . $_SESSION['duration'] . ' Mins:</b></span><br><form action="index.php?searchKey=' . $searchKey . '" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    } else {
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['durationName'] . '</b> To <b>' . $_SESSION['duration'] . ' Mins:</b></span><br><form action="index.php?durations" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    }

}

if (isset($_POST['UpdateYes']) && $_POST['UpdateYes'] == 'YES!') {
    $name = $_SESSION['durationName'];
    $duration = $_SESSION['duration'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'durations';
    }

    $start = $con->prepare("UPDATE tasktrack set default_duration=? where taskname=?");
    $start->execute([$duration, $name]);
    
    $start2 = $con->prepare("UPDATE tasklist set default_duration=?, sent_email=? where name=?");
    $start2->execute([$duration, null, $name]);

    $confirmMsg = '<br><span style="font-size:20px;"><B>' . $name . '</b> duration Life Updated To ' . $duration . ' Mins! <br></span><br>';
    Header("Refresh:3 index.php?$refreshPage");

}

if (isset($_POST['UpdateCancel']) && $_POST['UpdateCancel'] == 'Cancel!') {
    unset($_SESSION['duration']);
    unset($_SESSION['durationName']);

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'durations';
    }
    Header("Refresh:3 index.php?$refreshPage");
}



?>


<!DOCTYPE html>
<html>
<head>
	<title>Schedules</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<script src="js/jquery-3.6.0.min.js"></script>
    <style>
    .rotate-image {
  transition: transform 0.3s ease;
}

.rotate-image:hover {
  transform: rotate(360deg);
}

   .scroll-buttons {
    position: fixed;
    right: 30px;
    bottom: 50px;
    display: flex;
  }

  .scroll-button {
    width: 60px;
    height: 60px;
    background-color: #EC570C;
    text-align: center;
    line-height: 60px;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 20px;
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


 <form action="index.php" method="post">
 	<a href="index.php"><img src="img/schedule.png"></a> TaskName: <input type="text" id="taskname" name="taskname"> Duration <input type="text" id="duration" name="duration"> Mins
 	
  	 <input type="submit" name="addnew" value="Addnew!" class="btn btn-primary"> <?php echo $msg; ?>
       <p id="liveFeedback"></p></form> <a href="index.php?lasttasks" class="btn btn-warning" style="margin-right: 10px;">Last Tasks</a>  <a href="index.php" class="btn btn-info" style="margin-right: 10px;">Refresh</a> <a href="index.php?lock" class="btn btn-dark" style="margin-right: 10px;">Lock</a> <a href="index.php?unlock" class="btn btn-light" style="margin-right: 10px;">Unlock</a> <a href="index.php?durations" class="btn btn-info" style="margin-right: 10px;">durations</a>  
<span style="float:right;"></div>TimeNow: <?php echo $datenow;?>, Logged as: <b><?=$userLogged;?></b> <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a><div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div>
</span>
<br>
<center>
    <?php echo $confirmMsg; ?>
</center>


<?php

if (isset($_GET['lock'])) {
    $select = $con->query("SELECT * FROM tasklist where status='open' ORDER BY STR_TO_DATE(lasttask, '%d %M, %Y %h:%i %p') desc");
} elseif(isset($_GET['unlock'])){
    $select = $con->query("SELECT * FROM tasklist where status='lock' ORDER BY STR_TO_DATE(lasttask, '%d %M, %Y %h:%i %p') desc");
} elseif(isset($_GET['durations'])) {
    $select = $con->query("SELECT * FROM tasklist ORDER BY  default_duration='' asc, STR_TO_DATE(lasttask, '%d %M, %Y %h:%i %p') desc");
} else {
    $select = $con->query("SELECT * FROM tasklist where status='open' ORDER BY STR_TO_DATE(lasttask, '%d %M, %Y %h:%i %p') desc");
}

echo '<div style="text-align:center;border: 2px solid #909090;margin:5px;padding:2px;display: inline-block;border-radius: 20px;">';
echo '<span>Do: </span>';

$arr = array();

while ($fetch = $select->fetch()) {

    $stopForm = '';
    $howlong = '';
    $lastdon = '';
    $lockButton = '';

    if (isset($_GET['durations'])) {
        $taskname = '';
    } else {
        $taskname = $fetch['name'];
    }

   
        $status = '';

        if (isset($_GET['lock'])) {

                $lockButton = '<span id="' . $fetch['id'] . '" name="' . $fetch['name'] . '"><button class="button3" id="lockButton" style="padding: 3px 15px;"><img src="img/lock.png"></button></span>
                ';
        }elseif(isset($_GET['unlock'])){
                $lockButton = '<span id="' . $fetch['id'] . '" name="' . $fetch['name'] . '"><button class="button3" id="unlockButton" style="padding: 3px 15px;"><img src="img/unlock.png"></button></span>
                ';
        }

  

            $last_task = $fetch['lasttask'];
            $st1 = str_replace(',', '', $last_task);
            $lasttask = date('d-M-Y h:i a', strtotime($st1));

            $st2 = str_replace(',', '', $datenow);
            $timeNow = date('d-M-Y h:i a', strtotime($st2));

            $timeFirst = strtotime('' . $lasttask . '');
            $timeSecond = strtotime('' . $timeNow . '');
            $differenceInHrs2 = round(($timeSecond - $timeFirst) / 60, 2);
            
            $startDate = DateTime::createFromFormat('d M, Y h:i a', $last_task);
            $currentDate = new DateTime(); 
            $difference = $currentDate->diff($startDate);
            $totalMinutes = $difference->days * 24 * 60 + $difference->h * 60 + $difference->i;
            $differenceInHrs = $totalMinutes;

            $durationMinutes = round($fetch['default_duration']);
            $durationEnd = ' @' . date('d M, Y h:i a', strtotime($lasttask . ' +' . $durationMinutes . ' minutes'));
            
        if (isset($_GET['durations'])) {
            

            if (!empty($fetch['default_duration'])) {
                if ($fetch['default_duration'] > $differenceInHrs) {
                    $percentage = round(($differenceInHrs * 100) / $fetch['default_duration']) . '%';
                    $percentText = $percentage;
                    $remainHrs1 = '<p><font color="red"><b>' . ($fetch['default_duration'] - $differenceInHrs) . '</b></font> Mins Remain </p>';
                  

                } else {
                    $percentage = '100%';
                    $percentText = 'Available!';
                    $remainHrs1 = '';
                    $durationMinutes = '';
                    $durationEnd = '';
                }

                
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $durationMinutes = '';
                $durationEnd = '';
            }

            if (isset($_GET['searchKey'])) {
                echo $searchKey = $_POST['searchKey'];
                $formPage = 'searchKey=' . $searchKey;
            } else {
                $formPage = 'durations';
            }

            $lockButton = '<form action="index.php?' . $formPage . '" method="post"><input type="text" name="duration" id="duration" style="width:70px;text-align:center;" value="' . $fetch['default_duration'] . '"><input type="hidden" name="durationName" value="' . $fetch['name'] . '"> Mins <input type="submit" name="durationButton" value="Update" class="btn btn-primary btn-sm"></form><p style="display:inline-block"> <center><i>1x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
      </div></p></center><p>' . $remainHrs1 . ' ' . $durationEnd . '</p>
    
                ';
        }

      

        if ($fetch['status'] == 'open') {
            $doButtonID = 'doButton';
            $tasknames = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="' . $doButtonID . '">' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton;

           
             if($fetch['lasttask'] != '' OR $fetch['lasttask'] != null){

          if ($differenceInHrs >= $fetch['default_duration']) {
                        if (isset($_GET['durations'])) {
                            $arr[$taskname][] = '<span style="border: 2px solid #909090;margin:5px;padding:20px;display: inline-block;border-radius: 20px;">' . $tasknames . '</span>';
                        } else {
                            $arr[$taskname][] = $tasknames;
                        }
                    } else {
                        if (isset($_GET['durations'])) {
                            $arr[$taskname][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="' . $doButtonID . '" disabled>' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton;
                        } else {
                            $arr[$taskname][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="' . $doButtonID . '" disabled>' . $fetch['name'] . '</button> <font size="2">Next '.$durationEnd.'</font></span>' . $stopForm . ' ' . $howlong . $lockButton;
                        }
                    }


}else{
  $arr[$taskname][] = $tasknames;
}
           
           

        } else {
            if (isset($_GET['durations'])) {

                if (!empty($fetch['default_duration'])) {

                  
                        $arr[$taskname][] = $status . ' <span style="border: 2px solid #909090;margin:5px;padding:20px;display: inline-block;border-radius: 20px;"><span name="' . $fetch['name'] . '"><button class="button3" id="doButton" disabled>' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton . '</span>';
                  
                }

            } else {
                $diffTime = TimeLeft($fetch['last_lock'], $datenow);
                $arr[$taskname][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="doButton" disabled>' . $fetch['name'] . '</button> </span>' . $stopForm . ' <br><font size="3">Since: <b>'. $diffTime.'</b></font> <br> <font size="2">On: ' . $fetch['lasttask']. '</font>' . $lockButton;
            }
        }
    

}

$showItems = '';
$searching = '';
if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];

    $query1 = $con->query("SELECT * FROM tasklist where name LIKE '%$searchKey%'");
    $fetch = $query1->rowCount();
    if($fetch == 0){
        
    }else{
    $check = $con->query("SELECT * FROM searchhistory where search_name='$searchKey'");
    $row = $check->rowCount();
    if($row == 0){
        // insert
        $insert = $con->query("INSERT INTO searchhistory (search_name, search_date) VALUES ('$searchKey', '$datenow')");
    }else{
        // update
        $update = $con->query("UPDATE searchhistory set search_date='$datenow' where search_name = '$searchKey'");
    }
    }

    $query = $con->query("SELECT * FROM tasklist where name LIKE '%$searchKey%'");
    while ($show = $query->fetch()) {

        
        $task_date = $show['lasttask'];
        $str = str_replace(',', '', $task_date);
        $day = date('d M Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i a', strtotime($st1));

        $dateStarted = $task_date;
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds2 = ($timeSecond - $timeFirst);
        
        $startDate = DateTime::createFromFormat('d M, Y h:i a', $dateStarted);
            $currentDate = new DateTime();
            $difference = $currentDate->diff($startDate);
            $differenceInSeconds = $difference->days * 24 * 60 * 60 + $difference->h * 60 * 60 + $difference->i * 60 + $difference->s;

        $timespent = '';
        $daystohrs = '';
        $time_spent = '';

        $last_task = $show['lasttask'];
        $st1 = str_replace(',', '', $last_task);
        $lasttask = date('d-M-Y h:i a', strtotime($st1));

        $st3 = str_replace(',', '', $datenow);
       $timeNow = date('d-M-Y h:i a', strtotime($st3));

        $timeFirst2 = strtotime('' . $lasttask . '');
        $timeSecond2 = strtotime('' . $timeNow . '');
        $differenceInHrs2 = round((($timeSecond2 - $timeFirst2) / 60), 2);
        
          $startDate = DateTime::createFromFormat('d M, Y h:i a', $last_task);
            $currentDate = new DateTime(); 
            $difference = $currentDate->diff($startDate);
            $totalMinutes = $difference->days * 24 * 60 + $difference->h * 60 + $difference->i;
            $differenceInHrs = $totalMinutes;
        

        
        if (!empty($show['default_duration'])) {
                if (!empty($show['lasttask'])) {
        
        if($show['default_duration'] > $differenceInHrs){
                $percentage = round(($differenceInHrs * 100) / $show['default_duration']) . '%';
                            $percentText = $percentage;
                            $remainHrs1 = '<p><font color="red"><b>' . ($show['default_duration'] - $differenceInHrs) . '</b></font> Mins Remain </p>';
                            $durationMinutes = round($show['default_duration']);
                            $durationEnd = ' @' . date('d M, Y h:i a', strtotime($lasttask . ' +' . $durationMinutes . ' minutes'));
        } else {
                    $percentage = '100%';
                    $percentText = 'Available!';
                    $remainHrs1 = '';
                    $durationMinutes = '';
                    $durationEnd = '';
                }
            

                } else {
                    $percentage = '100%';
                    $percentText = 'Available!';
                    $remainHrs1 = '';
                    $durationMinutes = '';
                    $durationEnd = '';
                }

              
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $durationMinutes = '';
                $durationEnd = '';
            }

        if (isset($_GET['searchKey'])) {
            $formPage = 'searchKey=' . $searchKey;
        } else {
            $formPage = 'durations';
        }

        $notifications = '';
        if ($show['status'] == 'open') {
         
            if ($show['email_notify'] == 'yes') {
                $notifications = '<form class="notificationForm" id="notificationForm_' . $show['id'] . '" method="post">
                    <input type="hidden" name="taskid" value="' . $show['id'] . '">
                    <label for="email_notify">Receive Email Notifications:</label> <font color="green"><b>Yes</b></font>
                    <input type="hidden" name="email_notify" id="email_notify" value="no">
                    <br>
                    <button type="submit">Disable!</button>
                </form>';
            } else {
                $notifications = '<form class="notificationForm" id="notificationForm_' . $show['id'] . '" method="post">
                    <input type="hidden" name="taskid" value="' . $show['id'] . '">
                    <label for="email_notify">Receive Email Notifications: </label> <font color="red"><b>No</b></font>
                    <input type="hidden" name="email_notify" id="email_notify" value="yes">
                    <br>
                    <button type="submit">Enable!</button>
                </form>';
            }
            
            
        
            

        }


        $lockButton = '<form id="updateForm" action="index.php?' . $formPage . '" method="post">
        <input type="text" name="duration" id="duration" style="width:70px;text-align:center;" value="' . $show['default_duration'] . '">
        <input type="hidden" name="durationName" value="' . $show['name'] . '"> Mins('.round(($show['default_duration']/60), 2).' Hrs)
        <input type="submit" name="durationButton" value="Update" class="btn btn-primary btn-sm">
    </form>
    <br>'.$notifications.'
    <p style="display:inline-block"> 
        <center>
            <i>1x</i> 
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
            </div>
        </center>
    </p>
    <p>' . $remainHrs1 . ' ' . $durationEnd . '</p>';



        if (!empty($task_date)) {

            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 31104000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            } elseif ($differenceInSeconds >= 31104000) {
                $time_spent = round($differenceInSeconds / 31104000, 2) . ' yrs';
            }

        } else {
            $time_spent = 'None';
        }

        echo '<div class="space">';
        

        $result = preg_replace('/\s*\d+(\.\d+)?\D*$/', '', $show['name']);
      

       
            $unlock = '<button class="button3" id="unlockButton" style="padding: 3px 15px;"><img src="img/unlock.png"></button> | ';
        
        if ($show['status'] == 'open') {
       
            if($show['lasttask'] != '' OR $show['lasttask'] != null){

           if($differenceInHrs >= $show['default_duration']){
                        echo ' <span id="' . $show['id'] . '" name="' . $show['name'] . '"><button class="button3" id="doButton">' . $show['name'] . '</button> </span> <br><b>Last:</b> ' . $time_spent . $lockButton;
                    }else{
                        $reopenButton = '<span id="' . $show['id'] . '" name="' . $show['name'] . '" style="margin-left:10px;"><button class="button3" id="reopenButton">Reopen</button></span>';
                        echo ' <span id="' . $show['id'] . '" name="' . $show['name'] . '"><button class="button3" id="doButton" disabled>' . $show['name'] . '</button> <span class="myTaskText" data-id="'.$show['id'].'">'.htmlspecialchars($show['lasttask']).'</span>
                        <button class="editMyTask">Edit</button>
                        <input type="text" class="myTaskInput" style="display: none;">
                        <input type="hidden" name="showName" value="' . $show['name'] . '">
                        <button class="saveMyTask" style="display: none;">Save</button>
                         </span> '.$reopenButton.' <br><b>Last:</b> ' . $time_spent . $lockButton;
                    }

}else{
                                echo ' <span id="' . $show['id'] . '" name="' . $show['name'] . '"><button class="button3" id="doButton">' . $show['name'] . '</button> </span>' . $lockButton;
}

            
        } else {
            echo ' <span id="' . $show['id'] . '" name="' . $show['name'] . '"><button class="button3" id="doButton" disabled>' . $show['name'] . '</button>  '.$unlock.' </span> <br>' . $time_spent . $lockButton;
        }
      
     
        echo '</div>';
    }
}

$showlasttasks = '';
foreach ($arr as $key => $value) {

    foreach ($value as $item) {
        $selectlasttask = $con->query("SELECT * from tasklist where name='$key'");
        $ro = $selectlasttask->fetch();

        if (isset($ro['lasttask'])) {
            $task_date = $ro['lasttask'];
        } else {
            $task_date = '';
        }

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

        $time_spent = '';
        if (!empty($task_date)) {

            if ($differenceInSeconds <= 59) {
                $time_spent = $differenceInSeconds . ' sec';
            } elseif ($differenceInSeconds < 3600) {
                $time_spent = round(($differenceInSeconds / 60), 2) . ' mins';
            } elseif ($differenceInSeconds < 86400) {
                $time_spent = round($differenceInSeconds / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds <= 31104000) {
                $time_spent = round($differenceInSeconds / 86400, 2) . ' days';
            } elseif ($differenceInSeconds >= 31104000) {
                $time_spent = round($differenceInSeconds / 31104000, 2) . ' yrs';
            }

        } else {
            $time_spent = 'None';
        }

        if (isset($_GET['lasttasks'])) {
            $showlasttasks = '<bR><centeR>' . $time_spent . '</center>';
        }

        if (isset($_GET['searchKey'])) {
            $showItems = '';
        } else {
            $showItems = $item;
        }

        echo '<div class="space">';
        echo $showItems . $showlasttasks;
        echo '</div>';

    }

}

echo '</div>';

?>
<div style="display: flex; align-items: center; padding: 10px;">
  <div style="float: left;">
    <a href="index.php">
      <img src="img/refresh.png" class="rotate-image">
    </a>
  </div>
  <div style="flex: 1;">
    <center><br>
      <form action="index.php" style="display:inline;">
        <img src="img/search.png" style="padding-bottom: 5px;">
        <input type="text" name="searchKey" style="height: 40px; font-size: 20px; text-align: center;" <?php if (isset($_GET['searchKey'])) { echo "value='$_GET[searchKey]'"; } ?>>
        <input type="submit" name="search" value="Search" style="height: 40px; font-weight: bold;">

        ShowOthers? <input type="checkbox" name="showOthermeds" style="width: 15px; height: 15px;" <?php if (isset($_GET['showOthermeds'])) { echo "checked='checked'"; } ?>>
      </form>
      <br><span style="padding-top: 20px;">
      <?php 
$twelveHoursAgo = date('j M, Y h:i a', strtotime('-12 hours')); // Get the date and time 12 hours ago in the same format
$datenow2 = date('Y-m-d'); // Get the current date without the time

// Use these formatted date strings in your SQL query
$query = $con->query("SELECT * FROM searchhistory WHERE STR_TO_DATE(search_date, '%d %M, %Y %h:%i %p') >= STR_TO_DATE('$twelveHoursAgo', '%d %M, %Y %h:%i %p') ORDER BY STR_TO_DATE(search_date, '%d %M, %Y %h:%i %p') DESC");
    $result = $query->fetchAll(PDO::FETCH_ASSOC);

   
// Loop through the result set
foreach ($result as $row) {
    // Process each row as needed
    echo '<a href="index.php?searchKey='.$row['search_name'].'&search=Search"><b style="font-size:20px;margin:6px;">'.$row['search_name'] . "</b></a> ";
}
?>
</span>

     
     
     
    </center>
  </div>
</div>




<br>
<?php



if(!isset($_GET['searchKey'])){
    // Start and end dates
    $start_date = '2024-04-23'; // Specify your desired start date here

// Get current date
$current_date = date('Y-m-d');

// Generate date range
$date_range = [];
$next_date = $start_date;
while ($next_date <= $current_date) {
    $date_range[] = $next_date;
    $next_date = date('Y-m-d', strtotime($next_date . ' +1 day'));
}

// Reverse the date range array
$date_range = array_reverse($date_range);

// Get current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate total pages
$max_dates = 25;
$total_dates = count($date_range);
$total_pages = ceil($total_dates / $max_dates);

// Output dates and tasknames
if (isset($_GET['show_all'])) {
    // Show all dates and data on a single page
    foreach ($date_range as $date) {
        output_date_data($date, $con);
    }
} else {
    // Show data with pagination
    $start_index = ($page - 1) * $max_dates;
    $end_index = $start_index + $max_dates;


for ($i = $start_index; $i < $end_index && $i < $total_dates; $i++) {
    $date = $date_range[$i];
    
    // Call output_date_data with a unique counter value
    output_date_data($date, $con, $i);
}


}
}

    if (isset($_GET['searchKey'])) {
        
        $searchKey = $_GET['searchKey'];
        $query = $con->query("SELECT * FROM tasktrack where taskname LIKE '%$searchKey%' OR details LIKE '%$searchKey%' order by id desc");

       
        $arr2 = array();
        $dateArr = array();
        while ($row = $query->fetch()) {


            $task_date = $row['task_date'];
            $str = str_replace(',', '', $task_date);
            $day = date('M d, Y', strtotime($str));
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

            if (!empty($row['details'])) {
                $details = ', <b>[ ' . $row['details'] . ' ]</b>';
            } else {
                $details = '';
            }

            $dateArr[$day][] = ['searchKey' => $row['taskname'], 'timeOnly' => $timeonly, 'daystoHrs' => $daystohrs, 'timeSpent' => $time_spent, 'details' => $details];

        }

        foreach ($dateArr as $keys => $values) {
            echo '<b style="font-size:23px;color:#1e75cd;">' . $keys . ':</b><br>';

            if (isset($_GET['showOthermeds']) and $_GET['showOthermeds'] == 'on') {
                $getDate = $keys;
                $str = str_replace(',', '', $getDate);
                $newKey = date('Y-m-d', strtotime($str));
                $timeonly = date('h:i a', strtotime($str));
                $day = date('M d, Y', strtotime($str));
                $showOthers = $con->query("SELECT * FROM tasktrack where STR_TO_DATE(task_date, '%d %M, %Y')='$newKey' order by id desc");

                while ($show = $showOthers->fetch()) {
                    $getDates = $show['task_date'];
                    $strs = str_replace(',', '', $getDates);
                    $newKey = date('Y-m-d', strtotime($strs));
                    $timeonly = date('h:i a', strtotime($strs));

                    $task_date = $getDates;
                    $str = str_replace(',', '', $task_date);
                    $day = date('M d, Y', strtotime($str));

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

                    if (!empty($show['details'])) {
                        $details = ', <b>[ ' . $show['details'] . ' ]</b>';
                    } else {
                        $details = '';
                    }

                    $names = '';
                    foreach ($values as $items) {
                        if ($show['taskname'] == $items['searchKey']) {
                            $names = '<font color="red"><b>' . $show['taskname'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' )';
                        } else {
                            $names = $show['taskname'] . ', ' . $timeonly . '';
                        }
                    }

                    echo '- <span style="font-size:18px;">' . $names . '</span> ' . $details . '<br>';
                }

            } else {
                foreach ($values as $items) {

                    echo $names = '- <font color="red" style="font-size:18px;"><b>' . $items['searchKey'] . '</b></font>, ' . $items['timeOnly'] . ' ( ' . $items['daystoHrs'] . ' ' . $items['timeSpent'] . ' ) ' . $items['details'] . '<br>';

                }
            }

            echo '<br><br>';
        }

    }

    echo '<br>';

    if(!isset($_GET['searchKey']) and !isset($_GET['show_all'])){
        // Pages links
        echo '<br><br>';
      
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo '<button class="btn btn-dark btn-lg disabled"><b>' . $i . '</b></button> ';
            } else {
                echo '<a href="index.php?page='.$i.'" class="btn btn-primary btn-lg">'.$i.'</a> ';
            }
        }
      
    
    
        echo '<a href="index.php?show_all" class="btn btn-info btn-lg" >Show All</a>';
    

    }

    echo '<br><br>';


?>

<div class="scroll-buttons">
  <div class="scroll-button up" onclick="scrollToTop()"><img src="img/arrow_up.png"></div>
  <div class="scroll-button" onclick="scrollToBottom()"><img src="img/arrow_down.png"></div>
</div>

<script type="text/javascript">

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



    $(document).on('click', "#doButton",function(){
        var name = $(this).parents("span").attr("name");

       
            $.ajax({
               url: 'submit.php',
               type: 'GET',
               data: {name: name},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
       

    });



  

    $(document).on('click', "#lockButton",function(){
        var id = $(this).parents("span").attr("id");
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to lock '+ name +' ?'))
        {
            $.ajax({
               url: 'lock.php',
               type: 'GET',
               data: {id: id},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }
    });

    $(document).on('click', "#unlockButton",function(){
        var id = $(this).parents("span").attr("id");
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to unlock '+ name +' ?'))
        {
            $.ajax({
               url: 'unlock.php',
               type: 'GET',
               data: {id: id},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }
    });
    
      $(document).on('click', "#reopenButton",function(){
        var id = $(this).parents("span").attr("id");
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to Reopen '+ name +' ?'))
        {
            $.ajax({
               url: 'reopen.php',
               type: 'GET',
               data: {id: id},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }
    });



    $("#duration").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#durationButton").click();
    }
    });


// Function to store scroll position in sessionStorage when navigating away from the page
function saveScrollPosition() {
    sessionStorage.setItem('scrollPosition', window.scrollY);
}

// Function to restore scroll position from sessionStorage
function restoreScrollPosition() {
    var scrollPosition = sessionStorage.getItem('scrollPosition');
    if (scrollPosition !== null) {
        window.scrollTo(0, scrollPosition);
        sessionStorage.removeItem('scrollPosition'); // Clear the stored scroll position
    }
}

// Call saveScrollPosition function before navigating away from the page
window.addEventListener('beforeunload', saveScrollPosition);

// Call restoreScrollPosition function after the page reloads
window.addEventListener('load', restoreScrollPosition);


function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function scrollToBottom() {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  }


  function handleInput() {
    const value = $('#taskname').val();

    // Send an AJAX request to the PHP script
    $.ajax({
        url: 'check_textbox.php',
        method: 'POST',
        data: { taskname: value },
        success: function(response) {
            $('#liveFeedback').html(response);
        }
    });
}

// Attach the input event listener to the textbox
$('#taskname').on('input', handleInput);


$(document).ready(function() {
  // Existing input event listener for drugSearch

  // Click event listener for duration-life-value elements
  $(document).on('click', '.duration-life-value', function() {
    const durationValue = $(this).text();
    $('#duration').val(durationValue);
  });
});

$(document).ready(function(){
    $('.notificationForm').submit(function(e){
        e.preventDefault(); // Prevent default form submission
        var formData = $(this).serialize(); // Serialize form data
        
        // AJAX request to update the form content
        $.ajax({
            url: 'process_form.php',
            type: 'post',
            data: formData,
            success: function(response){
                console.log(response); // Log response for debugging
                location.reload(); // Reload the page
            }
        });
    });
});


$(document).ready(function() {
    $(".editMyTask").click(function() {
        var $container = $(this).parent();
        $container.find(".myTaskText").hide(); // Hide the text
        $container.find(".myTaskInput").val($container.find(".myTaskText").text()).show().focus(); // Set input value to current text, show input field, and focus
        $(this).hide(); // Hide the "Edit" button
        $container.find(".saveMyTask").show(); // Show the "Save" button
    });

    $(".saveMyTask").click(function() {
        var $container = $(this).parent();
        var newTask = $container.find(".myTaskInput").val();
        var id = $container.find(".myTaskText").data("id"); // Get the ID from the data attribute
        var showNameValue = $('input[name="showName"]').val();
        $.ajax({
            type: "POST",
            url: "update_my_task.php",
            data: { newTask: newTask, id: id, showNameValue: showNameValue  }, // Pass both newTask and id
            success: function(response) {
                $container.find(".myTaskText").text(response).show(); // Update the displayed text
                $container.find(".myTaskInput").hide(); // Hide the input field
                $container.find(".saveMyTask").hide(); // Hide the "Save" button
                $container.find(".editMyTask").show(); // Show the "Edit" button
            }
        });
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
