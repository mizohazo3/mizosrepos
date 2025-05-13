<?php

/**
 * Makes URLs in text clickable and fetches titles for common websites
 * 
 * @param string $text The text containing URLs
 * @return string Text with URLs converted to clickable links with titles
 */
function fetchTitledLinks($text) {
    // URL pattern matching
    $pattern = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    
    // Replace each URL with its titled link
    $text = preg_replace_callback($pattern, function($matches) {
        $url = $matches[0];
        
        // Make sure URL starts with http:// or https://
        if (strpos($url, 'http') !== 0) {
            $url = 'https://' . $url;
        }
        
        // Get the title for the URL
        $title = fetchTitleFromUrl($url);
        
        // If no title is found, use the URL as title
        if (empty($title)) {
            $title = $url;
        }
        
        // Clean URL for JavaScript (encode quotes and backslashes)
        $jsUrl = addslashes($url);
        
        // Check if refresh.png exists
        $refreshIcon = file_exists('img/refresh.png') 
            ? '<img src="img/refresh.png" style="width:12px;height:12px;margin-left:5px;vertical-align:middle;opacity:0.5;">'
            : '↻';
        
        // Return the formatted link with refresh button
        return '<span class="url-container" data-url="'.htmlspecialchars($url).'">'.
               '<a href="'.$url.'" target="_blank">['.$title.']</a>'.
               '<a href="javascript:void(0)" onclick="refreshUrlTitle(\''.$jsUrl.'\')" class="refresh-link" title="Refresh URL title">'.
               $refreshIcon.
               '</a></span>';
    }, $text);
    
    return $text;
}

// Override the original makeClickableLinks function to use our implementation
function makeClickableLinks($text) {
    return fetchTitledLinks($text);
}

// Global cache array for URL titles
$urlTitleCache = [];

/**
 * Fetches the title from a URL with caching
 * 
 * @param string $url The URL to fetch title from
 * @return string The title of the page or empty string if not found
 */
function fetchTitleFromUrl($url) {
    global $urlTitleCache;
    
    // Check if the URL is already in cache
    if (isset($urlTitleCache[$url])) {
        return $urlTitleCache[$url];
    }
    
    // Check if we have a database cache
    $title = fetchTitleFromDatabase($url);
    if ($title !== false) {
        $urlTitleCache[$url] = $title; // Update memory cache
        return $title;
    }
    
    // Handle YouTube URLs directly
    if (strpos($url, 'youtube.com/watch') !== false || strpos($url, 'youtu.be/') !== false) {
        // Extract video ID
        $videoId = '';
        if (strpos($url, 'youtube.com/watch') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $params);
            $videoId = isset($params['v']) ? $params['v'] : '';
        } elseif (strpos($url, 'youtu.be/') !== false) {
            $path = parse_url($url, PHP_URL_PATH);
            $videoId = trim($path, '/');
        }
        
        if (!empty($videoId)) {
            // Use oEmbed API to get video title
            $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json";
            $ch = curl_init($oembedUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['title'])) {
                    $title = $data['title'];
                    // Store in database cache
                    storeTitleInDatabase($url, $title);
                    
                    // Store in memory cache
                    $urlTitleCache[$url] = $title;
                    
                    return $title;
                }
            }
        }
    }
    
    // Initialize cURL for regular page title fetching
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout after 5 seconds
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Execute cURL
    $html = curl_exec($ch);
    
    // Check if any error occurred
    if (curl_errno($ch)) {
        curl_close($ch);
        $urlTitleCache[$url] = ''; // Cache the empty result
        return '';
    }
    
    curl_close($ch);
    
    // Extract title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
        $title = trim($matches[1]);
        
        // Clean up common title suffixes
        $suffixes = [' - YouTube', ' | Facebook', ' | Twitter', ' | LinkedIn', ' | Instagram'];
        foreach ($suffixes as $suffix) {
            $title = str_replace($suffix, '', $title);
        }
        
        $title = htmlspecialchars($title);
        
        // Store in database cache
        storeTitleInDatabase($url, $title);
        
        // Store in memory cache
        $urlTitleCache[$url] = $title;
        
        return $title;
    }
    
    // Cache the empty result
    $urlTitleCache[$url] = '';
    storeTitleInDatabase($url, '');
    
    return '';
}

/**
 * Fetches a URL title from the database cache
 * 
 * @param string $url The URL to look up
 * @return string|false The cached title or false if not found
 */
function fetchTitleFromDatabase($url) {
    global $con;
    
    try {
        // Check if the url_cache table exists
        $tableCheck = $con->query("SHOW TABLES LIKE 'url_cache'");
        if ($tableCheck->rowCount() == 0) {
            // Create the table if it doesn't exist
            $con->exec("CREATE TABLE url_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(2048) NOT NULL,
                title TEXT,
                fetch_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_url (url(255))
            )");
        }
        
        // Look up the URL
        $stmt = $con->prepare("SELECT title FROM url_cache WHERE url = ? LIMIT 1");
        $stmt->execute([$url]);
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['title'];
        }
        
        return false;
    } catch (PDOException $e) {
        // If any database error occurs, just return false and proceed without caching
        return false;
    }
}

/**
 * Stores a URL title in the database cache
 * 
 * @param string $url The URL to cache
 * @param string $title The title to cache
 * @return bool Success or failure
 */
function storeTitleInDatabase($url, $title) {
    global $con;
    
    try {
        // Check if the url_cache table exists
        $tableCheck = $con->query("SHOW TABLES LIKE 'url_cache'");
        if ($tableCheck->rowCount() == 0) {
            // If we get here, it means fetchTitleFromDatabase didn't create the table
            // This is unexpected but let's handle it anyway
            $con->exec("CREATE TABLE url_cache (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url VARCHAR(2048) NOT NULL,
                title TEXT,
                fetch_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_url (url(255))
            )");
        }
        
        // Insert or update the URL title
        $stmt = $con->prepare("INSERT INTO url_cache (url, title) VALUES (?, ?) 
                              ON DUPLICATE KEY UPDATE title = ?, fetch_date = CURRENT_TIMESTAMP");
        $stmt->execute([$url, $title, $title]);
        
        return true;
    } catch (PDOException $e) {
        // If any database error occurs, just return false
        return false;
    }
}

/**
 * Forces a refresh of a URL's title cache
 * 
 * @param string $url The URL to refresh
 * @return string The new title
 */
function refreshUrlTitle($url) {
    global $urlTitleCache, $con;
    
    // Remove from memory cache
    if (isset($urlTitleCache[$url])) {
        unset($urlTitleCache[$url]);
    }
    
    // Remove from database cache
    try {
        $stmt = $con->prepare("DELETE FROM url_cache WHERE url = ?");
        $stmt->execute([$url]);
    } catch (PDOException $e) {
        // Ignore database errors
    }
    
    // Fetch a fresh title
    return fetchTitleFromUrl($url);
} 