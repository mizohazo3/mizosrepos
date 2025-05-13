document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/'; // Adjust if your PHP files are elsewhere
    const POLLING_INTERVAL_MS = 5000; // Poll server state every 5 seconds
    const UI_TICK_INTERVAL_MS = 1000; // Update running timer displays every 1 second

    // --- State Variables ---
    let timers = {}; // Key: timer_id, Value: timer data object from get_data.php
    let bankBalance = 0.0;
    let difficultyMultiplier = 1.0;
    let levelsConfig = {}; // Key: level number, Value: level config object from get_data.php
    let pollingIntervalId = null;
    let uiTickIntervalId = null;
    let isPolling = false; // Flag to prevent concurrent polls

    // --- DOM Elements ---
    const timersContainer = document.getElementById('timers-container');
    const bankBalanceEl = document.getElementById('bank-balance');
    const statusMessageEl = document.getElementById('status-message');
    const difficultySelect = document.getElementById('difficulty-select');
    const setDifficultyBtn = document.getElementById('set-difficulty-btn');
    const difficultyStatusEl = document.getElementById('difficulty-status');
    const stopAllBtn = document.getElementById('stop-all-btn');
    const resetAllBtn = document.getElementById('reset-all-btn');
    const newTimerNameInput = document.getElementById('new-timer-name');
    const addTimerBtn = document.getElementById('add-timer-btn');
    const addTimerStatusEl = document.getElementById('add-timer-status');

    // --- Utility Functions ---
    function formatTime(totalSeconds) {
        if (isNaN(totalSeconds) || totalSeconds < 0) {
             return '00:00:00';
        }
        totalSeconds = Math.floor(totalSeconds);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    function displayStatus(message, isError = false, element = statusMessageEl, duration = 5000) {
        element.textContent = message;
        element.className = isError ? 'error' : 'success';
        if (duration > 0) {
             setTimeout(() => {
                 if (element.textContent === message) { // Only clear if it hasn't been overwritten
                    element.textContent = '';
                    element.className = '';
                }
            }, duration);
        }
    }

    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        };
        if (body) {
            options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, options);
            const responseText = await response.text(); // Get text first for better debugging

            if (!response.ok) {
                let errorMsg = `HTTP error ${response.status} calling ${endpoint}`;
                try {
                    const errorData = JSON.parse(responseText); // Try to parse error JSON
                    errorMsg = errorData.message || errorMsg;
                } catch (e) {
                     errorMsg += ` - Response: ${responseText.substring(0, 100)}`; // Include partial non-JSON response
                }
                throw new Error(errorMsg);
            }

             // Handle empty successful responses (e.g., 204 No Content or just status:success)
             if (!responseText) {
                return { status: 'success', message: 'Action successful (no content)'};
             }

             try {
                 const data = JSON.parse(responseText);
                 return data;
             } catch (e) {
                 throw new Error(`Failed to parse JSON response from ${endpoint}: ${e.message} - Response: ${responseText.substring(0,100)}`);
             }

        } catch (error) {
            // Display a more persistent error for API call failures
            displayStatus(`Network/Server Error: ${error.message}`, true, statusMessageEl, 0);
            return { status: 'error', message: error.message }; // Return error structure
        }
    }


    // --- Timer Rendering and UI Updates ---

    // Calculates the current total seconds for a timer (live)
    function calculateCurrentSeconds(timerData) {
         let currentTotalSeconds = parseFloat(timerData.accumulated_seconds) || 0;
         if (timerData.is_running && timerData.start_time) {
             // IMPORTANT: Server time might drift. Parsing assumes server provides a format JS can handle.
             // The `replace` and `+ 'Z'` is often needed if the PHP time isn't strictly ISO8601 UTC.
             try {
                 const startTimeMs = new Date(timerData.start_time.replace(' ', 'T') + 'Z').getTime();
                 if (!isNaN(startTimeMs)) {
                     const nowMs = Date.now();
                     const elapsedMs = Math.max(0, nowMs - startTimeMs);
                     currentTotalSeconds += elapsedMs / 1000;
                 } else {
                     // Display accumulated only if live calculation fails
                 }
             } catch (e) {
                 // Error parsing start_time
             }
         }
         return currentTotalSeconds;
    }


    // Renders a single timer's HTML or updates an existing one
    function renderTimer(timerId) {
        const timerData = timers[timerId];
        if (!timerData) {
            return;
        }

        let timerEl = document.getElementById(`timer-${timerId}`);
        const isNew = !timerEl;

        if (isNew) {
            timerEl = document.createElement('div');
            timerEl.id = `timer-${timerId}`;
            timerEl.classList.add('timer');
            timersContainer.appendChild(timerEl);
        }

        // Calculate display time and check running state based on potentially live data
        const currentTotalSeconds = calculateCurrentSeconds(timerData);
        const isEffectivelyRunning = timerData.is_running && timerData.start_time && !isNaN(new Date(timerData.start_time.replace(' ', 'T') + 'Z').getTime());

        // Update running class
        timerEl.classList.toggle('running', isEffectivelyRunning);

        // --- Level & Progress Calculation ---
        const currentLevel = parseInt(timerData.current_level) || 1;
        const currentLevelInfo = levelsConfig[currentLevel];
        const nextLevelInfo = levelsConfig[currentLevel + 1];
        const currentTotalHours = currentTotalSeconds / 3600.0;

        let rankName = timerData.rank_name || (currentLevelInfo ? currentLevelInfo.rank_name : 'N/A');
        let rewardRate = timerData.reward_rate_per_hour || (currentLevelInfo ? currentLevelInfo.reward_rate_per_hour : '0.00');

        // Effective hours required (using current difficultyMultiplier)
        const getEffectiveHoursRequired = (levelData) => {
            if (!levelData) return null; // Max level or invalid data
            if (parseInt(levelData.level) === 1) return 0.0;
            const baseHours = parseFloat(levelData.hours_required);
            if (isNaN(baseHours)) return null;
            return Math.max(0, baseHours * difficultyMultiplier); // Ensure non-negative
        };

        const currentLevelEffectiveHours = getEffectiveHoursRequired(currentLevelInfo) ?? 0; // Default to 0 if null
        const nextLevelEffectiveHours = getEffectiveHoursRequired(nextLevelInfo); // Can be null

        let progressText = '';
        let nextHoursDisplay = 'Max Level';
        if (nextLevelEffectiveHours !== null && nextLevelEffectiveHours > currentLevelEffectiveHours) {
            nextHoursDisplay = nextLevelEffectiveHours.toFixed(4);
            const hoursTowardsNext = Math.max(0, currentTotalHours - currentLevelEffectiveHours);
            const hoursNeededForLevel = nextLevelEffectiveHours - currentLevelEffectiveHours;
            const percentage = Math.min(100, (hoursTowardsNext / hoursNeededForLevel) * 100);
            progressText = ` (${percentage.toFixed(1)}%)`;
        } else if (nextLevelEffectiveHours !== null) {
            // Handle cases where next level hours might be <= current (data error?)
             nextHoursDisplay = nextLevelEffectiveHours.toFixed(4) + " (Check Config)";
        }
        // --- End Level & Progress ---

        // Update Inner HTML
        timerEl.innerHTML = `
            <h3>${timerData.name || 'Unnamed Timer'} (ID: ${timerId})</h3>
            <div class="timer-display">Time: <span class="time">${formatTime(currentTotalSeconds)}</span></div>
            <div class="level-info">
                Level: ${currentLevel} (${rankName})
                <br>
                Reward/hr: $${parseFloat(rewardRate).toFixed(2)}
                <br>
                Total Hours: ${currentTotalHours.toFixed(4)}
                <br>
                Next Level Hours: ${nextHoursDisplay}${progressText}
            </div>
            <div class="timer-controls">
                <button class="start-btn" data-id="${timerId}" ${isEffectivelyRunning ? 'disabled' : ''}>Start</button>
                <button class="stop-btn" data-id="${timerId}" ${!isEffectivelyRunning ? 'disabled' : ''}>Stop</button>
            </div>
        `;
    }

    // Fast UI tick - only updates time display for running timers
    function updateUITick() {
        for (const timerId in timers) {
            const timerData = timers[timerId];
            // Check if timer *should* be running based on local state
            if (timerData.is_running && timerData.start_time) {
                const timerEl = document.getElementById(`timer-${timerId}`);
                if (timerEl) {
                    // Re-render just this timer to update time and progress %
                     renderTimer(timerId);
                }
            }
        }
    }

     // Processes the full data from get_data.php and updates the entire UI accordingly
     function updateFullUI(data) {
         displayStatus("Syncing data...", false, statusMessageEl, 1000); // Short status

         // --- Update Global State ---
         difficultyMultiplier = parseFloat(data.difficulty_multiplier) || 1.0;
         bankBalance = parseFloat(data.bank_balance) || 0.0;
         levelsConfig = data.levels_config || {}; // Expecting object keyed by level

         // --- Update DOM for Global State ---
         bankBalanceEl.textContent = bankBalance.toFixed(4);
         // Ensure difficulty dropdown reflects the actual state
         if (difficultySelect.value !== String(data.difficulty_multiplier)) {
             difficultySelect.value = String(data.difficulty_multiplier);
         }

         // --- Update Timers ---
         const receivedTimers = data.timers || [];
         const receivedTimerIds = new Set();
         const changedTimerIds = new Set();

         // 1. Update existing timers and identify changes/new timers
         receivedTimers.forEach(newTimerData => {
             const timerId = newTimerData.id;
             receivedTimerIds.add(timerId);
             const existingTimerData = timers[timerId];

             // Normalize boolean/numeric types from PHP/JSON
             newTimerData.is_running = !!parseInt(newTimerData.is_running);
             newTimerData.accumulated_seconds = parseFloat(newTimerData.accumulated_seconds) || 0;
             newTimerData.current_level = parseInt(newTimerData.current_level) || 1;
             // Make sure essential level info is present directly on timer object if available
             const levelInfo = levelsConfig[newTimerData.current_level];
             newTimerData.rank_name = newTimerData.rank_name || (levelInfo ? levelInfo.rank_name : 'N/A');
             newTimerData.reward_rate_per_hour = newTimerData.reward_rate_per_hour || (levelInfo ? levelInfo.reward_rate_per_hour : '0.00');


             // Simple comparison (can be improved for complex objects)
             // Compare key fields relevant for rendering/state
             const hasChanged = !existingTimerData ||
                                existingTimerData.name !== newTimerData.name ||
                                existingTimerData.is_running !== newTimerData.is_running ||
                                existingTimerData.current_level !== newTimerData.current_level ||
                                // Only compare accumulated if NOT running (live calc handles running time)
                                (!newTimerData.is_running && existingTimerData.accumulated_seconds !== newTimerData.accumulated_seconds) ||
                                // Compare start_time only if it just started/stopped
                                (existingTimerData.is_running !== newTimerData.is_running && existingTimerData.start_time !== newTimerData.start_time);


             if (hasChanged) {
                 timers[timerId] = newTimerData; // Update local state
                 changedTimerIds.add(timerId); // Mark for re-render
             }
         });

         // 2. Identify and remove timers deleted on the server
         const timersToRemove = [];
         for (const existingIdStr in timers) {
             const existingId = parseInt(existingIdStr);
             if (!receivedTimerIds.has(existingId)) {
                 timersToRemove.push(existingId);
             }
         }

         // 3. Perform DOM updates (Remove, then Render changes)
         timersToRemove.forEach(timerId => {
             const timerEl = document.getElementById(`timer-${timerId}`);
             if (timerEl) {
                 timerEl.remove();
             }
             delete timers[timerId]; // Remove from local state
         });

         changedTimerIds.forEach(timerId => {
             renderTimer(timerId); // Render new or updated timer
         });


         // --- Ensure UI Tick is Running ---
         if (!uiTickIntervalId) {
             startUITick();
         }
     }


    // --- API Interaction Functions ---

    async function fetchInitialData() {
        displayStatus("Loading initial data...", false, statusMessageEl, 0); // Persistent while loading
        const data = await apiCall('get_data.php');
        if (data && data.status === 'success') {
            updateFullUI(data);
            displayStatus("Data loaded.", false);
            startPolling(); // Start polling loop *after* initial load succeeds
            startUITick(); // Start visual updates
        } else {
            // Error already displayed by apiCall
            displayStatus(`Failed to load initial data: ${data.message || 'Unknown error'}. Please refresh.`, true, statusMessageEl, 0);
            stopPolling(); // Don't poll if initial load failed
            stopUITick();
        }
    }

    async function handleTimerAction(action, timerId) {
        const timerData = timers[timerId];

        // Basic validation
        if (!action || (!timerId && !['stop_all', 'reset_all'].includes(action))) {
             return;
        }
        if (timerId && !timerData && !['stop_all', 'reset_all'].includes(action)) {
            pollServer();
            return;
        }

        // --- Optimistic UI Update ---
        let statusMsg = '';
        if (action === 'start' && timerData) {
            statusMsg = `Starting timer ${timerId}...`;
            timerData.is_running = true;
            timerData.start_time = new Date().toISOString().slice(0, 19).replace('T', ' '); // Approximate start time
            renderTimer(timerId); // Update UI immediately
        } else if (action === 'stop' && timerData) {
            statusMsg = `Stopping timer ${timerId}...`;
            // Calculate approximate new accumulated time visually
            timerData.accumulated_seconds = calculateCurrentSeconds(timerData);
            timerData.is_running = false;
            timerData.start_time = null;
            renderTimer(timerId); // Update UI immediately
        } else if (action === 'stop_all') {
            statusMsg = 'Stopping all timers...';
             Object.keys(timers).forEach(id => {
                if (timers[id].is_running) {
                     timers[id].accumulated_seconds = calculateCurrentSeconds(timers[id]); // Approx
                     timers[id].is_running = false;
                     timers[id].start_time = null;
                     renderTimer(id);
                }
             });
        } else if (action === 'reset_all') {
            statusMsg = 'Resetting all timers...';
             Object.keys(timers).forEach(id => {
                 timers[id].accumulated_seconds = 0;
                 timers[id].is_running = false;
                 timers[id].start_time = null;
                 timers[id].current_level = 1;
                 timers[id].notified_level = 1; // Reset based on PHP
                  // Update rank/reward based on level 1 config
                 const level1Config = levelsConfig[1];
                 timers[id].rank_name = level1Config ? level1Config.rank_name : 'N/A';
                 timers[id].reward_rate_per_hour = level1Config ? level1Config.reward_rate_per_hour : '0.00';
                 renderTimer(id);
             });
        }
        if (statusMsg) displayStatus(statusMsg, false, statusMessageEl, 3000);
        // --- End Optimistic Update ---


        // --- Send Request to Server ---
        const payload = { action: action };
        if (timerId && !['stop_all', 'reset_all'].includes(action)) {
           payload.id = timerId;
        }
        const response = await apiCall('timer_action.php', 'POST', payload);

        // --- Handle Response ---
        if (response && response.status === 'success') {
            displayStatus(`Action '${action}' successful.`, false);

            // Refine local state with server response data (more accurate than optimistic)
            if (action === 'start' && response.startTime && timerData) {
                timers[timerId].start_time = response.startTime; // Use server's start time
                timers[timerId].is_running = true; // Confirm running
                renderTimer(timerId); // Re-render with confirmed data
            } else if (action === 'stop' && response.accumulated_seconds !== undefined && timerData) {
                timers[timerId].accumulated_seconds = parseFloat(response.accumulated_seconds); // Use server's time
                timers[timerId].is_running = false;
                timers[timerId].start_time = null;
                renderTimer(timerId); // Re-render with confirmed data
            } else if (action === 'stop_all' && response.stopped_timers) {
                response.stopped_timers.forEach(stopped => {
                    if (timers[stopped.id]) {
                        timers[stopped.id].accumulated_seconds = parseFloat(stopped.accumulated_seconds);
                        timers[stopped.id].is_running = false;
                        timers[stopped.id].start_time = null;
                        renderTimer(stopped.id); // Re-render with confirmed data
                    }
                });
            } else if (action === 'reset_all' && response.reset_timers) {
                 // Optimistic update is likely good, but let's force a poll to get fresh level/rank data too
                 pollServer();
            }

        } else if (response && response.status === 'warning') {
            // E.g., tried to stop an already stopped timer. The poll will fix UI state.
            displayStatus(`Warning: ${response.message}`, false);
            pollServer(); // Get definitive state from server to correct UI if needed

        } else {
            // Handle actual error (already logged by apiCall)
            displayStatus(`Action '${action}' failed: ${response.message || 'Unknown error'}`, true);
            // Revert optimistic UI change by fetching the real state from the server
            pollServer();
        }
    }

    async function handleAddTimer() {
        const name = newTimerNameInput.value.trim();
        if (!name) {
            displayStatus("Timer name cannot be empty.", true, addTimerStatusEl);
            return;
        }
        addTimerBtn.disabled = true;
        displayStatus("Adding timer...", false, addTimerStatusEl);

        const response = await apiCall('add_timer.php', 'POST', { name: name });

        if (response && response.status === 'success' && response.timer) {
            const newTimer = response.timer;

             // Normalize types and add level info from config
             newTimer.is_running = !!parseInt(newTimer.is_running);
             newTimer.accumulated_seconds = parseFloat(newTimer.accumulated_seconds) || 0;
             newTimer.current_level = parseInt(newTimer.current_level) || 1;
             const levelInfo = levelsConfig[newTimer.current_level];
             newTimer.rank_name = newTimer.rank_name || (levelInfo ? levelInfo.rank_name : 'N/A');
             newTimer.reward_rate_per_hour = newTimer.reward_rate_per_hour || (levelInfo ? levelInfo.reward_rate_per_hour : '0.00');


            timers[newTimer.id] = newTimer; // Add to local state
            renderTimer(newTimer.id); // Render the new timer
            newTimerNameInput.value = ''; // Clear input
            displayStatus("Timer added successfully.", false, addTimerStatusEl);
        } else {
            displayStatus(`Failed to add timer: ${response.message || 'Unknown error'}`, true, addTimerStatusEl);
        }
        addTimerBtn.disabled = false;
    }

    async function handleSetDifficulty() {
        const selectedMultiplier = difficultySelect.value;
        displayStatus("Setting difficulty...", false, difficultyStatusEl);
        setDifficultyBtn.disabled = true;

        const response = await apiCall('difficulty_handler.php', 'POST', {
            action: 'set_difficulty',
            multiplier: selectedMultiplier
        });

        if (response && response.status === 'success') {
            // Update local multiplier *before* re-rendering
            difficultyMultiplier = parseFloat(selectedMultiplier);
            displayStatus("Difficulty updated. Recalculating...", false, difficultyStatusEl);
            // Re-render all timers to reflect new hour requirements & progress %
            Object.keys(timers).forEach(timerId => renderTimer(timerId));
        } else {
            displayStatus(`Failed to set difficulty: ${response.message || 'Unknown error'}`, true, difficultyStatusEl);
            // Revert dropdown to the last known good value from state
            difficultySelect.value = String(difficultyMultiplier);
        }
        setDifficultyBtn.disabled = false;
    }


    // --- Polling Logic ---

    async function pollServer() {
        // Prevent multiple polls running at the same time if one takes too long
        if (isPolling) {
            return;
        }
        isPolling = true;

        const data = await apiCall('get_data.php');
        if (data && data.status === 'success') {
            updateFullUI(data); // Update UI with the latest full state
        } else {
            // Error already logged by apiCall. Don't constantly update status for poll failures.
            // Consider stopping polling after multiple consecutive failures?
        }
        isPolling = false;
    }

    function startPolling() {
        if (pollingIntervalId) clearInterval(pollingIntervalId); // Clear existing interval
        // Run first poll immediately, then set interval
        pollServer().then(() => {
            pollingIntervalId = setInterval(pollServer, POLLING_INTERVAL_MS);
        });
    }

    function stopPolling() {
        clearInterval(pollingIntervalId);
        pollingIntervalId = null;
        isPolling = false;
    }

    function startUITick() {
        if (uiTickIntervalId) clearInterval(uiTickIntervalId);
        uiTickIntervalId = setInterval(updateUITick, UI_TICK_INTERVAL_MS);
    }

     function stopUITick() {
        clearInterval(uiTickIntervalId);
        uiTickIntervalId = null;
    }


    // --- Event Listeners ---

    // Use event delegation for timer controls (more efficient)
    timersContainer.addEventListener('click', (event) => {
        const target = event.target;
        if (target.classList.contains('start-btn')) {
            target.disabled = true; // Disable button immediately
            const id = target.dataset.id;
            handleTimerAction('start', parseInt(id));
        } else if (target.classList.contains('stop-btn')) {
            target.disabled = true; // Disable button immediately
            const id = target.dataset.id;
            handleTimerAction('stop', parseInt(id));
        }
    });

    addTimerBtn.addEventListener('click', handleAddTimer);
    newTimerNameInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            handleAddTimer();
        }
    });

    setDifficultyBtn.addEventListener('click', handleSetDifficulty);
    stopAllBtn.addEventListener('click', () => handleTimerAction('stop_all', null));
    resetAllBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to reset ALL timers, levels, and progress? This cannot be undone.')) {
            handleTimerAction('reset_all', null);
        }
    });

    // Optional: Pause polling/ticking when tab is not visible to save resources
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            stopUITick();
        } else {
            // Fetch latest data immediately when tab becomes visible again
            fetchInitialData(); // This will restart polling/ticking on success
        }
    });

    // --- Initialisation ---
    fetchInitialData(); // Start the application

});