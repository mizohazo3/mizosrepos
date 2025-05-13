<?php
date_default_timezone_set("Africa/Cairo");
require 'db.php';

if (isset($_GET['daytime'])) {
    $daytime = $_GET['daytime'];
    $keyword = $_GET['sidesText'];
    $selectedFeeling = $_GET['selectedFeeling'];
    $susText = $_GET['susText'];

    if(!empty($keyword)){
    $insertDose = $con->prepare("INSERT INTO side_effects (daytime, keyword, my_sus, feelings) VALUES (?, ?, ?, ?)");
    $insertDose->execute([$daytime, $keyword, $susText, $selectedFeeling]);
    }

}
