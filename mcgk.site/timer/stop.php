<?php 

include '../db.php';

   $stop = $con->prepare("UPDATE timer set status='done', paused='' , finished_date=? , totalPay=? , pay_status=? where id=?");
   $stop->execute([$_POST['finish_date'], $_POST['totalpay'], $_POST['payStatus'], $_POST['stop_id']]);


/*
 $update = mysqli_query($con, "UPDATE timer set status='done', paused='".$_POST['pause_date']."', finished_date='".$_POST['finish_date']."', totalPay='".$_POST['totalpay']."', pay_status='".$_POST['payStatus']."' where id=".$_POST['stop_id']."") or die(mysqli_error($con));
 */




 ?>
