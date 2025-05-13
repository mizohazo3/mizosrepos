<?php
session_start();
include 'db.php';
include '../checkSession.php';
include '../func.php';
include 'functions.php';
$msg = '';
date_default_timezone_set("Africa/Cairo");

$dateNow = date('d M, Y h:i:s a');

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;


if (isset($_POST['addnew']) && $_POST['addnew'] == 'AddNew!') {

    if (empty($_POST['storname'])) {
        $msg = '<font color="red">Name is empty!</font>';
        header("Refresh:2; url=index.php");
    } else {
        $name = $_POST['storname'];
        $insert = $con->prepare("INSERT INTO `storage` (`name`,`amount`) VALUES (?, ?)");
        $insert->execute([$name, '0']);
    }

}

if(isset($_GET['add']) OR isset($_GET['update'])){
     $select = $con->query("SELECT * FROM storage");
    
}else{
    $select = $con->query("SELECT * FROM storage where amount != 0");
}
$show = $con->query("SELECT SUM(amount) as totalAmount FROM storage");
$fetch = $show->fetch();

$dollar = $con->query("SELECT * FROM general where name='DollarPrice'");
$fetch2 = $dollar->fetch();

$Addmsg = '';
if (isset($_POST['add']) && $_POST['add'] == 'Add') {
    $amount = $_POST['addAmount'];
    $hiddenId = $_POST['hiddenId'];
    $hiddenName = $_POST['hiddenName'];
    $fromWho = $_POST['fromwho'];
    $hiddenBalance = $_POST['hiddenBalance'];
    $currentPage = $_SERVER['HTTP_REFERER'];

    if (empty($_POST['addAmount'])) {
        $Addmsg = '<font color="red">Enter Amount!</font>';
        header("Refresh:1; url=$currentPage");
    } else {
        $FinalBalance = $amount + $fetch['totalAmount'];
        $add = $con->prepare("UPDATE storage SET `amount`=`amount`+? where id=?");
        $add->execute([$amount, $hiddenId]);
        $insertTrans = $con->prepare("INSERT INTO transactions (storname, amount, thedate, type, `from`, reason, totalRemain) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertTrans->execute([$hiddenName, $amount, $dateNow, 'Add', $fromWho, '', $FinalBalance]);
        header("Location: $currentPage");
    }
}

$Updatemsg = '';
if (isset($_POST['update']) && $_POST['update'] == 'Update') {
    $updateAmount = $_POST['updateAmount'];
    $hiddenBalance = $_POST['hiddenBalance'];
    $hiddenName = $_POST['hiddenName'];
    $hiddenId = $_POST['hiddenId'];
    $currentPage = $_SERVER['HTTP_REFERER'];

    if ($updateAmount == '') {
        $Updatemsg = '<font color="red">Enter Amount!</font>';
        header("Refresh:1; url=$currentPage");
    } elseif ($updateAmount == $hiddenBalance) {
        $Updatemsg = '<font color="red">No changes!</font>';
        header("Refresh:1; url=$currentPage");
    } else {

        if ($updateAmount > $hiddenBalance) {
            $diff = $updateAmount - $hiddenBalance;
            $totalRemain = $fetch['totalAmount'] + $diff;
            $updateStor = $con->prepare("UPDATE storage set amount=? where id=?");
            $updateStor->execute([$updateAmount, $hiddenId]);

            $insertTrans = $con->prepare("INSERT INTO transactions (storname, amount, thedate, type, `from`, reason, totalRemain) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertTrans->execute([$hiddenName, $updateAmount, $dateNow, 'Update', '', '', $totalRemain]);
            header("Location: $currentPage");
        } elseif ($updateAmount < $hiddenBalance) {
            $diff = $hiddenBalance - $updateAmount;
            $totalRemain = $fetch['totalAmount'] - $diff;
            $updateStor = $con->prepare("UPDATE storage set amount=? where id=?");
            $updateStor->execute([$updateAmount, $hiddenId]);

            $insertTrans = $con->prepare("INSERT INTO transactions (storname, amount, thedate, type, `from`, reason, totalRemain) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertTrans->execute([$hiddenName, $updateAmount, $dateNow, 'Update', '', '', $totalRemain]);
            header("Location: $currentPage");

        }

    }

}
$takemsg = '';
if (isset($_POST['take']) && $_POST['take'] == 'Take') {
    $takeAmount = $_POST['takeAmount'];
    $hiddenId = $_POST['hiddenId'];
    $hiddenName = $_POST['hiddenName'];
    $hiddenBalance = $_POST['hiddenBalance'];
    $currentPage = $_SERVER['HTTP_REFERER'];
    $reason = $_POST['reason'];
    $reasonDroplist = $_POST['reasonDroplist'];

    if (empty($takeAmount)) {
        $takemsg = '<font color="#261de4">Enter Amount!</font>';
        header("Refresh:1; url=$currentPage");
    } elseif ($takeAmount > $hiddenBalance) {
        $takemsg = '<font color="#261de4">Insufficient balance!!</font>';
        header("Refresh:2; url=$currentPage");
    } elseif (!empty($reason) && !empty($reasonDroplist)) {
        $takemsg = 'Choose 1 reason Option Only!';
    } else {
        $whatreason = '';
        if (empty($reason) && !empty($reasonDroplist)) {
            $whatreason = $reasonDroplist;
        } elseif (!empty($reason) && empty($reasonDroplist)) {
            $whatreason = $reason;
        }
        $diff = $fetch['totalAmount'] - $takeAmount;
        $updateStorage = $con->prepare("UPDATE storage SET `amount`=`amount`-? where id=?");
        $updateStorage->execute([$takeAmount, $hiddenId]);

        $insertTrans = $con->prepare("INSERT INTO transactions (storname, amount, thedate, type, `from`, reason, totalRemain) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertTrans->execute([$hiddenName, $takeAmount, $dateNow, 'take', '', $whatreason, $diff]);
        header("Location: $currentPage");
    }

}

$moveMsg = '';
if (isset($_POST['move']) && $_POST['move'] == 'Move') {
    $moveAmount = $_POST['moveAmount'];
    $hiddenId = $_POST['hiddenId'];
    $hiddenName = $_POST['hiddenName'];
    $hiddenBalance = $_POST['hiddenBalance'];
    $currentPage = $_SERVER['HTTP_REFERER'];
    $storages = $_POST['storages'];
    $ar = explode(':', $storages);
    $reason = $ar[1];

    if (empty($moveAmount)) {
        $moveMsg = '<font color="#f5f51f">Enter Amount!</font>';
        header("Refresh:1; url=$currentPage");
    } elseif ($moveAmount > $hiddenBalance) {
        $moveMsg = '<font color="#f5f51f">Insufficient balance!!</font>';
        header("Refresh:2; url=$currentPage");
    } else {
        $diff = $fetch['totalAmount'];
        $updateStorage1 = $con->prepare("UPDATE storage SET `amount`=`amount`-? where id=?");
        $updateStorage1->execute([$moveAmount, $hiddenId]);
        $updateStorage2 = $con->prepare("UPDATE storage SET `amount`=`amount`+? where id=?");
        $updateStorage2->execute([$moveAmount, $ar[0]]);

        $insertTrans = $con->prepare("INSERT INTO transactions (storname, amount, thedate, type, `from`, reason, totalRemain) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertTrans->execute([$hiddenName, $moveAmount, $dateNow, 'move', '', $reason, $diff]);
        header("Location: $currentPage");

    }

}

$deleteMsg = '';
if (isset($_POST['delete']) && $_POST['delete'] == 'Delete') {
    if (!empty($_POST['deleteOption'])) {
        $records = count($_POST['deleteOption']);
        foreach ($_POST['deleteOption'] as $check) {

            $updateQ = $con->query("DELETE from transactions where id='$check' ");
            if ($updateQ) {
                $deleteMsg = 'DELETED: ' . $records . ' Records!';
                Header("Refresh:2 url=index.php?delete");
            }

        }

    } else {
        $deleteMsg = '<font color="red">Choose first!</font>';
        Header("Refresh:1 url=index.php?delete");
    }
}

$dollarMsg = '';
if (isset($_POST['updateDollar']) && $_POST['updateDollar'] == 'Update') {
    if (!empty($_POST['DollarPrice'])) {

        $updateValue = $con->prepare("UPDATE general set value=?");
        $updateValue->execute([$_POST['DollarPrice']]);
        $dollarMsg = 'Updated Successfully to ' . $_POST['DollarPrice'] . '';
        header("Refresh:2; url=index.php");
    } else {
        $dollarMsg = 'This cannot be empty!';
    }
}

?>


<html>

<head>
	<title>My Bank</title>
	 <meta name="viewport" content="width=device-width,initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<script src="js/jquery-3.6.0.min.js"></script>
	<style type="text/css">
		body{
			text-align: center;


		}
		table, th, td {
		  border: 1px solid;
		  margin: 0 auto;
		  text-align: center;
		  padding: 10px;
		}
		.spin{
			position: relative;
		}
		.spin:hover {
		transition: .5s;
		transform: rotate(360deg);
		}
		.circle_take{
		    background: #f00;
		    width: 10px;
		    height: 10px;
		    border-radius: 50%;
		    display:inline-block;
		}
		.circle_add{
		    background: #219026;
		    width: 10px;
		    height: 10px;
		    border-radius: 50%;
		    display:inline-block;
		}
		.circle_update{
		    background: #2ec0c4;
		    width: 10px;
		    height: 10px;
		    border-radius: 50%;
		    display:inline-block;
		}
		.circle_move{
		    background: #565656;
		    width: 10px;
		    height: 10px;
		    border-radius: 50%;
		    display:inline-block;
		}


	    .TransContent{
	    	text-align: left;
	    	padding: 4px;
	    }
	    .monthstat{
	    	padding-top: 5;
	    }

       /* Styles for screens that are 768 pixels or wider (desktop) */
      @media (min-width: 768px) {
         #Container {
	    width: 750px ;
	    margin-left: auto ;
	    margin-right: auto ;
	    }
      }

/* Styles for screens that are less than 768 pixels wide (mobile) */
      @media (max-width: 767px) {
         #Container {
        margin-left: auto ;
	    margin-right: auto ;
       padding:11px;
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
<span style="float:right;"><?php echo $dateNow; ?> / Logged as: <b><?=$userLogged;?></b> <a href="../leave.php" class="btn btn-warning btn-sm">Leave!</a> <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a> <div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div><div class="dollarBox">
	<?php
if (isset($_GET['updateDollar'])) {
    echo '	<form action="index.php?updateDollar" method="post">
		<b>$</b> Price <br><input type="text" name="DollarPrice" value="' . $fetch2['value'] . '" style="width:60px;"><br>
		<input type="submit" name="updateDollar" value="Update" class="btn btn-danger btn-sm" style="margin-top:5px;">
	</form> ';
    echo $dollarMsg;
} else {
    echo '<a href="?updateDollar" style="text-decoration: none;"><b>$ = ' . $fetch2['value'] . '</b></a>';
}

?>
	</div></span>
    <div id="Container"><br><br>


<form action="index.php" method="post" style="display: inline;">
		New Storage <input type="text" name="storname">
		<input type="submit" name="addnew" value="AddNew!" class="btn btn-primary btn-sm">
	</form> <a href="index.php?delete" class="btn btn-info btn-sm" style="position: relative;">Delete</a> <a href="index.php" class="btn btn-light btn-sm"><img src="img/refresh.png" class="spin"></a>	<br><?php echo $msg; ?><br><br>

		<div><font size="20" style="color:green;font-weight: bold;"><?php echo number_format($fetch['totalAmount']); ?> L.E</font> ~ $<?php echo round($fetch['totalAmount'] / $fetch2['value'], 2); ?></div>
<br><br>

<table cellspacing="0">
	<?php

$headers = $col = $cols = $col3 = $col4 = $col5 = "";
while ($pickresults = $select->fetch()) {
    $headers .= "<th> {$pickresults['name']} </th>";
    $col .= "<td style='font-size:20px;font-weight:bold;color:#047a7c;'> " . number_format($pickresults['amount']) . " </td>";

    if (isset($_GET['update'])) {
        if (isset($_GET['storname']) && $_GET['storname'] == $pickresults['name']) {
            $cols .= "<td style='background:#91c1fa;'><form action='index.php?update&storname=" . $_GET['storname'] . "' method='post' style='display: inline;'> <img src='img/update_icon.png' style='padding-bottom:5px;'> <input type='text' name='updateAmount' style='width:50%'><br>" . $Updatemsg . " <input type='hidden' name='hiddenId' value='{$pickresults['id']}'> <input type='hidden' name='hiddenName' value='{$pickresults['name']}'><input type='hidden' name='hiddenBalance' value='{$pickresults['amount']}'><br><input type='submit' name='update' value='Update'></form></td>";
        } else {
            $cols .= '<td><a href="?update&storname=' . $pickresults['name'] . '"><button>Update ' . $pickresults['name'] . '</button></a></td>';
        }
    } else {
        $cols = '';
    }

    if (isset($_GET['add'])) {
        if (isset($_GET['storname']) && $_GET['storname'] == $pickresults['name']) {
            $col3 .= "<td style='background:#adca9f;'><form action='index.php?add&storname=" . $_GET['storname'] . "' method='post' style='display: inline;'> <img src='img/add_icon.png' style='padding-bottom:5px;'> <input type='text' name='addAmount' style='height:30px;width:120px;'> L.E<br>" . $Addmsg . "<br> From? <textarea name='fromwho' rows='1' cols='15'></textarea> <input type='hidden' name='hiddenId' value='{$pickresults['id']}'> <input type='hidden' name='hiddenName' value='{$pickresults['name']}'><input type='hidden' name='hiddenBalance' value='{$pickresults['amount']}'> <br><bR><input type='submit' name='add' value='Add'></form></td>";
        } else {
            $col3 .= '<td><a href="?add&storname=' . $pickresults['name'] . '"><button>Add To ' . $pickresults['name'] . '</button></a></td>';
        }
    } else {
        $col3 = '';
    }

    if (isset($_GET['take'])) {
        if (isset($_GET['storname']) && $_GET['storname'] == $pickresults['name']) {

            $col4 .= "<td style='background:#ff9999;'><form action='index.php?take&storname=" . $_GET['storname'] . "' method='post' style='display: inline;'> <img src='img/take_icon.png' style='padding-bottom:5px;'> <input type='text' name='takeAmount' style='width:40%'><br>" . $takemsg . "<br> Reason <textarea name='reason' rows='5' cols='20'></textarea> <input type='hidden' name='hiddenId' value='{$pickresults['id']}'> <input type='hidden' name='hiddenName' value='{$pickresults['name']}'><input type='hidden' name='hiddenBalance' value='{$pickresults['amount']}'><br><br> " . getReasonDropdownList($con) . "<br><br> <input type='submit' name='take' value='Take'></form></td>";
        } else {
            $col4 .= '<td><a href="?take&storname=' . $pickresults['name'] . '"><button>Take From ' . $pickresults['name'] . '</button></a></td>';
        }
    } else {
        $col4 = '';
    }

    if (isset($_GET['move'])) {
        if (isset($_GET['storname']) && $_GET['storname'] == $pickresults['name']) {
            $col5 .= "<td style='background:#565656;'><form action='index.php?move&storname=" . $_GET['storname'] . "' method='post' style='display: inline;'><br> <img src='img/money.png' style='padding-bottom:5px;'> <input type='text' name='moveAmount' style='width:40%'><br>" . $moveMsg . " <div style='padding-top:10px;'><img src='img/move.png' style='padding-bottom:5px;'> ";

            $CurrentStor = $_GET['storname'];
            $storList = $con->query("SELECT * from storage where name != '$CurrentStor'");

            $col5 .= "<select name='storages'>";
            while ($rwlist = $storList->fetch()) {

                $col5 .= " <option value='" . $rwlist['id'] . ":" . $rwlist['name'] . "'>" . $rwlist['name'] . "</option>";

            }
            $col5 .= "</select></div>";

            $col5 .= "<input type='hidden' name='hiddenId' value='{$pickresults['id']}'> <input type='hidden' name='hiddenName' value='{$pickresults['name']}'><input type='hidden' name='hiddenBalance' value='{$pickresults['amount']}'><br> <input type='submit' name='move' value='Move'></form></td>";
        } else {
            $col5 .= '<td><a href="?move&storname=' . $pickresults['name'] . '"><button>Move ' . $pickresults['name'] . '</button></a></td>';
        }
    } else {
        $col5 = '';
    }

}

if (isset($_GET['update'])) {
    echo "Update Storages Balance:";
} elseif (isset($_GET['add'])) {
    echo "Add money:";
} elseif (isset($_GET['take'])) {
    echo 'Take money:';
} elseif (isset($_GET['move'])) {
    echo 'Move money:';
} else {
    echo '';
}
echo "<table><tr>$headers</tr><tr>$col</tr>";
if (isset($_GET['update'])) {
    echo "<tr>$cols</tr>";
} elseif (isset($_GET['add'])) {
    echo "<tr>$col3</tr>";
} elseif (isset($_GET['take'])) {
    echo "<tr>$col4</tr>";
} elseif (isset($_GET['move'])) {
    echo "<tr>$col5</tr>";
} else {
    echo '';
}
echo "</table>";

?>
	</table>

	<?php
if (!isset($_GET['add']) && !isset($_GET['take']) && !isset($_GET['update']) && !isset($_GET['move']) && !isset($_GET['delete'])) {
    echo '<bR><a href="?add" class="btn btn-success btn-sm">Add</a> <a href="?take" class="btn btn-danger btn-sm">Take</a> <a href="?update" class="btn btn-info btn-sm">Update</a> <a href="?move" class="btn btn-secondary btn-sm">Move</a>';
} else {
    echo '';
}
?>

	 <br><br><br>
	<?php

if (isset($_GET['searchKey'])) {
    $searchKey = $_GET['searchKey'];
    $selectdates = $con->query("SELECT thedate, type, storname, amount, totalRemain, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%M %Y') as tdate, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%d %M %Y') as rdate FROM transactions where reason LIKE '%$searchKey%' group by tdate, rdate order by id desc");
} elseif(isset($_GET['showAdded'])){
    $selectdates = $con->query("SELECT thedate, type, storname, amount, totalRemain, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%M %Y') as tdate, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%d %M %Y') as rdate FROM transactions where type='add' group by tdate, rdate order by id desc");
}elseif(isset($_GET['showTaken'])){
    $selectdates = $con->query("SELECT thedate, type, storname, amount, totalRemain, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%M %Y') as tdate, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%d %M %Y') as rdate FROM transactions where type='take' group by tdate, rdate order by id desc");
}else {
    $selectdates = $con->query("SELECT thedate, type, storname, amount, totalRemain, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%M %Y') as tdate, date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%d %M %Y') as rdate FROM transactions group by tdate, rdate order by id desc");
}

while ($rws = $selectdates->fetch()) {

    $tottime = $rws['tdate'];
    $arr2[$tottime][] = $rws['rdate'];

}

if (isset($_GET['delete'])) {
    echo '<form action="index.php?delete" method="post">
						<input type="submit" name="delete" value="Delete" class="btn btn-dark">
							<br>' . $deleteMsg . '<br>';
}

$searchValue = '';
if (isset($_GET['searchKey'])) {
    $searchValue = "value='$_GET[searchKey]'";
}

echo '<form action="index.php" align="center">
<img src="img/search.png" style="padding-bottom:5px;"> <input type="text" name="searchKey" style="height:40px;font-size:20px;text-align:center;" ' . $searchValue . '>
<input type="submit" name="search" value="Search" style="height:40px;font-weight:bold;"> <br><br><a href="index.php?showAdded" style="border:2px grey solid;padding:6px;font-weight:bold;">ShowAdded</a> <a href="index.php?showTaken" style="border:2px grey solid;padding:6px;font-weight:bold;">ShowTaken</a> <a href="index.php?showStat" style="border:2px grey solid;padding:6px;font-weight:bold;">Statistics</a> <br>
</form>';

if(isset($_GET['showStat'])){
    $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
    $sql = $con->query("SELECT SUM(amount) as total_sum FROM transactions WHERE type = 'take' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo'");
    $rows = $sql->fetch();
    $total_Take = $rows['total_sum'];
    $sql2 = $con->query("SELECT SUM(amount) as total_sum2 FROM transactions WHERE type = 'add' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo'");
    $rows2 = $sql2->fetch();
    $total_Add = $rows2['total_sum2'];
    echo 'From <b>'.$oneYearAgo.'</b> To <b>'.date('Y-m-d').'</b>';
    echo '<br>Spent <font color="red"><B>-'.number_format($total_Take).'</B></font> L.E = '.number_format($total_Take/12).' Per Month';
    echo '<br>';
    echo 'Gained <font color="green"><B>+'.number_format($total_Add).'</B></font> L.E = '.number_format($total_Add/12).' Per Month<br>';
}

if(isset($_GET['showTaken'])){
    $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
    $sql = $con->query("SELECT * FROM transactions WHERE type = 'take' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo' ORDER BY CAST(amount AS DECIMAL) DESC");

    $TotalTaken = $con->query("SELECT SUM(amount) as total_sum FROM transactions WHERE type = 'take' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo'");
    $rows = $TotalTaken ->fetch();
    $total_Take = $rows['total_sum'];


    $formattedOneYearAgo = date('d-M-Y', strtotime($oneYearAgo));


    echo '<br>From <b><i>'.$formattedOneYearAgo.'</i></b> To <b><i>'.date('d-M-Y').'</i></b>';
    echo '<Br><br><span style="border:2px grey solid;border-radius:5px;padding:5px;">Total Taken: <font color="red"><b>-'.number_format($total_Take).'</b></font> L.E</span><Br><bR><br>';

    $cumulativeTotal = 0;
    while($rows = $sql->fetch()){
        $amount = floatval($rows['amount']); // Convert the amount to a float

        // Update the cumulative total
        $cumulativeTotal += $amount;

        echo $rows['reason'] . ' = ' . $amount . ' -> <b>Total Taken: <font color="red">-' . number_format($cumulativeTotal) . '</font></b><br>';
    }
   
}

if(isset($_GET['showAdded'])){
    $oneYearAgo = date('Y-m-d', strtotime('-1 year'));
    $sql = $con->query("SELECT * FROM transactions WHERE type = 'add' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo' ORDER BY CAST(amount AS DECIMAL) DESC");

    $TotalAdded = $con->query("SELECT SUM(amount) as total_sum FROM transactions WHERE type = 'add' AND STR_TO_DATE(thedate, '%d %M, %Y') >= '$oneYearAgo'");
    $rows = $TotalAdded ->fetch();
    $total_Add = $rows['total_sum'];

    $formattedOneYearAgo = date('d-M-Y', strtotime($oneYearAgo));
    echo '<br>From <b><i>'.$formattedOneYearAgo.'</i></b> To <b><i>'.date('d-M-Y').'</i></b>';

    echo '<Br><Br><span style="border:2px grey solid;border-radius:5px;padding:5px;">Total Added: <font color="green"><b>+'.number_format($total_Add).'</b></font> L.E</span><Br><bR><br>';

    $cumulativeTotal = 0;
    while($rows = $sql->fetch()){
        $amount = floatval($rows['amount']); // Convert the amount to a float

        // Update the cumulative total
        $cumulativeTotal += $amount;

        echo $rows['from'] . ' = ' . $amount . ' -> <b>Total Added: <font color="green">+' . number_format($cumulativeTotal) . '</font></b><br>';
    }
   
}

foreach ($arr2 as $keys => $values) {
    $string = date('m/Y', strtotime($keys));
    $monthlyadd = $con->query("SELECT SUM(amount) as add_amount from transactions where date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%m/%Y')='$string' and type='Add'");
    $monthlytake = $con->query("SELECT SUM(amount) as take_amount from transactions where date_format(STR_TO_DATE(thedate, '%d %M, %Y'), '%m/%Y')='$string' and type='take'");

    echo '<br><b style="border:2px black solid;border-radius: 25px;margin:5px;padding:5px;background:#e4d21d;color:#373737">' . $string . '</b><br>';
    echo '<div class="monthstat">';
    while ($rn = $monthlyadd->fetch()) {
        echo 'Gain: <span style="color:green;font-weight:bold;">' . number_format($rn['add_amount']) . ' L.E</span>';
    }
    while ($rt = $monthlytake->fetch()) {
        echo ' | Loss: <span style="color:red;font-weight:bold;">' . number_format($rt['take_amount']) . ' L.E</span>';
    }
    echo '</div>';

    foreach ($values as $items) {
        $convertStart = date('d M, Y', strtotime($items));
        $datenow = date('d M, Y');
        $time_left = diffinTime($convertStart, $datenow);
        $days_left = '<span style="font-size:13px;font-style:italic;">since ' . $time_left[3] . ' days</span>';

        if ($time_left[3] > 30) {
            $days_left = '<span style="font-size:13px;font-style:italic;">since ' . round($time_left[3] / 30, 2) . ' months</span>';
        } elseif ($time_left[3] > 365) {
            $days_left = '<span style="font-size:13px;font-style:italic;">since ' . round($time_left[3] / 365, 2) . ' years</span>';
        }

        echo '<div style="border:1px #8a8a86 solid;margin:5px;padding:5px;">';
        echo '<div style="font-size:20px;text-align: left;padding:10px;"><img src="img/arrow.png" style="padding-bottom:5px;"> <b>' . $items . ':</b> ' . $days_left . '</div>';

        $str = date('Y-m-d', strtotime($items));
        if(isset($_GET['searchKey'])){
            $selectTrans = $con->query("SELECT * from transactions where STR_TO_DATE(thedate, '%d %M, %Y')='$str' and reason LIKE '%$searchKey%' order by id desc");
        }elseif(isset($_GET['showAdded'])){
            $selectTrans = $con->query("SELECT * from transactions where STR_TO_DATE(thedate, '%d %M, %Y')='$str' and type='add' order by id desc");
        }elseif(isset($_GET['showTaken'])){
            $selectTrans = $con->query("SELECT * from transactions where STR_TO_DATE(thedate, '%d %M, %Y')='$str' and type='take' order by id desc");
        }else{
            $selectTrans = $con->query("SELECT * from transactions where STR_TO_DATE(thedate, '%d %M, %Y')='$str' order by id desc");
        }
        
        while ($row = $selectTrans->fetch()) {
            $start_date = $row['thedate'];
            $str = str_replace(',', '', $start_date);
            $day = date('d M Y', strtotime($str));
            $timeonly = date('h:i a', strtotime($str));

            $deleteOption = '';
            if (isset($_GET['delete'])) {
                $deleteOption = '<input type="checkbox" name="deleteOption[]" value="' . $row['id'] . '"> ';
            } else {
                $deleteOption = '';
            }

            echo '<div class="TransContent">';
            if ($row['type'] == 'Add') {
                $from = $row['from'];
                echo $deleteOption . '<div class="circle_add"></div> ' . $timeonly . ': <font color="green"><b>+' . $row['amount'] . '</b></font> To ' . $row['storname'] . ', From ' . $from . ' = Total: <b>' . number_format($row['totalRemain']) . '</b><br>';
            } elseif ($row['type'] == 'take') {

                if (empty($row['reason'])) {
                    $reason = '';
                } else {
                    $reason = ' <b>' . $row['reason'].'</b>';
                }
                echo $deleteOption . '<div class="circle_take"></div> ' . $timeonly . ':  <font color="red"><b>-' . $row['amount'] . '</b></font> ' . $reason . ' From ' . $row['storname'] . '  = Remain: <b>' . number_format($row['totalRemain']) . '</b><br>';
            } elseif ($row['type'] == 'Update') {
                echo $deleteOption . '<div class="circle_update"></div> ' . $timeonly . ': Updated: (' . $row['storname'] . ') To ' . $row['amount'] . ' = Remain: <b>' . number_format($row['totalRemain']) . '</b><br>';
            } elseif ($row['type'] == 'move') {
                echo $deleteOption . '<div class="circle_move"></div> ' . $timeonly . ': ' . $row['amount'] . '  (' . $row['storname'] . ')  <img src="img/moving.png">  (' . $row['reason'] . ') = Remain: <b>' . number_format($row['totalRemain']) . '</b><br>';
            }
            echo '</div>';
        }

        echo '</div>';

    }
    echo '<br><br>';

}

if (isset($_GET['delete'])) {
    echo '</form>';
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