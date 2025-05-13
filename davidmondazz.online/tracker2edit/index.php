<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Timer Tracking System</title>
    
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
    
    <!-- Custom CSS -->
    <style>
        :root {
            /* Theme Colors - Light Mode (Default) */
            --bg-color: #f5f5f5;
            --text-color: #212529;
            --card-bg: #f8f5ff;
            --card-border: #dee2e6;
            --timer-display-bg: #000;
            --timer-display-text: #0ff;  /* Changed from #ff8800 to cyan */
            --navbar-bg: #212529;
            --navbar-text: #ffffff;
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --accent-color: #6f42c1;
            --boxshadow-color: rgba(0, 0, 0, 0.1);
        }
        
        /* Dark Mode Theme */
        [data-theme="dark"] {
            --bg-color: #121212;
            --text-color: #e9ecef;
            --card-bg: #1e1e2d;
            --card-border: #2c2c3a;
            --timer-display-bg: #000;
            --timer-display-text: #0ff;  /* Changed from #ff8800 to cyan */
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
            padding-bottom: 20px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Theme Toggle Button */
        .theme-toggle {
            background: none;
            border: none;
            color: var(--navbar-text);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .theme-toggle:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes timerGlow {
            0% { box-shadow: 0 0 5px rgba(0, 255, 255, 0.3); }
            50% { box-shadow: 0 0 20px rgba(0, 255, 255, 0.5); }
            100% { box-shadow: 0 0 5px rgba(0, 255, 255, 0.3); }
        }
        
        /* Running Timers Section Styles */
        .running-timers-section {
            display: none; /* Hidden by default */
            background: var(--card-bg); 
            padding: 10px 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px var(--boxshadow-color);
            margin-bottom: 20px; /* Increased space below */
            max-width: 100%;
            border: 1px solid var(--card-border);
        }

        .running-timers-container {
            display: flex;
            align-items: center;
            gap: 15px; /* Increased gap */
            flex-wrap: nowrap; /* Keep items in one line */
        }

        .running-timer-item {
            display: flex;
            align-items: center;
            gap: 8px; /* Gap between button and text */
            white-space: nowrap; /* Prevent wrapping within an item */
            border-right: 2px solid var(--card-border); /* Separator line */
            padding-right: 15px; /* Space before separator */
        }

        .running-timer-item:last-child {
            border-right: none; /* No separator for the last item */
            padding-right: 0;
        }

        .running-timer-text {
            font-size: 0.9rem; /* Slightly smaller */
            font-weight: 500; /* Medium weight */
            color: var(--text-color);
        }

        .running-timer-duration {
            font-size: 0.85rem; /* Smaller font size for duration */
            font-weight: 600; /* Bold duration */
            font-style: italic;
            color: var(--primary-color); /* Bootstrap primary blue */
            margin-left: 4px; /* Space between name and duration */
        }

        .running-stop-button {
            background: linear-gradient(to bottom, var(--secondary-color), rgba(var(--secondary-color), 0.8)); 
            color: white;
            padding: 5px 12px; /* Smaller button */
            border: none;
            border-radius: 6px;
            box-shadow: 0 1px 3px var(--boxshadow-color);
            font-size: 0.85rem; /* Smaller font */
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0; /* Prevent button from shrinking */
        }

        .running-stop-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--boxshadow-color);
        }
        
        .running-stop-button:active {
            transform: translateY(1px);
        }
        /* End Running Timers Section Styles */
        
        /* Footer styles */
        .fixed-bottom {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            background-color: #212529 !important;
            color: #fff !important;
            padding: 10px 0 !important; /* Reduced back to 10px */
            font-size: 14px !important;
            z-index: 9999 !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        .fixed-bottom a {
            color: #17a2b8 !important; /* text-info color */
            text-decoration: none !important;
        }
        
        .fixed-bottom a:hover {
            color: #13969d !important;
            text-decoration: none !important;
        }
        
        /* Footer icons - match spacing in screenshot */
        .fixed-bottom a i {
            margin-right: 4px !important;
        }
        
        /* Total hours display styling */
        #total-hours {
            font-size: 14px !important;
            opacity: 0.9 !important;
        }
        
        #total-hours i {
            margin-right: 4px !important;
            font-size: 12px !important;
        }
        
        #total-hours-value {
            font-weight: 500 !important;
        }
        
        /* Footer spacing adjustment */
        /* This adds to the existing body styles without replacing them */
        body {
            padding-bottom: 70px !important; /* Increased from 60px to 70px for more space */
        }
        
        /* Footer responsive adjustments */
        @media (max-width: 768px) {
            .fixed-bottom {
                padding: 12px 0 !important; /* Slightly smaller on mobile */
            }
            
            body {
                padding-bottom: 80px !important; /* Extra space on mobile */
            }
            
            /* Remove the top margin on mobile */
            .fixed-bottom a {
                margin-top: 0 !important;
                margin-bottom: 0 !important;
                display: inline-block !important;
            }
            
            /* Fix alignment on mobile to match screenshot */
            .fixed-bottom .mt-2 {
                margin-top: 0 !important;
            }
            
            /* Center the footer content on very small screens */
            @media (max-width: 576px) {
                .fixed-bottom .col-md-6 {
                    text-align: center !important;
                }
                
                .fixed-bottom p {
                    margin-bottom: 8px !important;
                }
            }
        }
        
        /* Make footer text inline on mobile devices */
        @media (max-width: 425px) {
            .fixed-bottom .row {
                flex-direction: row !important;
            }
            .fixed-bottom .col-6 {
                width: auto !important;
                flex: 0 0 auto !important;
            }
            .fixed-bottom .text-end {
                text-align: right !important;
            }
            .fixed-bottom .container {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .fixed-bottom small,
            .fixed-bottom a,
            .fixed-bottom span {
                font-size: 12px !important;
                margin-right: 5px !important;
            }
        }
        
        /* New Navbar Styles */
        .navbar {
            background-color: var(--navbar-bg) !important;
            box-shadow: 0 4px 10px var(--boxshadow-color);
            padding: 15px 0;
            transition: background-color 0.3s ease;
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
            transition: opacity 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            opacity: 0.8;
        }
        
        .navbar-nav .nav-item:last-child .nav-link {
            background-color: var(--info-color);
            border-radius: 50px;
            padding: 8px 20px;
            color: white !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .navbar-nav .nav-item:last-child .nav-link:hover {
            background-color: var(--info-color) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--boxshadow-color);
        }
        
        .navbar-nav .nav-item:last-child .nav-link:active {
            transform: translateY(1px);
        }
        
        /* Mobile Font Size Adjustment */
        @media (max-width: 768px) {
            .timer-current {
                font-size: 1.5rem !important;
            }
            
            .timer-current-placeholder {
                font-size: 1.5rem !important;
            }
            
            /* Smaller timer name on mobile */
            .timer-name {
                font-size: 1rem !important;
            }
            
            /* Smaller start and stop buttons on mobile */
            .btn-start, .btn-start:hover, .btn-start:active, .btn-start:focus,
            .btn-stop, .btn-stop:hover, .btn-stop:active, .btn-stop:focus {
                padding: 8px 12px !important;
                font-size: 0.9rem !important;
                min-width: 100px !important;
                height: 40px !important;
            }
        }

        /* Vertical Category Filter Layout */
        .main-container {
            display: flex;
            flex-direction: column; /* Make main container stack vertically */
            margin-top: 30px; /* Increased space below navbar (was 15px) */
        }
        
        /* New wrapper for sidebar and content */
        .sidebar-content-wrapper {
            display: flex;
            flex-direction: row;
            width: 100%;
        }
        
        .filter-sidebar {
            width: 250px;
            padding-right: 20px;
            flex-shrink: 0;
        }
        
        .content-area {
            flex-grow: 1;
        }
        
        .category-filters {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .category-filters h5 {
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .category-filters .category-btn-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .category-btn {
            margin-bottom: 8px;
            text-align: left;
            padding: 10px 15px;
            border-radius: 6px;
            transition: none !important;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            height: auto;
            min-height: 46px;
        }
        
        .category-btn.active {
            background-color: #0d6efd;
            color: white;
        }
        
        .timer-count {
            display: inline-flex;
            align-items: center;
            font-size: 0.75rem;
            margin-left: 3px;
        }
        
        .timer-count i {
            font-size: 8px;
            margin-right: 3px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            font-size: 1rem;
            height: 46px;
        }
        
        .search-box h5 {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        @media (max-width: 992px) {
            .main-container {
                flex-direction: column;
            }
            
            /* Add rule to stack sidebar and content on mobile */
            .sidebar-content-wrapper {
                flex-direction: column;
            }
            
            /* Mobile specific styles for running timers */
            .running-timers-section {
                overflow-x: hidden; /* Prevent accidental scrollbars */
            }
            .running-timers-container {
                flex-wrap: wrap; /* Allow items to wrap */
                justify-content: flex-start; /* Align items to the start */
                gap: 10px; /* Adjust gap for wrapped items */
            }
            .running-timer-item {
                flex-basis: calc(50% - 10px); /* Approx 2 per line, accounting for gap */
                border-right: none; /* Remove border for wrapped items */
                padding-right: 0;
                justify-content: center; /* Center content within the item */
                border: 1px solid #ddd; /* Optional: add border for clarity */
                padding: 5px;
                border-radius: 5px;
            }
            /* End mobile specific styles */
            
            .filter-sidebar {
                width: 100%;
                margin-bottom: 20px;
                padding-right: 0;
            }
            
            .category-filters {
                margin-bottom: 15px;
            }
            
            /* Reduce space below search box on mobile */
            .search-box {
                margin-bottom: 10px;
            }
        }
        
        /* Category Section Styles */
        .category-section {
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 15px;
            background-color: var(--bg-color);
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
            margin-bottom: 20px;
        }
        
        .category-title {
            background-color: var(--accent-color);
            color: white;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 500;
            margin-bottom: 15px;
            display: inline-block;
            border-left: 5px solid rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px var(--boxshadow-color);
        }
        
        .category-title:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px var(--boxshadow-color);
        }
        
        /* Timer Card Styles */
        .timer-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--boxshadow-color);
            padding: 15px 15px 3px 15px;
            margin-bottom: 15px;
            height: auto;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            overflow: visible;
            justify-content: space-between;
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
            position: relative;
        }
        
        .timer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px var(--boxshadow-color);
        }
        
        .timer-card.running {
            border-color: var(--success-color);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }
        
        .timer-card.paused {
            border-color: var(--warning-color);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
        }

        /* Delete Icon: Default state and transition */
        .delete-icon {
            cursor: pointer;
            color: var(--danger-color);
            font-size: 1rem;
            padding: 2px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 5;
            opacity: 0.2;
        }

        /* Hover States: Make icon visible ONLY on direct icon hover */
        .delete-icon:hover {
            color: var(--danger-color);
            transform: scale(1.1);
            opacity: 1;
        }

        /* Timer Header Styles */
        .timer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1px;
            margin-top: 0;
            width: 100%;
            background-color: rgba(240, 245, 255, 0.1);
            padding: 8px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        /* Timer Name Container Styles */
        .timer-name-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            flex-grow: 1;
            padding-right: 10px;
            min-width: 0;
        }
        
        .timer-name-row {
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .timer-category-badge {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-right: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-shrink: 0;
            box-shadow: 0 2px 4px var(--boxshadow-color);
            transition: all 0.3s ease;
        }
        
        .cat-filter-link {
            color: inherit;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .cat-filter-link:hover {
            color: var(--primary-color);
            transform: scale(1.05);
        }
        
        .timer-name {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 100%;
            min-width: 0;
            flex: 1;
            color: var(--text-color);
            transition: color 0.3s ease;
        }
        
        .timer-details-link {
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .timer-details-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .timer-info {
            margin-top: 2px;
            margin-bottom: 8px;
            color: var(--secondary-color);
        }
        
        /* Timer Display Styles - Adjusted for left alignment */
        .timer-display {
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Changed from center to flex-start for left alignment */
            justify-content: center;
            margin: 3px 0;
            margin-top: 5px;
            flex-grow: 1;
            width: 100%;
            min-height: 110px;
            position: relative;
        }
        
        .timer-display:empty::before {
            content: "";
            display: block;
            min-height: 59px;
            margin-bottom: 15px;
        }
        
        .timer-current {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 3px;
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            background-color: var(--timer-display-bg);
            color: var(--timer-display-text);
            padding: 15px;
            border-radius: 5px;
            letter-spacing: 3px;
            text-align: center;
            min-width: 220px;
            width: 100%;
            max-width: 280px;
            height: 70px;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
            word-break: keep-all;
            white-space: nowrap;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            text-shadow: 0 0 10px var(--timer-display-text);
            margin-left: 0; /* Added to ensure left alignment */
        }
        
        .timer-card.running .timer-current {
            animation: timerGlow 2s infinite;
        }
        
        .timer-current-placeholder {
            margin-bottom: 3px;
            background-color: var(--timer-display-bg);
            border-radius: 5px;
            min-width: 220px;
            width: 100%;
            max-width: 280px;
            height: 70px;
            padding: 15px;
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.1);
            font-size: 2.2rem;
            font-weight: 700;
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            color: #333;
            letter-spacing: 3px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0; /* Added to ensure left alignment */
        }
        
        /* Timer Controls - Adjust to match left alignment */
        .timer-controls {
            display: flex;
            justify-content: flex-start; /* Changed from center to flex-start */
            margin-top: 10px;
            margin-bottom: 10px;
            width: 100%;
        }
        
        .timer-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            width: 60px;
            text-align: center;
            margin-bottom: 0;
            transition: all 0.3s ease;
        }
        
        .status-idle {
            background-color: var(--secondary-color);
            color: white;
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
        
        .timer-total {
            color: #333;
            display: flex;
            align-items: center;
            background-color: #f0f0f0;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        [data-theme="dark"] .timer-total {
            background-color: #2a2a2a;
            color: #e0e0e0;
        }
        
        .total-time-value {
            color: var(--timer-display-text);
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            letter-spacing: 1px;
            text-shadow: 0 0 5px var(--timer-display-text);
        }
        
        .btn-timer-control {
            padding: 12px 28px; /* Increased from 10px 22px */
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 5px;
            pointer-events: auto;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            font-size: 1.2rem; /* Increased from 1.1rem */
            min-width: 140px; /* Increased from 120px */
            height: 55px; /* Increased from 45px */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease !important;
            position: relative;
            overflow: hidden;
        }
        
        .btn-timer-control:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn-timer-control:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.3;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        .btn-start {
            background-color: var(--success-color) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }
        
        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(40, 167, 69, 0.3);
        }
        
        .btn-start:active {
            transform: translateY(1px);
        }
        
        .btn-pause {
            background-color: var(--warning-color) !important;
            color: #212529 !important;
            border: none !important;
            box-shadow: 0 4px 6px rgba(255, 193, 7, 0.2);
        }
        
        .btn-pause:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-pause:active {
            transform: translateY(1px);
        }
        
        .btn-resume {
            background-color: var(--info-color) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 6px rgba(23, 162, 184, 0.2);
        }
        
        .btn-resume:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(23, 162, 184, 0.3);
        }
        
        .btn-resume:active {
            transform: translateY(1px);
        }
        
        .btn-stop {
            background-color: var(--warning-color) !important;
            color: #212529 !important;
            border: none !important;
            box-shadow: 0 4px 6px rgba(255, 193, 7, 0.2);
        }
        
        .btn-stop:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(255, 193, 7, 0.3);
        }
        
        .btn-stop:active {
            transform: translateY(1px);
        }
        
        .btn-delete {
            background-color: transparent !important;
            color: var(--danger-color) !important;
            border: 1px solid var(--danger-color) !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-delete:hover {
            background-color: var(--danger-color) !important;
            color: white !important;
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .new-timer-title {
            font-weight: 700;
            color: #0d6efd;
        }
        
        .no-timers {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        
        .add-category-link {
            margin-left: 15px;
            font-size: 0.9rem;
            cursor: pointer;
            color: #0d6efd;
        }
        
        /* Loading indicator */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
        }
        
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Disable all button hover effects */
        .btn,
        .btn:hover,
        .btn:active,
        .btn:focus,
        .btn:focus-visible,
        .btn-timer-control,
        .btn-timer-control:hover,
        .btn-timer-control:active,
        .btn-timer-control:focus,
        .category-btn,
        .category-btn:hover,
        .category-btn:active,
        .category-btn:focus {
            transition: none !important;
            transform: none !important;
            filter: none !important;
            -webkit-filter: none !important;
            box-shadow: none !important;
            text-decoration: none !important;
            outline: none !important;
            opacity: 1 !important;
            -webkit-tap-highlight-color: transparent !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
            backface-visibility: hidden !important;
            -webkit-backface-visibility: hidden !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            will-change: auto !important;
            contain: content !important;
        }
        
        /* Override Bootstrap's button hover states */
        .btn.btn-primary,
        .btn.btn-primary:hover,
        .btn.btn-primary:active,
        .btn.btn-primary:focus {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: white !important;
        }
        
        .btn.btn-outline-secondary,
        .btn.btn-outline-secondary:hover,
        .btn.btn-outline-secondary:active,
        .btn.btn-outline-secondary:focus {
            background-color: transparent !important;
            border-color: #6c757d !important;
            color: #6c757d !important;
        }
        
        /* Preserve specific button colors without hover effects */
        .btn-start, .btn-start:hover, .btn-start:active, .btn-start:focus {
            background-color: #28a745 !important;
            color: white !important;
            border: none !important;
            width: 80% !important; /* Added to make width consistent */
            min-width: 140px !important; /* Ensure minimum width */
            height: 55px !important; /* Ensure consistent height */
            font-size: 1.2rem !important; /* Consistent font size */
        }
        
        .btn-pause, .btn-pause:hover, .btn-pause:active, .btn-pause:focus {
            background-color: #ffc107 !important;
            color: #212529 !important;
            border: none !important;
            width: 80% !important; /* Added to make width consistent */
            min-width: 140px !important; /* Ensure minimum width */
            height: 55px !important; /* Ensure consistent height */
            font-size: 1.2rem !important; /* Consistent font size */
        }
        
        .btn-resume, .btn-resume:hover, .btn-resume:active, .btn-resume:focus {
            background-color: #17a2b8 !important;
            color: white !important;
            border: none !important;
            width: 100% !important; /* Added to make width consistent */
            min-width: 140px !important; /* Ensure minimum width */
            height: 55px !important; /* Ensure consistent height */
            font-size: 1.2rem !important; /* Consistent font size */
        }
        
        .btn-stop, .btn-stop:hover, .btn-stop:active, .btn-stop:focus {
            background-color: #dc3545 !important; /* Changed to match danger red */
            color: white !important; /* White text for better contrast */
            border: none !important;
            width: 80% !important; /* Added to make width consistent */
            min-width: 140px !important; /* Ensure minimum width */
            height: 55px !important; /* Ensure consistent height */
            font-size: 1.2rem !important; /* Consistent font size */
        }
        
        .btn-delete, .btn-delete:hover, .btn-delete:active, .btn-delete:focus {
            background-color: transparent !important;
            color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
        }
        
        /* Remove all animations and transitions globally */
        * {
            transition: none !important;
            animation: none !important;
            -webkit-transition: none !important;
            -moz-transition: none !important;
            -o-transition: none !important;
            backface-visibility: hidden !important;
            -webkit-backface-visibility: hidden !important;
            transform-style: preserve-3d !important;
            -webkit-transform-style: preserve-3d !important;
            perspective: 1000px !important;
            -webkit-perspective: 1000px !important;
        }
        
        @media (max-width: 768px) {
            .btn-timer-control {
                padding: 8px 16px; /* Increased from 6px 12px */
                font-size: 1rem; /* Increased from 0.9rem */
                min-width: 120px; /* Added minimum width */
                height: 45px; /* Added explicit height */
            }
            
            .btn-start, .btn-start:hover, .btn-start:active, .btn-start:focus,
            .btn-stop, .btn-stop:hover, .btn-stop:active, .btn-stop:focus {
                min-width: 100px !important; /* Reduced from 120px */
                height: 38px !important; /* Reduced from 45px */
                font-size: 0.85rem !important; /* Reduced from 1rem */
                padding: 6px 10px !important; /* Reduced padding */
            }
            
            .timer-controls .btn-start,
            .timer-controls .btn-stop {
                min-width: 180px; /* Reduced from 200px */
                height: 45px; /* Reduced from 55px */
                font-size: 1rem; /* Reduced from 1.1rem */
                max-width: 90% !important; /* Ensure buttons don't exceed card width */
                width: 100% !important; /* Fill available space within the constraint */
                margin-left: auto !important;
                margin-right: auto !important;
            }
            
            .timer-current,
            .timer-current-placeholder {
                min-width: 126px !important; /* Reduced by 30% from 180px */
                max-width: 140px !important; /* Reduced by 30% from 200px */
                height: 50px !important;
                font-size: 1.4rem !important; /* Reduced from 1.8rem */
                padding: 10px !important;
            }
            
            .timer-display:not(:has(.timer-current))::before {
                max-width: 140px !important; /* Reduced by 30% from 200px */
                height: 50px !important;
            }
        }

        /* Footer styles */
        .fixed-footer {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            background-color: #212529 !important;
            color: #fff !important;
            padding: 10px 0 !important;
            font-size: 14px !important;
            z-index: 9999 !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        .fixed-footer a {
            color: #17a2b8 !important;
            text-decoration: none !important;
            transition: none !important;
        }
        
        .fixed-footer a:hover {
            color: #17a2b8 !important;
            text-decoration: none !important;
        }
        
        /* Ensure content doesn't get hidden behind footer */
        body {
            min-height: 100vh !important;
            padding-bottom: 60px !important;
            margin-bottom: 0 !important;
            position: relative !important;
        }

        /* Optimize rendering performance */
        .timer-card {
            transform: translateZ(0) !important;
            -webkit-transform: translateZ(0) !important;
            backface-visibility: hidden !important;
            -webkit-backface-visibility: hidden !important;
            will-change: transform !important;
            contain: layout style paint !important;
        }

        /* Prevent loading indicator flicker */
        .loading {
            opacity: 0 !important;
            visibility: hidden !important;
            transition: none !important;
            animation: none !important;
        }

        .loading.show {
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Prevent button state changes */
        .btn-start, .btn-start:hover, .btn-start:active, .btn-start:focus {
            background-color: #28a745 !important;
            color: white !important;
            border: none !important;
            pointer-events: auto !important;
            touch-action: manipulation !important;
        }
        
        .btn-pause, .btn-pause:hover, .btn-pause:active, .btn-pause:focus {
            background-color: #ffc107 !important;
            color: #212529 !important;
            border: none !important;
            pointer-events: auto !important;
            touch-action: manipulation !important;
        }
        
        .btn-resume, .btn-resume:hover, .btn-resume:active, .btn-resume:focus {
            background-color: #17a2b8 !important;
            color: white !important;
            border: none !important;
            pointer-events: auto !important;
            touch-action: manipulation !important;
        }
        
        .btn-stop, .btn-stop:hover, .btn-stop:active, .btn-stop:focus {
            background-color: #dc3545 !important;
            color: white !important;
            border: none !important;
            pointer-events: auto !important;
            touch-action: manipulation !important;
        }
        
        .btn-delete, .btn-delete:hover, .btn-delete:active, .btn-delete:focus {
            background-color: transparent !important;
            color: #dc3545 !important;
            border: 1px solid #dc3545 !important;
            pointer-events: auto !important;
            touch-action: manipulation !important;
        }

        .modal-body {
            padding: 20px;
        }
        
        /* Fix for category select dropdown */
        #timerCategory {
            width: 100% !important;
            height: auto !important;
            padding: 0.375rem 0.75rem !important;
            font-size: 1rem !important;
            font-weight: 400 !important;
            line-height: 1.5 !important;
            color: #212529 !important;
            background-color: #fff !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
            transition: none !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            cursor: pointer !important;
            z-index: 100 !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 16px 12px !important;
        }
        
        #timerCategory:focus {
            border-color: #86b7fe !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        #timerCategory option {
            padding: 8px !important;
            cursor: pointer !important;
            background-color: #fff !important;
            color: #212529 !important;
        }

        /* Style for start button and stop button on idle cards to match timer width */
        .timer-controls .btn-start,
        .timer-controls .btn-stop {
            min-width: 240px; /* Increased from 220px */
            width: 100%;
            max-width: 300px; /* Increased from 280px */
            padding: 14px 20px; /* Increased from 10px 15px */
            font-size: 1.3rem; /* Increased from 1.2rem */
            height: 65px; /* Increased from 59px */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Add empty placeholder for timer-current in idle state */
        /* Ensure all cards in the grid have the same width */
        .col-md-6.col-lg-4 {
            display: flex;
        }
        
        /* Center idle state start button */
        .timer-controls {
            display: flex;
            justify-content: center;
            margin-top: 8px;
            margin-bottom: 8px;
            width: 100%;
        }
        
        /* Add empty timer background for idle state - Adjusted for left alignment */
        .timer-display:not(:has(.timer-current)) {
            position: relative;
        }
        
        .timer-display:not(:has(.timer-current))::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0; /* Changed from left: 50%; transform: translateX(-50%); to left: 0 */
            width: 100%;
            max-width: 280px;
            height: 65px;
            background-color: #111;
            border-radius: 5px;
            margin-bottom: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            transform: none; /* Remove the transform */
        }

        /* Adjust timer-ms: Use relative positioning to shift down */
        .timer-ms {
            font-size: 1.2rem;
            opacity: 0.9;
            display: inline-block;
            min-width: 3ch;
            text-align: left;
            margin-left: 2px;
            font-family: 'Digital-7', 'DSEG7 Classic', monospace;
            line-height: normal;
            position: relative;
            top: 5px;
            color: var(--timer-display-text);
            text-shadow: 0 0 10px var(--timer-display-text);
        }

        /* Timer card footer styles */
        .timer-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
        }
        
        .timer-separator-top {
            width: 100%;
            height: 1px;
            background-color: #e0e0e0;
            margin: 1px 0;
        }
        
        .timer-separator-bottom {
            width: 100%;
            height: 1px;
            background-color: #e0e0e0;
            margin: 1px 0;
        }
        
        .timer-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 500;
            width: 60px;
            text-align: center;
            margin-bottom: 0;
        }

        .timer-current::after {
            content: "";
            display: none;
        }

        @media (max-width: 576px) {
            .timer-name {
                font-size: calc(1rem + (100vw - 320px) * 0.004);
            }
        }

        /* Explicit style for Stop All Button */
        #stop-all-btn,
        #stop-all-btn:hover,
        #stop-all-btn:active,
        #stop-all-btn:focus,
        #stop-all-btn-mobile,
        #stop-all-btn-mobile:hover,
        #stop-all-btn-mobile:active,
        #stop-all-btn-mobile:focus {
            color: #ffc107 !important; /* Warning yellow */
        }

        /* Notification Badge */
        .timer-count-badge {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background-color: #dc3545; /* Red background */
            color: white;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
            height: 20px;
            width: 20px;
            margin-left: 8px;
            position: relative;
            top: -1px;
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

        /* Pagination Styles */
        .category-pagination, .timer-pagination {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 8px;
            margin-top: 15px;
            margin-bottom: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .timer-pagination {
            background-color: #f0f5ff;
            margin-top: 10px;
            border: 1px solid #dee2e6;
        }
        
        .timer-pagination .btn,
        .category-pagination .btn {
            min-width: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
        }
        
        .timer-pagination span,
        .category-pagination span {
            font-size: 0.9rem;
            font-weight: 500;
            color: #495057;
            min-width: 60px;
            text-align: center;
        }
        
        /* For small screens, make pagination more compact */
        @media (max-width: 576px) {
            .timer-pagination .btn,
            .category-pagination .btn {
                min-width: 32px;
                padding: 4px;
            }
            
            .timer-pagination span,
            .category-pagination span {
                min-width: 50px;
                font-size: 0.8rem;
            }
        }

        /* Hub Title Styles */
        .hub-title {
            font-size: 3rem;
            font-weight: 700;
            text-align: center;
            margin: 2rem 0;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        
        /* Search Container Styles */
        .search-container {
            max-width: 600px;
            margin: 0 auto 2rem;
            padding: 0 1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            border: 2px solid var(--card-border);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-color), 0.2);
        }

        /* Horizontal Timer Card Styles - Adjusted layout */
        .horizontal-timer-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 60%;
            margin-left: auto;
            margin-right: auto;
            position: relative;
        }
        
        [data-theme="dark"] .horizontal-timer-card {
            background-color: var(--card-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        /* Reordered sections for horizontal card */
        .timer-center {
            width: 40%;
            display: flex;
            justify-content: flex-start; /* Changed from center to flex-start */
            order: 1; /* Added to place timer display first */
        }
        
        .timer-left {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 30%;
            order: 2; /* Added to place details after timer display */
        }
        
        .timer-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }
        
        .timer-hours {
            font-size: 0.9rem;
            color: var(--timer-display-text);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .timer-hours i {
            opacity: 0.8;
        }
        
        .timer-right {
            width: 30%;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            order: 3; /* Added to keep controls at the end */
        }
        
        .status-pill {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 5px 15px;
            border-radius: 20px;
            text-align: center;
        }
        
        .status-pill.status-running {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-pill.status-paused {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .status-pill.status-idle {
            background-color: var(--secondary-color);
            color: white;
        }
        
        /* Adjust timer current display for horizontal layout */
        .horizontal-timer-card .timer-current,
        .horizontal-timer-card .timer-current-placeholder {
            min-width: 220px;
            max-width: 260px;
        }
        
        /* Make stop button match the screenshot - Bigger buttons for horizontal cards */
        .horizontal-timer-card .btn-stop {
            background-color: #dd3545 !important; /* More reddish color */
            color: white !important;
            padding: 10px 25px; /* Increased from 8px 20px */
            border-radius: 8px;
            font-size: 1.1rem; /* Added font size increase */
            min-width: 110px; /* Added minimum width */
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .horizontal-timer-card {
                width: 80%; /* Slightly wider on medium screens */
            }
        }
        
        /* Additional media query adjustments for horizontal layout */
        @media (max-width: 768px) {
            .horizontal-timer-card {
                width: 100%;
                flex-direction: row;
                padding: 12px 15px;
                gap: 10px;
                align-items: center;
            }
            
            .timer-center {
                width: 40%;
                order: 1;
                justify-content: flex-start;
            }
            
            .timer-left {
                width: 30%;
                order: 2;
                align-items: flex-start;
                text-align: left;
            }
            
            .timer-right {
                width: 30%;
                order: 3;
                flex-direction: column;
                align-items: flex-end;
                justify-content: center;
                gap: 5px;
            }
            
            /* Make title and hours font smaller on mobile */
            .timer-title {
                font-size: 0.9rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 100%;
            }
            
            .timer-hours {
                font-size: 0.8rem;
            }
            
            /* Make timer display smaller */
            .horizontal-timer-card .timer-current,
            .horizontal-timer-card .timer-current-placeholder {
                min-width: 160px;
                max-width: 180px;
                padding: 8px;
                font-size: 1.3rem;
            }
            
            .timer-ms {
                font-size: 0.8rem;
            }
            
            /* Make stop button smaller */
            .horizontal-timer-card .btn-stop {
                padding: 8px 16px; /* Increased from 6px 12px */
                font-size: 1rem; /* Increased from 0.9rem */
                min-width: 120px; /* Match mobile start button */
            }
        }

        @media (max-width: 576px) {
            .horizontal-timer-card {
                width: 100%;
            }
        }

        /* Add Sticky Button Styles with !important for all properties to override any conflicting rules */
        .sticky-toggle {
            background: none !important;
            border: none !important;
            font-size: 0.7rem !important;
            padding: 2px !important;
            cursor: pointer !important;
            transition: transform 0.2s ease !important;
            color: #6c757d !important;
            position: absolute !important;
            top: 2px !important;
            right: 2px !important;
            z-index: 1 !important;
            opacity: 0.4 !important;
            /* Override global animation disabling */
            animation: none !important;
            -webkit-transition: transform 0.2s ease !important;
            -moz-transition: transform 0.2s ease !important;
            transform-style: preserve-3d !important;
            backface-visibility: visible !important;
            will-change: transform !important;
        }
        
        .sticky-toggle.sticky-on {
            color: #ffc107 !important;
            transform: rotate(45deg) !important;
            -webkit-transform: rotate(45deg) !important;
            -moz-transform: rotate(45deg) !important;
            opacity: 0.7 !important;
        }
        
        .sticky-toggle:hover {
            opacity: 0.9 !important;
            transform: scale(1.1) !important;
            -webkit-transform: scale(1.1) !important;
            -moz-transform: scale(1.1) !important;
        }
        
        .sticky-toggle.sticky-on:hover {
            transform: rotate(45deg) scale(1.1) !important;
            -webkit-transform: rotate(45deg) scale(1.1) !important;
            -moz-transform: rotate(45deg) scale(1.1) !important;
            opacity: 0.9 !important;
        }
        
        /* Fix for sticky toggle in running timers */
        .horizontal-timer-card.sticky-timer .sticky-toggle {
            transform: rotate(45deg) !important;
            -webkit-transform: rotate(45deg) !important;
            -moz-transform: rotate(45deg) !important;
        }
        
        .horizontal-timer-card.sticky-timer .sticky-toggle:hover {
            transform: rotate(45deg) scale(1.1) !important;
            -webkit-transform: rotate(45deg) scale(1.1) !important;
            -moz-transform: rotate(45deg) scale(1.1) !important;
        }
        
        .sticky-timer {
            border-left: 4px solid #ffc107 !important;
        }
        
        /* Position the sticky button */
        .timer-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        /* Make sure the card has a relative position for absolute positioning of sticky toggle */
        .horizontal-timer-card {
            position: relative;
        }
    </style>

    <!-- Add Modal Styles for Dark Mode -->
    <style>
        /* Dark Mode Modal Styles */
        .modal-content {
            background-color: #1e1e2d;
            color: #e9ecef;
            border: 1px solid #2c2c3a;
        }
        
        .modal-header {
            background-color: #0d0d17;
            border-bottom: 1px solid #2c2c3a;
        }
        
        .modal-title {
            color: #e9ecef;
        }
        
        .form-label {
            color: #e9ecef;
        }
        
        .form-control, .form-select {
            background-color: #2c2c3a;
            border: 1px solid #3c3c4a;
            color: #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: #2c2c3a;
            border-color: #4d7cfe;
            color: #e9ecef;
            box-shadow: 0 0 0 0.25rem rgba(77, 124, 254, 0.25);
        }
        
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>
<body>
    <!-- Loading Indicator -->
    <div id="loading" style="display: none;">
        <div class="spinner"></div>
    </div>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Timer System</a>
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
    
    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Hub Title -->
        <h1 class="hub-title"><a href="index.php" style="text-decoration: none; color: inherit;">THE HUB</a></h1>
        
        <!-- Search Container -->
        <div class="search-container">
            <input type="text" id="timer-search" class="search-input" placeholder="Search timers...">
        </div>
        
        <!-- Timers Container -->
        <div id="timers-container" class="row"></div>
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
    
    <!-- Modern Footer -->
    <footer class="bg-dark text-white py-2 fixed-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-6">
                    <small class="mb-0">&copy; 2025 Timer Tracker</small>
                </div>
                <div class="col-6 text-end">
                    <span class="text-info me-3" id="total-hours">
                        <i class="fas fa-clock"></i> <span id="total-hours-value">0h</span>
                    </span>
                    <a href="clear_cache.php" class="text-info text-decoration-none me-3">
                        <i class="fas fa-broom"></i> Clear Cache
                    </a>
                    <a href="admin_control.php" class="text-info text-decoration-none">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/app.js"></script>
<!-- <script src="js/icons.js"></script> -->
    
    <script>
        // Function to calculate and display total hours of all timers
        function updateTotalHours() {
            $.ajax({
                url: 'api/get_total_time.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Convert total seconds to a more readable format
                        const totalSeconds = response.total_seconds || 0;
                        const hours = Math.floor(totalSeconds / 3600);
                        
                        // Format hours with commas for thousands
                        const formattedHours = hours.toLocaleString();
                        
                        // For large numbers of hours, just show hours
                        if (hours >= 10) {
                            $('#total-hours-value').text(formattedHours + 'h');
                        } else {
                            // For smaller numbers, show hours and minutes
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            if (hours > 0) {
                                $('#total-hours-value').text(formattedHours + 'h ' + minutes + 'm');
                            } else {
                                $('#total-hours-value').text(minutes + 'm');
                            }
                        }
                    } else {
                        $('#total-hours-value').text('0h');
                    }
                },
                error: function() {
                    $('#total-hours-value').text('0h');
                }
            });
        }
        
        // Update total hours when page loads
        $(document).ready(function() {
            updateTotalHours();
            
            // Update total hours every 30 seconds
            setInterval(updateTotalHours, 30000);
        });

        // Function to format total time properly
        function formatTotalTime(totalSeconds) {
            if (typeof totalSeconds !== 'number') {
                totalSeconds = parseInt(totalSeconds) || 0;
            }
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            
            // For large hour numbers
            if (hours >= 100) {
                return `${hours.toLocaleString()}h`;
            } 
            // Show hours and minutes for more precision
            else if (hours > 0) {
                return `${hours}h ${minutes}m`;
            } 
            // Show only minutes for small durations
            else {
                return `${minutes}m`;
            }
        }

        // Create timer card template
        function createTimerCard(timer) {
            // Generate icon based on timer name
            const icon = getIconForTimerName(timer.name);
            
            let timerCardHtml = `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="timer-card" data-timer-id="${timer.id}" data-status="${timer.status}">
                        <div class="timer-header">
                            <div class="timer-name-container">
                                <div class="timer-name-row">
                                    <span class="timer-category-badge">${timer.category}</span>
                                    <h5 class="timer-name">
                                        <i class="${icon} me-1"></i>
                                        <a href="timer_details.php?id=${timer.id}" class="timer-details-link">${timer.name}</a>
                                    </h5>
                                </div>
                            </div>
                            <i class="fas fa-trash delete-icon" data-timer-id="${timer.id}"></i>
                        </div>
                        <div class="timer-info">
                            Last updated: ${timer.updated_at}
                        </div>
                        <div class="timer-display">
                            ${timer.status !== 'idle' ? 
                                `<div class="timer-current" data-timer-id="${timer.id}">${timer.elapsed_time}</div>` : 
                                `<div class="timer-current-placeholder">00:00:00.00</div>`
                            }
                        </div>
                        <div class="timer-controls">
                            ${getTimerControlButtons(timer)}
                        </div>
                        <div class="timer-separator-top"></div>
                        <div class="timer-card-footer">
                            <span class="timer-status status-${timer.status}">${timer.status}</span>
                            <span class="timer-total">Total: <span class="total-time-value">${timer.total_time}</span></span>
                        </div>
                    </div>
                </div>
            `;
            
            return timerCardHtml;
        }
        
        // Create horizontal timer card template
        function createHorizontalTimerCard(timer) {
            // Generate icon based on timer name
            const icon = getIconForTimerName(timer.name);
            
            // Determine which control buttons to show based on status
            let controlButtons = '';
            // Check for running or paused first
            if (timer.status === 'running' || timer.status === 'paused') {
                controlButtons = `
                    <button class="btn btn-sm btn-stop" data-action="stop" data-timer-id="${timer.id}">
                        <i class="fas fa-square me-1"></i> Stop
                    </button>
                `;
            } else { // Default to START for 'idle' or any other unexpected status
                controlButtons = `
                    <button class="btn btn-sm btn-start" data-action="start" data-timer-id="${timer.id}">
                        <i class="fas fa-play me-1"></i> Start
                    </button>
                `;
            }

            // Format timer display with robust handling
            let currentElapsedHtml = '';
            // Check specifically for running or paused
            if (timer.status === 'running' || timer.status === 'paused') {
                // Get timer value in seconds, default to 0 if undefined, null, or not a number
                const seconds = (typeof timer.current_elapsed === 'number' && !isNaN(timer.current_elapsed))
                                ? timer.current_elapsed
                                : 0;

                // Format it
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = Math.floor(seconds % 60);
                const ms = Math.floor((Number(seconds) - Math.floor(seconds)) * 100); // Ensure seconds is number for calculation

                const displayHours = hours >= 1000 ? hours.toLocaleString() : String(hours).padStart(2, '0');
                const mainTime = `${displayHours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                const msTime = String(ms).padStart(2, '0');
                const fullFormattedTime = mainTime + '.' + msTime;

                // Use .timer-current class ONLY for running/paused
                currentElapsedHtml = `<div class="timer-current" data-timer-id="${timer.id}">${fullFormattedTime}</div>`;
            } else { // Default to placeholder for 'idle' or any other status
                // Use .timer-current-placeholder class
                currentElapsedHtml = `<div class="timer-current-placeholder">00:00:00.00</div>`;
            }
            
            let horizontalCardHtml = `
                <div class="horizontal-timer-card ${timer.sticky ? 'sticky-timer' : ''}" data-timer-id="${timer.id}" data-status="${timer.status}">
                    <button class="sticky-toggle ${timer.sticky ? 'sticky-on' : ''}" data-action="toggle-sticky" data-timer-id="${timer.id}" data-sticky="${timer.sticky ? 0 : 1}" title="${timer.sticky ? 'Unstick timer' : 'Stick timer'}">
                        <i class="fas fa-thumbtack"></i>
                    </button>
                    <div class="timer-left">
                        <div class="timer-title"><i class="${icon} me-1"></i> ${timer.name}</div>
                        <div class="timer-hours"><i class="fas fa-clock"></i> <strong>${formatTotalTime(timer.total_time)}</strong></div>
                    </div>
                    <div class="timer-center">
                        ${currentElapsedHtml}
                    </div>
                    <div class="timer-right">
                        ${controlButtons}
                    </div>
                </div>
            `;
            
            return horizontalCardHtml;
        }
        
        // Function to determine icon based on timer name
        function getIconForTimerName(timerName) {
            const name = timerName.toLowerCase();
            
            // Define vibrant icon patterns with saturated colors
            const iconPatterns = [
                { icon: "fas fa-briefcase text-primary", keywords: ["work", "job", "career", "business", "office", "professional", "corporate", "employment", "company"] },
                { icon: "fas fa-tasks text-info", keywords: ["project", "task", "assignment", "todo", "checklist", "manage", "management"] },
                { icon: "fas fa-users text-success", keywords: ["meeting", "call", "conference", "team", "group", "collaborate", "collaboration", "people"] },
                { icon: "fas fa-code text-danger", keywords: ["coding", "programming", "dev", "developer", "software", "git", "tech", "computer"] },
                { icon: "fas fa-pencil-ruler text-warning", keywords: ["design", "ui", "ux", "interface", "creative", "layout", "wireframe", "prototype"] },
                { icon: "fas fa-search text-info", keywords: ["research", "find", "explore", "investigate", "analysis", "review", "examine"] },
                { icon: "fas fa-dumbbell text-danger", keywords: ["exercise", "workout", "gym", "fitness", "training", "sport", "athletics", "physical"] },
                { icon: "fas fa-spa text-success", keywords: ["meditate", "meditation", "mindfulness", "relax", "calm", "peace", "yoga", "zen"] },
                { icon: "fas fa-bed text-info", keywords: ["sleep", "rest", "nap", "break", "pause", "recover", "relax"] },
                { icon: "fas fa-heartbeat text-danger", keywords: ["health", "doctor", "medical", "wellness", "checkup", "hospital", "clinic"] },
                { icon: "fas fa-book text-primary", keywords: ["study", "learn", "course", "education", "class", "school", "knowledge", "read"] },
                { icon: "fas fa-book-open text-danger", keywords: ["homework", "school", "college", "university", "assign", "text", "paper"] },
                { icon: "fas fa-book-reader text-success", keywords: ["read", "reading", "literature", "novel", "book", "article", "story"] },
                { icon: "fas fa-gamepad text-warning", keywords: ["game", "play", "fun", "entertainment", "gaming", "video game", "console"] },
                { icon: "fas fa-film text-primary", keywords: ["movie", "watch", "tv", "show", "film", "video", "stream", "episode", "netflix"] },
                { icon: "fas fa-music text-success", keywords: ["music", "listen", "song", "audio", "playlist", "band", "concert", "spotify"] },
                { icon: "fas fa-utensils text-warning", keywords: ["cook", "cooking", "bake", "kitchen", "recipe", "food prep"] },
                { icon: "fas fa-hamburger text-danger", keywords: ["meal", "eat", "lunch", "dinner", "breakfast", "food", "snack", "restaurant"] },
                { icon: "fas fa-plane text-primary", keywords: ["travel", "trip", "vacation", "flight", "journey", "tourism", "visit"] },
                { icon: "fas fa-car text-danger", keywords: ["drive", "car", "vehicle", "auto", "commute", "travel", "transport"] },
                { icon: "fas fa-bus text-info", keywords: ["commute", "transit", "train", "bus", "subway", "metro", "transport"] },
                { icon: "fas fa-broom text-warning", keywords: ["clean", "housework", "chore", "tidy", "organize", "house", "home"] },
                { icon: "fas fa-home text-primary", keywords: ["family", "kids", "child", "home", "house", "parent", "domestic"] },
                { icon: "fas fa-coffee text-warning", keywords: ["break", "pause", "rest", "drink", "coffee", "tea", "beverage"] },
                { icon: "fas fa-phone text-success", keywords: ["phone", "call", "talk", "chat", "conversation", "contact"] },
                { icon: "fas fa-envelope text-info", keywords: ["email", "mail", "message", "letter", "communication", "inbox"] },
                { icon: "fas fa-money-bill-wave text-success", keywords: ["budget", "finance", "money", "payment", "bill", "cash", "bank", "financial"] },
                { icon: "fas fa-building text-danger", keywords: ["business", "company", "corporate", "office", "organization", "enterprise"] },
                { icon: "fas fa-bullhorn text-warning", keywords: ["marketing", "social media", "advertisement", "promote", "campaign"] },
                { icon: "fas fa-chart-line text-info", keywords: ["report", "analytics", "data", "metrics", "dashboard", "statistics"] },
                { icon: "fas fa-pen-fancy text-primary", keywords: ["write", "writing", "blog", "author", "content", "article", "copy"] },
                { icon: "fas fa-paint-brush text-danger", keywords: ["art", "drawing", "paint", "sketch", "creative", "artist", "illustrate"] },
                { icon: "fas fa-camera text-warning", keywords: ["photo", "picture", "image", "photography", "camera", "capture"] },
                { icon: "fas fa-calendar-alt text-primary", keywords: ["plan", "planning", "schedule", "appointment", "date", "event"] },
                { icon: "fas fa-folder text-warning", keywords: ["organize", "sort", "file", "document", "category", "archive"] },
                { icon: "fas fa-shopping-cart text-success", keywords: ["shop", "shopping", "buy", "purchase", "store", "ecommerce", "cart"] },
                { icon: "fas fa-hands-helping text-primary", keywords: ["help", "support", "service", "assist", "contribute", "volunteer"] },
                { icon: "fas fa-brain text-danger", keywords: ["think", "idea", "brainstorm", "mental", "concept", "creative"] },
                { icon: "fas fa-comments text-info", keywords: ["discuss", "chat", "talk", "conversation", "communication"] },
                // Additional colorful icons with vibrant colors
                { icon: "fas fa-running text-success", keywords: ["run", "jog", "sprint", "marathon", "race", "racing"] },
                { icon: "fas fa-biking text-danger", keywords: ["bike", "cycling", "bicycle", "ride", "riding"] },
                { icon: "fas fa-swimmer text-info", keywords: ["swim", "pool", "water", "swimming"] },
                { icon: "fas fa-basketball-ball text-warning", keywords: ["basketball", "sports", "ball", "nba"] },
                { icon: "fas fa-football-ball text-danger", keywords: ["football", "nfl", "sport"] },
                { icon: "fas fa-baseball-ball text-primary", keywords: ["baseball", "mlb", "bat", "softball"] },
                { icon: "fas fa-table-tennis text-success", keywords: ["ping pong", "table tennis", "paddle"] },
                { icon: "fas fa-chess text-warning", keywords: ["chess", "strategy", "board game", "game"] },
                { icon: "fas fa-pizza-slice text-danger", keywords: ["pizza", "food", "fast food", "italian"] },
                { icon: "fas fa-ice-cream text-info", keywords: ["ice cream", "dessert", "sweet", "cone"] },
                { icon: "fas fa-birthday-cake text-primary", keywords: ["cake", "birthday", "celebration", "party", "dessert"] },
                { icon: "fas fa-guitar text-warning", keywords: ["guitar", "music", "instrument", "play", "band"] },
                { icon: "fas fa-headphones text-danger", keywords: ["headphones", "audio", "sound", "listen", "podcast"] },
                { icon: "fas fa-microphone text-success", keywords: ["microphone", "recording", "podcast", "voice", "speech"] },
                { icon: "fas fa-palette text-primary", keywords: ["palette", "artist", "painting", "colors", "art"] },
                { icon: "fas fa-cut text-danger", keywords: ["cut", "scissor", "crafts", "diy", "sewing"] },
                { icon: "fas fa-tshirt text-info", keywords: ["clothes", "shirt", "fashion", "clothing", "wear"] },
                { icon: "fas fa-baby text-primary", keywords: ["baby", "infant", "child", "toddler"] },
                { icon: "fas fa-cat text-warning", keywords: ["cat", "pet", "kitten", "animal"] },
                { icon: "fas fa-dog text-success", keywords: ["dog", "pet", "puppy", "animal"] },
                { icon: "fas fa-fish text-info", keywords: ["fish", "pet", "aquarium", "animal"] },
                { icon: "fas fa-laptop-code text-primary", keywords: ["laptop", "code", "program", "develop", "engineer"] },
                { icon: "fas fa-server text-warning", keywords: ["server", "cloud", "data", "database", "network"] },
                { icon: "fas fa-mobile-alt text-info", keywords: ["mobile", "phone", "app", "application", "smartphone"] },
                { icon: "fas fa-keyboard text-primary", keywords: ["keyboard", "type", "typing", "input"] },
                { icon: "fas fa-glass-cheers text-danger", keywords: ["drink", "party", "celebration", "cheers", "alcohol"] },
                { icon: "fas fa-glasses text-warning", keywords: ["glasses", "reading", "eyewear", "vision"] },
                { icon: "fas fa-graduation-cap text-success", keywords: ["graduate", "graduation", "student", "degree"] },
                { icon: "fas fa-award text-warning", keywords: ["award", "achievement", "medal", "trophy", "win"] },
                { icon: "fas fa-leaf text-success", keywords: ["nature", "plant", "garden", "outdoor", "environment"] },
                { icon: "fas fa-tree text-success", keywords: ["tree", "forest", "nature", "plant", "hike"] },
                { icon: "fas fa-mountain text-danger", keywords: ["mountain", "hike", "climb", "outdoor", "adventure"] },
                { icon: "fas fa-umbrella-beach text-warning", keywords: ["beach", "vacation", "sun", "summer", "relax"] },
                { icon: "fas fa-snowflake text-info", keywords: ["winter", "snow", "cold", "ice", "season"] },
                { icon: "fas fa-cloud-sun text-primary", keywords: ["weather", "sun", "day", "sky", "cloud"] },
                { icon: "fas fa-moon text-warning", keywords: ["night", "moon", "sky", "dark", "evening"] },
                { icon: "fas fa-star text-warning", keywords: ["star", "favorite", "important", "highlight"] }
            ];
            
            // Try to find a matching icon based on keywords
            for (const pattern of iconPatterns) {
                if (pattern.keywords.some(keyword => name.includes(keyword))) {
                    return pattern.icon;
                }
            }
            
            // Always return a distinctive, colorful icon based on the first letter
            // No defaults - every possible input gets a specific, colorful icon
            // Fixed colorful mapping for each letter
            const letterIcons = {
                '0': 'fas fa-0 text-danger',
                '1': 'fas fa-1 text-success', 
                '2': 'fas fa-2 text-primary',
                '3': 'fas fa-3 text-warning',
                '4': 'fas fa-4 text-info',
                '5': 'fas fa-5 text-danger',
                '6': 'fas fa-6 text-success',
                '7': 'fas fa-7 text-primary',
                '8': 'fas fa-8 text-warning',
                '9': 'fas fa-9 text-info',
                'a': 'fas fa-anchor text-danger',
                'b': 'fas fa-bell text-success',
                'c': 'fas fa-crown text-primary',
                'd': 'fas fa-dragon text-warning',
                'e': 'fas fa-envelope text-info',
                'f': 'fas fa-fire text-danger',
                'g': 'fas fa-gem text-success',
                'h': 'fas fa-heart text-primary',
                'i': 'fas fa-image text-warning',
                'j': 'fas fa-journal-whills text-info',
                'k': 'fas fa-key text-danger',
                'l': 'fas fa-lightbulb text-success',
                'm': 'fas fa-map-marker-alt text-primary',
                'n': 'fas fa-newspaper text-warning',
                'o': 'fas fa-om text-info',
                'p': 'fas fa-paper-plane text-danger',
                'q': 'fas fa-question text-success',
                'r': 'fas fa-rocket text-primary',
                's': 'fas fa-shield-alt text-warning',
                't': 'fas fa-trophy text-info',
                'u': 'fas fa-umbrella text-danger',
                'v': 'fas fa-volleyball-ball text-success',
                'w': 'fas fa-wind text-primary',
                'x': 'fas fa-times-circle text-warning',
                'y': 'fas fa-yin-yang text-info',
                'z': 'fas fa-bolt text-danger'
            };

            // Get first character and ensure it's lowercase
            const firstChar = name.charAt(0).toLowerCase();
            
            // Return the specific icon for this letter, or a compass for any other character
            return letterIcons[firstChar] || 'fas fa-compass text-primary';
        }
    </script>
</body>
</html>