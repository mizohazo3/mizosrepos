<?php
date_default_timezone_set("Africa/Cairo");
include '../db.php';

$showData = $con->query("SELECT * from medlist WHERE sent_email IS NULL and half_life !='' AND lastdose != '' ORDER BY id DESC");
while($fetch = $showData->fetch()){

    $doseDate = $fetch['lastdose'];
    $halflife = round($fetch['half_life']*60*60);
    $fiveHalf = $halflife*5;
    $name = $fetch['name'];
    $id = $fetch['id'];

    // Convert the last dose string to a DateTime object
    $targetDate = DateTime::createFromFormat('d M, Y h:i a', $doseDate);

    // Check if $targetDate is a valid DateTime object
    if (!$targetDate instanceof DateTime) {
        // Log an error if creating the DateTime object fails
        error_log("Failed to create valid DateTime object from '$doseDate'");
        continue; // Skip to the next iteration of the loop
    }

    $currentDate = new DateTime(); 
    $interval = $currentDate->diff($targetDate);

    // Check if $interval is a valid DateInterval object
    if (!$interval instanceof DateInterval) {
        // Log an error if calculating the interval fails
        error_log("Failed to calculate interval between dates");
        continue; // Skip to the next iteration of the loop
    }

    $totalMinutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
    $hours = round($totalMinutes / 60, 2);


    // Half Life Email
    if($hours > $fetch['half_life']){

        // Convert the last dose string to a DateTime object
        $dateTime = DateTime::createFromFormat('d M, Y h:i a', $doseDate);
        $interval = DateInterval::createFromDateString($halflife . ' seconds');
        $dateTime->add($interval);
        $reachedAt = $dateTime->format('d M, Y h:i a');


        $to = 'midowills49@gmail.com';
        $subject = '"'.$name . '" passed half life!';
        $message = 'Hello, ' . $name . ' passed half life at ' . $reachedAt;

        $headers = "From: MCGK Meds <Meds@mcgk.site>\r\n";
        $headers .= "Reply-To: Meds@mcgk.site\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "X-Priority: 1\r\n"; // Set a high priority

        // Add additional headers for email authentication (SPF, DKIM, DMARC)
        $headers .= "Return-Path: Meds@mcgk.site\r\n";
        $headers .= "Sender: Meds@mcgk.site\r\n";
        $headers .= "X-Sender: Meds@mcgk.site\r\n";
        $headers .= "X-Mailer: PHP\r\n";
        $headers .= "X-MSMail-Priority: High\r\n";

        // Send the email
        if(mail($to, $subject, $message, $headers)){
            $update = $con->query("UPDATE medlist set sent_email='1', fivehalf_email=null where id='$id'");
        } else {
            // Log an error if sending the email fails
            error_log("Failed to send email for '$name' passing half life");
        }

    }
    // End of Half Life Email

}

$showData2 = $con->query("SELECT * from medlist WHERE fivehalf_email IS NULL and half_life !='' order by id desc");
while($fetch2 = $showData2->fetch()){

    $doseDate = $fetch2['lastdose'];
    $halflife = round($fetch2['half_life']*60*60);
    $fiveHalf = $halflife*5;
    $name = $fetch2['name'];
    $id = $fetch2['id'];

    // Convert the last dose string to a DateTime object
    $targetDate = DateTime::createFromFormat('d M, Y h:i a', $doseDate); 

    $currentDate = new DateTime(); 
    $interval = $currentDate->diff($targetDate); 
    $totalMinutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
    $hours = round($totalMinutes / 60, 2);

    // Five Half Life Email
    $five_half = $fetch2['half_life']*5;
    if($hours > $five_half){
        $dateTime = DateTime::createFromFormat('d M, Y h:i a', $doseDate);
        $interval = DateInterval::createFromDateString($fiveHalf . ' seconds');
        $dateTime->add($interval);
        $reachedAt = $dateTime->format('d M, Y h:i a');

        $MedLink = 'https://'.$_SERVER['HTTP_HOST'].'/meds/index.php?searchKey=' .$name. '&search=Search';
        $modifiedLink = str_replace(' ', '+', $MedLink);
        $to = 'midowills49@gmail.com';
        $subject = '*'.$name. '* left system!!';
        $message = 'Hello, ' .$name. ' dose from '.$doseDate.' left your system at '.$reachedAt.' <br><br> More at: '.$modifiedLink.'';

        $headers = "From: MCGK Meds <Meds@mcgk.site>\r\n";
        $headers .= "Reply-To: Meds@mcgk.site\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

        // Send the email
        if(mail($to, $subject, $message, $headers)){
            $update = $con->query("UPDATE medlist set fivehalf_email='1' where id='$id'");
        } else {
            // Log an error if sending the email fails
            error_log("Failed to send email for '$name' leaving the system");
        }

    }
    // End of Five Half Life Email

}
?>
