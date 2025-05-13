<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer System</title>
    <!-- Preload DSEG7 font for immediate display -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/dseg@0.46.0/fonts/DSEG7-Classic/DSEG7Classic-Regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="style.css">
    <!-- Updated font imports -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet"> <!-- Example fonts -->
    <!-- DSEG 7-Segment Font for digital clock display -->
    <style>
        @font-face {
            font-family: 'DSEG7 Classic';
            src: url('https://cdn.jsdelivr.net/npm/dseg@0.46.0/fonts/DSEG7-Classic/DSEG7Classic-Regular.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
            font-display: block;
        }
        .button-link-nav {
            padding: 8px 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-right: 8px;
        }
        .controls-top button, 
        .controls-top .button-link {
            margin: 0 5px;
        }
        .timer-actions button {
            margin: 0;
        }
        .timer-button.pin-button {
            width: 22px;
            height: 22px;
        }
        /* Add styles for horizontal layout */
        #timer-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
            align-items: flex-start;
        }
        .timer-item {
            flex: 0 0 calc(33.333% - 14px);
            min-width: 280px;
            transition: all 0.3s ease;
        }
        
        /* Glowing Timer Styles */
        .timer-item.running {
            box-shadow: 0 0 15px rgba(76, 175, 80, 0.6);
            animation: pulse 2s infinite;
        }
        
        /* Red timer name in header for running timers */
        .timer-item.running .timer-name-header .timer-name {
            color: #ff0000;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(255, 0, 0, 0.3);
            transition: color 0.3s ease, text-shadow 0.3s ease;
        }
        
        .timer-item.running .timer-display {
            color: #4caf50;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.8);
            animation: glow 1.5s infinite alternate;
        }
        
        .timer-item.running .timer-progress-fill {
            box-shadow: 0 0 8px #4caf50;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 15px rgba(76, 175, 80, 0.6);
            }
            50% {
                box-shadow: 0 0 20px rgba(76, 175, 80, 0.8);
            }
            100% {
                box-shadow: 0 0 15px rgba(76, 175, 80, 0.6);
            }
        }
        
        @keyframes glow {
            from {
                text-shadow: 0 0 5px rgba(76, 175, 80, 0.8), 0 0 10px rgba(76, 175, 80, 0.5);
            }
            to {
                text-shadow: 0 0 10px rgba(76, 175, 80, 0.9), 0 0 20px rgba(76, 175, 80, 0.7), 0 0 30px rgba(76, 175, 80, 0.5);
            }
        }
        
        /* Current earnings indicator */
        .current-earnings-indicator {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(to right, rgba(25, 118, 210, 0.1), rgba(25, 118, 210, 0.2));
            border-left: 4px solid #1976d2;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .current-earnings-indicator.active {
            background: linear-gradient(to right, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            border-left-color: #4caf50;
            box-shadow: 0 2px 10px rgba(76, 175, 80, 0.2);
        }
        
        .earnings-label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }
        
        .earnings-icon {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .earnings-value {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: #1976d2;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .current-earnings-indicator.active .earnings-value {
            color: #4caf50;
        }
        
        .current-earnings-indicator.active .earnings-value {
            position: relative;
        }
        
        .current-earnings-indicator.active .earnings-value::after {
            content: "";
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 100%;
            height: 2px;
            background: rgba(76, 175, 80, 0.5);
            animation: valueUnderline 2s infinite;
        }
        
        @keyframes valueUnderline {
            0% {
                opacity: 0.3;
                width: 0;
                left: 0;
            }
            50% {
                opacity: 1;
                width: 100%;
                left: 0;
            }
            100% {
                opacity: 0.3;
                width: 0;
                left: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        include_once 'timezone_config.php';
        include 'includes/header_nav.php';
        ?>

        <section class="hub">
            <!-- Current Earnings Indicator -->
            <div class="current-earnings-indicator" id="current-earnings-indicator">
                <div class="earnings-label">
                    <span class="earnings-icon">💰</span>
                    <span>Current Earnings</span>
                </div>
                <div class="earnings-value" id="total-gained-value">$0.00</div>
            </div>
            <ul id="timer-list">
                <!-- Timer items will be added here by JavaScript -->
            </ul>
        </section>

    </div>

     <!-- Updated Timer Item Template -->
    <template id="timer-template">
         <li class="timer-item" data-timer-id="">
            <div class="timer-name-header">
                <span class="timer-id">#ID</span>
                <span class="timer-name">Timer Name</span>
                <span class="pin-icon" title="Pin Timer">📌</span> <!-- Added pin icon span -->
            </div>
            <div class="timer-display-wrapper">
                <div class="timer-display">
                    <span class="current-time">00:00:00</span>.<span class="milliseconds">00</span>
                </div>
                <div class="timer-info">
                    <div class="timer-progress-container">
                        <div class="timer-progress-bar">
                            <div class="timer-progress-fill"></div>
                        </div>
                        <span class="timer-progress-text">0%</span>
                    </div>
                    <div class="timer-details">
                        <span class="timer-level-rank">Lvl <span class="timer-level">1</span> (<span class="timer-rank">Novice</span>)</span>
                        <span class="timer-reward-rate">$0.00/hr</span>
                    </div>
                    <div class="session-total">Session: <span class="timer-session-total">$0.00</span></div> <!-- Changed label to Session, JS adds $ -->
                    <div class="total-earned">Total: <span class="timer-total-earned">0.00 h</span></div> <!-- Changed label to Total, JS adds h -->
                    <div class="timer-header">
                        <div class="timer-actions">
                            <div class="timer-start-stop">
                                <button class="timer-button start-button" title="Start Timer">▶</button>
                                <button class="timer-button stop-button" title="Stop Timer" style="display: none;">⏹</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    </template>

    <div id="notification-container"></div>
    <!-- Common utilities (using absolute path) -->
    <script src="./js/common.js"></script>
    <!-- Modal functionality (using absolute path) -->
    <script src="./js/modal.js"></script>
    <!-- Independent stop-all button functionality -->
    <script src="./js/stop_all_button.js"></script>
    <!-- Timer page specific logic (using absolute path) -->
    <script src="./js/timer_page.js"></script>
    <!-- notifications.js removed, functionality assumed in common.js -->
</body>
</html>