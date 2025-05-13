# Timer Tracking System - Project Documentation

## Overview
The Timer Tracking System is a web-based application designed for tracking and managing time across multiple activities or tasks. It allows users to create, start, pause, resume, and stop timers, organized by categories. The application features a modern, responsive interface with real-time updates.

## Project Structure

```
timer-tracking/
├── api/                        # Server API endpoints
│   ├── add_category.php        # Add a new category
│   ├── add_timer.php           # Add a new timer
│   ├── delete_timer.php        # Delete a timer
│   ├── get_level_ranks.php     # Retrieve level ranks data
│   ├── get_timer_xp.php        # Get timer experience points
│   ├── get_timers.php          # Retrieve timers with filtering
│   ├── get_total_time.php      # Get total accumulated time
│   ├── pause_timer.php         # Pause a running timer
│   ├── resume_timer.php        # Resume a paused timer
│   ├── save_log_note.php       # Save notes for timer logs
│   ├── search_timers.php       # Search for timers
│   ├── start_timer.php         # Start a timer
│   ├── sticky_timer.php        # Mark/unmark timer as sticky
│   ├── stop_timer.php          # Stop a timer
│   ├── unlock_timer.php        # Unlock a timer
│   ├── update_experience.php   # Update XP for timer
│   └── update_timer_xp.php     # Update timer XP
├── css/                        # Stylesheets
│   ├── style.css               # Main application styles
│   └── xp-animation.css        # Experience points animation styles
├── includes/                   # Shared PHP components
│   └── db_connect.php          # Database connection and helper functions
├── js/                         # JavaScript files
│   ├── app.js                  # Main application logic
│   ├── icons.js                # Icon management
│   └── xp-animation.js         # Experience animation logic
├── schema/                     # Database schema directory
├── admin_control.php           # Admin control panel
├── admin_leveling.php          # Admin leveling system management
├── check_leveling_system.php   # Check leveling system status
├── check_tables.php            # Database table verification
├── check_timer_logs.php        # Timer logs verification
├── check_xp_log.php            # XP logs verification
├── clear_cache.php             # Cache clearing utility
├── config.php                  # Database and app configuration
├── get_timer_xp.php            # Timer XP retrieval
├── import_activity_cats.php    # Import activity categories
├── import_activity_details.php # Import activity details
├── import_data_activities.php  # Import activity data
├── index.php                   # Main application page
├── README.md                   # Project documentation
├── reset_tables.php            # Reset database tables
├── reset_timers.php            # Reset all timers
├── schema.sql                  # SQL schema definition
├── setup.php                   # Initial setup script
├── setup_cron.php              # Setup cron jobs
├── setup_leveling.php          # Setup leveling system
├── timer_details.php           # Timer details page
├── update_leveling_system.php  # Update leveling system
├── update_sticky_timers.php    # Update sticky timers
├── update_timer_columns.php    # Update timer table columns
├── update_timer_logs_table.php # Update timer logs table
├── update_timer_xp.php         # Update timer XP
└── verify_leveling.php         # Verify leveling system
```

## Core Features

1. **Timer Management**
   - Create, start, pause, resume, and stop timers
   - Track time in real-time with millisecond precision
   - View total accumulated time for each timer
   - Organize timers by categories

2. **Category System**
   - Create and manage categories for timers
   - Filter timers by category
   - UI indicators for category association

3. **Persistence**
   - Timers continue running even when browser is closed
   - Data stored in MySQL database
   - State management for timers (idle, running, paused)

4. **Experience and Leveling System**
   - Gain XP based on timer activity
   - Level up system with ranks
   - XP animations and notifications

5. **Admin Features**
   - Admin control panel
   - Leveling system management
   - Database management utilities

6. **UI Features**
   - Modern, responsive design using Bootstrap
   - Theme switching (light/dark mode)
   - Real-time updates without page reloads
   - Search functionality
   - Sticky timers for prioritization

## Database Schema

**1. Timers Table**
- `id`: Primary key
- `name`: Timer name
- `category_id`: Foreign key to categories
- `status`: Current state (idle, running, paused)
- `manage_status`: Additional status information
- `total_time`: Total accumulated time in seconds
- `pause_time`: Accumulated time during pauses
- `start_time`: When the timer was started
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

**2. Categories Table**
- `id`: Primary key
- `name`: Category name
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## Technologies Used

1. **Backend**
   - PHP (7.4+ or 8.x)
   - MySQL/MariaDB
   - AJAX for asynchronous requests

2. **Frontend**
   - HTML5
   - CSS3
   - JavaScript (ES6+)
   - Bootstrap 5 for responsive design
   - SweetAlert2 for notifications
   - Font Awesome for icons
   - Google Fonts for typography

3. **Additional Libraries**
   - Chart.js for data visualization
   - jQuery for DOM manipulation

## Installation and Configuration

1. **Requirements**
   - PHP 7.4+ or 8.x
   - MySQL 5.7+ or MariaDB 10.3+
   - Web server (Apache, Nginx)
   - Modern web browser with JavaScript enabled

2. **Setup**
   - Configure database settings in `config.php`
   - Run `setup.php` to initialize database
   - Access the application through the web server

3. **Maintenance**
   - Use `clear_cache.php` to clear application cache
   - Use `reset_timers.php` to reset timer states if needed
   - Use admin tools for system management

## Integration Points

The application provides integration capabilities through:
- RESTful API endpoints in the `/api` directory
- Data import/export functionality
- Experience point system for gamification

## Leveling System

The application includes a gamification layer through:
- Experience points earned by timer usage
- Level progression system
- Ranks and rewards
- Management tools for system configuration

## User Interface

The main interface, "THE HUB", provides:
- Search functionality for timers
- Horizontal timer cards for active timers
- Timer controls (start, stop, pause, resume)
- Status indicators (idle, running, paused)
- Timer duration display with millisecond precision
- Total time accumulated display
- Sticky timer functionality for prioritization

## Future Development

Potential areas for enhancement:
- Mobile app integration
- Reporting and analytics features
- Team collaboration features
- Task management integration
- API expansion for third-party integrations 