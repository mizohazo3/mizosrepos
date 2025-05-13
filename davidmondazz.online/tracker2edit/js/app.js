/**
 * Advanced Timer Tracking System
 * Main JavaScript file for frontend functionality
 */

// Global variables
let timers = []; // Array to store timer data
let categories = []; // Array to store categories
let activeCategory = null; // Currently active category filter
let pollingInterval = null; // Interval for AJAX polling
const POLLING_INTERVAL_MS = 1000; // Poll every 1 second (changed from 3000)
let localTimers = {}; // Store local timer states for smoother updates
let searchTimeout = null;
let lastSearchQuery = '';
let currentSearchTerm = '';
// Pagination variables
let currentCategoryPage = 1;
let categoriesPerPage = 5;
let timerPages = {}; // Store current page for each category
let timersPerPage = 5; // Timers per page
let activeIntervals = {};
let chartInstances = {};

// DOM elements
const loadingIndicator = document.getElementById('loading');
const timersContainer = document.getElementById('timers-container');
const categoryFiltersContainer = document.getElementById('category-filters');
const addTimerForm = document.getElementById('addTimerForm');
const timerNameInput = document.getElementById('timerName');
const timerCategorySelect = document.getElementById('timerCategory');
const addTimerModal = new bootstrap.Modal(document.getElementById('addTimerModal'));
const runningTimersSection = document.getElementById('running-timers-section');
const runningTimersContainer = document.getElementById('running-timers-container');
const stopAllBtn = document.getElementById('stop-all-btn');

// Initialize the application when the DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Start polling for timer updates
    startPolling();
    
    // Set up event listeners
    setupEventListeners();
    
    // Start client-side timer updates
    startClientSideUpdates();
    
    // Initialize theme
    initTheme();
    
    // Set up event listener for the "Stop All Timers" button
    if (stopAllBtn) {
        stopAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleStopAllTimers(e);
            updateStopAllButtonVisibility();
        });
    }
    
    // Set up event listener for the mobile "Stop All" button
    const stopAllBtnMobile = document.getElementById('stop-all-btn-mobile');
    if (stopAllBtnMobile) {
        stopAllBtnMobile.addEventListener('click', function(e) {
            e.preventDefault();
            handleStopAllTimers(e);
            updateStopAllButtonVisibility();
        });
    }
    
    // Add custom CSS for highlighting working timers and responsive design
    addCustomStyles();
    
    // Add global event listener for sticky toggle buttons to ensure they work even after DOM updates
    document.addEventListener('click', function(e) {
        // Check if the clicked element is a sticky toggle button or its child (icon)
        let target = e.target;
        
        // If the target is an icon inside the button, get the parent button
        if (target.tagName.toLowerCase() === 'i' && target.parentNode.hasAttribute('data-action')) {
            target = target.parentNode;
        }
        
        // Now check if the target is the sticky toggle button
        if (target.getAttribute('data-action') === 'toggle-sticky') {
            e.preventDefault();
            e.stopPropagation();
            
            const timerId = target.getAttribute('data-timer-id');
            const stickyValue = target.getAttribute('data-sticky');
            
            if (timerId && stickyValue !== null) {
                toggleStickyTimer(timerId, stickyValue);
            }
        }
    });
});

/**
 * Set up all event listeners
 */
function setupEventListeners() {
    // Add Timer Form submission
    addTimerForm.addEventListener('submit', handleAddTimerFormSubmit);
    
    // Timer name input - allow submitting with Enter key
    timerNameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddTimerFormSubmit(e);
        }
    });
    
    // Add event listener for modal shown event to prevent category reset
    const addTimerModalElement = document.getElementById('addTimerModal');
    if (addTimerModalElement) {
        addTimerModalElement.addEventListener('shown.bs.modal', () => {
            // Ensure categories are populated with current selection maintained
            populateCategorySelect();
        });
    }
    
    // Add search functionality
    const timerSearch = document.getElementById('timer-search');
    if (timerSearch) {
        timerSearch.addEventListener('input', handleTimerSearch);
    }
    
    // Add event listener for theme toggle
    $(document).on('click', '#theme-toggle', function(e) {
        e.preventDefault();
        toggleTheme();
    });
}

/**
 * Start polling for timer updates
 */
function startPolling() {
    // Initial load
    fetchTimers();
    
    // Set up polling interval
    pollingInterval = setInterval(() => {
        fetchTimers(false); // Silent update (no loading indicator)
    }, POLLING_INTERVAL_MS);
}

/**
 * Start client-side timer updates for smoother display
 */
function startClientSideUpdates() {
    // Update running timers every 100ms for smoother display
    // Inside startClientSideUpdates in app.js

setInterval(() => {
    Object.keys(localTimers).forEach(timerIdStr => {
        const timerId = parseInt(timerIdStr);
        if (!localTimers[timerId] || typeof localTimers[timerId].baseElapsed === 'undefined' || typeof localTimers[timerId].startTime === 'undefined') {
            // Skip if local timer data is incomplete (might happen briefly during state changes)
            return;
        }

        // Calculate current elapsed time
        const elapsed = localTimers[timerId].baseElapsed + (Date.now() - localTimers[timerId].startTime) / 1000;

        // Check if elapsed is a valid number
        if (isNaN(elapsed)) {
            console.error(`Calculated elapsed time is NaN for timer ${timerId}. Base: ${localTimers[timerId].baseElapsed}, Start: ${localTimers[timerId].startTime}`);
            return; // Don't update if calculation failed
        }

        // Format the time
        const hours = Math.floor(elapsed / 3600);
        const minutes = Math.floor((elapsed % 3600) / 60);
        const secs = Math.floor(elapsed % 60);
        const ms = Math.floor((elapsed - Math.floor(elapsed)) * 100);

        const displayHours = hours >= 1000 ? hours.toLocaleString() : String(hours).padStart(2, '0');
        const formattedTime = `${displayHours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        const formattedMs = String(ms).padStart(2, '0');
        const fullFormattedTime = formattedTime + '.' + formattedMs; // Combine HH:MM:SS and .ms

        // 1. Update the main timer card display 
        const timerCardContainer = document.getElementById(`timer-${timerId}`);
        if (timerCardContainer) {
            // Find the .timer-current element within that specific card
            const currentTimeElement = timerCardContainer.querySelector('.timer-current');

            if (currentTimeElement) {
                // Split the time and milliseconds for better styling
                currentTimeElement.innerHTML = formattedTime + '<span class="timer-ms">.' + formattedMs + '</span>';
                
                // Also make sure the timer has the working-timer class
                const timerCard = timerCardContainer.querySelector('.horizontal-timer-card');
                if (timerCard && !timerCard.classList.contains('working-timer')) {
                    timerCard.classList.add('working-timer');
                    timerCard.classList.remove('paused-timer');
                }
            }
        }

        // 2. Update the running timer item display in the top bar (if it exists)
        const runningItem = document.getElementById(`running-timer-item-${timerId}`);
        if (runningItem) {
            const durationSpan = runningItem.querySelector('.running-timer-duration');
            if (durationSpan) {
                // Update duration span - use the HH:MM:SS format without ms for the top bar
                durationSpan.textContent = ` ${formattedTime}`;
            }
        }
    });

}, 100); // Update every 100ms
}

/**
 * Fetch timers from the server
 * @param {boolean} showLoadingIndicator - Whether to show the loading indicator
 */
function fetchTimers(showLoadingIndicator = true) {
    // Don't interrupt active searches with polling updates
    if (currentSearchTerm) {
        return;
    }
    
    const url = activeCategory 
        ? `api/get_timers.php?category_id=${activeCategory}` 
        : 'api/get_timers.php';
    
    if (showLoadingIndicator) {
        displayLoading();
    }
    
    fetch(url)
        .then(response => {
            // Check if the response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // Get the content type header
            const contentType = response.headers.get('content-type');
            
            // Check if the response is JSON
            if (!contentType || !contentType.includes('application/json')) {
                // First try to get the text content to see what's wrong
                return response.text().then(text => {
                    console.error('Non-JSON response received:', text);
                    throw new Error('The server did not return JSON data. Check the server logs.');
                });
            }
            
            return response.json();
        })
        .then(data => {
            // Don't process results if a search was started while waiting
            if (currentSearchTerm) {
                return;
            }
            
            if (data.success) {
                // Update timers and categories
                updateTimersData(data.timers);
                updateCategoriesData(data.categories);
                
                // Update UI
                renderTimers();
                renderCategoryFilters();
                populateCategorySelect();
                updateStopAllButtonVisibility();
                renderRunningTimers();
            } else {
                // Show error with SweetAlert2
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.error || 'Failed to fetch timers'
                });
            }
        })
        .catch(error => {
            console.error('Error fetching timers:', error);
            
            // Show user-friendly error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: 'Failed to connect to the server.<br>Please check that:<br>1. The database exists and is set up<br>2. PHP errors are not interrupting the JSON output<br><br>Try visiting the <a href="debug.html">debug page</a> for more information.',
                showCancelButton: true,
                confirmButtonText: 'Run Setup',
                cancelButtonText: 'OK',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'setup.php';
                }
            });
        })
        .finally(() => {
            if (showLoadingIndicator) {
                hideLoading();
            }
        });
}

/**
 * Update the timers data array and handle efficient DOM updates
 * @param {Array} newTimers - New timers data from the server
 */
function updateTimersData(newTimers) {
    // If this is the first load, just store the timers and initialize localTimers
    if (timers.length === 0) {
        timers = newTimers;
        // Initialize local timers for any initially running timers
        timers.forEach(timer => {
            if (timer.status === 'running') {
                localTimers[timer.id] = {
                    startTime: Date.now(),
                    baseElapsed: timer.current_elapsed
                };
            }
        });
        return;
    }
    
    // Store existing timer IDs to track new ones
    const existingTimerIds = new Set(timers.map(t => t.id));
    let needFullRender = false;
    
    // Update existing timers or add new ones
    for (const newTimer of newTimers) {
        const existingTimerIndex = timers.findIndex(t => t.id === newTimer.id);
        
        if (existingTimerIndex !== -1) {
            // Update existing timer if anything changed
            const existingTimer = timers[existingTimerIndex];
            
            // Check if timer state changed
            const stateChanged = (
                existingTimer.status !== newTimer.status ||
                existingTimer.total_time !== newTimer.total_time
            );
            
            if (stateChanged) {
                // Update the timer
                timers[existingTimerIndex] = newTimer;
                
                // Update just this timer's UI if it exists in the DOM
                const timerElement = document.getElementById(`timer-${newTimer.id}`);
                if (timerElement) {
                    updateTimerElement(timerElement, newTimer);
                }
                
                // If status changed to running, reset local timer
                if (newTimer.status === 'running') {
                    localTimers[newTimer.id] = {
                        startTime: Date.now(),
                        baseElapsed: newTimer.current_elapsed
                    };
                } else if (newTimer.status !== 'running') {
                    // If timer is no longer running, remove from local timers
                    delete localTimers[newTimer.id];
                }
            } else if (newTimer.status === 'running') {
                // For running timers, just update the current_elapsed
                timers[existingTimerIndex].current_elapsed = newTimer.current_elapsed;
                timers[existingTimerIndex].current_elapsed_formatted = newTimer.current_elapsed_formatted;
                
                // Always ensure local timer is set/updated for running timers
                localTimers[newTimer.id] = {
                    startTime: Date.now(),
                    baseElapsed: newTimer.current_elapsed
                };
            }
        } else {
            // Add new timer
            timers.push(newTimer);
            
            // Flag that we need a full render to correctly place the new timer
            if (!existingTimerIds.has(newTimer.id)) {
                needFullRender = true;
            }
            
            // Initialize local timer if it's running
            if (newTimer.status === 'running') {
                localTimers[newTimer.id] = {
                    startTime: Date.now(),
                    baseElapsed: newTimer.current_elapsed
                };
            }
        }
    }
    
    // Remove timers that no longer exist
    const newTimerIds = newTimers.map(t => t.id);
    for (let i = timers.length - 1; i >= 0; i--) {
        if (!newTimerIds.includes(timers[i].id)) {
            // Remove from array
            const removedTimer = timers.splice(i, 1)[0];
            
            // Remove from localTimers if it exists
            if (localTimers[removedTimer.id]) {
                delete localTimers[removedTimer.id];
            }
            
            // Remove from DOM if it exists
            const timerElement = document.getElementById(`timer-${removedTimer.id}`);
            if (timerElement) {
                timerElement.remove();
            }
            
            // Flag that we need a full render since we removed a timer
            needFullRender = true;
        }
    }
    
    // If we have new or removed timers, do a full render
    if (needFullRender && activeCategory !== null) {
        renderTimers();
    }
    
    // Check if we need to show "no timers" message
    const visibleTimers = activeCategory 
        ? timers.filter(t => t.category_id === activeCategory)
        : timers;
        
    if (visibleTimers.length === 0) {
        renderNoTimersMessage();
    }
}

/**
 * Update the categories data array
 * @param {Array} newCategories - New categories data from the server
 */
function updateCategoriesData(newCategories) {
    categories = newCategories;
}

/**
 * Render timers in the container, grouped by category.
 */
function renderTimers() {
    // Clear the container
    timersContainer.innerHTML = '';

    let filteredTimers = [...timers]; // Create a copy

    // Apply filtering based on search or active/sticky status
    if (currentSearchTerm) {
        // Search results are already filtered by the API
        filteredTimers = timers;
    } else {
        // Default view: Show running, paused, and sticky timers
        filteredTimers = filteredTimers.filter(timer =>
            timer.status === 'running' ||
            timer.status === 'paused' ||
            timer.is_sticky
        );
    }

    // --- Grouping Logic Start ---
    const timersByCategory = {};
    filteredTimers.forEach(timer => {
        // Use category_name from API instead of category
        const categoryName = timer.category_name || 'Uncategorized';
        if (!timersByCategory[categoryName]) {
            timersByCategory[categoryName] = [];
        }
        timersByCategory[categoryName].push(timer);
    });

    // Sort categories alphabetically
    const sortedCategories = Object.keys(timersByCategory).sort((a, b) => a.localeCompare(b));
    // --- Grouping Logic End ---

    // Check if there are any timers *after* filtering and grouping attempt
    if (sortedCategories.length === 0) {
        if (currentSearchTerm) {
            renderNoTimersMessage(`No timers found matching "${currentSearchTerm}".`);
        } else {
            renderNoTimersMessage("No active or sticky timers", "Click \"Add Timer\" in the navigation bar to create a new timer.");
        }
        return;
    }

    // --- Rendering Logic with Groups ---
    sortedCategories.forEach(categoryName => {
        const categoryTimers = timersByCategory[categoryName];

        // Create the category group wrapper
        const categoryGroup = document.createElement('div');
        categoryGroup.className = 'category-group';

        // Create and append the category header
        const categoryHeader = document.createElement('div');
        categoryHeader.className = 'category-header';
        categoryHeader.innerHTML = `
            <h2>${categoryName}</h2>
            <span class="timer-count">${categoryTimers.length} timer${categoryTimers.length !== 1 ? 's' : ''}</span>
        `;
        categoryGroup.appendChild(categoryHeader);

        // Create the timers list container
        const categoryTimersList = document.createElement('div');
        categoryTimersList.className = 'category-timers-list';

        // Render each timer within this category
        categoryTimers.forEach(timer => {
            renderSingleTimer(timer, null, categoryTimersList);
        });

        // Append the timers list to the category group
        categoryGroup.appendChild(categoryTimersList);
        
        // Append the complete category group to the main container
        timersContainer.appendChild(categoryGroup);
    });
}

/**
 * Render a single timer card
 * @param {Object} timer - Timer data
 * @param {HTMLElement|null} insertBeforeElement - Element to insert before (for prepending)
 * @param {HTMLElement|null} parentContainer - Parent container to append to (default: timersContainer)
 */
function renderSingleTimer(timer, insertBeforeElement = null, parentContainer = null) {
    // Check if element already exists to prevent duplicates
    const existingTimer = document.getElementById(`timer-${timer.id}`);
    if (existingTimer) {
        return existingTimer;
    }

    // Create the col-12 wrapper for the timer
    const timerWrapper = document.createElement('div');
    timerWrapper.className = 'col-12';
    timerWrapper.id = `timer-${timer.id}`;
    timerWrapper.setAttribute('data-timer-id', timer.id);
    timerWrapper.setAttribute('data-status', timer.status);

    // Set the inner HTML using createTimerHtml for the horizontal-timer-card
    timerWrapper.innerHTML = createTimerHtml(timer);

    // Use provided parent container or fallback to main timersContainer
    const container = parentContainer || timersContainer;

    // Insert or append the timer wrapper
    if (insertBeforeElement && insertBeforeElement.parentNode === container) {
        container.insertBefore(timerWrapper, insertBeforeElement);
    } else {
        container.appendChild(timerWrapper);
    }

    // Add event listeners to the timer control buttons
    attachTimerEventListeners(timerWrapper, timer);

    return timerWrapper;
}

/**
 * Update a timer element in the DOM
 * @param {HTMLElement} timerElement - The timer element to update
 * @param {Object} timer - Updated timer data
 */
function updateTimerElement(timerElement, timer) {
    // Check if we need a full refresh or just update certain elements
    const needsFullRefresh = 
        (timer.status !== timerElement.getAttribute('data-status')) || 
        (timer.is_sticky !== timerElement.classList.contains('sticky-timer'));
    
    if (needsFullRefresh) {
        // If status changed or sticky status changed, do a full refresh
        timerElement.innerHTML = createTimerHtml(timer);
        attachTimerEventListeners(timerElement, timer);
    } else {
        // Otherwise just update dynamic elements
        
        // Update timer display
        const timerDisplay = timerElement.querySelector('.timer-current');
        if (timerDisplay) {
            timerDisplay.textContent = formatElapsedTimeClient(timer.elapsed_time);
        }
    }
}

/**
 * Create HTML for a timer card
 * @param {Object} timer - Timer data
 * @returns {string} HTML string for the timer card
 */
function createTimerHtml(timer) {
    // Determine status class
    let statusClass = '';
    let statusText = '';
    
    // Show "Locked" status if timer is locked, otherwise show regular status
    if (timer.manage_status === 'lock' || timer.manage_status === 'lock&special') {
        statusClass = 'status-locked';
        statusText = 'Locked';
    } else {
        statusClass = `status-${timer.status}`;
        statusText = timer.status.charAt(0).toUpperCase() + timer.status.slice(1);
    }

    // Determine which control buttons to show based on status
    let controlButtons = '';

    if (timer.status === 'idle') {
        controlButtons = `
            <button class="btn btn-timer-control btn-start" data-action="start" data-timer-id="${timer.id}">
                <i class="fas fa-play me-1"></i> Start
            </button>
        `;
    } else if (timer.status === 'running' || timer.status === 'paused') {
        controlButtons = `
            <button class="btn btn-timer-control btn-stop" data-action="stop" data-timer-id="${timer.id}">
                <i class="fas fa-square me-1"></i> Stop
            </button>
        `;
    }

    // Create sticky toggle button with proper icon and orientation
    const stickyIcon = 'thumbtack';
    const stickyClass = timer.is_sticky ? 'sticky-on' : 'sticky-off';
    const stickyTitle = timer.is_sticky ? 'Unstick timer' : 'Stick timer';
    
    const stickyButton = `
        <button class="sticky-toggle ${stickyClass}" data-action="toggle-sticky" data-timer-id="${timer.id}" data-sticky="${timer.is_sticky ? 0 : 1}" title="${stickyTitle}">
            <i class="fas fa-${stickyIcon}"></i>
        </button>
    `;

    // Get a smart icon based on timer name
    const timerName = timer.name.toLowerCase();
    let smartIcon = 'clock'; // Default icon
    
    // Define keyword to icon mappings
    const iconMappings = {
        'work': 'briefcase',
        'meeting': 'users',
        'study': 'book',
        'learn': 'book',
        'read': 'book-open',
        'code': 'code',
        'dev': 'laptop-code',
        'exercise': 'running',
        'fitness': 'heartbeat',
        'gym': 'dumbbell',
        'eat': 'utensils',
        'lunch': 'utensils',
        'dinner': 'utensils',
        'breakfast': 'coffee',
        'travel': 'plane',
        'project': 'tasks',
        'call': 'phone',
        'break': 'mug-hot',
        'rest': 'bed',
        'write': 'pen',
        'design': 'palette',
        'art': 'paint-brush',
        'music': 'music',
        'game': 'gamepad',
        'play': 'gamepad',
        'shop': 'shopping-cart',
        'clean': 'broom',
        'drive': 'car',
        'commute': 'bus',
        'think': 'brain',
        'plan': 'calendar',
        'research': 'search',
        'watch': 'tv'
    };
    
    // Check for matching keywords
    for (const [keyword, icon] of Object.entries(iconMappings)) {
        if (timerName.includes(keyword)) {
            smartIcon = icon;
            break;
        }
    }

    // Format current elapsed time to match our digital format
    let currentElapsedHtml = '';
    if (timer.status === 'running' || timer.status === 'paused') {
        // Get timer value in seconds
        const seconds = timer.current_elapsed || 0;
        
        // Format it ourselves to avoid format switching
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        const ms = Math.floor((seconds - Math.floor(seconds)) * 100);
        
        // Apply comma formatting for large hour numbers (≥1000)
        const displayHours = hours >= 1000 ? hours.toLocaleString() : String(hours).padStart(2, '0');
        const mainTime = `${displayHours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        const msTime = String(ms).padStart(2, '0');
        
        // Use text node for main time and a span with class for ms
        currentElapsedHtml = `<div class="timer-current">${mainTime}<span class="timer-ms">.${msTime}</span></div>`;
    } else {
        currentElapsedHtml = `<div class="timer-current-placeholder">00:00:00</div>`;
    }

    // For the total hours display
    const totalSeconds = timer.total_time || 0;
    const totalHours = totalSeconds / 3600;
    // Format with comma for thousands separator and 1 decimal place (changed from 2)
    const formattedTotalHours = new Intl.NumberFormat('en-US', { 
        minimumFractionDigits: 1, 
        maximumFractionDigits: 1 
    }).format(totalHours);

    // Add working-timer class if the timer is running
    const workingTimerClass = timer.status === 'running' ? 'working-timer' : '';
    const pausedTimerClass = timer.status === 'paused' ? 'paused-timer' : '';
    
    return `
        <div class="horizontal-timer-card ${timer.is_sticky ? 'sticky-timer' : ''} ${workingTimerClass} ${pausedTimerClass}">
            ${stickyButton}
            <div class="timer-left">
                <div class="timer-title"><a href="timer_details.php?id=${timer.id}" style="text-decoration: none; color: inherit;"><i class="fas fa-${smartIcon} me-1"></i> ${timer.name}</a></div>
                <div class="timer-details">
                    <span class="timer-category"><i class="fas fa-folder me-1"></i> ${timer.category_name || 'Uncategorized'}</span>
                    <span class="timer-hours"><i class="fas fa-clock"></i> <strong>${formattedTotalHours}h</strong></span>
                </div>
            </div>
            <div class="timer-center">
                ${currentElapsedHtml}
            </div>
            <div class="timer-right">
                ${controlButtons}
            </div>
        </div>
    `;
}

/**
 * Update the experience bar in a timer card with animation
 * @param {HTMLElement} timerElement - The timer card element
 * @param {Object} timer - Timer data
 */
function updateTimerExpBar(timerElement, timer) {
    const expBar = timerElement.querySelector('.exp-bar');
    if (!expBar) return;
    
    const expPercentage = calculateExpPercentage(timer);
    const timerId = timer.id;
    const status = timer.status;
    
    // Use our continuous animation, passing the status
    setupProgressAnimation(expBar, expPercentage, `timer-${timerId}`, status);
    
    // Update experience text if it exists
    const expText = timerElement.querySelector('.exp-text');
    if (expText) {
        expText.textContent = `${timer.experience || 0} XP`;
    }
}

/**
 * Set up or update a continuous progress animation for a specific progress bar
 * @param {HTMLElement} progressBar - The progress bar element
 * @param {number} targetPercentage - The target percentage (0-100)
 * @param {string} id - Unique identifier for this animation (e.g., 'timer-123')
 * @param {string} status - The current status of the timer ('running', 'paused', 'idle')
 */
function setupProgressAnimation(progressBar, targetPercentage, id, status) {
    // Ensure global animation store exists
    window.progressAnimations = window.progressAnimations || {};
    
    // Get existing animation data or create new
    let animData = window.progressAnimations[id];

    // --- Stop animation and remove class if timer is not running --- 
    if (status !== 'running') {
        if (animData && animData.intervalId) {
            clearInterval(animData.intervalId);
        }
        progressBar.classList.remove('continuous-progress');
        progressBar.style.width = `${targetPercentage}%`; // Set final width
        delete window.progressAnimations[id]; // Clean up store
        return; 
    }

    // --- Timer is running --- 
    progressBar.classList.add('continuous-progress'); // Ensure class is present
    
    // If animation is already running, just update the target
    if (animData && animData.intervalId) {
        animData.target = targetPercentage; 
        // Optional: Adjust increment dynamically if target changes significantly?
        // For now, let the existing interval handle it.
        return; 
    }
    
    // --- Start a new animation --- 
    let currentProgress = parseFloat(progressBar.style.width) || 0;
    const animationInterval = 50; // Update every 50ms
    
    // Initialize animation data
    animData = { 
        intervalId: null, 
        target: targetPercentage, 
        current: currentProgress 
    };
    window.progressAnimations[id] = animData;
    
    animData.intervalId = setInterval(() => {
        // Use the potentially updated target from animData
        const currentTarget = window.progressAnimations[id]?.target ?? targetPercentage;
        const currentVal = window.progressAnimations[id]?.current ?? currentProgress;
        
        const distance = currentTarget - currentVal;
        
        // Stop if target reached (or very close)
        if (Math.abs(distance) < 0.1) {
            progressBar.style.width = `${currentTarget}%`;
            clearInterval(animData.intervalId);
            // Don't remove continuous class immediately if it might restart soon
            // Let the next status check handle class removal
            delete window.progressAnimations[id];
            return;
        }
        
        // Calculate increment (adjust speed based on distance)
        let progressIncrement = distance / 20; // Adjust divisor for speed (smaller = faster)
        progressIncrement = Math.sign(distance) * Math.max(0.1, Math.abs(progressIncrement)); // Min speed 0.1%
        progressIncrement = Math.sign(distance) * Math.min(1.5, Math.abs(progressIncrement)); // Max speed 1.5%

        // Update progress
        animData.current += progressIncrement;
        progressBar.style.width = `${animData.current}%`;
        
    }, animationInterval);
}

/**
 * Attach event listeners to timer control buttons
 * @param {HTMLElement} timerElement - The timer element
 * @param {Object} timer - Timer data
 */
function attachTimerEventListeners(timerElement, timer) {
    // Start button
    const startBtn = timerElement.querySelector('[data-action="start"]');
    if (startBtn) {
        startBtn.addEventListener('click', () => startTimer(timer.id));
    }
    
    // Pause button
    const pauseBtn = timerElement.querySelector('[data-action="pause"]');
    if (pauseBtn) {
        pauseBtn.addEventListener('click', () => pauseTimer(timer.id));
    }
    
    // Resume button
    const resumeBtn = timerElement.querySelector('[data-action="resume"]');
    if (resumeBtn) {
        resumeBtn.addEventListener('click', () => resumeTimer(timer.id));
    }
    
    // Stop button
    const stopBtn = timerElement.querySelector('[data-action="stop"]');
    if (stopBtn) {
        stopBtn.addEventListener('click', () => stopTimer(timer.id));
    }
    
    // Sticky toggle button
    const stickyBtn = timerElement.querySelector('[data-action="toggle-sticky"]');
    if (stickyBtn) {
        // Remove any existing event listeners to prevent duplicates
        const newStickyBtn = stickyBtn.cloneNode(true);
        if (stickyBtn.parentNode) {
            stickyBtn.parentNode.replaceChild(newStickyBtn, stickyBtn);
        }
        
        // Add event listener with up-to-date dataset value
        newStickyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get the current data-sticky attribute value at click time (important!)
            const stickyValue = this.getAttribute('data-sticky');
            
            // Ensure we're passing the correct value to toggle
            console.log(`Toggling sticky for timer ${timer.id}, setting sticky=${stickyValue}`);
            toggleStickyTimer(timer.id, stickyValue);
        });
    }
}

/**
 * Render category filter buttons
 */
function renderCategoryFilters() {
    // Check if the container exists
    if (!categoryFiltersContainer) {
        console.log("Category filters container not found - category filter UI disabled");
        return;
    }
    
    // Clear the container first
    categoryFiltersContainer.innerHTML = '';
    
    // Create "All" button
    const allButton = document.createElement('button');
    allButton.classList.add('btn', 'category-btn');
    
    // When activeCategory is null, "All Categories" should be primary
    if (activeCategory === null) {
        allButton.classList.add('active', 'btn-primary');
    } else {
        allButton.classList.add('btn-outline-secondary');
    }
    
    allButton.innerHTML = `<i class="fas fa-layer-group me-2"></i> All Categories`;
    allButton.addEventListener('click', () => filterByCategory(null));
    categoryFiltersContainer.appendChild(allButton);
    
    // Create buttons for all categories (no pagination)
    categories.forEach(category => {
        const button = document.createElement('button');
        button.classList.add('btn', 'category-btn');
        
        // Determine if this category is active and apply appropriate styling
        if (activeCategory === parseInt(category.id)) {
            button.classList.add('active', 'btn-primary');
        } else {
            button.classList.add('btn-outline-secondary');
        }
        
        button.setAttribute('data-category-id', category.id);
        
        // Get the category icon - enhanced matching
        let categoryIcon = 'tag';
        const catName = category.name.toLowerCase();
        
        // Common icon categories with expanded keywords
        const iconCategories = {
            'briefcase': ['work', 'job', 'career', 'business', 'office', 'professional', 'corporate', 'employment'],
            'user': ['personal', 'self', 'private', 'individual', 'own', 'me', 'my'],
            'book': ['study', 'education', 'learn', 'school', 'college', 'university', 'course', 'class', 'knowledge', 'reading', 'research', 'academic', 'lecture'],
            'heartbeat': ['health', 'fitness', 'exercise', 'workout', 'gym', 'medical', 'wellness', 'sport', 'training', 'run', 'jogging', 'cycling', 'cardio'],
            'film': ['entertainment', 'fun', 'leisure', 'movie', 'game', 'play', 'relax', 'recreation', 'hobby', 'enjoy', 'chill', 'watch', 'tv', 'series'],
            'utensils': ['food', 'meal', 'cooking', 'eat', 'restaurant', 'dining', 'lunch', 'dinner', 'breakfast', 'kitchen', 'recipe', 'cuisine', 'snack'],
            'plane': ['travel', 'trip', 'journey', 'vacation', 'holiday', 'flight', 'abroad', 'tour', 'tourism', 'visit', 'destination', 'hotel'],
            'home': ['home', 'house', 'apartment', 'domestic', 'chore', 'cleaning', 'family', 'household', 'residence', 'living'],
            'users': ['meeting', 'conference', 'discussion', 'team', 'group', 'collab', 'collaboration', 'people', 'together', 'social', 'committee'],
            'tasks': ['project', 'task', 'assignment', 'todo', 'plan', 'organize', 'management', 'goal', 'objective', 'planning', 'milestone'],
            'code': ['dev', 'code', 'program', 'software', 'development', 'coding', 'programming', 'engineering', 'tech', 'application', 'web', 'technical', 'computer'],
            'money-bill-wave': ['finance', 'money', 'budget', 'banking', 'accounting', 'financial', 'income', 'expense', 'investment', 'saving', 'economic', 'bill', 'payment'],
            'shopping-cart': ['shop', 'store', 'market', 'purchase', 'buy', 'shopping', 'retail', 'ecommerce', 'mall', 'consumer', 'checkout'],
            'car': ['drive', 'car', 'vehicle', 'auto', 'transport', 'commute', 'road', 'traffic', 'transportation', 'automotive'],
            'phone': ['call', 'phone', 'mobile', 'contact', 'communication', 'telephone', 'conversation', 'support'],
            'calendar': ['schedule', 'calendar', 'date', 'appointment', 'event', 'time', 'planning', 'month', 'week', 'day'],
            'music': ['music', 'song', 'audio', 'sound', 'playlist', 'listen', 'concert', 'instrument', 'band', 'artist'],
            'palette': ['design', 'art', 'creative', 'color', 'paint', 'graphic', 'draw', 'artistic', 'visual'],
            'comments': ['chat', 'talk', 'message', 'communication', 'discussion', 'conversation', 'feedback', 'review'],
            'clipboard': ['note', 'document', 'report', 'paper', 'record', 'file', 'form', 'list', 'write'],
            'cogs': ['setting', 'config', 'technical', 'tool', 'utility', 'function', 'maintenance', 'repair', 'system'],
            'wrench': ['fix', 'repair', 'tool', 'maintenance', 'mechanical', 'handy', 'build'],
            'brain': ['think', 'idea', 'mental', 'mind', 'thought', 'concept', 'brainstorm', 'creativity'],
            'trophy': ['achievement', 'award', 'goal', 'success', 'win', 'winner', 'competition', 'challenge']
        };
        
        // Search for all matching keywords
        for (const [icon, keywords] of Object.entries(iconCategories)) {
            if (keywords.some(keyword => catName.includes(keyword))) {
                categoryIcon = icon;
                break;
            }
        }
        
        // Create structure for flexbox layout (name left, counts right)
        let namePart = `<span class="category-name-part"><i class="fas fa-${categoryIcon} me-2"></i> ${category.name}</span>`;
        let countPart = '<span class="category-count-part">';
        
        // Add running count
        if (category.running_count > 0) {
            countPart += ` <span class="ms-1 timer-count running-count"><i class="fas fa-circle text-success"></i> ${category.running_count}</span>`;
        }
        
        // Add paused count
        if (category.paused_count > 0) {
            countPart += ` <span class="ms-1 timer-count paused-count"><i class="fas fa-circle text-warning"></i> ${category.paused_count}</span>`;
        }
        
        // Add idle count
        if (category.idle_count !== undefined && category.idle_count > 0) {
            countPart += ` <span class="ms-1 timer-count idle-count"><i class="fas fa-circle text-secondary"></i> ${category.idle_count}</span>`;
        }
        countPart += '</span>';

        button.innerHTML = namePart + countPart; // Combine parts
        button.addEventListener('click', () => filterByCategory(category.id));
        categoryFiltersContainer.appendChild(button);
    });
}

/**
 * Populate the category select dropdown in the add timer form
 */
function populateCategorySelect() {
    // Store the current selected value
    const currentSelectedValue = timerCategorySelect.value;
    
    // Keep the first option (Select a category) and remove the rest
    while (timerCategorySelect.options.length > 1) {
        timerCategorySelect.remove(1);
    }
    
    // Add category options
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;
        timerCategorySelect.appendChild(option);
    });
    
    // Restore the previously selected value if it exists
    if (currentSelectedValue && currentSelectedValue !== "") {
        timerCategorySelect.value = currentSelectedValue;
    }
}

/**
 * Filter timers by category
 * @param {number|null} categoryId - Category ID to filter by, or null for all
 */
function filterByCategory(categoryId) {
    // Update active category
    activeCategory = categoryId ? parseInt(categoryId) : null;

    // Check if category buttons exist before manipulating them
    const categoryButtons = document.querySelectorAll('.category-btn');
    if (categoryButtons.length > 0) {
        // Remove all button styling classes first
        categoryButtons.forEach(button => {
            button.classList.remove('active');
            button.classList.remove('btn-primary');
            button.classList.add('btn-outline-secondary');
        });
        
        // Then set the active class and proper button styling on the clicked button
        if (categoryId === null) {
            // This is the "All" button
            const allButton = document.querySelector('.category-btn:not([data-category-id])');
            if (allButton) {
                allButton.classList.add('active');
                allButton.classList.add('btn-primary');
                allButton.classList.remove('btn-outline-secondary');
            }
        } else {
            // This is a specific category button
            const activeButton = document.querySelector(`.category-btn[data-category-id="${categoryId}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
                activeButton.classList.add('btn-primary');
                activeButton.classList.remove('btn-outline-secondary');
            }
        }
    }

    // Update URL hash for bookmarking
    if (categoryId) {
        window.history.replaceState(null, null, `#category-${categoryId}`);
    } else {
        // Remove hash without page jump
        window.history.replaceState(null, null, window.location.pathname);
    }

    // If we have a search term, reset it
    if (currentSearchTerm) {
        currentSearchTerm = '';
        document.getElementById('timer-search').value = '';
    }

    // Render filtered timers
    renderTimers();
    
    // Update "Stop All" button visibility
    updateStopAllButtonVisibility();
    
    // Update running timers section
    renderRunningTimers();
    
    // Then fetch latest data from server
    fetchTimers(false);
}

/**
 * Handle Add Timer form submission
 * @param {Event} e - Submit event
 */
function handleAddTimerFormSubmit(e) {
    e.preventDefault();
    
    const name = timerNameInput.value.trim();
    const categoryId = parseInt(timerCategorySelect.value);
    
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Timer name is required'
        });
        return;
    }
    
    if (!categoryId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a category'
        });
        return;
    }
    
    // Show loading
    displayLoading();
    
    // Send request to add timer
    fetch('api/add_timer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            category_id: categoryId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form
            addTimerForm.reset();
            
            // Close modal
            addTimerModal.hide();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });
            
            // Add new timer to the array if matching current filter
            if (!activeCategory || data.timer.category_id === activeCategory) {
                timers.unshift(data.timer);
                renderTimers();
            } else {
                // Otherwise just refresh the data
                fetchTimers();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'Failed to add timer'
            });
        }
    })
    .catch(error => {
        console.error('Error adding timer:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to connect to the server'
        });
    })
    .finally(() => {
        hideLoading();
    });
}

/**
 * Start a timer
 * @param {number} timerId - Timer ID
 */
function startTimer(timerId) {
    timerAction('start_timer.php', timerId);
}

/**
 * Pause a timer
 * @param {number} timerId - Timer ID
 */
function pauseTimer(timerId) {
    timerAction('pause_timer.php', timerId);
}

/**
 * Resume a timer
 * @param {number} timerId - Timer ID
 */
function resumeTimer(timerId) {
    // Show loading
    displayLoading();
    
    // Send request
    fetch(`api/resume_timer.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ timer_id: timerId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('The server did not return JSON data. Check the server logs.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success && data.timer) {
            // Update timer in array
            const index = timers.findIndex(t => t.id === timerId);
            if (index !== -1) {
                timers[index] = data.timer;
                
                // Update timer in DOM
                const timerElement = document.getElementById(`timer-${timerId}`);
                if (timerElement) {
                    updateTimerElement(timerElement, data.timer);
                }
                
                // Set up local timer with the correct elapsed time
                if (data.timer.current_elapsed !== undefined) {
                    localTimers[timerId] = {
                        startTime: Date.now(),
                        baseElapsed: data.timer.current_elapsed
                    };
                }
            }
            
            // Refresh category filters (for counts)
            fetchTimers(false);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'Failed to resume timer'
            });
        }
    })
    .catch(error => {
        console.error(`Error resuming timer:`, error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'Failed to connect to the server.<br>Please check that:<br>1. The database exists and is set up<br>2. PHP errors are not interrupting the JSON output<br><br>Try visiting the <a href="debug.html">debug page</a> for more information.',
            showCancelButton: true,
            confirmButtonText: 'Run Setup',
            cancelButtonText: 'OK',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'setup.php';
            }
        });
    })
    .finally(() => {
        hideLoading();
    });
}

/**
 * Stop a timer
 * @param {number} timerId - Timer ID
 */
function stopTimer(timerId) {
    timerAction('stop_timer.php', timerId);
}

/**
 * Confirm timer deletion
 * @param {number} timerId - Timer ID
 * @param {string} timerName - Timer name for confirmation message
 */
function confirmDeleteTimer(timerId, timerName) {
    Swal.fire({
        title: 'Delete Timer',
        text: `Are you sure you want to delete "${timerName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteTimer(timerId);
        }
    });
}

/**
 * Delete a timer
 * @param {number} timerId - Timer ID
 */
function deleteTimer(timerId) {
    timerAction('delete_timer.php', timerId);
}

/**
 * Handle the click event for the "Stop All" button.
 */
function handleStopAllTimers(e) {
    if (e) e.preventDefault();
    const runningTimers = timers.filter(timer => timer.status === 'running');
    
    if (runningTimers.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Timers Running',
            text: 'There are no timers currently running to stop.',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }

    Swal.fire({
        title: 'Stop All Running Timers?',
        text: `This will stop ${runningTimers.length} running timer(s).`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Stop All',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            displayLoading();
            // Create an array of promises for each stop action
            const stopPromises = runningTimers.map(timer => {
                // Modify timerAction to return the fetch promise
                return timerAction('stop_timer.php', timer.id, false); // Pass false to prevent individual loading indicators
            });

            // Wait for all stop actions to complete
            Promise.all(stopPromises)
                .then(() => {
                    // Success message removed - no notification after stopping all timers
                })
                .catch(error => {
                    console.error("Error stopping all timers:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Stopping Timers',
                        text: 'An error occurred while trying to stop all timers. Please check the console.'
                    });
                })
                .finally(() => {
                    hideLoading();
                    // Fetch timers again to update UI correctly after all stops
                    fetchTimers(false); 
                });
        }
    });
}

/**
 * Perform a timer action via API
 * @param {string} endpoint - API endpoint filename
 * @param {number} timerId - Timer ID
 * @param {boolean} [showLoading=true] - Whether to show the loading indicator for this action.
 * @returns {Promise} The fetch promise for the API call.
 */
function timerAction(endpoint, timerId, showLoading = true) {
    // Show loading if requested
    if (showLoading) {
        displayLoading();
    }
    
    // Clear local timer immediately for better UX if pausing or stopping
    if (endpoint === 'pause_timer.php' || endpoint === 'stop_timer.php') {
        delete localTimers[timerId];
    }
    
    // Create FormData for the request
    const formData = new FormData();
    formData.append('timer_id', timerId);
    
    // Send request
    fetch(`api/${endpoint}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if the response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        // Get the content type header
        const contentType = response.headers.get('content-type');
        
        // Check if the response is JSON
        if (!contentType || !contentType.includes('application/json')) {
            // First try to get the text content to see what's wrong
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('The server did not return JSON data. Check the server logs.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update timers array
            if (endpoint === 'delete_timer.php') {
                // Remove timer from array
                const index = timers.findIndex(t => t.id === timerId);
                if (index !== -1) {
                    timers.splice(index, 1);
                    
                    // Remove from localTimers if it exists
                    if (localTimers[timerId]) {
                        delete localTimers[timerId];
                    }
                    
                    // Remove timer from DOM
                    const timerElement = document.getElementById(`timer-${timerId}`);
                    if (timerElement) {
                        timerElement.remove();
                    }
                    
                    // Check if we need to show "no timers" message
                    const visibleTimers = activeCategory 
                        ? timers.filter(t => t.category_id === activeCategory)
                        : timers;
                        
                    if (visibleTimers.length === 0) {
                        renderNoTimersMessage();
                    }
                }
            } else if (data.timer) {
                // Update timer in array
                const index = timers.findIndex(t => t.id === timerId);
                if (index !== -1) {
                    timers[index] = data.timer;
                    
                    // Update timer in DOM
                    const timerElement = document.getElementById(`timer-${timerId}`);
                    if (timerElement) {
                        updateTimerElement(timerElement, data.timer);
                    }
                    
                    // Update local timer if it's running
                    if (data.timer.status === 'running') {
                        localTimers[timerId] = {
                            startTime: Date.now(),
                            baseElapsed: data.timer.current_elapsed
                        };
                    } else {
                        // If timer is no longer running, remove from local timers
                        delete localTimers[timerId];
                    }
                }
            }
            
            // Refresh category filters (for counts)
            updateCategoriesData(data.categories || categories);
            renderCategoryFilters();
            updateStopAllButtonVisibility();
            renderRunningTimers();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error || 'Failed to perform action'
            });
        }
    })
    .catch(error => {
        console.error(`Error performing ${endpoint} action:`, error);
        
        // Show user-friendly error
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'Failed to connect to the server.<br>Please check that:<br>1. The database exists and is set up<br>2. PHP errors are not interrupting the JSON output<br><br>Try visiting the <a href="debug.html">debug page</a> for more information.',
            showCancelButton: true,
            confirmButtonText: 'Run Setup',
            cancelButtonText: 'OK',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'setup.php';
            }
        });
    })
    .finally(() => {
        // Hide loading only if it was shown for this action
        if (showLoading) {
            hideLoading();
        }
    });
}

/**
 * Show loading indicator
 */
function displayLoading() {
    loadingIndicator.style.display = 'block';
}

/**
 * Hide loading indicator
 */
function hideLoading() {
    loadingIndicator.style.display = 'none';
}

/**
 * Handle timer search input
 */
function handleTimerSearch(e) {
    const searchTerm = e.target.value.toLowerCase().trim();
    
    // Clear any previous timeout that might be pending
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }
    
    // For empty search, reset immediately
    if (searchTerm === '') {
        // If search is cleared, reset the search term and re-fetch data
        currentSearchTerm = '';
        lastSearchQuery = '';
        fetchTimers();
        return;
    }
    
    // Update global search term immediately
    currentSearchTerm = searchTerm;
    lastSearchQuery = searchTerm;
    
    // Show loading indicator
    displayLoading();
    
    // Make API call immediately without debounce
    fetch(`api/search_timers.php?q=${encodeURIComponent(searchTerm)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response received:', text);
                    throw new Error('The server did not return JSON data. Check the server logs.');
                });
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Store original timers if needed
                if (!window.originalTimers) {
                    window.originalTimers = [...timers];
                }
                
                // Update timers array with search results
                timers = data.timers;
                
                // Render the search results immediately
                renderTimers();
                
                // Update running timers section
                renderRunningTimers();
            } else {
                console.error('Search error:', data.error);
                // Still try to render what we have
                renderTimers();
            }
        })
        .catch(error => {
            console.error('Error searching timers:', error);
            // Still try to render what we have
            renderTimers();
        })
        .finally(() => {
            hideLoading();
        });
}

/**
 * Update the visibility of the "Stop All" button based on running timers.
 */
function updateStopAllButtonVisibility() {
    // Check if the stop-all-btn element exists
    var stopAllBtn = document.getElementById('stop-all-btn');
    var stopAllBtnMobile = document.getElementById('stop-all-btn-mobile');
    var timerCountBadge = document.getElementById('timer-count-badge');
    var timerCountBadgeMobile = document.getElementById('timer-count-badge-mobile');
    
    // Return if elements don't exist (happens in admin pages)
    if (!stopAllBtn || !stopAllBtnMobile) {
        return;
    }
    
    // Check if any timer is running
    var runningTimers = timers.filter(function(timer) {
        return timer.status === 'running';
    });
    
    var runningCount = runningTimers.length;
    
    if (runningCount > 0) {
        stopAllBtn.classList.add('active');
        stopAllBtnMobile.classList.add('active');
        
        // Update the badge count and display
        timerCountBadge.textContent = runningCount;
        timerCountBadgeMobile.textContent = runningCount;
        timerCountBadge.style.display = 'inline-flex';
        timerCountBadgeMobile.style.display = 'inline-flex';
    } else {
        stopAllBtn.classList.remove('active');
        stopAllBtnMobile.classList.remove('active');
        
        // Hide the badges when no timers are running
        timerCountBadge.style.display = 'none';
        timerCountBadgeMobile.style.display = 'none';
    }
}

/**
 * Format elapsed time for client-side display
 * @param {number} seconds - Elapsed seconds
 * @returns {Object} Object with formatted time versions
 */
function formatElapsedTimeClient(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    const ms = Math.floor((seconds - Math.floor(seconds)) * 100);
    
    // Apply comma formatting for large hour numbers (≥1000)
    const displayHours = hours >= 1000 ? hours.toLocaleString() : String(hours).padStart(2, '0');
    
    // Standard display format (HH:MM:SS)
    const standard = `${displayHours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    
    // Format for running bar (HH:MM:SS without ms)
    const runningBar = standard;
    
    // Format for card display (with milliseconds)
    const withMs = standard + '.' + String(ms).padStart(2, '0');
    
    return {
        standard: standard,
        withMs: withMs,
        runningBar: runningBar,
        ms: String(ms).padStart(2, '0')
    };
}

/**
 * Render the section displaying currently running timers.
 */
function renderRunningTimers() {
    // Skip if running timers section or container was removed
    if (!runningTimersSection || !runningTimersContainer) {
        console.log("Running timers section or container not found - running timers UI disabled");
        return;
    }

    const runningTimers = timers.filter(timer => timer.status === 'running');

    // Clear previous running timers
    runningTimersContainer.innerHTML = '';

    if (runningTimers.length > 0) {
        // Show the section
        runningTimersSection.style.display = 'block';

        runningTimers.forEach(timer => {
            const item = document.createElement('div');
            item.className = 'running-timer-item';
            item.id = `running-timer-item-${timer.id}`;

            // Initial duration display (will be updated dynamically by startClientSideUpdates)
            // Use the fetched current_elapsed_formatted for initial state, but reformat it
            const initialFormattedTimes = formatElapsedTimeClient(timer.current_elapsed || 0);
            
            item.innerHTML = `
                <button class="running-stop-button" data-action="stop" data-timer-id="${timer.id}">Stop</button>
                <div class="running-timer-text">
                    ${timer.name}
                    <span class="running-timer-duration"> ${initialFormattedTimes.runningBar}</span> 
                </div>
            `;
            runningTimersContainer.appendChild(item);

            // Add event listener to the stop button
            const stopButton = item.querySelector('.running-stop-button');
            if (stopButton) {
                stopButton.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent potential conflicts if item itself had listeners
                    timerAction('stop_timer.php', timer.id);
                });
            }
        });
    } else {
        // Hide the section if no timers are running
        runningTimersSection.style.display = 'none';
    }
}

/**
 * Add pagination controls to a container
 * @param {HTMLElement} container - The container to add pagination controls to
 * @param {number|string} categoryId - The category ID
 * @param {number} totalPages - Total number of pages
 */
function addPaginationControls(container, categoryId, totalPages) {
    const currentPage = timerPages[categoryId];
    
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'timer-pagination d-flex justify-content-center align-items-center mt-3 w-100';
    
    // Create previous button
    const prevButton = document.createElement('button');
    prevButton.className = 'btn btn-sm btn-outline-secondary';
    prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
        if (timerPages[categoryId] > 1) {
            timerPages[categoryId]--;
            renderTimers();
        }
    });
    
    // Create page indicator
    const pageIndicator = document.createElement('span');
    pageIndicator.className = 'mx-2';
    pageIndicator.textContent = `${currentPage} / ${totalPages}`;
    
    // Create next button
    const nextButton = document.createElement('button');
    nextButton.className = 'btn btn-sm btn-outline-secondary';
    nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (timerPages[categoryId] < totalPages) {
            timerPages[categoryId]++;
            renderTimers();
        }
    });
    
    // Add to container
    paginationContainer.appendChild(prevButton);
    paginationContainer.appendChild(pageIndicator);
    paginationContainer.appendChild(nextButton);
    
    // Add to container
    container.appendChild(paginationContainer);
}

// Theme Toggle Function
function toggleTheme() {
    const htmlElement = document.documentElement;
    const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    // Set the theme attribute
    htmlElement.setAttribute('data-theme', newTheme);
    
    // Save the preference to localStorage
    localStorage.setItem('timer-app-theme', newTheme);
    
    // Update the toggle button icon
    const themeToggleIcon = document.getElementById('theme-toggle-icon');
    if (themeToggleIcon) {
        if (newTheme === 'dark') {
            themeToggleIcon.classList.remove('fa-sun');
            themeToggleIcon.classList.add('fa-moon');
        } else {
            themeToggleIcon.classList.remove('fa-moon');
            themeToggleIcon.classList.add('fa-sun');
        }
    }
}

// Initialize theme on page load
function initTheme() {
    const savedTheme = localStorage.getItem('timer-app-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeToggleIcon = document.getElementById('theme-toggle-icon');
    if (themeToggleIcon) {
        if (savedTheme === 'dark') {
            themeToggleIcon.classList.remove('fa-sun');
            themeToggleIcon.classList.add('fa-moon');
        } else {
            themeToggleIcon.classList.remove('fa-moon');
            themeToggleIcon.classList.add('fa-sun');
        }
    }
}

/**
 * Toggle a timer's sticky status
 * @param {number} timerId - Timer ID
 * @param {number|string} sticky - New sticky status (0 or 1)
 */
function toggleStickyTimer(timerId, sticky) {
    // Prepare form data
    const formData = new FormData();
    formData.append('timer_id', timerId);
    formData.append('sticky', sticky);
    
    // Show loading
    displayLoading();
    
    // Find the timer element and its components
    const timerElement = document.getElementById(`timer-${timerId}`);
    if (!timerElement) {
        console.error(`Timer element with ID timer-${timerId} not found`);
        hideLoading();
        return;
    }
    
    // Get the timer card - could be horizontal-timer-card or timer-card depending on view
    const timerCard = timerElement.querySelector('.horizontal-timer-card') || timerElement.querySelector('.timer-card');
    if (!timerCard) {
        console.error(`Timer card not found within timer-${timerId}`);
        hideLoading();
        return;
    }
    
    // Get the sticky button
    const stickyBtn = timerElement.querySelector('[data-action="toggle-sticky"]');
    if (!stickyBtn) {
        console.error(`Sticky button not found within timer-${timerId}`);
        hideLoading();
        return;
    }
    
    // Convert sticky to boolean for cleaner code (ensure it's treated as a number first)
    const stickyNum = parseInt(sticky);
    const makeSticky = stickyNum === 1;
    
    // Send request first, then update UI only after success
    fetch('api/sticky_timer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response received:', text);
                throw new Error('The server did not return JSON data. Check the server logs.');
            });
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI after successful server response
            if (makeSticky) {
                // Setting to sticky
                timerCard.classList.add('sticky-timer');
                stickyBtn.classList.add('sticky-on');
                stickyBtn.classList.remove('sticky-off');
                stickyBtn.setAttribute('data-sticky', "0");
                stickyBtn.setAttribute('title', "Unstick timer");
            } else {
                // Setting to not sticky
                timerCard.classList.remove('sticky-timer');
                stickyBtn.classList.remove('sticky-on');
                stickyBtn.classList.add('sticky-off');
                stickyBtn.setAttribute('data-sticky', "1");
                stickyBtn.setAttribute('title', "Stick timer");
            }

            // Update timer in array
            const index = timers.findIndex(t => t.id === parseInt(timerId));
            if (index !== -1) {
                // Update the is_sticky property in the timers array
                timers[index].is_sticky = makeSticky;
                console.log(`Timer ${timerId} sticky status updated to ${makeSticky ? 'sticky' : 'not sticky'}`);
            }
            
            // Update categories if provided
            if (data.categories) {
                updateCategoriesData(data.categories);
                renderCategoryFilters();
            }
        } else {
            // Show error
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update timer sticky status'
            });
        }
    })
    .catch(error => {
        console.error('Error toggling sticky status:', error);
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to connect to the server'
        });
    })
    .finally(() => {
        hideLoading();
    });
}

// Handle search input
function handleSearch() {
  const searchInput = document.getElementById('searchInput');
  
  searchInput.addEventListener('input', function(e) {
    const query = e.target.value.trim();
    clearTimeout(searchTimeout);
    
    // Update the current search term
    currentSearchTerm = query;
    
    if (query === '') {
      // If search is cleared, reset and show all timers
      currentSearchTerm = '';
      lastSearchQuery = '';
      renderTimers(); // Use renderTimers instead of displayTimers
      return;
    }
    
    // Set a small delay to avoid searching on every keystroke
    searchTimeout = setTimeout(() => {
      if (query !== lastSearchQuery) {
        lastSearchQuery = query;
        searchTimers(query);
      } 
    }, 300);
  });
}

// Search for timers matching the query
function searchTimers(query) {
  // Show loading indicator while searching
  displayLoading();
  
  // Make API call to search_timers.php
  fetch(`api/search_timers.php?q=${encodeURIComponent(query)}`)
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Update timers array with search results
        timers = data.timers;
        
        // Update UI with search results
        renderTimers();
        
        // Update running timers section
        renderRunningTimers();
      } else {
        console.error('Search error:', data.error);
        Swal.fire({
          icon: 'error',
          title: 'Search Error',
          text: data.error || 'Failed to search timers'
        });
      }
    })
    .catch(error => {
      console.error('Search error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to search timers. Please try again.'
      });
    })
    .finally(() => {
      hideLoading();
      if (query === currentSearchTerm) {
        // Only clear if this was the most recent search
        currentSearchTerm = query; // Keep current search term to show search context
      }
    });
}

// Timer Level Progress Bar Functions
function initTimerLevelProgress() {
    // Initialize level progress for all timers on page load
    $('.timer-card').each(function() {
        const timerId = $(this).data('timer-id');
        updateTimerLevelProgress(timerId);
    });
    
    // Initialize level progress for running timers in the horizontal format
    $('.horizontal-timer-card').each(function() {
        const timerId = $(this).data('timer-id');
        updateTimerLevelProgress(timerId);
    });
}

function updateTimerLevelProgress(timerId) {
    if (!timerId) return;
    
    $.ajax({
        url: 'api/get_timer_xp.php',
        type: 'GET',
        data: { timer_id: timerId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderTimerLevelProgress(timerId, response);
                
                // If timer is running, schedule updates
                const timerStatus = $(`.timer-card[data-timer-id="${timerId}"]`).data('status') || 
                                   $(`.horizontal-timer-card[data-timer-id="${timerId}"]`).data('status');
                
                if (timerStatus === 'running') {
                    // Update progress every 15 seconds for running timers
                    setTimeout(function() {
                        updateTimerLevelProgress(timerId);
                    }, 15000);
                }
            }
        }
    });
}

function renderTimerLevelProgress(timerId, data) {
    if (!timerId || !data || !data.success) {
        console.log("Invalid data for timer level progress", data);
        return;
    }
    
    const levelProgress = data.level_progress || 0;
    const level = data.level || 1;
    const xpForNextLevel = data.xp_for_next_level || 100;
    
    // Find level containers for this timer in both card formats
    const cardLevelContainers = $(`.timer-card[data-timer-id="${timerId}"] .timer-level-container, 
                                 .horizontal-timer-card[data-timer-id="${timerId}"] .timer-level-container`);
    
    if (cardLevelContainers.length === 0) {
        // Create level container if it doesn't exist
        const levelHtml = `
            <div class="timer-level-container">
                <div class="timer-level-badge">${level}</div>
                <div class="timer-level-progress">
                    <div class="timer-level-progress-bar" style="width: ${levelProgress}%"></div>
                </div>
                <div class="timer-level-info">
                    <span class="timer-level-text">Level ${level}</span>
                    <span class="timer-level-percent">${levelProgress}% (${xpForNextLevel} XP to next)</span>
                </div>
            </div>
        `;
        
        // Find the timer cards
        const timerCard = $(`.timer-card[data-timer-id="${timerId}"]`);
        const horizontalCard = $(`.horizontal-timer-card[data-timer-id="${timerId}"]`);
        
        // Add to both card formats if they exist
        if (timerCard.length > 0) {
            timerCard.find('.timer-info').after(levelHtml);
        }
        
        if (horizontalCard.length > 0) {
            horizontalCard.find('.timer-right').append(levelHtml);
        }
    } else {
        // Update existing progress bar
        cardLevelContainers.each(function() {
            const container = $(this);
            const progressBar = container.find('.timer-level-progress-bar');
            const levelBadge = container.find('.timer-level-badge');
            const levelText = container.find('.timer-level-text');
            const levelPercent = container.find('.timer-level-percent');
            
            // Check if level has increased
            const currentLevel = parseInt(levelBadge.text()) || 1;
            if (level > currentLevel) {
                // Add level-up animation
                levelBadge.addClass('level-up');
                setTimeout(function() {
                    levelBadge.removeClass('level-up');
                }, 600);
                
                // Show a toast notification
                Swal.fire({
                    title: 'Level Up!',
                    text: `Timer "${timerId}" reached level ${level}!`,
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
            
            // Update elements
            levelBadge.text(level);
            levelText.text(`Level ${level}`);
            levelPercent.text(`${levelProgress}% (${xpForNextLevel} XP to next)`);
            
            // Animate width change
            progressBar.css('width', `${levelProgress}%`);
        });
    }
}

// Function to update timer XP on timer stop
function updateTimerXPOnStop(timerId, seconds) {
    if (!timerId || !seconds) return;
    
    const xpGained = Math.max(1, Math.floor(seconds / 60)); // 1 XP per minute, minimum 1
    
    $.ajax({
        url: 'api/update_timer_xp.php',
        type: 'POST',
        data: {
            timer_id: timerId,
            xp_gained: xpGained
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update the progress bar with new data
                updateTimerLevelProgress(timerId);
                
                // Show XP gain notification if significant
                if (xpGained >= 5) {
                    Swal.fire({
                        title: '+' + xpGained + ' XP',
                        text: 'Gained for timer activity',
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
                
                // If the timer leveled up, show a more prominent notification
                if (response.level_up) {
                    setTimeout(() => {
                        Swal.fire({
                            title: 'Level Up!',
                            text: `Timer reached level ${response.new_level}!`,
                            icon: 'success',
                            confirmButtonText: 'Awesome!',
                            timer: 5000,
                            timerProgressBar: true
                        });
                    }, 300); // Small delay to let the first notification finish
                }
            } else {
                console.error('Failed to update XP:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('XP update error:', error);
        }
    });
}

// Add to document ready
$(document).ready(function() {
    // Add this line to initialize timer levels
    initTimerLevelProgress();
    
    // Modify the stopTimer function to update XP
    const originalStopTimer = window.stopTimer;
    window.stopTimer = function(timerId) {
        // Get current elapsed time before stopping
        const timerSeconds = getElapsedSeconds(timerId);
        
        // Call the original function
        originalStopTimer(timerId);
        
        // Update XP based on elapsed time
        updateTimerXPOnStop(timerId, timerSeconds);
    };
    
    // Helper function to get elapsed seconds for a timer
    function getElapsedSeconds(timerId) {
        const timerElement = $(`.timer-current[data-timer-id="${timerId}"]`);
        if (timerElement.length === 0) return 0;
        
        const timerText = timerElement.text();
        const timeParts = timerText.split(':');
        
        if (timeParts.length !== 3) return 0;
        
        const hours = parseInt(timeParts[0]) || 0;
        const minutes = parseInt(timeParts[1]) || 0;
        const seconds = parseInt(timeParts[2]) || 0;
        
        return hours * 3600 + minutes * 60 + seconds;
    }
});

/**
 * Add custom CSS styles to the document
 */
function addCustomStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* Category Group Styles */
        .category-group {
            margin-bottom: 2rem;
            background: transparent;
            border-radius: 8px;
            overflow: hidden;
            width: 100%;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1.5rem;
            background: var(--card-bg);
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid var(--border-color, rgba(0, 0, 0, 0.1));
            margin-bottom: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .category-header {
            border-bottom: 1px solid var(--border-color, rgba(255, 255, 255, 0.1));
        }

        .category-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--heading-color);
        }

        .category-header .timer-count {
            font-size: 0.9rem;
            color: var(--text-muted);
            background: var(--count-bg, rgba(0, 0, 0, 0.1));
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
        }

        .category-timers-list {
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .category-timers-list .col-12:not(:last-child) {
            margin-bottom: 1rem;
        }
        
        .horizontal-timer-card {
            margin-bottom: 0.75rem;
            border-radius: 6px;
        }
        
        .category-timers-list .col-12:last-child .horizontal-timer-card {
            margin-bottom: 0;
        }
        
        /* Timer Details Styles */
        .timer-details {
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
        }
        
        .timer-category {
            display: inline-flex;
            align-items: center;
            background-color: rgba(0, 123, 255, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--primary-color, #007bff);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
        
        [data-theme="dark"] .timer-category {
            background-color: rgba(0, 123, 255, 0.2);
        }
        
        .timer-hours {
            white-space: nowrap;
        }

        /* Working Timer Highlights */
        .working-timer {
            border-left: 4px solid #28a745 !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2) !important;
            background-color: rgba(40, 167, 69, 0.05) !important;
        }
        
        [data-theme="dark"] .working-timer {
            background-color: rgba(40, 167, 69, 0.1) !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3) !important;
        }
        
        .paused-timer {
            border-left: 4px solid #ffc107 !important;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2) !important;
            background-color: rgba(255, 193, 7, 0.05) !important;
        }
        
        [data-theme="dark"] .paused-timer {
            background-color: rgba(255, 193, 7, 0.1) !important;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3) !important;
        }
        
        /* Better sticky button styling */
        .sticky-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            color: #adb5bd;
            padding: 5px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.2s ease;
            opacity: 0.4;
        }
        
        .sticky-toggle:hover {
            opacity: 1;
            transform: scale(1.2);
        }
        
        .sticky-toggle.sticky-on {
            color: #ffc107;
            opacity: 0.8;
        }
        
        .sticky-toggle.sticky-on:hover {
            opacity: 1;
        }
        
        [data-theme="dark"] .sticky-toggle {
            color: #6c757d;
        }
        
        [data-theme="dark"] .sticky-toggle.sticky-on {
            color: #ffc107;
        }
        
        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .category-header {
                padding: 0.7rem 1rem;
                border-radius: 6px 6px 0 0;
            }

            .category-header h2 {
                font-size: 1.1rem;
            }

            .category-timers-list {
                padding: 0.75rem;
                border-radius: 0 0 6px 6px;
            }

            .category-timers-list .col-12:not(:last-child) {
                margin-bottom: 0.75rem;
            }

            .horizontal-timer-card {
                padding: 12px 15px 12px 18px !important;
                margin-bottom: 10px !important;
            }
            
            .timer-center {
                width: 45% !important;
            }
            
            .timer-left {
                width: 30% !important;
            }
            
            .timer-right {
                width: 25% !important;
            }
            
            .timer-title {
                font-size: 0.85rem !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
            }
            
            .timer-details {
                flex-wrap: wrap !important;
                gap: 6px !important;
                margin-top: 3px !important;
            }
            
            .timer-category {
                font-size: 0.7rem !important;
                padding: 1px 6px !important;
                max-width: 120px !important;
            }
            
            .timer-hours {
                font-size: 0.75rem !important;
            }
            
            .horizontal-timer-card .timer-current,
            .horizontal-timer-card .timer-current-placeholder {
                font-size: 1.2rem !important;
                min-width: auto !important;
                max-width: 100% !important;
            }
            
            .horizontal-timer-card .timer-ms {
                font-size: 0.7rem !important;
            }
            
            .btn-timer-control {
                padding: 6px 12px !important;
                font-size: 0.9rem !important;
                width: 100% !important;
                max-width: 100px !important;
            }
            
            .horizontal-timer-card .btn-stop {
                background-color: #dd3545 !important;
                color: white !important;
            }
        }
    `;
    document.head.appendChild(styleElement);
}

function displayTimers(timers) {
    const timersContainer = $('#timers-container');
    timersContainer.empty();

    // Group timers by category
    const timersByCategory = {};
    timers.forEach(timer => {
        // Use category_name from API instead of category
        const categoryName = timer.category_name || 'Uncategorized';
        if (!timersByCategory[categoryName]) {
            timersByCategory[categoryName] = [];
        }
        timersByCategory[categoryName].push(timer);
    });

    // Sort categories alphabetically
    const sortedCategories = Object.keys(timersByCategory).sort();

    // Create HTML for each category
    sortedCategories.forEach(category => {
        const categoryTimers = timersByCategory[category];
        const categoryHtml = `
            <div class="category-group">
                <div class="category-header">
                    <h2>${category}</h2>
                    <span class="timer-count">${categoryTimers.length} timer${categoryTimers.length !== 1 ? 's' : ''}</span>
                </div>
                <div class="row">
                    ${categoryTimers.map(timer => createTimerCard(timer)).join('')}
                </div>
            </div>
        `;
        timersContainer.append(categoryHtml);
    });

    // If no timers, show message
    if (timers.length === 0) {
        timersContainer.html('<div class="no-timers">No timers found</div>');
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}