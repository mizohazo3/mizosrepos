
<?php 
$errorMsg = '';
if(isset($_GET['id'])){
 echo '';
}else{
    echo '<form action="index.php" method="post" class="formStyle">
    Name: <input type="text" name="name">
    <input type="submit" name="start" value="Start!">
</form>';
       if(isset($_POST['start']) && $_POST['start'] == 'Start!'){
    if(empty($_POST['name'])){
        $errorMsg = 'Please Enter the name!<br><br>';
    }else{
        
    }
}
}


 ?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<style type="text/css">
		body{
		  margin: auto;
		  width: 50%;
		  padding: 10px;
		  font-size: 40 ;
 		 background-color: #9b9b9b;
	
	/* Disable Text Selection */
	-webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    /* Disable Text Selection */
		}
        .formStyle{

            font-size: 30 ;
        }
	</style>


<?php echo '<span class="formStyle" style="font-size:20px;color:red;">'.$errorMsg.'</span>' ?>


<?php 
include '../db.php';


if(isset($_GET['id'])){
	echo ' <div id="buttons"></div>';
    echo '<div id="overlay">
     <img src="../loading.gif" alt="Loading" /><bR><i style="font-size:30px;padding-left:40px;">Loading...</i>
</div>';
}else{
	echo '<title>Pay Timer</title>';
	$select = $con->query("SELECT * from timer where status IN ('on','off') GROUP by id order by id");
    if($select->rowCount() >= 1){
        echo 'Working:<br>';
        while($row = $select->fetch(PDO::FETCH_ASSOC)){
        echo '<a href="http://mcgk.xyz/timer/index.php?id='.$row['id'].'">'.$row['name'].'</a> ';
        if($row['status'] == 'off'){
            echo '<span style="font-size:25px;color:red;">Paused</span>';
        }elseif($row['status'] == 'on'){
            echo '<span style="font-size:25px;color:#21872f;">Running</span>';
        }
        echo '<br>';
    }
    }
echo '<br>';

    $select2 = $con->query("SELECT * from timer where status='done' order by id");
    if($select2->rowCount() >= 1){
        echo 'Done:<br>';
        while($row = $select2->fetch(PDO::FETCH_ASSOC)){
        echo '<a href="http://mcgk.xyz/timer/index.php?id='.$row['id'].'">'.$row['name'].'</a>';
        if($row['pay_status'] == 'paid'){
        	echo '<span style="font-size:20;color:green;"> $'.$row['totalpay'].'</span><i style="font-size:20;"> (Paid)</i>';
        }elseif($row['pay_status'] == 'withdraw'){
            echo '<span style="font-size:20;color:blue;"> $'.$row['totalpay'].'</span><i style="font-size:20;"> (Withdraw Process...)</i>';
        }
        else
        {
        	echo '<span style="font-size:20;color:red;"> $'.$row['totalpay'].'</span><i style="font-size:20;"> (Not Paid)</i>';
        }
        echo '<br>';
    }
    }
	
}



  ?>

  <script src="http://mcgk.xyz/js/jquery.min.js"></script>
<script>

$(document).ready(function(){
 $('#overlay').fadeOut(1000);
function get_data()
{
    jQuery.ajax({
        type: "GET",
        url: "buttons.php?id=<?php echo $_GET['id']; ?>",
        data: "",
        cache: false, 
        async: true,
        success:function(data){
            $("#buttons").html(data);
            return true;
        }
    });
}

setInterval(function(){
    get_data()
}, 1000);

});




  $(function() {

            $(".btn-primary").click(function() {
                var pause_id = $(this).attr("id");
            
                    $.ajax({
                        type : "POST",
                        url : "pause.php", //URL to the delete php script
                        data : {pause_id:pause_id},
                        success : function(data) {
                        }
                    });
                  
                return false;
            });
        });

  $(function() {

            $(".btn-warning").click(function() {
                var id = $(this).attr("id");
            
                    $.ajax({
                        type : "POST",
                        url : "resume.php", //URL to the delete php script
                        data : {id:id},
                        success : function(data) {
                        }
                    });
                  
                return false;
            });
        });



</script>