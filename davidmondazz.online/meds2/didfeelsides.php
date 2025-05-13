<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['sidesText'])) {
    $sidesText = $_GET['sidesText'];
    $feeltime1 = $_GET['feeltime1']; // select start date
    $feeltime2 = $_GET['feeltime2']; // select end date
    $ongoing = $_GET['ongoing'];
    $selectedFeeling = $_GET['selectedFeeling'];
    $my_sus_value = $_GET['my_sus_value'];

    if($ongoing == 'false'){
        $ongoingCheck = 'no';
    }else{
        $ongoingCheck = 'yes';
    }

    $insertDose = $con->prepare("INSERT INTO side_effects (daytime, keyword, ongoing, ended, feelings, my_sus) VALUES (?, ?, ?, ?, ?, ?)");
    $insertDose->execute([$feeltime1, $sidesText, $ongoingCheck, $feeltime2, $selectedFeeling, $my_sus_value]);

}
