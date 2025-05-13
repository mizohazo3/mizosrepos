<?php 

include '../db.php';


////timer update////
$ReqWithdraw = $con->prepare("UPDATE timer set pay_status='withdraw' where id=? ");
$ReqWithdraw->execute([$_POST['with_id']]);



/// Start Random Probability ///
function fun() {
    $num = mt_rand(1, 100);


    if ($num > 0 && $num <= 50) {
        $return = mt_rand(1, 45).' = 50%'; 
    }elseif ($num > 50 && $num <= 85){
        $return = mt_rand(46, 90).' = 35%'; 
    }
    elseif ($num > 85 && $num <= 95){
        $return = mt_rand(91, 120).' = 10%'; 
    }else{
        $return = mt_rand(150, 240).' = 5%'; 
    }
   return $return;
   
}

/// END Random Probability ///


$processing_time = fun() * 60;


////bank Receive////
$updateBank = $con->prepare("INSERT INTO banking (`timer_name`,`pay`,`status`,`intiated_time`,`proccessing_time`,`paid_time`,`timer_id`) VALUES (? ,? ,'',? , ?,'', ?) ");

$updateBank->execute([$_POST[timer_name], $_POST[total_received], $_POST[intiated_time], $processing_time, $_POST[with_id]]);

 ?>