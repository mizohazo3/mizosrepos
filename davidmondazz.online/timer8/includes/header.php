<!-- Static Header Navigation -->
<header class="static-header">
    <div class="header-content">
        <h1><a href="index.php" class="logo-link">TIMER SYSTEM</a></h1>
        <div class="header-controls">
            <!-- Standardized Page Navigation -->
            <div class="page-navigation">
                <a href="index.php" class="button-link button-link-nav" title="Home">🏠 Home</a>
                <a href="bank.php" class="button-link button-link-nav" title="Bank Overview">🏦 Bank</a>
                <a href="marketplace.php" class="button-link button-link-nav" title="Marketplace">🛒 Marketplace</a>
                <a href="stats.php" class="button-link button-link-nav" title="Statistics">📊 Stats</a>
                <a href="search.php" class="button-link button-link-nav" title="Search Timers">🔍 Search</a>
            </div>

            <!-- Global Controls -->
            <div class="global-controls">
                <button id="stop-all-btn" title="Stop All Timers">⏹ <span id="running-timers-count">0</span></button>
                <a href="difficulty.php" id="difficulty-btn" class="button-link" title="Difficulty Settings">⚙️</a>
                <div class="global-bank">Bank: $<span id="global-bank-balance">0.00</span></div>
            </div>
        </div>
    </div>
</header> 