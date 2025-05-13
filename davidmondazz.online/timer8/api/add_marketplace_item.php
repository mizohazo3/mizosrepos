<?php

include_once '../timezone_config.php';
// api/add_marketplace_item.php

ini_set('display_errors', 1); // Turn ON for debugging during development
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'db.php'; // Ensure DB connection details are correct

// --- Configuration ---
$uploadDir = __DIR__ . '/../uploads/market_images/'; // Go up one level, then into uploads/market_images
$maxFileSize = 1 * 1024 * 1024; // 1 MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
// Base URL path for accessing uploaded images from the web
// Adjust if your uploads directory is mapped differently by the web server
$baseImageUrlPath = 'uploads/market_images/';
// --------------------

// --- Helper function for redirecting with messages ---
function redirect_with_message($page, $status, $message) {
    $location = "../{$page}?status=" . urlencode($status) . "&message=" . urlencode($message);
    header("Location: " . $location);
    exit; // Important: Stop script execution after redirect
}

// --- Script Logic ---

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     redirect_with_message('market_manage.php', 'error', 'Invalid request method.');
}

// --- Retrieve and Sanitize Form Data ---
$itemName = isset($_POST['item_name']) ? trim($_POST['item_name']) : null;
$itemDescription = isset($_POST['item_description']) ? trim($_POST['item_description']) : '';
$itemPrice = isset($_POST['item_price']) ? filter_var($_POST['item_price'], FILTER_VALIDATE_FLOAT) : null;
$itemStock = isset($_POST['stock']) ? filter_var($_POST['stock'], FILTER_VALIDATE_INT) : -1; // Default to -1 (infinite)

// Basic Validation
if (empty($itemName) || $itemPrice === false) {
    redirect_with_message('market_manage.php', 'error', 'Invalid or missing item name or price.');
}

// Check if base64 image data is provided
$base64Image = isset($_POST['base64_image']) ? $_POST['base64_image'] : null;
$imageUrlToStore = null;
$targetFilePath = null;

// Ensure upload directory exists and is writable
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) { // Create recursively with appropriate permissions
        redirect_with_message('market_manage.php', 'error', 'Server configuration error: Cannot create upload directory.');
    }
}
if (!is_writable($uploadDir)) {
    redirect_with_message('market_manage.php', 'error', 'Server configuration error: Upload directory not writable.');
}

// Handle image: either base64 data or file upload
if (!empty($base64Image)) {
    // Process base64 image data from Google fetch
    if (strpos($base64Image, 'data:image/') !== 0) {
        redirect_with_message('market_manage.php', 'error', 'Invalid base64 image format.');
    }
    
    // Extract image data
    $imageData = explode(',', $base64Image);
    if (count($imageData) !== 2) {
        redirect_with_message('market_manage.php', 'error', 'Invalid base64 image data.');
    }
    
    // Determine file type from base64 header
    $fileType = null;
    if (strpos($imageData[0], 'data:image/png') !== false) {
        $fileType = 'png';
    } elseif (strpos($imageData[0], 'data:image/jpeg') !== false || strpos($imageData[0], 'data:image/jpg') !== false) {
        $fileType = 'jpg';
    } elseif (strpos($imageData[0], 'data:image/gif') !== false) {
        $fileType = 'gif';
    } else {
        redirect_with_message('market_manage.php', 'error', 'Unsupported image format in base64 data.');
    }
    
    // Decode base64 data
    $imageContent = base64_decode($imageData[1]);
    if ($imageContent === false) {
        redirect_with_message('market_manage.php', 'error', 'Failed to decode base64 image data.');
    }
    
    // Create unique filename and save the image
    $uniqueFilename = uniqid('item_', true) . '.' . $fileType;
    $targetFilePath = $uploadDir . $uniqueFilename;
    
    if (file_put_contents($targetFilePath, $imageContent) === false) {
        redirect_with_message('market_manage.php', 'error', 'Failed to save fetched image.');
    }
    
    $imageUrlToStore = $baseImageUrlPath . $uniqueFilename;
    
} elseif (!empty($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Process standard file upload
    $fileInfo = $_FILES['item_image'];
    $uploadError = $fileInfo['error'];

    // Check for general upload errors
    if ($uploadError !== UPLOAD_ERR_OK) {
        $uploadErrorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $message = $uploadErrorMessages[$uploadError] ?? 'Unknown upload error.';
         redirect_with_message('market_manage.php', 'error', 'Image upload failed: ' . $message);
    }

    // Check file size
    if ($fileInfo['size'] > $maxFileSize) {
        redirect_with_message('market_manage.php', 'error', 'Image file is too large (Max: ' . ($maxFileSize / 1024 / 1024) . 'MB).');
    }

    // Check MIME type using finfo (more reliable than $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileInfo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        redirect_with_message('market_manage.php', 'error', 'Invalid image file type. Allowed types: JPG, PNG, GIF.');
    }

    // Create unique filename
    $fileExtension = pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
    $uniqueFilename = uniqid('item_', true) . '.' . strtolower($fileExtension);
    $targetFilePath = $uploadDir . $uniqueFilename;

    // Move the uploaded file
    if (!move_uploaded_file($fileInfo['tmp_name'], $targetFilePath)) {
         redirect_with_message('market_manage.php', 'error', 'Failed to save uploaded image.');
    }
    
    $imageUrlToStore = $baseImageUrlPath . $uniqueFilename;
} else {
    // No image provided
    redirect_with_message('market_manage.php', 'error', 'Image is required - either upload or fetch one.');
}

// --- Store in Database ---
try {
    $sql = "INSERT INTO marketplace_items
                (name, description, price, image_url, stock, is_active, created_at)
            VALUES
                (:name, :description, :price, :image_url, :stock, 1, NOW())";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':name', $itemName, PDO::PARAM_STR);
    $stmt->bindParam(':description', $itemDescription, PDO::PARAM_STR);
    $stmt->bindParam(':price', $itemPrice); // PDO should handle float type
    $stmt->bindParam(':image_url', $imageUrlToStore, PDO::PARAM_STR);
    $stmt->bindParam(':stock', $itemStock, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Success
        redirect_with_message('market_manage.php', 'success', "Item '$itemName' added.");
    } else {
        // Database execution failed
        // Optionally try to delete the uploaded file if DB insert fails
        if (file_exists($targetFilePath)) { unlink($targetFilePath); }
        redirect_with_message('market_manage.php', 'error', 'Database error while saving item.');
    }

} catch (PDOException $e) {
    // Handle potential PDO errors (like duplicate name if UNIQUE constraint)
    if (file_exists($targetFilePath)) { unlink($targetFilePath); } // Clean up uploaded file

    if ($e->getCode() == 23000) { // Integrity constraint violation (like duplicate entry)
        redirect_with_message('market_manage.php', 'error', 'Item name already exists.');
    } else {
        redirect_with_message('market_manage.php', 'error', 'Database error: ' . $e->getMessage());
    }
} catch (Exception $e) {
    // Handle other potential errors
     if (file_exists($targetFilePath)) { unlink($targetFilePath); } // Clean up uploaded file
     redirect_with_message('market_manage.php', 'error', 'An unexpected error occurred: ' . $e->getMessage());
}

?>