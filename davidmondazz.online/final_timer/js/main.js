/**
 * Timer Tracking Application
 * Main JavaScript functionality
 */

// Configuration
const CONFIG = {
    pollingInterval: 3000, // How often to poll for updates (ms)
};

// DOM Elements
const addTimerBtn = document.getElementById('add-timer-btn');
const addTimerModal = document.getElementById('add-timer-modal');
const closeModalBtn = document.querySelector('.close');
const addTimerForm = document.getElementById('add-timer-form');
const timerNameInput = document.getElementById('timer-name');
const timerCategorySelect = document.getElementById('timer-category');
const timersContainer = document.getElementById('timers-container');
const emptyState = document.getElementById('empty-state');
const categoryList = document.getElementById('category-list');
const timerTemplate = document.getElementById('timer-template');
const fontSelector = document.getElementById('font-selector');

// App State
const state = {
    timers: [],
    categories: [],
    activeCategory: 0, // 0 means "All"
    intervals: {}, // For tracking setInterval IDs
    preferences: {
        font: 'Poppins', // Default font
        databaseFont: null // Added for database font preference
    }
};

// Load font preference from localStorage and database
function loadFontPreference() {
    // First try to load from localStorage for quick application
    const savedFont = localStorage.getItem('timerAppFont');
    if (savedFont) {
        state.preferences.font = savedFont;
        fontSelector.value = savedFont;
        applyFont(savedFont);
    }
    
    // Then load preferences from database to ensure synchronization
    loadUserPreferences().then(preferences => {
        if (preferences && preferences.font) {
            // If database has a different font preference, override localStorage
            if (state.preferences.font !== preferences.font) {
                state.preferences.font = preferences.font;
                fontSelector.value = preferences.font;
                applyFont(preferences.font);
                // Update localStorage to match database
                localStorage.setItem('timerAppFont', preferences.font);
            }
        }
    }).catch(error => {
        console.error('Error loading font preference from database:', error);
    });
}

// Save font preference to localStorage and update in database
function saveFontPreference(font) {
    // Save to localStorage for quick access on this device
    localStorage.setItem('timerAppFont', font);
    state.preferences.font = font;
    
    // Save to database for cross-browser synchronization
    saveUserPreference('font', font)
        .then(success => {
            if (!success) {
                console.error('Failed to save font preference to database');
            }
        })
        .catch(error => {
            console.error('Error saving font preference to database:', error);
        });
}

// Apply font to the body
function applyFont(fontName) {
    document.body.className = '';
    document.body.classList.add(`font-${fontName.toLowerCase().replace(/\s+/g, '')}`);
}

// Load saved category selection from localStorage
function loadCategorySelection() {
    const savedCategory = localStorage.getItem('timerAppCategory');
    if (savedCategory) {
        const categoryId = parseInt(savedCategory);
        state.activeCategory = categoryId;
        
        // Apply the category filter immediately if categories are loaded
        if (state.categories.length > 0) {
            filterTimersByCategory(categoryId);
        } else {
            // If categories aren't loaded yet, wait for them
            const checkCategories = setInterval(() => {
                if (state.categories.length > 0) {
                    filterTimersByCategory(categoryId);
                    clearInterval(checkCategories);
                }
            }, 100);
            
            // Clear interval after 5 seconds to prevent infinite checking
            setTimeout(() => clearInterval(checkCategories), 5000);
        }
    }
}

// Save category selection to localStorage
function saveCategorySelection(categoryId) {
    localStorage.setItem('timerAppCategory', categoryId);
    
    // If the page is being refreshed, temporarily store a flag
    if (window.isRefreshing) {
        sessionStorage.setItem('fromRefresh', 'true');
    }
}

// Check if we're returning from a refresh and should maintain the current category
function checkForRefresh() {
    if (sessionStorage.getItem('fromRefresh') === 'true') {
        // Clear the flag
        sessionStorage.removeItem('fromRefresh');
        // Keep the current category (already stored in localStorage)
        const savedCategory = localStorage.getItem('timerAppCategory');
        if (savedCategory) {
            state.activeCategory = parseInt(savedCategory);
        }
        return true;
    }
    return false;
}

// Utility Functions
function formatTime(seconds, formatType = 'standard') {
    if (isNaN(seconds) || seconds < 0) seconds = 0;
    
    if (formatType === 'standard') {
        // Standard format (HH:MM:SS)
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        return [
            hours.toString().padStart(2, '0'),
            minutes.toString().padStart(2, '0'),
            secs.toString().padStart(2, '0')
        ].join(':');
    } else if (formatType === 'compact') {
        // Compact format (e.g., 1.4hrs, 45mins, 30secs)
        if (seconds >= 3600) {
            // Hours format (with 1 decimal place)
            const hours = (seconds / 3600).toFixed(1);
            return `${hours}hrs`;
        } else if (seconds >= 60) {
            // Minutes format
            const minutes = Math.floor(seconds / 60);
            return `${minutes}mins`;
        } else {
            // Seconds format
            return `${Math.floor(seconds)}secs`;
        }
    }
    
    return '00:00:00'; // Fallback
}

function makeRequest(action, data = {}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/timer_api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (this.status >= 200 && this.status < 300) {
                try {
                    const response = JSON.parse(this.responseText);
                    resolve(response);
                } catch (e) {
                    reject(new Error('Invalid JSON response'));
                }
            } else {
                reject(new Error(`Request failed with status ${this.status}`));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        // Prepare data for sending
        data.action = action;
        const params = new URLSearchParams();
        
        for (const key in data) {
            params.append(key, data[key]);
        }
        
        xhr.send(params.toString());
    });
}

// Display Functions
function showModal() {
    // Use SweetAlert instead of modal
    Swal.fire({
        title: 'Add New Timer',
        html: `
            <div class="form-group">
                <label for="swal-timer-name">Timer Name:</label>
                <input type="text" id="swal-timer-name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="swal-timer-category">Category:</label>
                <select id="swal-timer-category" class="form-select" required>
                    ${state.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
                    <option value="new">-- Add New Category --</option>
                </select>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#3498db',
        preConfirm: () => {
            const timerName = document.getElementById('swal-timer-name').value;
            const categoryId = document.getElementById('swal-timer-category').value;
            
            if (!timerName) {
                Swal.showValidationMessage('Please enter a timer name');
                return false;
            }
            
            if (!categoryId || categoryId === '') {
                Swal.showValidationMessage('Please select a category');
                return false;
            }
            
            if (categoryId === 'new') {
                // Return signal to create a new category
                return { createCategory: true };
            }
            
            return { 
                name: timerName,
                categoryId: categoryId
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.createCategory) {
                // Show new category prompt
                showAddCategoryPrompt();
            } else {
                // Add the timer
                addTimer(result.value.name, result.value.categoryId)
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Timer added successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `Error adding timer: ${error.message}`
                        });
                    });
            }
        }
    });
}

function hideModal() {
    addTimerModal.style.display = 'none';
    addTimerForm.reset();
}

function renderCategories() {
    // Clear existing category filter buttons except "All"
    const existingButtons = categoryList.querySelectorAll('.category-btn:not([data-category-id="0"])');
    existingButtons.forEach(button => button.remove());
    
    // Calculate counters for each category
    const counters = {
        0: { total: 0, running: 0, paused: 0 } // All category
    };
    
    // Initialize counters for all categories
    state.categories.forEach(category => {
        const categoryId = parseInt(category.id);
        counters[categoryId] = { total: 0, running: 0, paused: 0 };
    });
    
    // Count timers in each category
    state.timers.forEach(timer => {
        const categoryId = parseInt(timer.category_id);
        
        // Increment counters
        counters[categoryId].total++;
        counters[0].total++; // Also increment "All" category
        
        if (timer.status === 'running') {
            counters[categoryId].running++;
            counters[0].running++;
        } else if (timer.status === 'paused') {
            counters[categoryId].paused++;
            counters[0].paused++;
        }
    });
    
    // Update "All" category button
    const allCategoryBtn = document.querySelector('.category-btn[data-category-id="0"]');
    if (allCategoryBtn) {
        // Reset the button HTML to its base state first
        allCategoryBtn.innerHTML = 'All';
        
        // Add counters for All category
        if (counters[0].running > 0) {
            allCategoryBtn.innerHTML += `<span class="counter-badge running">${counters[0].running}</span>`;
        }
        
        if (counters[0].paused > 0) {
            allCategoryBtn.innerHTML += `<span class="counter-badge paused">${counters[0].paused}</span>`;
        }
        
        // Make sure the All button has a click handler
        if (!allCategoryBtn.hasClickListener) {
            allCategoryBtn.addEventListener('click', () => {
                filterTimersByCategory(0);
            });
            allCategoryBtn.hasClickListener = true;
        }
    }
    
    // Add the category filter buttons with counters
    state.categories.forEach(category => {
        const button = document.createElement('button');
        button.classList.add('category-btn');
        button.dataset.categoryId = category.id;
        button.innerHTML = category.name;
        
        // Add counter badges - only for active timers (running or paused)
        const categoryId = parseInt(category.id);
        const categoryCounters = counters[categoryId];
        
        // Only add running counter if there are running timers
        if (categoryCounters.running > 0) {
            button.innerHTML += `<span class="counter-badge running">${categoryCounters.running}</span>`;
        }
        
        // Only add paused counter if there are paused timers
        if (categoryCounters.paused > 0) {
            button.innerHTML += `<span class="counter-badge paused">${categoryCounters.paused}</span>`;
        }
        
        button.addEventListener('click', () => {
            filterTimersByCategory(category.id);
        });
        
        categoryList.appendChild(button);
    });
    
    // Add "Add Category" button
    const addCategoryBtn = document.createElement('button');
    addCategoryBtn.classList.add('category-btn', 'add-category-btn');
    addCategoryBtn.innerHTML = 'Add Category';
    addCategoryBtn.addEventListener('click', showAddCategoryPrompt);
    categoryList.appendChild(addCategoryBtn);
    
    // Set active category based on state
    document.querySelectorAll('.category-btn').forEach(button => {
        if (parseInt(button.dataset.categoryId) === state.activeCategory) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

function showAddCategoryPrompt() {
    Swal.fire({
        title: 'Add New Category',
        input: 'text',
        inputLabel: 'Category Name',
        inputPlaceholder: 'Enter category name',
        showCancelButton: true,
        confirmButtonColor: '#3498db',
        inputValidator: (value) => {
            if (!value || value.trim() === '') {
                return 'You need to write something!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            addNewCategory(result.value.trim());
        }
    });
}

function addNewCategory(name) {
    return makeRequest('add_category', { name })
        .then(response => {
            if (response.success) {
                // Reload categories to include the new one
                return loadCategories();
            } else {
                throw new Error(response.message || 'Failed to add category');
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: error.message
            });
        });
}

function filterTimersByCategory(categoryId) {
    state.activeCategory = parseInt(categoryId);
    
    // Save selection
    saveCategorySelection(categoryId);
    
    // Update active class on category buttons
    document.querySelectorAll('.category-btn').forEach(button => {
        if (parseInt(button.dataset.categoryId) === state.activeCategory) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
    
    // Fetch timers with the selected category
    loadTimers();
}

function createTimerElement(timer) {
    // Clone the template
    const timerElement = timerTemplate.content.cloneNode(true).firstElementChild;
    
    // Set timer ID
    timerElement.dataset.timerId = timer.id;
    
    // Add timer status class
    timerElement.classList.add(timer.status);
    
    // Set timer details - name in header, category in footer
    timerElement.querySelector('.timer-title').textContent = timer.name;
    const categoryNameElement = timerElement.querySelector('.category-name');
    categoryNameElement.textContent = timer.category_name;
    
    // Make category name clickable
    categoryNameElement.style.cursor = 'pointer';
    categoryNameElement.classList.add('clickable-category');
    categoryNameElement.addEventListener('click', () => {
        // Navigate to the timer's category
        filterTimersByCategory(timer.category_id);
    });
    
    // Set current elapsed time (if running or paused)
    let currentElapsed = 0;
    if (timer.current_elapsed !== undefined) {
        currentElapsed = timer.current_elapsed;
    }
    
    const currentTimeElement = timerElement.querySelector('.current-time');
    const currentTimeValue = timerElement.querySelector('.current-time .time-value');
    const milliseconds = currentTimeValue.querySelector('.milliseconds');
    
    // Show placeholder time for idle/stopped timers, but keep milliseconds visible
    if (timer.status === 'idle' || timer.status === 'stopped') {
        currentTimeElement.style.display = 'flex';
        // We need to set the main time value without removing the milliseconds span
        const textNode = document.createTextNode('00:00:00');
        // Clear any existing content but keep the milliseconds span
        while (currentTimeValue.firstChild && currentTimeValue.firstChild !== milliseconds) {
            currentTimeValue.removeChild(currentTimeValue.firstChild);
        }
        // Insert the main time text before the milliseconds span
        currentTimeValue.insertBefore(textNode, milliseconds);
        // Set placeholder milliseconds
        milliseconds.textContent = '.00';
    } else {
        currentTimeElement.style.display = 'flex';
        // We need to set the main time value without removing the milliseconds span
        const mainTimeText = formatTime(currentElapsed);
        const textNode = document.createTextNode(mainTimeText);
        // Clear any existing content but keep the milliseconds span
        while (currentTimeValue.firstChild && currentTimeValue.firstChild !== milliseconds) {
            currentTimeValue.removeChild(currentTimeValue.firstChild);
        }
        // Insert the main time text before the milliseconds span
        currentTimeValue.insertBefore(textNode, milliseconds);
        // Set random milliseconds for visual effect
        milliseconds.textContent = `.${Math.floor(Math.random() * 100).toString().padStart(2, '0')}`;
    }
    
    // Set total elapsed time in compact format
    timerElement.querySelector('.total-elapsed').textContent = formatTime(timer.total_elapsed_time, 'compact');
    
    // Set up button visibility based on status
    const startBtn = timerElement.querySelector('.start-btn');
    const stopBtn = timerElement.querySelector('.stop-btn');
    
    // Hide all buttons initially
    if (startBtn) startBtn.style.display = 'none';
    if (stopBtn) stopBtn.style.display = 'none';
    
    // Show appropriate buttons based on status
    if (timer.status === 'idle' || timer.status === 'stopped' || timer.status === 'paused') {
        if (startBtn) startBtn.style.display = 'block';
    }
    
    if (timer.status === 'running' || timer.status === 'paused') {
        if (stopBtn) stopBtn.style.display = 'block';
    }
    
    // Set up button event listeners
    if (startBtn) {
        startBtn.addEventListener('click', () => {
            // Check if the timer is paused - if so, resume it
            if (timer.status === 'paused') {
                resumeTimer(timer.id);
            } else {
                startTimer(timer.id);
            }
        });
    }
    
    if (stopBtn) {
        stopBtn.addEventListener('click', () => {
            // Just stop the timer
            stopTimer(timer.id);
        });
    }
    
    // Set up delete button
    const deleteBtn = timerElement.querySelector('.delete-timer-btn');
    if (deleteBtn) deleteBtn.addEventListener('click', () => deleteTimer(timer.id));
    
    // Setup interval for running timer
    if (timer.status === 'running') {
        startTimerInterval(timer.id, currentElapsed);
    }
    
    return timerElement;
}

// Function to render timers list with sorting by status
function renderTimers() {
    // Clear existing timers
    const timerElements = timersContainer.querySelectorAll('.timer-card');
    timerElements.forEach(elem => elem.remove());
    
    // Clear all intervals
    clearAllIntervals();
    
    // Show or hide empty state
    if (state.timers.length === 0) {
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    // Sort timers by status: running first, then paused, then the rest
    const sortedTimers = [...state.timers].sort((a, b) => {
        // Define priority order: running = 1, paused = 2, others = 3
        const getPriority = (status) => {
            if (status === 'running') return 1;
            if (status === 'paused') return 2;
            return 3;
        };
        
        const priorityA = getPriority(a.status);
        const priorityB = getPriority(b.status);
        
        // Compare priorities
        return priorityA - priorityB;
    });
    
    // Create and append timer elements
    sortedTimers.forEach(timer => {
        const timerElement = createTimerElement(timer);
        timersContainer.appendChild(timerElement);
    });
}

function updateTimerDisplay(timerId, currentElapsed) {
    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
    if (!timerElement) return;
    
    const timeDisplay = timerElement.querySelector('.current-time .time-value');
    const milliseconds = timeDisplay.querySelector('.milliseconds');
    
    // Update main time value (HH:MM:SS)
    const mainTimeText = formatTime(currentElapsed);
    
    // We need to set the main time value without removing the milliseconds span
    const textNode = document.createTextNode(mainTimeText);
    // Clear any existing content but keep the milliseconds span
    while (timeDisplay.firstChild && timeDisplay.firstChild !== milliseconds) {
        timeDisplay.removeChild(timeDisplay.firstChild);
    }
    // Insert the main time text before the milliseconds span
    timeDisplay.insertBefore(textNode, milliseconds);
    
    // Update milliseconds with random value to simulate counting (changes every second)
    milliseconds.textContent = `.${Math.floor(Math.random() * 100).toString().padStart(2, '0')}`;
}

// Add a function to update milliseconds for running timers
function updateMilliseconds() {
    // Get all running timers
    const runningTimers = document.querySelectorAll('.timer-card.running');
    
    runningTimers.forEach(timer => {
        const milliseconds = timer.querySelector('.milliseconds');
        if (milliseconds) {
            // Generate random milliseconds (00-99)
            const ms = Math.floor(Math.random() * 100).toString().padStart(2, '0');
            milliseconds.textContent = `.${ms}`;
        }
    });
}

// Start an interval to update milliseconds for all running timers
setInterval(updateMilliseconds, 100);  // Update every 100ms for a realistic effect

// Modify the startTimerInterval function to ensure milliseconds are visible
const originalStartTimerInterval = startTimerInterval;
function startTimerInterval(timerId, startElapsed = 0) {
    // Clear existing interval if any
    clearInterval(state.intervals[timerId]);
    
    let elapsed = startElapsed;
    
    // Create new interval
    state.intervals[timerId] = setInterval(() => {
        elapsed++;
        updateTimerDisplay(timerId, elapsed);
    }, 1000);
    
    // Make sure milliseconds are visible for this timer
    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
    if (timerElement) {
        const timeValue = timerElement.querySelector('.current-time .time-value');
        const milliseconds = timeValue.querySelector('.milliseconds');
        if (milliseconds) {
            milliseconds.style.display = 'inline';
        }
    }
}

function clearAllIntervals() {
    for (const timerId in state.intervals) {
        clearInterval(state.intervals[timerId]);
    }
    state.intervals = {};
}

// API Functions
function loadCategories() {
    return makeRequest('get_categories')
        .then(response => {
            if (response.success && response.categories) {
                state.categories = response.categories;
                renderCategories();
            } else {
                console.error('Failed to load categories:', response.message);
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

function loadTimers() {
    const data = {};
    // Only send category_id if filtering by specific category
    if (state.activeCategory > 0) {
        data.category_id = state.activeCategory;
    }
    
    return makeRequest('get_timers', data)
        .then(response => {
            if (response.success && response.timers) {
                state.timers = response.timers;
                renderTimers();
            } else {
                console.error('Failed to load timers:', response.message);
            }
        })
        .catch(error => {
            console.error('Error loading timers:', error);
        });
}

function addTimer(name, categoryId) {
    return makeRequest('add_timer', { name, category_id: categoryId })
        .then(response => {
            if (response.success) {
                // If we have an "All" filter or the timer matches our current category filter
                if (state.activeCategory === 0 || state.activeCategory === parseInt(categoryId)) {
                    // Add the new timer to our state
                    state.timers.unshift(response.timer);
                    renderTimers();
                }
                
                // Update category counters immediately
                renderCategories();
                
                return response;
            } else {
                throw new Error(response.message || 'Failed to add timer');
            }
        });
}

function deleteTimer(timerId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            makeRequest('delete_timer', { timer_id: timerId })
                .then(response => {
                    if (response.success) {
                        // Remove the timer from our state
                        state.timers = state.timers.filter(timer => timer.id !== timerId);
                        
                        // Remove the timer element
                        const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
                        if (timerElement) {
                            timerElement.remove();
                        }
                        
                        // Clear the interval if it exists
                        if (state.intervals[timerId]) {
                            clearInterval(state.intervals[timerId]);
                            delete state.intervals[timerId];
                        }
                        
                        // Show empty state if no timers
                        if (state.timers.length === 0) {
                            emptyState.style.display = 'block';
                        }
                        
                        // Update category counters immediately
                        renderCategories();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Your timer has been deleted.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        return response;
                    } else {
                        throw new Error(response.message || 'Failed to delete timer');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message
                    });
                });
        }
    });
}

// Define the base functions first
function startTimer(timerId) {
    return makeRequest('start_timer', { timer_id: timerId })
        .then(response => {
            if (response.success) {
                // Find the timer in our state
                const timer = state.timers.find(t => t.id === timerId);
                if (timer) {
                    // Update timer status
                    timer.status = 'running';
                    
                    // Get the timer element
                    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
                    if (timerElement) {
                        // Update the timer element
                        timerElement.classList.remove('idle', 'stopped', 'paused');
                        timerElement.classList.add('running');
                        
                        // Update button visibility
                        const startBtn = timerElement.querySelector('.start-btn');
                        const stopBtn = timerElement.querySelector('.stop-btn');
                        if (startBtn) startBtn.style.display = 'none';
                        if (stopBtn) stopBtn.style.display = 'block';
                        
                        // Set up interval for the timer
                        startTimerInterval(timerId, 0);
                        
                        // Ensure milliseconds span exists and is visible
                        const timeDisplay = timerElement.querySelector('.current-time .time-value');
                        if (timeDisplay) {
                            timeDisplay.classList.remove('placeholder-time');
                            let milliseconds = timeDisplay.querySelector('.milliseconds');
                            if (!milliseconds) {
                                milliseconds = document.createElement('span');
                                milliseconds.className = 'milliseconds';
                                milliseconds.textContent = '.00';
                                timeDisplay.appendChild(milliseconds);
                            }
                            milliseconds.style.display = 'inline';
                        }
                    }
                }
                // Update category counters immediately
                renderCategories();
                return response;
            } else {
                throw new Error(response.message || 'Failed to start timer');
            }
        });
}

function stopTimer(timerId) {
    return makeRequest('stop_timer', { timer_id: timerId })
        .then(response => {
            if (response.success) {
                // Find the timer in our state
                const timer = state.timers.find(t => t.id === timerId);
                if (timer) {
                    // Update timer status
                    timer.status = 'stopped';
                    timer.current_elapsed = 0;
                    
                    // Update total elapsed time if returned in the response
                    if (response.total_elapsed_time !== undefined) {
                        timer.total_elapsed_time = response.total_elapsed_time;
                    }
                    
                    // Get the timer element
                    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
                    if (timerElement) {
                        // Update the timer element
                        timerElement.classList.remove('running', 'paused');
                        timerElement.classList.add('stopped');
                        
                        // Update button visibility
                        const startBtn = timerElement.querySelector('.start-btn');
                        const stopBtn = timerElement.querySelector('.stop-btn');
                        if (startBtn) startBtn.style.display = 'block';
                        if (stopBtn) stopBtn.style.display = 'none';
                        
                        // Clear interval
                        if (state.intervals[timerId]) {
                            clearInterval(state.intervals[timerId]);
                            delete state.intervals[timerId];
                        }
                        
                        // Update display with 00:00:00.00 instead of placeholder
                        const timeDisplay = timerElement.querySelector('.current-time .time-value');
                        if (timeDisplay) {
                            const milliseconds = timeDisplay.querySelector('.milliseconds');
                            
                            // Clear current time display and show zeros
                            while (timeDisplay.firstChild && timeDisplay.firstChild !== milliseconds) {
                                timeDisplay.removeChild(timeDisplay.firstChild);
                            }
                            
                            const textNode = document.createTextNode('00:00:00');
                            timeDisplay.insertBefore(textNode, milliseconds);
                            timeDisplay.classList.remove('placeholder-time');
                            
                            // Show milliseconds as .00
                            if (milliseconds) {
                                milliseconds.textContent = '.00';
                                milliseconds.style.display = 'inline';
                            }
                        }
                        
                        // Update total time display
                        const totalElapsed = timerElement.querySelector('.total-elapsed');
                        if (totalElapsed && response.total_elapsed_time !== undefined) {
                            totalElapsed.textContent = formatTime(response.total_elapsed_time, 'compact');
                        }
                    }
                }
                // Update category counters immediately
                renderCategories();
                return response;
            } else {
                throw new Error(response.message || 'Failed to stop timer');
            }
        });
}

// Add pauseTimer function (but without the pause button UI, just for the API functionality)
function pauseTimer(timerId) {
    return makeRequest('pause_timer', { timer_id: timerId })
        .then(response => {
            if (response.success) {
                // Find the timer in our state
                const timer = state.timers.find(t => t.id === timerId);
                if (timer) {
                    // Update timer status
                    timer.status = 'paused';
                    
                    // Get the timer element
                    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
                    if (timerElement) {
                        // Update the timer element
                        timerElement.classList.remove('running');
                        timerElement.classList.add('paused');
                        
                        // Update button visibility - when paused, we only show the start button
                        const startBtn = timerElement.querySelector('.start-btn');
                        const stopBtn = timerElement.querySelector('.stop-btn');
                        if (startBtn) startBtn.style.display = 'block';
                        if (stopBtn) stopBtn.style.display = 'block';
                        
                        // Clear interval
                        if (state.intervals[timerId]) {
                            clearInterval(state.intervals[timerId]);
                            delete state.intervals[timerId];
                        }
                    }
                }
                return response;
            } else {
                throw new Error(response.message || 'Failed to pause timer');
            }
        });
}

// Add resumeTimer function
function resumeTimer(timerId) {
    return makeRequest('resume_timer', { timer_id: timerId })
        .then(response => {
            if (response.success) {
                // Find the timer in our state
                const timer = state.timers.find(t => t.id === timerId);
                if (timer) {
                    // Update timer status
                    timer.status = 'running';
                    
                    // Get the timer element
                    const timerElement = document.querySelector(`.timer-card[data-timer-id="${timerId}"]`);
                    if (timerElement) {
                        // Update the timer element
                        timerElement.classList.remove('paused');
                        timerElement.classList.add('running');
                        
                        // Update button visibility
                        const startBtn = timerElement.querySelector('.start-btn');
                        const stopBtn = timerElement.querySelector('.stop-btn');
                        if (startBtn) startBtn.style.display = 'none';
                        if (stopBtn) stopBtn.style.display = 'block';
                        
                        // Start the timer interval with the current elapsed time
                        let currentElapsed = 0;
                        if (timer.current_elapsed !== undefined) {
                            currentElapsed = timer.current_elapsed;
                        }
                        startTimerInterval(timerId, currentElapsed);
                    }
                }
                
                // Update category counters immediately
                renderCategories();
                
                return response;
            } else {
                throw new Error(response.message || 'Failed to resume timer');
            }
        });
}

// Updated pollTimers function to update counters immediately
function pollTimers() {
    const data = {};
    // Only send category_id if filtering by specific category
    if (state.activeCategory > 0) {
        data.category_id = state.activeCategory;
    }
    
    makeRequest('get_timers', data)
        .then(response => {
            if (response.success && response.timers) {
                let hasChanges = false;
                
                // Find changes in timer state
                response.timers.forEach(serverTimer => {
                    const clientTimer = state.timers.find(t => t.id === serverTimer.id);
                    
                    // If the timer doesn't exist locally or has a different status, update the UI
                    if (!clientTimer || clientTimer.status !== serverTimer.status) {
                        hasChanges = true;
                        // Update or add the timer in our state
                        if (clientTimer) {
                            Object.assign(clientTimer, serverTimer);
                        } else {
                            state.timers.push(serverTimer);
                        }
                        
                        // Get the timer element if it exists
                        let timerElement = document.querySelector(`.timer-card[data-timer-id="${serverTimer.id}"]`);
                        
                        // If the element exists, update it; otherwise, we'll create it
                        if (timerElement) {
                            // Clear any existing interval
                            if (state.intervals[serverTimer.id]) {
                                clearInterval(state.intervals[serverTimer.id]);
                                delete state.intervals[serverTimer.id];
                            }
                            
                            // Remove the element to replace it
                            timerElement.remove();
                        }
                        
                        // Create and append the updated timer element
                        const newTimerElement = createTimerElement(serverTimer);
                        
                        // Find where to insert the element (to maintain order)
                        const index = response.timers.findIndex(t => t.id === serverTimer.id);
                        const nextTimer = response.timers[index - 1]; // Next timer would have a higher index in the response but lower in the DOM
                        
                        if (nextTimer) {
                            const nextElement = document.querySelector(`.timer-card[data-timer-id="${nextTimer.id}"]`);
                            if (nextElement) {
                                timersContainer.insertBefore(newTimerElement, nextElement);
                            } else {
                                timersContainer.appendChild(newTimerElement);
                            }
                        } else {
                            timersContainer.appendChild(newTimerElement);
                        }
                    }
                    // Update total_elapsed_time if it changed
                    else if (clientTimer && clientTimer.total_elapsed_time !== serverTimer.total_elapsed_time) {
                        hasChanges = true;
                        clientTimer.total_elapsed_time = serverTimer.total_elapsed_time;
                        
                        const timerElement = document.querySelector(`.timer-card[data-timer-id="${serverTimer.id}"]`);
                        if (timerElement) {
                            timerElement.querySelector('.total-elapsed').textContent = formatTime(serverTimer.total_elapsed_time, 'compact');
                        }
                    }
                });
                
                // Check for deleted timers
                const timersBefore = state.timers.length;
                state.timers = state.timers.filter(clientTimer => {
                    const serverTimer = response.timers.find(t => t.id === clientTimer.id);
                    
                    if (!serverTimer) {
                        hasChanges = true;
                        // Timer was deleted on the server
                        const timerElement = document.querySelector(`.timer-card[data-timer-id="${clientTimer.id}"]`);
                        if (timerElement) {
                            timerElement.remove();
                        }
                        
                        // Clear the interval if it exists
                        if (state.intervals[clientTimer.id]) {
                            clearInterval(state.intervals[clientTimer.id]);
                            delete state.intervals[clientTimer.id];
                        }
                        
                        return false;
                    }
                    
                    return true;
                });
                
                if (timersBefore !== state.timers.length) {
                    hasChanges = true;
                }
                
                // Update category counters immediately if changes were detected
                if (hasChanges) {
                    renderCategories();
                    
                    // Show empty state if no timers
                    if (state.timers.length === 0) {
                        emptyState.style.display = 'block';
                    } else {
                        emptyState.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error polling timers:', error);
        });
}

/**
 * User preference related functions
 */

// Load user preferences from server
async function loadUserPreferences() {
    try {
        const response = await makeRequest('get_preferences');
        if (response.success) {
            // Store preferences in state
            if (response.preferences && response.preferences.font) {
                state.preferences.databaseFont = response.preferences.font;
            }
            applyUserPreferences(response.preferences);
            return response.preferences;
        }
        return {};
    } catch (error) {
        console.error('Failed to load user preferences:', error);
        return {};
    }
}

// Apply preferences to the UI
function applyUserPreferences(preferences) {
    if (preferences.font) {
        // Apply font from database
        applyFont(preferences.font);
        // Also update the selector
        if (fontSelector) {
            fontSelector.value = preferences.font;
        }
    }
    // Add more preference applications as needed
}

// Save a user preference
async function saveUserPreference(preferenceName, preferenceValue) {
    try {
        const response = await makeRequest('save_preference', {
            preference_name: preferenceName,
            preference_value: preferenceValue
        });
        return response.success;
    } catch (error) {
        console.error(`Failed to save preference ${preferenceName}:`, error);
        return false;
    }
}

// Initialize the app
function init() {
    // Set refresh detection
    window.addEventListener('beforeunload', function() {
        window.isRefreshing = true;
    });
    
    // Set up font selector change event
    fontSelector.addEventListener('change', function() {
        const selectedFont = this.value;
        applyFont(selectedFont);
        saveFontPreference(selectedFont);
    });
    
    // Set up add timer button click event
    addTimerBtn.addEventListener('click', showModal);
    
    // Check if we're returning from a refresh
    const isFromRefresh = checkForRefresh();
    
    // First, load user preferences from database
    loadUserPreferences()
        .then(preferences => {
            // Apply database font if available
            if (preferences && preferences.font) {
                state.preferences.font = preferences.font;
                state.preferences.databaseFont = preferences.font;
                fontSelector.value = preferences.font;
                applyFont(preferences.font);
            } else {
                // Load font preference from localStorage as fallback
                loadFontPreference();
            }
            
            // Now load categories
            return loadCategories();
        })
        .then(() => {
            // If coming from refresh, load saved category (but don't reset)
            loadCategorySelection();
            // Load timers after categories are loaded
            return loadTimers();
        })
        .then(() => {
            // Start polling
            setInterval(pollTimers, CONFIG.pollingInterval);
        })
        .catch(error => {
            console.error('Error initializing app:', error);
            Swal.fire({
                icon: 'error',
                title: 'Initialization Error',
                text: 'Failed to load application data: ' + error.message
            });
        });
}

// Start the app when DOM is loaded
document.addEventListener('DOMContentLoaded', init); 