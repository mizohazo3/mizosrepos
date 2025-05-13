# Advanced Timer Tracking System

A comprehensive, real-time timer tracking web application built with PHP, JavaScript (ES6+), AJAX, and MySQL. The system allows users to manage multiple timers, track time accurately, persist data across browser sessions, and provide a dynamic, responsive user interface that updates live without page reloads.

## Features

- **Real-time Timer Tracking**: Track multiple timers simultaneously with accurate time calculations
- **Persistent Storage**: Timers continue running even when browser is closed
- **Multiple Timer States**: Start, pause, resume, and stop timers
- **Categories & Filtering**: Organize timers by categories and filter view
- **Responsive UI**: Dynamic interface that works across devices
- **No Page Reloads**: AJAX polling creates a seamless experience

## Requirements

- PHP 7.4+ or 8.x
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache, Nginx, etc.)
- Modern web browser with JavaScript enabled

## Installation

1. **Clone or download** this repository to your web server directory.

2. **Configure database settings** in `config.php`:
   ```php
   $db_host = "localhost"; // Your database host
   $db_user = "username";  // Your database username
   $db_pass = "password";  // Your database password
   $db_name = "timer_tracking"; // Your desired database name
   ```

3. **Run the setup script** by navigating to `http://your-server/timer-tracking/setup.php` in your browser.
   This will:
   - Create the database if it doesn't exist
   - Create the required tables
   - Insert default categories

4. **Access the application** by navigating to `http://your-server/timer-tracking/` in your browser.

## Project Structure

```
timer-tracking/
├── api/                    # API endpoints
│   ├── add_category.php    # Add a new category
│   ├── add_timer.php       # Add a new timer
│   ├── delete_timer.php    # Delete a timer
│   ├── get_timers.php      # Get timers (with filtering)
│   ├── pause_timer.php     # Pause a running timer
│   ├── resume_timer.php    # Resume a paused timer
│   ├── start_timer.php     # Start a new timer
│   └── stop_timer.php      # Stop a timer, update total time
├── includes/               # Shared PHP files
│   └── db_connect.php      # Database connection and helpers
├── js/                     # JavaScript files
│   └── app.js              # Main application JavaScript
├── android/                # Android integration docs
│   └── README.md           # Instructions for Android WebView wrapper
├── config.php              # Database configuration
├── index.php               # Main application page
├── setup.php               # Database setup script
└── README.md               # This file
```

## Timer States

Timers can have three states:

1. **idle**: The timer is not currently tracking time
   - Available actions: Start
   
2. **running**: The timer is actively tracking time
   - Available actions: Pause, Stop
   
3. **paused**: The timer has been paused but remembers its elapsed time
   - Available actions: Resume, Stop

## Database Schema

**timers table**
- `id`: Unique identifier (INT, auto increment)
- `name`: Timer name (VARCHAR)
- `category_id`: Foreign key to categories table (INT)
- `status`: Current status - 'idle', 'running', 'paused' (ENUM)
- `start_time`: When the timer was started/resumed (TIMESTAMP)
- `pause_time`: Accumulated pause time in seconds (BIGINT)
- `total_time`: Total accumulated time in seconds (BIGINT)
- `created_at`: When the timer was created (TIMESTAMP)
- `updated_at`: When the timer was last updated (TIMESTAMP)

**categories table**
- `id`: Unique identifier (INT, auto increment)
- `name`: Category name (VARCHAR)
- `created_at`: When the category was created (TIMESTAMP)

## Android Integration

See the `android/README.md` file for instructions on how to wrap this web application in a native Android app using WebView.

## Usage

1. **Add a Timer**: Click "Add Timer" in the navigation bar, enter a name and select a category.

2. **Start a Timer**: Click the "Start" button on an idle timer to begin tracking time.

3. **Pause/Resume a Timer**: Click "Pause" to temporarily stop a timer, and "Resume" to continue.

4. **Stop a Timer**: Click "Stop" to permanently stop a timer for the current session. This adds the elapsed time to the timer's total accumulated time.

5. **Delete a Timer**: Click the "Delete" button to remove a timer (will ask for confirmation).

6. **Filter by Category**: Use the category filter buttons to view only timers for specific categories.

7. **Add a Category**: Click "Add Category" to create a new category for organizing timers.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Credits

- Bootstrap 5 for UI components
- SweetAlert2 for attractive dialogs
- Font Awesome for icons
- Google Fonts for typography 