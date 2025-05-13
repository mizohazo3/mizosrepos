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
$msg = '';
header("Location: https://mcgk.site/meds2");


if (isset($_POST['addnew']) && $_POST['addnew'] == 'Addnew!') {

    $medname = $_POST['medname'];
    $halflife = $_POST['halflife'];
    $halflife = empty($halflife) ? NULL : $halflife; // Set halflife to NULL if it's empty
    $checkMeds = $con->query("SELECT * from medlist where name='$medname'");

    if (empty($_POST['starting'])) {
        $starting = '';
    } else {
        $starting = $_POST['starting'];
    }

    if (empty($_POST['medname'])) {
        $msg = '<font color="red">enter med name!</font>';
        header("Refresh:1 url=index.php");
    } elseif ($checkMeds->rowCount() > 0) {
        $msg = '<font color="red">This Medication already Exist!</font>';
        header("Refresh:1 url=index.php");

    } else {
        $insert = $con->prepare("INSERT INTO medlist (name, start_date, end_date, status, half_life) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$medname, $starting, '', 'open', $halflife]);
        header("Refresh:1 url=index.php");
    }
}

$stopButton = '';
$checkRunning = $con->query("SELECT * FROM medlist where start_date != '' and end_date = ''");
if ($checkRunning->rowCount() > 0) {
    $stopButton = '<div style="text-align:center;border: 2px solid black;margin:5px;padding:2px;display: inline-block;"><a href="index.php?stop" class="btn btn-danger">Stop</a></div>';
}

/// Start Button

$confirmMsg = '';
if (isset($_POST['start']) && $_POST['start'] == 'Start') {
    $_SESSION['startdate'] = $_POST['startDate'];
    $_SESSION['hiddenName'] = $_POST['hiddenName'];

    $confirmMsg = '<br><span style="font-size:20px;">Are you sure you want to start <b style="color:red;">' . $_SESSION['hiddenName'] . '</b> at <b>' . $_SESSION['startdate'] . ':</b></span><br><form action="index.php?start" method="post"><input type="submit" name="startYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="StartNo" value="NO!" class="btn btn-danger"></form>';

}

if (isset($_POST['startYes']) && $_POST['startYes'] == 'YES!') {
    $name = $_SESSION['hiddenName'];
    $dateStart = $_SESSION['startdate'];

    $start = $con->prepare("UPDATE medlist set start_date=?, end_date=? where name=?");
    $start->execute([$dateStart, '', $name]);

    $confirmMsg = '<br><span style="font-size:20px;"><B>' . $name . '</b> Started Successfully! <br></span><br>';
    Header("Refresh:3 index.php?start");

}

if (isset($_POST['StartNo']) && $_POST['StartNo'] == 'NO!') {
    unset($_SESSION["startdate"]);
    unset($_SESSION["hiddenName"]);
    Header("Location: index.php?start");
}

/// End of Start Button

if (isset($_POST['HalfButton']) && $_POST['HalfButton'] == 'Update') {
    $_SESSION['halflife'] = $_POST['halflife'];
    $_SESSION['HalfName'] = $_POST['HalfName'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['HalfName'] . '</b> To <b>' . $_SESSION['halflife'] . ' Hrs:</b></span><br><form action="index.php?searchKey=' . $searchKey . '" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    } else {
        $confirmMsg = '<br><span style="font-size:20px;">Do you want to change <b style="color:red;">' . $_SESSION['HalfName'] . '</b> To <b>' . $_SESSION['halflife'] . ' Hrs:</b></span><br><form action="index.php?halflives" method="post"><input type="submit" name="UpdateYes" value="YES!" class="btn btn-success" style="margin-right:20px;"> <input type="submit" name="UpdateCancel" value="Cancel!" class="btn btn-danger"></form>';
    }

}

if (isset($_POST['UpdateYes']) && $_POST['UpdateYes'] == 'YES!') {
    $name = $_SESSION['HalfName'];
    $halflife = $_SESSION['halflife'];

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'halflives';
    }

    $start = $con->prepare("UPDATE medlist set half_life=? where name=?");
    $start->execute([$halflife, $name]);

    $confirmMsg = '<br><span style="font-size:20px;"><B>' . $name . '</b> Half Life Updated To ' . $halflife . ' Hrs! <br></span><br>';
    Header("Refresh:3 index.php?$refreshPage");

}

if (isset($_POST['UpdateCancel']) && $_POST['UpdateCancel'] == 'Cancel!') {
    unset($_SESSION['halflife']);
    unset($_SESSION['HalfName']);

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $refreshPage = 'searchKey=' . $searchKey;
    } else {
        $refreshPage = 'halflives';
    }
    Header("Refresh:3 index.php?$refreshPage");
}



?>


<!DOCTYPE html>
<html>
<head>
	<title>MedTracker</title>
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
    </style>
</head>
<body>

 <form action="index.php" method="post">
 	<a href="index.php"><img src="img/icon.png"></a>MedName: <input type="text" id="medname" name="medname"> HalfLife <input type="text" id="halflife" name="halflife">
 	Starting? <input type="checkbox" name="starting" value="<?php echo $datenow; ?>">
  <label>Yes</label>
  	 <input type="submit" name="addnew" value="Addnew!" class="btn btn-primary"> <?php echo $msg; ?>
       <p id="liveFeedback"></p></form> <a href="index.php?lastdoses" class="btn btn-warning" style="margin-right: 10px;">Last Doses</a>  <a href="index.php" class="btn btn-info" style="margin-right: 10px;">Refresh</a> <a href="index.php?lock" class="btn btn-dark" style="margin-right: 10px;">Lock</a> <a href="index.php?unlock" class="btn btn-light" style="margin-right: 10px;">Unlock</a> <a href="index.php?start" class="btn btn-success" style="margin-right: 10px;">Start</a><a href="index.php?halflives" class="btn btn-info" style="margin-right: 10px;">HalfLives</a> <a href="index.php?showNoMore" class="btn btn-secondary" style="margin-right: 10px;">ShowNoMore</a><?php echo $stopButton; ?>
<span style="float:right;"></div>Logged as: <b><?=$userLogged;?></b> <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a><?php echo checkWorking ();?></span>
<br>
<center>
    <?php echo $confirmMsg; ?>
</center>


<?php

if (isset($_GET['lock'])) {
    $select = $con->query("SELECT * FROM medlist where status='open' ORDER BY STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') desc");
} elseif(isset($_GET['unlock'])){
    $select = $con->query("SELECT * FROM medlist where status='lock' ORDER BY STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') desc");
} elseif(isset($_GET['halflives'])) {
    $select = $con->query("SELECT * FROM medlist ORDER BY  half_life='' asc, STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') desc");
} elseif(isset($_GET['showNoMore'])){
    $select = $con->query("SELECT * FROM medlist where nomore = 'yesFirst' ORDER BY STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') desc");
} else {
    $select = $con->query("SELECT * FROM medlist where status='open' ORDER BY STR_TO_DATE(lastdose, '%d %M, %Y %h:%i %p') desc");
}

echo '<div style="text-align:center;border: 2px solid #909090;margin:5px;padding:2px;display: inline-block;border-radius: 20px;">';
echo '<span>Take: </span>';

$arr = array();

while ($fetch = $select->fetch()) {

    $stopForm = '';
    $howlong = '';
    $lastTaken = '';
    $lockButton = '';

    if (isset($_GET['halflives']) or isset($_GET['showNoMore'])) {
        $medName = '';
    } else {
        $medName = $fetch['name'];
    }

    if (!empty($fetch['start_date']) && empty($fetch['end_date'])) {

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i:s a', strtotime($st1));

        $dateStarted = $fetch['start_date'];
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i:s a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds2 = ($timeSecond - $timeFirst);

        if (isset($_GET['lastdoses'])) {
            $status = '';
        } else {
            $status = '<img src="img/on.png">';
            if ($differenceInSeconds2 <= 59) {
                $howlong = $differenceInSeconds2 . ' sec';
            } elseif ($differenceInSeconds2 < 3600) {
                $howlong = round(($differenceInSeconds2 / 60), 2) . ' mins';
            } elseif ($differenceInSeconds2 < 86400) {
                $howlong = round($differenceInSeconds2 / 3600, 2) . ' hrs';
            } elseif ($differenceInSeconds2 >= 86400) {
                $howlong = round($differenceInSeconds2 / 86400, 2) . ' days';
            }
        }

        if (isset($_GET['stop'])) {

            $stopForm .= '
			<span name="' . $fetch['name'] . '"><button class="button3" id="stopButton" style="padding: 3px 15px;"><img src="img/stop.png"></button></span>
		';

        }
    } else {
        $status = '';

        if (isset($_GET['lock'])) {

                $lockButton = '<span name="' . $fetch['name'] . '"><button class="button3" id="lockButton" style="padding: 3px 15px;"><img src="img/lock.png"></button></span>
                ';
        }elseif(isset($_GET['unlock'])){
                $lockButton = '<span name="' . $fetch['name'] . '"><button class="button3" id="unlockButton" style="padding: 3px 15px;"><img src="img/unlock.png"></button></span>
                ';
        }

    }

    $startButton = '';
    if (isset($_GET['start'])) {
        $startButton = '<img src="img/start.png"><input type="submit" class="button3" style="padding: 3px 15px;" name="start" value="Start">';

        $qu = $con->query('select * from medtrack where medname="' . $fetch['name'] . '" order by id desc');
        $selectDate = '<input type="hidden" name="hiddenName" value="' . $fetch['name'] . '"><select name="startDate" id="selectDate">';
        while ($ros = $qu->fetch()) {
            $selectDate .= '<option value="' . $ros['dose_date'] . '">' . $ros['dose_date'] . '</option>';
        }
        $selectDate .= '</select>';

        if ($fetch['status'] == 'open') {
            if (empty($fetch['start_date']) && empty($fetch['end_date'])) {
                $arr[$medName][] = ' <span name="' . $fetch['name'] . '" style="border: 2px solid #909090;margin:5px;padding:2px;display: inline-block;border-radius: 20px;"><button class="button3" id="takeButton">' . $fetch['name'] . '</button><form action="index.php?start" method="post" style="display:inline;">' . $selectDate . $startButton . '</form> </span>';
            } elseif (!empty($fetch['start_date']) && !empty($fetch['end_date'])) {
                $arr[$medName][] = ' <span name="' . $fetch['name'] . '" style="border: 2px solid #909090;margin:5px;padding:2px;display: inline-block;border-radius: 20px;"><button class="button3" id="takeButton">' . $fetch['name'] . '</button> <form action="index.php?start" method="post" style="display:inline;">' . $selectDate . $startButton . '</form></span>';
            }
        } else {
            $arr[$medName][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="takeButton" disabled>' . $fetch['name'] . '</button> </span>';
        }

    } else {

        if (isset($_GET['halflives'])) {
            $last_dose = $fetch['lastdose'];
            $st1 = str_replace(',', '', $last_dose);
            $lastDose = date('d-M-Y h:i a', strtotime($st1));

            $st2 = str_replace(',', '', $datenow);
            $timeNow = date('d-M-Y h:i a', strtotime($st2));

            $timeFirst = strtotime('' . $lastDose . '');
            $timeSecond = strtotime('' . $timeNow . '');
            $differenceInHrs = round(($timeSecond - $timeFirst) / 60 / 60, 2);

            if (!empty($fetch['half_life'])) {
                if ($fetch['half_life'] > $differenceInHrs) {

                    $percentage = round(($differenceInHrs * 100) / $fetch['half_life']) . '%';
                    $percentText = $percentage;
                    $remainHrs1 = '<p><font color="red"><b>' . ($fetch['half_life'] - $differenceInHrs) . '</b></font> Hrs Remain </p>';
                    $halfLifeMinutes = round($fetch['half_life'] * 60);
                    $halfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $halfLifeMinutes . ' minutes'));

                } else {
                    $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
                }

                if (($fetch['half_life'] * 5) > $differenceInHrs) {
                    $percentage2 = round(($differenceInHrs * 100) / ($fetch['half_life'] * 5)) . '%';
                    $percentText2 = $percentage2;
                    $remainHrs2 = ($fetch['half_life'] * 5) - $differenceInHrs . ' Hrs Remain';
                    $fiveHalflife = round(($fetch['half_life'] * 5) * 60);
                    $fivehalfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $fiveHalflife . ' minutes'));
                } else {
                    $percentage2 = '100%';
                    $percentText2 = 'Left System!';
                    $remainHrs2 = '';
                    $fiveHalflife = '';
                    $fivehalfEnd = '';
                }
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $halfLifeMinutes = '';
                $halfEnd = '';
                $fiveHalflife = '';
                $fivehalfEnd = '';
            }

            if (isset($_GET['searchKey'])) {
                echo $searchKey = $_POST['searchKey'];
                $formPage = 'searchKey=' . $searchKey;
            } else {
                $formPage = 'halflives';
            }

            $lockButton = '<form action="index.php?' . $formPage . '" method="post"><input type="text" name="halflife" id="halflife" style="width:70px;text-align:center;" value="' . $fetch['half_life'] . '"><input type="hidden" name="HalfName" value="' . $fetch['name'] . '"> Hrs <input type="submit" name="HalfButton" value="Update" class="btn btn-primary btn-sm"></form><p style="display:inline-block"> <center><i>1x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
      </div></p></center><p>' . $remainHrs1 . ' ' . $halfEnd . '</p>
      <p style="display:inline-block"><center><i>5x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: ' . $percentage2 . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText2 . '</div>
      </div></p></center><p>' . $remainHrs2 . ' <p>' . $fivehalfEnd . '</p></p>
                ';
        }

      

        if ($fetch['status'] == 'open') {

            $mastText = '';
            $takeButtonID = 'takeButton';
            $mednames = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="' . $takeButtonID . '">' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton . '';
            if (isset($_GET['halflives']) or isset($_GET['showNoMore'])) {

                if ($fetch['name'] == 'Mast') {
                    $mastText = '';
                    $takeButtonID = '';
                    $arr[$medName][] = '';
                } else {

                    $arr[$medName][] = '<span style="border: 2px solid #909090;margin:5px;padding:20px;display: inline-block;border-radius: 20px;">' . $mednames . '</span>';

                }

            } else {

                if ($fetch['name'] == 'Mast') {
                    // Mast Text box & ID
                    $mastText = '<input type="text" name="mastText" id="mastText" style="width:150px;">';
                    $takeButtonID = 'mastButton';
                }

                $arr[$medName][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="' . $takeButtonID . '">' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton . $mastText . '';

            }

        } else {
            if (isset($_GET['halflives'])) {

                if (!empty($fetch['half_life'])) {

                    if (($fetch['half_life'] * 5) > $differenceInHrs) {
                        $arr[$medName][] = $status . ' <span style="border: 2px solid #909090;margin:5px;padding:20px;display: inline-block;border-radius: 20px;"><span name="' . $fetch['name'] . '"><button class="button3" id="takeButton" disabled>' . $fetch['name'] . '</button> </span>' . $stopForm . ' ' . $howlong . $lockButton . '</span>';
                    }
                }

            } else {
                $diffTime = diffinTime($fetch['lastdose'], $datenow);
                $arr[$medName][] = $status . ' <span name="' . $fetch['name'] . '"><button class="button3" id="takeButton" disabled>' . $fetch['name'] . '</button> </span>' . $stopForm . ' <br><font size="3">Since: <b>'. $diffTime[3].' Days</b></font> <br> <font size="2">On: ' . $fetch['lastdose']. '</font>' . $lockButton;
            }
        }
    }

}

$showItems = '';
$searching = '';
if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];

    $query1 = $con->query("SELECT * FROM medlist where name LIKE '%$searchKey%'");
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

    $query = $con->query("SELECT * FROM medlist where name LIKE '%$searchKey%'");
    while ($show = $query->fetch()) {

        
        $dose_date = $show['lastdose'];
        $str = str_replace(',', '', $dose_date);
        $day = date('d M Y', strtotime($str));
        $timeonly = date('h:i a', strtotime($str));

        $st1 = str_replace(',', '', $datenow);
        $dateNow2 = date('d-M-Y h:i a', strtotime($st1));

        $dateStarted = $dose_date;
        $st2 = str_replace(',', '', $dateStarted);
        $dateStarted2 = date('d-M-Y h:i a', strtotime($st2));

        $timeFirst = strtotime('' . $dateStarted2 . '');
        $timeSecond = strtotime('' . $dateNow2 . '');
        $differenceInSeconds = ($timeSecond - $timeFirst);

        $timespent = '';
        $daystohrs = '';
        $time_spent = '';

        $last_dose = $show['lastdose'];
        $st1 = str_replace(',', '', $last_dose);
        $lastDose = date('d-M-Y h:i a', strtotime($st1));

        $st2 = str_replace(',', '', $datenow);
        $timeNow = date('d-M-Y h:i a', strtotime($st2));

        $timeFirst = strtotime('' . $lastDose . '');
        $timeSecond = strtotime('' . $timeNow . '');
        $differenceInHrs = round(($timeSecond - $timeFirst) / 60 / 60, 2);

        if (!empty($show['half_life'])) {
                if ($show['half_life'] > $differenceInHrs) {

                    $percentage = round(($differenceInHrs * 100) / $show['half_life']) . '%';
                    $percentText = $percentage;
                    $remainHrs1 = '<p><font color="red"><b>' . ($show['half_life'] - $differenceInHrs) . '</b></font> Hrs Remain </p>';
                    $halfLifeMinutes = round($show['half_life'] * 60);
                    $halfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $halfLifeMinutes . ' minutes'));

                } else {
                    $percentage = '100%';
                    $percentText = 'Done!';
                    $remainHrs1 = '';
                    $halfLifeMinutes = '';
                    $halfEnd = '';
                }

                if (($show['half_life'] * 5) > $differenceInHrs) {
                    $percentage2 = round(($differenceInHrs * 100) / ($show['half_life'] * 5)) . '%';
                    $percentText2 = $percentage2;
                    $remainHrs2 = ($show['half_life'] * 5) - $differenceInHrs . ' Hrs Remain';
                    $fiveHalflife = round(($show['half_life'] * 5) * 60);
                    $fivehalfEnd = ' @' . date('d M, Y h:i a', strtotime($lastDose . ' +' . $fiveHalflife . ' minutes'));
                } else {
                    $percentage2 = '100%';
                    $percentText2 = 'Left System!';
                    $remainHrs2 = '';
                    $fiveHalflife = '';
                    $fivehalfEnd = '';
                }
            } else {
                $percentage = '';
                $percentText = '';
                $percentage2 = '';
                $percentText2 = '';
                $remainHrs1 = '';
                $remainHrs2 = '';
                $halfLifeMinutes = '';
                $halfEnd = '';
                $fiveHalflife = '';
                $fivehalfEnd = '';
            }

        if (isset($_GET['searchKey'])) {
            $formPage = 'searchKey=' . $searchKey;
        } else {
            $formPage = 'halflives';
        }

        $lockButton = '<form action="index.php?' . $formPage . '" method="post"><input type="text" name="halflife" id="halflife" style="width:70px;text-align:center;" value="' . $show['half_life'] . '"><input type="hidden" name="HalfName" value="' . $show['name'] . '"> Hrs <input type="submit" name="HalfButton" value="Update" class="btn btn-primary btn-sm"></form><p style="display:inline-block"> <center><i>1x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-info" role="progressbar" style="width: ' . $percentage . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText . '</div>
      </div></p></center><p>' . $remainHrs1 . ' ' . $halfEnd . '</p>
      <p style="display:inline-block"><center><i>5x</i> <div class="progress" style="height: 30px;">
        <div class="progress-bar bg-success" role="progressbar" style="width: ' . $percentage2 . ';" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">' . $percentText2 . '</div>
      </div></p></center><p>' . $remainHrs2 . ' <p>' . $fivehalfEnd . '</p></p>
                ';

        if (!empty($dose_date)) {

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
        $lockOption = '';

        $result = preg_replace('/\s*\d+(\.\d+)?\D*$/', '', $show['name']);
      

        if($show['nomore'] == 'yesFirst' OR $show['nomore'] == 'yes'){
            $nomore = '<i>No More</i>';
            $unlock = '';
        }else{
            $nomore = '<button class="button3" id="nomoreButton" style="padding: 3px 15px;" data-result="'.$result.'"><img src="img/nomore.png"></button>';
            $unlock = '<button class="button3" id="unlockButton" style="padding: 3px 15px;"><img src="img/unlock.png"></button> | ';
        }

        
        if ($show['status'] == 'open') {
            if (!empty($show['start_date']) && empty($show['end_date'])) {
                $lockOption = '<img src="img/on.png">';
            } else {
                $lockOption = '<button class="button3" id="lockButton" style="padding: 3px 15px;margin-left:4px;"><img src="img/lock.png"></button> '.$nomore.' | <button class="button3" id="startButton" style="padding: 3px 15px;"><img src="img/start.png"></button>';
            }
            echo ' <span name="' . $show['name'] . '"><button class="button3" id="takeButton">' . $show['name'] . '</button> ' . $lockOption . ' </span> <br>' . $time_spent . $lockButton;
        } else {
            echo ' <span name="' . $show['name'] . '"><button class="button3" id="takeButton" disabled>' . $show['name'] . '</button>  '.$unlock .$nomore.' </span> <br>' . $time_spent . $lockButton;
        }
      
     
        echo '</div>';
    }
}

$showLastDoses = '';
foreach ($arr as $key => $value) {

    foreach ($value as $item) {
        $selectLastDose = $con->query("SELECT * from medlist where name='$key'");
        $ro = $selectLastDose->fetch();

        if (isset($ro['lastdose'])) {
            $dose_date = $ro['lastdose'];
        } else {
            $dose_date = '';
        }

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

        $time_spent = '';
        if (!empty($dose_date)) {

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

        if (isset($_GET['lastdoses'])) {
            $showLastDoses = '<bR><centeR>' . $time_spent . '</center>';
        }

        if (isset($_GET['searchKey'])) {
            $showItems = '';
        } else {
            $showItems = $item;
        }

        echo '<div class="space">';
        echo $showItems . $showLastDoses;
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
$tenDaysAgo = date('Y-m-d', strtotime('-12 hours'));
$datenow2 = date('Y-m-d'); // Get the current date without the time

// Use these formatted date strings in your SQL query
$query = $con->query("SELECT * FROM searchhistory WHERE STR_TO_DATE(search_date, '%d %M, %Y') BETWEEN '$tenDaysAgo' AND '$datenow2' ORDER BY STR_TO_DATE(search_date, '%d %b, %Y %h:%i %p') DESC");
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
 $start_date = '2022-05-28'; // Specify your desired start date here

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

// Output dates and mednames
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
    
    output_date_data($date, $con);
}


}
}

    if (isset($_GET['searchKey'])) {
        $searchKey = $_GET['searchKey'];
        $query = $con->query("SELECT * FROM medtrack where medname LIKE '%$searchKey%' OR details LIKE '%$searchKey%' order by id desc");

       
        $arr2 = array();
        $dateArr = array();
        while ($row = $query->fetch()) {


            $dose_date = $row['dose_date'];
            $str = str_replace(',', '', $dose_date);
            $day = date('M d, Y', strtotime($str));
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

            if (!empty($row['details'])) {
                $details = ', <b>[ ' . $row['details'] . ' ]</b>';
            } else {
                $details = '';
            }

            $dateArr[$day][] = ['searchKey' => $row['medname'], 'timeOnly' => $timeonly, 'daystoHrs' => $daystohrs, 'timeSpent' => $time_spent, 'details' => $details];

        }

        foreach ($dateArr as $keys => $values) {
            echo '<b style="font-size:23px;color:#1e75cd;">' . $keys . ':</b><br>';

            if (isset($_GET['showOthermeds']) and $_GET['showOthermeds'] == 'on') {
                $getDate = $keys;
                $str = str_replace(',', '', $getDate);
                $newKey = date('Y-m-d', strtotime($str));
                $timeonly = date('h:i a', strtotime($str));
                $day = date('M d, Y', strtotime($str));
                $showOthers = $con->query("SELECT * FROM medtrack where STR_TO_DATE(dose_date, '%d %M, %Y')='$newKey' order by id desc");

                while ($show = $showOthers->fetch()) {
                    $getDates = $show['dose_date'];
                    $strs = str_replace(',', '', $getDates);
                    $newKey = date('Y-m-d', strtotime($strs));
                    $timeonly = date('h:i a', strtotime($strs));

                    $dose_date = $getDates;
                    $str = str_replace(',', '', $dose_date);
                    $day = date('M d, Y', strtotime($str));

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

                    if (!empty($show['details'])) {
                        $details = ', <b>[ ' . $show['details'] . ' ]</b>';
                    } else {
                        $details = '';
                    }

                    $names = '';
                    foreach ($values as $items) {
                        if ($show['medname'] == $items['searchKey']) {
                            $names = '<font color="red"><b>' . $show['medname'] . '</b></font>, ' . $timeonly . ' ( ' . $daystohrs . ' ' . $time_spent . ' )';
                        } else {
                            $names = $show['medname'] . ', ' . $timeonly . '';
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
        echo '<br><br><br>';
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

    $(document).on('click', "#takeButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to take '+ name +' ?'))
        {
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
        }

    });

    $(document).on('click', "#mastButton",function(){
        var name = $(this).parents("span").attr("name");
        var mastNote = $("input[type='text'][name='mastText']").val();

        if(confirm('Do you want to take '+ name +' on ' + mastNote + ' ?'))
        {
            $.ajax({
               url: 'submit.php',
               type: 'GET',
               data: {name: name, mastNote: mastNote},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });

    $(document).on('click', "#HalfButton",function(){
        var name = $(this).parents("span").attr("name");
        var halflife = $("input[type='text'][name='halflife']").val();

        if(confirm('Update '+ name +' Half Life To ' + halflife + ' ?'))
        {
            $.ajax({
               url: 'halflife.php',
               type: 'GET',
               data: {name: name, halflife: halflife},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });

    $(document).on('click', "#stopButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Are you sure you want to stop taking '+ name +' ?'))
        {
            $.ajax({
               url: 'stop.php',
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

        if(confirm('Do you want to lock '+ name +' ?'))
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

    $(document).on('click', "#unlockButton",function(){
        var name = $(this).parents("span").attr("name");

        if(confirm('Do you want to unlock '+ name +' ?'))
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

    $(document).on('click', "#nomoreButton",function(){
        var name = $(this).parents("span").attr("name");
        var result = $(this).data("result"); // Access the data attribute

        if(confirm('Do you want to No More ' + name + ' and all ' + result + ' meds ?'))
        {
            $.ajax({
               url: 'nomore.php',
               type: 'GET',
               data: {name: name, result:result},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }
    });

    $("#mastText").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#mastButton").click();
    }
    });

    $("#halflife").keyup(function(event) {
    if (event.keyCode === 13) {
        $("#halfButton").click();
    }
    });

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function scrollToBottom() {
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
  }

  function handleInput() {
    const value = $('#medname').val();

    // Send an AJAX request to the PHP script
    $.ajax({
        url: 'check_textbox.php',
        method: 'POST',
        data: { medname: value },
        success: function(response) {
            $('#liveFeedback').html(response);
        }
    });
}

// Attach the input event listener to the textbox
$('#medname').on('input', handleInput);


$(document).ready(function() {
  // Existing input event listener for drugSearch

  // Click event listener for half-life-value elements
  $(document).on('click', '.half-life-value', function() {
    const halfLifeValue = $(this).text();
    $('#halflife').val(halfLifeValue);
  });
});

</script>
</body>
</html>