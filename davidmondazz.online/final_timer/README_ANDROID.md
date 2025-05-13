# Timer Tracker Android App

This folder contains the React Native mobile app for the Timer Tracker system with real-time WebSocket synchronization.

## Folder Structure

- `android_app/` - Contains all files related to the Android mobile app:
  - `App.js` - Main React Native application component
  - `app.json` - Expo configuration file
  - `package.json` - Node.js dependencies
  - `app_builder.js` - Node.js script for building and configuring the app
  - `build_app.bat` - Windows batch file for easy building
  - `components/` - React Native UI components
  - `assets/` - App icons and images

## Installation and Setup

1. Make sure you have Node.js and npm installed
2. Navigate to the android_app directory:
   ```
   cd android_app
   ```

3. Run the setup script with your server's IP address:
   ```
   build_app.bat setup YOUR_SERVER_IP 8080
   ```
   Replace `YOUR_SERVER_IP` with the IP address of your Timer Tracker server.

4. Start the development server:
   ```
   build_app.bat dev
   ```

5. Scan the QR code with the Expo Go app on your Android device

## Building the APK

To build a standalone APK file:

```
build_app.bat build
```

This will:
1. Log you into your Expo account (you'll need to create one if you don't have one)
2. Build the Android APK
3. Provide a download link for the APK

## WebSocket Configuration

The app connects to your Timer Tracker server via WebSockets to provide real-time synchronization. 
Make sure your WebSocket server is running:

```
php ws_server.php
```

The app will automatically connect to the WebSocket server at the IP address you specified during setup.

## Features

- Real-time synchronization with the web app
- Start and stop timers
- Filter timers by category
- View timer statistics
- Clean, intuitive UI

## Troubleshooting

If you encounter connection issues:
1. Make sure the WebSocket server is running
2. Verify you're using the correct IP address
3. Check that port 8080 is open and accessible
4. Confirm your device is on the same network as the server

For build issues:
1. Make sure you have the latest version of Node.js and npm
2. Install Expo CLI globally: `npm install -g expo-cli`
3. Clear Expo cache: `expo r -c` 