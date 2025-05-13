<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timer Details</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Digital Font -->
    <link href="https://fonts.cdnfonts.com/css/digital-7-mono" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/dseg@0.46.0/css/dseg.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        :root {
            /* Theme Colors - Dark Mode (Default) */
            --bg-color: #121212;
            --text-color: #e9ecef;
            --card-bg: #1e1e2d;
            --card-border: #2c2c3a;
            --timer-display-bg: #000;
            --timer-display-text: #0ff;
            --navbar-bg: #0d0d17;
            --navbar-text: #ffffff;
            --primary-color: #4d7cfe;
            --secondary-color: #6c757d;
            --success-color: #2fb344;
            --warning-color: #f7b924;
            --danger-color: #e63757;
            --info-color: #39afd1;
            --accent-color: #9776e0;
            --boxshadow-color: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-top: 56px;
            padding-bottom: 70px;
            min-height: 100vh;
        }

        /* Media query for mobile devices */
        @media (max-width: 768px) {
            body {
                padding-top: 0; /* Remove top padding on mobile */
            }
            
            .navbar {
                position: static !important; /* Override Bootstrap's fixed-top on mobile */
            }
        }

        .vertical-timer-card {
            position: relative;
            background: var(--card-bg);
            border: 2px solid var(--accent-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px var(--boxshadow-color);
            transition: all 0.3s ease;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .timer-header {
            text-align: center;
            margin-bottom: 20px;
            padding: 8px;
            border-radius: 8px;
            background-color: rgba(240, 245, 255, 0.1);
        }

        .timer-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .timer-category {
            display: block;
            background-color: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 10px 10px 0 0;
            font-size: 1.1rem;
            font-weight: 600;
            margin: -20px -20px 20px -20px;
            text-align: center;
            box-shadow: 0 2px 4px var(--boxshadow-color);
        }

        .timer-display {
            text-align: center;
            margin: 15px 0 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 110px;
        }

        .timer-current {
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            font-size: 4rem;
            background-color: var(--timer-display-bg);
            color: var(--timer-display-text);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 320px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
            margin-bottom: 20px;
            letter-spacing: 3px;
            text-shadow: 0 0 10px var(--timer-display-text);
            height: 110px;
            word-break: keep-all;
            white-space: nowrap;
        }

        .timer-current.running {
            animation: timerGlow 2s infinite;
        }

        @keyframes timerGlow {
            0% { box-shadow: 0 0 5px rgba(0, 255, 255, 0.3); }
            50% { box-shadow: 0 0 20px rgba(0, 255, 255, 0.5); }
            100% { box-shadow: 0 0 5px rgba(0, 255, 255, 0.3); }
        }

        .timer-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 15px 0 10px;
            width: 100%;
        }

        .btn-timer-control {
            padding: 15px 40px;
            font-size: 1.4rem;
            min-width: 220px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin: 0 10px;
            font-weight: 600;
        }

        .btn-success.btn-timer-control {
            background-color: var(--success-color) !important;
            color: white !important;
        }

        .btn-warning.btn-timer-control {
            background-color: var(--warning-color) !important;
            color: #212529 !important;
        }

        .btn-info.btn-timer-control {
            background-color: var(--info-color) !important;
            color: white !important;
        }

        .timer-stats {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
        }

        .timer-total {
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        [data-theme="dark"] .timer-total {
            background-color: #2a2a2a;
            color: #e0e0e0;
        }

        .timer-total-value {
            color: var(--timer-display-text);
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            letter-spacing: 1px;
            text-shadow: 0 0 5px var(--timer-display-text);
        }

        .timer-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 10px;
            min-width: 100px;
        }

        .status-running {
            background-color: var(--success-color);
            color: white;
            animation: pulse 2s infinite;
        }

        .status-paused {
            background-color: var(--warning-color);
            color: #212529;
        }

        .status-idle {
            background-color: var(--secondary-color);
            color: white;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .back-button {
            margin: 20px;
            padding: 10px 20px;
            font-size: 1.1rem;
            color: var(--text-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .back-button:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .sticky-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--secondary-color);
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.3s ease;
            padding: 5px;
            z-index: 5;
        }

        .sticky-toggle:hover {
            opacity: 1;
        }

        .sticky-toggle.sticky-on {
            color: var(--warning-color);
            transform: rotate(45deg);
        }

        .sticky-timer {
            border-left: 4px solid var(--warning-color);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .timer-current {
                font-size: 2.5rem;
                min-width: 260px;
                height: 80px;
                padding: 15px;
            }

            .btn-timer-control {
                padding: 15px 30px;
                font-size: 1.3rem;
                min-width: 190px;
                height: 65px;
            }

            .timer-name {
                font-size: 1.5rem;
            }

            .timer-category {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .vertical-timer-card {
                margin: 15px;
                padding: 15px;
            }

            .timer-current {
                font-size: 2rem;
                min-width: 220px;
                height: 70px;
                padding: 10px;
            }

            .timer-display {
                min-height: 160px !important;
                margin-bottom: 5px;
            }

            .timer-controls {
                margin-top: 5px;
            }
        }

        /* Navbar Styles */
        .navbar {
            background-color: var(--navbar-bg) !important;
            box-shadow: 0 4px 10px var(--boxshadow-color);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 20px;
            color: var(--navbar-text) !important;
        }
        
        .navbar .container {
            max-width: 1200px;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            margin-left: 20px;
            color: var(--navbar-text) !important;
        }
        
        .navbar-nav .nav-link:hover {
            opacity: 0.8;
        }

        /* Timer Count Badge */
        .timer-count-badge {
            display: inline-flex !important; /* Force display as flex */
            visibility: visible !important; /* Ensure visibility */
            justify-content: center;
            align-items: center;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
            height: 20px;
            width: 20px;
            margin-left: 8px;
            position: relative;
            top: -1px;
            z-index: 1000; /* Ensure it's above other elements */
            opacity: 1 !important; /* Force full opacity */
        }

        /* Mobile Stop All Button */
        #stop-all-btn-mobile {
            margin-left: auto;
            margin-right: 10px;
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        #stop-all-btn-mobile.active {
            color: #ffc107 !important;
        }

        /* Timer Total Display */
        .timer-total {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
            color: var(--text-color);
        }

        .timer-total-value {
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            background-color: var(--timer-display-bg);
            color: var(--timer-display-text);
            padding: 8px 15px;
            border-radius: 5px;
            letter-spacing: 1px;
            text-shadow: 0 0 5px var(--timer-display-text);
            box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
            font-size: 1.2rem;
        }

        /* Timer Logs Styling */
        #timer-logs-container {
            margin-bottom: 50px;
        }
        
        #timer-logs-container .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--boxshadow-color);
        }
        
        #timer-logs-container .card-header {
            background-color: rgba(240, 245, 255, 0.05);
            border-bottom: 1px solid var(--card-border);
            padding: 15px 20px;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        
        #timer-logs-container .card-body {
            padding: 20px;
        }
        
        /* Day Card Grid Layout */
        .days-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .day-card {
            flex: 0 0 calc(33.333% - 10px);
            background-color: #232336; /* Lighter background color */
            border: 1px solid var(--card-border);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            margin-bottom: 5px;
        }
        
        .day-card:hover {
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }

        /* Timer Log Table Container */
        .day-card .table-responsive {
            padding: 0;
        }

        .day-card .timer-log-table {
            margin-bottom: 0 !important;
            color: var(--text-color);
            font-size: 0.9rem;
            border-collapse: collapse;
            width: 100%;
            background-color: #232336;
        }

        .day-card .timer-log-table td {
            padding: 5px 8px;
            vertical-align: middle;
            border-color: rgba(255, 255, 255, 0.03);
            color: #abb3c4 !important;
            font-size: 0.9rem;
        }

        /* Style for columns to match the image */
        .day-card .timer-log-table td:nth-child(1) { /* START TIME column */
            width: 15%;
            color: white !important;
            font-size: 0.85rem;
            white-space: nowrap;
            padding-left: 15px;
        }

        .day-card .timer-log-table td:nth-child(2) { /* END TIME column */
            width: 15%;
            color: white !important;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .day-card .timer-log-table td:nth-child(3) { /* DURATION column */
            width: 15%;
            color: #ffc107 !important;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            font-size: 0.9rem;
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
        }

        .day-card .timer-log-table td:nth-child(4) { /* NOTES column */
            width: 55%;
        }

        /* Enhance hover effect for rows */
        .day-card .timer-log-table tbody tr:hover {
            background-color: #2a2a46;
        }
        
        @media (max-width: 1200px) {
            .day-card {
                flex: 0 0 calc(50% - 10px);
            }
        }
        
        @media (max-width: 768px) {
            .day-card {
                flex: 0 0 100%;
            }
        }
        
        .day-card-header {
            background-color: #0d0d17;
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .day-total {
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: normal;
            background-color: #000;
            padding: 6px 12px;
            border-radius: 4px;
            color: #ffb700;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            text-shadow: 0 0 5px rgba(255, 183, 0, 0.5);
        }
        
        .day-card .table {
            margin-bottom: 0;
        }
        
        #timer-logs-table {
            margin-bottom: 0 !important;
            color: var(--text-color);
            font-size: 0.9rem;
            border-collapse: collapse;
            width: 100%;
        }
        
        #timer-logs-table td {
            padding: 6px 8px;
            vertical-align: middle;
            border-color: rgba(240, 245, 255, 0.05);
            color: #abb3c4 !important;
            font-size: 0.9rem;
        }
        
        #timer-logs-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Style for columns to maintain layout without headers */
        .timer-log-table td:nth-child(1) { /* START TIME column */
            width: 15%;
            color: white !important;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        .timer-log-table td:nth-child(2) { /* END TIME column */
            width: 15%;
            color: white !important;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        /* Duration column styling to match image */
        .timer-log-table td:nth-child(3) { /* DURATION column */
            width: 15%;
            color: #ffc107 !important;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
            font-size: 0.9rem;
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
        }
        
        .timer-log-table td:nth-child(4) { /* NOTES column */
            width: 55%;
        }
        
        /* Enhance hover effect for rows */
        .timer-log-table tbody tr:hover {
            background-color: #2a2a46;
        }
        
        #logs-loading {
            padding: 30px;
        }
        
        /* Compact layout for the notes column */
        .note-text {
            max-width: none;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            color: var(--text-color);
            padding: 0;
            line-height: 1.4;
            font-size: 0.85rem;
            width: 100%;
            text-align: left;
        }
        
        /* Make edit button always vertically aligned to the top */
        .d-flex.align-items-center {
            align-items: flex-start !important;
        }
        
        .btn-link {
            color: var(--primary-color) !important;
        }
        
        .btn-outline-secondary {
            color: var(--text-color) !important;
            border-color: var(--secondary-color) !important;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--secondary-color) !important;
            color: white !important;
        }
        
        @media (max-width: 768px) {
            .note-text {
                max-width: 150px;
            }
            
            .timer-log-table th, 
            .timer-log-table td {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
        
        #refresh-logs-btn {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        #refresh-logs-btn:hover {
            background-color: var(--accent-color);
            color: white;
        }

        /* Make code note blocks more compact */
        .code-note-block {
            background-color: #151825;
            border-radius: 4px;
            padding: 6px 8px;
            margin: 0;
            color: #e2e8f0;
            font-family: monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 100%;
            overflow-x: auto;
            border-left: 3px solid #3b82f6;
        }

        /* Update the Add Note button to match image */
        .add-note-btn {
            display: flex !important;
            align-items: center;
            justify-content: center;
            background-color: #2d2d46 !important;
            color: #ffffff !important;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 0.8rem;
            border: none;
            gap: 5px;
            height: 28px;
            transition: all 0.2s ease;
        }

        .add-note-btn:hover {
            background-color: #3d3d58 !important;
        }

        /* Update in-progress text styling */
        .in-progress-text {
            color: #4ade80; 
            font-weight: 600; 
            font-size: 0.85rem;
        }

        .timer-log-table tbody tr {
            background-color: #1e1e32; /* Lighter background for rows */
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Pagination styling */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 10px;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
            background-color:rgb(57, 69, 85);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #2d2d46;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 5px;
            width: 100%;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pagination li {
            display: inline-block;
        }

        .pagination li a {
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #232336;
            color: #fff;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solidrgb(0, 0, 0);
            min-width: 40px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .pagination li.active a {
            background-color: #4d7cfe;
            color: white;
            border-color: #4d7cfe;
        }

        .pagination li a:hover:not(.active) {
            background-color: #2a2a46;
        }

        /* Responsive pagination */
        @media (max-width: 576px) {
            .pagination-container {
                max-width: 100%;
                margin: 20px 10px 10px;
            }
            
            .pagination li a {
                padding: 6px 10px;
                min-width: 35px;
                font-size: 0.9rem;
            }
        }

        /* Search styling for logs */
        .card-header .search-container {
            max-width: 300px;
            flex: 1;
        }

        .card-header .search-input {
            width: 100%;
            padding: 8px 12px;
            font-size: 0.9rem;
            background-color: #232336;
            border: 1px solid #2d2d46;
            border-radius: 6px;
            color: white;
            transition: all 0.2s ease;
        }

        .card-header .search-input:focus {
            outline: none;
            border-color: #4d7cfe;
            box-shadow: 0 0 0 2px rgba(77, 124, 254, 0.25);
        }

        .card-header .search-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Add space above footer on mobile */
        @media (max-width: 768px) {
            #timer-logs-container {
                margin-bottom: 80px;
            }
        }

        /* Add more space above footer on smaller mobile screens */
        @media (max-width: 576px) {
            #timer-logs-container {
                margin-bottom: 120px;
            }
            
            body {
                padding-bottom: 100px;
            }
        }

        /* Add extra space above footer specifically for Samsung A30 */
        @media (max-width: 425px) {
            #timer-logs-container {
                margin-bottom: 200px;
            }
            
            body {
                padding-bottom: 180px;
            }
        }

        /* Samsung A30 specific footer spacing */
        @media (max-width: 360px) {
            #timer-logs-container {
                margin-bottom: 300px;
            }
            
            body {
                padding-bottom: 300px;
            }
            
            body:after {
                content: '';
                display: block;
                height: 250px;
                width: 100%;
                position: fixed;
                bottom: 0;
                background: transparent;
            }
        }

        /* Add style for badge counter to prevent flicker */
        document.addEventListener('DOMContentLoaded', function() {
            // Add CSS to make badge transitions smoother
            const styleEl = document.createElement('style');
            styleEl.textContent = `
                .timer-count-badge {
                    transition: none !important;
                }
            `;
            document.head.appendChild(styleEl);
        });

        /* Update running timers count */
        function updateRunningTimersCount(count) {
            const desktopBadge = document.getElementById('timer-count-badge');
            const mobileBadge = document.getElementById('timer-count-badge-mobile');
            
            // Only update the display if the count has actually changed
            if (count !== previousRunningCount) {
                if (count > 0) {
                    if (desktopBadge) {
                        desktopBadge.style.display = 'inline-flex';
                        desktopBadge.textContent = count;
                    }
                    if (mobileBadge) {
                        mobileBadge.style.display = 'inline-flex';
                        mobileBadge.textContent = count;
                    }
                } else {
                    if (desktopBadge) desktopBadge.style.display = 'none';
                    if (mobileBadge) mobileBadge.style.display = 'none';
                }
                
                // Update the previous count
                previousRunningCount = count;
            }
        }

        // Simple, reliable function to update the running timers count
        function updateRunningTimersCount(count) {
            const desktopBadge = document.getElementById('timer-count-badge');
            const mobileBadge = document.getElementById('timer-count-badge-mobile');
            
            if (count > 0) {
                // Always set content and display for badges
                if (desktopBadge) {
                    desktopBadge.style.display = 'inline-flex';
                    desktopBadge.textContent = count;
                }
                if (mobileBadge) {
                    mobileBadge.style.display = 'inline-flex';
                    mobileBadge.textContent = count;
                }
            } else {
                // Only hide badges when count is truly zero
                if (desktopBadge) desktopBadge.style.display = 'none';
                if (mobileBadge) mobileBadge.style.display = 'none';
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Timer System</a>
            <!-- Mobile Stop All Button - visible only on small screens -->
            <a class="nav-link d-lg-none me-2" href="#" id="stop-all-btn-mobile">
                <i class="fas fa-stop-circle"></i> Stop All
                <span id="timer-count-badge-mobile" class="timer-count-badge" style="display: none;">0</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item d-none d-lg-block">
                        <a class="nav-link" href="#" id="stop-all-btn">
                            <i class="fas fa-stop-circle me-1"></i> Stop All Timers
                            <span id="timer-count-badge" class="timer-count-badge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addTimerModal">
                            <i class="fas fa-plus-circle me-1"></i> Add Timer
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 20px;">
        <!-- Removed the "Back to Timers" button here -->
        
        <div id="timer-container">
            <!-- Timer will be loaded here via JavaScript -->
        </div>
        
        <!-- Timer Sessions Log Section -->
        <div class="mt-4" id="timer-logs-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i> Sessions
                    </h5>
                    <div class="search-container">
                        <input type="text" id="logs-search" class="search-input" placeholder="Search days or notes...">
                    </div>
                </div>
                <div class="card-body">
                    <div id="logs-table-container">
                        <div class="days-grid" id="days-grid-container">
                            <!-- Days will be loaded here via JavaScript -->
                        </div>
                        <div class="pagination-container" id="pagination-container">
                            <!-- Pagination will be added here via JavaScript -->
                        </div>
                    </div>
                    <div id="no-logs-message" class="text-center py-3" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i> No previous sessions found.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Timer Modal -->
    <div class="modal fade" id="addTimerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Timer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTimerForm">
                        <div class="mb-3">
                            <label for="timerName" class="form-label">Timer Name</label>
                            <input type="text" class="form-control" id="timerName" required>
                        </div>
                        <div class="mb-3">
                            <label for="timerCategory" class="form-label">Category</label>
                            <select class="form-select" id="timerCategory" required>
                                <option value="">Select a category</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Timer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.js"></script>
    
    <script>
        // Get timer ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const timerId = urlParams.get('id');
        let localTimer = null;
        let updateInterval = null;
        let lastLoadTime = 0; // Track last data load time
        const REFRESH_THRESHOLD = 1000; // Minimum time between refreshes in ms

        // Load timer data on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (timerId) {
                loadTimerData();
                // Use requestAnimationFrame for smoother updates instead of setInterval
                function updateLoop() {
                    const now = Date.now();
                    if (now - lastLoadTime >= REFRESH_THRESHOLD) {
                        loadTimerData();
                        lastLoadTime = now;
                    }
                    requestAnimationFrame(updateLoop);
                }
                requestAnimationFrame(updateLoop);
                
                // Load categories for Add Timer modal
                loadCategories();
                // Load timer logs
                loadTimerLogs();
            } else {
                showError('No timer ID provided');
            }
            
            // Event listener for search input
            const searchInput = document.getElementById('logs-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    searchLogs(this.value);
                });
            }
        });

        // Load categories for the Add Timer modal
        function loadCategories() {
            fetch('api/get_categories.php')
                .then(response => {
                    // First check if the response is ok (status in 200-299 range)
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    // Try to parse the response as JSON
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            console.error("JSON parse error:", error);
                            console.error("Raw response:", text);
                            throw new Error('Failed to parse server response as JSON');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('timerCategory');
                        // Clear existing options first
                        select.innerHTML = '<option value="">Select a category</option>';
                        
                        // Add categories
                        data.categories.forEach(category => {
                            const option = document.createElement('option');
                            option.value = category.id;
                            option.textContent = category.name;
                            select.appendChild(option);
                        });
                    } else {
                        throw new Error(data.message || 'Failed to load categories');
                    }
                })
                .catch(error => {
                    // console.error('Error loading categories:', error);
                    // Show error in select dropdown
                    const select = document.getElementById('timerCategory');
                    select.innerHTML = '<option value="">Error loading categories</option>';
                    select.classList.add('is-invalid');
                });
        }

        // Handle Add Timer form submission
        document.getElementById('addTimerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('name', document.getElementById('timerName').value);
            formData.append('category_id', document.getElementById('timerCategory').value);
            
            fetch('api/add_timer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#addTimerModal').modal('hide');
                    document.getElementById('addTimerForm').reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Timer added successfully'
                    });
                } else {
                    // Don't show error message
                }
            })
            .catch(error => {
                // Don't show error message
            });
        });

        // Update running timers count
        function updateRunningTimersCount(count) {
            const desktopBadge = document.getElementById('timer-count-badge');
            const mobileBadge = document.getElementById('timer-count-badge-mobile');
            
            // Only update the display if the count has actually changed
            if (count !== previousRunningCount) {
                if (count > 0) {
                    if (desktopBadge) {
                        desktopBadge.style.display = 'inline-flex';
                        desktopBadge.textContent = count;
                    }
                    if (mobileBadge) {
                        mobileBadge.style.display = 'inline-flex';
                        mobileBadge.textContent = count;
                    }
                } else {
                    if (desktopBadge) desktopBadge.style.display = 'none';
                    if (mobileBadge) mobileBadge.style.display = 'none';
                }
                
                // Update the previous count
                previousRunningCount = count;
            }
        }

        // Global variable to track previous count state
        let previousRunningCount = 0;

        function loadTimerData() {
            fetch(`api/get_timer.php?id=${timerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderTimer(data.timer);
                        setupTimerUpdates(data.timer);
                        
                        // Always check for running timers after loading the timer data
                        // This ensures the badge always gets updated
                        checkRunningTimers();
                    } else {
                        showError(data.message || 'Failed to load timer');
                    }
                })
                .catch(error => {
                    // console.error('Error loading timer:', error);
                    showError('Failed to load timer');
                });
        }

        function renderTimer(timer) {
            const timerHtml = `
                <div class="vertical-timer-card ${timer.is_sticky ? 'sticky-timer' : ''}" data-timer-id="${timer.id}">
                    <button class="sticky-toggle ${timer.is_sticky ? 'sticky-on' : ''}" 
                            data-action="toggle-sticky" 
                            data-timer-id="${timer.id}" 
                            data-sticky="${timer.is_sticky ? 0 : 1}"
                            title="${timer.is_sticky ? 'Unstick timer' : 'Stick timer'}">
                        <i class="fas fa-thumbtack"></i>
                    </button>
                    
                    <div class="timer-header">
                        <h1 class="timer-name">${timer.name}</h1>
                        <div class="timer-category">${timer.category_name}</div>
                    </div>
                    
                    <div class="timer-display">
                        <div class="timer-current ${timer.status === 'running' ? 'running' : ''}" data-timer-id="${timer.id}">
                            ${formatTime(timer.current_elapsed || 0)}
                        </div>
                    </div>
                    
                    <div class="timer-controls">
                        ${getControlButtons(timer)}
                    </div>
                    
                    <div class="timer-stats">
                        <div class="timer-status status-${timer.status}">
                            ${timer.status.charAt(0).toUpperCase() + timer.status.slice(1)}
                        </div>
                        <div class="timer-total">
                            <i class="far fa-clock"></i>
                            <span class="timer-total-value">${formatTimeSimple(timer.total_time)}</span>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('timer-container').innerHTML = timerHtml;
            attachEventListeners(timer);
            updateRunningTimersCount(timer.running_count || 0);
            
            // Update styles after rendering
            updateTimerDisplayStyles();
        }

        function getControlButtons(timer) {
            if (timer.status === 'idle') {
                return `
                    <button class="btn btn-success btn-timer-control" data-action="start">
                        <i class="fas fa-play me-2"></i> Start
                    </button>
                `;
            } else if (timer.status === 'running') {
                return `
                    <button class="btn btn-warning btn-timer-control" data-action="stop">
                        <i class="fas fa-stop me-2"></i> Stop
                    </button>
                `;
            } else if (timer.status === 'paused') {
                return `
                    <button class="btn btn-info btn-timer-control" data-action="resume">
                        <i class="fas fa-play me-2"></i> Resume
                    </button>
                    <button class="btn btn-warning btn-timer-control" data-action="stop">
                        <i class="fas fa-stop me-2"></i> Stop
                    </button>
                `;
            }
            return '';
        }

        function attachEventListeners(timer) {
            // Start button
            const startBtn = document.querySelector('[data-action="start"]');
            if (startBtn) {
                startBtn.addEventListener('click', () => startTimer(timer.id));
            }
            
            // Stop button
            const stopBtn = document.querySelector('[data-action="stop"]');
            if (stopBtn) {
                stopBtn.addEventListener('click', () => stopTimer(timer.id));
            }
            
            // Resume button
            const resumeBtn = document.querySelector('[data-action="resume"]');
            if (resumeBtn) {
                resumeBtn.addEventListener('click', () => resumeTimer(timer.id));
            }

            // Sticky toggle
            const stickyBtn = document.querySelector('[data-action="toggle-sticky"]');
            if (stickyBtn) {
                stickyBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const stickyValue = stickyBtn.getAttribute('data-sticky');
                    toggleStickyTimer(timer.id, stickyValue);
                });
            }
        }

        function setupTimerUpdates(timer) {
            if (timer.status === 'running') {
                if (!localTimer) {
                    localTimer = {
                        startTime: Date.now(),
                        baseElapsed: timer.current_elapsed || 0
                    };
                }
                
                if (!updateInterval) {
                    updateInterval = setInterval(updateTimerDisplay, 100);
                }
            } else {
                if (updateInterval) {
                    clearInterval(updateInterval);
                    updateInterval = null;
                }
                localTimer = null;
            }
        }

        function updateTimerDisplay() {
            if (!localTimer) return;
            
            const elapsed = localTimer.baseElapsed + (Date.now() - localTimer.startTime) / 1000;
            const timerDisplay = document.querySelector('.timer-current');
            if (timerDisplay) {
                timerDisplay.innerHTML = formatTime(elapsed);
            }
        }

        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            const ms = Math.floor((seconds - Math.floor(seconds)) * 100);
            
            // Format hours with at least 2 digits (padded with zero if needed) and commas for thousands
            const formattedHours = hours < 10 ? '0' + hours : hours.toLocaleString();
            const displayHours = formattedHours;
            const displayMinutes = String(minutes).padStart(2, '0');
            const displaySeconds = String(secs).padStart(2, '0');
            const displayMs = String(ms).padStart(2, '0');
            
            // Format with spans and small labels
            return `
                <div class="timer-component timer-hours-component">
                    <div class="timer-value">${displayHours}</div>
                    <div class="timer-label">h</div>
                </div>
                <div class="timer-component timer-minutes-component">
                    <div class="timer-value">${displayMinutes}</div>
                    <div class="timer-label">m</div>
                </div>
                <div class="timer-component timer-seconds-component">
                    <div class="timer-value">${displaySeconds}</div>
                    <div class="timer-label">s</div>
                </div>
                <div class="timer-component timer-ms-component">
                    <div class="timer-value">${displayMs}</div>
                </div>
            `;
        }

        function formatTimeSimple(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            
            // Format hours with at least 2 digits
            const formattedHours = hours < 10 ? '0' + hours : hours.toLocaleString();
            const displayHours = formattedHours;
            const displayMinutes = String(minutes).padStart(2, '0');
            const displaySeconds = String(secs).padStart(2, '0');
            
            return `<span class="total-time-value">${displayHours}<small>h</small> ${displayMinutes}<small>m</small> ${displaySeconds}<small>s</small></span>`;
        }

        function showError(message) {
            // Disable error messages
            return;
            
            // Original code - commented out
            /*
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
            });
            */
        }

        // Timer control functions
        function startTimer(timerId) {
            timerAction('start_timer.php', timerId);
        }

        function stopTimer(timerId) {
            timerAction('stop_timer.php', timerId);
        }

        function resumeTimer(timerId) {
            timerAction('resume_timer.php', timerId);
        }

        function toggleStickyTimer(timerId, sticky) {
            const formData = new FormData();
            formData.append('timer_id', timerId);
            formData.append('sticky', sticky);
            
            fetch('api/sticky_timer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTimerData(); // Reload timer data to reflect changes
                } else {
                    showError(data.message || 'Failed to update timer sticky status');
                }
            })
            .catch(error => {
                // console.error('Error toggling sticky status:', error);
                showError('Failed to update timer sticky status');
            });
        }

        function timerAction(endpoint, timerId) {
            const formData = new FormData();
            formData.append('timer_id', timerId);
            
            fetch(`api/${endpoint}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTimerData(); // Reload timer data to reflect changes
                } else {
                    showError(data.message || 'Failed to perform timer action');
                }
            })
            .catch(error => {
                // console.error('Error performing timer action:', error);
                showError('Failed to perform timer action');
            });
        }

        // Add function to check running timers count
        function checkRunningTimers() {
            fetch('api/get_timers.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Count running timers
                        const runningCount = data.timers.filter(timer => timer.status === 'running').length;
                        updateRunningTimersCount(runningCount);
                    }
                })
                .catch(error => {
                    // console.error('Error checking running timers:', error);
                });
        }

        // Simple, reliable function to check running timers count
        function checkRunningTimers() {
            fetch('api/get_timers.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Count running timers
                        const runningCount = data.timers.filter(timer => timer.status === 'running').length;
                        
                        // Always update the counter every time
                        updateRunningTimersCount(runningCount);
                    }
                })
                .catch(error => {
                    // console.error('Error checking running timers:', error);
                });
        }

        // Add Stop All functionality with enhanced confirmation
        function stopAllTimers() {
            Swal.fire({
                title: 'Stop All Timers',
                text: 'Are you sure you want to stop all running timers? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, stop all timers',
                cancelButtonText: 'No, keep them running',
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator 
                    Swal.fire({
                        title: 'Stopping all timers...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                    });
                    
                    fetch('api/stop_all_timers.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'All timers have been stopped',
                                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color')
                            });
                            loadTimerData(); // Reload current timer data
                            updateRunningTimersCount(0); // Reset counter
                        } else {
                            // Don't show error message
                        }
                    })
                    .catch(error => {
                        // Don't show error message
                    });
                }
            });
        }

        // Add event listeners for Stop All buttons
        document.addEventListener('DOMContentLoaded', function() {
            const stopAllBtn = document.getElementById('stop-all-btn');
            const stopAllBtnMobile = document.getElementById('stop-all-btn-mobile');
            
            if (stopAllBtn) {
                stopAllBtn.addEventListener('click', stopAllTimers);
            }
            
            if (stopAllBtnMobile) {
                stopAllBtnMobile.addEventListener('click', stopAllTimers);
            }
            
            // Initial check for running timers
            checkRunningTimers();
        });

        // Add event listeners for Stop All buttons
        document.addEventListener('DOMContentLoaded', function() {
            const stopAllBtn = document.getElementById('stop-all-btn');
            const stopAllBtnMobile = document.getElementById('stop-all-btn-mobile');
            
            if (stopAllBtn) {
                stopAllBtn.addEventListener('click', stopAllTimers);
            }
            
            if (stopAllBtnMobile) {
                stopAllBtnMobile.addEventListener('click', stopAllTimers);
            }
            
            // Force badge check immediately after DOM loads
            setTimeout(function() {
                checkRunningTimers();
            }, 500);
        });

        // Add event listeners for Stop All buttons - optimized version
        document.addEventListener('DOMContentLoaded', function() {
            const stopAllBtn = document.getElementById('stop-all-btn');
            const stopAllBtnMobile = document.getElementById('stop-all-btn-mobile');
            
            if (stopAllBtn) {
                stopAllBtn.addEventListener('click', stopAllTimers);
            }
            
            if (stopAllBtnMobile) {
                stopAllBtnMobile.addEventListener('click', stopAllTimers);
            }
            
            // Force badge check immediately and again after a delay to ensure it's visible
            checkRunningTimers();
            setTimeout(checkRunningTimers, 500);
        });

        // Update styles for timer display formatting
        document.addEventListener('DOMContentLoaded', function() {
            // Add style element to document head
            const styleElement = document.createElement('style');
            styleElement.textContent = `
                .timer-current {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: 'Digital-7', 'DSEG7 Classic', monospace;
                    padding: 10px 15px;
                    gap: 15px;
                    background-color: #000;
                    color: #0ff;
                    border-radius: 15px;
                    box-shadow: 0 0 10px rgba(0, 255, 255, 0.2);
                    letter-spacing: 0;
                    width: 100%;
                    max-width: 500px;
                    margin: 0 auto;
                }
                
                .timer-component {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                }
                
                .timer-value {
                    font-size: 3.8rem;
                    line-height: 1;
                    text-shadow: 0 0 10px rgba(0, 255, 255, 0.7);
                }
                
                .timer-ms-component .timer-value {
                    font-size: 2rem;
                    opacity: 0.7;
                }
                
                .timer-label {
                    font-size: 0.8rem;
                    text-transform: uppercase;
                    opacity: 0.6;
                    margin-top: 3px;
                    font-family: 'Rajdhani', sans-serif;
                    font-weight: 600;
                    letter-spacing: 1px;
                }
                
                /* Total time styling */
                .total-time-value {
                    font-family: 'Digital-7', 'DSEG7 Classic', monospace;
                    color: var(--timer-display-text);
                    text-shadow: 0 0 5px var(--timer-display-text);
                    font-size: 1.4rem;
                }
                
                .total-time-value small {
                    font-family: 'Rajdhani', sans-serif;
                    font-weight: 600;
                    font-size: 0.8rem;
                    opacity: 0.7;
                    text-transform: uppercase;
                    margin-left: 1px;
                    position: relative;
                    top: -1px;
                }
                
                .timer-total {
                    display: flex;
                    align-items: center;
                    background-color: rgba(0, 0, 0, 0.2);
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-weight: bold;
                    margin: 10px 0;
                    color: var(--text-color);
                }
                
                .timer-total i {
                    margin-right: 8px;
                    color: var(--timer-display-text);
                    opacity: 0.8;
                }
                
                /* Responsive adjustments */
                @media (max-width: 768px) {
                    .timer-value {
                        font-size: 3rem;
                    }
                    
                    .timer-ms-component .timer-value {
                        font-size: 1.6rem;
                    }
                    
                    .timer-label {
                        font-size: 0.7rem;
                    }
                    
                    .timer-current {
                        gap: 10px;
                        padding: 12px 15px;
                        max-width: 550px; /* Wider on tablet */
                    }
                    
                    .timer-display {
                        min-height: 160px !important;
                    }
                    
                    .btn-timer-control {
                        padding: 15px 30px;
                        font-size: 1.3rem;
                        min-width: 190px;
                        height: 65px;
                    }
                }
                
                @media (max-width: 576px) {
                    .timer-value {
                        font-size: 2.4rem;
                    }
                    
                    .timer-ms-component .timer-value {
                        font-size: 1.4rem;
                    }
                    
                    .timer-label {
                        font-size: 0.65rem;
                    }
                    
                    .timer-current {
                        gap: 12px;
                        padding: 15px;
                        min-height: 120px !important;
                        max-width: 100%; /* Full width on mobile */
                        width: 95%;
                    }
                    
                    .timer-display {
                        min-height: 160px !important;
                        margin-bottom: 5px;
                    }
                    
                    .timer-controls {
                        margin-top: 5px;
                    }
                    
                    .vertical-timer-card {
                        padding: 15px;
                        max-width: 100% !important;
                    }
                    
                    .total-time-value {
                        font-size: 1.2rem;
                    }
                    
                    .btn-timer-control {
                        padding: 15px 20px;
                        font-size: 1.5rem;
                        min-width: 90%;
                        height: 75px; /* Increased height */
                        margin: 10px auto;
                        font-weight: 700;
                    }
                    
                    .timer-controls {
                        flex-direction: column;
                        width: 100%;
                        padding: 10px 0;
                    }
                }
                
                /* Control button colors */
                .btn-success.btn-timer-control {
                    background-color: var(--success-color) !important;
                    color: white !important;
                }
                
                .btn-warning.btn-timer-control {
                    background-color: var(--warning-color) !important;
                    color: #212529 !important;
                }
                
                .btn-info.btn-timer-control {
                    background-color: var(--info-color) !important;
                    color: white !important;
                }
                
                /* Dark mode specific adjustments */
                [data-theme="dark"] .timer-total {
                    background-color: rgba(255, 255, 255, 0.1);
                }
            `;
            document.head.appendChild(styleElement);
        });

        // Update styles for timer display
        function updateTimerDisplayStyles() {
            const timerDisplay = document.querySelector('.timer-display');
            if (timerDisplay) {
                timerDisplay.style.minHeight = '170px';
                timerDisplay.style.marginBottom = '10px';
                
                // Adjust for mobile
                if (window.innerWidth <= 576) {
                    timerDisplay.style.minHeight = '160px';
                    timerDisplay.style.width = '100%';
                    timerDisplay.style.marginBottom = '5px';
                }
            }
            
            const timerCurrent = document.querySelector('.timer-current');
            if (timerCurrent) {
                timerCurrent.style.width = '100%';
                timerCurrent.style.maxWidth = '500px';
                timerCurrent.style.minHeight = '120px';
                timerCurrent.style.margin = '0 auto';
                
                // Adjust for mobile
                if (window.innerWidth <= 576) {
                    timerCurrent.style.minHeight = '120px';
                    timerCurrent.style.maxWidth = '95%';
                    timerCurrent.style.width = '95%';
                    timerCurrent.style.padding = '15px';
                }
            }
            
            // Make the whole timer card responsive on small screens
            if (window.innerWidth <= 576) {
                const timerCard = document.querySelector('.vertical-timer-card');
                if (timerCard) {
                    timerCard.style.maxWidth = '100%';
                    timerCard.style.margin = '10px auto';
                    timerCard.style.padding = '15px';
                    timerCard.style.width = '95%';
                }
                
                // Make buttons bigger on mobile
                const buttons = document.querySelectorAll('.btn-timer-control');
                buttons.forEach(button => {
                    button.style.padding = '15px 20px';
                    button.style.fontSize = '1.5rem';
                    button.style.width = '90%';
                    button.style.margin = '10px auto';
                    button.style.height = '75px'; // Increased height
                    button.style.fontWeight = '700';
                });
                
                // Make the controls column direction
                const controls = document.querySelector('.timer-controls');
                if (controls) {
                    controls.style.flexDirection = 'column';
                    controls.style.width = '100%';
                    controls.style.padding = '10px 0';
                }
            } else {
                // Desktop button sizing
                const buttons = document.querySelectorAll('.btn-timer-control');
                buttons.forEach(button => {
                    button.style.padding = '15px 40px';
                    button.style.fontSize = '1.4rem';
                    button.style.minWidth = '220px'; // Increased width
                    button.style.height = '70px'; // Increased height
                    button.style.margin = '0 10px';
                    button.style.fontWeight = '600';
                    button.style.borderRadius = '10px';
                });
                
                // If there are multiple buttons, adjust container
                const controls = document.querySelector('.timer-controls');
                if (controls && controls.childElementCount > 1) {
                    controls.style.gap = '20px';
                    controls.style.justifyContent = 'center';
                }
            }
            
            const timerControls = document.querySelector('.timer-controls');
            if (timerControls) {
                timerControls.style.marginTop = '10px';
                
                // Adjust for mobile
                if (window.innerWidth <= 576) {
                    timerControls.style.marginTop = '5px';
                }
            }
        }

        // Also call updateTimerDisplayStyles on window resize
        window.addEventListener('resize', updateTimerDisplayStyles);

        // Global variable to store all logs
        let allTimerLogs = [];
        
        // Function to search logs
        function searchLogs(query) {
            if (!allTimerLogs.length) return;
            
            query = query.toLowerCase().trim();
            
            if (query === '') {
                // If search is empty, clear search and return to page 1
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('show_all');
                url.searchParams.set('page', 1);
                window.history.pushState({}, '', url);
                
                // Render all logs with pagination
                loadTimerLogs();
                return;
            }
            
            // Split search terms
            const searchTerms = query.split(' ').filter(term => term.length > 0);
            
            // Filter logs based on search query
            const filteredLogs = allTimerLogs.filter(log => {
                // Check if day matches
                const dateParts = log.start_time_formatted.split(' ');
                const dayStr = `${dateParts[0]} ${dateParts[1]} ${dateParts[2]}`.toLowerCase();
                
                // Check if note matches
                const noteText = log.note ? log.note.toLowerCase() : '';
                
                // Check if all search terms match
                return searchTerms.every(term => {
                    return dayStr.includes(term) || noteText.includes(term);
                });
            });
            
            // Add search parameter to URL to indicate we're in search mode
            const url = new URL(window.location.href);
            url.searchParams.set('search', query);
            url.searchParams.delete('page');
            url.searchParams.set('show_all', 1);
            window.history.pushState({}, '', url);
            
            // Render results without pagination
            renderTimerLogs(filteredLogs);
        }

        // Function to load timer logs
        function loadTimerLogs() {
            // Hide no logs message initially
            document.getElementById('no-logs-message').style.display = 'none';
            
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const showAll = urlParams.has('show_all');
            const isSearchActive = urlParams.has('search');
            
            // Build API URL
            let apiUrl = `api/get_timer_logs.php?id=${timerId}`;
            if (showAll) {
                apiUrl += '&show_all=1';
            }
            
            fetch(apiUrl)
                .then(response => {
                    // First check if the response is ok (status in 200-299 range)
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    // Try to parse the response as JSON
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (error) {
                            // Silently handle parsing errors
                            return { success: false };
                        }
                    });
                })
                .then(data => {
                    if (data.success && data.logs && data.logs.length > 0) {
                        // Store logs in global variable for search functionality
                        allTimerLogs = data.logs;
                        // Show logs table and render logs
                        renderTimerLogs(data.logs);
                    } else {
                        // Clear any existing logs and show no logs message
                        allTimerLogs = [];
                        document.getElementById('days-grid-container').innerHTML = '';
                        document.getElementById('no-logs-message').style.display = 'block';
                    }
                })
                .catch(error => {
                    // Clear any existing logs without showing error message
                    allTimerLogs = [];
                    document.getElementById('days-grid-container').innerHTML = '';
                    document.getElementById('no-logs-message').style.display = 'none';
                });
        }
        
        // Function to render timer logs
        function renderTimerLogs(logs) {
            // Get the container for the days grid
            const daysGridContainer = document.getElementById('days-grid-container');
            const paginationContainer = document.getElementById('pagination-container');
            
            // Clear the containers
            daysGridContainer.innerHTML = '';
            paginationContainer.innerHTML = '';
            
            // Group logs by day
            const logsByDay = {};
            let dayTotals = {};
            
            logs.forEach(log => {
                // Extract the date part from the start time
                // Format from the API is "Apr 17, 2025 11:33 PM"
                const dateParts = log.start_time_formatted.split(' ');
                const startDate = `${dateParts[0]} ${dateParts[1]} ${dateParts[2]}`;
                
                if (!logsByDay[startDate]) {
                    logsByDay[startDate] = [];
                    dayTotals[startDate] = 0;
                }
                
                logsByDay[startDate].push(log);
                dayTotals[startDate] += parseFloat(log.duration || 0);
            });
            
            // Convert dates to Date objects for proper sorting
            const sortedDays = Object.keys(logsByDay).sort((a, b) => {
                // Convert date strings like "Apr 17 2025" to Date objects
                const dateA = new Date(a);
                const dateB = new Date(b);
                // Sort in descending order (newest first)
                return dateB - dateA;
            });
            
            // Pagination setup
            const itemsPerPage = 5;
            const totalPages = Math.ceil(sortedDays.length / itemsPerPage);
            
            // Get current page from URL or default to 1
            const urlParams = new URLSearchParams(window.location.search);
            let currentPage = parseInt(urlParams.get('page')) || 1;
            const showAll = urlParams.has('show_all');
            const isSearchActive = urlParams.has('search');
            
            // Ensure current page is valid
            if (currentPage < 1) currentPage = 1;
            if (currentPage > totalPages) currentPage = totalPages;
            
            // Calculate start and end index for the current page
            let daysForCurrentPage;
            if (showAll || isSearchActive) {
                // Show all days if show_all is set or if search is active
                daysForCurrentPage = sortedDays;
            } else {
                // Get days for the current page with pagination
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = Math.min(startIndex + itemsPerPage, sortedDays.length);
                daysForCurrentPage = sortedDays.slice(startIndex, endIndex);
            }
            
            // Render logs by day in grid layout for the current page
            daysForCurrentPage.forEach(day => {
                // Create day card
                const dayCard = document.createElement('div');
                dayCard.className = 'day-card';
                
                // Format day total duration (HH:MM:SS)
                const totalSeconds = dayTotals[day];
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = Math.floor(totalSeconds % 60);
                const formattedTotal = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Create uppercase day display
                const dateParts = day.split(' ');
                const monthUpper = dateParts[0].toUpperCase();
                const dayNum = dateParts[1];
                const year = dateParts[2];
                
                // Create header for the day card
                const dayCardHeader = document.createElement('div');
                dayCardHeader.className = 'day-card-header';
                dayCardHeader.innerHTML = `
                    <div>${monthUpper} ${dayNum} ${year}</div>
                    <div class="day-total">TOTAL: ${formattedTotal}</div>
                `;
                dayCard.appendChild(dayCardHeader);
                
                // Create table for the logs
                const tableContainer = document.createElement('div');
                tableContainer.className = 'table-responsive';
                
                const table = document.createElement('table');
                table.className = 'table timer-log-table';
                
                const tbody = document.createElement('tbody');
                
                // Add logs for this day in reverse order (latest first)
                logsByDay[day].sort((a, b) => {
                    // Sort by start time in descending order (newest first)
                    return new Date(b.start_time) - new Date(a.start_time);
                }).forEach(log => {
                    const row = document.createElement('tr');
                    
                    // Start time column - show only time part, not date
                    const startCell = document.createElement('td');
                    const startTimeParts = log.start_time_formatted.split(' ');
                    const startTimeOnly = `${startTimeParts[3]} ${startTimeParts[4]}`; // Format: "11:33 PM"
                    startCell.textContent = startTimeOnly;
                    row.appendChild(startCell);
                    
                    // End time column - show only time part, not date
                    const endCell = document.createElement('td');
                    if (log.stop_time_formatted) {
                        const endDateParts = log.stop_time_formatted.split(' ');
                        const endTimeOnly = `${endDateParts[3]} ${endDateParts[4]}`; // Format: "11:40 PM"
                        endCell.textContent = endTimeOnly;
                    } else {
                        endCell.innerHTML = '<span class="in-progress-text">In progress</span>';
                    }
                    row.appendChild(endCell);
                    
                    // Duration column
                    const durationCell = document.createElement('td');
                    durationCell.textContent = log.duration_formatted;
                    row.appendChild(durationCell);
                    
                    // Notes column with edit functionality
                    const notesCell = document.createElement('td');
                    
                    // Note display/edit container
                    const noteContainer = document.createElement('div');
                    noteContainer.className = 'd-flex align-items-center';
                    
                    if (log.note) {
                        // Display note with edit button - handle line breaks
                        const noteText = document.createElement('div');
                        noteText.className = 'note-text';
                        
                        // Create code block for note content
                        const codeBlock = document.createElement('pre');
                        codeBlock.className = 'code-note-block';
                        
                        // Replace literal "\n" strings with actual line breaks
                        // First handle escaped \n sequences that appear as \\n in the string
                        let formattedNote = log.note.replace(/\\n/g, '\n');
                        // Then handle regular \n characters
                        formattedNote = formattedNote.replace(/\n/g, '\n');
                        codeBlock.textContent = formattedNote;
                        
                        noteText.appendChild(codeBlock);
                        
                        const editBtn = document.createElement('button');
                        editBtn.className = 'btn btn-sm btn-link p-0 ms-1';
                        editBtn.innerHTML = '<i class="fas fa-edit" style="font-size: 0.75rem;"></i>';
                        editBtn.title = 'Edit note';
                        editBtn.style.color = 'rgba(255,255,255,0.5)';
                        editBtn.onclick = () => showNoteEditor(log.id, log.note);
                        
                        noteContainer.appendChild(noteText);
                        noteContainer.appendChild(editBtn);
                    } else {
                        // Add note button
                        const addNoteBtn = document.createElement('button');
                        addNoteBtn.className = 'add-note-btn';
                        addNoteBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Add Note';
                        addNoteBtn.onclick = () => showNoteEditor(log.id, '');
                        
                        noteContainer.appendChild(addNoteBtn);
                    }
                    
                    notesCell.appendChild(noteContainer);
                    row.appendChild(notesCell);
                    
                    tbody.appendChild(row);
                });
                
                table.appendChild(tbody);
                tableContainer.appendChild(table);
                dayCard.appendChild(tableContainer);
                
                // Add the day card to the grid
                daysGridContainer.appendChild(dayCard);
            });
            
            // Create pagination if more than one page
            if (totalPages > 1) {
                // Skip pagination if showing all logs or in search mode
                if (!showAll && !isSearchActive) {
                    const pagination = document.createElement('ul');
                    pagination.className = 'pagination';
                    
                    // Previous page button
                    const prevLi = document.createElement('li');
                    const prevLink = document.createElement('a');
                    prevLink.href = '#';
                    prevLink.innerHTML = '&laquo;';
                    prevLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (currentPage > 1) {
                            navigateToPage(currentPage - 1);
                        }
                    });
                    prevLi.appendChild(prevLink);
                    if (currentPage === 1) {
                        prevLi.style.opacity = '0.5';
                        prevLi.style.pointerEvents = 'none';
                    }
                    pagination.appendChild(prevLi);
                    
                    // Page numbers
                    for (let i = 1; i <= totalPages; i++) {
                        const pageLi = document.createElement('li');
                        if (i === currentPage) {
                            pageLi.className = 'active';
                        }
                        
                        const pageLink = document.createElement('a');
                        pageLink.href = '#';
                        pageLink.textContent = i;
                        pageLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            navigateToPage(i);
                        });
                        
                        pageLi.appendChild(pageLink);
                        pagination.appendChild(pageLi);
                    }
                    
                    // Next page button
                    const nextLi = document.createElement('li');
                    const nextLink = document.createElement('a');
                    nextLink.href = '#';
                    nextLink.innerHTML = '&raquo;';
                    nextLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (currentPage < totalPages) {
                            navigateToPage(currentPage + 1);
                        }
                    });
                    nextLi.appendChild(nextLink);
                    if (currentPage === totalPages) {
                        nextLi.style.opacity = '0.5';
                        nextLi.style.pointerEvents = 'none';
                    }
                    pagination.appendChild(nextLi);
                    
                    // Show All button
                    const showAllLi = document.createElement('li');
                    const showAllLink = document.createElement('a');
                    showAllLink.href = '#';
                    showAllLink.textContent = 'Show All';
                    showAllLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        showAllLogs();
                    });
                    showAllLi.appendChild(showAllLink);
                    pagination.appendChild(showAllLi);
                    
                    paginationContainer.appendChild(pagination);
                } else {
                    // Show "Show Less" button when all logs are displayed
                    const pagination = document.createElement('ul');
                    pagination.className = 'pagination';
                    
                    if (isSearchActive) {
                        // Show Clear Search button when in search mode
                        const clearSearchLi = document.createElement('li');
                        const clearSearchLink = document.createElement('a');
                        clearSearchLink.href = '#';
                        clearSearchLink.textContent = 'Clear Search';
                        clearSearchLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            clearSearch();
                        });
                        clearSearchLi.appendChild(clearSearchLink);
                        pagination.appendChild(clearSearchLi);
                    } else {
                        // Show Show Less button when in show all mode
                        const showLessLi = document.createElement('li');
                        const showLessLink = document.createElement('a');
                        showLessLink.href = '#';
                        showLessLink.textContent = 'Show Less';
                        showLessLink.addEventListener('click', (e) => {
                            e.preventDefault();
                            showLessLogs();
                        });
                        showLessLi.appendChild(showLessLink);
                        pagination.appendChild(showLessLi);
                    }
                    
                    paginationContainer.appendChild(pagination);
                }
            }
            
            // Function to navigate to a specific page
            function navigateToPage(page) {
                // Update URL with the new page parameter
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                window.history.pushState({}, '', url);
                
                // Reload timer logs with the new page
                loadTimerLogs();
            }
            
            // Function to show all logs without pagination
            function showAllLogs() {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                url.searchParams.set('show_all', 1);
                window.history.pushState({}, '', url);
                
                // Reload timer logs to show all
                loadTimerLogs();
            }
            
            // Function to return to paginated view
            function showLessLogs() {
                const url = new URL(window.location.href);
                url.searchParams.delete('show_all');
                url.searchParams.set('page', 1);
                window.history.pushState({}, '', url);
                
                // Reload timer logs with pagination
                loadTimerLogs();
            }
            
            // Function to clear search
            function clearSearch() {
                // Clear the search input
                const searchInput = document.getElementById('logs-search');
                if (searchInput) {
                    searchInput.value = '';
                }
                
                // Reset URL parameters
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                url.searchParams.delete('show_all');
                url.searchParams.set('page', 1);
                window.history.pushState({}, '', url);
                
                // Reload timer logs
                loadTimerLogs();
            }
        }
        
        // Function to show note editor
        function showNoteEditor(logId, currentNote) {
            Swal.fire({
                title: 'Session Note',
                input: 'textarea',
                inputValue: currentNote,
                inputPlaceholder: 'Enter a note for this session...',
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Cancel',
                background: getComputedStyle(document.documentElement).getPropertyValue('--card-bg'),
                color: getComputedStyle(document.documentElement).getPropertyValue('--text-color'),
                inputAttributes: {
                    rows: 5,
                    style: 'white-space: pre-wrap;'
                },
                inputValidator: (value) => {
                    if (!value && !currentNote) {
                        return 'You need to enter a note';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    saveLogNote(logId, result.value);
                }
            });
        }
        
        // Function to save log note
        function saveLogNote(logId, note) {
            // Preserve line breaks
            const formData = new FormData();
            formData.append('log_id', logId);
            formData.append('note', note); // Do not modify the note - send it as is with line breaks
            
            fetch('api/save_log_note.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload logs to show the updated note
                    loadTimerLogs();
                } else {
                    showError(data.message || 'Failed to save note');
                }
            })
            .catch(error => {
                // console.error('Error saving note:', error);
                showError('Failed to save note');
            });
        }
    </script>
</body>
</html> 