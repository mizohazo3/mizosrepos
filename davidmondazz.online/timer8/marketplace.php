<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - Timer System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="marketplace.css"> <!-- Enable this for specific CSS -->
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <?php
        include_once 'timezone_config.php';
        include 'includes/header_nav.php';
        ?>
        
        <!-- Additional marketplace-specific controls -->
        <div class="marketplace-specific-controls">
            <div class="basket-icon-container">
                <button id="view-basket" class="basket-button" title="View Basket">
                    🛒 <span id="basket-count">0</span>
                </button>
            </div>
            <button id="view-notes" class="notes-button" title="View Noted Items">
                📝 <span id="notes-count">0</span>
            </button>
            <a href="market_manage.php" class="button-link button-link-nav" title="Manage Marketplace">⚙️ Manage</a>
        </div>
        
        <!-- Basket summary section (initially hidden) - Now at the top -->
        <section id="basket-summary" class="basket-summary basket-modal" style="display: none;">
            <div class="basket-header">
                <h2>Your Basket</h2>
                <button id="close-basket" class="close-button" title="Close Basket">✕</button>
            </div>
            <div id="basket-items" class="basket-items">
                <!-- Basket items will be populated here -->
            </div>
            <div class="basket-footer">
                <div class="basket-total">Total: <span id="basket-total-amount">$0.00</span></div>
                <div class="basket-actions">
                    <button id="checkout-button" class="button primary-button">Checkout</button>
                    <button id="clear-basket" class="button secondary-button">Clear Basket</button>
                </div>
            </div>
        </section>

        <!-- Notes summary section (initially hidden) -->
        <section id="notes-summary" class="notes-summary notes-modal" style="display: none;">
            <div class="notes-header">
                <h2>Items on Note</h2>
                <button id="close-notes" class="close-button" title="Close Notes">✕</button>
            </div>
            <div id="noted-items" class="noted-items">
                <!-- Noted items will be populated here -->
            </div>
            <div class="notes-footer">
                <div class="notes-total">Total Required: <span id="notes-total-amount">$0.00</span></div>
                <div class="notes-actions">
                    <button id="clear-notes" class="button secondary-button">Clear All Notes</button>
                </div>
            </div>
        </section>

        <!-- Marketplace Items Section -->
        <section class="item-listing">
            <div class="section-header">
                
            </div>
            <div id="items-loading" class="loading-message">Loading items...</div>
            <div id="items-empty" class="empty-message" style="display: none;">No items currently available.</div>
            <div id="item-grid" class="item-grid-container">
                <!-- Items will be populated here -->
            </div>
        </section>

        <!-- Recent Purchases Section (now at the bottom) -->
        <section class="recent-purchases-section">
            <div class="section-header">
                
                <div class="section-actions">
                </div>
            </div>
            <div id="purchases-loading" class="loading-message">Loading recent purchases...</div>
            <div id="purchases-empty" class="empty-message" style="display: none;">No recent purchases.</div>
            <div id="recent-purchases" class="recent-purchases-container">
                <!-- Purchases will be populated here -->
            </div>
        </section>
    </div>

    <!-- Marketplace Item Template -->
    <template id="item-template">
        <div class="item-card" data-item-id="">
            <div class="item-header">
                <h3 class="item-name">Item Name</h3>
            </div>
            <div class="item-image-container" title="Click to buy">
                <img src="https://via.placeholder.com/150" alt="Item Image" class="item-image">
            </div>
            <div class="item-purchase-section">
                <span class="item-price">$0.00</span>
                <div class="item-buttons">
                    <button class="button icon-button add-to-note-button" title="Add to Note">📝</button>
                    <button class="button buy-button">Add to Basket</button>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Basket Item Template -->
    <template id="basket-item-template">
        <div class="basket-item" data-item-id="">
            <div class="basket-item-info">
                <div class="basket-item-details">
                    <img src="" alt="" class="basket-item-icon">
                    <span class="basket-item-name">Item Name</span>
                </div>
                <span class="basket-item-price">$0.00</span>
            </div>
            <button class="remove-from-basket" title="Remove from basket">✕</button>
        </div>
    </template>

    <!-- Note Item Template -->
    <template id="note-item-template">
        <div class="note-item" data-item-id="">
            <div class="note-item-info">
                <div class="note-item-details">
                    <img src="" alt="" class="note-item-icon">
                    <div class="note-item-text">
                        <span class="note-item-name">Item Name</span>
                        <span class="note-item-price">$0.00</span>
                    </div>
                </div>
                <div class="note-item-actions">
                    <button class="button buy-from-note" title="Buy Now" disabled>Buy</button>
                    <button class="remove-from-note" title="Remove from note">✕</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Purchase Item Template -->
    <template id="purchase-item-template">
        <div class="purchase-item" data-purchase-id="">
            <div class="purchase-item-content">
                <div class="purchase-item-info">
                    <img src="" alt="" class="purchase-item-icon">
                    <div class="purchase-item-details">
                        <span class="purchase-item-name">Item Name</span>
                        <span class="purchase-item-price">$0.00</span>
                    </div>
                </div>
                <div class="purchase-item-meta">
                    <span class="purchase-time-ago" title="">just now</span>
                    <button class="refund-button" title="Refund this purchase">Refund</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Checkout Modal Template -->
    <template id="checkout-modal-template">
        <div class="checkout-modal-content">
            <h2>Complete Purchase</h2>
            <div class="checkout-items">
                <!-- Checkout items will be populated here -->
            </div>
            <div class="checkout-summary">
                <div class="checkout-total">Total: <span class="checkout-total-amount">$0.00</span></div>
                <div class="checkout-balance">Current Balance: $<span class="checkout-current-balance">0.00</span></div>
                <div class="checkout-remaining">Remaining Balance: $<span class="checkout-remaining-balance">0.00</span></div>
            </div>
            <div class="checkout-actions">
                <button class="button primary-button confirm-purchase">Confirm Purchase</button>
                <button class="button secondary-button cancel-purchase">Cancel</button>
            </div>
        </div>
    </template>

    <!-- Confirmation Modal Template (new) -->
    <template id="confirm-modal-template">
        <div class="confirm-modal-content">
            <p class="confirm-message">Are you sure you want to proceed?</p>
            <div class="confirm-actions">
                <button class="button primary-button confirm-yes">Yes</button>
                <button class="button secondary-button confirm-no">No</button>
            </div>
        </div>
    </template>

    <!-- General Notification container -->
    <div id="notification-container"></div>

    <!-- Scripts -->
    <!-- Common utilities (using absolute path) -->
    <script src="./js/common.js"></script>
    <!-- Modal functionality (using absolute path) -->
    <script src="./js/modal.js"></script>
    <!-- Main app script for global functionality -->
    <script src="./script.js"></script>
    <!-- Notifications functionality -->
    <script src="./notifications.js"></script>
    <!-- Marketplace specific logic (using absolute paths) -->
    <script src="./js/marketplace.js"></script>
    <script src="./js/basket.js"></script>
    <script src="./js/purchases.js"></script>
    <script src="./js/notes.js"></script>
    
    <!-- Custom script to fix stop all button -->
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the stop-all counter using common.js function if available
            if (typeof initStopAllCounter === 'function') {
                initStopAllCounter();
            } else {
                const stopAllBtn = document.getElementById('stop-all-btn');
                
                if (stopAllBtn) {
                    stopAllBtn.addEventListener('click', async function() {
                        const runningTimersCount = document.getElementById('running-timers-count');
                        if (runningTimersCount && runningTimersCount.textContent === '0') return;
                        
                        try {
                            // Fetch running timer details
                            const response = await fetch('api/get_data.php');
                            const data = await response.json();
                            
                            if (data && data.status === 'success' && data.timers) {
                                // Filter running timers
                                const runningTimers = Object.values(data.timers).filter(timer => timer.is_running === '1');
                                const runningCount = runningTimers.length;
                                
                                if (runningCount === 0) {
                                    return; // No timers running
                                }
                                
                                // Calculate total hours and earnings
                                let totalSessionHours = 0;
                                let totalSessionEarnings = 0;
                                
                                // Generate list of running timers
                                const timerNamesList = runningTimers.map(timer => {
                                    const timerName = timer.name || 'Unnamed Timer';
                                    
                                    // Calculate session time
                                    const startTime = new Date(timer.start_time);
                                    const currentTime = new Date();
                                    const currentSeconds = Math.floor((currentTime - startTime) / 1000);
                                    const sessionHours = (currentSeconds / 3600).toFixed(2);
                                    totalSessionHours += parseFloat(sessionHours);
                                    
                                    // Calculate session earnings
                                    const rewardRate = parseFloat(timer.hourly_rate || 0);
                                    const sessionEarnings = (currentSeconds / 3600) * rewardRate;
                                    totalSessionEarnings += sessionEarnings;
                                    
                                    return `<li style="margin-left: 10px; margin-top: 5px; list-style-type: disc;">
                                        ${timerName} - ${sessionHours}h
                                    </li>`;
                                }).join('');
                                
                                // Add summary of total hours and earnings
                                const summaryInfo = `
                                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
                                        <p style="margin: 5px 0;">Total session hours: <strong>${totalSessionHours.toFixed(2)}h</strong></p>
                                        <p style="margin: 5px 0;">Total earned from sessions: <strong style="color: #67c23a;">+$${totalSessionEarnings.toFixed(2)}</strong></p>
                                    </div>
                                `;
                                
                                // Use the global modal instance
                                modal.show({
                                    title: 'Stop All Timers',
                                    message: `Are you sure you want to stop all ${runningCount} running timers?<ul style="margin-top: 10px; padding-left: 15px;">${timerNamesList}</ul>${summaryInfo}`,
                                    confirmText: 'Stop All',
                                    cancelText: 'Cancel',
                                    type: 'warning',
                                    onConfirm: async function() {
                                        await stopAllTimers();
                                    }
                                });
                            }
                        } catch (error) {
                            console.error('Error fetching timer details:', error);
                            // Fallback to a simpler modal if API call fails
                            modal.show({
                                title: 'Stop All Timers',
                                message: 'Are you sure you want to stop all running timers?',
                                confirmText: 'Stop All',
                                cancelText: 'Cancel',
                                type: 'warning',
                                onConfirm: async function() {
                                    await stopAllTimers();
                                }
                            });
                        }
                    });
                }
            }
            
            // Use the global stopAllTimers function if available
            async function stopAllTimers() {
                if (typeof window.stopAllTimers === 'function') {
                    return window.stopAllTimers();
                }
                
                try {
                    const response = await fetch('api/stop_all_timers.php');
                    const data = await response.json();
                    
                    if (data && data.status === 'success') {
                        // Show success notification
                        displayNotification('All timers stopped successfully', 'success');
                        
                        // Update the counter
                        const runningTimersCount = document.getElementById('running-timers-count');
                        if (runningTimersCount) {
                            runningTimersCount.textContent = '0';
                            runningTimersCount.style.display = 'none';
                        }
                        
                        const stopAllBtn = document.getElementById('stop-all-btn');
                        if (stopAllBtn) {
                            stopAllBtn.classList.remove('has-running-timers');
                        }
                    } else {
                        // Show error
                        const errorMsg = data?.message || 'Failed to stop all timers';
                        displayNotification(errorMsg, 'error');
                    }
                } catch (error) {
                    console.error('Error stopping all timers:', error);
                    displayNotification('Error stopping all timers: ' + error.message, 'error');
                }
            }
        });
    </script>
    <!-- script.js and notifications.js removed -->
</body>
</html>