<?php
// Include database connection
require_once 'includes/db_connect.php';

// Get timer ID from URL parameter
$timer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect to home if no valid timer ID
if ($timer_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get database connection
$conn = getDbConnection();

// Get timer details
$stmt = $conn->prepare("
    SELECT t.*, c.name as category_name 
    FROM timers t 
    INNER JOIN categories c ON t.category_id = c.id 
    WHERE t.id = ?
");
$stmt->bind_param("i", $timer_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if timer exists
if ($result->num_rows === 0) {
    // Timer not found, redirect to home
    header('Location: index.php');
    exit;
}

// Get timer details
$timer = $result->fetch_assoc();
$stmt->close();

// Get timer logs
$logs_stmt = $conn->prepare("
    SELECT * FROM timer_logs 
    WHERE timer_id = ? 
    ORDER BY duration DESC
");
$logs_stmt->bind_param("i", $timer_id);
$logs_stmt->execute();
$logs_result = $logs_stmt->get_result();
$logs = [];

while ($log = $logs_result->fetch_assoc()) {
    $logs[] = $log;
}
$logs_stmt->close();

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Details - <?php echo htmlspecialchars($timer['name']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            position: relative;
        }

        .content-wrapper {
            flex: 1 0 auto;
            padding-bottom: 30px; /* Increased padding to ensure content doesn't touch footer */
            width: 100%;
        }
        
        footer {
            flex-shrink: 0;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                padding-bottom: 60px; /* More padding on mobile */
            }
        }
        
        .navbar {
            background-color: #212529 !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            background-color: #f8f5ff;
            border: 1px solid #dee2e6;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .timer-details-value {
            font-weight: 700;
            font-size: 1rem;
            color: #6f42c1;
        }
        
        /* Day header styling */
        .day-header {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 15px 0 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-header-date {
            font-weight: 600;
            font-size: 1.1rem;
            color: #495057;
        }
        
        .day-header-total {
            background-color: #ffc107;
            color: #212529;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .timer-session-table {
            width: 100%;
            margin-bottom: 1rem;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .timer-session-table th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .timer-session-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .session-running {
            background-color: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .action-button {
            background-color: #6f42c1;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 16px;
            font-weight: 500;
            margin-top: 10px;
            margin-right: 5px;
        }
        
        .action-button:hover {
            background-color: #5a32a3;
        }
        
        .session-duration {
            font-weight: 600;
            color: #6f42c1;
        }
        
        /* Day layout side by side */
        .days-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .day-wrapper {
            flex: 1 1 calc(33.333% - 15px);
            min-width: 280px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Note functionality */
        .note-button {
            background-color: transparent;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 4px 8px;
            font-size: 0.8rem;
            border-radius: 4px;
        }
        
        .note-button:hover {
            background-color: #e9ecef;
        }
        
        .note-content {
            margin-top: 8px;
            padding: 8px;
            background-color: #2d2d2d;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #e0e0e0;
            min-height: 40px;
            font-family: monospace;
            white-space: pre-wrap;
            display: block;
            border: 1px solid #444;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .note-box {
            background-color: #1e1e1e;
            border-radius: 4px;
            padding: 12px;
            margin-top: 8px;
        }

        .note-title {
            color: #e0e0e0;
            font-size: 0.9rem;
            margin-bottom: 6px;
            font-weight: bold;
        }
        
        .note-form {
            display: none;
            margin-top: 8px;
        }
        
        .note-textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
            font-size: 0.9rem;
        }
        
        .note-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            gap: 8px;
        }
        
        .btn-start {
            background-color: #28a745;
            color: white;
        }
        
        .btn-start:hover {
            background-color: #1d9238;
            color: white;
            transform: scale(1.03);
            transition: all 0.2s ease;
        }
        
        .btn-stop {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-stop:hover {
            background-color: #bb2d3b;
            color: white;
            transform: scale(1.03);
            transition: all 0.2s ease;
        }
        
        .note-box {
            background-color: #1e1e1e;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .note-content {
            padding: 8px;
            background-color: #2d2d2d;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #e0e0e0;
            min-height: 40px;
            font-family: monospace;
            white-space: pre-wrap;
            display: block;
            border: 1px solid #444;
            margin: 0;
        }

        .note-content a {
            color: #58a6ff;
            text-decoration: none;
        }

        .note-content a:hover {
            text-decoration: underline;
        }
        
        /* Locked page style */
        .locked-page-overlay {
            position: relative;
        }
        
        .locked-page-overlay::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 100;
            border-radius: 10px;
            pointer-events: none;
        }

        /* Make unlock button visible */
        #unlock-timer {
            position: relative;
            z-index: 101;
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
            font-weight: bold;
            padding: 12px 25px;
            font-size: 1.1rem;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.7);
            animation: pulse 2s infinite;
            margin-top: 20px;
        }
        
        #unlock-timer:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(255, 193, 7, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }
        
        .disabled-button {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }

        .level-info-container {
            padding: 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }

        .level-display {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }

        .rank-badge {
            display: inline-block;
            padding: 5px 15px;
            background-color: #17a2b8;
            color: white;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        .upcoming-rank-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #6c757d;
        }

        .upcoming-rank-item.current-rank {
            border-left: 3px solid #28a745;
            background-color: #e8f4ea;
        }

        .rank-name {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .rank-level, .rank-hours {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-stopwatch me-2"></i>TIMER TRACKER
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_control.php"><i class="fas fa-cog"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug.php"><i class="fas fa-bug"></i> Debug</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-wrapper">
        <?php 
        // Check if timer is locked
        $isLocked = ($timer['manage_status'] === 'lock' || $timer['manage_status'] === 'lock&special');
        ?>

        <div class="container <?php echo $isLocked ? 'locked-page-overlay' : ''; ?>" style="margin-top: 30px;">
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($timer['name']); ?>
                                <?php if ($isLocked): ?>
                                <span class="badge bg-danger ms-2"><i class="fas fa-lock"></i> Locked</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Category:</strong> 
                                        <?php if ($timer['category_id'] && $timer['category_name']): ?>
                                            <a href="index.php#category-<?php echo $timer['category_id']; ?>" class="timer-details-value">
                                                <?php echo htmlspecialchars($timer['category_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="timer-details-value">Uncategorized</span>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Status:</strong> <span class="badge <?php 
                                        if ($isLocked) {
                                            echo 'bg-danger';
                                        } else {
                                            echo ($timer['status'] == 'running') ? 'bg-success' : (($timer['status'] == 'paused') ? 'bg-warning' : 'bg-secondary');
                                        }
                                    ?>"><?php echo $isLocked ? htmlspecialchars($timer['manage_status']) : ucfirst($timer['status']); ?></span></p>
                                    <p><strong>Created:</strong> <span class="timer-details-value"><?php echo $timer['created_at']; ?></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Time:</strong> <span class="timer-details-value"><?php echo formatTime((int)$timer['total_time']); ?></span></p>
                                    <p><strong>Last Updated:</strong> <span class="timer-details-value"><?php echo $timer['updated_at']; ?></span></p>
                                    <div>
                                        <?php if ($isLocked): ?>
                                            <div class="d-flex justify-content-center" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
                                                <button id="unlock-timer" class="btn action-button btn-warning" data-timer-id="<?php echo $timer_id; ?>">
                                                    <i class="fas fa-unlock me-2"></i>Unlock Timer
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <?php if ($timer['status'] == 'idle'): ?>
                                                <button id="start-timer" class="btn action-button btn-start" data-timer-id="<?php echo $timer_id; ?>">
                                                    <i class="fas fa-play me-2"></i>Start Timer
                                                </button>
                                            <?php elseif ($timer['status'] == 'running'): ?>
                                                <button id="stop-timer" class="btn action-button btn-stop" data-timer-id="<?php echo $timer_id; ?>">
                                                    <i class="fas fa-stop me-2"></i>Stop Timer
                                                </button>
                                            <?php elseif ($timer['status'] == 'paused'): ?>
                                                <button id="resume-timer" class="btn action-button btn-start me-2" data-timer-id="<?php echo $timer_id; ?>">
                                                    <i class="fas fa-play me-2"></i>Resume Timer
                                                </button>
                                                <button id="stop-timer" class="btn action-button btn-stop" data-timer-id="<?php echo $timer_id; ?>">
                                                    <i class="fas fa-stop me-2"></i>Stop Timer
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this section after the timer details and before the timer logs -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>Level & Ranking
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="level-info-container">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h2 class="level-display mb-0" id="level-display">
                                        Level <span id="current-level"><?php echo $timer['level'] ?? 1; ?></span>
                                    </h2>
                                    <span class="rank-badge" id="rank-badge"><?php echo $timer['rank_name'] ?? 'Novice'; ?></span>
                                </div>
                                
                                <div class="progress mb-2" style="height: 25px;">
                                    <div id="level-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                         role="progressbar" 
                                         style="width: 0%;" 
                                         aria-valuenow="0" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">0%</div>
                                </div>
                                
                                <div class="d-flex justify-content-between small text-muted">
                                    <span id="current-hours">Current: <?php echo number_format($timer['total_time'] / 3600, 1); ?>h</span>
                                    <span id="next-level-hours">Next Level: Loading...</span>
                                </div>
                                
                                <div class="mt-3">
                                    <p class="mb-1">Total Experience: <span id="total-xp"><?php echo $timer['experience'] ?? 0; ?> XP</span></p>
                                    <p class="mb-0 text-muted">Gain 1-5 XP per second while timer is running</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="upcoming-levels">
                                <h6 class="mb-3">Upcoming Ranks</h6>
                                <div id="upcoming-ranks">
                                    <div class="d-flex justify-content-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-history me-2"></i>Timer Sessions History
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (count($logs) > 0): 
                                // Group logs by date
                                $groupedLogs = [];
                                foreach ($logs as $log) {
                                    $date = date('Y-m-d', strtotime($log['start_time']));
                                    if (!isset($groupedLogs[$date])) {
                                        $groupedLogs[$date] = [];
                                    }
                                    $groupedLogs[$date][] = $log;
                                }
                                
                                // Sort dates in descending order (newest first)
                                krsort($groupedLogs);
                                
                                // Display days container to arrange side by side
                                echo '<div class="days-container">';
                                
                                // Display logs by date
                                foreach ($groupedLogs as $date => $dayLogs):
                                    // Sort logs within each day by most recent first
                                    usort($dayLogs, function($a, $b) {
                                        return strtotime($b['start_time']) - strtotime($a['start_time']);
                                    });
                                    
                                    // Calculate total duration for the day
                                    $totalDuration = 0;
                                    foreach ($dayLogs as $log) {
                                        $totalDuration += (int)($log['duration'] ?? 0);
                                    }
                                    
                                    // Format the date
                                    $formattedDate = date('d M Y', strtotime($date));
                                    $dayNumber = date('d', strtotime($date));
                            ?>
                                    <div class="day-wrapper">
                                        <div class="day-header">
                                            <div class="day-header-date">
                                                <?php echo $formattedDate; ?>
                                            </div>
                                            <div class="day-header-total"><?php echo formatTime($totalDuration); ?></div>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="timer-session-table">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">#</th>
                                                        <th width="15%">From</th>
                                                        <th width="15%">To</th>
                                                        <th width="20%">Duration</th>
                                                        <th width="45%">Note</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($dayLogs as $index => $log): ?>
                                                        <tr>
                                                            <td><?php echo count($dayLogs) - $index; ?></td>
                                                            <td><?php echo date('h:i a', strtotime($log['start_time'])); ?></td>
                                                            <td>
                                                                <?php if ($log['stop_time']): ?>
                                                                    <?php echo date('h:i a', strtotime($log['stop_time'])); ?>
                                                                <?php else: ?>
                                                                    <span class="session-running">Running...</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($log['stop_time']): ?>
                                                                    <span class="session-duration">
                                                                        <?php 
                                                                        $duration = (int)$log['duration'];
                                                                        echo formatTime($duration);
                                                                        ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="session-duration">In progress</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!$isLocked): ?>
                                                                <button class="note-button" data-log-id="<?php echo $log['id']; ?>">
                                                                    <i class="fas fa-sticky-note"></i> <?php echo !empty($log['note']) ? 'Edit Note' : 'Add Note'; ?>
                                                                </button>
                                                                <?php else: ?>
                                                                <button class="note-button disabled-button">
                                                                    <i class="fas fa-lock"></i> Notes Locked
                                                                </button>
                                                                <?php endif; ?>
                                                                <?php if (!empty($log['note'])): ?>
                                                                <div class="note-box">
                                                                    <pre><code class="note-content" id="note-content-<?php echo $log['id']; ?>"><?php 
                                                                        $note = htmlspecialchars($log['note']);
                                                                        // Convert URLs to clickable links
                                                                        $note = preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank">$1</a>', $note);
                                                                        // Add Vanced app icon beside YouTube links
                                                                        $note = preg_replace('/(<a href="(https?:\/\/(?:www\.)?youtu(?:\.be|be\.com)[^\s<]+)"[^>]*>)(.*?)(<\/a>)/', 
                                                                        '$1$3$4 <a href="vanced://$2" class="vanced-link" title="Open in Vanced app"><i class="fas fa-play-circle"></i></a>', $note);
                                                                        echo $note;
                                                                    ?></code></pre>
                                                                </div>
                                                                <?php endif; ?>
                                                                <div class="note-form" id="note-form-<?php echo $log['id']; ?>" style="display: none;">
                                                                    <textarea class="note-textarea" id="note-textarea-<?php echo $log['id']; ?>" placeholder="Add a note for this session..."><?php echo !empty($log['note']) ? htmlspecialchars($log['note']) : ''; ?></textarea>
                                                                    <div class="note-actions">
                                                                        <button class="btn btn-sm btn-secondary cancel-note" data-log-id="<?php echo $log['id']; ?>">Cancel</button>
                                                                        <button class="btn btn-sm btn-primary save-note" data-log-id="<?php echo $log['id']; ?>">Save</button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                            <?php 
                                endforeach;
                                // Close the days container
                                echo '</div>';
                            else: ?>
                                <div class="alert alert-info">
                                    No timer sessions recorded yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Professional Footer -->
    <footer class="bg-dark text-white py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Timer Tracker</p>
                </div>
                <div class="col-md-6 text-end">
                    <a href="http://localhost/phpmyadmin" target="_blank" class="text-white text-decoration-none me-3">
                        <i class="fas fa-database me-1"></i> MySQL
                    </a>
                    <a href="admin_control.php" class="text-white text-decoration-none">
                        <i class="fas fa-cog me-1"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Timer control buttons
            const startButton = document.getElementById('start-timer');
            const stopButton = document.getElementById('stop-timer');
            const resumeButton = document.getElementById('resume-timer');
            
            // Add event listeners for timer control buttons
            if (startButton) {
                startButton.addEventListener('click', function() {
                    const timerId = this.getAttribute('data-timer-id');
                    timerAction('start', timerId);
                });
            }
            
            if (stopButton) {
                stopButton.addEventListener('click', function() {
                    const timerId = this.getAttribute('data-timer-id');
                    timerAction('stop', timerId);
                });
            }
            
            if (resumeButton) {
                resumeButton.addEventListener('click', function() {
                    const timerId = this.getAttribute('data-timer-id');
                    timerAction('resume', timerId);
                });
            }
            
            // Timer action function
            function timerAction(action, timerId) {
                // Show loading indicator
                Swal.fire({
                    title: 'Processing...',
                    text: `${action.charAt(0).toUpperCase() + action.slice(1)}ing timer`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Prepare form data
                const formData = new FormData();
                formData.append('timer_id', timerId);
                
                // Send request
                fetch(`api/${action}_timer.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    
                    if (data.success) {
                        // Success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || `Timer ${action}ed successfully`,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the page to reflect changes
                            window.location.reload();
                        });
                    } else {
                        // Error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || `Failed to ${action} timer`
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: `An error occurred: ${error.message}`
                    });
                });
            }
            
            // Add event listener for unlock button
            const unlockButton = document.getElementById('unlock-timer');
            if (unlockButton) {
                unlockButton.addEventListener('click', function() {
                    const timerId = this.getAttribute('data-timer-id');
                    
                    // Confirm before unlocking
                    Swal.fire({
                        title: 'Unlock Timer?',
                        text: 'Are you sure you want to unlock this timer? This will allow modifications to the timer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, unlock it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            timerAction('unlock', timerId);
                        }
                    });
                });
            }
            
            // Note functionality
            const noteButtons = document.querySelectorAll('.note-button');
            const saveNoteButtons = document.querySelectorAll('.save-note');
            const cancelNoteButtons = document.querySelectorAll('.cancel-note');
            
            noteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const logId = this.getAttribute('data-log-id');
                    const noteContent = document.getElementById(`note-content-${logId}`);
                    const noteForm = document.getElementById(`note-form-${logId}`);
                    
                    noteForm.style.display = 'block';
                    noteContent.style.display = 'none';
                });
            });
            
            saveNoteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const logId = this.getAttribute('data-log-id');
                    const noteTextarea = document.getElementById(`note-textarea-${logId}`);
                    const noteForm = document.getElementById(`note-form-${logId}`);
                    const noteText = noteTextarea.value.trim();
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('log_id', logId);
                    formData.append('note', noteText);
                    
                    // Save note via AJAX
                    fetch('api/save_log_note.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Check if elements exist before updating
                            let noteContent = document.getElementById(`note-content-${logId}`);
                            let noteBox = noteContent ? noteContent.closest('.note-box') : null;
                            
                            if (!noteContent) {
                                // Create new note elements if they don't exist
                                const td = noteForm.parentElement;
                                const newNoteBox = document.createElement('div');
                                newNoteBox.className = 'note-box';
                                newNoteBox.innerHTML = `
                                    <pre><code class="note-content" id="note-content-${logId}"></code></pre>
                                `;
                                td.insertBefore(newNoteBox, noteForm);
                                noteContent = document.getElementById(`note-content-${logId}`);
                                noteBox = newNoteBox;
                            }
                            
                            // Process note text for URLs
                            let processedNote = noteText.replace(/</g, '&lt;').replace(/>/g, '&gt;');
                            // Convert URLs to clickable links
                            processedNote = processedNote.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
                            // Add Vanced app icon beside YouTube links
                            processedNote = processedNote.replace(/(<a href="(https?:\/\/(?:www\.)?youtu(?:\.be|be\.com)[^\s<]+)"[^>]*>)(.*?)(<\/a>)/g, 
                                '$1$3$4 <a href="vanced://$2" class="vanced-link" title="Open in Vanced app"><i class="fas fa-play-circle"></i></a>');
                            
                            // Update existing note with processed HTML
                            noteContent.innerHTML = processedNote;
                            
                            // Show the note box and hide the form
                            if (noteBox) {
                                noteBox.style.display = noteText ? 'block' : 'none';
                            }
                            
                            noteForm.style.display = 'none';
                            noteContent.style.display = 'block';
                            
                            // Update note button text
                            const noteButton = document.querySelector(`.note-button[data-log-id="${logId}"]`);
                            if (noteButton) {
                                noteButton.innerHTML = '<i class="fas fa-sticky-note"></i> Edit Note';
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Note saved successfully',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'An error occurred while saving the note'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while saving the note'
                        });
                    });
                });
            });
            
            cancelNoteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const logId = this.getAttribute('data-log-id');
                    const noteForm = document.getElementById(`note-form-${logId}`);
                    const noteContent = document.getElementById(`note-content-${logId}`);
                    
                    noteForm.style.display = 'none';
                    if (noteContent) {
                        noteContent.style.display = 'block';
                    }
                });
            });
        });

        // Function to calculate and display level progress
        function updateLevelProgress() {
            const timerLevel = <?php echo $timer['level'] ?? 1; ?>;
            const timerHours = <?php echo $timer['total_time'] / 3600; ?>;
            const timerXP = <?php echo $timer['experience'] ?? 0; ?>;
            
            // Calculate required hours for current and next level based on the same progression used in the PHP scripts
            let currentLevelHours = 0;
            let nextLevelHours = 0;
            
            if (timerLevel < 10) {
                currentLevelHours = (timerLevel - 1) * 2;
                nextLevelHours = timerLevel * 2;
            } else if (timerLevel < 50) {
                currentLevelHours = 20 + (timerLevel - 10) * 20;
                nextLevelHours = 20 + (timerLevel - 9) * 20;
            } else {
                currentLevelHours = 820 + (timerLevel - 50) * 30;
                nextLevelHours = 820 + (timerLevel - 49) * 30;
            }
            
            // Calculate progress percentage
            let progressPercent = 0;
            if (timerHours <= currentLevelHours) {
                progressPercent = 0;
            } else if (timerHours >= nextLevelHours) {
                progressPercent = 100;
            } else {
                progressPercent = ((timerHours - currentLevelHours) / (nextLevelHours - currentLevelHours)) * 100;
            }
            
            // Update the progress bar
            const progressBar = document.getElementById('level-progress-bar');
            progressBar.style.width = `${progressPercent}%`;
            progressBar.setAttribute('aria-valuenow', progressPercent);
            progressBar.textContent = `${Math.round(progressPercent)}%`;
            
            // Update hours display
            document.getElementById('current-hours').textContent = `Current: ${timerHours.toFixed(1)}h`;
            document.getElementById('next-level-hours').textContent = `Next Level: ${nextLevelHours}h`;
            
            // Update XP display
            document.getElementById('total-xp').textContent = `${timerXP} XP`;
            
            // Fetch upcoming ranks
            fetch('api/get_level_ranks.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let upcomingRanksHtml = '';
                        const currentRank = '<?php echo $timer['rank_name'] ?? 'Novice'; ?>';
                        let foundCurrent = false;
                        let displayedRanks = 0;
                        
                        // Filter to show only the current rank and next 3-4 ranks
                        for (const rank of data.ranks) {
                            // Skip until we find the current rank
                            if (!foundCurrent && rank.rank_name !== currentRank) {
                                continue;
                            }
                            
                            // Mark that we found the current rank
                            if (!foundCurrent && rank.rank_name === currentRank) {
                                foundCurrent = true;
                            }
                            
                            // Only show up to 4 ranks including current
                            if (displayedRanks >= 4) {
                                break;
                            }
                            
                            // Check if this is a new rank we haven't seen yet
                            if (displayedRanks === 0 || upcomingRanksHtml.indexOf(`<strong>${rank.rank_name}</strong>`) === -1) {
                                const isCurrentRank = rank.rank_name === currentRank;
                                upcomingRanksHtml += `
                                    <div class="upcoming-rank-item ${isCurrentRank ? 'current-rank' : ''}">
                                        <div class="rank-name"><strong>${rank.rank_name}</strong></div>
                                        <div class="rank-level">Level ${rank.level}</div>
                                        <div class="rank-hours">${rank.time_format} (${rank.hours_required}h)</div>
                                    </div>
                                `;
                                displayedRanks++;
                            }
                        }
                        
                        document.getElementById('upcoming-ranks').innerHTML = upcomingRanksHtml;
                    }
                })
                .catch(error => {
                    console.error('Error fetching rank data:', error);
                    document.getElementById('upcoming-ranks').innerHTML = '<p class="text-danger">Error loading rank data</p>';
                });
        }

        // Call the function on page load
        document.addEventListener('DOMContentLoaded', updateLevelProgress);
    </script>
</body>
</html>