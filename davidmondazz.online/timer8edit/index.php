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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
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
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><a href="index.php" style="text-decoration: none; color: var(--text-primary);">TIMER SYSTEM</a></h1>
            <div class="header-controls">
                <span id="connection-status">Status: Connecting...</span>
                <div class="controls-top">
                    <!-- Page Navigation -->
                    <a href="bank.php" class="button-link button-link-nav" title="Bank Overview">🏦 Bank</a>
                    <a href="marketplace.php" class="button-link button-link-nav" title="Marketplace">🛒 Marketplace</a>

                    <!-- Spacer/Separator -->
                    <span style="border-left: 1px solid var(--border-color); height: 25px; margin: 0 5px;"></span>

                    <!-- Timer Controls -->
                    <button id="stop-all-btn" title="Stop All Timers">⏹</button>
                    <button id="reset-all-btn" title="Reset All Timers">🔄</button>
                    <a href="difficulty.php" id="difficulty-btn" class="button-link" title="Difficulty Settings">⚙️</a>
                    <button id="add-timer-btn" title="Add Timer">➕</button>
                </div>
            </div>
        </header>

        <section class="hub">
            <!-- Hub Title Removed/Integrated -->
            <input type="text" id="search-timers" placeholder="Search timers by name or ID...">

             <div class="global-bank">
                 Bank: $<span id="global-bank-balance">0.0000</span>
             </div>

            <ul id="timer-list">
                <!-- Timer items will be added here by JavaScript -->
            </ul>
        </section>

    </div>

     <!-- Updated Timer Item Template -->
    <template id="timer-template">
         <li class="timer-item" data-timer-id="">
            <div class="timer-display-wrapper">
                <div class="timer-display">
                    <span class="current-time">00:00:00</span>.<span class="milliseconds">00</span>
                </div>
                <div class="accumulated-time">Total: 0.00h</div>
                <div class="total-earned">Total Earned: $<span class="timer-total-earned">0.0000</span></div>
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
                <div class="timer-header">
                    <span class="timer-name-icon"></span>
                    <span class="timer-name">Timer Name</span>
                    <div class="timer-actions">
                        <button class="timer-button start-button" title="Start Timer">▶</button>
                        <button class="timer-button stop-button" title="Stop Timer" style="display: none;">⏹</button>
                    </div>
                </div>
            </div>
        </li>
    </template>

    <div id="notification-container"></div>
    <script src="notifications.js"></script>
    <script src="modal.js"></script>
    <script src="script.js"></script>
</body>
</html>