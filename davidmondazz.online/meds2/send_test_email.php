<?php
/**
 * Simple Email Test Script
 * 
 * This script sends a test email to verify if the server can send emails.
 */

// Email configuration
$to_email = 'midomeds2000@gmail.com';
$from_email = 'test@' . $_SERVER['HTTP_HOST']; // Uses server hostname in from address
$subject = 'Test Email from ' . $_SERVER['HTTP_HOST'] . ' - ' . date('Y-m-d H:i:s');
$message = "
<html>
<head>
    <title>Email Test</title>
</head>
<body>
    <h2>This is a test email</h2>
    <p>If you're receiving this email, then your server can send emails.</p>
    <p>Sent on: " . date('Y-m-d H:i:s') . "</p>
    <p>Server: " . $_SERVER['HTTP_HOST'] . "</p>
</body>
</html>
";

// Email headers
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $from_email" . "\r\n";
$headers .= "Reply-To: $from_email" . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Try to send email
$mail_result = mail($to_email, $subject, $message, $headers);

// Display result
echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>";
echo "<h1 style='color: #333;'>Email Test Results</h1>";

if ($mail_result) {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<strong>✅ Success!</strong> Email was sent to $to_email.";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<strong>❌ Failed!</strong> Email could not be sent.";
    echo "</div>";
    
    $error = error_get_last();
    if ($error) {
        echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<strong>Error Details:</strong> " . $error['message'];
        echo "</div>";
    }
}

// Debug information
echo "<h2 style='color: #333; margin-top: 30px;'>Debug Information</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";
echo "<strong>To:</strong> $to_email<br>";
echo "<strong>From:</strong> $from_email<br>";
echo "<strong>Subject:</strong> $subject<br>";
echo "<strong>Server:</strong> " . $_SERVER['SERVER_NAME'] . "<br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Mail Function Return:</strong> " . ($mail_result ? 'TRUE' : 'FALSE') . "<br>";
echo "</div>";

// Additional mail server test
echo "<h2 style='color: #333; margin-top: 30px;'>Mail Server Connection Test</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace;'>";

// Test if we can connect to localhost on port 25 (typical SMTP port)
$connection = @fsockopen('localhost', 25, $errno, $errstr, 5);
if ($connection) {
    echo "<strong style='color: #155724;'>✅ SMTP Connection:</strong> Connection to localhost:25 successful<br>";
    fclose($connection);
} else {
    echo "<strong style='color: #721c24;'>❌ SMTP Connection:</strong> Could not connect to localhost:25 - $errstr ($errno)<br>";
}

echo "</div>";
echo "</div>";
?>