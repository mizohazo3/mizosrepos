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
        return '$' + num.toFixed(2);
    }

    // Enhanced currency formatter for bank log
    function formatLogCurrency(amount, type) {
        const num = parseFloat(amount);
        if (isNaN(num)) {
            return '$--.--';
        }
        // Always show sign for purchases, optional for earnings
        const sign = type === 'purchase' || type === 'note' ? '-' : (num > 0 ? '+' : '');
        const absNum = Math.abs(num);
        return `${sign}$${absNum.toFixed(2)}`;
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
        if (type === 'purchase' || type === 'note') return '-';
        return formatDuration(durationSeconds); // Use existing helper
    }

    // Get note details
    async function fetchNoteDetails(noteId) {
        try {
            const response = await apiCall(`get_note_details.php?id=${noteId}`);
            return response;
        } catch (error) {
            return null;
        }
    }

    // Show note details modal
    function showNoteDetailsModal(noteId, details) {
        // Create modal container if it doesn't exist
        let modal = document.getElementById('note-details-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'note-details-modal';
            modal.className = 'modal';
            document.body.appendChild(modal);
        }
        
        // Populate modal content
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Note Payment Details</h3>
                    <button class="close-button">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Note ID: ${noteId}</p>
                    <p>Payment Time: ${formatTimestamp(details.paid_at || '')}</p>
                    <p>Total Amount: ${formatCurrency(details.total_amount || 0)}</p>
                    <h4>Items:</h4>
                    <ul class="note-items-list">
                        ${renderNoteItems(details.items_list || '[]')}
                    </ul>
                </div>
            </div>
        `;
        
        // Add event listener to close button
        const closeButton = modal.querySelector('.close-button');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        
        // Add click outside to close
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Show the modal
        modal.style.display = 'block';
    }
    
    // Render note items list
    function renderNoteItems(itemsListJson) {
        try {
            const itemsList = typeof itemsListJson === 'string' ? 
                JSON.parse(itemsListJson) : itemsListJson;
                
            if (!Array.isArray(itemsList) || itemsList.length === 0) {
                return '<li>No items found</li>';
            }
            
            return itemsList.map(item => `
                <li class="note-item">
                    <div class="note-item-name">${item.name || 'Unknown Item'}</div>
                    <div class="note-item-price">${formatCurrency(item.price || 0)}</div>
                </li>
            `).join('');
        } catch (error) {
            return '<li>Error loading items</li>';
        }
    }

    // Format timestamp (basic)
    function formatTimestamp(dateTimeString) {
        try {
            // Remove the Z suffix that's forcing UTC interpretation
            // The server already returns dates in the correct timezone (Africa/Cairo)
            const date = new Date(dateTimeString.replace(' ', 'T'));
            if (isNaN(date.getTime())) return 'Invalid Date';
            // Use browser's local time formatting
            return date.toLocaleString(undefined, {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
        } catch (e) {
            return dateTimeString; // Fallback
        }
    }

    // Basic API Call function
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json'
            },
        };
         if (method === 'POST' && body) {
             options.headers['Content-Type'] = 'application/json';
             options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, options);
            const responseText = await response.text();

            if (!response.ok) {
                let errorMsg = `HTTP error ${response.status} calling ${endpoint}`;
                try { const errorData = JSON.parse(responseText); errorMsg = errorData.message || errorMsg;}
                catch (e) { errorMsg += ` - Response: ${responseText.substring(0, 100)}`; }
                throw new Error(errorMsg);
            }

            if (!responseText.trim()) return { status: 'success', message: 'Empty response' };
            try {
                const data = JSON.parse(responseText);
                return data;
            } catch (e) {
                 throw new Error(`Failed to parse JSON from ${endpoint}: ${e.message}`);
             }
         } catch (error) {
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
                 if (!transactionTemplate || !transactionTemplate.content) {
                     return;
                 }

                const templateContent = transactionTemplate.content.cloneNode(true);
                const item = templateContent.querySelector('.transaction-item');
                
                if (!item) {
                    return;
                }
                
                const nameEl = item.querySelector('.transaction-timer-name');
                const timeEl = item.querySelector('.transaction-time');
                const durationEl = item.querySelector('.transaction-duration');
                const earnedEl = item.querySelector('.transaction-earned');

                // Add class based on type
                item.classList.add(`transaction-${tx.type}`);

                if (nameEl) nameEl.textContent = tx.details || (tx.type === 'earn' ? `Timer #${tx.related_id}`: `Item #${tx.related_id}`);
                if (timeEl) timeEl.textContent = formatTimestamp(tx.timestamp);
                if (durationEl) {
                    if (tx.type === 'note' || tx.type === 'purchase') {
                        durationEl.textContent = '-'; // No duration for note payments
                    } else {
                        durationEl.textContent = formatLogDuration(tx.duration, tx.type);
                    }
                }
                if (earnedEl) {
                    earnedEl.textContent = formatLogCurrency(tx.amount, tx.type);
                    // Add class based on value sign AND type for color coding
                    const amountNum = parseFloat(tx.amount);
                    if (tx.type === 'purchase' || tx.type === 'note') {
                        earnedEl.classList.add('negative');
                    } else if (amountNum > 0) {
                        earnedEl.classList.add('positive');
                    }
                }
                
                // Add "View Items" button for note payments
                if (tx.type === 'note') {
                    // Create a new button
                    const viewItemsBtn = document.createElement('button');
                    viewItemsBtn.className = 'view-note-items button small-button';
                    viewItemsBtn.textContent = 'View Items';
                    viewItemsBtn.dataset.noteId = tx.log_id;
                    
                    // Add event listener
                    viewItemsBtn.addEventListener('click', async () => {
                        // Fetch note details from server
                        const noteDetails = await apiCall(`get_note_details.php?id=${tx.log_id}`);
                        if (noteDetails && noteDetails.status === 'success') {
                            showNoteDetailsModal(tx.log_id, noteDetails.note);
                        } else {
                            if (typeof displayNotification === 'function') {
                                displayNotification('Failed to load note details', 'error');
                            } else {
                                alert('Failed to load note details');
                            }
                        }
                    });
                    
                    // Add button to the item
                    const btnContainer = document.createElement('div');
                    btnContainer.className = 'transaction-actions';
                    btnContainer.appendChild(viewItemsBtn);
                    item.appendChild(btnContainer);
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
        // Show loading states
        currentBalanceEl.textContent = 'Loading...';
        totalEarnedEl.textContent = 'Loading...';
        loadingMessageEl.style.display = 'block';
        emptyMessageEl.style.display = 'none';
        transactionListEl.style.display = 'none';

        try {
            // Directly fetch and log the raw response
            const response = await fetch(API_BASE_URL + 'get_bank_data.php');
            const responseText = await response.text();
            
            // Parse the data
            const data = JSON.parse(responseText);
            
            // Log transactions specifically
            if (data && data.transactions) {
                data.transactions.forEach((tx, i) => {
                });
            }
            
            updateBankUI(data);
        } catch (error) {
            currentBalanceEl.textContent = 'Error loading';
            totalEarnedEl.textContent = 'Error loading';
            loadingMessageEl.textContent = 'Failed to load transaction data: ' + error.message;
        }
    }

    // --- Initialize ---
    fetchBankData();

    // --- Expose functions for external use ---
    window.updateBankDisplay = fetchBankData;
}); // End DOMContentLoaded