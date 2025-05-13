document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';
    const POLL_INTERVAL = 30000; // 30 seconds between updates
    const SEARCH_DEBOUNCE_DELAY = 300; // Debounce delay for search input in milliseconds

    // --- DOM Elements ---
    const timerList = document.getElementById('timer-list');
    const timerTemplate = document.getElementById('timer-template');
    const searchInput = document.getElementById('search-input');
    const connectionStatus = document.getElementById('connection-status');
    const currentBalanceEl = document.getElementById('current-balance');
    const stopAllBtn = document.getElementById('stop-all-btn');
    const notificationContainer = document.getElementById('notification-container');

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
                
                // Don't show timers until search is performed
                hideAllTimers();
                
                // Add placeholder text to timer list
                addSearchPlaceholder();
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
    
    // Hide all timers initially
    function hideAllTimers() {
        if (!timerList) return;
        const timerItems = timerList.querySelectorAll('.timer-item');
        timerItems.forEach(item => {
            item.style.display = 'none';
        });
    }
    
    // Add search placeholder text
    function addSearchPlaceholder() {
        if (!timerList) return;
        
        // Check if placeholder already exists
        if (document.getElementById('search-placeholder')) return;
        
        const placeholder = document.createElement('div');
        placeholder.id = 'search-placeholder';
        placeholder.className = 'search-placeholder';
        placeholder.innerHTML = `
            <div class="search-icon">🔍</div>
            <p>Enter a timer name to search</p>
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
    
    // Remove search placeholder
    function removeSearchPlaceholder() {
        const placeholder = document.getElementById('search-placeholder');
        if (placeholder) {
            placeholder.remove();
        }
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

                // Store the updated timer data (done on line 322)

                // Render the timer UI ONLY if a search has been performed
                if (hasSearched) {
                    renderTimer(currentTimerId);
                }
            }

            // Check for removed timers
            for (const timerId in previousTimers) {
                if (!receivedTimerIds.has(timerId)) {
                    const timerEl = document.querySelector(`.timer-item[data-timer-id='${timerId}']`);
                    if (timerEl) {
                        timerEl.remove();
                    }
                    delete timers[timerId];
                }
            }

        }

        // Update bank balance if provided
        if (data.bank_balance !== undefined && currentBalanceEl) {
            currentBalanceEl.textContent = parseFloat(data.bank_balance).toFixed(2);
        }

        // If search is active, reapply filter; otherwise, ensure placeholder is shown
        if (hasSearched) {
            filterTimers(); // Re-filter with potentially updated data
        } else {
            hideAllTimers(); // Ensure timers remain hidden if no search active
            addSearchPlaceholder(); // Ensure placeholder is present if no search active
        }
    }


    function renderTimer(timerId) {
        // Ensure timerId is a string and properly formatted
        timerId = String(timerId).trim();
        if (!timerId) return;
        
        const timerData = timers[timerId];
        if (!timerData) return;

        let timerEl = document.querySelector(`[data-timer-id="${timerId}"]`);
        if (!timerEl) {
            const template = document.getElementById('timer-template');
            if (!template) return;
            timerEl = template.content.firstElementChild.cloneNode(true);
            timerList.appendChild(timerEl);
        }

        // Set ID consistently
        timerEl.dataset.timerId = timerId;
        
        // Update timer ID display
        const timerIdEl = timerEl.querySelector('.timer-id');
        if (timerIdEl) {
            timerIdEl.textContent = `#${timerId}`;
        }

        // Update timer name
        const timerNameEl = timerEl.querySelector('.timer-name');
        if (timerNameEl) {
            timerNameEl.textContent = timerData.name || 'Unnamed Timer';
        }

        // Update pin status
        const pinIcon = timerEl.querySelector('.pin-icon');
        if (pinIcon) {
            pinIcon.classList.toggle('pinned', Boolean(timerData.is_pinned));
            pinIcon.title = timerData.is_pinned ? 'Unpin Timer' : 'Pin Timer';
            pinIcon.onclick = async () => {
                pinIcon.style.pointerEvents = 'none';
                try {
                    await handleTimerAction('toggle_pin', timerId, timerEl);
                } finally {
                    pinIcon.style.pointerEvents = '';
                }
            };
        }

        // Update timer display
        const currentSeconds = calculateCurrentSeconds(timerData);
        const timeObj = formatTimeWithMilliseconds(currentSeconds);
        
        const timerDisplay = timerEl.querySelector('.current-time');
        const millisecondsDisplay = timerEl.querySelector('.milliseconds');
        
        if (timerDisplay) {
            timerDisplay.textContent = `${timeObj.hours}:${timeObj.minutes}:${timeObj.seconds}`;
        }
        if (millisecondsDisplay) {
            millisecondsDisplay.textContent = timeObj.milliseconds;
        }

        // Update accumulated time
        const accumulatedEl = timerEl.querySelector('.accumulated-time');
        if (accumulatedEl) {
            const totalHours = currentSeconds / 3600;
            accumulatedEl.textContent = `Total: ${totalHours.toFixed(2)}h`;
        }

        // Update total earned (if visible)
        const totalEarnedEl = timerEl.querySelector('.timer-total-earned');
        if (totalEarnedEl && timerData.is_pinned) {
            const currentEarnings = calculateCurrentEarnings(timerData, currentSeconds);
            totalEarnedEl.textContent = (currentEarnings > 0 ? '+' : '') + '$' + currentEarnings.toFixed(2);
        }

        // Update buttons
        const startButton = timerEl.querySelector('.start-button');
        const stopButton = timerEl.querySelector('.stop-button');
        
        if (startButton && stopButton) {
            if (timerData.is_running) {
                startButton.style.display = 'none';
                stopButton.style.display = '';
            } else {
                startButton.style.display = '';
                stopButton.style.display = 'none';
            }
        }

        // Calculate and update progress
        calculateAndRenderProgress(timerEl, timerData, currentSeconds / 3600);
    }


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


    function updateUITick() {
        if (!timers || Object.keys(timers).length === 0) return;

        const now = Date.now();
        const updateSecondaryInfo = now % 500 < 50; // Update non-essential info every 500ms

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
                    const totalEarnedEl = timerEl.querySelector('.timer-total-earned');

                    if (accumulatedTime) accumulatedTime.textContent = `Total: ${currentHours.toFixed(2)}h`;
                    if (totalEarnedEl) {
                        const currentSessionEarnings = calculateCurrentEarnings(timerData, currentSeconds);
                        totalEarnedEl.textContent = (timerData.is_running && currentSessionEarnings > 0 ? '+' : '') + '$' + currentSessionEarnings.toFixed(2);
                    }

                    calculateAndRenderProgress(timerEl, timerData, currentHours);
                }
            }
        }
    }


    function filterTimers() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const timerItems = timerList.querySelectorAll('.timer-item');
        
        hasSearched = searchTerm.length > 0;
        const searchParts = searchTerm.split(' ').filter(part => part.length > 0);
        let visibleCount = 0;

        const fragment = document.createDocumentFragment();
        const pinnedItems = [];
        const unpinnedItems = [];

        // Show/hide placeholder based on search status
        if (hasSearched) {
            removeSearchPlaceholder();
        } else {
            addSearchPlaceholder();
            hideAllTimers();
            return; // Exit early if no search term
        }

        timerItems.forEach(item => {
            const timerId = String(item.getAttribute('data-timer-id')).trim();
            if (!timerId) return;
            
            const timerData = timers[timerId];
            let shouldShow = false;

            if (hasSearched && timerData) {
                const timerName = (timerData.name || '').toLowerCase().trim();
                // Search in name and ID separately to avoid partial matches
                const searchInName = searchParts.every(part => timerName.includes(part));
                const searchInId = searchParts.every(part => timerId === part);
                shouldShow = searchInName || searchInId;
            }

            item.style.display = shouldShow ? '' : 'none';

            if (shouldShow) {
                visibleCount++;
                if (timerData && timerData.is_pinned) {
                    pinnedItems.push(item);
                } else {
                    unpinnedItems.push(item);
                }
            }
        });

        // If no search results found
        if (hasSearched && visibleCount === 0) {
            const placeholder = document.getElementById('search-placeholder');
            if (!placeholder) {
                addSearchPlaceholder();
                const placeholder = document.getElementById('search-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = `
                        <div class="search-icon">❓</div>
                        <p>No timers found matching "${searchTerm}"</p>
                    `;
                }
            } else {
                placeholder.innerHTML = `
                    <div class="search-icon">❓</div>
                    <p>No timers found matching "${searchTerm}"</p>
                `;
            }
        }

        pinnedItems.forEach(item => fragment.appendChild(item));
        unpinnedItems.forEach(item => fragment.appendChild(item));
        timerList.appendChild(fragment);
    }


    // --- Timer Actions ---
    async function handleTimerAction(action, timerId, timerEl = null) {
        // Ensure timerId is a string and properly formatted
        timerId = timerId ? String(timerId).trim() : null;
        if (!timerId && !['stop_all', 'reset_all'].includes(action)) return;
        if (pendingActions.has(timerId)) return;
        pendingActions.add(timerId);

        try {
            let endpoint = '';
            let method = 'POST';
            let data = { id: timerId, action: action };

            switch (action) {
                case 'start':
                case 'stop':
                case 'toggle_pin':
                    endpoint = 'timer_action.php';
                    break;
                default:
                    throw new Error('Invalid action');
            }

            const response = await apiCall(endpoint, method, data);

            if (response.status === 'success') {
                // Update local data store and UI from response
                if (response.timer) {
                    const updatedTimerData = response.timer;
                    // Ensure consistent data types
                    updatedTimerData.is_running = Boolean(parseInt(updatedTimerData.is_running || 0));
                    updatedTimerData.is_pinned = Boolean(parseInt(updatedTimerData.is_pinned || 0));
                    updatedTimerData.accumulated_seconds = parseFloat(updatedTimerData.accumulated_seconds || 0);
                    updatedTimerData.id = String(updatedTimerData.id).trim();
                    
                    // Only update if IDs match
                    if (updatedTimerData.id === timerId) {
                        timers[timerId] = { ...timers[timerId], ...updatedTimerData };
                        renderTimer(timerId);
                    }
                }

                // Update bank balance if provided
                if (response.bank_balance !== undefined && currentBalanceEl) {
                    currentBalanceEl.textContent = parseFloat(response.bank_balance).toFixed(4);
                }

                filterTimers();
            } else {
                throw new Error(response.message || 'Timer action failed');
            }
        } catch (error) {
            throw error;
        } finally {
            pendingActions.delete(timerId);
        }
    }


    // --- Event Listeners ---
    if (timerList) {
        timerList.addEventListener('click', (event) => {
            const button = event.target.closest('button');
            if (!button) return;

            const timerItem = button.closest('.timer-item');
            if (!timerItem) return;

            const timerIdStr = timerItem.dataset.timerId;
            if (!timerIdStr) {
                 return;
            }

            const timerId = timerIdStr;

            let action = null;
            if (button.classList.contains('start-button')) {
                action = 'start';
            } else if (button.classList.contains('stop-button')) {
                action = 'stop';
            }

            if (action) {
                button.disabled = true;
                handleTimerAction(action, timerId, timerItem)
                    .catch(error => {
                        // Error is already handled inside handleTimerAction with notification and revert attempt
                    })
                    .finally(() => {
                         try {
                             const finalButton = timerItem.querySelector(`button[class*="${action}-button"]`);
                             if (finalButton && finalButton.closest('.timer-item')) {
                                 finalButton.disabled = false;
                             } else {
                                 const anyButton = timerItem.querySelector('button');
                                 if (anyButton && anyButton.closest('.timer-item')) anyButton.disabled = false;
                             }
                         } catch (e) {
                         }
                    });
            }
        });
    } else {
    }

    if (stopAllBtn) {
        stopAllBtn.addEventListener('click', () => {
             stopAllBtn.disabled = true;
             handleTimerAction('stop_all', null, null)
                 .finally(() => {
                     stopAllBtn.disabled = false;
                 });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                filterTimers();
            }, SEARCH_DEBOUNCE_DELAY);
        });
        searchInput.focus();
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
            stopUITick();
        } else {
            fetchInitialData();
        }
    });

    // --- Initialize ---
    fetchInitialData();
});