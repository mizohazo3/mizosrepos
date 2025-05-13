<?php

include_once 'timezone_config.php';
// --- Fetch Base Level Data (Include reward rate) ---
$baseLevelsData = [];
$errorMessage = null;
try {
    require_once 'api/db.php';
    // *** Fetch reward_rate_per_hour ***
    $stmt = $pdo->query("SELECT level, hours_required, rank_name, reward_rate_per_hour FROM levels ORDER BY level ASC");
    $baseLevelsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = "Could not load base level data. Estimates cannot be displayed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Difficulty Settings & Level Estimates</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* === Keep ALL existing CSS styles === */
        /* ... */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #1e1e1e; color: #e0e0e0; padding: 20px; box-sizing: border-box; }
        .content-wrapper { max-width: 800px; margin: 30px auto; }
        .settings-container { background-color: #2a2a2a; padding: 30px 40px; border-radius: 10px; border: 1px solid #444; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); width: 100%; box-sizing: border-box; margin-bottom: 30px; }
        h1 { color: #bb86fc; text-align: center; margin-top: 0; margin-bottom: 15px; font-weight: 500; }
        p { text-align: center; color: #aaa; margin-bottom: 30px; font-size: 0.95em; }
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; color: #bbb; font-weight: 500; font-size: 0.9em; }
        select { width: 100%; padding: 12px 15px; border-radius: 5px; border: 1px solid #555; background-color: #333; color: #e0e0e0; font-size: 1em; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23BBB%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.4-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 15px center; background-size: 10px auto; cursor: pointer; }
        select:focus { outline: none; border-color: #bb86fc; box-shadow: 0 0 0 2px rgba(187, 134, 252, 0.3); }
        button { background-color: #03dac6; color: #121212; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-size: 1.05em; font-weight: 700; display: block; width: 100%; transition: background-color 0.2s ease, transform 0.1s ease; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        button:hover { background-color: #01afa0; } button:active { transform: scale(0.98); }
        #message { margin-top: 20px; padding: 12px; border-radius: 5px; text-align: center; font-weight: 500; font-size: 0.95em; opacity: 0; transition: opacity 0.3s ease; min-height: 40px; box-sizing: border-box; }
        .message-success { background-color: rgba(3, 218, 198, 0.2); color: #03dac6; border: 1px solid #03dac6; } .message-error { background-color: rgba(207, 102, 121, 0.2); color: #cf6679; border: 1px solid #cf6679; }
        .back-link-container { text-align: center; margin-top: 25px; } a { color: #03dac6; text-decoration: none; display: inline-block; font-size: 0.9em; } a:hover { text-decoration: underline; }
        .level-estimates { background-color: #2a2a2a; padding: 20px 30px; border-radius: 10px; border: 1px solid #444; margin-top: 30px; }
        .level-estimates h2 { color: #bb86fc; text-align: center; margin-top: 0; margin-bottom: 20px; font-weight: 500; }
        .level-estimates-list { list-style: none; padding: 10px; margin: 0; max-height: 400px; overflow-y: auto; border: 1px solid #383838; border-radius: 5px; background-color: #1f1f1f; }
        .level-estimates-list::-webkit-scrollbar { width: 8px; } .level-estimates-list::-webkit-scrollbar-track { background: #2a2a2a; border-radius: 4px; } .level-estimates-list::-webkit-scrollbar-thumb { background-color: #555; border-radius: 4px; border: 2px solid #2a2a2a; }
        .level-estimates-list li { display: flex; justify-content: space-between; align-items: center; /* Align items vertically */ padding: 8px 5px; border-bottom: 1px solid #383838; font-size: 0.9em; color: #ccc; gap: 10px; /* Add gap between elements */ }
        .level-estimates-list li:last-child { border-bottom: none; }
        .level-estimates-list .level-info { flex-grow: 1; /* Allow level info to take more space */ }
        .level-estimates-list .level-number { font-weight: bold; color: #e0e0e0; display: inline-block; min-width: 35px; /* Keep alignment */ }
        .level-estimates-list .level-rank { font-style: italic; color: #aaa; margin-left: 5px; }
        /* *** NEW STYLE for Rate *** */
        .level-estimates-list .level-rate {
            font-size: 0.9em;
            color: #fdd835; /* Yellow for rate */
            font-family: 'Roboto Mono', monospace;
            white-space: nowrap; /* Prevent wrapping */
            margin-left: auto; /* Push rate towards the right */
            padding-left: 10px; /* Space before rate */
        }
        .level-estimates-list .level-time {
            font-weight: bold;
            font-family: 'Roboto Mono', monospace;
            color: #76ff03; /* Green for time */
            text-align: right;
            min-width: 60px; /* Ensure space for time */
            white-space: nowrap;
        }
        
        /* Timer management section styles */
        .timer-management {
            background-color: #2a2a2a;
            padding: 20px 30px;
            border-radius: 10px;
            border: 1px solid #444;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .timer-management h2 {
            color: #bb86fc;
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .timer-management-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .timer-management-buttons button {
            flex: 1;
            font-size: 0.95em;
            padding: 10px 15px;
        }
        
        #add-timer-btn {
            background-color: #03dac6;
        }
        
        #add-timer-btn:hover {
            background-color: #01afa0;
        }
        
        #reset-all-btn {
            background-color: #cf6679;
            color: white;
        }
        
        #reset-all-btn:hover {
            background-color: #b55560;
        }
        
        @media (max-width: 600px) { /* ... keep existing media queries ... */ }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header_nav.php'; ?>
    
        <div class="content-wrapper">
            <div class="settings-container">
                <h1>Difficulty Settings</h1>
                <p>Adjusting difficulty changes the hours required to reach each level.</p>
                <div class="form-group">
                    <label for="difficulty-select">Select Difficulty:</label>
                    <select id="difficulty-select">
                        <option value="0.00012">Test: ~2 Sec Lvl 2</option>
                        <option value="0.00028">Test: ~5 Sec Lvl 2</option>
                        <option value="0.001">Near Instant (0.1% hours)</option>
                        <option value="0.005">Ludicrous Easy (0.5% hours)</option>
                        <option value="0.01">Hyper Easy (1% hours required)</option>
                        <option value="0.03">Super Easy (3% hours required)</option>
                        <option value="0.05">Extreme Easy (5% hours required)</option>
                        <option value="0.10">Very Easy (10% hours required)</option>
                        <option value="0.25">Easy (25% hours required)</option>
                        <option value="0.50">Medium Easy (50% hours required)</option>
                        <option value="0.75">Slightly Easy (75% hours required)</option>
                        <option value="1.0">Normal (100% hours required)</option>
                        <option value="1.25">Hard (125% hours required)</option>
                        <option value="1.5">Very Hard (150% hours required)</option>
                    </select>
                </div>
                <button id="save-btn">Save Settings</button>
                <div id="message" style="opacity: 0;"></div>
                <div class="back-link-container"> <a href="index.php">Back to Timer App</a> </div>
             </div>
             
             <!-- Timer Management Section -->
             <div class="timer-management">
                <h2>Timer Management</h2>
                <p>Create new timers or reset all existing timers and progress.</p>
                <div class="timer-management-buttons">
                    <button id="add-timer-btn" title="Add a new timer">➕ Add New Timer</button>
                    <button id="reset-all-btn" title="Reset all timers and progress">🔄 Reset All Progress</button>
                </div>
             </div>

             <!-- USD to EGP Exchange Rate Section -->
             <div class="settings-container">
                <h1>USD to EGP Exchange Rate</h1>
                <p>Set the exchange rate for converting EGP to USD in medical pricing</p>
                <div class="form-group">
                    <label for="usdegp-rate">1 USD = ? EGP:</label>
                    <input type="number" id="usdegp-rate" min="1" step="0.01" placeholder="Enter exchange rate" style="width: 100%; padding: 12px 15px; border-radius: 5px; border: 1px solid #555; background-color: #333; color: #e0e0e0; font-size: 1em;">
                </div>
                <button id="save-rate-btn">Save Exchange Rate</button>
                <div id="rate-message" style="opacity: 0;"></div>
             </div>

             <!-- Level Estimates Section -->
            <?php if (!empty($baseLevelsData)): ?>
                <div class="level-estimates">
                    <h2>Level Time Estimates (<span id="current-difficulty-display">Normal</span>)</h2>
                    <ul id="level-estimates-list" class="level-estimates-list">
                        <li>Loading estimates...</li>
                    </ul>
                </div>
            <?php else: ?>
                 <div class="level-estimates"> <h2 style="color: #cf6679;">Error</h2> <p style="color: #cf6679;"><?php echo htmlspecialchars($errorMessage ?: 'Could not display level estimates.'); ?></p> </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="notification-container"></div>
    <!-- Common utilities (using absolute path) -->
    <script src="./js/common.js"></script>
    <!-- Modal functionality (using absolute path) -->
    <script src="./js/modal.js"></script>
    <!-- script.js and notifications.js removed -->

<script src="js/timer_page.js"></script>
    <script>
        const baseLevelsData = <?php echo !empty($baseLevelsData) ? json_encode($baseLevelsData) : '[]'; ?>;
        const didLevelLoadFail = <?php echo empty($baseLevelsData) ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('difficulty-select');
            const saveBtn = document.getElementById('save-btn');
            const messageDiv = document.getElementById('message');
            const estimatesList = document.getElementById('level-estimates-list');
            const currentDifficultyDisplay = document.getElementById('current-difficulty-display');

            if (!select || !saveBtn || !messageDiv || !estimatesList || !currentDifficultyDisplay) { console.error("Essential elements missing!"); return; }

                    // --- Helper Function to Format Hours (HOURS ONLY version) ---
                    function formatEstimatedHours(totalHours) {
                if (isNaN(totalHours) || totalHours < 0) return "0.0h"; // Return hours format

                const negligibleThreshold = 0.00005; // ~ 1/5th of a second
                if (totalHours < negligibleThreshold) return "< 0.1h"; // Show negligible time

                // Display hours with 1 decimal place, unless it's a whole number then 0 decimals.
                if (totalHours % 1 === 0) {
                    // It's a whole number
                    return `${totalHours.toFixed(0)}h`;
                } else {
                    // Has decimal places, show 1
                    return `${totalHours.toFixed(1)}h`;
                }
                // Alternative: Always show 1 decimal place
                // return `${totalHours.toFixed(1)}h`;
            }

            // --- Function to Populate Level Estimates (MODIFIED) ---
            function displayLevelEstimates(multiplier, levelsData) {
                if (didLevelLoadFail || !estimatesList) return;
                estimatesList.innerHTML = ''; if(!levelsData || levelsData.length === 0){ estimatesList.innerHTML = '<li>No level data.</li>'; return; }

                levelsData.forEach(level => {
                    const baseHours = parseFloat(level.hours_required);
                    const effectiveHours = (level.level == 1) ? 0 : (baseHours * parseFloat(multiplier));
                    const formattedTime = formatEstimatedHours(effectiveHours);
                    // *** Get and format the reward rate ***
                    const rewardRate = parseFloat(level.reward_rate_per_hour || 0);
                    const formattedRate = `$${rewardRate.toFixed(2)}/hr`;

                    const li = document.createElement('li');
                    // *** Updated innerHTML to include rate ***
                    li.innerHTML = `
                        <span class="level-info">
                            <span class="level-number">Lvl ${level.level}</span>
                            <span class="level-rank">(${level.rank_name || 'N/A'})</span>
                        </span>
                        <span class="level-rate">${formattedRate}</span>
                        <span class="level-time">${formattedTime}</span>
                    `;
                    estimatesList.appendChild(li);
                });

                if(currentDifficultyDisplay){ const selectedOptionText = select.options[select.selectedIndex]?.text || '?'; currentDifficultyDisplay.textContent = selectedOptionText.split('(')[0].trim(); }
            }

            // --- Initial fetch and populate logic (Keep existing) ---
            fetch('api/difficulty_handler.php?action=get_difficulty') /* ... keep as is ... */
                .then(response => { if (!response.ok) throw new Error(`HTTP ${response.status}`); return response.json(); }) .then(data => { if (data.status === 'success') { if (Array.from(select.options).some(o=>o.value === data.multiplier)) select.value = data.multiplier; else { select.value='1.0'; showMessage('Saved diff invalid',true); } displayLevelEstimates(select.value, baseLevelsData); } else { showMessage('Err fetch',true); displayLevelEstimates('1.0', baseLevelsData); } }) .catch(error => { showMessage(`Net err fetch`,true); displayLevelEstimates('1.0', baseLevelsData); });

            // --- Select change listener (Keep existing) ---
            select.addEventListener('change', () => { displayLevelEstimates(select.value, baseLevelsData); });

                    // --- Save button logic (Add Logging) ---
                    saveBtn.addEventListener('click', () => {
                 if(!select.value){ showMessage('Invalid option', true); return; }
                 // Save the setting
                 fetch('api/difficulty_handler.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=set_difficulty&multiplier=${select.value}` }) .then(response => { if (!response.ok) throw new Error(`HTTP ${response.status}`); return response.json(); }) .then(data => { if (data.status === 'success') showMessage('Difficulty saved!'); else showMessage(data.message || 'Error saving difficulty settings.', true); }) .catch(error => { console.error('Error:', error); showMessage('Network error while saving settings.', true); });
             });

            // --- showMessage function (Keep existing) ---
            function showMessage(text, isError = false) { if(!messageDiv) return; messageDiv.textContent = text; messageDiv.className = isError ? 'message-error' : 'message-success'; messageDiv.style.opacity = 1; setTimeout(() => { messageDiv.style.opacity = 0; }, 3000); }

            // --- Add timer management button handlers ---
            // Add Timer Button
            document.getElementById('add-timer-btn')?.addEventListener('click', () => { window.location.href = 'add_timer.php'; });
            
            // Reset All Button - with confirmation!
            document.getElementById('reset-all-btn')?.addEventListener('click', function() {
                if (confirm("⚠️ WARNING: This will reset ALL timers and ALL progress! Are you sure?")) {
                    fetch('api/reset_handler.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Always clean reload from server after reset
                            alert('All timers and progress have been reset.');
                            window.location.reload(true);
                        } else {
                            alert('Error: ' + (data.message || 'Failed to reset.'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Network error while resetting.');
                    });
                }
            });
            
            // --- USD to EGP Exchange Rate Handler ---
            const usdegpRateInput = document.getElementById('usdegp-rate');
            const saveRateBtn = document.getElementById('save-rate-btn');
            const rateMessageDiv = document.getElementById('rate-message');
            
            // Function to show rate messages
            function showRateMessage(text, isError = false) {
                if(!rateMessageDiv) return;
                rateMessageDiv.textContent = text;
                rateMessageDiv.className = isError ? 'message-error' : 'message-success';
                rateMessageDiv.style.opacity = 1;
                setTimeout(() => {
                    rateMessageDiv.style.opacity = 0;
                }, 3000);
            }
            
            // Fetch current exchange rate on page load
            fetch('api/exchange_rate_handler.php?action=get_rate')
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && data.rate) {
                        usdegpRateInput.value = data.rate;
                    } else {
                        // Set default if not found
                        usdegpRateInput.value = '50.7';
                    }
                })
                .catch(error => {
                    console.error('Error fetching exchange rate:', error);
                    // Set default on error
                    usdegpRateInput.value = '50.7';
                });
            
            // Save exchange rate button handler
            saveRateBtn.addEventListener('click', () => {
                const rate = parseFloat(usdegpRateInput.value);
                if (isNaN(rate) || rate <= 0) {
                    showRateMessage('Please enter a valid positive number', true);
                    return;
                }
                
                // Save the exchange rate
                fetch('api/exchange_rate_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=set_rate&rate=${rate}`
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showRateMessage('Exchange rate saved!');
                    } else {
                        showRateMessage(data.message || 'Error saving exchange rate.', true);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showRateMessage('Network error while saving exchange rate.', true);
                });
            });
        });
    </script>
</body>
</html>