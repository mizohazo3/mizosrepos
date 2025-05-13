<?php
// First, let's make sure this script won't timeout during download
set_time_limit(300);

// For PHPMailer namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Create a directory for PHPMailer if it doesn't exist
if (!file_exists('phpmailer')) {
    mkdir('phpmailer', 0777, true);
}

// Define the URLs for PHPMailer files
$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
];

// Download PHPMailer files if they don't exist
foreach ($files as $file => $url) {
    if (!file_exists('phpmailer/' . $file)) {
        echo "Downloading $file...<br>";
        $content = @file_get_contents($url);
        if ($content === false) {
            echo "Failed to download $file from $url<br>";
        } else {
            file_put_contents('phpmailer/' . $file, $content);
            echo "Downloaded $file successfully<br>";
        }
    } else {
        echo "$file already exists<br>";
    }
}

// Check if PHPMailer files exist
$phpmailer_exists = true;
foreach ($files as $file => $url) {
    if (!file_exists('phpmailer/' . $file)) {
        $phpmailer_exists = false;
        echo "<p style='color:red'>Missing PHPMailer file: $file</p>";
    }
}

if (!$phpmailer_exists) {
    echo "<p style='color:red'>PHPMailer files are missing. Please download them manually to the 'phpmailer' directory.</p>";
    echo "<p>You can download them from: <a href='https://github.com/PHPMailer/PHPMailer'>https://github.com/PHPMailer/PHPMailer</a></p>";
    die();
}

// Process form submission
$message = '';
$sent = false;

if (isset($_POST['send'])) {
    // Get form data
    $to = $_POST['to'];
    $smtp_host = $_POST['smtp_host'];
    $smtp_port = $_POST['smtp_port'];
    $smtp_user = $_POST['smtp_user'];
    $smtp_pass = $_POST['smtp_pass'];
    $from_email = $_POST['from_email'];
    $from_name = $_POST['from_name'];
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $use_ssl = isset($_POST['use_ssl']) ? $_POST['use_ssl'] : 'tls';
    
    // Include PHPMailer
    require 'phpmailer/PHPMailer.php';
    require 'phpmailer/SMTP.php';
    require 'phpmailer/Exception.php';
    
    // Create an instance of PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        // $mail->isSMTP();                                            // Send using SMTP
        // $mail->Host       = $smtp_host;                            // Set the SMTP server to send through
        // $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        // $mail->Username   = $smtp_user;                     // SMTP username
        // $mail->Password   = $smtp_pass;                               // SMTP password
        // if ($use_ssl == 'ssl') {
        //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable SSL encryption
        // } else {
        //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
        // }
        // $mail->Port       = $smtp_port;                           // TCP port to connect to
        
        // // Disable certificate verification - use only if you trust the server
        // $mail->SMTPOptions = [
        //     'ssl' => [
        //         'verify_peer' => false,
        //         'verify_peer_name' => false,
        //         'allow_self_signed' => true
        //     ]
        // ];

        // Use PHP mail() function
        $mail->isMail();
    
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);                                     // Add a recipient
    
        // Content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
    
        // Save output to variable
        ob_start();
        $result = $mail->send();
        $debug_output = ob_get_clean();
        
        $message = "<div style='background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px;'>
                    <h3>Email sent successfully!</h3>
                    <p>The email was accepted by the mail server. Check your inbox for the test message.</p>
                    <h4>SMTP Debug Output:</h4>
                    <pre style='background: #f8f9fa; padding: 10px; overflow: auto;'>" . htmlentities($debug_output) . "</pre>
                    </div>";
        $sent = true;
    } catch (Exception $e) {
        $debug_output = ob_get_clean();
        $message = "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px;'>
                    <h3>Email could not be sent</h3>
                    <p>Error: " . $mail->ErrorInfo . "</p>
                    <h4>SMTP Debug Output:</h4>
                    <pre style='background: #f8f9fa; padding: 10px; overflow: auto;'>" . htmlentities($debug_output) . "</pre>
                    </div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHPMailer SMTP Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; 
        }
        textarea { height: 100px; }
        .btn { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background-color: #45a049; }
        .info-box { background-color: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .warning { background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>PHPMailer SMTP Test</h1>
    
    <div class="info-box">
        <p><strong>This tool tests email sending using PHPMailer with SMTP.</strong></p>
        <p>If direct PHP mail() isn't working, using a third-party SMTP server is a reliable alternative.</p>
        <p>You'll need SMTP credentials from a provider like Gmail, Outlook, SendGrid, etc.</p>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <div class="container">
        <form method="post">
            <div class="form-group">
                <label for="to">To Email:</label>
                <input type="email" id="to" name="to" value="midomeds2000@gmail.com" required>
            </div>
            
            <h3>SMTP Settings</h3>
            <div class="form-group">
                <label for="smtp_host">SMTP Host:</label>
                <input type="text" id="smtp_host" name="smtp_host" value="smtp.gmail.com" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_port">SMTP Port:</label>
                <input type="text" id="smtp_port" name="smtp_port" value="587" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_user">SMTP Username (Email):</label>
                <input type="email" id="smtp_user" name="smtp_user" placeholder="your-email@gmail.com" required>
            </div>
            
            <div class="form-group">
                <label for="smtp_pass">SMTP Password (App Password):</label>
                <input type="password" id="smtp_pass" name="smtp_pass" placeholder="Your app password" required>
                <small>For Gmail, use an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> if you have 2FA enabled</small>
            </div>
            
            <div class="form-group">
                <label for="use_ssl">Encryption Type:</label>
                <select id="use_ssl" name="use_ssl">
                    <option value="tls">TLS (Port 587)</option>
                    <option value="ssl">SSL (Port 465)</option>
                </select>
            </div>
            
            <h3>Email Content</h3>
            <div class="form-group">
                <label for="from_email">From Email:</label>
                <input type="email" id="from_email" name="from_email" placeholder="your-email@gmail.com" required>
                <small>Usually must match your SMTP username for most providers</small>
            </div>
            
            <div class="form-group">
                <label for="from_name">From Name:</label>
                <input type="text" id="from_name" name="from_name" value="MedTracker System" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" value="MedTracker Test Email via SMTP" required>
            </div>
            
            <div class="form-group">
                <label for="body">Email Body (HTML allowed):</label>
                <textarea id="body" name="body" required><h1>Test Email from MedTracker</h1>
<p>This is a test email sent via PHPMailer using SMTP.</p>
<p>If you received this, your SMTP configuration is working correctly.</p>
</textarea>
            </div>
            
            <div class="form-group">
                <input type="submit" name="send" value="Send Test Email" class="btn">
            </div>
        </form>
    </div>
    
    <div class="warning" style="margin-top: 20px;">
        <h3>Important Notes:</h3>
        <ul>
            <li>For Gmail, you must use an <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a> if you have two-factor authentication enabled.</li>
            <li>Make sure your email provider allows SMTP access for your account.</li>
            <li>Some hosting providers block outgoing connections on ports 25, 465, or 587.</li>
            <li>The "From Email" usually needs to match the SMTP username for authentication.</li>
        </ul>
    </div>
</body>
</html> 