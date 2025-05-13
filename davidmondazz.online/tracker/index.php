<?php
session_start();
include '../checkSession.php';
include 'db.php';
include $_SERVER['DOCUMENT_ROOT'] . '/func.php';
include '../db.php';
include 'functions.php';
include '../countdown.php';
date_default_timezone_set("Africa/Cairo");
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

$errMsg = '';
$linkUrl = '';
$dateNow = date('Y-m-d H:i:s');
if (isset($_POST['addnew']) && $_POST['addnew'] == 'AddNew!') {
    $catList = $_POST['cats_list'];
    $actName = $_POST['activity_name'];
    $selectall = $con->query("SELECT * FROM activity where name = '$actName' and cat_name='$catList'");

    if (empty($_POST['activity_name'])) {
        $errMsg = '<font color="red">Enter Activity Name!</font>';
        header("Refresh:1; url=index.php");
    } elseif ($selectall->rowCount() > 0) {
        $errMsg = '<font color="red">Activity Already Exists!!</font>';
        header("Refresh:2; url=index.php");
    } else {
        $activity_name = $_POST['activity_name'];
        $linkUrl = $_POST['link_url'];
        $cats_list = $_POST['cats_list'];
        $insert = $con->prepare("INSERT INTO activity (name, added_date, cat_name, status, time_spent, links) VALUES (?, ?, ?, ?, ?, ?) ");
        $insert->execute([$activity_name, $dateNow, $cats_list, 'off', '', $linkUrl]);
        // Get the ID of the last inserted row
        $lastId = $con->lastInsertId();
        $errMsg = '<font color="green">Added Successfully!</font> -> <a href="show.php?id=' . $lastId . '"><font size="4"><b>'.$activity_name.'</b></font></a><br><br>';
    }
}

if (isset($_POST['stop']) && $_POST['stop'] == 'Stop') {

    $getid = $_POST['StopId'];
    $select2 = $con->query("SELECT * FROM details where activity_id='$getid' and current_status='on'");
    $row2 = $select2->fetch();

    $dateNow = date('Y-m-d H:i:s');

    $dateStarted = $row2['start_date'];
    
    $timeFirst = strtotime($dateStarted);
    $timeSecond = strtotime($dateNow);
    $differenceInSeconds = ($timeSecond - $timeFirst);

    $id = $row2['id'];
    $finish = $con->prepare("UPDATE details set end_date=? , total_time=?, current_status=? where id=?");
    $finish->execute([$dateNow, $differenceInSeconds, 'off', $id]);
    
 /*
   $subject2 = $row2['activity_name'].' ('.TimeLeft($dateStarted, $dateNow).')';
          $showID = 'show.php?id='.$getid.'&name';
         $notification = $connect->query("INSERT INTO notifications (message, date_time, notif_type, notif_cat, title, page, activity_id) VALUES ('$subject2', '$dateNow', 'Tracker', '$row2[cat_name]', '$row2[activity_name]', '$showID', '$getid')");
         */

    
    
    $update = $con->prepare("UPDATE activity set status=?, time_spent=time_spent+? where id=?");
    $update->execute(['off', $differenceInSeconds, $getid]);

    $catid = $row2['cat_name'];
    $catupdate = $con->prepare("UPDATE categories set total_time=total_time+? where name=?");
    $catupdate->execute([$differenceInSeconds, $catid]);
    header("Refresh:0; url=index.php");

}

?>

<!DOCTYPE html>
<html>
<head>
	<title>Activity Tracker</title>
 	 <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
 	<link rel="stylesheet" type="text/css" href="css/style.css">
 	<script src="js/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/fontawesome.css">
	<style type="text/css">
		form{
			display:inline;
		}
		.cats{
			float: right;
			font-size: 20px;
			font-weight: bold;
			padding: 10px;
		}
		a:link{
			text-decoration: none;
		}
		a:hover{
			color: hotpink;
		}
		.spin:hover{
		transition: .5s;
		transform: rotate(360deg);
		}

	.list{
    padding-top: 40px;
    }

	body{
    padding: 20px;
	}
   .lock-btn::before {
  content: "\f023"; /* Lock icon */
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
  margin-right: 5px;
}

button {
  all: unset;
  cursor: pointer;
}

button:focus {
  outline: orange 5px auto;
}

.circle {
  width: 8px;
  height: 8px;
  border-radius: 0;
  background-color: #686D72;
  display:inline-block;
}

   .stroked-text {
  -webkit-text-stroke: 0.3px black; /* For Safari */
  text-stroke: 0.3px #C6C6C6; /* For other browsers */
}

.multiplework{
   border:2px  solid;
   margin:5px;
   padding:14px;
   border-radius:10px;
   display: inline-block;
}


/* Styles for screens that are 768 pixels or wider (desktop) */
@media (min-width: 768px) {
 .line-break::before {
  content: "\00a0 || \00a0";
}
}

/* Styles for screens that are less than 768 pixels wide (mobile) */
@media (max-width: 767px) {
   .currentWorking{
       text-align:center;
   }

   .multiplework{
   border:2px  solid;
   margin:5px;
   border-radius:10px;
   display: inline-block;
   text-align: left;
   padding-top:16px;
   padding-right:70px;
   }

 .line-break::before {
  content: " \a\a"; /* This is the ASCII code for a line break */
  white-space: pre; /* This ensures that the line break is displayed */
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

<body onload="realtime();">

<div class="cats" style="border:2px solid #a9a9a9;border-radius:20px;margin:5px;"> 	<div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a> <a href="categories.php" class="btn btn-light btn-sm" style="margin:5px;">Categories</a> <a href="../timeline" style="margin:5px;"><img src="<?php echo $baseImgPath; ?>/img/timeline_icon.png"></a><a href="../leave.php" class="btn btn-warning btn-sm" style="margin:5px;">Leave!</a></div> 
 <a href="index.php"><img src="<?php echo $baseImgPath; ?>/img/icon.png" class="spin"></a><form action="index.php" method="post" style="display:inline">
 	Name: <input type="text" name="activity_name"> Link: <input type="text" name="link_url">
 	<select name="cats_list">
 		<?php
$cats = $con->query("SELECT * FROM categories");
if ($cats->rowCount() > 0) {
    while ($fetch = $cats->fetch(PDO::FETCH_ASSOC)) {
        echo '<option value="' . $fetch['name'] . '">' . $fetch['name'] . '</option>';
    }
} else {
    echo '<option value=""></option>';
}

?>
 	</select>
  <input type="submit" name="addnew" value="AddNew!" class="btn btn-primary btn-sm"> <?php echo $errMsg; ?> <a href="?manage" class="btn btn-info">Manage</a>
 </form>

<?php

$lockMsg = '';
$names = '';
if (isset($_POST['locking']) && $_POST['locking'] == 'Lock') {

    if (!empty($_POST['doneOption'])) {

        foreach ($_POST['doneOption'] as $check) {

            $expCheck = explode('|', $check);
            $id = $expCheck[0];

            $getName = $con->query("SELECT * from activity where id='$id'");
            $updateQ = $con->query("UPDATE activity set manage_status='lock' where id='$id'");

            while ($fetch = $getName->fetch(PDO::FETCH_ASSOC)) {
                $names .= $fetch['name'] . ', ';
            }

            $lockMsg = '<font color="black">You have locked <b>' . rtrim($names, ', ') . '</b></font>';
            if (isset($_GET['select_cat'])) {
                header("Refresh:1 ; url=index.php?manage&select_cat=$_POST[select_cat_hidden]");
            } elseif (isset($_GET['searchKey'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header("Refresh:1 ; url=index.php?manage");
            }

        }

    } else {
        $lockMsg = '<font color="red">Choose what to lock first!</font>';
    }
}

if (isset($_POST['special_locking'])) {

    if (!empty($_POST['doneOption'])) {

        foreach ($_POST['doneOption'] as $check) {

            $expCheck = explode('|', $check);
            $id = $expCheck[0];

            $getName = $con->query("SELECT * from activity where id='$id'");
            $updateQ = $con->query("UPDATE activity set manage_status='lock&special' where id='$id'");

            while ($fetch = $getName->fetch(PDO::FETCH_ASSOC)) {
                $names .= $fetch['name'] . ', ';
            }

            $lockMsg = '<font color="black">You have locked <b>' . rtrim($names, ', ') . '</b> and in Special!</font>';
            if (isset($_GET['select_cat'])) {
                header("Refresh:1 ; url=index.php?manage&select_cat=$_POST[select_cat_hidden]");
            } elseif (isset($_GET['searchKey'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header("Refresh:1 ; url=index.php?manage");
            }

        }

    } else {
        $lockMsg = '<font color="red">Choose what to lock first!</font>';
    }
}

if (isset($_POST['unlocking']) && $_POST['unlocking'] == 'UNLock') {
    if (!empty($_POST['doneOption'])) {

        foreach ($_POST['doneOption'] as $check) {

            $expCheck = explode('|', $check);
            $id = $expCheck[0];

            $getName = $con->query("SELECT * from activity where id='$id'");
            $updateQ = $con->query("UPDATE activity set manage_status='' where id='$id'");

            while ($fetch = $getName->fetch(PDO::FETCH_ASSOC)) {
                $names .= $fetch['name'] . ', ';
            }

            $lockMsg = '<font color="blue">You have unlocked </font><b>' . rtrim($names, ', ') . '</b>';
            if (isset($_GET['select_cat'])) {
                header("Refresh:1 ; url=index.php?manage&select_cat=$_POST[select_cat_hidden]");
            } elseif (isset($_GET['searchKey'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header("Refresh:1 ; url=index.php?manage");
            }
        }

    } else {
        $lockMsg = '<font color="red">Choose what to unlock first!</font>';
    }
}

if (isset($_POST['calcHours']) && $_POST['calcHours'] == 'CalcHours') {

    if (!empty($_POST['doneOption'])) {

        $counter = 0;
        foreach ($_POST['doneOption'] as $check) {

            $getName = $con->query("SELECT * from activity where id='$check'");
            $fetch = $getName->fetch();

            $ar = explode('|', $check);
            $names .= $fetch['name'] . ', ';

            $counter += $ar[1];

            $lockMsg = '<font color="black">You have chosed <b>' . rtrim($names, ', ') . ' => </b></font>';
        }
        $lockMsg .= '<b style="color:red;font-size:25px">' . detailTime($counter) . '</b>';

    } else {
        $lockMsg = '<font color="red">Choose what to calculate first!</font>';
    }
}

$linkmsg = '';
if (isset($_POST['linkchange']) && $_POST['linkchange'] == 'Change!') {

    $link = $_POST['linksOption'];
    $hiddenid = $_POST['hiddenid'];

    $update = $con->query("UPDATE activity set links = '$link' where id = '$hiddenid'");
    $select = $con->query("SELECT * FROM activity WHERE id = '$hiddenid'");
    $row = $select->fetch();

    $linkmsg = '<img src="' . $baseImgPath . '/img/rightsign.png"> <b>' . $row['name'] . '</b> Updated link successfully!';
}

if (isset($_POST['editchange']) && $_POST['editchange'] == 'Edit!') {

    $edit = $_POST['editOption'];
    $hiddenid = $_POST['hiddenid'];

    $update_activity = $con->query("UPDATE activity set name='$edit' where id='$hiddenid'");
    $update_details = $con->query("UPDATE details set activity_name='$edit' where activity_id='$hiddenid'");

    $select = $con->query("SELECT * FROM activity WHERE id = '$hiddenid'");
    $row = $select->fetch();

    $linkmsg = '<img src="' . $baseImgPath . '/img/rightsign.png"> <b>' . $row['name'] . '</b> Edited to <b>' . $edit . '</b> successfully!';
}

$keyword = '';
if (isset($_GET['manage'])) {

    if (!empty($_GET['searchKey'])) {
        $keyword = $_GET['searchKey'];
    }

    echo '<form action="index.php?searchManage" align="center" style="display:inline;">
    <img src="' . $baseImgPath . '/img/search.png" style="padding-bottom:5px;"> <input type="text" name="searchKey" value="' . $keyword . '" style="height:40px;font-size:20px;text-align:center;">
    <input type="submit" name="searchManage" value="Search" style="height:40px;font-weight:bold;">
    </form>';
} else {
    if (!empty($_GET['searchKey'])) {
        $keyword = $_GET['searchKey'];
    }
    if (isset($_GET['searchManage'])) {
        echo '<form action="index.php" align="center" style="display:inline;">
    <img src="' . $baseImgPath . '/img/search.png" style="padding-bottom:5px;"> <input type="text" name="searchKey" value="' . $keyword . '" style="height:40px;font-size:20px;text-align:center;">
    <input type="submit" name="searchManage" value="Search" style="height:40px;font-weight:bold;">
    </form>';
    } else {
        echo '<form action="index.php" align="center" style="display:inline;">
    <img src="' . $baseImgPath . '/img/search.png" style="padding-bottom:5px;"> <input type="text" name="searchKey" value="' . $keyword . '" style="height:40px;font-size:20px;text-align:center;">
    <input type="submit" name="search" value="Search" style="height:40px;font-weight:bold;">
    </form>';
    }
}

?>



 <div class="list">
 	<?php

if (!isset($_GET['manage']) && !isset($_GET['searchKey'])) {
    $checkWorking = $con->query("SELECT * FROM activity where status = 'on'");
    $workingCount = $checkWorking->rowCount();
    if ($checkWorking->rowCount() > 0) {

        echo '<span class="currentWorking">';
        echo ' <div style="border:2px black solid;border-radius: 1em;background: #ddd;padding: 15px;border: 2px solid white;"><div style="margin-bottom:10px;margin-left:15px;color:#1650DF;"><b>Current Working:  (' . $workingCount . ')</b></div>';
        $out = array();

        while ($show = $checkWorking->fetch()) {
            $lastStarted = '<span class="timerLive" data-id="' . $show['last_started'] . '" style="border:2px #0AAD9B solid;border-radius:7px;margin:5px;padding: 5px;vertical-align: middle;background:black;color:white;"></span>';
            $stopButton = '<form action="index.php" method="post" style="display:inline;">
               <input type="hidden" name="StopId" value="' . $show['id'] . '">
            <input type="submit" name="stop" value="Stop" class="btn btn-danger btn-sm" style="opacity: 0.8;border-radius:10px;margin-left:3px;">
            </form>';
            $out[] = '<span style="display: inline-block; padding-bottom:15px;">' . $stopButton . ' ' . $lastStarted . '<img src="img/on3.png" style="padding-left:10px;"> <b style="color:' . $show['colorCode'] . ';font-size:16px;"><a href="show.php?id=' . $show['id'] . '" style="color: inherit; " class="stroked-text">' . $show['name'] . '</a></b> ';
            $current[] = $show['name'];
            $allID[] = $show['id'];

        }
        if ($workingCount == 1) {
            $stopWork = implode(', ', $current);
            $allIDs = implode(', ', $allID);
            echo '<div style="border:2px  solid;margin:5px;padding:14px;border-radius:10px;display: inline-block;">';
            echo implode(' || ', $out) . ' <span name="' . $stopWork . '" id="' . $allIDs . '""></span></div></div>';
            echo '<br><br>';
        } else {
            $stopWork = implode(', ', $current);
            $allIDs = implode(', ', $allID);
            echo '<div class="multiplework">';
            echo implode('<p style="display:inline;" ><span class="line-break"></span></p>', $out) . ' </div><span name="' . $stopWork . '" id="' . $allIDs . '""><br><button class="btn btn-danger btn-sm" id="StopAll" style="vertical-align: middle;margin-top:20px;">StopALL</button></span></div>';
            echo '<br><br>';
        }
        echo '</span>';
    }

}

$serArray = array();
if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];
    echo '<center><span style="font-size:25px;">Search for: <b style="color:red;">' . $searchKey . '</b></span></center><br><br>';
    $searchSelect = $con->query("SELECT * FROM activity where name LIKE '%$searchKey%' order by id desc");
    while ($fo = $searchSelect->fetch()) {
        $lastStarted = gmdate("H:i:s", TimerNow($fo['last_started']));
        $serArray[$fo['cat_name']][] = ['id' => $fo['id'], 'name' => $fo['name'], 'statusOn' => $fo['status'], 'timeSpent' => $fo['time_spent'], 'manageStatus' => $fo['manage_status'], 'cat_name' => $fo['cat_name'], 'lastStarted' => $lastStarted, 'links' => $fo['links']];
    }

    if (empty($_GET['searchKey'])) {
        echo '<center><span style="color:red;font-weight:bold;">Enter a keyword!</span></centeR>';
    }

}

if (isset($_GET['manage']) or isset($_GET['searchManage'])) {

    if (isset($_GET['manage']) && $_GET['manage'] == 'links' or $_GET['manage'] == 'Edit') {

        $form = '';
        $formlinks = '';
    } else {

        $select_cat = '';
        if (isset($_GET['select_cat'])) {
            $select_cat = '<input type="hidden" name="select_cat_hidden" value="' . $_GET['select_cat'] . '">';
            $form = '<form action="index.php?manage&select_cat=' . $_GET['select_cat'] . '" method="post">';
        } elseif (isset($_GET['searchKey'])) {
            $form = '<form action="index.php?searchKey=' . $_GET['searchKey'] . '&searchManage=Search" method="post">';
        } else {
            $select_cat = '';
            $form = '<form action="index.php?manage" method="post">';
        }

        $formlinks = '<div class="btn-group btn-group-sm border border-dark border-2 rounded p-1" role="group">
        <button type="submit" name="locking" value="Lock" class="btn btn-dark me-2"><i class="fas fa-lock"></i> Lock</button>
        <button type="submit" name="special_locking" class="btn btn-dark"><i class="fas fa-lock"></i> <i class="fas fa-star"></i> Lock & Special</button> ' . $select_cat . '
      </div> <button type="submit" name="unlocking" value="UNLock" class="btn btn-primary"><i class="fas fa-unlock"></i> UNLock </button> <button type="submit" name="calcHours" value="CalcHours" class="btn btn-warning"><i class="fas fa-hourglass"></i> CalcHours </button> <a href="index.php?manage=showSpecial" class="btn btn-secondary"><i class="fas fa-star"></i> ShowSpecial </a>';
    }

    $getselectCat = '';
    if (isset($_GET['select_cat'])) {
        $getselectCat = $_GET['select_cat'];
    }

    echo $form . $formlinks . '
	 <a href="index.php?manage=links" class="btn btn-info"><i class="fas fa-link"></i> Links </a> <a href="index.php?manage&select_cat=' . $getselectCat . '&showGamesUpdates" class="btn btn-success"><i class="fas fa-fire"></i> showUpdates  </a> <a href="index.php?manage=Edit" class="btn btn-warning"><i class="fas fa-pencil-alt"></i> Edit </a> ';
    echo $lockMsg;
    echo '<br><br>';

}

foreach ($serArray as $key => $value) {
    if (!empty($_GET['searchKey'])) {
        echo $searchCats = '<span style="font-weight:bold;font-size: 25px;">' . $key . '</span><br>';
    } else {
        echo $searchCats = '';
    }

    foreach ($value as $item) {
        if (!empty($_GET['searchKey'])) {

            if ($item['statusOn'] == 'on') {
                $stopButton = '<form action="index.php" method="post" style="display:inline;">
                <input type="hidden" name="StopId" value="' . $item['id'] . '">
               <input type="submit" name="stop" value="Stop" class="btn btn-danger btn-sm">
               </form> => <span style="border:2px black solid;margin:5px;padding:2px;"><b id="timerBox">' . $item['lastStarted'] . '</b></span>';
                $activeIcon = '<img src="' . $baseImgPath . '/img/on.png">';
                $fastStart = '';
            } else {
                $stopButton = '';
                $activeIcon = '';
                $fastStart = '<span name="' . $item['name'] . '" id="' . $item['id'] . '""><button><img src="' . $baseImgPath . '/img/starticon22.png" class="FastStart" style="opacity:0.6;border:2px solid #A7A7A7;border-radius:10px;padding:3px;"></button></span>';
            }

            if (stripos($item['links'], "f95zone") !== false) {
                $imglink = $baseImgPath . '/img/f95zone.png';
            } else {
                $imglink = $baseImgPath . '/img/linkicon2.png';
            }

            if (!empty($item['links'])) {
                $showlinks = '<a href="' . $item['links'] . '" target="_blank"><img src="' . $imglink . '"></a>';
            } else {
                $showlinks = '';
            }

            ///// Normal Search with Lock Status
            if (isset($_GET['search']) && $_GET['search'] == 'Search') {

                if (isset($_GET['changelink_id']) && $_GET['changelink_id'] == $item['id']) {
                    $changelinks = '<form method="post" action="index.php?searchKey=' . $_GET['searchKey'] . '&search=Search&changelink_id=' . $item['id'] . '"><input type="text" name="linksOption" value="' . $item['links'] . '"> <input type="hidden" name="hiddenid" value="' . $item['id'] . '"><input type="submit" name="linkchange" value="Change!"></form> ' . $linkmsg;
                } else {
                    $changelinks = '<u style="font-size:12px;"><i><a href="index.php?searchKey=' . $_GET['searchKey'] . '&search=Search&changelink_id=' . $item['id'] . '" style="color: inherit;">ChangeLink</a></i></u>';
                }

                if ($item['manageStatus'] == 'lock') {
                    echo $activeIcon . ' <a href="show.php?id=' . $item['id'] . '" style="font-weight:600;color:#5D5D5D;"><img src="img/lock.png"> <strike style="text-decoration-line: line-through;
                    text-decoration-thickness: 0.1rem;">' . $item['name'] . '</strike></a> ' . detailTime($item['timeSpent']) . $stopButton . ' ' . $showlinks . ' ' . $changelinks . '<br>';
                } elseif ($item['manageStatus'] == 'lock&special') {
                    echo $activeIcon . ' <a href="show.php?id=' . $item['id'] . '" style="font-weight:600;color:#5D5D5D;"><img src="img/star.png"> <strike style="text-decoration-line: line-through;
                    text-decoration-thickness: 0.1rem;">' . $item['name'] . '</strike></a> ' . detailTime($item['timeSpent']) . $stopButton . ' ' . $showlinks . ' ' . $changelinks . '<br>';
                } else {
                    echo $activeIcon . ' <a href="show.php?id=' . $item['id'] . '" style="font-weight:600;"><img src="img/active.png"> ' . $item['name'] . '</a> ' . detailTime($item['timeSpent']) . $stopButton . ' ' . $showlinks . ' ' . $changelinks . ' ' . $fastStart . ' <br>';
                }

            }

            // Search inside Manage
            if (isset($_GET['searchManage']) && $_GET['searchManage'] == 'Search') {

                if ($item['timeSpent'] == '') {
                    $equalSign = '';
                } else {
                    $equalSign = ' = ';
                }

                if ($item['manageStatus'] == 'lock') {
                    $doneOption = '<img src="img/lock.png"> <input type="checkbox" name="doneOption[]" value="' . $item['id'] . '">';
                    echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $item['id'] . '"><strike style="color:#4f5051;">' . $item['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($item['timeSpent']) . ' ' . $showlinks . '<br>';

                } elseif ($item['manageStatus'] == 'lock&special') {
                    $doneOption = '<img src="img/star.png"> <input type="checkbox" name="doneOption[]" value="' . $item['id'] . '">';
                    echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $item['id'] . '"><strike style="color:#4f5051;">' . $item['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($item['timeSpent']) . ' ' . $showlinks . '<br>';
                } else {
                    if ($item['statusOn'] == 'on') {
                        $doneOption = '';
                    } else {
                        $doneOption = '<input type="checkbox" name="doneOption[]" value="' . $item['id'] . '">';
                    }
                    echo $doneOption . $activeIcon . ' <span style="font-weight:600;"><a href="show.php?id=' . $item['id'] . '">' . $item['name'] . '</a></span>' . $equalSign . '' . detailTime($item['timeSpent']) . $stopButton . ' ' . $showlinks . '<br>';

                }

            }
        }

    }
    echo '<br>';
}

// Tacker Begining list
if (isset($_GET['select_cat'])) {
    $select = $con->query("SELECT * FROM activity where cat_name='$_GET[select_cat]' order by CONVERT(time_spent,SIGNED) desc");
} elseif (isset($_GET['manage']) && $_GET['manage'] == 'showSpecial') {
    $select = $con->query("SELECT * FROM activity where manage_status = 'lock&special' order by CONVERT(time_spent,SIGNED) desc");
} else {
    $select = $con->query("SELECT * FROM activity order by CONVERT(time_spent,SIGNED) desc");
}

if ($select->rowCount() > 0) {
    $cat = array();

    while ($row = $select->fetch()) {
        $selectTotal = $con->query("SELECT SUM(total_time) as totTime FROM details where activity_name='$row[name]'");
        $rws = $selectTotal->fetch();
        $tottime = $rws['totTime'];

        $lastStarted = gmdate("H:i:s", TimerNow($row['last_started']));

        $catnames = $row['cat_name'];

        if (isset($_GET['manage'])) {
            $selectCats = $con->query("SELECT * FROM activity where cat_name='$catnames'");
        } else {
            $selectCats = $con->query("SELECT * FROM activity where cat_name='$catnames' and manage_status = ''");
        }

        if ($selectCats->rowCount() > 0) {
            $cat[$row['cat_name']][] = ['id' => $row['id'], 'name' => $row['name'], 'statusOn' => $row['status'], 'TimeConv' => $tottime, 'timeSpent' => $row['time_spent'], 'manageStatus' => $row['manage_status'], 'cat_name' => $row['cat_name'], 'lastStarted' => $lastStarted, 'links' => $row['links'], 'last_started' => $row['last_started']];
        }

    }

    foreach ($cat as $key => $value) {
        if (!isset($_GET['searchKey'])) {
            $catColor = $con->query("SELECT * FROM categories where name='$key'");
            $getcolor = $catColor->fetch();
            $selectCats = $con->query("SELECT * FROM activity where cat_name='$key' and manage_status=''");
            if ($selectCats->rowCount() > 0) {
                if (isset($_GET['manage'])) {
                    if (isset($_GET['select_cat'])) {
                        echo '<input type="checkbox" id="select-all"> Select All | <input type="checkbox" id="select-active"> Active Only | <input type="checkbox" id="select-locked"> Locked Only | <input type="checkbox" id="select-special"> Special Only <br><span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                    } else {
                        echo '<span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                    }
                } else {

                    echo '<span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                }
            } else {
                if (isset($_GET['manage'])) {
                    if (isset($_GET['select_cat'])) {
                        echo '<input type="checkbox" id="select-all"> Select All <span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                    } else {
                        echo '<span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                    }
                } else {
                    echo '<span style="font-weight:bold;font-size: 25px;"><a href="index.php?manage&select_cat=' . $key . '" style="color: ' . $getcolor['colorCode'] . '; " class="stroked-text"><u>' . $key . '</u></a></span><br>';
                }
            }

        } else {

        }

        $orderBylock = $con->query("SELECT * FROM activity where cat_name = '$key' order by manage_status='' desc, manage_status='lock&special' desc, manage_status='lock' desc, CONVERT(time_spent,SIGNED) desc");

        while ($fet = $orderBylock->fetch()) {

            $lastStarted = gmdate("H:i:s", TimerNow($fet['last_started']));

            if ($fet['time_spent'] == '') {
                $equalSign = '';
            } else {
                $equalSign = ' = ';
            }

            if ($fet['status'] == 'on') {
                $stopButton = '<form action="index.php" method="post" style="display:inline;">
                <input type="hidden" name="StopId" value="' . $fet['id'] . '">
               <input type="submit" name="stop" value="Stop" class="btn btn-danger btn-sm">
               </form> => <span style="border:2px black solid;margin:5px;padding:2px;"><b id="timerBox">' . $lastStarted . '</b></span>';
                $activeIcon = '<img src="' . $baseImgPath . '/img/on.png">';
            } else {
                $stopButton = '';
                $activeIcon = '';
            }

            // Manage Button Page
            if (isset($_GET['manage'])) {
                $GamesUpdate = '';

                if (stripos($fet['links'], "f95zone") !== false) {
                    $imglink = $baseImgPath . '/img/f95zone.png';
                } else {
                    $imglink = $baseImgPath . '/img/linkicon2.png';
                }

                if ($_GET['manage'] == 'links') {
                    $arrowicon = '<img src="img/right_arrow2.png"> ';
                } else {
                    $arrowicon = '';
                }

                if (!empty($fet['links'])) {
                    $showlinks = $arrowicon . '<a href="' . $fet['links'] . '" target="_blank"><img src="' . $imglink . '"></a>';
                } else {
                    $showlinks = '';
                }

                if ($_GET['manage'] == 'links') {

                    if (isset($_POST['hiddenid']) && $_POST['hiddenid'] == $fet['id']) {
                        $msg = $linkmsg;
                    } else {
                        $msg = '';
                    }

                    if ($fet['manage_status'] == 'lock') {

                        echo ' - <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '"><strike style="color:#4f5051;">' . $fet['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' <form action="index.php?manage=links" method="post"><input type="text" name="linksOption" value="' . $fet['links'] . '"><input type="hidden" name="hiddenid" value="' . $fet['id'] . '">  <input type="submit" name="linkchange" value="Change!" class="btn btn-secondary"> ' . $msg . '</form> ' . $showlinks . ' <br>';

                    } else {

                        echo ' - <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '">' . $fet['name'] . '</a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' <form action="index.php?manage=links" method="post"><input type="text" name="linksOption" value="' . $fet['links'] . '"><input type="hidden" name="hiddenid" value="' . $fet['id'] . '">  <input type="submit" name="linkchange" value="Change!" class="btn btn-secondary"> ' . $msg . '</form> ' . $showlinks . ' <br>';

                    }

                } elseif ($_GET['manage'] == 'Edit') {

                    if (isset($_POST['hiddenid']) && $_POST['hiddenid'] == $fet['id']) {
                        $msg = $linkmsg;
                    } else {
                        $msg = '';
                    }

                    if (isset($_GET['name']) && $_GET['name'] == $fet['name']) {
                        $edit_tracker = ' - <form action="./index.php?manage=Edit" method="post"><input type="text" name="editOption" value="' . $fet['name'] . '" style="font-weight:bold;"><input type="hidden" name="hiddenid" value="' . $fet['id'] . '">  <input type="submit" name="editchange" value="Edit!" class="btn btn-secondary"> ' . $msg . '</form>';
                    } else {
                        $edit_tracker = '<a href="./index.php?manage=Edit&name=' . $fet['name'] . '" class="btn btn-success" style="margin:6px;"><i class="fas fa-pencil-alt"></i></a> - <span style="font-weight:600;"><a href="./show.php?id=' . $fet['id'] . '">' . $fet['name'] . '</a></span>';
                    }

                    echo $edit_tracker . $equalSign . '' . detailTime($fet['time_spent']) . '  <br>';

                } elseif ($_GET['manage'] == 'showSpecial') {
                    if ($fet['manage_status'] == 'lock&special') {
                        $doneOption = '<img src="img/star.png"><input type="checkbox" name="doneOption[]" value="' . $fet['id'] . '|' . $fet['time_spent'] . '" id="checkall" class="checkspecial">';
                        echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '"><strike style="color:#4f5051;">' . $fet['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' ' . $showlinks . '<br>';
                    }
                } else {

                    if ($fet['manage_status'] == 'lock') {
                        $doneOption = '<img src="img/lock.png"><input type="checkbox" name="doneOption[]" value="' . $fet['id'] . '|' . $fet['time_spent'] . '" id="checkall" class="checklocked">';
                        echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '"><strike style="color:#4f5051;">' . $fet['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' ' . $showlinks . '<br>';

                    } elseif ($fet['manage_status'] == 'lock&special') {

                        if (isset($_GET['select_cat']) && isset($_GET['showGamesUpdates'])) {
                            if (!empty($fet['links'])) {
                                $GamesUpdate = '|| <b style="color:green;">Last Update: <font color="red">' . showgamesUpdates($fet['links']) . '</font></b>';
                            }
                            $doneOption = '<img src="img/star.png"><input type="checkbox" name="doneOption[]" value="' . $fet['id'] . '|' . $fet['time_spent'] . '" id="checkall" class="checkspecial">';
                            echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '"><strike style="color:#4f5051;">' . $fet['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' ' . $showlinks . ' ' . $GamesUpdate . ' <br>';
                        } else {
                            $doneOption = '<img src="img/star.png"><input type="checkbox" name="doneOption[]" value="' . $fet['id'] . '|' . $fet['time_spent'] . '" id="checkall" class="checkspecial">';
                            echo $doneOption . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '"><strike style="color:#4f5051;">' . $fet['name'] . '</strike></a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . ' ' . $showlinks . ' <br>';
                        }

                    } else {
                        if ($fet['status'] == 'on') {
                            $doneOption = '';
                        } else {
                            $doneOption = '<input type="checkbox" name="doneOption[]" value="' . $fet['id'] . '|' . $fet['time_spent'] . '" id="checkall" class="checkactive"> ';
                        }

                        if (isset($_GET['select_cat']) && isset($_GET['showGamesUpdates'])) {
                            if (!empty($fet['links'])) {
                                $GamesUpdate = '|| <b style="color:green;">Last Update: <font color="red">' . showgamesUpdates($fet['links']) . '</font></b>';
                            }
                            echo $doneOption . $activeIcon . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '">' . $fet['name'] . '</a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . $stopButton . ' ' . $showlinks . ' ' . $GamesUpdate . ' <br>';
                        } else {
                            echo $doneOption . $activeIcon . ' <span style="font-weight:600;"><a href="show.php?id=' . $fet['id'] . '">' . $fet['name'] . '</a></span>' . $equalSign . '' . detailTime($fet['time_spent']) . $stopButton . ' ' . $showlinks . ' <br>';
                        }

                    }

                }

            }

        }

        foreach ($value as $item) {

            ///// Tracker Main List
            if (!isset($_GET['manage']) and !isset($_GET['searchKey'])) {

                if (stripos($item['links'], "f95zone") !== false) {
                    $imglink = $baseImgPath . '/img/f95zone.png';
                } else {
                    $imglink = $baseImgPath . '/img/linkicon2.png';
                }

                if (!empty($item['links'])) {
                    $showlinks = '<a href="' . $item['links'] . '" target="_blank"><img src="' . $imglink . '"></a>';
                } else {
                    $showlinks = '';
                }

                if ($item['statusOn'] == 'on') {
                    $stopButton = '<span style="border:2px black solid;border-radius:12px;margin:5px;padding: 0 5px;vertical-align: middle;"><b class="timerBox"><span class="timerLive" data-id="' . $item['last_started'] . '"></span></b></span><form action="index.php" method="post" style="display:inline;">
                    <input type="hidden" name="StopId" value="' . $item['id'] . '">
                   <input type="submit" name="stop" value="Stop" class="btn btn-danger btn-sm" style="opacity:0.9;border-radius:12px;">
                   </form>';
                    $activeIcon = '<img src="' . $baseImgPath . '/img/on.png">';
                    $startButton = '';
                } else {
                    $stopButton = '';
                    $activeIcon = '<div class="circle"></div> ';
                    $startButton = '<span name="' . $item['name'] . '" id="' . $item['id'] . '""><button><img src="' . $baseImgPath . '/img/starticon22.png" class="FastStart" style="opacity:0.6;border:2px solid #A7A7A7;border-radius:10px;padding:3px;"></button></span>';
                }

                if ($item['manageStatus'] == '') {
                    echo '<span style="padding:5px;line-height: 1.8em;border-bottom: 1px solid #ECECEC;padding-top: 12px;
                    ">' . $activeIcon . ' <a href="show.php?id=' . $item['id'] . '" style="font-weight:600;"> ' . $item['name'] . '</a>  ' . detailTime($item['timeSpent']) . ' ' . $showlinks . $stopButton . ' ' . $startButton . ' </span><br>';
                }

            }

        }
        echo '<br>';

    }

} else {
    echo 'There are no current activities!';
}

if (isset($_GET['manage'])) {
    if ($_GET['manage'] == 'links' or $_GET['manage'] == 'Edit') {
        echo '';
    } else {
        echo '</form>';
    }

}

?>
 </div>

 <script src="js/sweetalert2.all.min.js"></script>
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
        url: '<?php echo $mainDomainURL;?>/notifications.php',
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


function realtime() {
  const timerLiveElements = document.getElementsByClassName("timerLive");
  for (let i = 0; i < timerLiveElements.length; i++) {
    const xhttp = new XMLHttpRequest();
    xhttp.onload = function() {
      timerLiveElements[i].innerHTML = xhttp.responseText;
    }
    const id = timerLiveElements[i].getAttribute("data-id");
    xhttp.open("GET", `timerLive.php?id=${id}`);
    xhttp.send();
  }
}

function updateRealtime() {
  realtime();
  setTimeout(updateRealtime, 1000);
}

updateRealtime();

 document.addEventListener("DOMContentLoaded", function(event) {
            var scrollpos = localStorage.getItem('scrollpos');
            if (scrollpos) window.scrollTo(0, scrollpos);
        });

        window.onbeforeunload = function(e) {
            localStorage.setItem('scrollpos', window.scrollY);
        };

 $(document).on('click', "#StopAll",function(){
        var name = $(this).parents("span").attr("name");
        var id = $(this).parents("span").attr("id");


        if(confirm('Do you want to stop '+ name +' ?'))
        {
            $.ajax({
               url: 'stopall.php',
               type: 'GET',
               data: {name: name, id: id},
               error: function() {
                  alert('Something is wrong');
               },
               success: function(data) {
                window.location=window.location;
               }
            });
        }

    });



   $(document).delegate(".FastStart", "click", function(){
    var name = $(this).parents("span").attr("name");
    var id = $(this).parents("span").attr("id");

    // Directly call the AJAX request without showing the confirmation
    $.ajax({
        type: 'GET',
        url: 'faststart.php',
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

    document.getElementById('select-all').onclick = function() {
        var checkboxes = document.querySelectorAll('#checkall');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    }

    document.getElementById('select-active').onclick = function() {
        var checkboxes = document.getElementsByClassName("checkactive");
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    }

    document.getElementById('select-locked').onclick = function() {
        var checkboxes = document.getElementsByClassName("checklocked");
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    }

    document.getElementById('select-special').onclick = function() {
        var checkboxes = document.getElementsByClassName("checkspecial");
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    }




 </script>

<?php
// End the output buffer to apply the image path fix
?>
</body>
</html>
