<?php 

include '../db.php';

/*
$update = mysqli_query($con, "UPDATE timer set status='on', resumed='".$_POST['resume_date']."', paused_time=paused_time+'".$_POST['pause_time']."' where id=".$_POST['resume_id']."");
*/

$resume = $con->prepare("UPDATE timer set status='on', resumed=?, paused_time=paused_time+? where id=?");
$resume->execute([$_POST['resume_date'], $_POST['pause_time'], $_POST['resume_id']]);


 ?>