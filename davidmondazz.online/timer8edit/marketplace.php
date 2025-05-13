<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - Timer System</title>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="stylesheet" href="marketplace.css"> --> <!-- Optional: specific CSS -->
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="marketplace-header">
            <h1>MARKETPLACE</h1>
             <div class="header-info">
                 <span class="current-balance-display">Balance: $<span id="market-current-balance">---</span></span>
                 <a href="add_item.php" class="button-link-text" title="Add New Item">➕ Add Item</a>
                 <a href="index.php" class="button-link-text" title="Back to Timers">← Timers</a>
                 <a href="bank.php" class="button-link-text" title="View Bank">🏦 Bank</a>
             </div>
        </header>

        <section class="item-listing">
            <div id="items-loading" class="loading-message">Loading items...</div>
            <div id="items-empty" class="empty-message" style="display: none;">No items currently available.</div>
            <div id="item-grid" class="item-grid-container">
                <!-- Items will be populated here -->
            </div>
        </section>
    </div>

    <!-- Marketplace Item Template -->
    <template id="item-template">
        <div class="item-card" data-item-id="">
            <div class="item-image-container">
                 <img src="https://via.placeholder.com/150" alt="Item Image" class="item-image">
             </div>
             <div class="item-info">
                 <h3 class="item-name">Item Name</h3>
                 <p class="item-description">Item description goes here...</p>
             </div>
            <div class="item-purchase-section">
                <span class="item-price">$0.00</span>
                <button class="button buy-button">Buy</button>
                <button class="button delete-button" title="Delete Item">🗑️</button>
            </div>
         </div>
     </template>

     <!-- General Notification container -->
     <div id="notification-container"></div>

    <!-- Scripts -->
    <script src="notifications.js"></script>
    <script src="modal.js"></script>
    <script src="marketplace.js"></script>
</body>
</html>