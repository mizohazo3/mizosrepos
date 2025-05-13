<?php
/**
 * Medication Image Search Script
 * 
 * This script performs a direct search on Google Images to find medication images.
 * DISCLAIMER: Web scraping may violate Google's Terms of Service.
 * This is for educational purposes only.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to search Google Images
function searchGoogleImages($query) {
    $query = urlencode($query . " medication");
    $url = "https://www.google.com/search?q={$query}&tbm=isch&hl=en";
    
    // Set up a user agent to mimic a browser
    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n" .
                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                        "Accept-Language: en-US,en;q=0.5\r\n" .
                        "Connection: keep-alive\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) {
        return ["error" => "Failed to fetch search results. Google may be blocking the request."];
    }
    
    // Save the HTML for debugging if needed
    // file_put_contents('debug_google_response.html', $html);
    
    // Try multiple regex patterns to extract image URLs
    $patterns = [
        '/"ou":"(https?:\/\/[^"]+)"/', // Original pattern
        '/\["(https?:\/\/[^"]+)",\d+,\d+\]/', // Another common pattern
        '/\["(https?:\/\/[^"]+)",\d+,\d+,[^,]+,[^,]+,"[^"]+"\]/', // Expanded pattern
        '/src="(https?:\/\/[^"]+)"/', // Simple src pattern
        '/data-src="(https?:\/\/[^"]+)"/', // Data-src pattern
        '/url=(https?:\/\/[^&"]+)/' // URL parameter pattern
    ];
    
    $imageUrls = [];
    
    // Try each pattern until we find matches
    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $html, $matches);
        if (!empty($matches[1])) {
            $imageUrls = array_merge($imageUrls, $matches[1]);
        }
    }
    
    // Filter out non-image URLs
    $imageUrls = array_filter($imageUrls, function($url) {
        return preg_match('/(\.jpg|\.jpeg|\.png|\.gif|\.webp|\.bmp)/i', $url);
    });
    
    if (empty($imageUrls)) {
        return ["error" => "No images found. Google may have changed their HTML structure."];
    }
    
    // Get unique image URLs
    $imageUrls = array_unique($imageUrls);
    $results = [];
    
    // Limit to first 20 results
    $count = 0;
    foreach ($imageUrls as $imageUrl) {
        if ($count >= 20) break;
        // Decode entities in URLs
        $imageUrl = html_entity_decode($imageUrl);
        $results[] = $imageUrl;
        $count++;
    }
    
    return $results;
}

// Function to download an image
function downloadImage($url, $filename = null) {
    if (empty($filename)) {
        $filename = basename($url);
    }
    
    // Clean the filename more thoroughly to avoid problematic characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    // Ensure filename isn't too long
    if (strlen($filename) > 100) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 90) . '.' . $extension;
    }
    
    // Create downloads directory if it doesn't exist
    if (!file_exists('downloads')) {
        if (!mkdir('downloads', 0777, true)) {
            error_log("Failed to create 'downloads' directory");
            return false;
        }
    }
    
    $path = 'downloads/' . $filename;
    
    // Fetch and save the image
    $options = [
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
            'timeout' => 30
        ]
    ];
    
    $context = stream_context_create($options);
    $imageData = @file_get_contents($url, false, $context);
    
    if ($imageData) {
        if (file_put_contents($path, $imageData) !== false) {
            return $path;
        } else {
            error_log("Failed to save image to: $path");
        }
    }
    
    return false;
}

// Function to resize an image
function resizeImage($sourcePath, $width, $height) {
    // Get image info
    $info = getimagesize($sourcePath);
    
    if (!$info) {
        return false;
    }
    
    // Create source image based on file type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create blank image with new dimensions
    $destination = imagecreatetruecolor($width, $height);
    
    // Handle transparency for PNG
    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $width, $height, $transparent);
    }
    
    // Resize image
    imagecopyresampled(
        $destination, $source,
        0, 0, 0, 0,
        $width, $height, $info[0], $info[1]
    );
    
    // Create resized directory if it doesn't exist
    if (!file_exists('resized')) {
        mkdir('resized', 0777, true);
    }
    
    // Generate resized filename
    $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
    $resizedPath = "resized/{$filename}_{$width}x{$height}.{$extension}";
    
    // Save resized image
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $resizedPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $resizedPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $resizedPath);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $resizedPath;
}

// Function to crop an image
function cropImage($sourcePath, $x, $y, $width, $height) {
    // Get image info
    $info = getimagesize($sourcePath);
    
    if (!$info) {
        return false;
    }
    
    // Create source image based on file type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create blank image with new dimensions
    $destination = imagecreatetruecolor($width, $height);
    
    // Handle transparency for PNG
    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $width, $height, $transparent);
    }
    
    // Crop image
    imagecopy(
        $destination, $source,
        0, 0, $x, $y,
        $width, $height
    );
    
    // Create cropped directory if it doesn't exist
    if (!file_exists('cropped')) {
        mkdir('cropped', 0777, true);
    }
    
    // Generate cropped filename
    $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
    $croppedPath = "cropped/{$filename}_crop_{$width}x{$height}.{$extension}";
    
    // Save cropped image
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $croppedPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $croppedPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $croppedPath);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $croppedPath;
}

// Function to auto-crop an image by removing whitespace/background
function autoCropImage($sourcePath, $threshold = 240, $padding = 10) {
    // Get image info
    $info = getimagesize($sourcePath);
    
    if (!$info) {
        return false;
    }
    
    // Create source image based on file type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            // Set error handler to suppress warnings
            set_error_handler(function($errno, $errstr) {
                // Log the error if needed but don't display
                return true;
            });
            $source = imagecreatefrompng($sourcePath);
            // Restore error handler
            restore_error_handler();
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    $width = $info[0];
    $height = $info[1];
    
    // Initialize crop boundaries at extremes
    $top = $height;
    $bottom = 0;
    $left = $width;
    $right = 0;
    
    // Scan for content pixels
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($source, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $alpha = ($rgb >> 24) & 0x7F; // For PNG transparency
            
            // Check if this pixel is likely not background
            // For transparent images, check alpha channel
            // For regular images, check if color is below threshold (not white)
            $isContent = ($info[2] === IMAGETYPE_PNG && $alpha < 127) || 
                         (max($r, $g, $b) < $threshold);
            
            if ($isContent) {
                $top = min($top, $y);
                $bottom = max($bottom, $y);
                $left = min($left, $x);
                $right = max($right, $x);
            }
        }
    }
    
    // If no content was found, return the original
    if ($top >= $bottom || $left >= $right) {
        return $sourcePath;
    }
    
    // Add padding
    $top = max(0, $top - $padding);
    $bottom = min($height - 1, $bottom + $padding);
    $left = max(0, $left - $padding);
    $right = min($width - 1, $right + $padding);
    
    // Calculate new dimensions
    $newWidth = $right - $left + 1;
    $newHeight = $bottom - $top + 1;
    
    // Create a new image with the cropped dimensions
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG
    if ($info[2] === IMAGETYPE_PNG) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Copy the cropped portion
    imagecopy(
        $destination, $source,
        0, 0, $left, $top,
        $newWidth, $newHeight
    );
    
    // Create autocrop directory if it doesn't exist
    if (!file_exists('autocrop')) {
        if (!mkdir('autocrop', 0777, true)) {
            error_log("Failed to create 'autocrop' directory");
            imagedestroy($source);
            imagedestroy($destination);
            return false;
        }
    }
    
    // Generate cropped filename - sanitize to avoid invalid filenames
    $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
    // Remove problematic characters like '=' that might appear in URL-based filenames
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
    $croppedPath = "autocrop/{$filename}_autocrop.{$extension}";
    
    // Save cropped image
    $success = false;
    try {
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($destination, $croppedPath, 90);
                break;
            case IMAGETYPE_PNG:
                // Turn on interlace handling for PNGs
                imageinterlace($destination, true);
                $success = imagepng($destination, $croppedPath, 9);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($destination, $croppedPath);
                break;
        }
    } catch (Exception $e) {
        error_log("Failed to save cropped image: " . $e->getMessage());
        $success = false;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($destination);
    
    return $success ? $croppedPath : false;
}

// Process form submission
$searchResults = [];
$downloadedImage = null;
$resizedImage = null;
$croppedImage = null;
$autoCroppedImage = null;
$searchQuery = '';
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search']) && !empty($_POST['query'])) {
        $searchQuery = trim($_POST['query']);
        $searchResults = searchGoogleImages($searchQuery);
    } elseif (isset($_POST['search_autocrop']) && !empty($_POST['query'])) {
        // Search and auto-crop in one action
        $searchQuery = trim($_POST['query']);
        $searchResults = searchGoogleImages($searchQuery);
        
        // If we got results, download and auto-crop a random image
        if (!isset($searchResults['error']) && !empty($searchResults)) {
            // Pick a random image
            $randomIndex = array_rand($searchResults);
            $imageUrl = $searchResults[$randomIndex];
            
            // Download it
            $downloadedImage = downloadImage($imageUrl);
            if ($downloadedImage) {
                // Auto-crop it
                $autoCroppedImage = autoCropImage($downloadedImage);
                if (!$autoCroppedImage) {
                    $errorMessage = "Failed to auto-crop image. The image format may not be supported.";
                }
            } else {
                $errorMessage = "Failed to download image for auto-cropping.";
            }
        }
    } elseif (isset($_POST['download']) && !empty($_POST['image_url'])) {
        $imageUrl = $_POST['image_url'];
        $downloadedImage = downloadImage($imageUrl);
        if (!$downloadedImage) {
            $errorMessage = "Failed to download image. The image may no longer be accessible.";
        }
    } elseif (isset($_POST['resize']) && !empty($_POST['image_path'])) {
        $sourcePath = $_POST['image_path'];
        $width = (int)$_POST['width'];
        $height = (int)$_POST['height'];
        
        if ($width > 0 && $height > 0) {
            $resizedImage = resizeImage($sourcePath, $width, $height);
            if (!$resizedImage) {
                $errorMessage = "Failed to resize image. The image format may not be supported.";
            }
        }
    } elseif (isset($_POST['crop']) && !empty($_POST['image_path'])) {
        $sourcePath = $_POST['image_path'];
        $cropX = (int)$_POST['crop_x'];
        $cropY = (int)$_POST['crop_y'];
        $cropWidth = (int)$_POST['crop_width'];
        $cropHeight = (int)$_POST['crop_height'];
        
        if ($cropWidth > 0 && $cropHeight > 0) {
            $croppedImage = cropImage($sourcePath, $cropX, $cropY, $cropWidth, $cropHeight);
            if (!$croppedImage) {
                $errorMessage = "Failed to crop image. The image format may not be supported.";
            }
        }
    } elseif (isset($_POST['autocrop']) && !empty($_POST['image_path'])) {
        $sourcePath = $_POST['image_path'];
        $autoCroppedImage = autoCropImage($sourcePath);
        if (!$autoCroppedImage) {
            $errorMessage = "Failed to auto-crop image. The image format may not be supported.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Image Search</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .search-form {
            margin-bottom: 20px;
        }
        
        .search-form input[type="text"] {
            width: 300px;
            padding: 8px;
            font-size: 16px;
        }
        
        .search-form button {
            padding: 8px 16px;
            font-size: 16px;
            background-color: #4285f4;
            color: white;
            border: none;
            cursor: pointer;
        }

        .search-form button.autocrop {
            background-color: #34a853;
        }
        
        .results {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .image-card {
            border: 1px solid #ddd;
            padding: 10px;
            width: 250px;
        }
        
        .image-card img {
            max-width: 100%;
            height: auto;
            min-height: 150px;
            background-color: #f5f5f5;
        }
        
        .image-card form {
            margin-top: 10px;
        }
        
        .resize-form, .crop-form, .autocrop-form {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
        }
        
        .message {
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f8f8;
            border-left: 4px solid #4285f4;
        }
        
        .error {
            background-color: #fff8f8;
            border-left: 4px solid #d32f2f;
        }
        
        .hidden {
            display: none !important;
        }
        
        .image-container {
            position: relative;
            min-height: 150px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 150px;
            background-color: #f5f5f5;
        }
        
        .error-message {
            color: #d32f2f;
            padding: 10px;
            text-align: center;
            font-size: 12px;
        }
        
        .croparea {
            position: relative;
            max-width: 100%;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .crop-container {
            position: relative;
            margin-top: 20px;
        }
        
        #crop-preview {
            max-width: 100%;
            display: block;
        }
        
        #crop-selection {
            position: absolute;
            border: 2px dashed #4285f4;
            background-color: rgba(66, 133, 244, 0.2);
            cursor: move;
        }
        
        .folder-link {
            display: inline-block;
            margin: 10px 0;
            padding: 8px 16px;
            background-color: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .folder-link:hover {
            background-color: #e3e3e3;
        }
        
        .folder-icon {
            margin-right: 6px;
        }
        
        .control-buttons {
            margin-top: 10px;
        }
        
        .control-buttons button {
            margin-right: 8px;
            padding: 6px 12px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .before-after {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .before-after > div {
            flex: 1;
            min-width: 300px;
        }

        .before-after h4 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>Medication Image Search</h1>
    
    <div class="search-form">
        <form method="post">
            <input type="text" name="query" placeholder="Enter medication name" value="<?php echo htmlspecialchars($searchQuery); ?>" required>
            <button type="submit" name="search">Search</button>
            <button type="submit" name="search_autocrop" class="autocrop">Search & Auto-Crop</button>
        </form>
    </div>
    
    <!-- Open Downloads Folder Link -->
    <div class="folder-links">
        <?php if (file_exists('downloads')): ?>
            <a href="downloads/" class="folder-link" target="_blank">
                <span class="folder-icon">📁</span> Open Downloads Folder
            </a>
        <?php endif; ?>
        
        <?php if (file_exists('resized')): ?>
            <a href="resized/" class="folder-link" target="_blank">
                <span class="folder-icon">📁</span> Open Resized Images Folder
            </a>
        <?php endif; ?>
        
        <?php if (file_exists('cropped')): ?>
            <a href="cropped/" class="folder-link" target="_blank">
                <span class="folder-icon">📁</span> Open Cropped Images Folder
            </a>
        <?php endif; ?>
        
        <?php if (file_exists('autocrop')): ?>
            <a href="autocrop/" class="folder-link" target="_blank">
                <span class="folder-icon">📁</span> Open Auto-Cropped Images Folder
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($errorMessage): ?>
        <div class="message error">
            <p>Error: <?php echo htmlspecialchars($errorMessage); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($searchResults['error'])): ?>
        <div class="message">
            <p>Error: <?php echo $searchResults['error']; ?></p>
            <p>Try using a different search term or check your internet connection.</p>
        </div>
    <?php elseif (!empty($searchResults)): ?>
        <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
        <div class="results">
            <?php foreach ($searchResults as $index => $imageUrl): ?>
                <div class="image-card" id="card-<?php echo $index; ?>">
                    <div class="image-container">
                        <div class="image-loading" id="loading-<?php echo $index; ?>">Loading...</div>
                        <img 
                            src="<?php echo htmlspecialchars($imageUrl); ?>" 
                            alt="Medication image" 
                            data-index="<?php echo $index; ?>"
                            id="img-<?php echo $index; ?>"
                            style="display: none;"
                            onload="imageLoaded(<?php echo $index; ?>)"
                            onerror="imageError(<?php echo $index; ?>)"
                        >
                    </div>
                    <form method="post" id="form-<?php echo $index; ?>">
                        <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($imageUrl); ?>">
                        <button type="submit" name="download">Download</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
            // Track number of successful and failed images
            let successCount = 0;
            let failureCount = 0;
            const totalImages = <?php echo count($searchResults); ?>;
            
            // Set a timeout for image loading
            const imageTimeouts = {};
            
            function imageLoaded(index) {
                // Clear timeout if it exists
                if (imageTimeouts[index]) {
                    clearTimeout(imageTimeouts[index]);
                }
                
                // Show the image, hide loading indicator
                document.getElementById('img-' + index).style.display = 'block';
                document.getElementById('loading-' + index).style.display = 'none';
                
                successCount++;
                updateStatusMessage();
            }
            
            function imageError(index) {
                // Clear timeout if it exists
                if (imageTimeouts[index]) {
                    clearTimeout(imageTimeouts[index]);
                }
                
                // Remove the entire card when image fails to load
                const card = document.getElementById('card-' + index);
                if (card) {
                    card.remove();
                }
                
                failureCount++;
                updateStatusMessage();
            }
            
            function updateStatusMessage() {
                // Create a status message if needed
                if (successCount + failureCount === totalImages) {
                    const resultsDiv = document.querySelector('.results');
                    let statusMessage = document.getElementById('status-message');
                    
                    if (!statusMessage) {
                        statusMessage = document.createElement('div');
                        statusMessage.id = 'status-message';
                        statusMessage.className = 'message';
                        document.querySelector('h2').after(statusMessage);
                    }
                    
                    statusMessage.innerHTML = `
                        <p>Found ${successCount} valid image${successCount !== 1 ? 's' : ''}. 
                        ${failureCount} image${failureCount !== 1 ? 's' : ''} removed.</p>
                    `;
                    
                    // If all images failed, show a suggestion
                    if (successCount === 0) {
                        statusMessage.innerHTML += `
                            <p>Try a different search term or check your internet connection.</p>
                        `;
                    }
                }
            }
            
            // Set timeout for images to detect long-running loading issues
            document.addEventListener('DOMContentLoaded', function() {
                const allImages = document.querySelectorAll('img[data-index]');
                allImages.forEach(img => {
                    const index = img.getAttribute('data-index');
                    
                    // Set a timeout for each image (6 seconds)
                    imageTimeouts[index] = setTimeout(() => {
                        // If image is still loading after timeout, mark as error
                        if (img.style.display === 'none' && 
                            document.getElementById('loading-' + index).style.display !== 'none') {
                            imageError(index);
                        }
                    }, 6000);
                });
            });
        </script>
    <?php endif; ?>
    
    <?php if ($downloadedImage): ?>
        <div class="message">
            <p>Image downloaded successfully: <?php echo htmlspecialchars($downloadedImage); ?></p>
            
            <!-- Image Processing Options -->
            <div class="image-options">
                <!-- Resize Form -->
                <div class="resize-form">
                    <h3>Resize Image</h3>
                    <form method="post">
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($downloadedImage); ?>">
                        <label for="width">Width:</label>
                        <input type="number" id="width" name="width" min="10" max="2000" value="300" required>
                        <label for="height">Height:</label>
                        <input type="number" id="height" name="height" min="10" max="2000" value="300" required>
                        <button type="submit" name="resize">Resize</button>
                    </form>
                </div>
                
                <!-- Auto-Crop Form -->
                <div class="autocrop-form">
                    <h3>Auto-Crop Image</h3>
                    <p>Automatically crop the image to remove whitespace around the main content:</p>
                    <form method="post">
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($downloadedImage); ?>">
                        <button type="submit" name="autocrop">Auto-Crop Image</button>
                    </form>
                </div>
                
                <!-- Crop Form -->
                <div class="crop-form">
                    <h3>Manual Crop</h3>
                    <p>Select an area to crop by clicking and dragging on the image:</p>
                    
                    <div class="crop-container">
                        <img src="<?php echo htmlspecialchars($downloadedImage); ?>" id="crop-preview" alt="Image to crop">
                        <div id="crop-selection" style="display: none;"></div>
                    </div>
                    
                    <div class="control-buttons">
                        <button type="button" id="reset-crop">Reset Selection</button>
                    </div>
                    
                    <form method="post" id="crop-form">
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($downloadedImage); ?>">
                        <input type="hidden" id="crop_x" name="crop_x" value="0">
                        <input type="hidden" id="crop_y" name="crop_y" value="0">
                        <input type="hidden" id="crop_width" name="crop_width" value="0">
                        <input type="hidden" id="crop_height" name="crop_height" value="0">
                        <button type="submit" name="crop" id="crop-button" disabled>Crop Image</button>
                    </form>
                    
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const preview = document.getElementById('crop-preview');
                            const selection = document.getElementById('crop-selection');
                            const cropForm = document.getElementById('crop-form');
                            const cropXInput = document.getElementById('crop_x');
                            const cropYInput = document.getElementById('crop_y');
                            const cropWidthInput = document.getElementById('crop_width');
                            const cropHeightInput = document.getElementById('crop_height');
                            const cropButton = document.getElementById('crop-button');
                            const resetButton = document.getElementById('reset-crop');
                            
                            let isSelecting = false;
                            let startX = 0;
                            let startY = 0;
                            let originalWidth = 0;
                            let originalHeight = 0;
                            let scale = 1;
                            
                            // Once image is loaded, get its dimensions
                            preview.onload = function() {
                                originalWidth = preview.naturalWidth;
                                originalHeight = preview.naturalHeight;
                                scale = preview.width / originalWidth;
                            };
                            
                            // Mouse down event - start selection
                            preview.addEventListener('mousedown', function(e) {
                                // Get position relative to image
                                const rect = preview.getBoundingClientRect();
                                startX = e.clientX - rect.left;
                                startY = e.clientY - rect.top;
                                
                                // Show selection and position it
                                selection.style.left = startX + 'px';
                                selection.style.top = startY + 'px';
                                selection.style.width = '0px';
                                selection.style.height = '0px';
                                selection.style.display = 'block';
                                
                                isSelecting = true;
                            });
                            
                            // Mouse move event - resize selection
                            document.addEventListener('mousemove', function(e) {
                                if (!isSelecting) return;
                                
                                const rect = preview.getBoundingClientRect();
                                let currentX = e.clientX - rect.left;
                                let currentY = e.clientY - rect.top;
                                
                                // Ensure selection stays within image boundaries
                                currentX = Math.max(0, Math.min(currentX, preview.width));
                                currentY = Math.max(0, Math.min(currentY, preview.height));
                                
                                // Calculate selection width and height
                                const width = Math.abs(currentX - startX);
                                const height = Math.abs(currentY - startY);
                                
                                // Calculate top-left corner for selection
                                const left = Math.min(startX, currentX);
                                const top = Math.min(startY, currentY);
                                
                                // Update selection position and size
                                selection.style.left = left + 'px';
                                selection.style.top = top + 'px';
                                selection.style.width = width + 'px';
                                selection.style.height = height + 'px';
                                
                                // Update form inputs (convert to original image coordinates)
                                cropXInput.value = Math.round(left / scale);
                                cropYInput.value = Math.round(top / scale);
                                cropWidthInput.value = Math.round(width / scale);
                                cropHeightInput.value = Math.round(height / scale);
                                
                                // Enable crop button if selection is valid
                                cropButton.disabled = (width < 10 || height < 10);
                            });
                            
                            // Mouse up event - finish selection
                            document.addEventListener('mouseup', function() {
                                isSelecting = false;
                            });
                            
                            // Reset button click
                            resetButton.addEventListener('click', function() {
                                selection.style.display = 'none';
                                cropXInput.value = 0;
                                cropYInput.value = 0;
                                cropWidthInput.value = 0;
                                cropHeightInput.value = 0;
                                cropButton.disabled = true;
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($resizedImage): ?>
        <div class="message">
            <p>Image resized successfully: <?php echo htmlspecialchars($resizedImage); ?></p>
            <img src="<?php echo htmlspecialchars($resizedImage); ?>" alt="Resized image">
            <a href="<?php echo htmlspecialchars($resizedImage); ?>" download class="folder-link">
                <span class="folder-icon">⬇️</span> Download Resized Image
            </a>
        </div>
    <?php endif; ?>
    
    <?php if ($croppedImage): ?>
        <div class="message">
            <p>Image cropped successfully: <?php echo htmlspecialchars($croppedImage); ?></p>
            <img src="<?php echo htmlspecialchars($croppedImage); ?>" alt="Cropped image">
            <a href="<?php echo htmlspecialchars($croppedImage); ?>" download class="folder-link">
                <span class="folder-icon">⬇️</span> Download Cropped Image
            </a>
        </div>
    <?php endif; ?>
    
    <?php if ($autoCroppedImage): ?>
        <div class="message">
            <p>Image auto-cropped successfully!</p>
            
            <div class="before-after">
                <div class="before">
                    <h4>Before</h4>
                    <img src="<?php echo htmlspecialchars($downloadedImage); ?>" alt="Original image" style="max-width: 100%;">
                </div>
                <div class="after">
                    <h4>After Auto-Crop</h4>
                    <img src="<?php echo htmlspecialchars($autoCroppedImage); ?>" alt="Auto-cropped image" style="max-width: 100%;">
                </div>
            </div>
            
            <a href="<?php echo htmlspecialchars($autoCroppedImage); ?>" download class="folder-link">
                <span class="folder-icon">⬇️</span> Download Auto-Cropped Image
            </a>
        </div>
    <?php endif; ?>
    
    <footer>
        <p><small>Disclaimer: This tool scrapes Google Images directly. Use responsibly and be aware this may violate Google's Terms of Service.</small></p>
    </footer>
</body>
</html> 