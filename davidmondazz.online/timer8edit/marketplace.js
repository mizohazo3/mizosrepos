document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';

    // --- DOM Elements ---
    const itemGrid = document.getElementById('item-grid');
    const itemTemplate = document.getElementById('item-template');
    const loadingMessageEl = document.getElementById('items-loading');
    const emptyMessageEl = document.getElementById('items-empty');
    const currentBalanceSpan = document.getElementById('market-current-balance');
    const notificationContainer = document.getElementById('notification-container'); // Ensure this exists

    // --- Utility ---
    // Basic notification function (adapt or copy from script.js/bank.js)
     // displayNotification (Assume this exists, possibly in a shared notifications.js)
     function displayNotification(message, type = 'info', duration = 4000) {
         // Simplified version for example. Use your existing robust one.
         if (!notificationContainer) return;
         const notification = document.createElement('div');
          // Apply appropriate classes and content based on type
         notification.className = `notification notification-${type} show`; // Basic styling
         notification.textContent = message;
         notificationContainer.appendChild(notification);
         setTimeout(() => {
             notification.remove();
         }, duration);
         console.log(`Notification [${type}]: ${message}`);
    }

    function formatMarketCurrency(amount) {
        const num = parseFloat(amount);
        return isNaN(num) ? '$--.--' : '$' + num.toFixed(2); // Standard currency format
    }


    // Basic API Call function (can be shared)
    async function apiCall(endpoint, method = 'GET', body = null) {
         const url = API_BASE_URL + endpoint;
         const options = {
             method: method,
             headers: { 'Accept': 'application/json' },
         };
         if (method === 'POST' && body) {
             options.headers['Content-Type'] = 'application/json';
             options.body = JSON.stringify(body);
         }
         try {
             const response = await fetch(url, options);
             const data = await response.json(); // Directly parse JSON
             if (!response.ok) {
                throw new Error(data.message || `HTTP error ${response.status}`);
            }
            return data;
        } catch (error) {
            console.error(`API Error (${method} ${url}):`, error);
             return { status: 'error', message: error.message }; // Return error structure
        }
     }


    // --- Render Items ---
    function renderMarketplace(data) {
        if (!data || data.status !== 'success') {
            loadingMessageEl.textContent = `Error loading marketplace: ${data?.message || 'Unknown error'}`;
            emptyMessageEl.style.display = 'none';
            itemGrid.style.display = 'none';
            currentBalanceSpan.textContent = 'Error';
            return;
        }

        const items = data.items || [];
        itemGrid.innerHTML = ''; // Clear existing

        // Update balance display
        currentBalanceSpan.textContent = parseFloat(data.current_balance).toFixed(4);

        if (items.length > 0) {
            loadingMessageEl.style.display = 'none';
            emptyMessageEl.style.display = 'none';
            itemGrid.style.display = 'grid'; // Use the grid display

            items.forEach(itemData => {
                if (!itemTemplate?.content) return;

                const templateContent = itemTemplate.content.cloneNode(true);
                const card = templateContent.querySelector('.item-card');
                const img = card.querySelector('.item-image');
                const nameEl = card.querySelector('.item-name');
                const descEl = card.querySelector('.item-description');
                const priceEl = card.querySelector('.item-price');
                const buyButton = card.querySelector('.buy-button');

                card.dataset.itemId = itemData.id;
                if (img) {
                    img.src = itemData.image_url || 'https://via.placeholder.com/150/CCCCCC/FFFFFF?text=No+Image';
                    img.alt = itemData.name;
                }
                if (nameEl) nameEl.textContent = itemData.name;
                if (descEl) descEl.textContent = itemData.description || 'No description available.';
                if (priceEl) priceEl.textContent = formatMarketCurrency(itemData.price);

                // Check if user can afford THIS specific item
                const canAfford = parseFloat(data.current_balance) >= parseFloat(itemData.price);
                if (buyButton) {
                    buyButton.disabled = !canAfford;
                    buyButton.dataset.itemId = itemData.id; // Add ID to button for easier handling
                    buyButton.title = canAfford ? `Buy ${itemData.name}` : "Insufficient funds";
                }

                // Add event listener directly here
                buyButton.addEventListener('click', handlePurchaseClick);

                itemGrid.appendChild(card);
            });

        } else {
            loadingMessageEl.style.display = 'none';
            emptyMessageEl.style.display = 'block';
            itemGrid.style.display = 'none';
        }
    }

    // --- Purchase Handling ---
    async function handlePurchaseClick(event) {
        const button = event.target;
        const itemId = button.dataset.itemId;
         if (!itemId) return;

        const card = button.closest('.item-card');
        const itemName = card.querySelector('.item-name').textContent;
        const itemPrice = card.querySelector('.item-price').textContent;

        // Show confirmation modal instead of using confirm()
        modal.show({
            title: 'Confirm Purchase',
            message: `Are you sure you want to buy "${itemName}" for ${itemPrice}?`,
            confirmText: 'Buy Now',
            type: 'confirm',
            onConfirm: async () => {
                // Disable button during processing
                button.disabled = true;
                button.textContent = 'Buying...';

                try {
                    const result = await apiCall('purchase_item.php', 'POST', { item_id: parseInt(itemId) });

                    if (result && result.status === 'success') {
                         displayNotification(result.message || 'Purchase successful!', 'success');
                        // Update balance display immediately
                         if (currentBalanceSpan) currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(4);

                        // Re-evaluate affordability for ALL items after purchase
                         updateAffordability(result.new_balance);

                         // Optionally change button text permanently if stock is limited/gone
                         // button.textContent = 'Purchased';
                         // Or simply re-enable if multiple buys are allowed and affordable
                          button.textContent = 'Buy';
                          // Check if still affordable after purchase (e.g. buying multiple)
                          const priceNum = parseFloat(itemPrice.replace('$', ''));
                          if (result.new_balance < priceNum) {
                                button.disabled = true;
                                button.title = "Insufficient funds";
                           } else {
                               button.disabled = false;
                           }


                    } else {
                        // Handle specific errors like insufficient funds differently if desired
                         displayNotification(result.message || 'Purchase failed.', 'error');
                         button.disabled = false; // Re-enable on failure
                          button.textContent = 'Buy';
                          // No need to re-evaluate affordability on failure unless backend indicates balance changed
                     }

                } catch (error) { // Catch network/fetch errors
                     displayNotification(`Purchase request failed: ${error.message}`, 'error');
                     button.disabled = false; // Re-enable on failure
                     button.textContent = 'Buy';
                }
            }
        });
    }

    // --- Function to update buy button states based on balance ---
    function updateAffordability(currentBalance) {
        const allBuyButtons = itemGrid.querySelectorAll('.buy-button');
         allBuyButtons.forEach(button => {
             const card = button.closest('.item-card');
             if (!card) return;
             const priceEl = card.querySelector('.item-price');
             if (!priceEl) return;

             const price = parseFloat(priceEl.textContent.replace('$', ''));
             const canAfford = !isNaN(price) && currentBalance >= price;

             button.disabled = !canAfford;
             button.title = canAfford ? `Buy ${card.querySelector('.item-name').textContent}` : "Insufficient funds";
        });
    }

    // --- DELETE HANDLING ---
    async function handleDeleteClick(event) {
        const button = event.target;
        const card = button.closest('.item-card');
        const itemId = card?.dataset.itemId;
        if (!itemId) {
            console.error("Could not find item ID for delete.");
            return;
        }

        const itemName = card.querySelector('.item-name')?.textContent || `Item #${itemId}`;

        // Show delete confirmation modal instead of using confirm()
        modal.show({
            title: 'Delete Item',
            message: `Are you sure you want to permanently delete "${itemName}"? This action cannot be undone.`,
            confirmText: 'Delete',
            type: 'warning',
            onConfirm: async () => {
                button.disabled = true;
                button.innerHTML = '...';

                try {
                    const result = await apiCall('delete_item.php', 'POST', { item_id: parseInt(itemId) });

                    if (result && (result.status === 'success' || result.status === 'success_with_warning')) {
                        displayNotification(result.message || 'Item deleted.', 'success');
                        card.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            if (itemGrid.childElementCount <= 1) {
                                emptyMessageEl.style.display = 'block';
                                itemGrid.style.display = 'none';
                            }
                        }, 300);

                        if (result.warning) {
                            displayNotification(result.warning, 'warning', 6000);
                        }
                    } else {
                        displayNotification(result.message || 'Failed to delete item.', 'error');
                        button.disabled = false;
                        button.innerHTML = '🗑️';
                    }
                } catch (error) {
                    displayNotification(`Error deleting item: ${error.message}`, 'error');
                    button.disabled = false;
                    button.innerHTML = '🗑️';
                }
            }
        });
    }

    // --- Event Delegation for Grid ---
    itemGrid.addEventListener('click', (event) => {
        const target = event.target;
        if (target.classList.contains('buy-button') && target.closest('.item-card')) {
            handlePurchaseClick(event);
        } else if (target.classList.contains('delete-button') && target.closest('.item-card')) {
            handleDeleteClick(event);
        }
    });

    // --- Fetch Initial Data ---
    async function fetchMarketplaceData() {
        loadingMessageEl.style.display = 'block';
        emptyMessageEl.style.display = 'none';
        itemGrid.style.display = 'none';
        const data = await apiCall('get_marketplace_data.php');
        renderMarketplace(data);
    }

    // --- Initialisation ---
    fetchMarketplaceData();

}); // End DOMContentLoaded