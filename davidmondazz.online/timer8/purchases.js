// purchases.js - Recent purchases and refund functionality with improved live refresh

document.addEventListener('DOMContentLoaded', () => {
    // --- Configuration ---
    const API_BASE_URL = 'api/';
    const REFUND_WINDOW_HOURS = 24; // 24 hour refund window
    const PURCHASES_REFRESH_INTERVAL = 3000; // 3 seconds auto-refresh (reduced from 10s for more live updates)
    const PURCHASE_ANIMATION_DURATION = 300; // ms

    // --- DOM Elements ---
    const purchasesContainer = document.getElementById('recent-purchases');
    const purchasesLoading = document.getElementById('purchases-loading');
    const purchasesEmpty = document.getElementById('purchases-empty');
    const currentBalanceSpan = document.getElementById('current-balance');
    const notificationContainer = document.getElementById('notification-container');

    // --- State ---
    let purchases = [];
    let refreshInterval = null;
    let lastRefreshTime = 0;
    let isRefreshing = false;

    // --- Utility Functions ---
    function displayNotification(message, type = 'info', duration = 3000) {
        
        // Check if using global notification system first
        if (typeof window.displayNotification === 'function') {
            return window.displayNotification(message, type, duration);
        }
        
        // Fallback to local implementation
        if (!notificationContainer) return;
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} show`;
        notification.textContent = message;
        notificationContainer.appendChild(notification);
        
        // Remove after duration
        setTimeout(() => {
            notification.classList.remove('show');
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    function formatCurrency(amount) {
        const num = parseFloat(amount);
        return isNaN(num) ? '$--.--' : '$' + num.toFixed(2);
    }

    // --- Event Listeners ---
    
    // Delegate refund button clicks
    if (purchasesContainer) {
        purchasesContainer.addEventListener('click', (event) => {
            const target = event.target;
            
            // Check if refund button or its parent
            if (target.classList.contains('refund-button') || 
                target.parentElement.classList.contains('refund-button')) {
                
                const button = target.classList.contains('refund-button') ? 
                               target : target.parentElement;
                               
                const purchaseItem = button.closest('.purchase-item');
                
                if (purchaseItem) {
                    const purchaseId = purchaseItem.dataset.purchaseId;
                    confirmRefund(purchaseId);
                }
            }
        });
    }

    // --- Functions ---
    
    // Load recent purchases with better update handling
    async function loadPurchases(forceRefresh = false) {
        if (!purchasesContainer || !purchasesLoading || !purchasesEmpty) {
            return;
        }
        
        // Prevent multiple concurrent refreshes
        if (isRefreshing) return;
        isRefreshing = true;
        
        // Don't refresh too frequently unless forced
        const now = Date.now();
        if (!forceRefresh && (now - lastRefreshTime < 2000)) { // Reduced threshold for quicker updates
            isRefreshing = false;
            return;
        }
        
        lastRefreshTime = now;
        
        // Show loading only on first load or when explicitly forced by user
        if (purchases.length === 0 || (forceRefresh && purchasesContainer.innerHTML === '')) {
            purchasesLoading.style.display = 'block';
        }
        
        try {
            const response = await fetch(`${API_BASE_URL}get_purchases.php?_nocache=${now}`);
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result && result.status === 'success') {
                const newPurchases = result.purchases || [];
                
                // Check if there are any changes
                const hasNewPurchases = !areArraysEqual(purchases, newPurchases, 'id');
                
                if (hasNewPurchases) {
                    // Update our purchases array
                    purchases = newPurchases;
                    
                    if (purchases.length === 0) {
                        purchasesEmpty.style.display = 'block';
                        purchasesContainer.innerHTML = '';
                    } else {
                        purchasesEmpty.style.display = 'none';
                        displayPurchases();
                    }
                    
                    // Only show refresh notification when forced by user click
                    if (forceRefresh && purchases.length > 0) {
                        displayNotification('Purchase list refreshed', 'info');
                    }
                }
            } else {
                if (forceRefresh) {
                    displayNotification('Failed to load purchases', 'error');
                }
                purchasesEmpty.style.display = 'block';
            }
        } catch (error) {
            if (forceRefresh) {
                displayNotification(`Error: ${error.message}`, 'error');
            }
        } finally {
            purchasesLoading.style.display = 'none';
            isRefreshing = false;
        }
    }
    
    // Display purchases
    function displayPurchases() {
        if (!purchasesContainer) return;
        
        purchasesContainer.innerHTML = '';
        
        if (purchases.length === 0) {
            purchasesEmpty.style.display = 'block';
            return;
        }
        
        purchases.forEach(purchase => {
            // Create purchase item
            const purchaseItem = document.createElement('div');
            purchaseItem.className = 'purchase-item';
            purchaseItem.dataset.purchaseId = purchase.id;
            
            // Calculate time ago
            const purchaseTime = new Date(purchase.timestamp);
            const timeAgoText = getTimeAgo(purchaseTime);
            const isRefundable = isWithinRefundWindow(purchaseTime);
            
            // Create purchase content
            purchaseItem.innerHTML = `
                <div class="purchase-item-content">
                    <div class="purchase-item-info">
                        <img src="${purchase.image_url || 'https://via.placeholder.com/40/61dafb/FFFFFF?text=Item'}" 
                             alt="${purchase.item_name}" 
                             class="purchase-item-icon">
                        <div class="purchase-item-details">
                            <span class="purchase-item-name">${purchase.item_name}</span>
                            <span class="purchase-item-price">${formatCurrency(purchase.price_paid)}</span>
                        </div>
                    </div>
                    <div class="purchase-item-meta">
                        <span class="purchase-time-ago" title="${purchase.formatted_time || ''}">${timeAgoText}</span>
                        <button class="refund-button" ${!isRefundable ? 'disabled' : ''} 
                                title="${isRefundable ? 'Refund this purchase' : 'Refund period expired (24 hours)'}"
                                data-purchase-id="${purchase.id}">
                            <span>Refund</span>
                        </button>
                    </div>
                </div>
            `;
            
            purchasesContainer.appendChild(purchaseItem);
            
            // Add click handler directly to button for extra reliability
            const refundButton = purchaseItem.querySelector('.refund-button');
            if (refundButton) {
                refundButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.dataset.purchaseId || purchaseItem.dataset.purchaseId;
                    if (id && !this.disabled) {
                        confirmRefund(id);
                    }
                });
            }
            
            // Add entrance animation with delay
            setTimeout(() => {
                purchaseItem.classList.add('visible');
            }, 50);
        });
    }
    
    // Check if purchase is within refund window
    function isWithinRefundWindow(purchaseTime) {
        const now = new Date();
        const timeDiff = now - purchaseTime; // in milliseconds
        const hoursDiff = timeDiff / (1000 * 60 * 60);
        
        return hoursDiff <= REFUND_WINDOW_HOURS;
    }
    
    // Get time ago text
    function getTimeAgo(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        
        if (diffSec < 60) {
            return 'just now';
        } else if (diffMin < 60) {
            return diffMin === 1 ? '1 minute ago' : `${diffMin} minutes ago`;
        } else if (diffHour < 24) {
            return diffHour === 1 ? '1 hour ago' : `${diffHour} hours ago`;
        } else {
            return diffDay === 1 ? '1 day ago' : `${diffDay} days ago`;
        }
    }
    
    // Confirm refund
    function confirmRefund(purchaseId) {
        // Convert purchaseId to string for consistent comparison
        const idStr = String(purchaseId);
        
        // Find the purchase using string comparison
        const purchase = purchases.find(p => String(p.id) === idStr);
        
        if (!purchase) {
            displayNotification('Error: Purchase not found', 'error');
            return;
        }
        
        
        // Use simple confirm dialog if modal is not available
        if (typeof modal === 'undefined' || !modal || !modal.show) {
            if (confirm(`Are you sure you want to refund "${purchase.item_name}" for ${formatCurrency(purchase.price_paid)}?`)) {
                processRefund(idStr);
            }
            return;
        }
        
        // Use the modal API
        modal.show({
            title: 'Confirm Refund',
            message: `Are you sure you want to refund "${purchase.item_name}" for ${formatCurrency(purchase.price_paid)}?`,
            confirmText: 'Yes, Refund',
            cancelText: 'Cancel',
            type: 'warning',
            onConfirm: () => {
                processRefund(idStr);
            }
        });
    }
    
    // Process refund
    async function processRefund(purchaseId) {
        
        // Find purchase item in DOM
        const purchaseItem = document.querySelector(`.purchase-item[data-purchase-id="${purchaseId}"]`);
        if (!purchaseItem) {
        }
        
        if (purchaseItem) {
            // Disable refund button
            const refundButton = purchaseItem.querySelector('.refund-button');
            if (refundButton) {
                refundButton.disabled = true;
                refundButton.innerHTML = '<span>Processing...</span>';
            }
            
            // Visual feedback
            purchaseItem.classList.add('refunding');
        }
        
        try {
            
            const response = await fetch(`${API_BASE_URL}refund_purchase.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ purchase_id: purchaseId })
            });
            
            const result = await response.json();
            
            if (response.ok && result.status === 'success') {
                // Show success notification
                displayNotification(
                    `Refunded ${result.item_name} for ${formatCurrency(result.refunded_amount)}`, 
                    'success'
                );
                
                // Update balance display
                if (currentBalanceSpan && result.new_balance) {
                    currentBalanceSpan.textContent = parseFloat(result.new_balance).toFixed(2);
                }
                
                // Remove item with animation
                if (purchaseItem) {
                    purchaseItem.classList.add('removing');
                    setTimeout(() => {
                        purchaseItem.remove();
                        
                        // Check if there are any purchases left
                        if (purchasesContainer.children.length === 0) {
                            purchasesEmpty.style.display = 'block';
                        }
                    }, PURCHASE_ANIMATION_DURATION);
                }
                
                // Force refresh after a short delay
                setTimeout(() => {
                    loadPurchases(true);
                }, PURCHASE_ANIMATION_DURATION + 100);
                
                // Update affordability if the function exists
                if (typeof window.updateAffordability === 'function') {
                    window.updateAffordability(result.new_balance);
                }
            } else {
                // Show error notification
                displayNotification(result.message || 'Refund failed', 'error');
                
                // Reset refund button
                if (purchaseItem) {
                    purchaseItem.classList.remove('refunding');
                    const refundButton = purchaseItem.querySelector('.refund-button');
                    if (refundButton) {
                        refundButton.disabled = false;
                        refundButton.innerHTML = '<span>Refund</span>';
                    }
                }
            }
        } catch (error) {
            displayNotification(`Refund failed: ${error.message}`, 'error');
            
            // Reset refund button
            if (purchaseItem) {
                purchaseItem.classList.remove('refunding');
                const refundButton = purchaseItem.querySelector('.refund-button');
                if (refundButton) {
                    refundButton.disabled = false;
                    refundButton.innerHTML = '<span>Refund</span>';
                }
            }
        }
    }
    
    // Start automatic refresh timer
    function startAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        
        refreshInterval = setInterval(() => {
            loadPurchases();
        }, PURCHASES_REFRESH_INTERVAL);
        
    }
    
    // Helper to efficiently compare arrays of objects by a key
    function areArraysEqual(arr1, arr2, idKey) {
        if (arr1.length !== arr2.length) return false;
        
        // Create a map of IDs for quick lookup
        const idMap = new Map();
        arr1.forEach(item => idMap.set(String(item[idKey]), item));
        
        // Check if all items in arr2 exist in arr1 with the same ID
        return arr2.every(item => idMap.has(String(item[idKey])));
    }
    
    // Initialize purchases
    function init() {
        // Add CSS for animations and refund button
        const style = document.createElement('style');
        style.textContent = `
            .purchase-item {
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.3s ease, transform 0.3s ease, background-color 0.3s ease;
            }
            
            .purchase-item.visible {
                opacity: 1;
                transform: translateY(0);
            }
            
            .purchase-item.refunding {
                background-color: rgba(255, 204, 0, 0.1);
            }
            
            .purchase-item.removing {
                opacity: 0;
                transform: translateX(50px);
                pointer-events: none;
            }
            
            .refund-button {
                background-color: #e44c4c;
                color: white;
                border: none;
                border-radius: 4px;
                padding: 6px 12px;
                font-size: 0.9rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease-in-out;
                min-width: 70px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                position: relative;
                overflow: hidden;
            }
            
            .refund-button:before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.1);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .refund-button:hover:not(:disabled):before {
                transform: translateX(0);
            }
            
            .refund-button:hover:not(:disabled) {
                background-color: #d12f2f;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            }
            
            .refund-button:active:not(:disabled) {
                transform: translateY(1px);
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            }
            
            .refund-button:disabled {
                background-color: #7a4c4c;
                cursor: not-allowed;
                opacity: 0.6;
                box-shadow: none;
            }
            
            /* New styles for the refund button */
            .refund-button {
                background-color: #ff5252;
                border-radius: 20px;
                padding: 6px 14px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 0.8rem;
                box-shadow: 0 3px 5px rgba(255, 82, 82, 0.3);
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            
            .refund-button:hover:not(:disabled) {
                background-color: #ff3333;
                transform: translateY(-3px) scale(1.05);
                box-shadow: 0 5px 10px rgba(255, 82, 82, 0.4);
            }
            
            .refund-button:active:not(:disabled) {
                transform: translateY(1px) scale(0.98);
                box-shadow: 0 2px 3px rgba(255, 82, 82, 0.3);
            }
            
            .refund-button:disabled {
                background-color: #aaa;
                color: #eee;
                cursor: not-allowed;
                box-shadow: none;
                transform: none;
            }
        `;
        document.head.appendChild(style);
        
        // Initial load
        loadPurchases();
        
        // Start auto-refresh
        startAutoRefresh();
        
    }
    
    // Start the purchases functionality
    init();
    
    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
}); 