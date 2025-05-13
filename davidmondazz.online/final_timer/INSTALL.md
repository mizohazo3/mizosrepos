# Timer Tracking System - Complete Installation Guide

This guide covers the complete setup process for both the web and mobile applications.

## Web Application Setup

### Prerequisites

1. PHP 8.0 or higher
2. MySQL or MariaDB
3. Web server (Apache, Nginx, etc.)
4. Composer (PHP package manager)

### Installation Steps

1. **Clone or download the repository to your web server directory**

2. **Configure the database**
   - Edit `includes/config.php` with your database credentials:
     ```php
     define('DB_HOST', 'localhost'); // Your database host
     define('DB_USER', 'root');      // Your database username
     define('DB_PASS', '');          // Your database password
     define('DB_NAME', 'timer_tracker'); // Your database name
     ```

3. **Set up the database tables**
   - Visit `http://your-server/setup.php` in your browser
   - This will create the necessary database, tables, and add default categories

4. **Install Composer (if not already installed)**
   - Download Composer from https://getcomposer.org/download/
   - Follow the installation instructions for your operating system

5. **Install WebSocket dependencies**
   - Open a terminal/command prompt in your project directory
   - Run: `composer require cboden/ratchet`

6. **Start the WebSocket server**
   - Open a terminal/command prompt in your project directory
   - Run: `php ws_server.php`
   - Keep this terminal window open while using the application

7. **Access the web application**
   - Open your browser and navigate to `http://your-server/`

## Mobile Application Setup

### Prerequisites

1. Node.js 14+ and npm
2. Expo CLI: `npm install -g expo-cli`
3. Android Studio (for Android development and emulation)
4. Physical Android device or emulator

### Installation Steps

1. **Install Expo CLI**
   - Open a terminal/command prompt
   - Run: `npm install -g expo-cli`

2. **Install project dependencies**
   - Navigate to the android_app directory:
     ```bash
     cd android_app
     ```
   - Run: `npm install`

3. **Configure the WebSocket connection**
   - The most convenient way is to use the included setup script:
     ```bash
     # Windows
     build_app.bat setup YOUR_SERVER_IP 8080
     
     # Linux/Mac
     node app_builder.js setup YOUR_SERVER_IP 8080
     ```
   - Replace `YOUR_SERVER_IP` with your actual server IP address

4. **Start the Expo development server**
   - In the android_app directory, run:
     ```bash
     # Windows
     build_app.bat dev
     
     # Linux/Mac
     node app_builder.js dev
     ```
   - This will open a new browser window with a QR code

5. **Run on a device or emulator**
   - **Physical device**:
     - Install the Expo Go app on your Android device
     - Scan the QR code with the Expo Go app
   - **Emulator**:
     - Press 'a' in the terminal to open in an Android emulator
     - Make sure Android Studio and an emulator are already set up

## Building an APK for Distribution

1. **Create an Expo account**
   - Register at https://expo.dev/signup

2. **Log in to Expo from the CLI**
   - Run: `expo login` inside the android_app directory

3. **Build the APK**
   - Use the build script:
     ```bash
     # Windows
     build_app.bat build
     
     # Linux/Mac
     node app_builder.js build
     ```
   - Follow the prompts to complete the build process

4. **Download the APK**
   - Once the build is complete, Expo will provide a URL to download the APK
   - You can also access your builds from your Expo account dashboard

## Troubleshooting

### Web Application Issues

- **Database Connection Errors**: Verify your database credentials in `includes/config.php`
- **WebSocket Connection Failures**: Check that port 8080 is open and accessible
- **Permission Issues**: Ensure your web server has write permissions to the project directory

### Mobile Application Issues

- **Connection Errors**: Make sure the WebSocket server is running and accessible from your device's network
- **Build Failures**: Verify that all dependencies are installed correctly
- **Expo Errors**: Try clearing the cache with `expo r -c`

For more assistance, refer to `README_ANDROID.md` for the mobile app and `README.md` for the web application. 