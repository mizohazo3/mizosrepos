<?php
/**
 * setup_cron.php
 * 
 * This script helps set up the cron job for balance updating and allows running
 * a test update to see if it works properly.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Set timezone
date_default_timezone_set("Africa/Cairo");

// Check if user requested to run a test update
$testRun = isset($_GET['test_run']) && $_GET['test_run'] == 1;
$message = '';

if ($testRun) {
    // Run the update script and capture output
    ob_start();
    include 'update_balance_cron.php';
    $output = ob_get_clean();
    
    // Format the output for display
    $outputLines = explode("\n", $output);
    $formattedOutput = '';
    foreach ($outputLines as $line) {
        if (trim($line) != '') {
            $formattedOutput .= htmlspecialchars($line) . '<br>';
        }
    }
    
    $message = '<div class="alert alert-info"><strong>Test Run Output:</strong><br>' . $formattedOutput . '</div>';
}

// Get the absolute path to the cron script
$scriptPath = realpath('update_balance_cron.php');
$cronCommand = '';

// Generate the appropriate cron command based on server OS
if (stripos(PHP_OS, 'WIN') === 0) {
    // Windows scheduled task format
    $cronCommand = 'schtasks /create /tn "UpdateTimerBalance" /tr "C:\\path\\to\\php.exe ' . $scriptPath . '" /sc minute /mo 5';
} else {
    // Unix/Linux cron format
    $phpPath = '/usr/bin/php'; // Adjust if needed
    $cronCommand = "*/5 * * * * $phpPath $scriptPath >> " . dirname($scriptPath) . "/logs/cron_log.txt 2>&1";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Balance Cron Job Setup</title>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        .code-block {
            font-family: monospace;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }
        .instructions {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .step {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Balance Cron Job Setup</h1>
        
        <?php if (!empty($message)) echo $message; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>About</h2>
            </div>
            <div class="card-body">
                <p>This script helps you set up a cron job that will regularly update the balance in the <code>user_progress</code> table based on the total sum from <code>timer_logs</code>, <code>purchase_logs</code>, and <code>on_the_note</code> tables.</p>
                <p>The balance will be automatically calculated and kept consistent across all applications using the timer database.</p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Test the Balance Update</h2>
            </div>
            <div class="card-body">
                <p>Click the button below to run the balance update script once and see if it works correctly:</p>
                <a href="setup_cron.php?test_run=1" class="btn btn-primary">Run Test Update</a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Setting up the Cron Job</h2>
            </div>
            <div class="card-body">
                <div class="instructions">
                    <h4>For Linux/Unix servers:</h4>
                    <div class="step">1. Access your server via SSH or your hosting control panel's cron job manager.</div>
                    <div class="step">2. Add the following line to your crontab (this will run the update every 5 minutes):</div>
                    <div class="code-block"><?php echo htmlspecialchars($cronCommand); ?></div>
                </div>
                
                <div class="instructions">
                    <h4>For cPanel Hosting:</h4>
                    <div class="step">1. Log in to your cPanel account.</div>
                    <div class="step">2. Find and click on "Cron Jobs" under the "Advanced" section.</div>
                    <div class="step">3. Set the time interval to "Every 5 minutes".</div>
                    <div class="step">4. In the command field, enter:</div>
                    <div class="code-block"><?php echo htmlspecialchars("/usr/bin/php " . $scriptPath); ?></div>
                </div>
                
                <div class="instructions">
                    <h4>For Windows servers:</h4>
                    <div class="step">1. Open Command Prompt as Administrator.</div>
                    <div class="step">2. Run this command (adjust the PHP path if needed):</div>
                    <div class="code-block"><?php echo htmlspecialchars($cronCommand); ?></div>
                </div>
                
                <div class="alert alert-warning">
                    <strong>Note:</strong> Make sure the PHP CLI is installed and available at the specified path. You may need to adjust the PHP path in the commands above.
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Checking the Logs</h2>
            </div>
            <div class="card-body">
                <p>The cron job will create log entries in:</p>
                <div class="code-block"><?php echo dirname($scriptPath); ?>/logs/balance_cron.log</div>
                <p>You can check these logs to verify that the cron job is running properly and to diagnose any issues.</p>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">Back to Medications</a>
        </div>
    </div>
</body>
</html> 