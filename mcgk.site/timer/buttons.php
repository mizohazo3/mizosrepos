<html>

<head>
	<link rel="stylesheet" href="http://mcgk.xyz/css/bootstrap.min.css">
<meta name=viewport content="width=device-width, initial-scale=1">
<style type="text/css">

@import url('https://fonts.googleapis.com/css?family=Montserrat&display=swap');



body {
	background-color: #f4f4f4;
	font-family: 'Montserrat', sans-serif;
	font-size: 30px;
	text-align: left;
}

@media (max-width: 700px) {

      body {
     background-color: #f4f4f4;
	font-family: 'Montserrat', sans-serif;
	font-size: 30px;
	text-align: left;
       }

}


.btn-primary{
  padding: 5px 10px;
    font-size: 18px;
    border-radius: 6px;
}
.btn-danger{
  padding: 5px 10px;
    font-size: 18px;
    border-radius: 6px;
}
.btn-warning{
  padding: 5px 10px;
    font-size: 18px;
    border-radius: 6px;
}

</style>
</head>

<body>
<?php 
include '../db.php';
date_default_timezone_set("Africa/Cairo");

$select = $con->query("SELECT * from timer where id='".$_GET['id']."'");
$row = $select->fetch(PDO::FETCH_ASSOC);

$PayRate = $row['PayRate'];
$datenow = date('Y-m-d H:i:s');
$dateStarted = $row['started'];
$paused = $row['paused'];
$stopped = $row['finished_date'];

echo '<title>'.$row['name'].'</title>';

////////////Status ON////////////

if($row['status'] == 'on'){

$timeFirst  = strtotime(''.$dateStarted.'');
$timeSecond = strtotime(''.$datenow.'');
$differenceInSeconds = ($timeSecond - $timeFirst) - $row['paused_time'];
$hours = ($differenceInSeconds/60)/60;


echo '<b style="color:#1b74ae;">Running...</b> ';
echo '"'.$row['name'].'"';
echo '<br>';


$minsec = gmdate("i:s", $differenceInSeconds); 
$hour = (gmdate("d", $differenceInSeconds)-1)*24 + gmdate("H", $differenceInSeconds);


$time = $hour . ':' . $minsec; // 56:12:12
echo $timeRed = '<font color="#ac1916">'.$time.'</font>';


echo '<br>';
$timerName = $row['name'];
$profit = round($hours * $PayRate, 2);
echo '<br>';
echo '<b style="color:green;">$'.number_format($profit, 2, '.', ',').'</b><br>';


echo '<button id="'.$row['id'].'" class="btn btn-primary">Pause</button> ';
echo '<button id="'.$row['id'].'" class="btn btn-danger">Stop</button>';
}
////////////Status ON////////////

////////////Status OFF////////////
else if($row['status'] == 'off')

{
$timeFirst  = strtotime(''.$dateStarted.'');
$timeSecond = strtotime(''.$paused.'');
$differenceInSeconds = ($timeSecond - $timeFirst) - $row['paused_time'];
$hours = ($differenceInSeconds/60)/60;


echo '<b style="color:red;">Paused...</b> ';
echo '"'.$row['name'].'"';
echo '<br>';

$minsec = gmdate("i:s", $differenceInSeconds); 
$hour = (gmdate("d", $differenceInSeconds)-1)*24 + gmdate("H", $differenceInSeconds);

echo $time = $hour . ':' . $minsec; // 56:12:12


echo '<br>';
$timerName = $row['name'];
$profit = round($hours * $PayRate, 2);
echo '<br>';
echo '<b style="color:green;">$'.number_format($profit, 2, '.', ',').'</b><br>';

echo '<button id="'.$row['id'].'" class="btn btn-warning">Resume</button> ';
echo '<button id="'.$row['id'].'" class="btn btn-danger">Stop</button>';
}
////////////Status OFF////////////

////////////Status DONE////////////
else

{
$timeFirst  = strtotime(''.$dateStarted.'');
$timeSecond = strtotime(''.$stopped.'');
$differenceInSeconds = ($timeSecond - $timeFirst) - $row['paused_time'];
$hours = ($differenceInSeconds/60)/60;
echo '<div class="container">';
echo '<center><b style="color:#1c6b7c;font-size:30px;">(Finished)</b> </center>';
echo '<b>'.$row['name'].'</b><br> ';

echo '<font color="#6d600e">';

$minsec = gmdate("i:s", $differenceInSeconds); 
$hours = (gmdate("d", $differenceInSeconds)-1)*24 + gmdate("H", $differenceInSeconds);

echo $time = $hours . ':' . $minsec; // 56:12:12

echo '</font>';
echo '<br>';

$timerName = $row['name'];
$profit = $row['totalpay'];
$totalpay = $row['totalpay'];
echo '<br>';
echo '<b style="color:green;">$'.number_format($totalpay, 2, '.', ',').'</b> -> <a href="http://mcgk.xyz/banking/" target="_blank">Bank</a><br> ';

   if($row['pay_status'] == 'no'){
                    echo '<button id="'.$row['id'].'" class="btn btn-info">Withdraw</button></center><br>
                    <i style="font-size:20px;color:red;">Not Paid</i>';
                    }
                    else
                    {
              $bank = $con->query("SELECT * from banking where timer_id='".$row['id']."'");  
              $info = $bank->fetch();        
                    if($info['status'] == 'received'){
                         echo '</center>
                         <br><span class="successPaid"><img src="../banking/paid.png"><i style="color:#b2790d;font-size:20px;"> <b>Paid on '.$info['paid_time'].'</b></span></i><br><br>';
                    }else{

                    // calculate Progressbar Percentage
                    $datenow = strtotime(date('Y-m-d H:i:s'));
                    $intiated = $info['intiated_time'];
					$mins = $info['proccessing_time'];

					$convertCurrent = strtotime($intiated);
					$futuredate = $convertCurrent+$mins;
					$diff = (($futuredate - $datenow) * 100) / $info['proccessing_time'] ;
					$finalPercent = round(100 - $diff);

					 // calculate Progressbar Percentage
                     echo '</center><br>
                    <i style="color:#737141;">Payment Processing...</i>';

                    echo '

<div class="progress" style="width: 40%;">

 <div class="progress-bar" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width:'; 
if ($finalPercent >= 100){
	echo 100;
}else{
	echo $finalPercent;
}
  echo '%; background-color:#1582b5 !important;">
 
 '; 
if ($finalPercent >= 100){
	echo 'Arriving Soon...';
}else{
	echo $finalPercent.'%';
}
echo '
 
 </div>
 </div>

';

                    }
}
echo '</div>';
}
////////////Status DONE////////////

if($row['status'] =='done'){
echo '<br>';
echo '<div class="container">';
echo '<b style="font-size:25px;"><u>PayRate:</u> $'.number_format($PayRate,2).'</b><br>';
echo 'Started on: '. date('d/m/Y,  h:i a', strtotime($row['started'])).'<bR>';
echo 'Finished on: '. date('d/m/Y,  h:i a', strtotime($row['finished_date'])).'';
echo '</div>';
}else{
echo '<div class="container"><b style="font-size:25px;">PayRate: $'.number_format($PayRate,2).'</b></div>';
}

///////////////////Paused Time////////////////
$dateNow = date('Y-m-d H:i:s');
$datePaused = $row['paused'];
echo '<br>';

$time1  = strtotime(''.$datePaused.'');
$time2 = strtotime(''.$datenow.'');
$pause_time = ($time2 - $time1);
////////////////////Paused Time////////////////


 ?>



<script>
	
	  $(function() {

            $(".btn-primary").click(function() {
                var pause_id = $(this).attr("id");
                var pause_date = "<?php echo $datenow; ?>";
                    $.ajax({
                        type : "POST",
                        url : "pause.php", //URL to the delete php script
                        data : {pause_id:pause_id, pause_date:pause_date},
                        success : function(data) {
                        }
                    });
                  
                return false;
            });
        });

  $(function() {

            $(".btn-warning").click(function() {
                var resume_id = $(this).attr("id");
            	var resume_date = "<?php echo $datenow; ?>";
            	var pause_time = "<?php echo $pause_time; ?>";
                    $.ajax({
                        type : "POST",
                        url : "resume.php", //URL to the delete php script
                        data : {resume_id:resume_id, resume_date:resume_date, pause_time:pause_time},
                        success : function(data) {
                        }
                    });
                  
                return false;
            });
        });

    $(function() {

            $(".btn-danger").click(function() {
                var stop_id = $(this).attr("id");
                var finish_date = "<?php echo $datenow; ?>";
                var totalpay = "<?php echo $profit; ?>";
                var payStatus = 'no';
                if(confirm("Are you sure you want to stop this timer ?")){
                    $.ajax({
                        type : "POST",
                        url : "stop.php", //URL to the delete php script
                        data : {stop_id:stop_id, finish_date:finish_date, totalpay:totalpay, payStatus:payStatus},
                        success : function(data) {
                        }
                    });
                  }
                else {
                return false;
            }
            });
        });

  $(function() {

            $(".btn-info").click(function() {
                var with_id = $(this).attr("id");
                var timer_name = "<?php echo $timerName; ?>";
                var intiated_time = "<?php echo $datenow; ?>";
                var total_received = "<?php if($row['status'] == 'done'){echo $totalpay;} ?>";
                if(confirm("Are you sure you want to withdraw $" + total_received +" now ?")){
                    $.ajax({
                        type : "POST",
                        url : "withdraw.php", //URL to the delete php script
                        data : {with_id:with_id, timer_name:timer_name, intiated_time:intiated_time, total_received:total_received},
                        success : function(data) {
                        }
                    });
                  }
                  else{
                     return false;
                  }
               
            });
        });

	

</script>

<script src="http://mcgk.xyz/js/jquery.min.js"></script>
</body>
</html>