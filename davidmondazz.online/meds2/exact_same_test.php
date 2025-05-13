<?php
    $to      = 'midomeds2000@gmail.com';
    $subject = 'Payment $22.00 Has Recieved Successfully!';
    $message = 'Payment of $22.00 Has Recieved Successfully To Your Bank!';
    $headers = 'From: MCGK Payment <noreply@mcgk.site>'       . "\r\n" .
                 'Reply-To: egyform@gmail.com' . "\r\n" .
                 'X-Mailer: PHP/' . phpversion();

    if ( mail($to,$subject,$message,$headers) ) {
   echo "The email has been sent!";
   } else {
   echo "The email has failed!";
   }
?> 