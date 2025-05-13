# Free Icon Finder Application

A simple PHP application that allows you to search for free PNG icons on the web using Google Search and save them to your server.

## Features

- Search for free transparent PNG icons based on keywords
- Uses Google Image Search with specific filters for transparent PNG images
- Display search results in a responsive grid layout
- Save icons to your server with search term-based filenames
- Resize icons to custom dimensions before saving
- Browse all downloaded icons in a dedicated gallery
- Error handling for broken images

## Requirements

- PHP 7.0 or higher
- cURL extension enabled
- GD extension enabled for image resizing
- Web server (Apache, Nginx, etc.)
- Write permissions for the uploads directory

## Installation

1. Place the files in your web server directory
2. Ensure PHP is properly configured with cURL and GD support
3. Make sure the application has permission to create and write to the 'uploads' directory
4. Access the application through your web browser

## Usage

1. Open the application in your web browser
2. Enter a search term in the search box (e.g., "apple", "car", "user")
3. Click "Search" to find free PNG icons related to your search term
4. Click "Download" to save the original icon to your server
5. Click "Resize" to specify custom dimensions before saving
6. Click "Browse Downloaded Icons" to view all saved icons

## Files

- `index.php` - The main search page with a clean, user-friendly interface
- `search.php` - Handles the search functionality using Google and displays results
- `uploads/` - Directory where downloaded icons are saved
- `uploads/index.php` - Interface for browsing saved icons

## Technical Details

The application works by:
1. Adding "icon png transparent free" to your search query
2. Searching Google Images with specific parameters for transparent PNG images
3. Parsing the results to extract image URLs
4. Displaying them in a responsive grid with download and resize options
5. Saving selected icons to the server with unique filenames based on search terms

All downloaded icons are stored in the 'uploads' directory with filenames that include:
- The search term (sanitized for use in filenames)
- Timestamp
- Random number to ensure uniqueness

## Notes

This application uses web scraping for educational purposes. For production environments or commercial use, consider using official APIs with proper authentication and respect usage terms of both Google and the image sources.

## License

This project is open source and available under the [MIT License](https://opensource.org/licenses/MIT). 