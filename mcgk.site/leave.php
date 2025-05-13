
<?php 
ini_set('session.save_path', '/home/mcgkxyz/mcgk.site/temp');
session_start();


session_unset();
session_destroy();

setcookie("username", "", time()-3600);

header("Location: auth.php");

 ?>

