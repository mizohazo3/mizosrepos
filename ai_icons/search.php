<?php
// Process download or resize request if present
if (isset($_GET['download']) && !empty($_GET['image_url'])) {
    $imageUrl = urldecode($_GET['image_url']);
    $width = isset($_GET['width']) ? (int)$_GET['width'] : 0;
    $height = isset($_GET['height']) ? (int)$_GET['height'] : 0;
    $searchTerm = isset($_GET['term']) ? preg_replace('/[^a-z0-9]+/i', '_', $_GET['term']) : 'icon';
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = 'uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate a unique filename based on search term
    $baseFilename = $searchTerm . '_' . time() . '_' . mt_rand(1000, 9999);
    $filename = $baseFilename . '.png';
    $filepath = $uploadsDir . '/' . $filename;
    
    // Make sure the filename is unique
    $counter = 1;
    while (file_exists($filepath)) {
        $filename = $baseFilename . '_' . $counter . '.png';
        $filepath = $uploadsDir . '/' . $filename;
        $counter++;
    }
    
    // Get image content
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $imageData = curl_exec($ch);
    curl_close($ch);
    
    if (!$imageData) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error downloading image']);
        exit;
    }
    
    // If resize is requested
    if ($width > 0 && $height > 0) {
        // Create image from string
        $source = imagecreatefromstring($imageData);
        if (!$source) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid image format']);
            exit;
        }
        
        // Create destination image
        $destination = imagecreatetruecolor($width, $height);
        
        // Preserve transparency for PNG
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $width, $height, $transparent);
        
        // Resize image
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        
        // Save the resized image to file
        $success = imagepng($destination, $filepath);
        imagedestroy($source);
        imagedestroy($destination);
        
        if (!$success) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to save resized image']);
            exit;
        }
    } else {
        // Save the original image to file
        $success = file_put_contents($filepath, $imageData);
        
        if (!$success) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
            exit;
        }
    }
    
    // Respond with success and file information
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Image saved successfully', 
        'filename' => $filename,
        'filepath' => $filepath
    ]);
    exit;
}

// Get the search query from the user
$query = isset($_GET['query']) ? $_GET['query'] . ' icon png transparent free' : '';

if (empty($query)) {
    header('Location: index.php');
    exit;
}

// Function to search for images using curl
function searchImages($query, $count = 20) {
    // Using Google Image Search
    $searchUrl = "https://www.google.com/search?q=" . urlencode($query) . "&tbm=isch&tbs=ic:trans,isz:m,itp:clipart,ift:png,il:cl";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $searchUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEFILE, '');
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return array('error' => curl_error($ch));
    }
    
    curl_close($ch);
    
    // Extract image URLs from the response
    $imageUrls = array();
    
    // Pattern to match Google image data
    preg_match_all('/"(https?:\/\/[^"]+\.(?:png|PNG))"/', $response, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $url) {
            // Filter out Google's own images and ensure we're getting PNG files
            if (strpos($url, 'gstatic.com') === false && 
                strpos($url, 'google.com') === false && 
                preg_match('/\.png$/i', $url)) {
                $imageUrls[] = $url;
            }
        }
    }
    
    // Limit to unique results and cap at requested count
    $imageUrls = array_unique($imageUrls);
    $imageUrls = array_slice($imageUrls, 0, $count);
    
    return $imageUrls;
}

// Search for images
$results = searchImages($query);

// Debug information - useful for development
$debug = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for <?php echo htmlspecialchars($_GET['query']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .search-bar {
            display: flex;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        input[type="text"] {
            flex: 1;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        button {
            padding: 12px 24px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.2s;
        }
        button:hover {
            background: #357ae8;
        }
        .results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .icon-item {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .icon-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .icon-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        .icon-item:hover .icon-image {
            transform: scale(1.05);
        }
        .download-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
            margin-right: 5px;
        }
        .download-btn:hover {
            background: #3d8b40;
        }
        .resize-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #FF9800;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
            cursor: pointer;
        }
        .resize-btn:hover {
            background: #F57C00;
        }
        .error-message {
            text-align: center;
            color: #d32f2f;
            margin: 50px 0;
            padding: 20px;
            background: #ffebee;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .back-link {
            display: block;
            text-align: center;
            margin: 20px 0;
            text-decoration: none;
            color: #4285f4;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 15px;
            margin-top: 30px;
            border-radius: 8px;
            border: 1px dashed #aaa;
            font-family: monospace;
            white-space: pre-wrap;
            overflow: auto;
            max-height: 300px;
        }
        .result-stats {
            text-align: center;
            color: #666;
            margin: 10px 0;
        }
        .resize-modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-title {
            margin-top: 0;
            color: #333;
        }
        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .modal-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .modal-form input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        .modal-btns {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .modal-close {
            padding: 8px 16px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-download {
            padding: 8px 16px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-container {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        .hidden {
            display: none !important;
        }
        .download-btn:disabled, .resize-btn:disabled, .modal-download:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        #notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .uploads-link {
            display: block;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .uploads-link:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <h1>Free Icon Search Results</h1>
    
    <form action="search.php" method="GET" class="search-bar">
        <input type="text" name="query" value="<?php echo htmlspecialchars($_GET['query']); ?>" placeholder="Search for icons..." required>
        <button type="submit">Search</button>
    </form>
    
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <?php if (file_exists('uploads') && is_dir('uploads')): ?>
        <a href="uploads/" class="uploads-link">Browse Downloaded Icons (<?php echo count(glob('uploads/*.png')); ?> icons)</a>
    <?php endif; ?>
    
    <?php if (!empty($results) && !isset($results['error'])): ?>
        <p class="result-stats">Found <?php echo count($results); ?> free PNG icons for "<?php echo htmlspecialchars($_GET['query']); ?>"</p>
    <?php endif; ?>
    
    <div class="results">
        <?php if (isset($results['error'])): ?>
            <div class="error-message">
                <p>Error: <?php echo htmlspecialchars($results['error']); ?></p>
            </div>
        <?php elseif (empty($results)): ?>
            <div class="error-message">
                <p>No free PNG icons found for "<?php echo htmlspecialchars($_GET['query']); ?>". Try another search term.</p>
            </div>
        <?php else: ?>
            <?php foreach ($results as $index => $imageUrl): ?>
                <div class="icon-item">
                    <img class="icon-image" src="<?php echo htmlspecialchars($imageUrl); ?>" 
                         alt="<?php echo htmlspecialchars($_GET['query']); ?> icon" 
                         onerror="this.parentNode.style.display = 'none';">
                    <div class="btn-container">
                        <button type="button" class="download-btn" data-url="<?php echo htmlspecialchars($imageUrl); ?>" data-term="<?php echo htmlspecialchars($_GET['query']); ?>">Download</button>
                        <button type="button" class="resize-btn" data-url="<?php echo htmlspecialchars($imageUrl); ?>" data-term="<?php echo htmlspecialchars($_GET['query']); ?>">Resize</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Resize Modal -->
    <div id="resizeModal" class="resize-modal">
        <div class="modal-content">
            <h3 class="modal-title">Resize Image</h3>
            <form id="resizeForm" class="modal-form" method="POST">
                <input type="hidden" name="image_url" id="modalImageUrl" value="">
                <input type="hidden" name="term" id="modalSearchTerm" value="">
                <div>
                    <label for="width">Width (px):</label>
                    <input type="number" id="width" name="width" min="16" max="1000" value="128" required>
                </div>
                <div>
                    <label for="height">Height (px):</label>
                    <input type="number" id="height" name="height" min="16" max="1000" value="128" required>
                </div>
                <div class="modal-btns">
                    <button type="button" class="modal-close">Cancel</button>
                    <button type="submit" class="modal-download">Download</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($debug): ?>
    <div class="debug-info">
        <h3>Debug Information:</h3>
        <p>Query: <?php echo htmlspecialchars($query); ?></p>
        <p>Number of results: <?php echo count($results); ?></p>
        <p>URLs found:</p>
        <pre><?php echo is_array($results) ? htmlspecialchars(print_r($results, true)) : htmlspecialchars($results); ?></pre>
    </div>
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('resizeModal');
            const resizeButtons = document.querySelectorAll('.resize-btn');
            const downloadButtons = document.querySelectorAll('.download-btn');
            const closeButton = document.querySelector('.modal-close');
            const modalImageUrl = document.getElementById('modalImageUrl');
            const modalSearchTerm = document.getElementById('modalSearchTerm');
            const resizeForm = document.getElementById('resizeForm');
            
            // Handle direct downloads
            downloadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const imageUrl = this.getAttribute('data-url');
                    const searchTerm = this.getAttribute('data-term');
                    const button = this;
                    
                    // Original text
                    const originalText = button.textContent;
                    button.textContent = 'Downloading...';
                    button.disabled = true;
                    
                    // Send AJAX request
                    fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?download=1&image_url=${encodeURIComponent(imageUrl)}&term=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                button.textContent = 'Saved!';
                                setTimeout(() => {
                                    button.textContent = originalText;
                                    button.disabled = false;
                                }, 2000);
                                showNotification(`Image saved to ${data.filepath}`);
                            } else {
                                button.textContent = 'Failed';
                                alert(data.message || 'Failed to download image');
                                setTimeout(() => {
                                    button.textContent = originalText;
                                    button.disabled = false;
                                }, 2000);
                            }
                        })
                        .catch(error => {
                            button.textContent = 'Error';
                            console.error('Error:', error);
                            alert('An error occurred during download');
                            setTimeout(() => {
                                button.textContent = originalText;
                                button.disabled = false;
                            }, 2000);
                        });
                });
            });
            
            // Open modal when resize button is clicked
            resizeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const imageUrl = this.getAttribute('data-url');
                    const searchTerm = this.getAttribute('data-term');
                    modalImageUrl.value = imageUrl;
                    modalSearchTerm.value = searchTerm;
                    modal.style.display = 'block';
                });
            });
            
            // Close modal when close button is clicked
            closeButton.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Handle resize form submission
            resizeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!modalImageUrl.value) {
                    alert('Image URL is missing');
                    return;
                }
                
                const formData = new FormData(this);
                const width = formData.get('width');
                const height = formData.get('height');
                const imageUrl = formData.get('image_url');
                const searchTerm = formData.get('term');
                
                const downloadBtn = document.querySelector('.modal-download');
                const originalText = downloadBtn.textContent;
                downloadBtn.textContent = 'Processing...';
                downloadBtn.disabled = true;
                
                // Send AJAX request
                fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?download=1&image_url=${encodeURIComponent(imageUrl)}&term=${encodeURIComponent(searchTerm)}&width=${width}&height=${height}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            downloadBtn.textContent = 'Saved!';
                            setTimeout(() => {
                                downloadBtn.textContent = originalText;
                                downloadBtn.disabled = false;
                                modal.style.display = 'none';
                            }, 2000);
                            showNotification(`Resized image saved to ${data.filepath}`);
                        } else {
                            downloadBtn.textContent = 'Failed';
                            alert(data.message || 'Failed to resize and download image');
                            setTimeout(() => {
                                downloadBtn.textContent = originalText;
                                downloadBtn.disabled = false;
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        downloadBtn.textContent = 'Error';
                        console.error('Error:', error);
                        alert('An error occurred during resize and download');
                        setTimeout(() => {
                            downloadBtn.textContent = originalText;
                            downloadBtn.disabled = false;
                        }, 2000);
                    });
            });
            
            // Notification function
            function showNotification(message) {
                // Create notification element if it doesn't exist
                let notification = document.getElementById('notification');
                if (!notification) {
                    notification = document.createElement('div');
                    notification.id = 'notification';
                    notification.style.position = 'fixed';
                    notification.style.bottom = '20px';
                    notification.style.right = '20px';
                    notification.style.backgroundColor = '#4CAF50';
                    notification.style.color = 'white';
                    notification.style.padding = '15px 20px';
                    notification.style.borderRadius = '5px';
                    notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                    notification.style.zIndex = '1000';
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.3s ease-in-out';
                    document.body.appendChild(notification);
                }
                
                // Set message and show notification
                notification.textContent = message;
                notification.style.opacity = '1';
                
                // Hide after 4 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                }, 4000);
            }
        });
    </script>
</body>
</html> 