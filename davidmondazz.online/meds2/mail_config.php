<?php
/**
 * Mail Configuration File
 * This file contains the email settings for the notification system.
 * Edit this file to configure email sending settings.
 */

// Default email address to receive notifications
$adminEmail = "midomeds2000@gmail.com";

// SMTP Settings
$smtpSettings = [
    'use_smtp' => false, // Set to true to use SMTP instead of PHP's mail() function
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com',
    'password' => 'your-password-or-app-password',
    'encryption' => 'tls', // tls or ssl
    'from_email' => 'noreply@medtracker.com',
    'from_name' => 'MedTracker Notifications'
];

// Logging Settings
$logSettings = [
    'enabled' => true,
    'file' => 'mail_log.txt',
    'level' => 'debug' // debug, info, error
];

/**
 * Enhanced mail sending function that works with various environments
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message HTML message body
 * @param array $attachments Optional file attachments
 * @return bool Success or failure
 */
function sendAppEmail($to, $subject, $message, $attachments = []) {
    global $smtpSettings, $logSettings;
    
    // Default to using PHP's mail() function
    if (!$smtpSettings['use_smtp']) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$smtpSettings['from_name']} <{$smtpSettings['from_email']}>" . "\r\n";
        
        // Record whether native mail function succeeded
        $mailResult = mail($to, $subject, $message, $headers);
        
        // Log mail attempt if logging enabled
        if ($logSettings['enabled']) {
            file_put_contents(
                $logSettings['file'], 
                date('Y-m-d H:i:s') . " - PHP mail(): To: $to, Subject: $subject, Result: " . 
                ($mailResult ? 'SUCCESS' : 'FAILED') . "\n", 
                FILE_APPEND
            );
        }
        
        return $mailResult;
    }
    
    // If SMTP is enabled, you would implement SMTP sending logic here
    // This would typically involve using a library like PHPMailer or Symfony Mailer
    // For now, we'll just return false and log that SMTP is not fully implemented
    
    if ($logSettings['enabled']) {
        file_put_contents(
            $logSettings['file'], 
            date('Y-m-d H:i:s') . " - SMTP sending not implemented. To configure, install PHPMailer and update this function.\n", 
            FILE_APPEND
        );
    }
    
    return false;
}
?> 