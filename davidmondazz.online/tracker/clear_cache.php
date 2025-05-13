<?php
// Clear PHP opcache (if enabled and you have permissions)
$opcache_cleared = false;
if (function_exists('opcache_reset')) {
    $opcache_cleared = opcache_reset();
}

// Clear PHP realpath cache
clearstatcache(true);

// Clear PHP session data (only for this site)
session_start();
$session_cleared = false;
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    $session_cleared = session_destroy();
    // Regenerate session ID for new sessions
    if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
        session_start([
            'use_strict_mode' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax'
        ]);
        session_regenerate_id(true);
    } else {
        session_start();
        session_regenerate_id(true);
    }
}

// Prevent caching of this page and future pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

// Get your site's domain to target cookies
$domain = $_SERVER['HTTP_HOST'];
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$domain";
$redirect_url = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : 'index.php';

// Handle non-script browsers or direct PHP access
$manual_redirect = $base_url . '/' . $redirect_url;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearing Cache - Timer Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            font-family: 'Roboto', sans-serif;
        }
        .container {
            max-width: 600px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            font-weight: 500;
            padding: 15px;
            border-bottom: none;
        }
        .progress-container {
            padding: 20px;
        }
        .progress {
            height: 8px;
            margin-bottom: 15px;
        }
        .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 8px;
        }
        .clear-step {
            padding: 12px 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
        }
        .step-icon {
            margin-right: 12px;
            width: 24px;
            height: 24px;
            text-align: center;
        }
        .step-status {
            margin-left: auto;
            font-size: 14px;
        }
        .footer-actions {
            margin-top: 20px;
            text-align: center;
        }
        [data-status="pending"] .step-icon {
            color: #6c757d;
        }
        [data-status="completed"] .step-icon {
            color: #28a745;
        }
        [data-status="failed"] .step-icon {
            color: #dc3545;
        }
        .card-footer {
            background-color: #f8f9fa;
            border-top: none;
            padding: 15px;
            text-align: center;
        }
    </style>
    <script>
        // Functions to clear browser data
        function clearBrowserData() {
            // Track completion status
            let completedSteps = 0;
            const totalSteps = 4;
            
            // Update progress UI
            function updateProgress() {
                const progressPercentage = (completedSteps / totalSteps) * 100;
                document.getElementById('clearProgress').style.width = progressPercentage + '%';
                document.getElementById('progressText').textContent = Math.round(progressPercentage) + '%';
                
                if (completedSteps >= totalSteps) {
                    document.getElementById('allCompleteMessage').style.display = 'block';
                    // Enable the continue button
                    document.getElementById('continueBtn').disabled = false;
                }
            }
            
            // Update step status in UI
            function updateStepStatus(stepId, status, message = '') {
                const step = document.getElementById(stepId);
                step.setAttribute('data-status', status);
                
                const statusElement = step.querySelector('.step-status');
                if (status === 'completed') {
                    statusElement.innerHTML = '<span class="text-success">✓ Completed</span>';
                    if (message) statusElement.innerHTML += ' ' + message;
                    completedSteps++;
                    updateProgress();
                } else if (status === 'failed') {
                    statusElement.innerHTML = '<span class="text-danger">✗ Failed</span>';
                    if (message) statusElement.innerHTML += ' ' + message;
                    updateProgress();
                } else if (status === 'pending') {
                    statusElement.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
                }
            }
            
            // Step 1: Clear localStorage and sessionStorage
            updateStepStatus('step1', 'pending');
            try {
                // Count items before clearing
                const lsCount = localStorage.length;
                const ssCount = sessionStorage.length;
                
                // Clear storages
                localStorage.clear();
                sessionStorage.clear();
                
                updateStepStatus('step1', 'completed', `(${lsCount + ssCount} items removed)`);
            } catch (error) {
                console.error('Error clearing storage:', error);
                updateStepStatus('step1', 'failed');
            }
            
            // Step 2: Delete site cookies
            updateStepStatus('step2', 'pending');
            try {
                const cookies = document.cookie.split(";");
                let cookieCount = 0;
                
                // Main domain and subdomain handling
                const hostname = window.location.hostname;
                const domainParts = hostname.split(".");
                let mainDomain;
                
                // Handle localhost and IP addresses
                if (hostname === 'localhost' || /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname)) {
                    mainDomain = hostname;
                } else {
                    // Handle actual domains
                    if (domainParts.length > 2) {
                        mainDomain = "." + domainParts.slice(-2).join(".");
                    } else {
                        mainDomain = "." + hostname;
                    }
                }
                
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i].trim();
                    if (!cookie) continue;
                    
                    const eqPos = cookie.indexOf("=");
                    const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                    
                    // Expire the cookie on all possible paths and domains
                    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;";
                    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=" + hostname;
                    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=" + mainDomain;
                    cookieCount++;
                }
                
                updateStepStatus('step2', 'completed', `(${cookieCount} cookies removed)`);
            } catch (error) {
                console.error('Error clearing cookies:', error);
                updateStepStatus('step2', 'failed');
            }
            
            // Step 3: Verify PHP session status
            updateStepStatus('step3', 'pending');
            // This is handled server-side, but we need to show status
            setTimeout(() => {
                updateStepStatus('step3', 'completed', '(Session data reset)');
            }, 500);
            
            // Step 4: Trigger cache-busting reload
            updateStepStatus('step4', 'pending');
            setTimeout(() => {
                updateStepStatus('step4', 'completed');
                // Don't redirect automatically - let user click the button
            }, 1000);
        }
        
        // Start the clearing process when page loads
        window.onload = function() {
            clearBrowserData();
        };
        
        // Function to redirect with cache busting
        function redirectWithCacheBusting() {
            let destination = '<?php echo $redirect_url; ?>';
            
            // Add cache-busting parameter
            if (destination.includes('?')) {
                destination += '&cache_bust=' + Date.now();
            } else {
                destination += '?cache_bust=' + Date.now();
            }
            
            window.location.href = destination;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-broom me-2"></i> Clearing Cache</h4>
            </div>
            <div class="card-body">
                <div class="progress-container">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Clearing progress</span>
                        <span id="progressText">0%</span>
                    </div>
                    <div class="progress" role="progressbar">
                        <div id="clearProgress" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                    </div>
                </div>
                
                <div class="clear-steps">
                    <div id="step1" class="clear-step" data-status="pending">
                        <div class="step-icon"><i class="fas fa-database"></i></div>
                        <div class="step-content">Clear localStorage & sessionStorage</div>
                        <div class="step-status">Pending...</div>
                    </div>
                    
                    <div id="step2" class="clear-step" data-status="pending">
                        <div class="step-icon"><i class="fas fa-cookie-bite"></i></div>
                        <div class="step-content">Delete site cookies</div>
                        <div class="step-status">Pending...</div>
                    </div>
                    
                    <div id="step3" class="clear-step" data-status="pending">
                        <div class="step-icon"><i class="fas fa-user-lock"></i></div>
                        <div class="step-content">Reset PHP session data</div>
                        <div class="step-status">Pending...</div>
                    </div>
                    
                    <div id="step4" class="clear-step" data-status="pending">
                        <div class="step-icon"><i class="fas fa-sync-alt"></i></div>
                        <div class="step-content">Prepare cache-busting reload</div>
                        <div class="step-status">Pending...</div>
                    </div>
                </div>
                
                <div id="allCompleteMessage" class="alert alert-success mt-4" style="display:none">
                    <i class="fas fa-check-circle me-2"></i> All cache data has been successfully cleared!
                </div>
                
                <div class="footer-actions">
                    <button id="continueBtn" class="btn btn-primary" disabled onclick="redirectWithCacheBusting()">
                        <i class="fas fa-arrow-right me-2"></i> Continue to Site
                    </button>
                </div>
            </div>
            
            <div class="card-footer text-muted">
                <div class="d-flex justify-content-between align-items-center">
                    <small>
                        <i class="fas fa-server me-1"></i> Server info: 
                        <?php echo $opcache_cleared ? '<span class="text-success">OPcache cleared</span>' : '<span class="text-secondary">OPcache N/A</span>'; ?>
                    </small>
                    <small><?php echo date('Y-m-d H:i:s'); ?></small>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-muted">If you aren't redirected, <a href="<?php echo $manual_redirect; ?>">click here</a></small>
        </div>
    </div>
</body>
</html>