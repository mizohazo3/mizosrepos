document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    // --- Configuration (Timer Page Specific) ---
    const POLLING_INTERVAL_MS = 5000;
    const UI_TICK_INTERVAL_MS = 50; // Faster tick can make progress look smoother
    // API_BASE_URL and RANK_ICONS moved to js/common.js

    // --- State Variables (Timer Page Specific) ---
    let timers = {};
    let bankBalance = 0.0;
    let difficultyMultiplier = 1.0;
    let levelsConfig = {};
    let pollingIntervalId = null;
    let uiTickIntervalId = null;
    let isPolling = false;
    let isInitialDataLoaded = false; // Flag to track if initial data is loaded
    let pendingActions = new Set(); // Stores timer IDs with pending actions

    // --- DOM Elements ---
    const timerList = document.getElementById('timer-list');
    const currentBalanceEl = document.getElementById('current-balance');
    const stopAllBtn = document.getElementById('stop-all-btn');
    const resetAllBtn = document.getElementById('reset-all-btn'); // Assuming this exists in your HTML
    const addTimerBtn = document.getElementById('add-timer-btn');
    const timerTemplate = document.getElementById('timer-template');
    const notificationContainer = document.getElementById('notification-container');
    const connectionStatusEl = document.getElementById('connection-status');
    const searchInput = document.getElementById('search-input');
    const runningTimersCountEl = document.getElementById('running-timers-count');
    
    // Check if we're on a page that uses timers
    const hasTimerFeatures = !!timerList && !!timerTemplate;

    // --- Event Listeners ---
    // No longer need click handler for global bank balance as it's now in the header

    // --- Timer Specific Calculation Functions ---
    // Utility functions (formatTimeWithMilliseconds, displayNotification, displayStatus, apiCall) moved to js/common.js

    // Calculate current seconds for a running timer's session
    function calculateCurrentSeconds(timerData) {
        if (!timerData) return 0;
        if (!timerData.is_running || !timerData.start_time) {
            return 0;
        }
        try {
            const startTime = new Date(timerData.start_time.replace(' ', 'T') + 'Z'); // Append 'Z' to specify UTC
            if (!isNaN(startTime.getTime())) {
                const elapsedSeconds = (Date.now() - startTime.getTime()) / 1000;
                return Math.max(0, elapsedSeconds);
            } else {
            }
        } catch (e) {
            // Error calculating time
        }
        return 0;
    }

    // Calculate current earnings for a running timer's session
    function calculateCurrentEarnings(timerData, totalSeconds) {
        if (!timerData || !levelsConfig[timerData.current_level]) return 0;
        if (!timerData.is_running || !timerData.start_time) return 0;
        try {
            const startTime = new Date(timerData.start_time.replace(' ', 'T') + 'Z');
            if (!isNaN(startTime.getTime())) {
                const elapsedSeconds = (Date.now() - startTime.getTime()) / 1000;
                const rewardRate = parseFloat(levelsConfig[timerData.current_level].reward_rate_per_hour) || 0;
                const currentSessionEarnings = (elapsedSeconds / 3600.0) * rewardRate;
                return currentSessionEarnings;
            }
        } catch (e) {
            // Error calculating earnings
        }
        return 0;
    }

    // Calculate current session time in hours
    function calculateCurrentSessionHours(timerData) {
        if (!timerData || !timerData.is_running || !timerData.start_time) return 0;
        try {
            const startTime = new Date(timerData.start_time.replace(' ', 'T') + 'Z');
            if (!isNaN(startTime.getTime())) {
                const elapsedSeconds = (Date.now() - startTime.getTime()) / 1000;
                return elapsedSeconds / 3600.0;
            }
        } catch (e) {
            // Error calculating session hours
        }
        return 0;
    }

    // --- Function to Calculate and Render Progress ---
    function calculateAndRenderProgress(timerEl, timerData, currentTotalHours) {
        if (!timerEl || !timerData || !levelsConfig) return;

        try {
            const currentLevel = parseInt(timerData.current_level) || 1;
            const maxLevel = Math.max(...Object.keys(levelsConfig).map(k => parseInt(k)));
            const isMaxLevel = currentLevel >= maxLevel;

            // Use the efficient method to get hours
            const getEffectiveHoursRequired = (level) => {
                if (!levelsConfig[level]) return null;
                if (level === 1) return 0.0;
                const baseHours = parseFloat(levelsConfig[level].hours_required);
                if (isNaN(baseHours)) return null;
                return baseHours * difficultyMultiplier;
            };

            // Calculate progress percentage
            let progressPercentage;
            if (isMaxLevel) {
                progressPercentage = 100;
            } else {
                const currentThreshold = getEffectiveHoursRequired(currentLevel) || 0;
                const nextThreshold = getEffectiveHoursRequired(currentLevel + 1);
                
                if (nextThreshold === null || nextThreshold <= currentThreshold) {
                    progressPercentage = 100;
                } else {
                    const hoursTowardsNext = Math.max(0, currentTotalHours - currentThreshold);
                    const hoursNeeded = nextThreshold - currentThreshold;
                    progressPercentage = Math.min(100, (hoursTowardsNext / hoursNeeded) * 100);
                }
            }

            // Update UI elements
            const progressBarFill = timerEl.querySelector('.timer-progress-fill');
            const progressText = timerEl.querySelector('.timer-progress-text');
            
            if (progressBarFill) {
                progressBarFill.style.width = `${progressPercentage}%`;
            }
            
            if (progressText) {
                progressText.textContent = isMaxLevel ? 'MAX' : `${Math.floor(progressPercentage)}%`;
            }
        } catch (error) {
            // Silent error handling
        }
    }

    // Renders a single timer
    function renderTimer(timerId) {
        if (!timers || !timers[timerId]) {
            return;
        }

        try {
            const timerData = timers[timerId];
            let timerEl = timerList.querySelector(`.timer-item[data-timer-id='${timerId}']`);

            if (!timerEl) {
                const templateContent = timerTemplate.content.cloneNode(true);
                timerEl = templateContent.querySelector('.timer-item');
                if (!timerEl) return;
                timerEl.setAttribute('data-timer-id', timerId);
                
                // Keep track of the timer's original position in the DOM
                timerEl.setAttribute('data-original-position', Object.keys(timers).length);
                
                // Find or create the proper row to add this timer to
                const allExistingTimers = timerList.querySelectorAll('.timer-item');
                const timerCount = allExistingTimers.length;
                const rowIndex = Math.floor(timerCount / 3); // 3 timers per row
                
                // Ensure we're looking for the row with the right data-row-index
                let targetRow = timerList.querySelector(`.timer-row[data-row-index="${rowIndex}"]`);
                
                // If the row doesn't exist or already has 3 timers, create a new row
                if (!targetRow || targetRow.children.length >= 3) {
                    targetRow = document.createElement('div');
                    targetRow.className = 'timer-row';
                    targetRow.setAttribute('data-row-index', rowIndex);
                    timerList.appendChild(targetRow);
                }
                
                // Add the timer to the row
                targetRow.appendChild(timerEl);
                
                // If we just added a timer and now have multiple rows, 
                // re-run organizeTimerRows to ensure proper layout
                if (timerList.querySelectorAll('.timer-row').length > 1) {
                    // Use setTimeout to run after current rendering
                    setTimeout(organizeTimerRows, 0);
                }
            }

            const qs = (selector) => timerEl.querySelector(selector);

            // Set the timer ID
            const timerIdEl = qs('.timer-id');
            if (timerIdEl) {
                timerIdEl.textContent = `#${timerId}`;
            }
            
            const timerDisplay = qs('.current-time');
            const millisecondsDisplay = qs('.milliseconds');
            // For running timers, show current seconds; for stopped timers, show 00:00:00
            const secondsToDisplay = timerData.is_running ? calculateCurrentSeconds(timerData) : 0;
            const timeObj = formatTimeWithMilliseconds(secondsToDisplay);
            if (timerDisplay) {
                timerDisplay.textContent = `${timeObj.hours}:${timeObj.minutes}:${timeObj.seconds}`;
                
                // Add clickable class and cursor styling to indicate it's clickable
                timerDisplay.classList.add('clickable-time');
                
                // Set title based on timer state
                timerDisplay.title = timerData.is_running ? 'Click to stop timer' : 'Click to start timer';
            }
            
            if (millisecondsDisplay) {
                millisecondsDisplay.textContent = timeObj.milliseconds;
                millisecondsDisplay.classList.add('clickable-time');
                millisecondsDisplay.title = timerData.is_running ? 'Click to stop timer' : 'Click to start timer';
            }

            // Make the entire time display container clickable
            const timeDisplayContainer = qs('.time-display');
            if (timeDisplayContainer) {
                timeDisplayContainer.classList.add('clickable-time-container');
                timeDisplayContainer.title = timerData.is_running ? 'Click to stop timer' : 'Click to start timer';
            }

            const timerNameEl = qs('.timer-name');
            if (timerNameEl) {
                timerNameEl.textContent = timerData.name || 'Unnamed Timer';
                timerNameEl.setAttribute('title', timerData.name || 'Unnamed Timer');
            }

            // Update session total earnings (was session hours)
            const sessionTotalEl = qs('.timer-session-total'); // Element still uses this class
            if (sessionTotalEl) {
                const sessionEarnings = timerData.is_running ? calculateCurrentEarnings(timerData, calculateCurrentSeconds(timerData)) : 0;
                sessionTotalEl.textContent = `$${sessionEarnings.toFixed(2)}`; // Show $ amount
            }

            timerEl.classList.toggle('running', !!timerData.is_running);
            timerEl.classList.toggle('pinned', !!timerData.is_pinned);

            const startButton = qs('.start-button');
            const stopButton = qs('.stop-button');
            if (startButton && stopButton) {
                startButton.style.display = timerData.is_running ? 'none' : 'block';
                stopButton.style.display = timerData.is_running ? 'block' : 'none';
            }

            const pinIcon = qs('.pin-icon'); // Select the new pin icon
            if (pinIcon) {
                const isPinned = !!timerData.is_pinned;
                pinIcon.classList.toggle('off', !isPinned); // Toggle 'off' class based on pinned state
                pinIcon.textContent = isPinned ? '📌' : '📍'; // Set icon based on pinned state
                pinIcon.title = isPinned ? 'Unpin Timer' : 'Pin Timer'; // Set title

                // Remove the duplicate click handler
                if (!pinIcon.dataset.listenerAdded) {
                    pinIcon.dataset.listenerAdded = 'true'; // Mark as processed but don't add duplicate handler
                }
            }

            const totalEarnedEl = qs('.timer-total-earned'); // Element still uses this class
            if (totalEarnedEl) {
                // Display total accumulated hours (was session earnings)
                const totalSeconds = parseFloat(timerData.accumulated_seconds || 0);
                const totalHours = totalSeconds / 3600.0;
                totalEarnedEl.textContent = `${totalHours.toFixed(2)} h`; // Show total hours
                // Remove earning class toggle as it's now total hours
                totalEarnedEl.classList.remove('earning');
            }

            const levelEl = qs('.timer-level');
            const rankEl = qs('.timer-rank');
            const rewardRateEl = qs('.timer-reward-rate');

            if (levelEl) levelEl.textContent = timerData.current_level || '1';
            if (rankEl && levelsConfig[timerData.current_level]) {
                const rankName = levelsConfig[timerData.current_level].rank_name || 'Novice';
                rankEl.textContent = rankName;
                if (RANK_ICONS[rankName]) {
                    rankEl.textContent = `${RANK_ICONS[rankName]} ${rankName}`;
                }
            }

            if (rewardRateEl && levelsConfig[timerData.current_level]) {
                const ratePerHour = parseFloat(levelsConfig[timerData.current_level].reward_rate_per_hour) || 0;
                rewardRateEl.textContent = `$${ratePerHour.toFixed(2)}/hr`;
            }

            calculateAndRenderProgress(timerEl, timerData, parseFloat(timerData.accumulated_seconds || 0) / 3600.0);
        } catch (error) {
            // Error rendering timer
        }
    }

    // Fast UI tick
    function updateUITick() {
        if (!timers || Object.keys(timers).length === 0) return;

        const now = Date.now();
        const updateSecondaryInfo = now % 500 < 50; // Update non-essential info every 500ms

        // Update only running timers' UI for performance
        for (const timerId in timers) {
            const timerEl = timerList.querySelector(`.timer-item[data-timer-id='${timerId}']`);
            if (!timerEl) continue;

            const timerData = timers[timerId];
            if (!timerData.is_running) continue;
            
            // Always update timer display - this is most important for visual feedback
            const timeDisplay = timerEl.querySelector('.current-time');
            const msDisplay = timerEl.querySelector('.milliseconds');
            if (timeDisplay && msDisplay) {
                const currentSeconds = calculateCurrentSeconds(timerData);
                const timeObj = formatTimeWithMilliseconds(currentSeconds);
                timeDisplay.textContent = `${timeObj.hours}:${timeObj.minutes}:${timeObj.seconds}`;
                msDisplay.textContent = timeObj.milliseconds;
                
                // Update less critical elements less frequently to improve performance
                if (updateSecondaryInfo) {
                    // Update session total earnings dynamically
                    const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                    if (sessionTotalEl) {
                        const sessionEarnings = calculateCurrentEarnings(timerData, currentSeconds);
                        sessionTotalEl.textContent = `$${sessionEarnings.toFixed(2)}`;
                    }

                    // Update total hours dynamically
                    const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                    if (totalEarnedEl) {
                        const accumulatedSeconds = parseFloat(timerData.accumulated_seconds || 0);
                        const currentSessionSeconds = calculateCurrentSeconds(timerData);
                        const totalSecondsForDisplay = accumulatedSeconds + currentSessionSeconds;
                        const totalHoursForDisplay = totalSecondsForDisplay / 3600.0;
                        totalEarnedEl.textContent = `${totalHoursForDisplay.toFixed(2)} h`;
                    }

                    // Update progress bar only when we need to refresh other data
                }
            }
        }
    }

    // Sort timers - modified to preserve original order
    function sortTimersByActiveStatus() {
        // Intentionally not sorting to preserve original timer positions
        // Original sorting code has been removed to prevent reordering
    }

    // Update UI from fetched data
    function updateFullUI(data) {
        if (!data) return;

        if (data.bank_balance !== undefined && currentBalanceEl) {
            currentBalanceEl.textContent = parseFloat(data.bank_balance).toFixed(2);
            bankBalance = parseFloat(data.bank_balance);
        }
        if (data.difficulty_multiplier !== undefined) {
            difficultyMultiplier = parseFloat(data.difficulty_multiplier);
        }
        if (data.levels) {
            levelsConfig = data.levels;
        }
        if (data.timers && typeof data.timers === 'object') {
            const receivedTimerIds = new Set();
            
            // Before updating timers, make sure we have the right row structure
            organizeTimerRows();
            
            for (const timerId in data.timers) {
                if (!data.timers.hasOwnProperty(timerId)) continue;
                const timer = data.timers[timerId];
                if (String(timer.id) !== String(timerId)) {
                    continue;
                }
                receivedTimerIds.add(timerId);
                timers[timerId] = timer;
                renderTimer(timerId);
            }
            
            // Handle removed timers
            for (const existingTimerId in timers) {
                if (!receivedTimerIds.has(existingTimerId)) {
                    const timerEl = document.querySelector(`.timer-item[data-timer-id='${existingTimerId}']`);
                    if (timerEl) {
                        // Remove the timer element
                        const parentRow = timerEl.closest('.timer-row');
                        timerEl.remove();
                        
                        // If the row is now empty, remove it
                        if (parentRow && parentRow.children.length === 0) {
                            parentRow.remove();
                        }
                    }
                    delete timers[existingTimerId];
                }
            }
            
            // Reorganize timer rows if needed
            organizeTimerRows();
            
            // Update running timers count
            updateRunningTimersCount();
        }
    }

    // New function to organize timers into rows
    function organizeTimerRows() {
        if (!timerList) return;
        
        // Get all timer items directly under timerList or in any existing row
        const allTimers = [];
        timerList.querySelectorAll('.timer-item').forEach(timer => {
            allTimers.push(timer);
        });
        
        // Clear all content to reorganize
        timerList.innerHTML = '';
        
        // Create rows and add timers
        let currentRow = null;
        allTimers.forEach((timer, index) => {
            if (index % 3 === 0) {
                // Create a new row every 3 timers
                currentRow = document.createElement('div');
                currentRow.className = 'timer-row';
                currentRow.setAttribute('data-row-index', Math.floor(index / 3));
                timerList.appendChild(currentRow);
            }
            currentRow.appendChild(timer);
        });
    }

    // Update running timers count
    function updateRunningTimersCount() {
        if (runningTimersCountEl) {
            const runningCount = Object.values(timers).filter(timer => timer.is_running).length;
            runningTimersCountEl.textContent = runningCount;

            // Style update for visibility
            if (runningCount === 0) {
                runningTimersCountEl.style.display = 'none';
                if (stopAllBtn) {
                    stopAllBtn.classList.remove('has-running-timers');
                }
            } else {
                runningTimersCountEl.style.display = 'inline-flex';
                runningTimersCountEl.style.visibility = 'visible';
                
                // Add visual indication to the parent button
                if (stopAllBtn) {
                    stopAllBtn.classList.add('has-running-timers');
                    
                    // Force a reflow to ensure the counter is visible
                    void runningTimersCountEl.offsetWidth;
                    
                    // Add a subtle animation to draw attention to the counter
                    runningTimersCountEl.classList.remove('counter-pulse');
                    setTimeout(() => {
                        runningTimersCountEl.classList.add('counter-pulse');
                    }, 10);
                }
            }
        }
    }

    // Fetch initial data (only pinned timers)
    async function fetchInitialData() {
        if (connectionStatusEl) {
            connectionStatusEl.textContent = 'Status: Connecting...';
            connectionStatusEl.style.color = 'var(--accent-warning)';
        }
        try {
            // Fetch only pinned timers initially
            const data = await apiCall('get_data.php?filter=pinned');
            if (data && data.status === 'success') {
                // Ensure levelsConfig and difficultyMultiplier are still loaded if needed,
                // even when fetching only pinned timers. Adjust API if necessary.
                // For now, assume the response includes these even with the filter.
                updateFullUI(data);
                isInitialDataLoaded = true; // Set flag after data is loaded
                startPolling();
                if (timerList) { startUITick(); } // Always start UI tick after initial data is loaded
            } else {
                stopPolling();
                stopUITick();
            }
        } catch (error) {
            stopPolling();
            stopUITick();
            if (connectionStatusEl) {
                connectionStatusEl.textContent = 'Status: Error';
                connectionStatusEl.style.color = 'var(--accent-error)';
            }
        }
    }

    // Handle timer actions
    async function handleTimerAction(action, timerId) {
        if (!timerId && !['stop_all', 'reset_all'].includes(action)) return false;
        
        try {
            const payload = { action };
            if (timerId && !['stop_all', 'reset_all'].includes(action)) {
                payload.id = timerId;
            }

            // Immediately update UI for start action before API call completes
            if (action === 'start' && timers[timerId]) {
                // Save the current accumulated seconds before resetting
                const previousAccumulatedSeconds = parseFloat(timers[timerId].accumulated_seconds || 0);
                
                // Update visual state immediately
                timers[timerId].is_running = 1;
                
                // Do NOT reset accumulated seconds when starting the timer
                // This is key to preserving progress bar position
                
                // Show stop button, hide start button
                const startButton = document.querySelector(`.timer-item[data-timer-id='${timerId}'] .start-button`);
                const stopButton = document.querySelector(`.timer-item[data-timer-id='${timerId}'] .stop-button`);
                if (startButton) startButton.style.display = 'none';
                if (stopButton) stopButton.style.display = 'block';

                // Set start time to now for immediate display
                const currentTime = new Date().toISOString().replace('T', ' ').substring(0, 19);
                timers[timerId].start_time = currentTime;
                
                // Update time display immediately to show 00:00:00
                const timeDisplay = document.querySelector(`.timer-item[data-timer-id='${timerId}'] .current-time`);
                const msDisplay = document.querySelector(`.timer-item[data-timer-id='${timerId}'] .milliseconds`);
                if (timeDisplay) timeDisplay.textContent = '00:00:00';
                if (msDisplay) msDisplay.textContent = '00';
                
                // Initialize the progress bar immediately for better UX
                const timerEl = document.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                if (timerEl) {
                    const progressBarFill = timerEl.querySelector('.timer-progress-fill');
                    const progressText = timerEl.querySelector('.timer-progress-text');
                    
                    // Preserve the existing progress by using the saved accumulated seconds
                    // This ensures the progress bar doesn't reset to zero
                    if (progressBarFill && progressText) {
                        // Use the existing progress percentage directly from the DOM
                        const existingProgressText = progressText.textContent;
                        const existingPercentage = parseFloat(existingProgressText);
                        
                        if (!isNaN(existingPercentage)) {
                            // Keep the existing percentage
                            progressBarFill.style.width = `${existingPercentage}%`;
                            progressText.textContent = existingProgressText;
                        }
                    }
                    
                    // Initialize session total and earned values to zero
                    const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                    if (sessionTotalEl) {
                        sessionTotalEl.textContent = '0.00';
                    }
                    
                    // Update total earned display to show $0.00
                    const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                    if (totalEarnedEl) {
                        totalEarnedEl.textContent = '$0.00';
                        totalEarnedEl.classList.add('earning');
                    }
                }
                
                // Update running count immediately
                updateRunningTimersCount();
                
                // Start UI tick if not already running
                if (timerList && !uiTickIntervalId) { startUITick(); }
            }

            const response = await apiCall('timer_action.php', 'POST', payload);
            
            if (response.status === 'success') {
                // For start action, update with server response data
                if (action === 'start') {
                    if (timers[timerId]) {
                        // Update timer data with actual server values
                        timers[timerId].is_running = 1;
                        if (response.start_time) {
                            timers[timerId].start_time = response.start_time;
                        }
                        
                        // Don't update accumulated_seconds from the response as it would reset the progress
                        // If the response includes accumulated_seconds, we'll preserve the existing value
                        if (response.accumulated_seconds === 0 && timers[timerId].accumulated_seconds > 0) {
                            // Keep the existing accumulated seconds to maintain progress bar
                        } else if (response.accumulated_seconds !== undefined) {
                            // Only update if not resetting to zero or if already zero
                            timers[timerId].accumulated_seconds = response.accumulated_seconds;
                        }
                        
                        // Make sure running timers count is updated
                        updateRunningTimersCount();
                        
                        // Update UI to reflect the actual server state
                        // But don't rerender which might reset the progress bar
                        // Just update time display
                        const timerEl = document.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                        if (timerEl) {
                            const timeDisplay = timerEl.querySelector('.current-time');
                            const msDisplay = timerEl.querySelector('.milliseconds');
                            if (timeDisplay) timeDisplay.textContent = '00:00:00';
                            if (msDisplay) msDisplay.textContent = '00';
                            
                            // Update session values only
                            const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                            if (sessionTotalEl) {
                                sessionTotalEl.textContent = '0.00';
                            }
                            
                            const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                            if (totalEarnedEl) {
                                totalEarnedEl.textContent = '$0.00';
                                totalEarnedEl.classList.add('earning');
                            }
                            
                            // Ensure progress bar remains unchanged
                            const progressBarFill = timerEl.querySelector('.timer-progress-fill');
                            const progressText = timerEl.querySelector('.timer-progress-text');
                            if (progressBarFill && progressText) {
                                // Keep the existing width and text (no changes)
                            }
                        }
                    }
                }
                
                // For stop action, immediately update UI
                if (action === 'stop' && response.bank_balance !== undefined) {
                    // Update bank balance right away in both places
                    if (currentBalanceEl) {
                        currentBalanceEl.textContent = parseFloat(response.bank_balance).toFixed(2);
                    }
                    
                    bankBalance = parseFloat(response.bank_balance);
                    
                    // Update timer data
                    if (timers[timerId]) {
                        timers[timerId].is_running = 0;
                        timers[timerId].start_time = null;
                        timers[timerId].accumulated_seconds = response.accumulated_seconds;
                        
                        // Immediately reset the display to 00:00:00
                        const timerEl = document.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                        if (timerEl) {
                            const timeDisplay = timerEl.querySelector('.current-time');
                            const msDisplay = timerEl.querySelector('.milliseconds');
                            if (timeDisplay) timeDisplay.textContent = '00:00:00';
                            if (msDisplay) msDisplay.textContent = '00';
                            
                            // Also reset session totals and earned
                            const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                            if (sessionTotalEl) {
                                sessionTotalEl.textContent = '0.00';
                            }
                            
                            const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                            if (totalEarnedEl) {
                                totalEarnedEl.textContent = '$0.00';
                                totalEarnedEl.classList.remove('earning');
                            }
                        }
                        
                        if (response.level_up) {
                            timers[timerId].current_level = response.new_level;
                            timers[timerId].notified_level = response.new_level;
                            
                            displayNotification(
                                `Your timer "${timers[timerId].name}" leveled up to ${response.new_level} (${response.new_rank})!`, 
                                'level-up',
                                8000
                            );
                        }
                    }
                    
                    // Update running timers count
                    updateRunningTimersCount();
                }
                
                // If the stop_all action is called directly
                if (action === 'stop_all' && response.bank_balance !== undefined) {
                    if (currentBalanceEl) {
                        currentBalanceEl.textContent = parseFloat(response.bank_balance).toFixed(2);
                    }
                    bankBalance = parseFloat(response.bank_balance);
                    
                    // Update all timers stopped in the response
                    if (response.stopped_timers && Array.isArray(response.stopped_timers)) {
                        response.stopped_timers.forEach(stoppedTimer => {
                            if (timers[stoppedTimer.id]) {
                                timers[stoppedTimer.id].is_running = 0;
                                timers[stoppedTimer.id].start_time = null;
                                timers[stoppedTimer.id].accumulated_seconds = stoppedTimer.accumulated_seconds;
                                
                                // Reset timer display to 00:00:00
                                const timerEl = document.querySelector(`.timer-item[data-timer-id='${stoppedTimer.id}']`);
                                if (timerEl) {
                                    const timeDisplay = timerEl.querySelector('.current-time');
                                    const msDisplay = timerEl.querySelector('.milliseconds');
                                    if (timeDisplay) timeDisplay.textContent = '00:00:00';
                                    if (msDisplay) msDisplay.textContent = '00';
                                    
                                    // Also reset session totals and earned
                                    const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                                    if (sessionTotalEl) {
                                        sessionTotalEl.textContent = '0.00';
                                    }
                                    
                                    const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                                    if (totalEarnedEl) {
                                        totalEarnedEl.textContent = '$0.00';
                                        totalEarnedEl.classList.remove('earning');
                                    }
                                }
                                
                                if (stoppedTimer.level_up) {
                                    timers[stoppedTimer.id].current_level = stoppedTimer.new_level;
                                    timers[stoppedTimer.id].notified_level = stoppedTimer.new_level;
                                }
                            }
                        });
                    } else {
                        // If no stopped_timers in response, stop all running timers locally
                        Object.keys(timers).forEach(id => {
                            if (timers[id].is_running) {
                                timers[id].is_running = 0;
                                timers[id].start_time = null;
                                
                                // Reset timer display to 00:00:00
                                const timerEl = document.querySelector(`.timer-item[data-timer-id='${id}']`);
                                if (timerEl) {
                                    const timeDisplay = timerEl.querySelector('.current-time');
                                    const msDisplay = timerEl.querySelector('.milliseconds');
                                    if (timeDisplay) timeDisplay.textContent = '00:00:00';
                                    if (msDisplay) msDisplay.textContent = '00';
                                    
                                    // Also reset session totals and earned
                                    const sessionTotalEl = timerEl.querySelector('.timer-session-total');
                                    if (sessionTotalEl) {
                                        sessionTotalEl.textContent = '0.00';
                                    }
                                    
                                    const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
                                    if (totalEarnedEl) {
                                        totalEarnedEl.textContent = '$0.00';
                                        totalEarnedEl.classList.remove('earning');
                                    }
                                }
                            }
                        });
                    }
                    
                    // Update running timers count to 0
                    updateRunningTimersCount();
                }
                
                // For reset_all, immediately update UI
                if (action === 'reset_all' && response.bank_balance !== undefined) {
                    bankBalance = parseFloat(response.bank_balance);
                    if (currentBalanceEl) {
                        currentBalanceEl.textContent = parseFloat(response.bank_balance).toFixed(2);
                }
                    
                    if (response.reset_timers && Array.isArray(response.reset_timers)) {
                        response.reset_timers.forEach(resetTimer => {
                            if (timers[resetTimer.id]) {
                                Object.assign(timers[resetTimer.id], {
                                    is_running: 0,
                                    start_time: null,
                                    accumulated_seconds: 0,
                                    current_level: 1,
                                    notified_level: 1
                                });
                                renderTimer(resetTimer.id);
                            }
                        });
                    }
                    
                    updateRunningTimersCount();
                }
                
                const successMessage = response.message || `Timer ${action} successful`;
                displayNotification(successMessage, 'success');
                return true;
            } else {
                const errorMessage = response.message || `Failed to ${action} timer`;
                displayNotification(errorMessage, 'error');
                return false;
            }
        } catch (error) {
            displayNotification(`Error: ${error.message}`, 'error');
            return false;
        }
    }

    // Handle adding a new timer
    async function handleAddTimer() {
        // Assuming modal is globally available or imported
        modal.show({
            title: 'Add New Timer',
            message: 'Enter a name for the new timer:',
            confirmText: 'Add Timer',
            type: 'confirm',
            onConfirm: async (inputElement, inputValue) => {
                const name = inputValue;
                if (!name) {
                    displayNotification("Timer name could not be retrieved.", 'error');
                    return;
                }
                displayNotification(`Adding timer '${name}'...`, 'info');
                if (addTimerBtn) addTimerBtn.disabled = true;

                const response = await apiCall('add_timer.php', 'POST', { name: name });
                if (response && response.status === 'success' && response.timer) {
                    const newTimer = response.timer;
                    newTimer.is_running = !!parseInt(newTimer.is_running || 0);
                    newTimer.accumulated_seconds = parseFloat(newTimer.accumulated_seconds || 0);
                    newTimer.current_level = parseInt(newTimer.current_level || 1) || 1;
                    newTimer.notified_level = parseInt(newTimer.notified_level || 1) || 1;
                    const levelInfo = (levelsConfig && typeof levelsConfig === 'object') ? levelsConfig[newTimer.current_level] : null;
                    newTimer.rank_name = newTimer.rank_name || (levelInfo ? levelInfo.rank_name : 'N/A');
                    newTimer.reward_rate_per_hour = newTimer.reward_rate_per_hour || (levelInfo ? levelInfo.reward_rate_per_hour : '0.00');
                    timers[newTimer.id] = newTimer;
                    renderTimer(newTimer.id);
                    displayNotification(`Timer '${newTimer.name}' added.`, 'success');
                    if (inputElement) inputElement.value = '';
                    if (timerList && !uiTickIntervalId) { startUITick(); }
                } else {
                    displayNotification(`Failed to add timer: ${response?.message || 'Unknown error'}`, 'error');
                }
                if (addTimerBtn) addTimerBtn.disabled = false;
            },
            onCancel: () => {
                if (addTimerBtn) addTimerBtn.disabled = false;
            },
            customContent: `<input type="text" class="modal-input" placeholder="Timer name" style="width: 100%; padding: 8px; margin-top: 10px; border-radius: 4px; border: 1px solid var(--border-color); background: var(--bg-dark); color: var(--text-primary);">`
        });
    }

    // --- Polling Logic ---
    async function pollServer() {
        if (isPolling) return;
        isPolling = true;
        try {
            const data = await apiCall('get_data.php?filter=pinned');
            if (data && data.status === 'success') {
                updateFullUI(data);
                updateRunningTimersCount(); // Always update timer count on every poll
            }
        } catch (error) {
        } finally {
            isPolling = false;
        }
    }

    function startPolling() {
        if (pollingIntervalId) clearInterval(pollingIntervalId);
        pollServer().finally(() => {
            if (!pollingIntervalId) {
                pollingIntervalId = setInterval(pollServer, POLLING_INTERVAL_MS);
            }
        });
    }

    function stopPolling() {
        if (pollingIntervalId) {
            clearInterval(pollingIntervalId);
            pollingIntervalId = null;
        }
        isPolling = false;
    }

    function startUITick() {
        if (uiTickIntervalId) return;
        uiTickIntervalId = setInterval(updateUITick, 50); // Use 50ms for smoother display
    }

    function stopUITick() {
        if (uiTickIntervalId) {
            clearInterval(uiTickIntervalId);
            uiTickIntervalId = null;
        }
    }

    // Filter timers based on search input
    function filterTimers() {
        if (!searchInput || !timerList) return;

        const rawSearchTerm = searchInput.value.toLowerCase();
        const sanitizedSearchTermForSplit = rawSearchTerm.replace(/[^a-z0-9\s]/g, '').trim();
        const searchParts = sanitizedSearchTermForSplit.split(' ').filter(part => part.length > 0);

        const timerItems = timerList.querySelectorAll('.timer-item');

        if (searchParts.length > 0) {
            timerItems.forEach(item => { item.style.display = ''; });
        }

        timerItems.forEach(item => {
            const timerIdStr = item.dataset.timerId;
            if (!timerIdStr) return;
            const timerId = parseInt(timerIdStr); // Assuming ID is numeric
            const timerData = timers[timerId];

            if (searchParts.length === 0) {
                // When search is empty, always show the item (don't hide non-running/non-pinned)
                item.style.display = '';
            } else {
                let shouldShow = true;
                if (!timerData) {
                    shouldShow = false;
                } else {
                    const timerName = timerData.name ? timerData.name.toLowerCase() : '';
                    const rawTargetText = `${timerName} ${timerId}`;
                    const sanitizedTargetText = rawTargetText.replace(/[^a-z0-9]/g, '');
                    const match = searchParts.every(part => {
                        const sanitizedPart = part.replace(/[^a-z0-9]/g, '');
                        if (sanitizedPart === '') return true;
                        return sanitizedTargetText.includes(sanitizedPart);
                    });
                    shouldShow = match;
                }
                item.style.display = shouldShow ? '' : 'none';
            }
        });
    }

    // --- Event Listeners ---
    if (timerList) {
        timerList.addEventListener('click', (event) => {
            const target = event.target;
            const timerItem = target.closest('.timer-item');
            if (!timerItem) return;
            const timerIdStr = timerItem.dataset.timerId;
            if (!timerIdStr) return;
            const timerId = parseInt(timerIdStr); // Assuming ID is numeric
            if (isNaN(timerId)) return;

            let action = null;
            let button = null;

            // Check if the clicked element is the time display
            const isTimeDisplay = target.classList.contains('clickable-time') || 
                                  target.classList.contains('time-display') ||
                                  target.closest('.clickable-time-container');
            
            if (isTimeDisplay) {
                // If timer is running, stop it; otherwise, start it
                const isRunning = timerItem.classList.contains('running');
                action = isRunning ? 'stop' : 'start';
                
                // Apply immediate visual feedback
                if (action === 'start') {
                    // Immediate visual feedback on start
                    timerItem.classList.add('starting');
                    const timeDisplay = timerItem.querySelector('.current-time');
                    const msDisplay = timerItem.querySelector('.milliseconds');
                    
                    // Apply a quick flash effect
                    const flashTimeout = setTimeout(() => {
                        timerItem.classList.remove('starting');
                        clearTimeout(flashTimeout);
                    }, 300);
                    
                    // Show immediate visual feedback
                    if (timeDisplay) timeDisplay.textContent = '00:00:00';
                    if (msDisplay) msDisplay.textContent = '00';
                    
                    timerItem.classList.add('running');
                    
                    // Swap buttons
                    const startButton = timerItem.querySelector('.start-button');
                    const stopButton = timerItem.querySelector('.stop-button');
                    if (startButton) startButton.style.display = 'none';
                    if (stopButton) stopButton.style.display = 'block';
                } else {
                    // Immediate visual feedback for stopping
                    timerItem.classList.add('stopping');
                    
                    // Apply a quick flash effect
                    const flashTimeout = setTimeout(() => {
                        timerItem.classList.remove('stopping');
                        clearTimeout(flashTimeout);
                    }, 300);
                    
                    timerItem.classList.remove('running');
                    
                    // Swap buttons
                    const startButton = timerItem.querySelector('.start-button');
                    const stopButton = timerItem.querySelector('.stop-button');
                    if (startButton) startButton.style.display = 'block';
                    if (stopButton) stopButton.style.display = 'none';
                }
            } else if (target.classList.contains('start-button')) {
                action = 'start';
                button = target;
                
                // Immediate visual feedback on start button click
                timerItem.classList.add('starting');
                const timeDisplay = timerItem.querySelector('.current-time');
                const msDisplay = timerItem.querySelector('.milliseconds');
                
                // Apply a quick flash effect to the timer card
                const flashTimeout = setTimeout(() => {
                    timerItem.classList.remove('starting');
                    clearTimeout(flashTimeout);
                }, 300);
                
                // Show immediate visual feedback that timer is being started
                if (timeDisplay) timeDisplay.textContent = '00:00:00';
                if (msDisplay) msDisplay.textContent = '00';
                
                // Apply the 'running' class immediately for visual feedback
                timerItem.classList.add('running');
                
                // Swap buttons immediately for better UX
                const startButton = timerItem.querySelector('.start-button');
                const stopButton = timerItem.querySelector('.stop-button');
                if (startButton) startButton.style.display = 'none';
                if (stopButton) stopButton.style.display = 'block';
            } else if (target.classList.contains('stop-button')) {
                action = 'stop';
                button = target;
                
                // Immediate visual feedback for stopping
                timerItem.classList.add('stopping');
                
                // Apply a quick flash effect
                const flashTimeout = setTimeout(() => {
                    timerItem.classList.remove('stopping');
                    clearTimeout(flashTimeout);
                }, 300);
                
                // Remove running class immediately
                timerItem.classList.remove('running');
                
                // Swap buttons immediately
                const startButton = timerItem.querySelector('.start-button');
                const stopButton = timerItem.querySelector('.stop-button');
                if (startButton) startButton.style.display = 'block';
                if (stopButton) stopButton.style.display = 'none';
            } else if (target.classList.contains('pin-icon')) {
                action = 'toggle_pin';
                button = target;
                event.stopPropagation();
            }

            if (action) {
                // Disable any buttons if they were clicked
                if (button) button.disabled = true;
                
                handleTimerAction(action, timerId)
                    .finally(() => {
                        // Only re-enable if we had a button click (not a time display click)
                        if (button) {
                        // Use a slight delay to ensure DOM updates potentially finish
                        setTimeout(() => {
                            // Re-find the button in case the DOM structure changed
                            const finalButton = timerItem.querySelector(`.${action}-button`);
                            if (finalButton) {
                                finalButton.disabled = false;
                            } else {
                                // Fallback if specific button class isn't found (e.g., start/stop swap)
                                const anyButton = timerItem.querySelector('button');
                                if (anyButton) anyButton.disabled = false;
                            }
                        }, 50); // Short delay
                        }
                    });
            }
        });
    }

    if (addTimerBtn) addTimerBtn.addEventListener('click', handleAddTimer);
    if (stopAllBtn) {
        stopAllBtn.addEventListener('click', async () => {
            if (!isInitialDataLoaded) {
                displayNotification('Timer data is still loading. Please wait a moment.', 'info');
                return;
            }

            const runningTimers = Object.keys(timers).filter(id => timers[id].is_running);
            const runningCount = runningTimers.length;

            if (runningCount === 0) {
                displayNotification('No running timers to stop', 'info');
                return;
            }

            // Create a list of timer names for display
            let totalSessionHours = 0;
            let totalSessionEarnings = 0;

            const timerNamesList = runningTimers.map(id => {
                const timer = timers[id];
                const timerName = timer.name || 'Unnamed Timer';

                // Calculate session time
                const currentSeconds = calculateCurrentSeconds(timer);
                const sessionHours = (currentSeconds / 3600).toFixed(2);
                totalSessionHours += parseFloat(sessionHours);

                // Calculate session earnings
                const sessionEarnings = calculateCurrentEarnings(timer, currentSeconds);
                totalSessionEarnings += sessionEarnings;

                return `<li style="margin-left: 10px; margin-top: 5px; list-style-type: disc;">
                    ${timerName} - ${sessionHours}h
                </li>`;
            }).join('');

            // Add summary of total hours and earnings
            const summaryInfo = `
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
                    <p style="margin: 5px 0;">Total session hours: <strong>${totalSessionHours.toFixed(2)}h</strong></p>
                    <p style="margin: 5px 0;">Total earned from sessions: <strong style="color: #67c23a;">+$${totalSessionEarnings.toFixed(2)}</strong></p>
                </div>
            `;

            // Show confirmation modal
            modal.show({
                title: 'Stop All Timers',
                message: `Are you sure you want to stop all ${runningCount} running timers?<ul style="margin-top: 10px; padding-left: 15px;">${timerNamesList}</ul>${summaryInfo}`,
                type: 'warning',
                confirmText: 'Stop All',
                cancelText: 'Cancel',
                onConfirm: async () => {
                    stopAllBtn.disabled = true;
                    try {
                        const result = await handleTimerAction('stop_all', null);
                        if (result) {
                            displayNotification('All timers stopped successfully', 'success');
                        } else {
                            displayNotification('Failed to stop all timers', 'error');
                        }
                    } catch (error) {
                        displayNotification('Failed to stop all timers: ' + error.message, 'error');
                    } finally {
                        stopAllBtn.disabled = false;
                    }
                }
            });
        });
    }
    if (resetAllBtn) resetAllBtn.addEventListener('click', () => handleTimerAction('reset_all', null)); // Assuming reset button exists
    if (searchInput) searchInput.addEventListener('input', filterTimers);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            stopUITick();
        } else {
            fetchInitialData(); // This will restart polling/ticking if successful
        }
    });

    // --- Initialisation ---
    fetchInitialData().catch(error => {
        displayNotification('Failed to initialize timers. Please refresh the page.', 'error');
    });

    // Initialize running timers count for immediate UI update
    updateRunningTimersCount();

    // Add CSS class for animation
    document.addEventListener('DOMContentLoaded', () => {
        // Add animation styles to head
        const style = document.createElement('style');
        style.textContent = `
            .counter-pulse {
                animation: pulse 1.5s ease-out;
            }
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Initial counter visibility check
        if (runningTimersCountEl) {
            const count = runningTimersCountEl.textContent.trim();
            if (count === '0' || count === '') {
                runningTimersCountEl.style.display = 'none';
            } else {
                runningTimersCountEl.style.display = 'inline-flex';
                runningTimersCountEl.style.visibility = 'visible';
            }
        }
    });

    // Redundant API call removed. Initial count is updated by fetchInitialData -> updateFullUI.

    // --- Error Prevention (Optional - Keep or Remove based on need) ---
    function preventRunningUIErrors() {
      const selectors = [
        '[role="alert"]', '.warning-popup', '.browser-warning', 'div[class*="warning"]',
        'div[class*="error"]', 'div:has(> .close-icon)', 'div:has(> svg[class*="warning"])',
        'div:has(> svg[class*="error"])', 'div:has(> span.warning-icon)',
        'div:has(> span.error-icon)', 'div.popup-notification', 'div[aria-live="polite"]'
      ];
      selectors.forEach(selector => {
        try {
          document.querySelectorAll(selector).forEach(el => {
            el.style.display = 'none'; el.style.opacity = '0';
            el.style.visibility = 'hidden'; el.style.pointerEvents = 'none';
          });
        } catch (e) { /* Ignore query errors */ }
      });

      const originalFetch = window.fetch;
      window.fetch = async function(...args) {
        try {
          const response = await originalFetch.apply(this, args);
          if (args[0] && typeof args[0] === 'string' &&
              (args[0].includes('timer_action.php') || args[0].includes('api/timer'))) {
            if (!response.ok) {
              return new Response(JSON.stringify({ status: 'success', message: 'Action completed (error suppressed)' }),
                                { status: 200, headers: {'Content-Type': 'application/json'} });
            }
            const clone = response.clone();
            try {
              const text = await clone.text();
              if (text.includes('Timer not found') || text.includes('not running') ||
                  text.includes('already running') || text.includes('Internal Server Error')) {
                return new Response(JSON.stringify({ status: 'success', message: 'Action completed (error suppressed)' }),
                                  { status: 200, headers: {'Content-Type': 'application/json'} });
              }
            } catch (e) { /* Ignore text parsing errors */ }
          }
          return response;
        } catch (error) {
          return new Response(JSON.stringify({status: 'success', message: 'Action completed (network error suppressed)'}),
                            {status: 200, headers: {'Content-Type': 'application/json'}});
        }
      };
    }
    // preventRunningUIErrors(); // Uncomment this line to activate the error suppression

    // Add CSS styles for immediate visual feedback
    document.addEventListener('DOMContentLoaded', () => {
        // Add animation styles for timer start/stop
        const style = document.createElement('style');
        style.textContent += `
            .timer-item.starting {
                animation: timer-start-flash 0.3s ease-out;
            }
            
            .timer-item.stopping {
                animation: timer-stop-flash 0.3s ease-out;
            }
            
            @keyframes timer-start-flash {
                0% { background-color: rgba(0, 255, 0, 0); }
                50% { background-color: rgba(0, 255, 0, 0.15); }
                100% { background-color: rgba(0, 255, 0, 0); }
            }
            
            @keyframes timer-stop-flash {
                0% { background-color: rgba(255, 0, 0, 0); }
                50% { background-color: rgba(255, 0, 0, 0.15); }
                100% { background-color: rgba(255, 0, 0, 0); }
            }
            
            /* Clickable time display styling */
            .clickable-time, 
            .clickable-time-container,
            .time-display,
            .timer-item .current-time,
            .timer-item .milliseconds {
                cursor: pointer !important; /* Force hand cursor with higher specificity */
                user-select: none;
                transition: color 0.2s ease;
            }
            
            /* Ensure the entire time area shows hand cursor */
            .timer-item .time-display-wrapper,
            .timer-item .time-display {
                cursor: pointer !important;
            }
            
            .clickable-time:hover, 
            .clickable-time-container:hover,
            .time-display:hover {
                color: var(--accent-primary, #00c2ff);
                text-shadow: 0 0 8px rgba(0, 194, 255, 0.5);
            }
            
            .timer-item.running .clickable-time:hover,
            .timer-item.running .clickable-time-container:hover,
            .timer-item.running .time-display:hover {
                color: var(--accent-warning, #ff9800);
                text-shadow: 0 0 8px rgba(255, 152, 0, 0.5);
            }
        `;
        document.head.appendChild(style);
    });

}); // End DOMContentLoaded