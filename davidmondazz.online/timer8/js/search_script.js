document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';
    const POLL_INTERVAL = 30000; // 30 seconds between updates
    const SEARCH_DEBOUNCE_DELAY = 300; // Debounce delay for search input in milliseconds
    const STORAGE_KEY_SEARCH_TERM = 'timer8_search_term'; // Key for storing search term

    // --- DOM Elements ---
    const timerList = document.getElementById('timer-list');
    const timerTemplate = document.getElementById('timer-template');
    const searchInput = document.getElementById('search-input');
    const connectionStatus = document.getElementById('connection-status');
    const currentBalanceEl = document.getElementById('current-balance');
    const stopAllBtn = document.getElementById('stop-all-btn');
    const notificationContainer = document.getElementById('notification-container');
    const searchPlaceholder = document.getElementById('search-placeholder');
    const searchResults = document.getElementById('search-results');

    // --- State Management ---
    let timers = {}; // All timer data keyed by timer ID
    let levelsConfig = {}; // Cached level configuration
    let isPolling = false;
    let pollTimeoutId = null;
    let uiTickIntervalId = null;
    let hasSearched = false; // Track if user has searched
    const pendingActions = new Set(); // Track timers with pending actions
    let searchDebounceTimer = null; // For debouncing search input
    let difficultyMultiplier = 1.0; // Default difficulty multiplier

    // --- Utility Functions ---
    function formatTimeWithMilliseconds(totalSeconds) {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = Math.floor(totalSeconds % 60);
        const milliseconds = Math.floor((totalSeconds % 1) * 100);

        return {
            hours: hours.toString().padStart(2, '0'),
            minutes: minutes.toString().padStart(2, '0'),
            seconds: seconds.toString().padStart(2, '0'),
            milliseconds: milliseconds.toString().padStart(2, '0')
        };
    }

    function calculateCurrentSeconds(timerData) {
        if (!timerData) return 0;
        const baseSeconds = parseFloat(timerData.accumulated_seconds) || 0;
        if (!timerData.is_running || !timerData.start_time) return baseSeconds;
        try {
            // Ensure correct parsing, assuming UTC from backend
            const startTime = new Date(timerData.start_time.replace(' ', 'T') + 'Z');
            if (isNaN(startTime.getTime())) { // Check for invalid date
                 return baseSeconds;
            }
            const currentTime = new Date();
            const secondsSinceStart = (currentTime.getTime() - startTime.getTime()) / 1000;
            return baseSeconds + Math.max(0, secondsSinceStart);
        } catch (error) {
            return baseSeconds;
        }
    }


    function calculateCurrentEarnings(timerData, currentSeconds) {
        if (!timerData || !timerData.is_running) return 0;

        const baseSeconds = parseFloat(timerData.accumulated_seconds) || 0;
        const additionalSeconds = Math.max(0, currentSeconds - baseSeconds);
        const hourlyRate = parseFloat(timerData.reward_rate_per_hour) || 0;

        return (additionalSeconds / 3600) * hourlyRate;
    }

    // --- Data Fetching ---
    async function apiCall(endpoint, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
            if (!response.ok) {
                const responseText = await response.text();
                let errorMsg = `HTTP error ${response.status}`;
                try {
                    const errorJson = JSON.parse(responseText);
                    if (errorJson && errorJson.message) {
                        errorMsg = errorJson.message;
                    }
                } catch (e) { /* Ignore parsing error */ }
                throw new Error(errorMsg);
            }
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return await response.json();
            } else {
                return { status: 'warning', message: 'Non-JSON response', data: await response.text() };
            }
        } catch (error) {
            updateConnectionStatus('error');
            return { status: 'error', message: error.message || 'Network or fetch error' };
        }
    }


    // Load all timers when page loads
    async function fetchInitialData() {
        updateConnectionStatus('connecting');
        try {
            const response = await apiCall('get_data.php?filter=all'); // Fetch all initially

            if (response && response.status === 'success') {
                updateFullUI(response);
                updateConnectionStatus('connected');
                startPolling();
                startUITick();
                
                // Check if there's a saved search term and restore it
                restorePreviousSearch();
            } else {
                updateConnectionStatus('error');
                displayNotification(response?.message || 'Failed to fetch initial data', 'error');
                throw new Error(response?.message || 'Failed to fetch initial data');
            }
        } catch (error) {
            updateConnectionStatus('error');
            displayNotification(`Error loading data: ${error.message}`, 'error');
        }
    }
    
    // Function to restore the previous search
    function restorePreviousSearch() {
        const savedSearchTerm = localStorage.getItem(STORAGE_KEY_SEARCH_TERM);
        
        if (savedSearchTerm && searchInput) {
            // Set the search input value
            searchInput.value = savedSearchTerm;
            
            // Show/hide clear button based on input value
            const clearButton = document.getElementById('search-clear');
            if (clearButton && savedSearchTerm.trim()) {
                clearButton.classList.add('visible');
            }
            
            // Execute the search
            filterTimers();
        } else {
            // If no saved search, show the placeholder
            const timerList = document.getElementById('timer-list');
            if (timerList) {
                timerList.style.display = 'none';
            }
            
            const placeholder = document.getElementById('search-placeholder');
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
        }
    }

    // Show search placeholder with custom message
    function showSearchPlaceholder(message) {
        if (!timerList) return;
        
        // Clear timer list
        while (timerList.firstChild) {
            timerList.removeChild(timerList.firstChild);
        }
        
        // Create placeholder
        const placeholder = document.createElement('div');
        placeholder.id = 'search-placeholder';
        placeholder.className = 'search-placeholder';
        placeholder.innerHTML = `
            <div class="search-icon">🔍</div>
            <p>${message}</p>
        `;
        
        // Add styles for the placeholder
        const style = document.createElement('style');
        style.textContent = `
            .search-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 30px;
                text-align: center;
                color: var(--text-secondary, #999);
                background: var(--bg-secondary, #2a2a2a);
                border-radius: 8px;
                margin: 20px 0;
            }
            
            .search-placeholder .search-icon {
                font-size: 2rem;
                margin-bottom: 10px;
            }
            
            .search-placeholder p {
                margin: 0;
                font-size: 1.1rem;
            }
        `;
        document.head.appendChild(style);
        
        // Add placeholder to timer list
        timerList.appendChild(placeholder);
    }

    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        pollData(); // Start immediately
    }

    function stopPolling() {
        isPolling = false;
        if (pollTimeoutId) {
            clearTimeout(pollTimeoutId);
            pollTimeoutId = null;
        }
    }

    async function pollData() {
        if (!isPolling) return;

        try {
            const response = await apiCall('get_data.php?filter=all');

            if (response && response.status === 'success') {
                updateFullUI(response);
                updateConnectionStatus('connected');
            } else if (response && response.status === 'error') {
                 updateConnectionStatus('error');
            }
             else {
                updateConnectionStatus('error');
            }
        } catch (error) {
            updateConnectionStatus('error');
        } finally {
             if (isPolling) {
                pollTimeoutId = setTimeout(pollData, POLL_INTERVAL);
             }
        }
    }


    function startUITick() {
        if (uiTickIntervalId) return;
        uiTickIntervalId = setInterval(updateUITick, 50); // Increase refresh rate from 100ms to 50ms for smoother display
    }

    function stopUITick() {
        if (uiTickIntervalId) {
            clearInterval(uiTickIntervalId);
            uiTickIntervalId = null;
        }
    }

    // --- UI Updates ---
    function updateConnectionStatus(status) {
        if (!connectionStatus) return;
        switch (status) {
            case 'connecting':
                connectionStatus.textContent = 'Status: Connecting...';
                connectionStatus.style.color = 'var(--accent-warning)';
                break;
            case 'connected':
                connectionStatus.textContent = 'Status: Connected';
                connectionStatus.style.color = 'var(--accent-secondary)';
                break;
            case 'error':
                connectionStatus.textContent = 'Status: Connection Error';
                connectionStatus.style.color = 'var(--accent-error)';
                break;
            default:
                connectionStatus.textContent = `Status: ${status}`;
        }
    }

    function updateFullUI(data) {
        // Update levels config if provided
        if (data.levels && typeof data.levels === 'object' && Object.keys(data.levels).length > 0) {
            levelsConfig = data.levels;
        }

        // Update difficulty multiplier if provided
        if (data.difficulty_multiplier !== undefined) {
            difficultyMultiplier = parseFloat(data.difficulty_multiplier) || 1.0;
        }

        // Update timers data
        if (data.timers && typeof data.timers === 'object') {
            const previousTimers = {...timers};
            const receivedTimerIds = new Set();

            // Normalize and update timer data
            for (const timerId in data.timers) {
                const currentTimerId = String(timerId);
                receivedTimerIds.add(currentTimerId);
                const timerData = data.timers[currentTimerId];

                if (!timerData || typeof timerData !== 'object' || !timerData.id) {
                    continue;
                }

                // Ensure internal ID matches the key (check removed as backend is fixed)
                const internalId = String(timerData.id);

                // Normalize data types
                timerData.is_running = !!parseInt(timerData.is_running || 0);
                timerData.is_pinned = !!parseInt(timerData.is_pinned || 0);
                timerData.accumulated_seconds = parseFloat(timerData.accumulated_seconds || 0);
                timerData.current_level = parseInt(timerData.current_level || 1);
                timerData.id = String(timerData.id);

                // Add level-specific data
                if (levelsConfig && levelsConfig[timerData.current_level]) {
                    const levelInfo = levelsConfig[timerData.current_level];
                    timerData.rank_name = timerData.rank_name || levelInfo.rank_name || 'Unknown Rank';
                    timerData.reward_rate_per_hour = timerData.reward_rate_per_hour || levelInfo.reward_rate_per_hour || '0.00';
                } else {
                     timerData.rank_name = timerData.rank_name || 'Unknown Rank';
                     timerData.reward_rate_per_hour = timerData.reward_rate_per_hour || '0.00';
                }

                // Store the updated timer data
                timers[currentTimerId] = timerData;
            }

            // Check for removed timers
            for (const timerId in previousTimers) {
                if (!receivedTimerIds.has(timerId)) {
                    delete timers[timerId];
                    // If this timer is currently displayed, remove it from the DOM
                    const timerEl = timerList?.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                    if (timerEl) {
                        timerEl.remove();
                    }
                }
            }
        }

        // Update bank balance if provided
        if (data.bank_balance !== undefined && currentBalanceEl) {
            currentBalanceEl.textContent = parseFloat(data.bank_balance).toFixed(2);
        }
        
        // If there are already search results displayed, update them with fresh data
        if (timerList && timerList.querySelectorAll('.timer-item').length > 0) {
            // Update displayed timers with fresh data without clearing the list
            Array.from(timerList.querySelectorAll('.timer-item')).forEach(timerEl => {
                const timerId = timerEl.dataset.timerId;
                if (timerId && timers[timerId]) {
                    renderTimer(timerId);
                }
            });
        }
    }


    function renderTimer(timerId, animationIndex = 0) {
        // Ensure timerId is a string and properly formatted
        timerId = String(timerId).trim();
        if (!timerId) {
            console.error("renderTimer called with invalid timerId");
            return;
        }

        const timerData = timers[timerId];
        if (!timerData) {
            console.warn(`No data found for timerId: ${timerId} in renderTimer`);
            const existingEl = document.querySelector(`[data-timer-id="${timerId}"]`);
            if (existingEl) existingEl.remove();
            return;
        }

        let timerEl = timerList.querySelector(`.timer-item[data-timer-id='${timerId}']`);

        if (!timerEl) {
            const template = document.getElementById('timer-template');
            if (!template) {
                console.error("Timer template not found!");
                return;
            }

            timerEl = template.content.cloneNode(true).querySelector('.timer-item');
            if (!timerEl) {
                console.error("Cloned template content is invalid");
                return;
            }
            timerEl.dataset.timerId = timerId;
            
            // Set animation index for staggered animation
            timerEl.style.setProperty('--animation-index', animationIndex);
            
            timerList.appendChild(timerEl);
        }

        // Update timer name and ID
        const timerIdEl = timerEl.querySelector('.timer-id');
        const timerNameEl = timerEl.querySelector('.timer-name');
        if (timerIdEl) timerIdEl.textContent = `#${timerId}`;
        if (timerNameEl) timerNameEl.textContent = timerData.name || 'Unnamed Timer';

        // Update pin icon state
        const pinIcon = timerEl.querySelector('.pin-icon');
        if (pinIcon) {
            const isPinned = !!timerData.is_pinned;
            pinIcon.classList.toggle('off', !isPinned); // Toggle 'off' class based on pinned state
            pinIcon.textContent = isPinned ? '📌' : '📍'; // Set icon based on pinned state
            pinIcon.title = isPinned ? 'Unpin Timer' : 'Pin Timer'; // Set title
        }

        // Add 'pinned' class to the timer element
        timerEl.classList.toggle('pinned', !!timerData.is_pinned);

        // Update timer display
        const currentTimeEl = timerEl.querySelector('.current-time');
        const millisecondsEl = timerEl.querySelector('.milliseconds');
        const accumulatedTimeEl = timerEl.querySelector('.accumulated-time');
        
        if (currentTimeEl && millisecondsEl && accumulatedTimeEl) {
            const totalSeconds = calculateCurrentSeconds(timerData);
            const timeDisplay = formatTimeWithMilliseconds(totalSeconds);
            
            currentTimeEl.textContent = `${timeDisplay.hours}:${timeDisplay.minutes}:${timeDisplay.seconds}`;
            millisecondsEl.textContent = timeDisplay.milliseconds;
            
            const totalHours = totalSeconds / 3600;
            accumulatedTimeEl.textContent = `Total: ${totalHours.toFixed(2)}h`;
        }

        // Update level and rank
        const levelEl = timerEl.querySelector('.timer-level');
        const rankEl = timerEl.querySelector('.timer-rank');
        if (levelEl) levelEl.textContent = timerData.current_level || 1;
        if (rankEl) rankEl.textContent = timerData.rank_name || 'Novice';

        // Update reward rate
        const rewardRateEl = timerEl.querySelector('.timer-reward-rate');
        if (rewardRateEl) {
            const rate = parseFloat(timerData.reward_rate_per_hour || 0).toFixed(2);
            rewardRateEl.textContent = `$${rate}/hr`;
        }

        // Calculate and update progress
        const currentTotalHours = (calculateCurrentSeconds(timerData) || 0) / 3600;
        calculateAndRenderProgress(timerEl, timerData, currentTotalHours);

        // Add appropriate classes based on state
        timerEl.classList.toggle('timer-running', !!timerData.is_running);
        timerEl.classList.toggle('timer-pinned', !!timerData.is_pinned);

        // Add action buttons for timer controls
        addTimerActionButtons(timerEl, timerData);
    }

    // Calculate and render timer progress bar
    function calculateAndRenderProgress(timerEl, timerData, currentTotalHours) {
        if (!timerEl || !timerData || !levelsConfig) return;

        try {
            const currentLevel = parseInt(timerData.current_level) || 1;
            const maxLevel = Math.max(...Object.keys(levelsConfig).map(k => parseInt(k)));
            const isMaxLevel = currentLevel >= maxLevel;

            // Calculate hours needed for level
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
            console.error("Error calculating progress:", error);
        }
    }

    // Add action buttons to timer element
    function addTimerActionButtons(timerEl, timerData) {
        const actionsContainer = timerEl.querySelector('.timer-actions');
        if (!actionsContainer) return;

        // Clear existing buttons
        actionsContainer.innerHTML = '';

        // Create buttons based on timer state
        if (timerData.is_running) {
            // Stop button for running timers
            const stopBtn = document.createElement('button');
            stopBtn.className = 'stop-button action-button';
            stopBtn.innerHTML = '⏹️ Stop';
            stopBtn.addEventListener('click', () => handleTimerAction('stop', timerData.id, timerEl));
            actionsContainer.appendChild(stopBtn);
        } else {
            // Start button for stopped timers
            const startBtn = document.createElement('button');
            startBtn.className = 'start-button action-button';
            startBtn.innerHTML = '▶️ Start';
            startBtn.addEventListener('click', () => handleTimerAction('start', timerData.id, timerEl));
            actionsContainer.appendChild(startBtn);
        }
    }

    // Update UI for running timers
    function updateUITick() {
        if (!timers || Object.keys(timers).length === 0) return;

        const now = Date.now();
        const updateSecondaryInfo = now % 500 < 50; // Update less critical info less often

        // Update only running timers' UI for performance
        for (const timerId in timers) {
            const timerData = timers[timerId];
            if (timerData && timerData.is_running) {
                const timerEl = timerList.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                if (!timerEl || timerEl.style.display === 'none') continue; // Skip hidden timers

                const currentSeconds = calculateCurrentSeconds(timerData);
                const timeObj = formatTimeWithMilliseconds(currentSeconds);

                // Always update timer display - this is the most important visual element
                const timerDisplay = timerEl.querySelector('.current-time');
                const millisecondsDisplay = timerEl.querySelector('.milliseconds');
                
                if (timerDisplay) timerDisplay.textContent = `${timeObj.hours}:${timeObj.minutes}:${timeObj.seconds}`;
                if (millisecondsDisplay) millisecondsDisplay.textContent = timeObj.milliseconds;
                
                // Update less critical elements less frequently to improve performance
                if (updateSecondaryInfo) {
                    const currentHours = currentSeconds / 3600.0;
                    const accumulatedTime = timerEl.querySelector('.accumulated-time');
                    
                    if (accumulatedTime) accumulatedTime.textContent = `Total: ${currentHours.toFixed(2)}h`;
                    
                    calculateAndRenderProgress(timerEl, timerData, currentHours);
                }
            }
        }
    }

    // --- Search function implementation ---
    function filterTimers() {
        if (!searchInput || !timerList) return;

        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // Save the search term to localStorage (even if empty)
        localStorage.setItem(STORAGE_KEY_SEARCH_TERM, searchTerm);
        
        // If search term is empty, show the placeholder and hide the list
        if (!searchTerm) {
            // Clear timer list
            while (timerList.firstChild) {
                timerList.removeChild(timerList.firstChild);
            }
            
            // Hide timer list, show placeholder
            timerList.style.display = 'none';
            
            const placeholder = document.getElementById('search-placeholder');
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
            
            return;
        }

        // Clear existing timer elements
        while (timerList.firstChild) {
            timerList.removeChild(timerList.firstChild);
        }

        // Hide the initial search placeholder if it exists
        const searchPlaceholderEl = document.getElementById('search-placeholder');
        if (searchPlaceholderEl) {
            searchPlaceholderEl.style.display = 'none';
        }
        
        // Show timer list container
        timerList.style.display = '';

        // Use the search_timers.php API endpoint
        apiCall(`search_timers.php?term=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                // Clear timer list
                while (timerList.firstChild) {
                    timerList.removeChild(timerList.firstChild);
                }

                if (response && response.status === 'success') {
                    if (response.timers && Array.isArray(response.timers) && response.timers.length > 0) {
                        // Update the local timers object with the received timers
                        response.timers.forEach((timer, index) => {
                            const id = String(timer.id).trim();
                            // Convert relevant string values to appropriate types
                            timer.is_running = Boolean(parseInt(timer.is_running || 0));
                            timer.is_pinned = Boolean(parseInt(timer.is_pinned || 0));
                            timer.accumulated_seconds = parseFloat(timer.accumulated_seconds || 0);
                            
                            // Update our local timers object
                            timers[id] = {
                                ...timers[id], // Keep any existing data we might have
                                ...timer // Override with new data from search
                            };
                            
                            // Render this timer with animation index for staggered animation
                            renderTimer(id, index);
                        });
                    } else {
                        // Show "no results" message
                        const placeholder = document.createElement('div');
                        placeholder.id = 'search-no-results-placeholder';
                        placeholder.className = 'search-placeholder';
                        placeholder.innerHTML = `
                            <div class="search-icon">❓</div>
                            <p>No timers found matching "${searchTerm}"</p>
                        `;
                        timerList.appendChild(placeholder);
                    }
                } else {
                    // Show error message
                    const errorMsg = response?.message || 'Error performing search';
                    displayNotification(errorMsg, 'error');
                    
                    const placeholder = document.createElement('div');
                    placeholder.id = 'search-error-placeholder';
                    placeholder.className = 'search-placeholder';
                    placeholder.innerHTML = `
                        <div class="search-icon">⚠️</div>
                        <p>Error searching for timers</p>
                    `;
                    timerList.appendChild(placeholder);
                }
            })
            .catch(error => {
                // Show error message
                displayNotification(`Search error: ${error.message}`, 'error');
                
                const placeholder = document.createElement('div');
                placeholder.id = 'search-error-placeholder';
                placeholder.className = 'search-placeholder';
                placeholder.innerHTML = `
                    <div class="search-icon">⚠️</div>
                    <p>Error searching for timers</p>
                `;
                timerList.appendChild(placeholder);
            });
    }

    // --- Timer Actions ---
    async function handleTimerAction(action, timerId, timerEl = null) {
        // Ensure timerId is a string and properly formatted
        timerId = String(timerId).trim();
        if (!timerId || !action) {
            displayNotification('Invalid timer or action', 'error');
            return;
        }

        // Skip if there's a pending action for this timer
        if (pendingActions.has(timerId)) {
            console.log(`Skipping ${action} for timer ${timerId} - action pending`);
            return;
        }

        // Add this timer to pending actions
        pendingActions.add(timerId);

        // Update UI to show pending state
        if (timerEl) {
            // Remove any existing pending class
            timerEl.classList.remove('pending-start', 'pending-stop', 'pending-toggle-pin');
            
            // Add appropriate pending class
            if (action === 'start') {
                timerEl.classList.add('pending-start');
            } else if (action === 'stop') {
                timerEl.classList.add('pending-stop');
            } else if (action === 'toggle_pin') {
                timerEl.classList.add('pending-toggle-pin');
            }
        }

        try {
            // Call the API to perform the action
            const response = await apiCall('timer_action.php', 'POST', { 
                action: action, 
                id: timerId 
            });

            if (response && response.status === 'success') {
                // Update the timer data with the response
                if (response.timer) {
                    const updatedTimerData = response.timer;
                    
                    // Ensure consistent data types
                    updatedTimerData.is_running = Boolean(parseInt(updatedTimerData.is_running || 0));
                    updatedTimerData.is_pinned = Boolean(parseInt(updatedTimerData.is_pinned || 0));
                    updatedTimerData.accumulated_seconds = parseFloat(updatedTimerData.accumulated_seconds || 0);
                    
                    // Update our local timer data
                    timers[timerId] = {
                        ...timers[timerId],
                        ...updatedTimerData
                    };
                    
                    // Re-render the timer
                    renderTimer(timerId);
                    
                    // Show success notification
                    if (action === 'start') {
                        displayNotification(`Timer started successfully`, 'success');
                    } else if (action === 'stop') {
                        displayNotification(`Timer stopped successfully`, 'success');
                    } else if (action === 'toggle_pin') {
                        const isPinned = timers[timerId].is_pinned;
                        displayNotification(`Timer ${isPinned ? 'pinned' : 'unpinned'} successfully`, 'success');
                    }
                }
            } else {
                // Show error message
                const errorMsg = response?.message || `Failed to ${action} timer`;
                displayNotification(errorMsg, 'error');
            }
        } catch (error) {
            displayNotification(`Error: ${error.message}`, 'error');
        } finally {
            // Remove pending action
            pendingActions.delete(timerId);
            
            // Reset UI pending state
            if (timerEl) {
                timerEl.classList.remove('pending-start', 'pending-stop', 'pending-toggle-pin');
            }
        }
    }

    // Helper function to display notifications
    function displayNotification(message, type = 'info') {
        if (!notificationContainer) return;
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        notificationContainer.appendChild(notification);
        
        // Auto-remove notification after a delay
        setTimeout(() => {
            notification.classList.add('fadeout');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }

    // --- Event Listeners ---
    if (searchInput) {
        // Input event to trigger search
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(filterTimers, SEARCH_DEBOUNCE_DELAY);
            
            // Show/hide clear button based on input value
            const clearButton = document.getElementById('search-clear');
            if (clearButton) {
                if (searchInput.value.trim()) {
                    clearButton.classList.add('visible');
                } else {
                    clearButton.classList.remove('visible');
                }
            }
        });
        
        // Focus the search input on page load
        searchInput.focus();
    }
    
    // Clear search button functionality
    const clearSearchButton = document.getElementById('search-clear');
    if (clearSearchButton) {
        clearSearchButton.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                
                // Clear timer list and show placeholder
                if (timerList) {
                    // Clear existing timer elements
                    while (timerList.firstChild) {
                        timerList.removeChild(timerList.firstChild);
                    }
                    
                    // Hide timer list
                    timerList.style.display = 'none';
                    
                    // Show placeholder
                    const placeholder = document.getElementById('search-placeholder');
                    if (placeholder) {
                        placeholder.style.display = 'flex';
                    }
                }
                
                // Clear the saved search term
                localStorage.removeItem(STORAGE_KEY_SEARCH_TERM);
                
                clearSearchButton.classList.remove('visible');
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            stopUITick();
        } else {
            fetchInitialData();
        }
    });

    // Initialize everything
    fetchInitialData();
    
    // Add click event listener to timer list for timer actions
    if (timerList) {
        timerList.addEventListener('click', async (event) => {
            const target = event.target;
            const timerItem = target.closest('.timer-item');
            
            if (!timerItem) return;
            
            const timerId = timerItem.dataset.timerId;
            if (!timerId) return;
            
            if (target.classList.contains('start-button')) {
                handleTimerAction('start', timerId, timerItem);
            } else if (target.classList.contains('stop-button')) {
                handleTimerAction('stop', timerId, timerItem);
            } else if (target.classList.contains('pin-icon')) {
                handleTimerAction('toggle_pin', timerId, timerItem);
                event.stopPropagation(); // Prevent other click handlers
            } else if (target.classList.contains('timer-display') || 
                      target.classList.contains('current-time') ||
                      target.classList.contains('milliseconds') ||
                      target.closest('.timer-display')) {
                const timerData = timers[timerId];
                if (timerData) {
                    // Toggle timer on/off when clicking the timer display
                    const action = timerData.is_running ? 'stop' : 'start';
                    handleTimerAction(action, timerId, timerItem);
                }
            }
        });
    }
});