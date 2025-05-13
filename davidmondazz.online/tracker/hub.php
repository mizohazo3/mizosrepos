<!DOCTYPE html>
<html lang="en">
<head>
    // ...existing code...
    <style>
        /* Hub Layout Styles */
        .main-container {
            margin-bottom: 60px;
        }
        
        /* Layout Container */
        .layout-container {
            display: flex;
            gap: 20px;
            margin: 0 -10px;
            flex-wrap: wrap;
        }

        /* Left Sidebar */
        .sidebar {
            flex: 0 0 300px;
            padding: 0 10px;
        }

        /* Timer Search Box Styles */
        .search-box {
            margin-bottom: 30px;
            width: 100%;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 1rem;
            height: 46px;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            padding: 0 10px;
            min-width: 0; /* Prevent flex items from overflowing */
        }

        /* Total Time Display */
        .total-time {
            text-align: center;
            margin-bottom: 30px;
        }

        .total-time-value {
            font-size: 2.2rem;
            font-family: 'DSEG7 Classic', monospace;
            color: var(--accent-color);
            text-shadow: 0 0 10px rgba(var(--accent-color-rgb), 0.3);
            background: rgba(0, 0, 0, 0.05);
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
        }
        
        /* Hub Title Styles */
        .hub-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin: 20px 0 30px 0;
            color: var(--accent-color);
        }

        body {
            padding-top: 70px;
            padding-bottom: 80px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .layout-container {
                flex-direction: column;
            }
            
            .sidebar {
                flex: none;
                width: 100%;
            }

            .total-time-value {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    // ...existing code...
    <!-- Main Content -->
    <div class="container main-container" style="margin-top: 30px;">
        <!-- Hub Title -->
        <h1 class="hub-title">THE HUB</h1>
        
        <!-- Layout Container -->
        <div class="layout-container">
            <!-- Left Sidebar with Timer Search -->
            <div class="sidebar">
                <div class="search-box">
                    <input type="text" id="timer-search" class="form-control" placeholder="Search timer name or category..." />
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="main-content">
                <!-- Total Time Display -->
                <div class="total-time">
                    <div class="total-time-value" id="total-time-display">00:00:00</div>
                </div>

                <!-- Timers Container -->
                <div class="row" id="timers-container">
                    <!-- Working timer cards will be populated here via JavaScript -->
                    <div class="col-12 text-center py-5" id="initial-loading-message">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading working timers...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    // ...existing code...
    <!-- Add this before the closing body tag -->
    <script>
        // Function to update total time
        function updateTotalTime() {
            fetch('api/get_total_time.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const totalSeconds = data.total_seconds;
                        const hours = Math.floor(totalSeconds / 3600);
                        const minutes = Math.floor((totalSeconds % 3600) / 60);
                        const seconds = totalSeconds % 60;
                        const formattedTime = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                        document.getElementById('total-time-display').textContent = formattedTime;
                    }
                })
                .catch(error => console.error('Error fetching total time:', error));
        }

        // Update total time every minute
        updateTotalTime();
        setInterval(updateTotalTime, 60000);
    </script>
</body>
</html>