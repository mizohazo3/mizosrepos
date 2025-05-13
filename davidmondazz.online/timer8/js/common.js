// --- Common Configuration & Constants ---
const API_BASE_URL = 'api/'; // Base URL for API calls

// Rank Icons mapping (Potentially used across different pages)
const RANK_ICONS = {
    'Novice': '⭐',
    'Apprentice': '🔧',
    'Intermediate': '🏅',
    'Advanced': '🎯',
    'Specialist': '🧩',
    'Expert': '☀️',
    'Elite': '🏆',
    'Master': '👑',
    'Grandmaster': '♕',
    'Legendary': '🔥',
    'Ultimate': '∞'
};

// --- Common Utility Functions ---

/**
 * Formats total seconds into an object with hours, minutes, seconds, and milliseconds (first 2 digits).
 * @param {number} totalSeconds - The total seconds to format.
 * @returns {object} Object with { hours, minutes, seconds, milliseconds } strings.
 */
function formatTimeWithMilliseconds(totalSeconds) {
    if (isNaN(totalSeconds) || totalSeconds < 0) {
        totalSeconds = 0;
    }
    const totalMilliseconds = Math.floor(totalSeconds * 1000);
    const hours = Math.floor(totalMilliseconds / (3600 * 1000));
    const minutes = Math.floor((totalMilliseconds % (3600 * 1000)) / (60 * 1000));
    const seconds = Math.floor((totalMilliseconds % (60 * 1000)) / 1000);
    const milliseconds = Math.floor(totalMilliseconds % 1000);

    return {
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
        milliseconds: String(milliseconds).padStart(3, '0').substring(0, 2)
    };
}

/**
 * Displays a notification message on the screen.
 * @param {string} message - The message to display.
 * @param {string} [type='info'] - Type of notification ('success', 'error', 'warning', 'info', 'level-up').
 * @param {number} [duration=4000] - Duration in ms. 0 for permanent until closed.
 * @returns {HTMLElement|null} The notification element or null if container not found.
 */
function displayNotification(message, type = 'info', duration = 4000) {
    const notificationContainer = document.getElementById('notification-container'); // Assumes this exists in the HTML where needed
    if (!notificationContainer) {
        console.warn('Notification container not found.');
        return null;
    }
    const notification = document.createElement('div');
    notification.classList.add('notification', `notification-${type}`);

    const iconSpan = document.createElement('span');
    iconSpan.classList.add('notification-icon');
    switch (type) {
        case 'success': iconSpan.textContent = '✓'; break;
        case 'error': iconSpan.textContent = '✕'; break;
        case 'warning': iconSpan.textContent = '!'; break;
        case 'info': iconSpan.textContent = 'ℹ'; break;
        case 'level-up': iconSpan.textContent = '⭐'; break;
        default: iconSpan.textContent = '•';
    }

    const contentDiv = document.createElement('div');
    contentDiv.classList.add('notification-content');
    const messageP = document.createElement('p');
    messageP.classList.add('notification-message');
    messageP.textContent = message;
    contentDiv.appendChild(messageP);

    const closeButton = document.createElement('button');
    closeButton.classList.add('notification-close');
    closeButton.innerHTML = '×';
    closeButton.onclick = () => {
        notification.style.opacity = '0';
        notification.addEventListener('transitionend', () => notification.remove(), { once: true });
        setTimeout(() => { if (notification.parentNode) notification.remove(); }, 500); // Fallback removal
    };

    const timeoutBar = document.createElement('div');
    timeoutBar.classList.add('notification-timeout-bar');

    notification.appendChild(iconSpan);
    notification.appendChild(contentDiv);
    notification.appendChild(closeButton);
    notification.appendChild(timeoutBar);

    notificationContainer.insertBefore(notification, notificationContainer.firstChild);

    // Trigger animation
    requestAnimationFrame(() => {
        notification.classList.add('show');
        if (duration > 0) {
            timeoutBar.style.transition = `transform ${duration / 1000}s linear`;
            requestAnimationFrame(() => {
                timeoutBar.style.transform = 'scaleX(0)';
            });
        }
    });

    // Auto-close timer
    if (duration > 0) {
         const timer = setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.addEventListener('transitionend', () => notification.remove(), { once: true });
                setTimeout(() => { if (notification.parentNode) notification.remove(); }, 500); // Fallback removal
            }
        }, duration);
         // Clear timeout if closed manually
         closeButton.addEventListener('click', () => clearTimeout(timer));
    }
    return notification;
}

/**
 * Simple wrapper for displayNotification, primarily for success/error messages.
 * @param {string} message - The message to display.
 * @param {boolean} [isError=false] - If true, displays an error notification, otherwise success.
 * @param {HTMLElement} [element=null] - Legacy parameter, not used. Kept for potential compatibility if called elsewhere.
 * @param {number} [duration=4000] - Duration in ms.
 */
function displayStatus(message, isError = false, element = null, duration = 4000) {
     displayNotification(message, isError ? 'error' : 'success', duration);
}


/**
 * Makes an asynchronous API call to a specified endpoint.
 * Handles JSON request/response and basic error handling.
 * @param {string} endpoint - The API endpoint (relative to API_BASE_URL).
 * @param {string} [method='GET'] - HTTP method (GET, POST, PUT, DELETE, etc.).
 * @param {object|null} [body=null] - Data to send in the request body (will be JSON.stringify'd).
 * @returns {Promise<object>} A promise that resolves with the parsed JSON response or rejects on error.
 */
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
        const responseText = await response.text(); // Get text first for debugging

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
        if (!responseText.trim()) {
           return { status: 'success', message: 'Action successful (no content)' };
        }

        try {
            const data = JSON.parse(responseText);
            // Special handling for connection status update (might be specific to timer page, consider moving if needed)
            const connectionStatusEl = document.getElementById('connection-status');
            if (endpoint.startsWith('get_data.php') && data.status === 'success' && connectionStatusEl) {
                setTimeout(() => {
                    if (connectionStatusEl && connectionStatusEl.textContent !== 'Status: Error') {
                        connectionStatusEl.textContent = 'Status: Connected';
                        connectionStatusEl.style.color = 'var(--accent-secondary)';
                    }
                }, 400);
            }
            return data;
        } catch (e) {
             // Log the raw response text for debugging JSON parse errors
             console.error(`Failed to parse JSON response from ${endpoint}. Response text:`, responseText);
             throw new Error(`Failed to parse JSON response from ${endpoint}: ${e.message}`);
        }
    } catch (error) {
        console.error(`API Call Error (${endpoint}):`, error);
        // Use displayNotification for errors originating from apiCall itself
        displayNotification(`Network/Server Error: ${error.message}`, 'error', 10000);
        // Update connection status element if it exists
        const connectionStatusEl = document.getElementById('connection-status');
        if (connectionStatusEl) {
            connectionStatusEl.textContent = 'Status: Error';
            connectionStatusEl.style.color = 'var(--accent-error)';
        }
        // Return a standard error structure for the caller to handle
        return { status: 'error', message: error.message };
    }
}
// --- Balance Update Logic ---

/**
 * Fetches the current bank balance from the API and updates the header display.
 */
async function fetchAndUpdateBalance() {
    try {
        // Assuming 'get_bank_data.php' returns { status: 'success', bank_balance: 123.45 }
        const data = await apiCall('get_bank_data.php');
        // Check for 'current_balance' instead of 'bank_balance' based on API response
        if (data && data.status === 'success' && data.current_balance !== undefined) {
            // Check if the global function from header_nav.php exists
            if (typeof window.updateHeaderBalance === 'function') {
                window.updateHeaderBalance(data.current_balance); // Use current_balance
            } else {
                // Fallback if the function isn't found (e.g., header not included)
                console.warn('window.updateHeaderBalance function not found. Attempting direct update.');
                const balanceEl = document.getElementById('current-balance');
                if (balanceEl) {
                    balanceEl.textContent = parseFloat(data.current_balance).toFixed(2); // Use current_balance
                }
            }
        } else if (data && data.status === 'error') {
            console.error('API Error fetching bank balance:', data.message);
            // Optionally display a notification or keep the old balance
        } else {
             console.warn('Received unexpected data structure from get_bank_data.php:', data);
        }
    } catch (error) {
        // Network errors or JSON parsing errors from apiCall will be handled by apiCall's catch block
        console.error('Error in fetchAndUpdateBalance:', error);
    }
}

// --- Initialization ---
document.addEventListener('DOMContentLoaded', () => {
    // Initial balance fetch when the page is ready
    fetchAndUpdateBalance();

    // Set interval to periodically update balance (e.g., every 15 seconds)
    let balanceUpdateInterval = setInterval(fetchAndUpdateBalance, 15000);

    // Pause updates when the tab is hidden to save resources
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(balanceUpdateInterval);
        } else {
            // Update immediately when tab becomes visible again
            fetchAndUpdateBalance();
            // Clear any potentially lingering interval and restart
            clearInterval(balanceUpdateInterval);
            balanceUpdateInterval = setInterval(fetchAndUpdateBalance, 15000);
        }
    });

    // --- Running Timer Counter Initialization ---
    initStopAllCounter();
    
    // Set up timer to periodically check running timer count
    setInterval(updateRunningTimerCount, 10000); // Check every 10 seconds
});

/**
 * Initializes the stop-all button and running timer counter
 */
function initStopAllCounter() {
    const stopAllBtn = document.getElementById('stop-all-btn');
    const runningTimersCount = document.getElementById('running-timers-count');
    
    if (!stopAllBtn || !runningTimersCount) return;
    
    // Ensure the stop button is always visible with correct styling
    stopAllBtn.style.display = 'inline-flex';
    
    // Setup the counter initially
    updateRunningTimerCount();
    
    // Add click handler to stop-all button
    stopAllBtn.addEventListener('click', async function() {
        // Exit if no timers are running
        if (runningTimersCount.textContent === '0') return;
        
        try {
            // Fetch running timer details
            const response = await fetch('api/get_data.php');
            const data = await response.json();
            
            if (data && data.status === 'success' && data.timers) {
                // Filter running timers
                const runningTimers = Object.values(data.timers).filter(timer => timer.is_running === '1');
                const runningCount = runningTimers.length;
                
                if (runningCount === 0) {
                    return; // No timers running
                }
                
                // Calculate total hours and earnings
                let totalSessionHours = 0;
                let totalSessionEarnings = 0;
                
                // Generate list of running timers
                const timerNamesList = runningTimers.map(timer => {
                    const timerName = timer.name || 'Unnamed Timer';
                    
                    // Calculate session time
                    const startTime = new Date(timer.start_time);
                    const currentTime = new Date();
                    const currentSeconds = Math.floor((currentTime - startTime) / 1000);
                    const sessionHours = (currentSeconds / 3600).toFixed(2);
                    totalSessionHours += parseFloat(sessionHours);
                    
                    // Calculate session earnings
                    const rewardRate = parseFloat(timer.hourly_rate || 0);
                    const sessionEarnings = (currentSeconds / 3600) * rewardRate;
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
                
                // Show confirmation dialog using modal
                if (typeof modal !== 'undefined') {
                    modal.show({
                        title: 'Stop All Timers',
                        message: `Are you sure you want to stop all ${runningCount} running timers?<ul style="margin-top: 10px; padding-left: 15px;">${timerNamesList}</ul>${summaryInfo}`,
                        confirmText: 'Stop All',
                        cancelText: 'Cancel',
                        type: 'warning',
                        onConfirm: async function() {
                            await stopAllTimers();
                        }
                    });
                } else {
                    // Create a modal if it doesn't exist
                    createSimpleModal('Stop All Timers', 
                        `Are you sure you want to stop all ${runningCount} running timers?<ul style="margin-top: 10px; padding-left: 15px;">${timerNamesList}</ul>${summaryInfo}`,
                        'Stop All', 'Cancel', 'warning', async function() {
                            await stopAllTimers();
                        });
                }
            } else {
                // Fallback to basic confirmation if data fetch fails
                if (typeof modal !== 'undefined') {
                    modal.show({
                        title: 'Stop All Timers',
                        message: 'Are you sure you want to stop all running timers?',
                        confirmText: 'Stop All',
                        cancelText: 'Cancel',
                        type: 'warning',
                        onConfirm: async function() {
                            await stopAllTimers();
                        }
                    });
                } else {
                    // Create a modal if it doesn't exist
                    createSimpleModal('Stop All Timers', 
                        'Are you sure you want to stop all running timers?',
                        'Stop All', 'Cancel', 'warning', async function() {
                            await stopAllTimers();
                        });
                }
            }
        } catch (error) {
            console.error('Error fetching timer details:', error);
            // Fallback to basic confirmation
            if (typeof modal !== 'undefined') {
                modal.show({
                    title: 'Stop All Timers',
                    message: 'Are you sure you want to stop all running timers?',
                    confirmText: 'Stop All',
                    cancelText: 'Cancel',
                    type: 'warning',
                    onConfirm: async function() {
                        await stopAllTimers();
                    }
                });
            } else {
                // Create a modal if it doesn't exist
                createSimpleModal('Stop All Timers', 
                    'Are you sure you want to stop all running timers?',
                    'Stop All', 'Cancel', 'warning', async function() {
                        await stopAllTimers();
                    });
            }
        }
    });
}

/**
 * Updates the running timer count display
 */
async function updateRunningTimerCount() {
    const runningTimersCount = document.getElementById('running-timers-count');
    const stopAllBtn = document.getElementById('stop-all-btn');
    
    if (!runningTimersCount || !stopAllBtn) return;
    
    try {
        const response = await fetch('api/get_data.php?count_only=1');
        const data = await response.json();
        
        if (data && data.status === 'success') {
            // Get the running count
            let count = 0;
            
            if (data.running_count !== undefined) {
                // Direct count from API
                count = parseInt(data.running_count) || 0;
            } else if (data.timers) {
                // Calculate from timer data
                if (Array.isArray(data.timers)) {
                    count = data.timers.filter(timer => timer.is_running === '1').length;
                } else {
                    count = Object.values(data.timers).filter(timer => timer.is_running === '1').length;
                }
            }
            
            // Update the counter display
            runningTimersCount.textContent = count;
            
            // Update visibility and styling
            if (count === 0) {
                runningTimersCount.style.display = 'none';
                stopAllBtn.classList.remove('has-running-timers');
            } else {
                runningTimersCount.style.display = 'inline-flex';
                stopAllBtn.classList.add('has-running-timers');
            }
        }
    } catch (error) {
        console.error('Error updating running timer count:', error);
    }
}

/**
 * Stops all running timers
 */
async function stopAllTimers() {
    try {
        const response = await fetch('api/stop_all_timers.php');
        const data = await response.json();
        
        if (data && data.status === 'success') {
            // Show success notification
            if (typeof displayNotification === 'function') {
                displayNotification('All timers stopped successfully', 'success');
            }
            
            // Update the counter
            updateRunningTimerCount();
            
            // Reload the page if we're on the timer page
            if (window.location.pathname.endsWith('index.php') || 
                window.location.pathname.endsWith('/')) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            // Show error
            const errorMsg = data?.message || 'Failed to stop all timers';
            if (typeof displayNotification === 'function') {
                displayNotification(errorMsg, 'error');
            } else {
                console.error(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error stopping all timers:', error);
        if (typeof displayNotification === 'function') {
            displayNotification('Error stopping all timers: ' + error.message, 'error');
        }
    }
}

/**
 * Creates a simple modal when the modal class isn't available
 */
function createSimpleModal(title, message, confirmText, cancelText, type, onConfirm) {
    // Create modal elements
    const modalContainer = document.createElement('div');
    modalContainer.id = 'modal-container';
    modalContainer.style.position = 'fixed';
    modalContainer.style.top = '0';
    modalContainer.style.left = '0';
    modalContainer.style.width = '100%';
    modalContainer.style.height = '100%';
    modalContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    modalContainer.style.display = 'flex';
    modalContainer.style.justifyContent = 'center';
    modalContainer.style.alignItems = 'center';
    modalContainer.style.zIndex = '9999';
    
    const modalContent = document.createElement('div');
    modalContent.style.backgroundColor = '#1e2228';
    modalContent.style.borderRadius = '5px';
    modalContent.style.width = '90%';
    modalContent.style.maxWidth = '500px';
    modalContent.style.maxHeight = '80vh';
    modalContent.style.overflow = 'auto';
    modalContent.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.5)';
    
    const modalHeader = document.createElement('div');
    modalHeader.style.padding = '15px';
    modalHeader.style.borderBottom = '1px solid rgba(255, 255, 255, 0.1)';
    
    const modalTitle = document.createElement('h3');
    modalTitle.textContent = title;
    modalTitle.style.margin = '0';
    modalTitle.style.color = '#fff';
    
    const modalBody = document.createElement('div');
    modalBody.style.padding = '15px';
    modalBody.innerHTML = message;
    modalBody.style.color = '#ddd';
    
    const modalFooter = document.createElement('div');
    modalFooter.style.padding = '15px';
    modalFooter.style.borderTop = '1px solid rgba(255, 255, 255, 0.1)';
    modalFooter.style.display = 'flex';
    modalFooter.style.justifyContent = 'flex-end';
    
    const confirmButton = document.createElement('button');
    confirmButton.textContent = confirmText;
    confirmButton.style.padding = '8px 15px';
    confirmButton.style.marginLeft = '10px';
    confirmButton.style.backgroundColor = type === 'warning' ? '#e6484f' : '#4caf50';
    confirmButton.style.color = 'white';
    confirmButton.style.border = 'none';
    confirmButton.style.borderRadius = '4px';
    confirmButton.style.cursor = 'pointer';
    
    const cancelButton = document.createElement('button');
    cancelButton.textContent = cancelText;
    cancelButton.style.padding = '8px 15px';
    cancelButton.style.backgroundColor = 'transparent';
    cancelButton.style.color = '#ddd';
    cancelButton.style.border = '1px solid rgba(255, 255, 255, 0.3)';
    cancelButton.style.borderRadius = '4px';
    cancelButton.style.cursor = 'pointer';
    
    // Add event listeners
    confirmButton.addEventListener('click', () => {
        if (onConfirm) onConfirm();
        document.body.removeChild(modalContainer);
    });
    
    cancelButton.addEventListener('click', () => {
        document.body.removeChild(modalContainer);
    });
    
    // Handle click outside to close
    modalContainer.addEventListener('click', (e) => {
        if (e.target === modalContainer) {
            document.body.removeChild(modalContainer);
        }
    });
    
    // Assemble the modal
    modalHeader.appendChild(modalTitle);
    modalFooter.appendChild(cancelButton);
    modalFooter.appendChild(confirmButton);
    
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modalContent.appendChild(modalFooter);
    
    modalContainer.appendChild(modalContent);
    
    // Add to document
    document.body.appendChild(modalContainer);
    
    // Focus on confirm button
    setTimeout(() => confirmButton.focus(), 100);
}