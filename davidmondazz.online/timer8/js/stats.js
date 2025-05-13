document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';
    
    // --- DOM Elements ---
    const totalHoursEl = document.getElementById('total-hours');
    const totalEarnedEl = document.getElementById('total-earned');
    const totalSpentEl = document.getElementById('total-spent');
    const avgHoursEl = document.getElementById('avg-hours');
    const periodButtons = document.querySelectorAll('.period-selector button');
    
    // Chart elements
    const earningsChartEl = document.getElementById('earnings-chart');
    const earningsLoadingEl = document.getElementById('earnings-loading');
    const earningsEmptyEl = document.getElementById('earnings-empty');
    const earningsContainerEl = document.getElementById('earnings-chart-container');
    
    const spendingChartEl = document.getElementById('spending-chart');
    const spendingLoadingEl = document.getElementById('spending-loading');
    const spendingEmptyEl = document.getElementById('spending-empty');
    const spendingContainerEl = document.getElementById('spending-chart-container');
    
    const hoursChartEl = document.getElementById('hours-chart');
    const hoursLoadingEl = document.getElementById('hours-loading');
    const hoursEmptyEl = document.getElementById('hours-empty');
    const hoursContainerEl = document.getElementById('hours-chart-container');

    // Tooltip Element
    const chartTooltipEl = document.getElementById('chart-tooltip');
    
    // --- State ---
    let currentPeriod = 'day'; // Default to last 24 hours
    
    // --- Utility Functions ---
    
    // Show notification if the notifications.js is loaded
    function showNotification(message, type = 'info') {
        if (window.showNotification) {
            window.showNotification(message, type);
        }
    }

    // Function to show the tooltip above a specific element
    function showChartTooltip(content, barElement) {
        if (!chartTooltipEl || !barElement) return;

        chartTooltipEl.innerHTML = content;

        // Calculate Position
        const barRect = barElement.getBoundingClientRect();
        const scrollX = window.scrollX || window.pageXOffset;
        const scrollY = window.scrollY || window.pageYOffset;

        // Calculate the horizontal center of the bar
        const barCenterX = barRect.left + scrollX + (barRect.width / 2);

        // Calculate the top of the bar
        const barTop = barRect.top + scrollY;

        // Temporarily display tooltip off-screen to measure its dimensions
        chartTooltipEl.style.visibility = 'hidden';
        chartTooltipEl.style.display = 'block';
        const tooltipHeight = chartTooltipEl.offsetHeight;

        // Calculate desired top position (above the bar with a small gap)
        const tooltipTop = barTop - tooltipHeight - 7;

        // Set final position
        chartTooltipEl.style.left = `${barCenterX}px`;
        chartTooltipEl.style.top = `${tooltipTop}px`;

        // Make it visible at the calculated position
        chartTooltipEl.style.visibility = 'visible';
    }

    // Function to hide the tooltip
    function hideChartTooltip() {
        if (!chartTooltipEl) return;
        chartTooltipEl.style.display = 'none';
    }
    
    // Format currency values
    function formatCurrency(amount, type = 'default') {
        const num = parseFloat(amount);
        if (isNaN(num)) {
            return '$--.--';
        }
        
        if (type === 'earned') {
            // Money earned in green with plus sign
            return `<span style="color: #4CAF50">+$${num.toFixed(2)}</span>`;
        } else if (type === 'spent') {
            // Money spent in red with minus sign
            return `<span style="color: #E53935">-$${Math.abs(num).toFixed(2)}</span>`;
        } else {
            // Default formatting without color
            return '$' + num.toFixed(2);
        }
    }
    
    // Format hour values
    function formatHours(hours) {
        const num = parseFloat(hours);
        if (isNaN(num)) {
            return '0h';
        }
        return num.toFixed(2) + 'h';
    }
    
    // Format date for display
    function formatDate(dateStr) {
        try {
            // Check if it's likely a Year-Month format (e.g., "2025-04")
            if (/^\d{4}-\d{2}$/.test(dateStr)) {
                // Create a date object (defaults to the 1st of the month)
                const date = new Date(dateStr + '-01T00:00:00'); // Add day/time for parsing
                if (isNaN(date.getTime())) { // Check if date is valid
                    return dateStr; // Return original string if parsing failed
                }
                // Format as "Mon YYYY" (e.g., "Apr 2025")
                return date.toLocaleDateString(undefined, {
                    month: 'short',
                    year: 'numeric',
                    timeZone: 'UTC' // Specify timezone if dates are UTC based
                });
            }
            // Check if it includes time (hourly format)
            else if (dateStr.includes(':')) {
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                // For hourly data (24-hour view), display hour
                return date.toLocaleTimeString(undefined, {
                    hour: 'numeric',
                    hour12: true
                });
            }
            // Otherwise, assume daily format
            else {
                const date = new Date(dateStr + 'T00:00:00');
                if (isNaN(date.getTime())) return dateStr;
                // For daily data, show month and day
                return date.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric',
                    timeZone: 'UTC'
                });
            }
        } catch (e) {
            return dateStr;
        }
    }
    
    // Create a simple bar chart
    function createBarChart(containerId, containerEl, data, valueKey, maxValue, barColor) {
        // Clear existing chart
        const chartEl = document.getElementById(containerId);
        if (!chartEl) {
            return false;
        }
        chartEl.innerHTML = '';

        // Check if we have any non-zero values
        const hasData = data.some(item => item[valueKey] > 0);

        if (!hasData) {
            return false;
        }

        // Find maximum value if not provided
        const chartMax = maxValue || Math.max(...data.map(item => item[valueKey]));

        // Add day-view class to container if needed
        if (currentPeriod === 'day') {
            containerEl.classList.add('day-view');
        } else {
            containerEl.classList.remove('day-view');
        }
        
        // Get all values for the specified key
        const values = data.map(item => parseFloat(item[valueKey]) || 0);

        // Create bars for each data point
        data.forEach((item, index) => {
            const value = parseFloat(item[valueKey]) || 0;

            let heightPercent = 0;
            let barElementHeight = '1px';

            if (value > 0 && chartMax > 0) {
                 heightPercent = (value / chartMax) * 100;
                 if (heightPercent < 5) heightPercent = 5;
                 barElementHeight = `${heightPercent}%`;
            } else if (value === 0) {
                 barElementHeight = '1px';
            } else {
                 const absValue = Math.abs(value);
                 if (absValue > 0 && chartMax > 0) {
                     heightPercent = (absValue / chartMax) * 100;
                     if (heightPercent < 5) heightPercent = 5;
                     barElementHeight = `${heightPercent}%`;
                 } else {
                      barElementHeight = '1px';
                 }
            }

            const barContainer = document.createElement('div');
            barContainer.className = 'bar-container';

            const bar = document.createElement('div');
            bar.className = 'bar';
            bar.style.height = barElementHeight;

            // Set color based on the value type
            if (barColor) {
                bar.style.backgroundColor = barColor;
            } else if (valueKey === 'total_earned') {
                bar.style.backgroundColor = '#4CAF50';
            } else if (valueKey === 'total_spent') {
                bar.style.backgroundColor = '#E53935';
            } else if (valueKey === 'total_hours') {
                 bar.style.backgroundColor = 'var(--secondary-color)';
            }

            // Add tooltip content
            let formattedValueForTitle = '';
            if (valueKey === 'total_earned') {
                formattedValueForTitle = value > 0 ? formatCurrency(value, 'earned') : '$0.0000';
                bar.title = value > 0 ? `+$${value.toFixed(2)}` : '$0.00';
            } else if (valueKey === 'total_hours') {
                formattedValueForTitle = formatHours(value);
                bar.title = formattedValueForTitle;
            } else if (valueKey === 'total_spent') {
                formattedValueForTitle = formatCurrency(value, 'spent');
                bar.title = value < 0 ? `-$${Math.abs(value).toFixed(2)}` : (value > 0 ? `+$${value.toFixed(2)}` : '$0.00');
            }
            bar.dataset.tooltipContent = formattedValueForTitle;

            const label = document.createElement('div');
            label.className = 'label';
            label.textContent = formatDate(item.date);

            barContainer.appendChild(bar);
            barContainer.appendChild(label);
            chartEl.appendChild(barContainer);
        });

        setTimeout(() => {
            const scrollContainer = chartEl.parentElement;
            if (scrollContainer) {
                scrollContainer.scrollLeft = scrollContainer.scrollWidth;
            }
        }, 100);

        return true;
    }
    
    // --- API Call Function ---
    async function apiCall(endpoint, params = {}) {
        let url = API_BASE_URL + endpoint;
        
        if (Object.keys(params).length > 0) {
            url += '?';
            const queryParams = [];
            for (const key in params) {
                queryParams.push(`${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`);
            }
            url += queryParams.join('&');
        }
        
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                throw new Error('Invalid JSON response');
            }
        } catch (error) {
            showNotification('Failed to fetch statistics data: ' + error.message, 'error');
            return { status: 'error', message: error.message };
        }
    }
    
    // --- UI Update Functions ---
    function updateSummaryUI(data) {
        if (!data || data.status !== 'success') {
            totalHoursEl.textContent = 'Error loading';
            totalEarnedEl.textContent = 'Error loading';
            totalSpentEl.textContent = 'Error loading';
            avgHoursEl.textContent = 'Error loading';
            return;
        }
        
        const totals = data.totals;
        
        totalHoursEl.textContent = formatHours(totals.total_hours);
        totalEarnedEl.innerHTML = formatCurrency(totals.total_earned, 'earned');
        totalSpentEl.innerHTML = formatCurrency(totals.total_spent, 'spent');
        avgHoursEl.textContent = formatHours(totals.avg_hours);
    }
    
    // Update the charts with daily data
    function updateChartsUI(data) {
        hideChartTooltip();

        if (!data || data.status !== 'success' || !data.daily_data || data.daily_data.length === 0) {
            earningsLoadingEl.style.display = 'none';
            earningsChartEl.style.display = 'none';
            earningsEmptyEl.style.display = 'block';
            
            spendingLoadingEl.style.display = 'none';
            spendingChartEl.style.display = 'none';
            spendingEmptyEl.style.display = 'block';
            
            hoursLoadingEl.style.display = 'none';
            hoursChartEl.style.display = 'none';
            hoursEmptyEl.style.display = 'block';
            return;
        }
        
        const hasEarningsData = createBarChart('earnings-chart', earningsContainerEl, data.daily_data, 'total_earned');
        earningsLoadingEl.style.display = 'none';
        earningsChartEl.style.display = hasEarningsData ? 'flex' : 'none';
        earningsEmptyEl.style.display = hasEarningsData ? 'none' : 'block';
        
        const hasSpendingData = createBarChart('spending-chart', spendingContainerEl, data.daily_data, 'total_spent');
        spendingLoadingEl.style.display = 'none';
        spendingChartEl.style.display = hasSpendingData ? 'flex' : 'none';
        spendingEmptyEl.style.display = hasSpendingData ? 'none' : 'block';
        
        const hasHoursData = createBarChart('hours-chart', hoursContainerEl, data.daily_data, 'total_hours', null, 'var(--secondary-color)');
        hoursLoadingEl.style.display = 'none';
        hoursChartEl.style.display = hasHoursData ? 'flex' : 'none';
        hoursEmptyEl.style.display = hasHoursData ? 'none' : 'block';
    }
    
    // --- Data Fetching ---
    async function fetchStatsData(period = 'day') {
        totalHoursEl.textContent = 'Loading...';
        totalEarnedEl.textContent = 'Loading...';
        totalSpentEl.textContent = 'Loading...';
        avgHoursEl.textContent = 'Loading...';
        
        earningsLoadingEl.style.display = 'flex';
        earningsChartEl.style.display = 'none';
        earningsEmptyEl.style.display = 'none';
        
        spendingLoadingEl.style.display = 'flex';
        spendingChartEl.style.display = 'none';
        spendingEmptyEl.style.display = 'none';
        
        hoursLoadingEl.style.display = 'flex';
        hoursChartEl.style.display = 'none';
        hoursEmptyEl.style.display = 'none';
        
        try {
            const data = await apiCall('get_stats_data.php', { period });

            if (data.status === 'error') {
                showNotification(data.message || 'Failed to load statistics', 'error');
            }
            
            updateSummaryUI(data);
            updateChartsUI(data);
        } catch (error) {
            showNotification('Failed to load statistics: ' + error.message, 'error');
            
            totalHoursEl.textContent = 'Error loading';
            totalEarnedEl.textContent = 'Error loading';
            totalSpentEl.textContent = 'Error loading';
            avgHoursEl.textContent = 'Error loading';
            
            earningsLoadingEl.style.display = 'none';
            earningsChartEl.style.display = 'none';
            earningsEmptyEl.style.display = 'block';
            earningsEmptyEl.textContent = 'Error loading chart data.';
            
            spendingLoadingEl.style.display = 'none';
            spendingChartEl.style.display = 'none';
            spendingEmptyEl.style.display = 'block';
            spendingEmptyEl.textContent = 'Error loading chart data.';
            
            hoursLoadingEl.style.display = 'none';
            hoursChartEl.style.display = 'none';
            hoursEmptyEl.style.display = 'block';
            hoursEmptyEl.textContent = 'Error loading chart data.';
        }
    }
    
    // --- Event Listeners ---
    
    // Period selector buttons
    periodButtons.forEach(button => {
        button.addEventListener('click', () => {
            const period = button.getAttribute('data-period');
            if (period === currentPeriod) return;
            
            document.querySelector('.period-selector button.active').classList.remove('active');
            button.classList.add('active');
            
            currentPeriod = period;
            fetchStatsData(period);
        });
    });

    // Event listener for chart clicks
    function handleChartClick(event) {
        if (event.target.classList.contains('bar')) {
            const barElement = event.target;
            const tooltipContent = barElement.dataset.tooltipContent;
            if (tooltipContent) {
                showChartTooltip(tooltipContent, barElement);
            } else {
                showChartTooltip(barElement.title, barElement);
            }
        } else {
            hideChartTooltip();
        }
    }

    // Attach listeners to chart containers
    earningsChartEl.addEventListener('click', handleChartClick);
    spendingChartEl.addEventListener('click', handleChartClick);
    hoursChartEl.addEventListener('click', handleChartClick);

    // Listener to hide tooltip when clicking outside charts
    document.addEventListener('click', (event) => {
        if (!earningsChartEl.contains(event.target) &&
            !spendingChartEl.contains(event.target) &&
            !hoursChartEl.contains(event.target)) {
            hideChartTooltip();
        }
    });
    
    // --- Initialization ---
    fetchStatsData(currentPeriod);
}); 