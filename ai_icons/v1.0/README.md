# Free Icon Finder Application

A simple PHP application that allows you to search for free PNG icons on the web using Google Search.

## Features

- Search for free transparent PNG icons based on keywords
- Uses Google Image Search with specific filters for transparent PNG images
- Display search results in a responsive grid layout
- Error handling for broken images
- Download icons directly from the search results

## Requirements

- PHP 7.0 or higher
- cURL extension enabled
- Web server (Apache, Nginx, etc.)

## Installation

1. Place the files in your web server directory
2. Ensure PHP is properly configured with cURL support
3. Access the application through your web browser

## Usage

1. Open the application in your web browser
2. Enter a search term in the search box (e.g., "apple", "car", "user")
3. Click "Search" to find free PNG icons related to your search term
4. Click "Download" beneath any icon to save it to your device

## Files

- `index.php` - The main search page with a clean, user-friendly interface
- `search.php` - Handles the search functionality using Google and displays results

## Technical Details

The application works by:
1. Adding "icon png transparent free" to your search query
2. Searching Google Images with specific parameters for transparent PNG images
3. Parsing the results to extract image URLs
4. Displaying them in a responsive grid with download options

## Notes

This application uses web scraping for educational purposes. For production environments or commercial use, consider using official APIs with proper authentication and respect usage terms of both Google and the image sources.

## License

This project is open source and available under the [MIT License](https://opensource.org/licenses/MIT). 