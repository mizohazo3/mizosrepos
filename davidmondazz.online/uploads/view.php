<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .media-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .media-player {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
        }
        .media-info {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .back-button {
            margin-bottom: 20px;
        }
        .image-preview {
            max-width: 100%;
            max-height: 80vh;
            margin: 20px auto;
            display: block;
        }
    </style>
</head>
<body>
<?php
session_start();
date_default_timezone_set("Africa/Cairo");
include '../checkSession.php';

// Get the file parameter from the query string
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Define supported media types
$audioTypes = ['mp3', 'ogg', 'wav', 'flac', 'aac', 'wma'];
$videoTypes = ['mp4', 'webm', 'ogg', 'mkv', 'avi', 'mov', 'flv'];
$imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

// Get the file extension
$fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));

// Check if the file exists
if (file_exists($file) && is_file($file)) {
    echo '<div class="media-container">';
    echo '<a href="index.php" class="btn btn-secondary back-button">← Back to Files</a>';
    echo '<h1 class="mb-4">' . htmlspecialchars(basename($file)) . '</h1>';
    
    // File information
    $fileSize = filesize($file);
    $fileDate = date("F j, Y, g:i a", filemtime($file));
    echo '<div class="media-info">';
    echo '<p><strong>File Size:</strong> ' . formatSizeUnits($fileSize) . '</p>';
    echo '<p><strong>Upload Date:</strong> ' . $fileDate . '</p>';
    echo '</div>';
    
    if (in_array($fileExt, $imageTypes)) {
        // Display image with preview
        echo '<img src="' . htmlspecialchars($file) . '" alt="' . htmlspecialchars(basename($file)) . '" class="image-preview">';
    } elseif (in_array($fileExt, $audioTypes)) {
        // Display audio file with enhanced audio player
        echo '<div class="media-player">';
        echo '<audio controls class="w-100">';
        echo '<source src="' . htmlspecialchars($file) . '" type="audio/' . htmlspecialchars($fileExt) . '">';
        echo 'Your browser does not support the audio element.';
        echo '</audio>';
        echo '</div>';
    } elseif (in_array($fileExt, $videoTypes)) {
        // Display video file with enhanced video player
        echo '<div class="media-player">';
        echo '<video controls class="w-100">';
        echo '<source src="' . htmlspecialchars($file) . '" type="video/' . htmlspecialchars($fileExt) . '">';
        echo 'Your browser does not support the video element.';
        echo '</video>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-info">This file type is not supported for preview.</div>';
    }
    
    // Download button
    echo '<div class="mt-4">';
    echo '<a href="' . htmlspecialchars($file) . '" download class="btn btn-primary">Download File</a>';
    echo '</div>';
    
    echo '</div>';
} else {
    echo '<div class="media-container">';
    echo '<div class="alert alert-danger">File does not exist.</div>';
    echo '<a href="index.php" class="btn btn-secondary">Back to Files</a>';
    echo '</div>';
}

// Function to format file sizes
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>