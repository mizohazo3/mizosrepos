/**
 * Search functionality for the timer search page
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const searchInput = document.getElementById('search-input');
    const searchClear = document.getElementById('search-clear');
    const timerList = document.getElementById('timer-list');
    const searchPlaceholder = document.getElementById('search-placeholder');
    const timerTemplate = document.getElementById('timer-template');
    
    // Initialize
    initSearch();
    
    /**
     * Initialize search functionality
     */
    function initSearch() {
        if (!searchInput || !timerList) return;
        
        // Add event listeners
        searchInput.addEventListener('input', handleSearch);
        
        if (searchClear) {
            searchClear.addEventListener('click', clearSearch);
        }
        
        // Focus search input on page load
        setTimeout(() => searchInput.focus(), 100);
    }
    
    /**
     * Handle search input changes
     */
    async function handleSearch() {
        const query = searchInput.value.trim();
        
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
    
    /**
     * Clear search input and results
     */
    function clearSearch() {
        searchInput.value = '';
        searchInput.focus();
        
        if (searchClear) {
            searchClear.style.display = 'none';
        }
        
        timerList.style.display = 'none';
        searchPlaceholder.style.display = 'flex';
    }
    
    /**
     * Display search results
     */
    function displaySearchResults(data) {
        // Hide placeholder, show results
        searchPlaceholder.style.display = 'none';
        timerList.style.display = 'grid';  // Changed from 'block' to 'grid' to use the grid layout
        
        // Clear previous results
        timerList.innerHTML = '';
        
        if (!data || !data.timers || data.timers.length === 0) {
            // No results - show a message in the timer list
            searchPlaceholder.style.display = 'flex';
            searchPlaceholder.innerHTML = '<div class="search-icon">🔍</div><p>No timers found matching your search</p>';
            return;
        }
        
        // Create and append timer elements
        data.timers.forEach(timer => {
            const timerElement = createTimerElement(timer);
            timerList.appendChild(timerElement);
        });
    }
    
    /**
     * Create timer element from template
     */
    function createTimerElement(timer) {
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
                
                // Open timer in new tab instead of redirecting current page
                window.open(`index.php?timer_id=${timer.id}`, '_blank');
                
                // Alternatively, if you want to show timer details in a modal or sidebar on the search page
                // showTimerDetails(timer.id);
                
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
}); 