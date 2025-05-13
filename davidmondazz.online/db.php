<?php 

$host = 'localhost';
$user = 'mcgkxyz_masterpop';
$password = 'aA0109587045';
$db_name = 'mcgkxyz_centerbase';

try {
    $connect = new PDO("mysql:host=$host;dbname=$db_name", 
                    $user, $password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
  echo "Connection failed : ". $e->getMessage();
}

?>