<!-- header_nav.php - Shared header and navigation component -->
<header>
    <h1><a href="index.php" style="text-decoration: none; color: var(--text-primary);">TIMER SYSTEM</a></h1>
    <div class="header-controls">
        <!-- Bank Balance Display - Moved to top for prominence -->
        <div class="balance-display">
            <div class="balance-wrapper">
                <div class="balance-pill">
                    <div class="balance-inner">
                        <div class="balance-label">BALANCE</div>
                        <div class="amount-container">
                            <span class="dollar-sign">$</span>
                            <span class="balance-amount"><span id="current-balance">---</span></span>
                        </div>
                        <div class="balance-glow"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Standardized Page Navigation -->
        <div class="page-navigation">
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']);
            
            // Navigation links with conditionally added "active" class
            $nav_links = [
                'index.php' => '🏠 Home',
                'bank.php' => '🏦 Bank',
                'marketplace.php' => '🛒 Marketplace',
                'stats.php' => '📊 Stats',
                'search.php' => '🔍 Search'
            ];
            
            foreach ($nav_links as $page => $label) {
                $active_class = ($current_page === $page) ? ' active' : '';
                $extra_style = ($page === 'search.php') ? ' style="margin-right:-2px;"' : '';
                echo '<a href="' . $page . '" class="button-link button-link-nav' . $active_class . '" title="' . $label . '"' . $extra_style . '>' . $label . '</a>';
            }
            ?>
        </div>

        <!-- Fixed Controls -->
        <div class="page-controls fixed-controls">
            <button id="stop-all-btn" title="Stop All Timers" class="nav-button">⏹ <span id="running-timers-count">0</span></button>
            <a href="difficulty.php" id="difficulty-btn" class="button-link nav-button<?php echo ($current_page === 'difficulty.php') ? ' active' : ''; ?>" title="Difficulty Settings">⚙️</a>
        </div>
    </div>
<!-- Stop All Timers Confirmation Modal -->
    <div id="modal-container">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Stop All Timers</h3>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to stop all running timers?</p>
                </div>
                <div class="modal-footer">
                    <button class="modal-button cancel-button">Cancel</button>
                    <button class="modal-button confirm-button warning">Stop All</button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Add balance display styles -->
<style>
    /* Font consistency rule for balance display */
    .balance-amount,
    .balance-amount *,
    #current-balance,
    .dollar-sign {
        font-family: 'SF Mono', SFMono-Regular, 'JetBrains Mono', Menlo, Monaco, Consolas, monospace !important;
        letter-spacing: 0.5px !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
    }
    
    :root {
        --neon-cyan: #00ffd5;
        --neon-blue: #00c3ff;
        --dark-bg-1: #1a1a2e;
        --dark-bg-2: #16213e;
        --dark-text: #e7e7e7;
    }
    
    .balance-display {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 15px 0 0;
        position: relative;
    }
    
    .balance-wrapper {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    /* Main balance pill */
    .balance-pill {
        position: relative;
        border-radius: 10px;
        padding: 1px;
        background: linear-gradient(135deg, var(--neon-cyan), var(--neon-blue));
        box-shadow: 0 0 10px rgba(0, 255, 213, 0.3);
        overflow: hidden;
        min-width: 128px;
        transition: all 0.3s ease;
    }
    
    .balance-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: linear-gradient(135deg, var(--dark-bg-1), var(--dark-bg-2));
        color: var(--dark-text);
        padding: 8px 14px;
        border-radius: 9px;
        position: relative;
        cursor: pointer;
        z-index: 1;
    }
    
    /* Label styling */
    .balance-label {
        font-size: 0.62rem;
        text-transform: uppercase;
        color: var(--neon-blue);
        letter-spacing: 1.1px;
        margin-bottom: 3px;
        font-weight: 700;
        text-shadow: 0 0 5px rgba(0, 195, 255, 0.7);
        line-height: 1;
    }
    
    /* Amount container */
    .amount-container {
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.1;
    }
    
    /* Dollar sign */
    .dollar-sign {
        font-size: 1.2rem;
        font-weight: 700;
        background: linear-gradient(to right, var(--neon-cyan), var(--neon-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-right: 2px;
        text-shadow: 0 0 8px rgba(0, 255, 213, 0.6);
    }
    
    /* Balance amount styling */
    .balance-amount {
        font-size: 1.25rem;
        letter-spacing: 0.5px;
        font-weight: 700;
        font-family: 'SF Mono', SFMono-Regular, ui-monospace, 'JetBrains Mono', monospace;
        background: linear-gradient(to right, var(--neon-cyan), var(--neon-blue));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 8px rgba(0, 255, 213, 0.6);
        position: relative;
        z-index: 1;
    }
    
    /* Glow effect */
    .balance-glow {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: linear-gradient(135deg, rgba(0, 255, 213, 0.1), rgba(0, 195, 255, 0.1));
        opacity: 0;
        transition: opacity 0.3s ease;
        mix-blend-mode: overlay;
        z-index: 0;
    }
    
    /* Hover and active states */
    .balance-wrapper:hover .balance-pill {
        box-shadow: 0 0 15px rgba(0, 255, 213, 0.5), 0 0 30px rgba(0, 195, 255, 0.3);
        transform: translateY(-2px) scale(1.02);
    }
    
    .balance-wrapper:hover .balance-glow {
        opacity: 1;
    }
    
    .balance-wrapper:active .balance-pill {
        transform: translateY(1px) scale(0.98);
        box-shadow: 0 0 8px rgba(0, 255, 213, 0.4);
    }
    
    /* Make balance more visible on small screens */
    @media (max-width: 768px) {
        .balance-pill {
            position: relative;
            z-index: 10;
        }
    }
    
    /* Balance update visual feedback */
    .balance-updated {
        animation: flash-update 1.5s ease;
    }
    
    @keyframes flash-update {
        0%, 100% {
            box-shadow: 0 0 10px rgba(0, 255, 213, 0.3);
        }
        25% {
            box-shadow: 0 0 20px rgba(0, 255, 213, 0.7), 0 0 30px rgba(0, 195, 255, 0.5);
        }
        50% {
            box-shadow: 0 0 25px rgba(0, 255, 213, 0.8), 0 0 35px rgba(0, 195, 255, 0.6);
        }
        75% {
            box-shadow: 0 0 20px rgba(0, 255, 213, 0.7), 0 0 30px rgba(0, 195, 255, 0.5);
        }
    }
</style>

<!-- Initialize the counter visibility and fetch accurate count -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const runningTimersCountEl = document.getElementById('running-timers-count');
    const stopAllBtn = document.getElementById('stop-all-btn');
    const balanceEl = document.getElementById('current-balance');
    const balancePill = document.querySelector('.balance-pill');
    const balanceWrapper = document.querySelector('.balance-wrapper');
    let previousBalance = 0;
    
    // Make balance pill clickable to go to bank page
    if (balanceWrapper) {
        balanceWrapper.addEventListener('click', function() {
            window.location.href = 'bank.php';
        });
        balanceWrapper.title = "View Bank Details";
    }
    
    // Initialize stop-all button click handler
    if (stopAllBtn) {
        // Removed - now handled in script.js
    }
    
    // Intercept single timer stop actions by listening for custom events
    document.addEventListener('timerStopped', function(event) {
        if (event.detail && event.detail.bank_balance !== undefined && balanceEl) {
            // Update balance immediately when a timer is stopped
            const formattedBalance = parseFloat(event.detail.bank_balance).toFixed(2);
            balanceEl.textContent = formattedBalance;
            
            // Visual feedback
            if (balancePill) {
                balancePill.classList.add('balance-updated');
                setTimeout(() => balancePill.classList.remove('balance-updated'), 1800);
            }
            
            // Update timer count if provided
            if (event.detail.running_count !== undefined) {
                updateCounterDisplay(event.detail.running_count);
            }
        }
    });
    
    // Initial visibility state
    if (runningTimersCountEl && runningTimersCountEl.textContent === '0') {
        runningTimersCountEl.style.display = 'none';
        if (stopAllBtn) {
            stopAllBtn.classList.remove('has-running-timers');
        }
    } else if (runningTimersCountEl && runningTimersCountEl.textContent !== '0') {
        runningTimersCountEl.style.display = 'inline-flex';
        runningTimersCountEl.style.visibility = 'visible';
        if (stopAllBtn) {
            stopAllBtn.classList.add('has-running-timers');
        }
    }
    
    // Function to make API calls
    async function apiCall(endpoint, method = 'GET', body = null) {
        const url = endpoint.startsWith('api/') ? endpoint : ('api/' + endpoint);
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        };
        if (body) {
            options.body = JSON.stringify(body);
        }
        
        try {
            const response = await fetch(url, options);
            const responseText = await response.text();
            if (!responseText.trim()) return { status: 'error' };
            
            try {
                return JSON.parse(responseText);
            } catch (e) {
                console.error('Error parsing timer data:', e);
                return { status: 'error' };
            }
        } catch (error) {
            console.error('Navigation API error:', error);
            return { status: 'error', message: error.message };
        }
    }
    
    // Update counter function
    function updateCounterDisplay(count) {
        if (!runningTimersCountEl) return;
        
        count = parseInt(count) || 0;
        runningTimersCountEl.textContent = count;
        
        if (count === 0) {
            runningTimersCountEl.style.display = 'none';
            if (stopAllBtn) {
                stopAllBtn.classList.remove('has-running-timers');
            }
        } else {
            runningTimersCountEl.style.display = 'inline-flex';
            runningTimersCountEl.style.visibility = 'visible';
            if (stopAllBtn) {
                stopAllBtn.classList.add('has-running-timers');
            }
        }
    }
    
    // Function to fetch and update timer count
    async function updateTimerCount() {
        try {
            const data = await apiCall('get_data.php?count_only=1');
            if (data && data.status === 'success') {
                // If API returns a direct count
                if (data.running_count !== undefined) {
                    updateCounterDisplay(data.running_count);
                    return;
                }
                
                // Otherwise calculate from timers data
                if (data.timers) {
                    let runningCount = 0;
                    
                    // Handle both array and object format
                    if (Array.isArray(data.timers)) {
                        runningCount = data.timers.filter(timer => !!parseInt(timer.is_running || 0)).length;
                    } else if (typeof data.timers === 'object') {
                        runningCount = Object.values(data.timers).filter(timer => !!parseInt(timer.is_running || 0)).length;
                    }
                    
                    updateCounterDisplay(runningCount);
                }
            }
        } catch (error) {
            console.error('Error updating timer count:', error);
        }
    }
    
    // Bank balance update logic removed - handled by script.js via window.updateHeaderBalance
    
    // Set up a global function that other scripts can call to update the balance
    window.updateHeaderBalance = function(newBalance) {
        if (balanceEl) {
            // Always ensure 2 decimal places
            const formattedBalance = parseFloat(newBalance).toFixed(2);
            balanceEl.textContent = formattedBalance;
            
            // Visual feedback
            if (balancePill) {
                balancePill.classList.add('balance-updated');
                setTimeout(() => balancePill.classList.remove('balance-updated'), 1800);
            }
        }
    };
    
    // Initial updates
    updateTimerCount();
    // updateBankBalance(); // Removed initial call
    
    // Update timer count every 10 seconds to keep it synchronized across pages
    const timerCountInterval = setInterval(updateTimerCount, 10000);
    
    // Update bank balance interval removed
    // const bankBalanceInterval = setInterval(updateBankBalance, 15000); // Removed
    
    // Clear intervals when page is hidden to conserve resources
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(timerCountInterval);
            // clearInterval(bankBalanceInterval); // Removed
        } else {
            // Update immediately when page becomes visible again
            updateTimerCount();
            // updateBankBalance(); // Removed
            // Restart the intervals
            clearInterval(timerCountInterval);
            // clearInterval(bankBalanceInterval); // Removed
            setInterval(updateTimerCount, 10000);
            // setInterval(updateBankBalance, 15000); // Removed
        }
    });
});
</script>