<?php
date_default_timezone_set("Africa/Cairo");
$datenow = date("d M, Y h:i a");
include '../db.php';
include '../../db.php';

$showData = $con->query("SELECT * from tasklist WHERE sent_email IS NULL and default_duration !='' AND lasttask != '' and email_notify = 'yes' and status = 'open' ORDER BY id DESC");
while($fetch = $showData->fetch()){

    $taskDate = $fetch['lasttask'];
    $taskDuration = round($fetch['default_duration']*60*60);
    $name = $fetch['name'];
    $id = $fetch['id'];

    // Convert the last task string to a DateTime object
    $targetDate = DateTime::createFromFormat('d M, Y h:i a', $taskDate);

    // Check if $targetDate is a valid DateTime object
    if (!$targetDate instanceof DateTime) {
        // Log an error if creating the DateTime object fails
        error_log("Failed to create valid DateTime object from '$taskDate'");
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


    // duration Email
    if($totalMinutes > $fetch['default_duration']){

        // Convert the last task string to a DateTime object
        $dateTime = DateTime::createFromFormat('d M, Y h:i a', $taskDate);
        $interval = DateInterval::createFromDateString($taskDuration . ' seconds');
        $dateTime->add($interval);
        $reachedAt = $dateTime->format('d M, Y h:i a');


        $to = 'midowills49@gmail.com';
        $subject = '"'.$name . '" is now available!';
        $message = 'Hello, ' . $name . ' is now available at ' . $reachedAt;

        $headers = "From: MCGK Schedules <Schedules@mcgk.site>\r\n";
        $headers .= "Reply-To: Schedules@mcgk.site\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "X-Priority: 1\r\n"; // Set a high priority

        // Add additional headers for email authentication (SPF, DKIM, DMARC)
        $headers .= "Return-Path: Schedules@mcgk.site\r\n";
        $headers .= "Sender: Schedules@mcgk.site\r\n";
        $headers .= "X-Sender: Schedules@mcgk.site\r\n";
        $headers .= "X-Mailer: PHP\r\n";
        $headers .= "X-MSMail-Priority: High\r\n";

        $subject2 = ''.$name . ' is now available!';
        // Send the email
        if(mail($to, $subject, $message, $headers)){
            $update = $con->query("UPDATE tasklist set sent_email='1' where id='$id'");
            $notification = $connect->query("INSERT INTO notifications (message, date_time, notif_type, title, page) VALUES ('$subject2', '$datenow', 'Schedules', '$name', 'index.php?index')");
        } 

    }
    // End of duration Email

}


?>
