document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';

    // --- State Variables ---
    let notedItems = [];

    // --- DOM Elements ---
    const viewNotesButton = document.getElementById('view-notes');
    const notesModal = document.getElementById('notes-summary');
    const closeNotesButton = document.getElementById('close-notes');
    const notedItemsContainer = document.getElementById('noted-items');
    const notesCountSpan = document.getElementById('notes-count');
    const notesTotalAmount = document.getElementById('notes-total-amount');
    const clearNotesButton = document.getElementById('clear-notes');
    const noteItemTemplate = document.getElementById('note-item-template');
    
    // --- Find the notes-actions div and add Pay All button ---
    const notesActions = document.querySelector('.notes-actions');
    if (notesActions) {
        // Create Pay All button if it doesn't exist
        if (!document.getElementById('pay-all-notes')) {
            const payAllButton = document.createElement('button');
            payAllButton.id = 'pay-all-notes';
            payAllButton.className = 'button primary-button';
            payAllButton.textContent = 'Pay All Items';
            payAllButton.title = 'Pay for all items on note';
            
            // Insert Pay All button before Clear All button
            notesActions.insertBefore(payAllButton, clearNotesButton);
            
            // Add event listener
            payAllButton.addEventListener('click', handlePayAllNotes);
        }
    }

    // --- Event Listeners ---
    viewNotesButton?.addEventListener('click', toggleNotesModal);
    closeNotesButton?.addEventListener('click', toggleNotesModal);
    clearNotesButton?.addEventListener('click', clearAllNotes);

    // Add event delegation for add-to-note buttons
    document.addEventListener('click', (event) => {
        if (event.target.matches('.add-to-note-button')) {
            handleAddToNote(event);
        } else if (event.target.matches('.remove-from-note')) {
            handleRemoveFromNote(event);
        } else if (event.target.matches('.buy-from-note')) {
            handleBuyFromNote(event);
        }
    });

    // --- Functions ---
    function toggleNotesModal() {
        if (notesModal) {
            const isVisible = notesModal.style.display === 'block';
            notesModal.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                loadNotedItems(); // Refresh notes when opening modal
            }
            
            // Add/remove modal overlay
            let overlay = document.querySelector('.modal-overlay');
            if (!overlay && !isVisible) {
                overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                document.body.appendChild(overlay);
                overlay.addEventListener('click', toggleNotesModal);
            } else if (overlay && isVisible) {
                overlay.remove();
            }
        }
    }

    async function loadNotedItems() {
        try {
            const response = await apiCall('notes.php');
            if (response.status === 'success') {
                notedItems = response.notes;
                updateNotesDisplay();
            }
        } catch (error) {
            displayNotification('Failed to load notes', 'error');
        }
    }

    function updateNotesDisplay() {
        if (!notedItemsContainer || !notesCountSpan || !notesTotalAmount) return;

        // Update count
        notesCountSpan.textContent = notedItems.length;

        // Get the Pay All button
        const payAllButton = document.getElementById('pay-all-notes');

        // Clear container
        notedItemsContainer.innerHTML = '';

        if (notedItems.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'empty-message';
            emptyMessage.textContent = 'No items on note';
            notedItemsContainer.appendChild(emptyMessage);
            notesTotalAmount.textContent = '$0.00';
            
            // Disable Pay All button if no items
            if (payAllButton) {
                payAllButton.disabled = true;
                payAllButton.title = 'No items to pay for';
            }
            return;
        }

        // Calculate total
        const total = notedItems.reduce((sum, item) => sum + parseFloat(item.price), 0);
        notesTotalAmount.textContent = `$${total.toFixed(2)}`;

        // Get current balance for buy button state
        const currentBalance = parseFloat(document.getElementById('current-balance')?.textContent || '0');
        
        // Enable/disable Pay All button based on balance
        if (payAllButton) {
            const canAffordAll = currentBalance >= total;
            payAllButton.disabled = !canAffordAll;
            payAllButton.title = canAffordAll ? 
                `Pay for all items ($${total.toFixed(2)})` : 
                `Insufficient funds (need $${total.toFixed(2)})`;
        }

        // Render items
        notedItems.forEach(item => {
            if (!noteItemTemplate?.content) return;

            const templateContent = noteItemTemplate.content.cloneNode(true);
            const noteItem = templateContent.querySelector('.note-item');
            const img = noteItem.querySelector('.note-item-icon');
            const nameEl = noteItem.querySelector('.note-item-name');
            const priceEl = noteItem.querySelector('.note-item-price');
            const buyButton = noteItem.querySelector('.buy-from-note');

            noteItem.dataset.itemId = item.item_id;
            if (img) {
                img.src = item.image_url || 'https://via.placeholder.com/150/CCCCCC/FFFFFF?text=No+Image';
                img.alt = item.name;
            }
            if (nameEl) nameEl.textContent = item.name;
            if (priceEl) priceEl.textContent = `$${parseFloat(item.price).toFixed(2)}`;
            
            // Enable/disable buy button based on current balance
            if (buyButton) {
                const canAfford = currentBalance >= parseFloat(item.price);
                buyButton.disabled = !canAfford;
                buyButton.title = canAfford ? `Buy ${item.name}` : "Insufficient funds";
            }

            notedItemsContainer.appendChild(noteItem);
        });
    }

    async function handleAddToNote(event) {
        const button = event.target;
        const card = button.closest('.item-card');
        if (!card) return;

        const itemId = card.dataset.itemId;
        if (!itemId) return;

        try {
            const response = await apiCall('notes.php', 'POST', { item_id: itemId });
            if (response.status === 'success') {
                await loadNotedItems(); // Reload notes from server
                
                // Show notification when item is added to note
                if (typeof displayNotification === 'function') {
                    const itemName = card.querySelector('.item-name')?.textContent || 'Item';
                    displayNotification(`Added "${itemName}" to your note`, 'success');
                }
            } else {
                displayNotification(response.message, 'error');
            }
        } catch (error) {
            displayNotification('Failed to add item to notes', 'error');
        }
    }

    async function handleRemoveFromNote(event) {
        const button = event.target;
        const noteItem = button.closest('.note-item');
        if (!noteItem) return;

        const itemId = noteItem.dataset.itemId;
        if (!itemId) return;

        try {
            const response = await apiCall('notes.php', 'DELETE', { item_id: itemId });
            if (response.status === 'success') {
                await loadNotedItems(); // Reload notes from server
            } else {
                displayNotification(response.message, 'error');
            }
        } catch (error) {
            displayNotification('Failed to remove item from notes', 'error');
        }
    }
    
    // New function to handle paying for all notes
    async function handlePayAllNotes() {
        if (notedItems.length === 0) return;
        
        const payAllButton = document.getElementById('pay-all-notes');
        if (!payAllButton) return;
        
        // Disable button during processing
        payAllButton.disabled = true;
        payAllButton.textContent = 'Processing...';
        
        try {
            const result = await apiCall('pay_all_notes.php', 'POST');
            
            if (result && result.status === 'success') {
                // Show success notification
                displayNotification(result.message, 'success');
                
                // Update balance display
                if (window.updateAffordability && result.new_balance !== undefined) {
                    const currentBalanceSpan = document.getElementById('current-balance');
                    if (currentBalanceSpan) {
                        currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(2);
                    }
                    window.updateAffordability(result.new_balance);
                }
                
                // Close notes modal after successful payment
                toggleNotesModal();
                
                // Reload notes (should be empty now)
                await loadNotedItems();
                
                // Refresh page data if needed
                if (typeof loadRecentPurchases === 'function') {
                    loadRecentPurchases();
                }
            } else {
                displayNotification(result.message || 'Payment failed.', 'error');
            }
        } catch (error) {
            displayNotification(`Payment failed: ${error.message}`, 'error');
        } finally {
            // Reset button state
            payAllButton.disabled = false;
            payAllButton.textContent = 'Pay All Items';
        }
    }

    async function handleBuyFromNote(event) {
        const button = event.target;
        const noteItem = button.closest('.note-item');
        if (!noteItem) return;

        const itemId = noteItem.dataset.itemId;

        // Disable button during processing
        button.disabled = true;
        button.textContent = 'Buying...';

        try {
            const result = await apiCall('purchase_item.php', 'POST', { item_id: parseInt(itemId) });

            if (result && result.status === 'success') {
                // Remove from notes after successful purchase
                await apiCall('notes.php', 'DELETE', { item_id: itemId });
                await loadNotedItems(); // Reload notes from server

                // Update balance display
                if (window.updateAffordability && result.new_balance) {
                    const currentBalanceSpan = document.getElementById('current-balance');
                    if (currentBalanceSpan) {
                        currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(2);
                    }
                    window.updateAffordability(result.new_balance);
                }
            } else {
                displayNotification(result.message || 'Purchase failed.', 'error');
                button.disabled = false;
                button.textContent = 'Buy Now';
            }
        } catch (error) {
            displayNotification(`Purchase request failed: ${error.message}`, 'error');
            button.disabled = false;
            button.textContent = 'Buy Now';
        }
    }

    async function clearAllNotes() {
        if (notedItems.length === 0) return;

        if (confirm('Are you sure you want to clear all noted items?')) {
            try {
                const response = await apiCall('notes.php', 'DELETE');
                if (response.status === 'success') {
                    await loadNotedItems(); // Reload notes from server
                } else {
                    displayNotification(response.message, 'error');
                }
            } catch (error) {
                displayNotification('Failed to clear notes', 'error');
            }
        }
    }

    // --- API Helper ---
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
        };
        if (method === 'POST' || method === 'DELETE') {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`HTTP error ${response.status}`);
        return response.json();
    }

    // --- Initialize ---
    loadNotedItems();

    // --- Expose functions for external use ---
    window.updateNotesDisplay = updateNotesDisplay;
}); 