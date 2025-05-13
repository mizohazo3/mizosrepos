document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';

    // --- DOM Elements ---
    const currentBalanceEl = document.getElementById('bank-current-balance');
    const totalEarnedEl = document.getElementById('bank-total-earned');
    const transactionListEl = document.getElementById('transaction-list');
    const transactionTemplate = document.getElementById('transaction-item-template');
    const loadingMessageEl = document.getElementById('transactions-loading');
    const emptyMessageEl = document.getElementById('transactions-empty');
    const countShownEl = document.getElementById('transaction-count-shown');
    const limitNoteEl = document.querySelector('.transaction-limit-note');
    const limitSpanEl = document.getElementById('transaction-limit');

    // --- Utility Functions ---

    // Simple currency formatter
    function formatCurrency(amount) {
        const num = parseFloat(amount);
        if (isNaN(num)) {
            return '$--.--'; // Indicate error or non-number
        }
        // Show more precision for potentially small earnings
        return '$' + num.toFixed(6);
    }

    // Enhanced currency formatter for bank log
    function formatLogCurrency(amount, type) {
        const num = parseFloat(amount);
        if (isNaN(num)) {
            return '$--.--';
        }
        // Always show sign for purchases, optional for earnings
        const sign = type === 'purchase' ? '-' : (num > 0 ? '+' : '');
        const absNum = Math.abs(num);
        return `${sign}$${absNum.toFixed(type === 'purchase' ? 2 : 6)}`; // Purchases likely round prices, earnings can be tiny
    }

    // Simple duration formatter (from seconds)
    function formatDuration(totalSeconds) {
        if (isNaN(totalSeconds) || totalSeconds < 0) {
            return '0s';
        }
        totalSeconds = Math.floor(totalSeconds);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        let parts = [];
        if (hours > 0) parts.push(`${hours}h`);
        if (minutes > 0) parts.push(`${minutes}m`);
        if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`); // Always show seconds if no h/m or if duration is 0

        return parts.join(' ');
    }

    // Format duration (show '-' for purchases)
    function formatLogDuration(durationSeconds, type) {
        if (type === 'purchase') return '-';
        return formatDuration(durationSeconds); // Use existing helper
    }

    // Format timestamp (basic)
    function formatTimestamp(dateTimeString) {
        try {
            const date = new Date(dateTimeString.replace(' ', 'T') + 'Z'); // Assume UTC from DB
            if (isNaN(date.getTime())) return 'Invalid Date';
            // Use browser's local time formatting
            return date.toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: 'numeric', minute: '2-digit' // , second: '2-digit'
            });
        } catch (e) {
            console.error("Error formatting timestamp:", dateTimeString, e);
            return dateTimeString; // Fallback
        }
    }

    // Basic API Call function (can be copied/adapted from main script.js)
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json' // Important: We expect JSON back
            },
        };
         if (method === 'POST' && body) {
             options.headers['Content-Type'] = 'application/json';
             options.body = JSON.stringify(body);
        }
         console.log(`API Call: ${method} ${url}`);

        try {
            const response = await fetch(url, options);
            const responseText = await response.text(); // Get text first

            if (!response.ok) {
                let errorMsg = `HTTP error ${response.status} calling ${endpoint}`;
                try { const errorData = JSON.parse(responseText); errorMsg = errorData.message || errorMsg;}
                catch (e) { errorMsg += ` - Response: ${responseText.substring(0, 100)}`; }
                throw new Error(errorMsg);
            }

            if (!responseText.trim()) return { status: 'success', message: 'Empty response' };
            try {
                const data = JSON.parse(responseText);
                console.log(`API Response for ${endpoint}:`, data);
                return data;
            } catch (e) {
                 throw new Error(`Failed to parse JSON from ${endpoint}: ${e.message}`);
             }
         } catch (error) {
             console.error(`API call failed: ${method} ${url}`, error);
             // Display error to user appropriately elsewhere
             return { status: 'error', message: error.message };
         }
     }

    // --- UI Update Function ---
    function updateBankUI(data) {
         if (!data || data.status !== 'success') {
            currentBalanceEl.textContent = 'Error loading';
            totalEarnedEl.textContent = 'Error loading';
            loadingMessageEl.textContent = 'Failed to load transaction data.';
            emptyMessageEl.style.display = 'none';
            loadingMessageEl.style.display = 'block';
            return;
         }

        // Update Summary Cards
        currentBalanceEl.textContent = formatCurrency(data.current_balance);
        totalEarnedEl.textContent = formatCurrency(data.total_earned_timers);
        currentBalanceEl.classList.add('loaded');
        totalEarnedEl.classList.add('loaded');

        // Update Transactions
        const transactions = data.transactions || [];
        transactionListEl.innerHTML = '';

        if (transactions.length > 0) {
             loadingMessageEl.style.display = 'none';
             emptyMessageEl.style.display = 'none';
             transactionListEl.style.display = 'block';

             transactions.forEach(tx => {
                 if (!transactionTemplate.content) return;

                const templateContent = transactionTemplate.content.cloneNode(true);
                const item = templateContent.querySelector('.transaction-item');
                const nameEl = item.querySelector('.transaction-timer-name');
                const timeEl = item.querySelector('.transaction-time');
                const durationEl = item.querySelector('.transaction-duration');
                const earnedEl = item.querySelector('.transaction-earned');

                // Add class based on type
                item.classList.add(`transaction-${tx.type}`);

                if (nameEl) nameEl.textContent = tx.details || (tx.type === 'earn' ? `Timer #${tx.related_id}`: `Item #${tx.related_id}`);
                if (timeEl) timeEl.textContent = formatTimestamp(tx.timestamp);
                if (durationEl) durationEl.textContent = formatLogDuration(tx.duration, tx.type);
                if (earnedEl) {
                    earnedEl.textContent = formatLogCurrency(tx.amount, tx.type);
                    // Add class based on value sign AND type for color coding
                    const amountNum = parseFloat(tx.amount);
                    if (tx.type === 'purchase') {
                        earnedEl.classList.add('negative');
                    } else if (amountNum > 0) {
                        earnedEl.classList.add('positive');
                    }
                }

                transactionListEl.appendChild(item);
             });

            // Update count and limit note
            countShownEl.textContent = transactions.length;
            if (limitSpanEl) limitSpanEl.textContent = data.transaction_limit_applied;
            if (limitNoteEl) limitNoteEl.style.display = 'block';

        } else {
             loadingMessageEl.style.display = 'none';
             emptyMessageEl.style.display = 'block';
             transactionListEl.style.display = 'none';
             if (limitNoteEl) limitNoteEl.style.display = 'none';
             countShownEl.textContent = '0';
         }
    }

    // --- Fetch Initial Data ---
    async function fetchBankData() {
         console.log("Fetching bank data...");
         // Show loading states
         currentBalanceEl.textContent = 'Loading...';
         totalEarnedEl.textContent = 'Loading...';
         loadingMessageEl.style.display = 'block';
         emptyMessageEl.style.display = 'none';
         transactionListEl.style.display = 'none';

         const data = await apiCall('get_bank_data.php');
         updateBankUI(data); // Update UI based on response (handles success/error)
     }

    // --- Initialisation ---
    fetchBankData();

}); // End DOMContentLoaded