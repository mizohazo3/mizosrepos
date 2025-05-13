<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Overview - Timer System</title>
    <!-- Assuming shared stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Specific styles for bank page could go in bank.css -->
    <!-- <link rel="stylesheet" href="bank.css"> -->
     <!-- Using fonts from index -->
     <link rel="preconnect" href="https://fonts.googleapis.com">
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="bank-header">
            <h1>BANK OVERVIEW</h1>
            <a href="index.php" class="button-link-text" title="Back to Timers">← Back to Timers</a>
        </header>

        <section class="bank-summary">
             <div class="summary-card balance-card">
                 <h2>Current Balance</h2>
                 <p id="bank-current-balance" class="balance-amount">Loading...</p>
             </div>
             <div class="summary-card total-earned-card">
                 <h2>Total Earned (All Time)</h2>
                 <p id="bank-total-earned" class="total-earned-amount">Loading...</p>
             </div>
         </section>

        <section class="transaction-history">
            <h2>Recent Transactions (<span id="transaction-count-shown">0</span>)</h2>
             <div id="transactions-loading" class="loading-message">Loading transaction history...</div>
             <div id="transactions-empty" class="empty-message" style="display: none;">No transactions found.</div>
             <ul id="transaction-list">
                <!-- Transactions will be populated here by JavaScript -->
            </ul>
             <p class="transaction-limit-note" style="display: none;">Showing the last <span id="transaction-limit"></span> transactions.</p>
        </section>

    </div>

    <!-- Transaction Item Template -->
    <template id="transaction-item-template">
        <li class="transaction-item">
            <div class="transaction-details">
                <span class="transaction-timer-name">Timer Name</span>
                <span class="transaction-time">Timestamp</span>
            </div>
            <div class="transaction-values">
                <span class="transaction-duration">Duration</span>
                <span class="transaction-earned">Amount Earned</span>
            </div>
        </li>
    </template>

    <script src="bank.js"></script>
</body>
</html>