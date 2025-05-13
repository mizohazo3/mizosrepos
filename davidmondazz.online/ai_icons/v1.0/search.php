<?php
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
        }
        .download-btn:hover {
            background: #3d8b40;
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
    </style>
</head>
<body>
    <h1>Free Icon Search Results</h1>
    
    <form action="search.php" method="GET" class="search-bar">
        <input type="text" name="query" value="<?php echo htmlspecialchars($_GET['query']); ?>" placeholder="Search for icons..." required>
        <button type="submit">Search</button>
    </form>
    
    <a href="index.php" class="back-link">← Back to Home</a>
    
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
                    <img class="icon-image" src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($_GET['query']); ?> icon" onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='; this.style.opacity='0.2';">
                    <a href="<?php echo htmlspecialchars($imageUrl); ?>" download class="download-btn">Download</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
</body>
</html> 