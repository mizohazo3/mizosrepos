# Timer Tracking System

A real-time timer tracking system built with PHP, MySQL, and vanilla JavaScript. This application allows users to create, manage, and track time spent on various activities categorized by user-defined categories. Also includes a React Native mobile app for Android.

## Features

- Create and manage timers categorized by different activities
- Start, pause, resume, and stop timers with accurate time tracking
- Filter timers by category
- Real-time synchronization across multiple tabs/browsers and mobile devices
- Persistent storage of timer states in the database
- Professional and intuitive user interface
- Android mobile app with the same functionality

## Technology Stack

- **Backend**: PHP 8.x
- **Frontend**: Vanilla JavaScript (ES6+), HTML5, CSS3
- **Mobile App**: React Native with Expo
- **Communication**: AJAX, WebSockets for real-time updates
- **Database**: MySQL/MariaDB

## Project Structure

- **Web Application**: Main timer tracking web interface
  - `index.php`: Main entry point
  - `js/`: JavaScript files
  - `css/`: Stylesheet files
  - `api/`: API endpoints for timer operations
  - `includes/`: Database and configuration files

- **WebSocket Server**: Real-time communication server
  - `ws_server.php`: WebSocket server implementation
  - `composer.json`: PHP dependencies

- **Android App**: Mobile application
  - `android_app/`: Contains all mobile app files
  - `android_app/App.js`: Main React Native component
  - `android_app/components/`: UI components
  - `android_app/build_app.bat`: Windows build script
  - `android_app/app_builder.js`: Node.js build utilities

## Setup Instructions

For full installation instructions, see:
- `install.php`: Web-based installation wizard
- `INSTALL.md`: Detailed manual installation guide
- `USAGE.md`: Usage instructions for both web and mobile apps
- `README_ANDROID.md`: Mobile app specific instructions

### Quick Start

1. Configure the database settings in `includes/config.php`
2. Run the setup script by visiting `http://your-server/setup.php`
3. Start the WebSocket server: `php ws_server.php`
4. For the mobile app:
   ```bash
   cd android_app
   build_app.bat setup YOUR_SERVER_IP 8080
   build_app.bat dev
   ```

## Usage

### Adding a Timer

1. Click the "Add New Timer" button in the navigation bar
2. Enter a name for your timer
3. Select a category from the dropdown
4. Click "Save" to create the timer

### Managing Timers

Each timer has the following controls:

- **Start**: Begins tracking time for the timer
- **Stop**: Ends the current timing session and adds the elapsed time to the total

### Filtering Timers

Use the category filter buttons to show only timers belonging to a specific category.

## Technical Implementation Details

- Timer states (running, paused, stopped) are stored in the database for persistence
- WebSockets provide real-time synchronization across devices
- Time is tracked accurately even when the browser is closed and reopened
- Total elapsed time is maintained separately from current session time

## License

This project is open-source and available under the MIT License. 