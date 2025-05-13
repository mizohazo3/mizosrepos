<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Search</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="search.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- Added grid layout styles for search results -->
    <style>
        #timer-list {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Fixed 3 columns per row */
            gap: 20px;
        }
        .timer-item {
            margin-bottom: 0 !important;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 768px) {
            #timer-list {
                grid-template-columns: 1fr; /* Single column on mobile */
            }
        }
        @media (min-width: 769px) and (max-width: 1200px) {
            #timer-list {
                grid-template-columns: repeat(2, 1fr); /* 2 columns on tablets */
            }
        }
    </style>
    <!-- Completely disable timer_page.js functionality for search page -->
    <script>
        // Set multiple flags to ensure search page behavior is preserved
        window.isSearchPage = true;
        window.disableTimerPagePolling = true;
        window.preventPageReload = true;
        
        // Override any reload functions
        if (typeof window.location.reload !== 'undefined') {
            const originalReload = window.location.reload;
            window.location.reload = function() {
                console.log('Page reload prevented on search page');
                return false;
            };
        }
        
        // Store and restore search results on tab visibility change
        document.addEventListener('DOMContentLoaded', function() {
            // Get last search query from sessionStorage if exists
            const lastQuery = sessionStorage.getItem('lastSearchQuery');
            const lastResults = sessionStorage.getItem('lastSearchResults');
            
            // Function to handle visibility change
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('Tab became visible - restoring search state');
                    const searchInput = document.getElementById('search-input');
                    const lastQuery = sessionStorage.getItem('lastSearchQuery');
                    if (lastQuery && searchInput) {
                        searchInput.value = lastQuery;
                        
                        // Restore search results from session storage
                        const lastResults = sessionStorage.getItem('lastSearchResults');
                        if (lastResults) {
                            try {
                                const data = JSON.parse(lastResults);
                                displaySearchResults(data);
                            } catch(e) {
                                console.error('Error restoring search results:', e);
                            }
                        }
                    }
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <?php
        include_once 'timezone_config.php';
        include 'includes/header_nav.php';
        ?>

        <section class="search-section">
            <div class="search-container">
                <div id="search-form">
                    <input type="text" id="search-input" class="search-input" placeholder="Search timers by name or ID...">
                    <button id="search-clear" class="search-clear-btn">✖</button>
                </div>
            </div>

            <div id="search-results">
                <div id="search-placeholder" class="search-placeholder">
                    <div class="search-icon">🔍</div>
                    <p>Use the search box above to find timers</p>
                </div>
                <ul id="timer-list" style="display: none;">
                    <!-- Timer items will be added here by JavaScript -->
                </ul>
            </div>
        </section>
    </div>

    <!-- Timer Item Template -->
    <template id="timer-template">
        <li class="timer-item" data-timer-id="">
            <div class="timer-name-header">
                <span class="timer-id">#ID</span>
                <span class="timer-name">Timer Name</span>
                <span class="pin-icon" title="Pin Timer">📌</span>
            </div>
            <div class="timer-display-wrapper">
                <div class="timer-display">
                    <span class="current-time">00:00:00</span>.<span class="milliseconds">00</span>
                </div>
                <div class="accumulated-time">Total: 0.00h</div>
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
            </div>
        </li>
    </template>

    <div id="notification-container"></div>
    <!-- Load required scripts -->
    <script src="./js/common.js"></script>
    <script src="./js/modal.js"></script>
    <!-- Load the independent stop-all button script -->
    <script src="./js/stop_all_button.js"></script>
    
    <!-- Custom search script with persistent results -->
    <script>
        // Declare displaySearchResults in global scope to be accessible by other scripts
        window.displaySearchResults = function(data) {
            const searchPlaceholder = document.getElementById('search-placeholder');
            const timerList = document.getElementById('timer-list');
            
            // Hide placeholder, show results
            if (searchPlaceholder) searchPlaceholder.style.display = 'none';
            if (timerList) timerList.style.display = 'grid';  // Changed from 'block' to 'grid' to use the grid layout
            
            // Clear previous results
            if (timerList) timerList.innerHTML = '';
            
            if (!data || !data.timers || data.timers.length === 0) {
                // No results - show a message in the timer list
                if (searchPlaceholder) {
                    searchPlaceholder.style.display = 'flex';
                    searchPlaceholder.innerHTML = '<div class="search-icon">🔍</div><p>No timers found matching your search</p>';
                }
                return;
            }
            
            // Create and append timer elements
            if (timerList && data.timers) {
                data.timers.forEach(timer => {
                    const timerElement = createTimerElement(timer);
                    if (timerElement) timerList.appendChild(timerElement);
                });
            }
        };
        
        // Create timer element from template
        function createTimerElement(timer) {
            const timerTemplate = document.getElementById('timer-template');
            if (!timerTemplate || !timerTemplate.content) {
                // Fallback if template not supported
                const fallback = document.createElement('li');
                fallback.textContent = timer.name || 'Unnamed Timer';
                return fallback;
            }
            
            // Clone template
            const element = document.importNode(timerTemplate.content, true).querySelector('.timer-item');
            
            // Set timer data
            element.dataset.timerId = timer.id;
            element.dataset.timerName = (timer.name || 'Unnamed Timer').toLowerCase();
            
            // Set text content
            element.querySelector('.timer-id').textContent = `#${timer.id}`;
            element.querySelector('.timer-name').textContent = timer.name || 'Unnamed Timer';
            
            // Set current time display
            const timeDisplay = element.querySelector('.current-time');
            const milliseconds = element.querySelector('.milliseconds');
            
            if (timeDisplay && milliseconds) {
                const totalSeconds = parseFloat(timer.accumulated_seconds) || 0;
                const timeObj = formatTimeWithMilliseconds ? 
                    formatTimeWithMilliseconds(totalSeconds) : 
                    { hours: '00', minutes: '00', seconds: '00', milliseconds: '00' };
                    
                timeDisplay.textContent = `${timeObj.hours}:${timeObj.minutes}:${timeObj.seconds}`;
                milliseconds.textContent = timeObj.milliseconds;
            }
            
            // Set accumulated time
            const accTimeDisplay = element.querySelector('.accumulated-time');
            if (accTimeDisplay) {
                const totalHours = ((parseFloat(timer.accumulated_seconds) || 0) / 3600).toFixed(2);
                accTimeDisplay.textContent = `Total: ${totalHours}h`;
            }
            
            // Set level and rank
            const levelDisplay = element.querySelector('.timer-level');
            const rankDisplay = element.querySelector('.timer-rank');
            if (levelDisplay) levelDisplay.textContent = timer.current_level || '1';
            if (rankDisplay) rankDisplay.textContent = timer.rank_name || 'Novice';
            
            // Set reward rate
            const rateDisplay = element.querySelector('.timer-reward-rate');
            if (rateDisplay) {
                const rate = parseFloat(timer.hourly_rate || 0).toFixed(2);
                rateDisplay.textContent = `$${rate}/hr`;
            }
            
            // Set progress bar
            const progressFill = element.querySelector('.timer-progress-fill');
            const progressText = element.querySelector('.timer-progress-text');
            if (progressFill && progressText && timer.progress_percent !== undefined) {
                const percent = Math.min(100, Math.max(0, parseFloat(timer.progress_percent) || 0));
                progressFill.style.width = `${percent}%`;
                progressText.textContent = `${Math.round(percent)}%`;
            }
            
            // Add event listeners
            const nameHeader = element.querySelector('.timer-name-header');
            if (nameHeader) {
                nameHeader.addEventListener('click', (e) => {
                    e.preventDefault(); // Prevent default behavior
                    
                    // Open timer in new tab
                    window.open(`index.php?timer_id=${timer.id}`, '_blank');
                    
                    return false; // Prevent event bubbling
                });
            }
            
            // Add pin functionality
            const pinIcon = element.querySelector('.pin-icon');
            if (pinIcon) {
                pinIcon.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    try {
                        const response = await fetch('api/timer_action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'toggle_pin', id: timer.id })
                        });
                        
                        const data = await response.json();
                        if (data.status === 'success') {
                            if (typeof displayNotification === 'function') {
                                displayNotification(`Timer ${data.is_pinned ? 'pinned' : 'unpinned'}`, 'success');
                            }
                            
                            // Toggle visual state
                            pinIcon.classList.toggle('pinned', data.is_pinned);
                        }
                    } catch (error) {
                        console.error('Error toggling pin:', error);
                    }
                });
                
                // Set initial pin state
                pinIcon.classList.toggle('pinned', timer.is_pinned === '1');
            }
            
            return element;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // DOM elements
            const searchInput = document.getElementById('search-input');
            const searchClear = document.getElementById('search-clear');
            const timerList = document.getElementById('timer-list');
            const searchPlaceholder = document.getElementById('search-placeholder');
            
            // Initialize by restoring previous search if exists
            const lastQuery = sessionStorage.getItem('lastSearchQuery');
            if (lastQuery && searchInput) {
                searchInput.value = lastQuery;
                
                // Restore search results from session storage
                const lastResults = sessionStorage.getItem('lastSearchResults');
                if (lastResults) {
                    try {
                        const data = JSON.parse(lastResults);
                        displaySearchResults(data);
                    } catch(e) {
                        console.error('Error restoring search results:', e);
                    }
                }
            }
            
            // Initialize search functionality
            function initSearch() {
                if (!searchInput || !timerList) return;
                
                // Add event listeners
                searchInput.addEventListener('input', handleSearch);
                
                // Add keyboard support
                searchInput.addEventListener('keyup', function(event) {
                    // Trigger search on Enter key
                    if (event.key === 'Enter') {
                        handleSearch();
                    }
                    
                    // Apply client-side filtering on each keypress for immediate feedback
                    // This gives a responsive feel while the server request is processing
                    clientSideFilter(searchInput.value.trim());
                });
                
                if (searchClear) {
                    searchClear.addEventListener('click', clearSearch);
                }
                
                // Focus search input on page load
                setTimeout(() => searchInput.focus(), 100);
            }
            
            // Handle search input changes
            async function handleSearch() {
                const query = searchInput.value.trim();
                
                // Store the query in sessionStorage
                if (query) {
                    sessionStorage.setItem('lastSearchQuery', query);
                } else {
                    sessionStorage.removeItem('lastSearchQuery');
                    sessionStorage.removeItem('lastSearchResults');
                }
                
                // Show/hide clear button
                if (searchClear) {
                    searchClear.style.display = query ? 'block' : 'none';
                }
                
                // Show placeholder if query is empty
                if (!query) {
                    timerList.style.display = 'none';
                    searchPlaceholder.style.display = 'flex';
                    return;
                }
                
                try {
                    // Show a loading indicator in the search results area
                    searchPlaceholder.style.display = 'flex';
                    searchPlaceholder.innerHTML = '<div class="search-icon">⏳</div><p>Searching...</p>';
                    
                    // Fetch search results
                    const response = await fetch(`api/search_timers.php?query=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    // Log results for debugging
                    console.log('Search query:', query);
                    console.log('Search results:', data);
                    
                    // Store results in sessionStorage
                    if (data.status === 'success') {
                        sessionStorage.setItem('lastSearchResults', JSON.stringify(data));
                    }
                    
                    // Display results
                    displaySearchResults(data);
                } catch (error) {
                    console.error('Search error:', error);
                    // Show a notification if displayNotification function exists
                    if (typeof displayNotification === 'function') {
                        displayNotification('Error searching timers', 'error');
                    }
                    
                    // Show error in search results area
                    searchPlaceholder.style.display = 'flex';
                    searchPlaceholder.innerHTML = '<div class="search-icon">❌</div><p>Error searching timers</p>';
                }
            }
            
            // Clear search input and results
            function clearSearch() {
                searchInput.value = '';
                searchInput.focus();
                
                if (searchClear) {
                    searchClear.style.display = 'none';
                }
                
                timerList.style.display = 'none';
                searchPlaceholder.style.display = 'flex';
                
                // Clear sessionStorage
                sessionStorage.removeItem('lastSearchQuery');
                sessionStorage.removeItem('lastSearchResults');
            }
            
            // Initialize search
            initSearch();
            
            /**
             * Perform client-side filtering on existing timer elements
             * This serves as a backup in case server-side filtering doesn't work
             */
            function clientSideFilter(query) {
                if (!query || !timerList) return;
                
                // Get all timer items
                const timerItems = timerList.querySelectorAll('.timer-item');
                if (!timerItems.length) return;
                
                // Split query into words and remove empty strings
                const queryWords = query.toLowerCase().split(/\s+/).filter(word => word.trim() !== '');
                
                // No words to filter by
                if (!queryWords.length) {
                    timerItems.forEach(item => {
                        item.style.display = '';
                    });
                    return;
                }
                
                // Check each timer against all words (must match ALL words)
                timerItems.forEach(item => {
                    const timerName = item.dataset.timerName || '';
                    const timerId = item.dataset.timerId || '';
                    
                    // Check if timer matches all query words
                    const matchesAll = queryWords.every(word => {
                        return timerName.includes(word) || timerId === word;
                    });
                    
                    // Show/hide based on match result
                    item.style.display = matchesAll ? '' : 'none';
                });
                
                // Check if we have any visible results
                const visibleItems = timerList.querySelectorAll('.timer-item[style="display: none;"]');
                
                // Show no results message if all items are hidden
                if (visibleItems.length === timerItems.length) {
                    searchPlaceholder.style.display = 'flex';
                    searchPlaceholder.innerHTML = '<div class="search-icon">🔍</div><p>No timers found matching your search</p>';
                    timerList.style.display = 'none';
                } else {
                    searchPlaceholder.style.display = 'none';
                    timerList.style.display = 'grid';
                }
            }
            
            // Block any potential reload attempts from other scripts
            window.stopPolling = window.stopPolling || function() {
                console.log('Polling stopped on search page');
            };
            window.stopPolling();
            
            // Override any interval that might try to reload data
            const originalSetInterval = window.setInterval;
            window.setInterval = function(callback, delay, ...args) {
                // Skip any intervals with short polling times (likely data refresh)
                if (delay < 10000 && typeof callback === 'function') {
                    console.log('Blocking potential polling interval on search page');
                    return null;
                }
                return originalSetInterval(callback, delay, ...args);
            };
        });
    </script>
</body>
</html>