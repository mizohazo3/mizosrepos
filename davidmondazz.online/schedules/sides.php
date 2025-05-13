<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['daytime'])) {
    $daytime = $_GET['daytime'];
    $keyword = $_GET['sidesText'];
    $ongoing = $_GET['ongoing'];
    $selectedFeeling = $_GET['selectedFeeling'];
    $susText = $_GET['susText'];


    if($ongoing == 'false'){
        $ongoingCheck = 'no';
    }else{
        $ongoingCheck = 'yes';
    }

    $insertDose = $con->prepare("INSERT INTO side_effects (daytime, keyword, ongoing, my_sus, feelings) VALUES (?, ?, ?, ?, ?)");
    $insertDose->execute([$daytime, $keyword, $ongoingCheck, $susText, $selectedFeeling]);

}
