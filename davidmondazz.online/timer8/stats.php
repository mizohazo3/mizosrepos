<?php

include_once 'timezone_config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Timer System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
        }
        .stats-card h3 {
            font-size: 1rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
        .stats-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }
        .chart-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .chart-container h2 {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .chart-scroll-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .chart {
            display: flex;
            align-items: flex-end;
            /* gap: 2px; Remove gap for diagnosis */
            height: 300px;
            min-width: 100%;
            display: flex !important; /* Diagnostic: Ensure display is flex */
        }
        .day-view .chart {
            min-width: 150%; /* Make day view scrollable */
        }
        .bar-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 20px; /* Minimum width for bars */
        }
        .day-view .bar-container {
            min-width: 40px; /* Wider for hourly data */
        }
        .bar {
            width: 100%;
            background: var(--primary-color);
            border-radius: 4px 4px 0 0;
            min-height: 4px; /* Re-add min-height */
            transition: height 0.5s ease; /* Re-add transition */
        }
        .bar-container .label {
            font-size: 0.7rem;
            margin-top: 0.5rem;
            text-align: center;
            white-space: nowrap;
            transform: rotate(-45deg);
            transform-origin: top left;
            margin-left: 8px;
        }
        .day-view .bar-container .label {
            transform: rotate(-60deg);
        }
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 6px;
        }
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .period-selector button {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 4px 10px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .period-selector button.active {
            background: var(--primary-color);
            color: var(--text-on-primary);
            border-color: var(--primary-color);
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/header_nav.php'; ?>

        <div class="period-selector">
            <button data-period="day" class="active">Last 24 Hours</button>
            <button data-period="week">Last 7 Days</button>
            <button data-period="month">Last 30 Days</button>
            <button data-period="year_monthly">Last 12 Months</button>
        </div>

        <section class="stats-summary">
            <div class="stats-card">
                <h3>Total Hours Worked</h3>
                <p class="value" id="total-hours">Loading...</p>
            </div>
            <div class="stats-card">
                <h3>Total Money Earned</h3>
                <p class="value" id="total-earned">Loading...</p>
            </div>
            <div class="stats-card">
                <h3>Total Money Spent</h3>
                <p class="value" id="total-spent">Loading...</p>
            </div>
            <div class="stats-card">
                <h3>Average Daily Hours</h3>
                <p class="value" id="avg-hours">Loading...</p>
            </div>
        </section>

        <div class="chart-container" id="earnings-chart-container">
            <h2>Daily Earnings</h2>
            <div id="earnings-loading" class="loading">Loading chart data...</div>
            <div id="earnings-empty" class="empty-state" style="display: none;">No earnings data available for the selected period.</div>
            <div class="chart-scroll-container">
                <div id="earnings-chart" class="chart" style="display: none;"></div>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: #4CAF50;"></div>
                    <span>Earnings (+)</span>
                </div>
            </div>
        </div>

        <div class="chart-container" id="spending-chart-container">
            <h2>Daily Spending</h2>
            <div id="spending-loading" class="loading">Loading chart data...</div>
            <div id="spending-empty" class="empty-state" style="display: none;">No spending data available for the selected period.</div>
            <div class="chart-scroll-container">
                <div id="spending-chart" class="chart" style="display: none;"></div>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: #E53935;"></div>
                    <span>Spending (-)</span>
                </div>
            </div>
        </div>

        <div class="chart-container" id="hours-chart-container">
            <h2>Daily Hours</h2>
            <div id="hours-loading" class="loading">Loading chart data...</div>
            <div id="hours-empty" class="empty-state" style="display: none;">No hours data available for the selected period.</div>
            <div class="chart-scroll-container">
                <div id="hours-chart" class="chart" style="display: none;"></div>
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--secondary-color);"></div>
                    <span>Hours</span>
                </div>
            </div>
        </div>
    </div>

    <div id="chart-tooltip" style="display: none;"></div>

    <div id="notification-container"></div>

    <!-- Common utilities (using absolute path) -->
    <script src="./js/common.js"></script>
    <!-- Modal functionality (using absolute path) -->
    <script src="./js/modal.js"></script>
    <!-- Stats page specific logic (using absolute path) -->
    <script src="./js/stats.js"></script>
    <!-- Add timer page script for stop all timer functionality -->
    <script src="./js/timer_page.js"></script>
    <!-- script.js and notifications.js removed -->
</body>
</html>