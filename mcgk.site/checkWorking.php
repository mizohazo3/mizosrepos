
<style>
.flashing-image {
    position: relative; /* Ensure the container is positioned relative */
    width: 50px; /* Adjust the width and height as needed */
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto; /* Center the element horizontally */
}

.flashing-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.number {
    position: absolute; /* Position the number relative to the flashing image */
    bottom: 5px;
    right: 5px;
    background-color: red;
    color: #fff;
    padding: 5px 10px;
    border-radius: 50px;
    font-size: 12px; /* Adjust the font size as needed */
    z-index: 1; /* Ensure the number appears on top of the image */
}

@keyframes flash {
  0%, 50%, 100% {
    opacity: 3; /* Fully visible */
  }
  25%, 75% {
    opacity: 0; /* Invisible */
  }
}
</style>

<?php


$host = 'localhost';
$user = 'mcgkxyz_masterpop';
$password = 'aA0109587045';
$db_name = 'mcgkxyz_tracker';

try {
    $connectCheck = new PDO("mysql:host=$host;dbname=$db_name", 
                    $user, $password);
    $connectCheck->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
  echo "Connection failed : ". $e->getMessage();
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;


function checkWorking (){
    global $connectCheck;
    global $mainDomainURL; 

    $checkWorking = $connectCheck->query("SELECT * FROM activity where status = 'on'");
    $workingCount = $checkWorking->rowCount();
    if ($checkWorking->rowCount() > 0) {

   echo '<div class="flashing-image">
   <a href="'.$mainDomainURL.'/tracker"><img src="../tracker/img/icon.png" alt="Flashing Image">
   <span class="number">'.$checkWorking->rowCount().'</span></a>
   </div>';
      
    }

}

echo checkWorking ();

?>
