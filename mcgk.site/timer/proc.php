<?php 


$timeNow = date('d M, y h:i a');



$addTime = 14400;
$random = rand(3300, $addTime);

$current = strtotime(str_replace(',', '', $timeNow));
$futuredate = $current+$random;
echo date('d M, y h:i a', $futuredate);



 ?>