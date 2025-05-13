# Timer Tracking System - Usage Guide

Welcome to the Timer Tracking System with React Native mobile app integration. This guide covers the complete setup and usage instructions for both the web application and mobile app.

## Web Application Installation

1. **Automatic Installation** (Recommended)

   Simply open your web browser and navigate to:
   ```
   http://your-server/install.php
   ```

   Follow the step-by-step instructions in the installation wizard. It will:
   - Configure your database
   - Set up tables and default data
   - Install required dependencies
   - Configure the WebSocket server

2. **Manual Installation** (If automatic installation fails)

   Follow the instructions in `INSTALL.md` for a manual step-by-step setup.

## Starting the WebSocket Server

After installation, you need to start the WebSocket server to enable real-time communication between web and mobile clients:

```bash
php ws_server.php
```

Keep this terminal window open while using the application. You may want to use a tool like [PM2](https://pm2.keymetrics.io/) or [Supervisor](http://supervisord.org/) to keep the WebSocket server running in production.

## Using the Web Application

1. Open your web browser and navigate to:
   ```
   http://your-server/
   ```

2. **Creating Timers**:
   - Click the "Add New Timer" button
   - Enter a name and select a category
   - Click "Save"

3. **Managing Timers**:
   - Start: Click the "Start" button on a timer to begin timing
   - Stop: Click the "Stop" button to end timing and add the elapsed time to the total
   - Delete: Click the "×" button to remove a timer

4. **Filtering Timers**:
   - Click on a category button at the top to filter timers by category
   - The "All" button shows all timers

## Mobile App Setup

### Automatic Setup (Windows)

1. Run the batch file with your server IP and WebSocket port:
   ```
   cd android_app
   build_app.bat setup 192.168.1.10 8080
   ```
   Replace `192.168.1.10` with your actual server IP address.

2. Start the development server:
   ```
   build_app.bat dev
   ```

3. Scan the QR code with the Expo Go app on your Android device

### Manual Setup

1. Navigate to the android_app directory:
   ```bash
   cd android_app
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Edit `App.js` and update the WebSocket URL:
   ```javascript
   const CONFIG = {
     wsUrl: 'ws://YOUR_SERVER_IP:8080',
   };
   ```

4. Start the development server:
   ```bash
   npm start
   ```

5. Scan the QR code with the Expo Go app

## Building the Android APK

1. Automatic build (Windows):
   ```
   cd android_app
   build_app.bat build
   ```

2. Manual build:
   ```bash
   cd android_app
   expo login
   expo build:android -t apk
   ```

3. Follow the Expo instructions to download your APK when the build is complete

## Troubleshooting

### Web Application Issues

1. **Database Connection Errors**:
   - Check your database credentials in `includes/config.php`
   - Ensure your MySQL server is running

2. **WebSocket Connection Issues**:
   - Verify that port 8080 is open and accessible
   - Check that the WebSocket server is running (`php ws_server.php`)
   - Check for any firewall blocking WebSocket connections

3. **Timer Synchronization Problems**:
   - The WebSocket server must be running for real-time updates
   - Check your network connection

### Mobile App Issues

1. **Connection Errors**:
   - Ensure the WebSocket server is running
   - Make sure your device is on the same network as the server
   - Verify the correct IP address in the app configuration

2. **Build Failures**:
   - Check Node.js and npm versions
   - Make sure Expo CLI is installed globally
   - Clear the Expo cache: `expo r -c`

3. **App Not Updating**:
   - The WebSocket server must be running for real-time updates
   - Check the connection status indicator in the app

## Need Help?

If you encounter any issues not covered in this guide, please check:
- The error logs in your web server
- The terminal output of the WebSocket server
- The Expo build logs

For more detailed technical information, refer to the comments in the source code files or read `README_ANDROID.md` for the mobile app. 