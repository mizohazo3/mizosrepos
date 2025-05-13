<?php 

include '../db.php';


/*
 $update = mysqli_query($con, "UPDATE timer set status='off', paused='$_POST[pause_date]' where id='$_POST[pause_id]' ") or die(mysqli_error($con));
 */


$pause = $con->prepare("UPDATE timer set status='off', paused=? where id=? ");
$pause->execute([$_POST[pause_date], $_POST[pause_id]]);


 ?>
