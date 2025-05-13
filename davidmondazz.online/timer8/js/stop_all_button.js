/**
 * Completely independent stop-all-timers button functionality
 * Uses namespacing to avoid conflicts with any other scripts
 */
(function() {
    // Create namespace to avoid global scope pollution
    window.StopAllButtonManager = window.StopAllButtonManager || {};
    
    // Only initialize once
    if (window.StopAllButtonManager.initialized) return;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeStopAllButton);
    } else {
        initializeStopAllButton();
    }
    
    /**
     * Main initialization function
     */
    function initializeStopAllButton() {
        // DOM elements
        const stopAllBtn = document.getElementById('stop-all-btn');
        const runningTimersCount = document.getElementById('running-timers-count');
        
        // If stop-all button doesn't exist, exit early
        if (!stopAllBtn) return;
        
        // Store original click handler if it exists
        const originalClickHandler = stopAllBtn.onclick;
        
        // Remove any existing click handlers to prevent conflicts
        stopAllBtn.onclick = null;
        stopAllBtn.removeEventListener('click', originalClickHandler);
        
        // Clear any other event listeners
        const newStopAllBtn = stopAllBtn.cloneNode(true);
        stopAllBtn.parentNode.replaceChild(newStopAllBtn, stopAllBtn);
        
        // Re-assign the reference
        const cleanStopAllBtn = document.getElementById('stop-all-btn');
        
        // Initialize the counter
        initializeCounter();
        
        // Make the button visible
        cleanStopAllBtn.style.display = 'inline-flex';
        
        // Add our clean stop all functionality without detailed modal
        cleanStopAllBtn.addEventListener('click', handleStopAllClick);
        
        // Mark as initialized
        window.StopAllButtonManager.initialized = true;
        window.StopAllButtonManager.stopAllBtn = cleanStopAllBtn;
        window.StopAllButtonManager.handleClick = handleStopAllClick;
    }
    
    /**
     * Handle the stop all button click
     */
    async function handleStopAllClick(event) {
        // Stop event propagation to prevent other handlers from firing
        event.stopPropagation();
        event.preventDefault();
        
        const stopAllBtn = window.StopAllButtonManager.stopAllBtn || document.getElementById('stop-all-btn');
        const runningTimersCount = document.getElementById('running-timers-count');
        
        // Skip if no running timers
        if (runningTimersCount && runningTimersCount.textContent === '0') return;
        
        // No confirmation - directly stop all timers
        stopAllBtn.disabled = true;
        stopAllBtn.classList.add('stopping');
        
        try {
            const response = await fetch('api/stop_all_timers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-By': 'StopAllButtonManager' // Custom header to identify our requests
                }
            });
            
            const data = await response.json();
            
            if (data && data.status === 'success') {
                // Success notification
                if (typeof displayNotification === 'function') {
                    displayNotification('All timers stopped successfully', 'success');
                } else {
                    // Fallback notification
                    const notificationContainer = document.getElementById('notification-container');
                    if (notificationContainer) {
                        const notification = document.createElement('div');
                        notification.className = 'notification notification-success';
                        notification.innerHTML = '<span class="notification-icon">✓</span><div class="notification-content"><p>All timers stopped successfully</p></div>';
                        notificationContainer.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.style.opacity = '0';
                            setTimeout(() => notification.remove(), 300);
                        }, 3000);
                    }
                }
                
                // Update the counter to 0
                if (runningTimersCount) {
                    runningTimersCount.textContent = '0';
                    runningTimersCount.style.display = 'none';
                }
                
                // Update the button state
                stopAllBtn.classList.remove('has-running-timers');
                
                // Update timer UI if we're not on the search page
                if (!window.isSearchPage && typeof updateRunningTimerCount === 'function') {
                    updateRunningTimerCount();
                }
            } else {
                // Error notification
                const message = data?.message || 'Failed to stop all timers';
                if (typeof displayNotification === 'function') {
                    displayNotification(message, 'error');
                }
            }
        } catch (error) {
            console.error('Failed to stop all timers:', error);
            // Error notification
            if (typeof displayNotification === 'function') {
                displayNotification('Failed to stop all timers', 'error');
            }
        } finally {
            // Re-enable the button
            stopAllBtn.disabled = false;
            stopAllBtn.classList.remove('stopping');
        }
        
        // Prevent default behavior and stop propagation again to be extra safe
        return false;
    }
    
    /**
     * Initialize the running timers counter
     */
    function initializeCounter() {
        // Store reference to our updateRunningCount in the namespace
        window.StopAllButtonManager.updateCount = updateRunningCount;
        
        // Run it once immediately
        updateRunningCount();
        
        // Set interval to update count periodically, store the interval ID
        window.StopAllButtonManager.counterInterval = setInterval(updateRunningCount, 30000); // Update every 30 seconds
    }
    
    /**
     * Update the running timers count
     */
    async function updateRunningCount() {
        const runningTimersCount = document.getElementById('running-timers-count');
        const stopAllBtn = window.StopAllButtonManager.stopAllBtn || document.getElementById('stop-all-btn');
        
        if (!runningTimersCount || !stopAllBtn) return;
        
        try {
            const response = await fetch('api/get_data.php?count_only=1&nocache=' + new Date().getTime(), {
                headers: {
                    'X-Requested-By': 'StopAllButtonManager' // Custom header to identify our requests
                }
            });
            
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
                
                // Store count in the namespace
                window.StopAllButtonManager.count = count;
            }
        } catch (error) {
            console.error('Error updating running timer count:', error);
        }
    }
    
    // Ensure fixed controls is displayed
    function ensureFixedControlsVisible() {
        const fixedControls = document.querySelector('.fixed-controls');
        if (fixedControls) {
            fixedControls.style.display = 'flex';
        }
    }
    
    // Add button style
    function addButtonStyles() {
        // Only add styles once
        if (document.getElementById('stop-all-btn-styles')) return;
        
        const style = document.createElement('style');
        style.id = 'stop-all-btn-styles';
        style.textContent = `
            #stop-all-btn.stopping {
                opacity: 0.7;
                cursor: wait;
            }
            .fixed-controls {
                display: flex !important;
            }
            #running-timers-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: #ff4d4f;
                color: white;
                border-radius: 10px;
                min-width: 18px;
                height: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
                padding: 0 4px;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Call these functions during initialization
    function initialize() {
        ensureFixedControlsVisible();
        addButtonStyles();
    }
    
    // Store our functions in the namespace
    window.StopAllButtonManager.initialize = initialize;
    window.StopAllButtonManager.updateRunningCount = updateRunningCount;
    window.StopAllButtonManager.handleStopAllClick = handleStopAllClick;
    
    // Clean up function for page unload
    function cleanup() {
        if (window.StopAllButtonManager.counterInterval) {
            clearInterval(window.StopAllButtonManager.counterInterval);
        }
    }
    
    // Add unload event listener
    window.addEventListener('beforeunload', cleanup);
    
    // Call initialize on load
    initialize();
})(); 