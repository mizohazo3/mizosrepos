<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_POST['sidesText2'])) {
    $sidesText2 = $_POST['sidesText2'];
    $feeltime1 = $_POST['feeltime1'];
    $feeltime2 = $_POST['feeltime2'];
    $ongoing = $_POST['ongoing'];


    if($ongoing == 'false'){
        $ongoingCheck = 'no';
    }else{
        $ongoingCheck = 'yes';
    }
   

    $insertDose = $con->prepare("INSERT INTO side_effects (daytime, keyword, ongoing, ended) VALUES (?, ?, ?, ?)");
    $insertDose->execute([$feeltime1, $sidesText2, $ongoingCheck, $feeltime2]);

}
