<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Timer XP Cron Job</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            padding: 50px 0;
        }
        
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .setup-title {
            color: #0d6efd;
            font-weight: 700;
        }
        
        .setup-subtitle {
            color: #6c757d;
            font-weight: 300;
        }
        
        .cron-steps {
            margin-bottom: 30px;
        }
        
        .step {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #0d6efd;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .command-box {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            position: relative;
        }
        
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            border-radius: 3px;
            padding: 3px 8px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .copy-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .alert-info {
            background-color: #cce5ff;
            border-color: #b8daff;
            color: #004085;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn-action {
            padding: 10px 30px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 10px;
        }
        
        .test-result {
            display: none;
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        
        .result-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="setup-header">
                <h1 class="setup-title">Setup Timer XP Background Process</h1>
                <p class="setup-subtitle">Configure your server to update timer XP even when browsers are closed</p>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Why is this needed?</strong> To ensure your timers continue to gain XP even when your browser is closed or you're not visiting the site, we need to set up a scheduled task (cron job) on your server.
            </div>
            
            <div class="cron-steps">
                <div class="step">
                    <h3><span class="step-number">1</span> Test the script manually</h3>
                    <p>First, let's make sure the XP update script works properly on your server:</p>
                    <div class="command-box">
                        php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php
                        <button class="copy-btn" data-command="php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php">Copy</button>
                    </div>
                    <button id="test-script-btn" class="btn btn-primary">
                        <i class="fas fa-play-circle me-2"></i>Test Script Now
                    </button>
                    <div id="test-result" class="test-result"></div>
                </div>
                
                <div class="step">
                    <h3><span class="step-number">2</span> Access your server's cron configuration</h3>
                    <p>You need to access your server's cron configuration. This depends on your hosting:</p>
                    <ul>
                        <li><strong>cPanel:</strong> Look for "Cron Jobs" in your cPanel dashboard</li>
                        <li><strong>Plesk:</strong> Go to "Scheduled Tasks" in your Plesk panel</li>
                        <li><strong>Linux server:</strong> Use the terminal and type <code>crontab -e</code></li>
                        <li><strong>Windows server:</strong> Use the Task Scheduler</li>
                        <li><strong>Shared hosting:</strong> Check your hosting provider's documentation or contact support</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3><span class="step-number">3</span> Add the cron job</h3>
                    <p>Add a new cron job/scheduled task with the following settings:</p>
                    
                    <h5>Option 1: For Linux servers (using crontab)</h5>
                    <p>Add this line to run the script every minute:</p>
                    <div class="command-box">
                        * * * * * php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php
                        <button class="copy-btn" data-command="* * * * * php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php">Copy</button>
                    </div>
                    
                    <h5>Option 2: For cPanel</h5>
                    <p>Set the following:</p>
                    <ul>
                        <li><strong>Minute:</strong> */1 (or select "Every Minute")</li>
                        <li><strong>Hour:</strong> * (or select "Every Hour")</li>
                        <li><strong>Day:</strong> * (or select "Every Day")</li>
                        <li><strong>Month:</strong> * (or select "Every Month")</li>
                        <li><strong>Weekday:</strong> * (or select "Every Weekday")</li>
                        <li><strong>Command:</strong> <code>php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php</code></li>
                    </ul>
                    
                    <h5>Option 3: For Windows Task Scheduler</h5>
                    <p>Create a new basic task with these settings:</p>
                    <ul>
                        <li><strong>Trigger:</strong> Daily, recur every 1 minute</li>
                        <li><strong>Action:</strong> Start a program</li>
                        <li><strong>Program/script:</strong> <code>php.exe</code></li>
                        <li><strong>Arguments:</strong> <code>"<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/update_timer_xp.php"</code></li>
                    </ul>
                </div>
                
                <div class="step">
                    <h3><span class="step-number">4</span> Verify it's working</h3>
                    <p>To verify the cron job is working:</p>
                    <ol>
                        <li>Start a timer through the web interface</li>
                        <li>Wait a few minutes</li>
                        <li>Check the <code>xp_updates.log</code> file in your root directory to see if entries are being added</li>
                        <li>Refresh the timer page to see if XP has increased</li>
                    </ol>
                </div>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Note:</strong> On some shared hosting environments, cron jobs may not run exactly every minute. If your hosting limits cron frequency, adjust accordingly (e.g., run every 5 minutes).
            </div>
            
            <div class="action-buttons">
                <a href="verify_leveling.php" class="btn btn-primary btn-action">
                    <i class="fas fa-check-circle me-2"></i>Verify Leveling System
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-action">
                    <i class="fas fa-home me-2"></i>Return to Timer Hub
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Copy command to clipboard
            document.querySelectorAll('.copy-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const command = this.getAttribute('data-command');
                    navigator.clipboard.writeText(command).then(() => {
                        const originalText = this.textContent;
                        this.textContent = 'Copied!';
                        setTimeout(() => {
                            this.textContent = originalText;
                        }, 2000);
                    });
                });
            });
            
            // Test script functionality
            document.getElementById('test-script-btn').addEventListener('click', function() {
                const resultDiv = document.getElementById('test-result');
                const button = this;
                
                // Show loading state
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
                resultDiv.style.display = 'none';
                
                // Call the update script
                fetch('update_timer_xp.php')
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        }
                        throw new Error('Failed to run script. HTTP status: ' + response.status);
                    })
                    .then(() => {
                        // Check log file to see if it ran
                        return fetch('check_xp_log.php');
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        }
                        throw new Error('Failed to check log file');
                    })
                    .then(data => {
                        // Show result
                        resultDiv.style.display = 'block';
                        resultDiv.className = 'test-result result-success';
                        resultDiv.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Success!</strong> The XP update script ran successfully.
                            <p class="mt-2 mb-0">Log entries: ${data.entries} (newest: "${data.latest}")</p>
                        `;
                    })
                    .catch(error => {
                        // Show error
                        resultDiv.style.display = 'block';
                        resultDiv.className = 'test-result result-error';
                        resultDiv.innerHTML = `
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Error:</strong> ${error.message}
                        `;
                    })
                    .finally(() => {
                        // Reset button
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-play-circle me-2"></i>Test Script Again';
                    });
            });
        });
    </script>
</body>
</html> 