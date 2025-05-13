<?php

include_once '../timezone_config.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Set CORS headers to allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Add debugging function
function debug_log($message, $data = null) {
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log_message .= " - Data: " . json_encode($data);
    }
    error_log($log_message);
}

// Function to search for icon images using curl
function searchImages($query, $count = 20) {
    // Search specifically for icons, logos and clipart - using google image search parameters
    $searchUrl = "https://www.google.com/search?q=" . urlencode($query) . 
                "&tbm=isch&tbs=ic:trans,isz:m,itp:clipart,ift:png";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Increased timeout
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return false;
    }
    
    curl_close($ch);
    
    // Extract image URLs from the response
    $imageUrls = array();
    
    // Pattern to match Google image data - improved pattern
    preg_match_all('/"(https?:\/\/[^"]+\.(png|jpg|jpeg|svg|webp|gif))"/', $response, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $i => $url) {
            // Filter out Google's own images
            if (strpos($url, 'gstatic.com') === false && 
                strpos($url, 'google.com') === false) {
                $imageUrls[] = $url;
                if (count($imageUrls) >= $count) {
                    break;
                }
            }
        }
    }
    
    return $imageUrls;
}

// Alternative method to search for images using Bing
function searchImagesBing($query, $count = 10) {
    $searchUrl = "https://www.bing.com/images/search?q=" . urlencode($query) . "&qft=+filterui:photo-transparent&FORM=IRFLTR";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $imageUrls = array();
    preg_match_all('/"murl":"(https?:\/\/[^"]+\.(png|jpg|jpeg|svg|webp|gif))"/', $response, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $i => $url) {
            $imageUrls[] = $url;
            if (count($imageUrls) >= $count) {
                break;
            }
        }
    }
    
    return $imageUrls;
}

// Function to download an image from URL
function downloadImage($imageUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout to prevent hanging
    curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
    $imageData = curl_exec($ch);
    
    // Check for curl errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return false;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Only return data for successful responses
    if ($httpCode >= 200 && $httpCode < 300) {
        return $imageData;
    }
    
    return false;
}

// Function to create a fallback image with the item name
function createFallbackImage($text) {
    // Sanitize text for display
    $text = trim($text);
    if (strlen($text) > 30) {
        $text = substr($text, 0, 27) . '...';
    }
    
    // Set image dimensions
    $width = 500;
    $height = 500;
    
    // Create a new image
    $image = imagecreatetruecolor($width, $height);
    if (!$image) {
        return false;
    }
    
    // Define colors - use random accent color each time
    $bgColor = imagecolorallocate($image, 60, 70, 90); // Dark blue-gray background
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    
    // Generate random accent color
    $r = rand(100, 255);
    $g = rand(100, 255);
    $b = rand(100, 255);
    $accentColor = imagecolorallocate($image, $r, $g, $b);
    
    // Fill the background
    imagefill($image, 0, 0, $bgColor);
    
    // Draw a decorative rectangle
    $borderSize = 20;
    imagerectangle($image, $borderSize, $borderSize, $width - $borderSize, $height - $borderSize, $accentColor);
    
    // Draw a circle in the center
    $centerX = $width / 2;
    $centerY = $height / 2;
    $radius = min($width, $height) / 4;
    imagefilledellipse($image, $centerX, $centerY, $radius, $radius, $accentColor);
    
    // Add the text
    $font = 5; // Built-in font size (1-5)
    $lines = explode(" ", $text);
    $lineHeight = imagefontheight($font) + 5;
    $y = $centerY + $radius + 20; // Position below the circle
    
    // Center and render each word on a new line
    foreach ($lines as $i => $line) {
        $lineWidth = imagefontwidth($font) * strlen($line);
        $x = ($width - $lineWidth) / 2;
        imagestring($image, $font, $x, $y + ($i * $lineHeight), $line, $textColor);
    }
    
    // Add a note about fallback
    $note = "(No icon found)";
    $noteWidth = imagefontwidth(2) * strlen($note);
    imagestring($image, 2, ($width - $noteWidth) / 2, $height - 40, $note, $textColor);
    
    // Add a generated timestamp to make it unique
    $timestamp = date('Y-m-d H:i:s');
    $timestampWidth = imagefontwidth(2) * strlen($timestamp);
    imagestring($image, 2, ($width - $timestampWidth) / 2, $height - 20, $timestamp, $textColor);
    
    // Capture the image data
    ob_start();
    imagepng($image);
    $imageData = ob_get_clean();
    
    // Free memory
    imagedestroy($image);
    
    if (empty($imageData)) {
        return false;
    }
    
    return $imageData;
}

// Function to crop and resize image to square
function processImage($imageData, $size = 500) {
    error_log("processImage: Starting processing."); // Log start

    if (empty($imageData)) {
        error_log("processImage Error: Input image data is empty.");
        return false;
    }

    try {
        // Create image from string (removed @ suppression)
        error_log("processImage: Attempting imagecreatefromstring.");
        $image = imagecreatefromstring($imageData);

        if ($image === false) {
            error_log("processImage Error: imagecreatefromstring failed. Possibly invalid image data or unsupported format.");
            return false;
        }
        error_log("processImage: imagecreatefromstring successful.");

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        error_log("processImage: Original dimensions: {$width}x{$height}");

        // Calculate crop dimensions for square
        $squareSize = min($width, $height);
        $x = ($width - $squareSize) / 2;
        $y = ($height - $squareSize) / 2;
        error_log("processImage: Cropping to {$squareSize}x{$squareSize} at ({$x}, {$y}). Resizing to {$size}x{$size}.");

        // Create a new square image with alpha channel
        error_log("processImage: Creating true color image {$size}x{$size}.");
        $squareImage = imagecreatetruecolor($size, $size);
        if (!$squareImage) {
             error_log("processImage Error: imagecreatetruecolor failed.");
             imagedestroy($image); // Clean up original image
             return false;
        }

        // Preserve transparency
        error_log("processImage: Setting transparency.");
        imagealphablending($squareImage, false);
        imagesavealpha($squareImage, true);
        $transparent = imagecolorallocatealpha($squareImage, 0, 0, 0, 127);
        imagefilledrectangle($squareImage, 0, 0, $size, $size, $transparent);

        // Copy and resize the cropped portion
        error_log("processImage: Attempting imagecopyresampled.");
        $resampleResult = imagecopyresampled($squareImage, $image, 0, 0, $x, $y, $size, $size, $squareSize, $squareSize);
        if (!$resampleResult) {
            error_log("processImage Error: imagecopyresampled failed.");
            imagedestroy($image);
            imagedestroy($squareImage);
            return false;
        }
        error_log("processImage: imagecopyresampled successful.");

        // Free original image memory
        imagedestroy($image);

        // Capture output
        error_log("processImage: Capturing PNG output with ob_start.");
        ob_start();
        $pngResult = imagepng($squareImage);
        $imageData = ob_get_clean();

        if (!$pngResult || empty($imageData)) {
             error_log("processImage Error: imagepng failed or produced empty output.");
             imagedestroy($squareImage);
             return false;
        }
        error_log("processImage: imagepng successful. Output length: " . strlen($imageData));

        // Free square image memory
        imagedestroy($squareImage);

        $base64Image = 'data:image/png;base64,' . base64_encode($imageData);
        error_log("processImage: Processing successful. Base64 length: " . strlen($base64Image));
        return $base64Image;
    }
    catch (Exception $e) {
        // Catch potential GD errors if they throw exceptions (less common)
        error_log("processImage Exception: " . $e->getMessage());
        // Ensure resources are freed if an exception occurs mid-process
        if (isset($image) && is_resource($image)) imagedestroy($image);
        if (isset($squareImage) && is_resource($squareImage)) imagedestroy($squareImage);
        return false;
    }
}

// Try to use some popular free icon search API sources
function searchIconsFromAPI($query) {
    $apis = [
        // Trying with some free icon APIs - you may replace these with actual API keys
        "https://api.iconfinder.com/v4/icons/search?query=" . urlencode($query) . "&count=10",
        "https://api.flaticon.com/v3/search/icons/free?q=" . urlencode($query) . "&limit=10",
    ];
    
    foreach ($apis as $apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            // Each API has different response format, so handle accordingly
            // This is a simplified example
            if (isset($data['icons']) && count($data['icons']) > 0) {
                foreach ($data['icons'] as $icon) {
                    if (isset($icon['url']) || isset($icon['image_url']) || isset($icon['png_url'])) {
                        $iconUrl = isset($icon['url']) ? $icon['url'] : 
                                  (isset($icon['image_url']) ? $icon['image_url'] : $icon['png_url']);
                        $imageData = downloadImage($iconUrl);
                        if ($imageData) {
                            return $imageData;
                        }
                    }
                }
            }
        }
    }
    
    return false;
}

// Main execution
try {
    debug_log("Starting image fetch process");
    
    // Check if query parameter is provided
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        debug_log("No query parameter provided");
        throw new Exception('Query parameter is required');
    }
    
    $query = $_GET['query'];
    debug_log("Processing query", $query);
    
    // Add random seed to query to help get different results each time
    $rand = isset($_GET['rand']) ? intval($_GET['rand']) : rand(1, 1000);
    
    // Craft specific search queries for better icon results
    $cleanQuery = preg_replace('/[^\w\s]/', '', $query); // Remove special characters
    
    // Create an array of search queries for icon search
    $searchQueries = [
        $cleanQuery . " icon transparent", // Best for finding transparent icons
        $cleanQuery . " logo transparent", // Try logo search
        $cleanQuery . " symbol", // Try symbol search
        $cleanQuery . " icon " . $rand, // With random number for variety
        $cleanQuery . " clip art transparent", // Try clip art search
        $cleanQuery // Plain search as last resort
    ];
    
    // Try each search query until we find results
    $imageUrls = [];
    $usedQuery = "";
    
    // First try the Google image search method
    foreach ($searchQueries as $searchQuery) {
        $imageUrls = searchImages($searchQuery);
        if (!empty($imageUrls)) {
            $usedQuery = $searchQuery;
            break; // Found images, stop trying more queries
        }
    }
    
    // If Google search failed, try Bing
    if (empty($imageUrls)) {
        foreach ($searchQueries as $searchQuery) {
            $imageUrls = searchImagesBing($searchQuery);
            if (!empty($imageUrls)) {
                $usedQuery = $searchQuery;
                break;
            }
        }
    }
    
    // If still no images, try API sources
    if (empty($imageUrls)) {
        $apiImageData = searchIconsFromAPI($query);
        if ($apiImageData) {
            $processedImage = processImage($apiImageData);
            if ($processedImage) {
                echo json_encode([
                    'status' => 'success',
                    'image_data' => $processedImage,
                    'source' => 'API'
                ]);
                exit;
            }
        }
    }
    
    // Initialize a variable to store the image data
    $imageData = null;
    
    // If we have images, select a random one
    if (!empty($imageUrls)) {
        // Shuffle the array to randomize
        shuffle($imageUrls);
        
        // Try up to 5 random images (increased from 3)
        $maxTries = min(5, count($imageUrls));
        for ($i = 0; $i < $maxTries; $i++) {
            $imageData = downloadImage($imageUrls[$i]);
            if (!empty($imageData)) {
                break; // Found a valid image
            }
        }
    }
    
    // If we still don't have an image, create a fallback
    if (empty($imageData)) {
        $imageData = createFallbackImage($query);
        
        if ($imageData) {
            // We already have the processed image data, just encode it
            echo json_encode([
                'status' => 'success',
                'image_data' => 'data:image/png;base64,' . base64_encode($imageData),
                'source' => 'fallback'
            ]);
            exit;
        } else {
            // If even the fallback failed, return an error
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to generate fallback image.'
            ]);
            exit;
        }
    }
    
    // Process the image
    $processedImage = processImage($imageData);
    
    if ($processedImage) {
        echo json_encode([
            'status' => 'success',
            'image_data' => $processedImage,
            'source' => 'web',
            'query_used' => $usedQuery
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to process image.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 