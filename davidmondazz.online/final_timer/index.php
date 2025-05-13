<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Tracker</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&family=Montserrat:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&family=Lato:wght@300;400;700&family=Roboto+Mono:wght@400;700&family=Courier+Prime:wght@400;700&family=Orbitron:wght@400;700&family=Rajdhani:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.min.css">
    
    <!-- Custom CSS (keep this last to override) -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="font-poppins">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <div class="brand">
                <a href="index.php" class="home-link"><i class="fas fa-clock"></i> Timer Tracker</a>
            </div>
            <div class="d-flex align-items-center">
                <div class="font-selector me-3">
                    <select id="font-selector" class="form-select form-select-sm">
                        <option value="Poppins">Poppins</option>
                        <option value="Roboto">Roboto</option>
                        <option value="Montserrat">Montserrat</option>
                        <option value="Open Sans">Open Sans</option>
                        <option value="Lato">Lato</option>
                        <option value="Roboto Mono">Roboto Mono (Monospace)</option>
                        <option value="Courier Prime">Courier Prime (Monospace)</option>
                        <option value="Orbitron">Orbitron (Digital)</option>
                        <option value="Rajdhani">Rajdhani (Digital)</option>
                    </select>
                </div>
                <button id="add-timer-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Timer</button>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="category-filter">
            <h3>Filter by Category:</h3>
            <div id="category-list">
                <button class="category-btn active" data-category-id="0">All</button>
                <!-- Categories will be loaded here dynamically -->
            </div>
        </div>
        
        <div class="timers-container" id="timers-container">
            <!-- Timers will be loaded here dynamically -->
            <div class="empty-state" id="empty-state">
                <p>No timers found. Click "Add New Timer" to create one.</p>
            </div>
        </div>
    </div>
    
    <!-- Add Timer Modal -->
    <div id="add-timer-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Timer</h2>
            <form id="add-timer-form">
                <div class="form-group">
                    <label for="timer-name">Timer Name:</label>
                    <input type="text" id="timer-name" name="timer-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="timer-category">Category:</label>
                    <select id="timer-category" name="timer-category" class="form-select" required>
                        <!-- Categories will be loaded here dynamically -->
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>
    
    <!-- Timer Template (Hidden) -->
    <template id="timer-template">
        <div class="timer-card" data-timer-id="">
            <div class="timer-header">
                <h3 class="timer-name"><span class="timer-title"></span></h3>
                <button class="delete-timer-btn"><i class="fas fa-trash"></i></button>
            </div>
            <div class="timer-display">
                <div class="current-time">
                    <span class="time-value">00:00:00<span class="milliseconds">.00</span></span>
                </div>
            </div>
            <div class="timer-controls">
                <button class="timer-btn start-btn"><i class="fas fa-play"></i> Start</button>
                <button class="timer-btn stop-btn"><i class="fas fa-stop"></i> Stop</button>
            </div>
            <div class="timer-footer">
                <div class="category-name-footer"><span class="category-name"></span></div>
                <div class="total-time">
                    <span class="time-value total-elapsed">00:00:00</span>
                </div>
            </div>
        </div>
    </template>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>
</html> 