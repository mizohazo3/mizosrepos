// basket.js - Shopping basket functionality for marketplace

document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';
    const DEBUG_MODE = true; // Enable debug mode for troubleshooting
    const USER_TOKEN_KEY = 'timer_app_user_token'; // Key for localStorage user token

    // --- Initialize or retrieve user token ---
    let userToken = localStorage.getItem(USER_TOKEN_KEY);
    if (!userToken) {
        // Generate a new user token if not found in localStorage
        userToken = generateUniqueToken();
        localStorage.setItem(USER_TOKEN_KEY, userToken);
    }
    
    // Generate a unique token for the user
    function generateUniqueToken() {
        // Generate a random string with timestamp for uniqueness
        const timestamp = new Date().getTime();
        const randomStr = Math.random().toString(36).substring(2, 15);
        return `${timestamp}-${randomStr}`;
    }

    // --- DOM Elements ---
    const basketButton = document.getElementById('view-basket');
    const basketCount = document.getElementById('basket-count');
    const basketSummary = document.getElementById('basket-summary');
    const basketItems = document.getElementById('basket-items');
    const basketTotalAmount = document.getElementById('basket-total-amount');
    const checkoutButton = document.getElementById('checkout-button');
    const clearBasketButton = document.getElementById('clear-basket');
    const closeBasketButton = document.getElementById('close-basket');
    const basketItemTemplate = document.getElementById('basket-item-template');
    const checkoutModalTemplate = document.getElementById('checkout-modal-template');
    const currentBalanceSpan = document.getElementById('current-balance');
    const itemGrid = document.getElementById('item-grid');
    const container = document.querySelector('.container');

    // --- Basket State ---
    let basket = {
        items: [],
        total: 0
    };

    // --- Event Listeners ---

    // Add to basket button (delegated to item grid)
    itemGrid.addEventListener('click', (event) => {
        if (event.target.classList.contains('add-to-basket-button')) {
            handleAddToBasket(event);
        }
    });

    // Listen for custom add-to-basket events (from image clicks)
    document.addEventListener('add-to-basket', (event) => {
        // Extract item data from event
        const itemData = event.detail;
        
        // Process the add to basket action
        addItemToBasket(
            itemData.itemId,
            itemData.name,
            parseFloat(itemData.price),
            itemData.image_url
        );
    });

    // Toggle basket visibility
    basketButton.addEventListener('click', showBasket);
    closeBasketButton.addEventListener('click', hideBasket);

    // Checkout button
    checkoutButton.addEventListener('click', handleCheckout);

    // Clear basket button
    clearBasketButton.addEventListener('click', () => {
        // Clear without confirmation
        clearBasket();
        clearBasketFromServer();
        displayNotification('Basket cleared', 'info');
    });

    // Remove item from basket (delegated to basket items)
    basketItems.addEventListener('click', (event) => {
        if (event.target.classList.contains('remove-from-basket')) {
            const basketItem = event.target.closest('.basket-item');
            if (basketItem) {
                const itemId = basketItem.dataset.itemId;
                removeFromBasket(itemId);
            }
        }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && basketSummary.style.display !== 'none') {
            hideBasket();
        }
    });

    // --- Functions ---

    // Format currency for display
    function formatCurrency(amount) {
        const num = parseFloat(amount);
        return isNaN(num) ? '$--.--' : '$' + num.toFixed(2);
    }

    // Show basket
    function showBasket() {
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'basket-overlay';
        overlay.id = 'basket-overlay';
        overlay.addEventListener('click', hideBasket);
        document.body.appendChild(overlay);
        
        // Show basket
        basketSummary.style.display = 'block';
        
        // Set focus on close button
        setTimeout(() => closeBasketButton.focus(), 50);
    }
    
    // Hide basket
    function hideBasket() {
        basketSummary.style.display = 'none';
        const overlay = document.getElementById('basket-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Function to add an item to basket (used by both click handlers and custom events)
    function addItemToBasket(itemId, itemName, itemPrice, imageUrl) {
        const currentBalance = parseFloat(currentBalanceSpan.textContent);

        // Check if user can afford the item
        if (isNaN(currentBalance) || itemPrice > currentBalance) {
            displayNotification('Insufficient funds to add this item', 'error');
            return;
        }

        // Check if adding this would exceed balance
        if (basket.total + itemPrice > currentBalance) {
            displayNotification('Adding this item would exceed your balance', 'error');
            return;
        }

        // Add item to basket
        const item = {
            id: itemId,
            name: itemName,
            price: itemPrice,
            image_url: imageUrl,
            quantity: 1
        };

        // Check if item already in basket
        const existingItemIndex = basket.items.findIndex(item => item.id === itemId);
        
        if (existingItemIndex !== -1) {
            // Increment quantity instead of showing a notification
            basket.items[existingItemIndex].quantity += 1;
            // Update the price to reflect the new quantity
            basket.total += itemPrice;
            displayNotification(`Added another "${itemName}" to basket`, 'success');
        } else {
            // Add new item to basket
            basket.items.push(item);
            basket.total += itemPrice;
            displayNotification(`Added "${itemName}" to basket`, 'success');
        }

        // Update UI
        updateBasketDisplay();
        
        // Save basket to server
        saveBasketToServer();
        
        // Show basket counter animation
        animateBasketCounter();
    }

    // Handle add to basket button click
    function handleAddToBasket(event) {
        const button = event.target;
        const card = button.closest('.item-card');
        if (!card) return;

        const itemId = card.dataset.itemId;
        const itemName = card.querySelector('.item-name').textContent;
        const itemPrice = parseFloat(card.querySelector('.item-price').textContent.replace('$', ''));
        const imageUrl = card.querySelector('.item-image')?.src || '';

        addItemToBasket(itemId, itemName, itemPrice, imageUrl);
    }
    
    // Animate basket counter
    function animateBasketCounter() {
        basketCount.style.transform = 'scale(1.3)';
        basketCount.style.transition = 'transform 0.3s ease';
        setTimeout(() => {
            basketCount.style.transform = 'scale(1)';
        }, 300);
    }

    // Update basket display
    function updateBasketDisplay() {
        // Update basket count - now count total quantity of items
        let totalQuantity = 0;
        basket.items.forEach(item => {
            totalQuantity += item.quantity || 1;
        });
        basketCount.textContent = totalQuantity;

        // Update basket items
        basketItems.innerHTML = '';

        if (basket.items.length === 0) {
            const emptyMessage = document.createElement('p');
            emptyMessage.className = 'empty-basket-message';
            emptyMessage.textContent = 'Your basket is empty';
            basketItems.appendChild(emptyMessage);
            
            // Disable checkout button
            checkoutButton.disabled = true;
        } else {
            // Enable checkout button
            checkoutButton.disabled = false;
            
            // Add items to basket display
            basket.items.forEach(item => {
                if (!basketItemTemplate?.content) return;

                const templateContent = basketItemTemplate.content.cloneNode(true);
                const basketItem = templateContent.querySelector('.basket-item');
                
                basketItem.dataset.itemId = item.id;
                
                // Add item icon if available
                const basketItemIcon = templateContent.querySelector('.basket-item-icon');
                if (basketItemIcon && item.image_url) {
                    basketItemIcon.src = item.image_url;
                    basketItemIcon.alt = item.name;
                    basketItemIcon.style.display = 'block';
                } else if (basketItemIcon) {
                    basketItemIcon.style.display = 'none';
                }
                
                // Display item name with quantity if more than 1
                const itemNameElement = templateContent.querySelector('.basket-item-name');
                if (item.quantity && item.quantity > 1) {
                    itemNameElement.textContent = `${item.name} (x${item.quantity})`;
                } else {
                    itemNameElement.textContent = item.name;
                }
                
                // Display price for the total quantity
                const itemPrice = (item.quantity || 1) * item.price;
                templateContent.querySelector('.basket-item-price').textContent = formatCurrency(itemPrice);
                
                basketItems.appendChild(basketItem);
            });
        }

        // Update total
        basketTotalAmount.textContent = formatCurrency(basket.total);
        
        // Update add buttons
        updateAddButtons();
    }
    
    // Update add-to-basket buttons based on current balance
    function updateAddButtons() {
        const currentBalance = parseFloat(currentBalanceSpan.textContent);
        if (isNaN(currentBalance)) return;
        
        const remainingBalance = currentBalance - basket.total;
        const allAddButtons = document.querySelectorAll('.add-to-basket-button');
        
        allAddButtons.forEach(button => {
            const card = button.closest('.item-card');
            if (!card) return;
            
            const priceEl = card.querySelector('.item-price');
            if (!priceEl) return;
            
            const price = parseFloat(priceEl.textContent.replace('$', ''));
            const canAfford = !isNaN(price) && remainingBalance >= price;
            
            button.disabled = !canAfford;
            button.title = canAfford 
                ? `Add ${card.querySelector('.item-name').textContent} to basket` 
                : "Cannot afford this item";
        });
    }

    // Remove item from basket
    function removeFromBasket(itemId) {
        const itemIndex = basket.items.findIndex(item => item.id === itemId);
        
        if (itemIndex !== -1) {
            const item = basket.items[itemIndex];
            
            // If quantity is greater than 1, just decrement it
            if (item.quantity && item.quantity > 1) {
                item.quantity -= 1;
                basket.total -= item.price;
                updateBasketDisplay();
                saveBasketToServer();
                displayNotification(`Removed one "${item.name}" from basket`, 'info');
            } else {
                // Otherwise remove the item completely
                basket.total -= item.price;
                basket.items.splice(itemIndex, 1);
                updateBasketDisplay();
                saveBasketToServer();
                displayNotification(`Removed "${item.name}" from basket`, 'info');
            }
        }
    }

    // Clear basket
    function clearBasket() {
        basket.items = [];
        basket.total = 0;
        updateBasketDisplay();
    }
    
    // Debug logger
    function logDebug(message, data = null) {
        if (!DEBUG_MODE) return;
        
        const formattedMessage = data 
            ? `🧺 Basket: ${message} | Data: ${JSON.stringify(data)}`
            : `🧺 Basket: ${message}`;
            
        console.log(formattedMessage);
    }
    
    // Save basket to server
    async function saveBasketToServer() {
        try {
            // Convert items to the format expected by the server
            // We need to expand items with quantity > 1 into multiple items
            const itemsForServer = [];
            
            basket.items.forEach(item => {
                const quantity = item.quantity || 1;
                for (let i = 0; i < quantity; i++) {
                    itemsForServer.push({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        image_url: item.image_url
                    });
                }
            });
            
            logDebug('Saving basket to server', { items: itemsForServer, userToken });
            
            const response = await apiCall('save_basket.php', 'POST', {
                items: itemsForServer,
                user_token: userToken
            });
            
            logDebug('Save basket response', response);
            
            // If the server sent back a user token, update ours
            if (response && response.user_token) {
                userToken = response.user_token;
                localStorage.setItem(USER_TOKEN_KEY, userToken);
            }
        } catch (error) {
            // Log the error with the debug logger but continue silently for UX
            logDebug('Error saving basket', { error: error.message });
        }
    }
    
    // Load basket from server
    async function loadBasketFromServer() {
        try {
            logDebug('Loading basket from server', { userToken });
            
            // Use GET method for loading with user token as query parameter
            const result = await apiCall(`load_basket.php?user_token=${encodeURIComponent(userToken)}`, 'GET'); 
            logDebug('Load basket response', result);
            
            if (result && result.status === 'success' && Array.isArray(result.items)) {
                // Group items by ID to handle quantities
                const groupedItems = {};
                
                result.items.forEach(item => {
                    if (!groupedItems[item.id]) {
                        groupedItems[item.id] = {
                            ...item,
                            quantity: 1
                        };
                    } else {
                        groupedItems[item.id].quantity += 1;
                    }
                });
                
                // Convert back to array
                basket.items = Object.values(groupedItems);
                basket.total = result.total || 0;
                updateBasketDisplay();
                
                logDebug('Basket loaded from server', { 
                    itemCount: basket.items.length, 
                    total: basket.total 
                });
                
                if (basket.items.length > 0) {
                    // Removed notification on load as requested
                }
                
                // If the server sent back a user token, update ours
                if (result.user_token) {
                    userToken = result.user_token;
                    localStorage.setItem(USER_TOKEN_KEY, userToken);
                }
            } else {
                logDebug('No items found or invalid response', result);
            }
        } catch (error) {
            logDebug('Error loading basket', { error: error.message });
            // Continue silently - local basket is the fallback
        }
    }
    
    // Clear basket from server
    async function clearBasketFromServer() {
        try {
            logDebug('Clearing basket from server', { userToken });
            const result = await apiCall('clear_basket.php', 'POST', { user_token: userToken });
            logDebug('Clear basket response', result);
        } catch (error) {
            logDebug('Error clearing basket', { error: error.message });
            // Continue silently
        }
    }

    // Handle checkout
    function handleCheckout() {
        if (basket.items.length === 0) {
            displayNotification('Your basket is empty', 'error');
            return;
        }

        const currentBalance = parseFloat(currentBalanceSpan.textContent);
        
        if (isNaN(currentBalance)) {
            displayNotification('Could not determine current balance', 'error');
            return;
        }

        if (basket.total > currentBalance) {
            displayNotification('Insufficient funds for checkout', 'error');
            return;
        }

        // Show checkout modal
        showCheckoutModal(currentBalance);
    }

    // Show checkout modal
    function showCheckoutModal(currentBalance) {
        // Clone template content
        const templateContent = checkoutModalTemplate.content.cloneNode(true);
        const modalContent = templateContent.querySelector('.checkout-modal-content');
        
        // Populate checkout items
        const checkoutItems = modalContent.querySelector('.checkout-items');
        basket.items.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'basket-item';
            
            let imageHtml = '';
            if (item.image_url) {
                imageHtml = `<img src="${item.image_url}" alt="${item.name}" class="checkout-item-icon">`;
            }
            
            // Calculate item total price based on quantity
            const quantity = item.quantity || 1;
            const itemTotalPrice = item.price * quantity;
            
            // Add quantity to item name if more than 1
            let displayName = item.name;
            if (quantity > 1) {
                displayName = `${item.name} (x${quantity})`;
            }
            
            itemEl.innerHTML = `
                <div class="basket-item-info">
                    ${imageHtml}
                    <span class="basket-item-name">${displayName}</span>
                    <span class="basket-item-price">${formatCurrency(itemTotalPrice)}</span>
                </div>
            `;
            checkoutItems.appendChild(itemEl);
        });
        
        // Update summary
        const remainingBalance = currentBalance - basket.total;
        modalContent.querySelector('.checkout-total-amount').textContent = formatCurrency(basket.total);
        modalContent.querySelector('.checkout-current-balance').textContent = currentBalance.toFixed(2);
        modalContent.querySelector('.checkout-remaining-balance').textContent = remainingBalance.toFixed(2);
        
        // Add event listeners
        const confirmButton = modalContent.querySelector('.confirm-purchase');
        const cancelButton = modalContent.querySelector('.cancel-purchase');
        
        // Hide basket when showing modal
        hideBasket();
        
        // Show modal
        modal.show({
            title: 'Complete Purchase',
            content: modalContent,
            onOpen: () => {
                // Add event listeners
                confirmButton.addEventListener('click', processPurchase);
                cancelButton.addEventListener('click', () => modal.close());
            }
        });
    }

    // Process purchase
    async function processPurchase() {
        // Disable buttons
        const confirmButton = document.querySelector('.confirm-purchase');
        const cancelButton = document.querySelector('.cancel-purchase');
        
        if (confirmButton) confirmButton.disabled = true;
        if (cancelButton) cancelButton.disabled = true;
        
        try {
            // Create payload with expanded items for quantities
            const itemIds = [];
            
            // Expand items with quantities
            basket.items.forEach(item => {
                const quantity = item.quantity || 1;
                for (let i = 0; i < quantity; i++) {
                    itemIds.push(item.id);
                }
            });
            
            // Call API
            const payload = {
                item_ids: itemIds,
                user_token: userToken // Include the user token for basket identification
            };
            
            const result = await apiCall('batch_purchase.php', 'POST', payload);
            
            if (result && result.status === 'success') {
                displayNotification(result.message || 'Purchase successful!', 'success');
                
                // Update balance display
                if (currentBalanceSpan && result.new_balance) {
                    currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(4);
                }
                
                // Clear basket
                clearBasket();
                
                // Clear server basket
                clearBasketFromServer();
                
                // Close modal
                modal.close();
                
                // Update affordability for all items
                if (result.new_balance) {
                    updateAffordability(result.new_balance);
                }
            } else {
                displayNotification(result.message || 'Purchase failed', 'error');
                
                // Re-enable buttons
                if (confirmButton) confirmButton.disabled = false;
                if (cancelButton) cancelButton.disabled = false;
            }
        } catch (error) {
            displayNotification(`Purchase failed: ${error.message}`, 'error');
            
            // Re-enable buttons
            if (confirmButton) confirmButton.disabled = false;
            if (cancelButton) cancelButton.disabled = false;
        }
    }

    // Share user token functionality
    function showShareTokenDialog() {
        // Create modal content for sharing
        const modalContent = document.createElement('div');
        modalContent.className = 'share-token-modal';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'text';
        tokenInput.value = userToken;
        tokenInput.readOnly = true;
        tokenInput.className = 'share-token-input';
        tokenInput.style.width = '100%';
        tokenInput.style.padding = '10px';
        tokenInput.style.marginBottom = '10px';
        
        const copyButton = document.createElement('button');
        copyButton.textContent = 'Copy Token';
        copyButton.className = 'share-token-copy-btn';
        copyButton.style.padding = '5px 10px';
        copyButton.style.marginBottom = '15px';
        copyButton.addEventListener('click', () => {
            tokenInput.select();
            document.execCommand('copy');
            displayNotification('Token copied to clipboard', 'success');
        });
        
        const instructions = document.createElement('p');
        instructions.textContent = 'Share this token with yourself on another device to access the same basket. On the other device, click "Enter Token" and paste this value.';
        instructions.style.marginBottom = '15px';
        
        modalContent.appendChild(instructions);
        modalContent.appendChild(tokenInput);
        modalContent.appendChild(copyButton);
        
        // Show in modal
        modal.show({
            title: 'Share Basket Across Devices',
            content: modalContent
        });
    }
    
    // Enter token dialog
    function showEnterTokenDialog() {
        const modalContent = document.createElement('div');
        modalContent.className = 'enter-token-modal';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'text';
        tokenInput.placeholder = 'Paste user token here';
        tokenInput.className = 'enter-token-input';
        tokenInput.style.width = '100%';
        tokenInput.style.padding = '10px';
        tokenInput.style.marginBottom = '10px';
        
        const submitButton = document.createElement('button');
        submitButton.textContent = 'Use This Token';
        submitButton.className = 'enter-token-submit-btn';
        submitButton.style.padding = '5px 10px';
        submitButton.style.marginBottom = '15px';
        
        const instructions = document.createElement('p');
        instructions.textContent = 'Enter a token from another device to access the same basket.';
        instructions.style.marginBottom = '15px';
        
        submitButton.addEventListener('click', () => {
            const newToken = tokenInput.value.trim();
            if (newToken) {
                // Save the new token and reload basket
                userToken = newToken;
                localStorage.setItem(USER_TOKEN_KEY, userToken);
                loadBasketFromServer();
                modal.close();
                displayNotification('Basket token updated. Now sharing basket across devices.', 'success');
            } else {
                displayNotification('Please enter a valid token', 'error');
            }
        });
        
        modalContent.appendChild(instructions);
        modalContent.appendChild(tokenInput);
        modalContent.appendChild(submitButton);
        
        // Show in modal
        modal.show({
            title: 'Enter Shared Basket Token',
            content: modalContent
        });
    }

    // API call function
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = API_BASE_URL + endpoint;
        logDebug(`API Call: ${method} ${url}`, body);
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(url, options);
            const data = await response.json();
            logDebug(`API Response: ${method} ${url}`, data);
            return data;
        } catch (error) {
            logDebug(`API Error: ${method} ${url}`, { error: error.message });
            throw error;
        }
    }

    // Update affordability for all items
    function updateAffordability(currentBalance) {
        // Update buy buttons (from marketplace.js)
        if (typeof window.updateAffordability === 'function') {
            window.updateAffordability(currentBalance);
        }
        
        // Also update add to basket buttons
        updateAddButtons();
    }

    // --- Initialization ---
    // Initial update of basket display (starts empty)
    updateBasketDisplay(); 
    // Load basket from server on initial page load
    loadBasketFromServer(); 

    // Initialize
    logDebug('Basket module initialized', { userToken });
    
    // Create basket control buttons
    function addBasketControls() {
        // Create container for controls
        const basketControls = document.createElement('div');
        basketControls.className = 'basket-cross-device-controls';
        basketControls.style.margin = '10px 0';
        basketControls.style.display = 'flex';
        basketControls.style.gap = '10px';
        basketControls.style.justifyContent = 'center';
        
        // Share button
        const shareButton = document.createElement('button');
        shareButton.textContent = 'Share Basket';
        shareButton.className = 'share-basket-btn';
        shareButton.addEventListener('click', showShareTokenDialog);
        shareButton.style.padding = '5px 10px';
        shareButton.style.borderRadius = '4px';
        shareButton.style.background = '#4CAF50';
        shareButton.style.color = 'white';
        shareButton.style.border = 'none';
        shareButton.style.cursor = 'pointer';
        
        // Enter token button
        const enterTokenButton = document.createElement('button');
        enterTokenButton.textContent = 'Enter Token';
        enterTokenButton.className = 'enter-token-btn';
        enterTokenButton.addEventListener('click', showEnterTokenDialog);
        enterTokenButton.style.padding = '5px 10px';
        enterTokenButton.style.borderRadius = '4px';
        enterTokenButton.style.background = '#2196F3';
        enterTokenButton.style.color = 'white';
        enterTokenButton.style.border = 'none';
        enterTokenButton.style.cursor = 'pointer';
        
        // Add buttons to container
        basketControls.appendChild(shareButton);
        basketControls.appendChild(enterTokenButton);
        
        // Add to basket summary - find a good location to insert
        if (basketSummary) {
            // Try to find the basket actions container or use basketSummary
            const basketActionsContainer = basketSummary.querySelector('.basket-actions') || basketSummary;
            basketActionsContainer.parentNode.insertBefore(basketControls, basketActionsContainer);
        }
    }
    
    // Add the controls
    // Delay slightly to ensure the basket UI is fully loaded
    setTimeout(addBasketControls, 100);
    
    // Create a diagnostic button for admins/developers
    if (DEBUG_MODE) {
        const diagnosticBtn = document.createElement('button');
        diagnosticBtn.className = 'diagnostic-btn';
        diagnosticBtn.textContent = 'Basket Diagnostics';
        diagnosticBtn.style.position = 'fixed';
        diagnosticBtn.style.bottom = '10px';
        diagnosticBtn.style.right = '10px';
        diagnosticBtn.style.zIndex = '9999';
        diagnosticBtn.style.padding = '5px 10px';
        diagnosticBtn.style.background = '#ffcc00';
        diagnosticBtn.style.border = 'none';
        diagnosticBtn.style.borderRadius = '4px';
        diagnosticBtn.style.cursor = 'pointer';
        
        diagnosticBtn.addEventListener('click', async () => {
            console.group('🧺 Basket Diagnostics');
            console.log('Current Basket State:', JSON.parse(JSON.stringify(basket)));
            console.log('User Token:', userToken);
            
            try {
                const sessionResponse = await fetch('api/get_session_info.php');
                const sessionData = await sessionResponse.json();
                console.log('Session Info:', sessionData);
            } catch (error) {
                console.error('Error fetching session info:', error);
            }
            
            console.groupEnd();
        });
        
        document.body.appendChild(diagnosticBtn);
    }

});
