<?php



$userLogged = '';
if(isset($_SESSION['username'])){
   
  $userLogged = $_SESSION['username'];
    
}elseif(isset($_COOKIE['username'])){

    $userLogged = $_COOKIE['username'];

    setcookie('username', $userLogged, time() + 900);

}else{
    $actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $_SESSION['referLink'] = $actual_link;
	header("Location: ../auth.php");
}
