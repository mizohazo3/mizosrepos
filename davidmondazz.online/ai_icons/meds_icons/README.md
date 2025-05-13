# Medication Image Search Tool

A PHP script that searches Google Images for medication images, with options to download and resize them.

## Features

- Search Google Images for medication-related images
- Download images to a local folder
- Resize downloaded images to custom dimensions
- Simple and user-friendly interface

## Setup

1. Ensure PHP is installed on your server with GD library support
2. Place all files in your web server directory
3. Create the placeholder image by running `create_placeholder.php` once:
   ```
   php create_placeholder.php
   ```
4. Access the search tool by navigating to `search_medications.php` in your web browser

## Usage

1. Enter the name of a medication in the search box
2. Click "Search" to find related images
3. Click "Download" on any image to save it to the "downloads" folder
4. After downloading, you can resize the image by specifying width and height

## Important Notes

- Web scraping Google Images directly may violate Google's Terms of Service
- This script is for educational purposes only
- Google may change their HTML structure which could break the search functionality
- Consider using the official Google Custom Search API for production use

## Requirements

- PHP 7.0 or higher
- GD Library extension enabled
- Write permissions for creating "downloads" and "resized" directories 