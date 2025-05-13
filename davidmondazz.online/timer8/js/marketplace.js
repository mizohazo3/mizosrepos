document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';

    // --- State Variables ---
    let marketplaceItems = []; // To store fetched marketplace items

    // --- DOM Elements ---
    const itemGrid = document.getElementById('item-grid');
    const itemTemplate = document.getElementById('item-template');
    const loadingMessageEl = document.getElementById('items-loading');
    const emptyMessageEl = document.getElementById('items-empty');
    const currentBalanceSpan = document.getElementById('current-balance');
    const notificationContainer = document.getElementById('notification-container');
    const basketItemTemplate = document.getElementById('basket-item-template');

    // Check if required elements exist
    if (!itemGrid || !itemTemplate) {
        return;
    }

    // --- Utility Functions ---
    function displayNotification(message, type = 'info', duration = 4000) {
        if (!notificationContainer) return;
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} show`;
        notification.textContent = message;
        notificationContainer.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, duration);
    }

    function formatMarketCurrency(amount) {
        const num = parseFloat(amount);
        return isNaN(num) ? '$--.--' : '$' + num.toFixed(2);
    }

    // --- API Call Function ---
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        const options = {
            method: method,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
        };
        if (method === 'POST' && body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        try {
            const response = await fetch(url, options);

            // Log response status for debugging

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            throw error;
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
        marketplaceItems = items; // Store fetched items in the state variable
        itemGrid.innerHTML = ''; // Clear existing

        // Update balance display
        currentBalanceSpan.textContent = parseFloat(data.current_balance).toFixed(2);

        if (items.length > 0) {
            loadingMessageEl.style.display = 'none';
            emptyMessageEl.style.display = 'none';
            itemGrid.style.display = 'grid'; // Use the grid display

            items.forEach(itemData => {
                if (!itemTemplate?.content) return;

                const templateContent = itemTemplate.content.cloneNode(true);
                const card = templateContent.querySelector('.item-card');
                const imgContainer = card.querySelector('.item-image-container');
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
                if (descEl && descEl.textContent) descEl.textContent = itemData.description || 'No description available.';
                if (priceEl) priceEl.textContent = formatMarketCurrency(itemData.price);

                // Check if user can afford THIS specific item
                const canAfford = parseFloat(data.current_balance) >= parseFloat(itemData.price);
                if (buyButton) {
                    buyButton.disabled = !canAfford;
                    buyButton.dataset.itemId = itemData.id; // Add ID to button for easier handling
                    buyButton.title = canAfford ? `Buy ${itemData.name}` : "Insufficient funds";
                }

                // Make image container clickable for adding to basket
                if (imgContainer) {
                    imgContainer.style.cursor = 'pointer';
                    imgContainer.dataset.itemId = itemData.id;
                    imgContainer.addEventListener('click', function() {
                        // Call purchase function directly
                        handlePurchaseClick({
                            target: buyButton // Pass the buy button as target since it has the itemId
                        });
                    });
                }

                // Set up the add to note button
                const addToNoteButton = card.querySelector('.add-to-note-button');
                if (addToNoteButton) {
                    addToNoteButton.dataset.itemId = itemData.id;
                    addToNoteButton.title = `Add ${itemData.name} to note`;
                }

                // Add event listener for buy button to add to basket instead
                buyButton.addEventListener('click', function() {
                    // Dispatch a custom event to be handled by basket.js
                    const addToBasketEvent = new CustomEvent('add-to-basket', {
                        detail: {
                            itemId: itemData.id,
                            name: itemData.name,
                            price: itemData.price,
                            image_url: img.src
                        }
                    });
                    document.dispatchEvent(addToBasketEvent);
                });

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

        // Disable button during processing
        button.disabled = true;
        button.textContent = 'Buying...';

        try {
            const result = await apiCall('purchase_item.php', 'POST', { item_id: parseInt(itemId) });

            if (result && result.status === 'success') {
                displayNotification(result.message || 'Purchase successful!', 'success');
                // Update balance display immediately
                if (currentBalanceSpan) currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(2);

                // Re-evaluate affordability for ALL items after purchase
                updateAffordability(result.new_balance);

                // Reset button
                button.textContent = 'Add to Basket';
                // Check if still affordable after purchase (e.g. buying multiple)
                const priceEl = card.querySelector('.item-price');
                const priceNum = parseFloat(priceEl.textContent.replace('$', ''));
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
                button.textContent = 'Add to Basket';
                // No need to re-evaluate affordability on failure unless backend indicates balance changed
            }
        } catch (error) {
            displayNotification(`Purchase failed: ${error.message}`, 'error');
            button.disabled = false; // Re-enable on failure
            button.textContent = 'Add to Basket';
        }
    }

    // --- Function to update buy button states based on balance ---
    // Expose the function globally for use by basket.js
    window.updateAffordability = function(currentBalance) {
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

    // --- EDIT PRICE HANDLING ---
    async function handleEditClick(event) {
        const button = event.target;
        const card = button.closest('.item-card');
        const itemId = card?.dataset.itemId;
        if (!itemId) {
            return;
        }

        const itemName = card.querySelector('.item-name')?.textContent || `Item #${itemId}`;
        const priceEl = card.querySelector('.item-price');
        const currentPrice = priceEl ? parseFloat(priceEl.textContent.replace('$', '')) : 0;

        // Create a simple prompt for the new price
        const newPrice = prompt(`Enter new price for "${itemName}" (current: $${currentPrice.toFixed(2)}):`, currentPrice.toFixed(2));

        // Check if the user canceled or entered an invalid value
        if (newPrice === null) return; // User canceled

        const newPriceValue = parseFloat(newPrice);
        if (isNaN(newPriceValue) || newPriceValue < 0) {
            displayNotification('Please enter a valid price.', 'error');
            return;
        }

        button.disabled = true;
        button.textContent = '...';

        try {
            const result = await apiCall('update_item_price.php', 'POST', {
                item_id: parseInt(itemId),
                new_price: newPriceValue
            });

            if (result && result.status === 'success') {
                displayNotification(result.message || 'Price updated successfully!', 'success');

                // Update the price in the UI
                if (priceEl) {
                    priceEl.textContent = formatMarketCurrency(newPriceValue);
                }

                // Re-evaluate affordability for this item
                const currentBalance = parseFloat(currentBalanceSpan.textContent);
                if (!isNaN(currentBalance)) {
                    const canAfford = currentBalance >= newPriceValue;
                    const buyButton = card.querySelector('.buy-button');
                    if (buyButton) {
                        buyButton.disabled = !canAfford;
                        buyButton.title = canAfford ? `Buy ${itemName}` : "Insufficient funds";
                    }
                }
            } else {
                displayNotification(result.message || 'Failed to update price.', 'error');
            }
        } catch (error) {
            displayNotification('Error updating price: ' + error.message, 'error');
        } finally {
            button.disabled = false;
            button.textContent = '✏️';
        }
    }

    // --- DELETE ITEM HANDLING ---
    async function handleDeleteClick(event) {
        const button = event.target;
        const card = button.closest('.item-card');
        const itemId = card?.dataset.itemId;
        if (!itemId) return;

        const itemName = card.querySelector('.item-name')?.textContent || `Item #${itemId}`;

        // Show confirmation dialog
        modal.show({
            title: 'Confirm Delete',
            message: `Are you sure you want to delete "${itemName}"? This cannot be undone.`,
            confirmText: 'Delete',
            type: 'confirm',
            onConfirm: async () => {
                try {
                    const result = await apiCall('delete_item.php', 'POST', {
                        item_id: parseInt(itemId)
                    });

                    if (result && result.status === 'success') {
                        // Remove the card from the UI
                        card.remove();
                        displayNotification('Item deleted successfully!', 'success');

                        // Check if there are any items left
                        if (itemGrid.children.length === 0) {
                            emptyMessageEl.style.display = 'block';
                            itemGrid.style.display = 'none';
                        }
                    } else {
                        displayNotification(result.message || 'Failed to delete item.', 'error');
                    }
                } catch (error) {
                    displayNotification('Error deleting item: ' + error.message, 'error');
                }
            }
        });
    }

    // --- Fetch and Display Marketplace Data ---
    async function fetchMarketplaceData() {
        loadingMessageEl.style.display = 'block';
        emptyMessageEl.style.display = 'none';
        itemGrid.style.display = 'none';

        try {
            const data = await apiCall('get_marketplace_data.php');
            renderMarketplace(data);
        } catch (error) {
            loadingMessageEl.textContent = `Error loading marketplace: ${error.message}`;
        }
    }

    // Setup delegated event handlers for item buttons
    itemGrid.addEventListener('click', (event) => {
        if (event.target.classList.contains('edit-button')) {
            handleEditClick(event);
        } else if (event.target.classList.contains('delete-button')) {
            handleDeleteClick(event);
        }
    });

    // Initial fetch of marketplace data
    fetchMarketplaceData();
});

// Expose updateAffordability globally (already exists, just confirming)
// window.updateAffordability = updateAffordability; // This line was already present and is now above