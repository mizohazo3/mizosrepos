<?php 
include('db.php');

$getDay = $_GET['daydate'];
$getName = $_GET['name'];
$str = date('Y-m-d', strtotime($getDay));


$select = $con->query("SELECT SUM(total_time) as totTime, activity_name FROM details where STR_TO_DATE(start_date, '%d %M, %Y')='$str' and activity_name='$getName'");
$row = $select->fetch(PDO::FETCH_ASSOC);

echo '<div class="custom">'.$row['activity_name'].'<br>';


$time_spent = '';
 			if(!empty($row['totTime'])){
 				if($row['totTime'] <= 59){
 				$time_spent = '('.$row['totTime'].' sec)';
 			}
 			elseif($row['totTime'] < 3600){
 				$time_spent = '('.round(($row['totTime'] / 60), 2).' mins)';
 			}
 			elseif ($row['totTime'] > 3600) {
 				$time_spent = '('.round($row['totTime'] / 3600, 2).' hrs)';
 			}
 			}
 			else
 			{
 				$time_spent = '';
 			}
 			$str2 = date('d M, Y', strtotime($getDay));
 			echo $str2.' -> '.$time_spent.'<div>';
 			

 		?>

 		<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
 		<style type="text/css">
 			.custom{
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  margin: 0;
  font-size: 30px;
  text-align: center;
 			}
 		</style>
 		<br>
 		<a href="<?php echo $_SERVER['HTTP_REFERER']; ?>" class="btn btn-primary">Go Back</a>