<?php
// Start output buffering
ob_start();

// Remove session check since it's not available
// session_start();
// date_default_timezone_set("Africa/Cairo");
// include '../checkSession.php';

// Define upload directory
$uploadsDir = '.';

// Define supported media types
$audioTypes = ['mp3', 'ogg', 'wav', 'flac', 'aac', 'wma'];
$videoTypes = ['mp4', 'webm', 'ogg', 'mkv', 'avi', 'mov', 'flv'];
$imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

// Check if user is logged in
session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'admin123') { // Set password to admin123
        $_SESSION['logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Invalid password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle file upload (from AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fileToUpload'])) {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    
    $fileTmpName = $_FILES['fileToUpload']['tmp_name'];
    $fileName = basename($_FILES['fileToUpload']['name']);
    $filePath = $uploadsDir . '/' . $fileName;

    // Function to generate a unique filename
    function generateUniqueFilename($dir, $filename) {
        $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
        $fileBaseName = pathinfo($filename, PATHINFO_FILENAME);
        $newFileName = $fileBaseName;
        $i = 1;
        while (file_exists($dir . '/' . $newFileName . '.' . $fileExt)) {
            $newFileName = $fileBaseName . "($i)";
            $i++;
        }
        return $newFileName . '.' . $fileExt;
    }

    // Generate a unique filename if the file already exists
    if (file_exists($filePath)) {
        $fileName = generateUniqueFilename($uploadsDir, $fileName);
        $filePath = $uploadsDir . '/' . $fileName;
    }

    // Check for upload errors
    if ($_FILES['fileToUpload']['error'] != 0) {
        echo json_encode(['status' => 'error', 'message' => 'Error uploading file. Error code: ' . $_FILES['fileToUpload']['error']]);
        exit;
    } else {
        // Move uploaded file to uploads directory
        if (move_uploaded_file($fileTmpName, $filePath)) {
            echo json_encode(['status' => 'success', 'message' => "File '$fileName' uploaded successfully!", 'fileName' => $fileName]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check directory permissions.']);
            exit;
        }
    }
}

// Handle file deletion (from AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteFile'])) {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    
    $fileToDelete = basename($_POST['deleteFile']);
    $filePath = $uploadsDir . '/' . $fileToDelete;

    if (is_file($filePath)) {
        if (unlink($filePath)) {
            echo json_encode(['status' => 'success', 'message' => "File '$fileToDelete' deleted successfully!"]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete file.']);
            exit;
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }
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

// Function to get badge color based on file type
function getBadgeColor($fileType) {
    switch ($fileType) {
        case 'image':
            return 'success';
        case 'audio':
            return 'info';
        case 'video':
            return 'primary';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .files-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .login-form input {
            margin-bottom: 15px;
        }
        .login-form button {
            width: 100%;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background: #f1f3f5;
        }
        .progress {
            height: 25px;
            margin: 10px 0;
        }
        .file-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.2s;
            background: white;
        }
        .file-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .media-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            cursor: pointer;
        }
        .media-preview.audio {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
            color: #6c757d;
            border: 2px dashed #dee2e6;
        }
        .file-info {
            flex-grow: 1;
            margin-bottom: 10px;
        }
        .file-name {
            font-weight: 500;
            margin-bottom: 5px;
            word-break: break-all;
        }
        .file-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
            color: #6c757d;
        }
        .file-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        .audio-player {
            width: 100%;
            margin-top: 10px;
        }
        .preview-modal .modal-dialog {
            max-width: 90vw;
        }
        .file-icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .file-type-badge {
            font-size: 0.8em;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .file-size {
            color: #6c757d;
            font-size: 0.9em;
        }
        .upload-speed {
            color: #0d6efd;
            font-weight: bold;
        }
        .preview-modal .modal-body {
            padding: 0;
            background: #000;
        }
        .preview-modal .modal-content {
            background: #000;
            border: none;
        }
        .preview-modal .modal-header {
            border-bottom: 1px solid #333;
            background: #000;
            color: white;
        }
        .preview-modal .modal-footer {
            border-top: 1px solid #333;
            background: #000;
            color: white;
        }
        .preview-modal .btn-close {
            color: white;
        }
        .preview-modal .modal-body img,
        .preview-modal .modal-body video {
            max-width: 100%;
            max-height: 80vh;
            margin: 0 auto;
            display: block;
        }
        .preview-modal .modal-body audio {
            width: 100%;
            margin: 20px auto;
            display: block;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background-color: #bb2d3b;
            transform: translateY(-1px);
        }
        .btn-delete i {
            font-size: 0.9em;
        }
        .video-preview {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            cursor: pointer;
        }
        .video-preview:hover {
            opacity: 0.9;
        }
        .video-player {
            width: 100%;
            margin-top: 10px;
        }
        .footer {
            position: relative;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #2c3237;
            background-color: #343a40;
            color: #ffffff;
        }
        .footer .container {
            background: transparent;
            box-shadow: none;
            padding: 0;
        }
        .footer p {
            color: #ffffff !important;
        }
        .footer a {
            color: #9ec5fe;
            text-decoration: none;
        }
        .footer a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            flex: 1;
        }
    </style>
</head>
<body>
<?php
if (!$isLoggedIn) {
    // Show login form
    echo '<div class="container">';
    echo '<div class="login-container">';
    echo '<h2><i class="fas fa-lock"></i> Protected Files</h2>';
    if (isset($loginError)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($loginError) . '</div>';
    }
    echo '<form method="POST" class="login-form" id="loginForm">';
    echo '<div class="mb-3">';
    echo '<label for="password" class="form-label">Password</label>';
    echo '<input type="password" class="form-control" id="password" name="password" required>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary mb-3">Login</button>';
    
    // Add fingerprint login button for mobile devices
    echo '<div id="fingerprintSection" style="display: none;">';
    echo '<div class="divider d-flex align-items-center my-4">';
    echo '<p class="text-center fw-bold mx-3 mb-0">OR</p>';
    echo '</div>';
    echo '<button type="button" class="btn btn-outline-primary w-100" id="fingerprintBtn">';
    echo '<i class="fas fa-fingerprint"></i> Login with Fingerprint';
    echo '</button>';
    echo '</div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
    
    // Add fingerprint authentication script
    echo '<script>
    // Check if fingerprint authentication is available
    if (window.PublicKeyCredential) {
        document.getElementById("fingerprintSection").style.display = "block";
        
        document.getElementById("fingerprintBtn").addEventListener("click", async function() {
            try {
                // Check if biometric authentication is available
                const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (!available) {
                    alert("Fingerprint authentication is not available on this device.");
                    return;
                }
                
                // Create credentials
                const credential = await navigator.credentials.create({
                    publicKey: {
                        challenge: new Uint8Array(32),
                        rp: {
                            name: "File Manager",
                            id: window.location.hostname
                        },
                        user: {
                            id: new Uint8Array(16),
                            name: "user@example.com",
                            displayName: "User"
                        },
                        pubKeyCredParams: [{
                            type: "public-key",
                            alg: -7
                        }],
                        authenticatorSelection: {
                            authenticatorAttachment: "platform",
                            userVerification: "required"
                        }
                    }
                });
                
                if (credential) {
                    // If fingerprint authentication is successful, submit the form
                    document.getElementById("loginForm").submit();
                }
            } catch (error) {
                console.error("Fingerprint authentication error:", error);
                alert("Fingerprint authentication failed. Please try again or use password login.");
            }
        });
    }
    </script>';
} else {
    // Show file manager
    if (is_dir($uploadsDir)) {
        echo '<div class="container">';
        // Header with File Manager and buttons
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '<h1 class="mb-0">File Manager</h1>';
        echo '<div class="d-flex align-items-center">';
        echo '<a href="https://mcgk.site" target="_blank" class="btn btn-outline-primary me-2"><i class="fas fa-globe"></i> mcgk.site</a>';
        echo '<a href="?logout" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>';
        echo '</div>';
        echo '</div>';
        
        // File upload area
        echo '<div class="upload-area" id="dropZone">';
        echo '<i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>';
        echo '<h4>Drag & Drop files here or click to upload</h4>';
        echo '<form id="uploadForm" action="" method="POST" enctype="multipart/form-data" target="uploadFrame">';
        echo '<input type="file" name="fileToUpload" id="fileToUpload" class="d-none" accept="image/*,video/*,audio/*">';
        echo '<button type="button" class="btn btn-primary" id="selectFilesBtn">Select Files</button>';
        echo '</form>';
        echo '<iframe id="uploadFrame" name="uploadFrame" style="display:none;"></iframe>';
        echo '</div>';

        // Progress Bar Container
        echo '<div id="progressContainer" style="display:none;">';
        echo '<div class="progress">';
        echo '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>';
        echo '</div>';
        echo '<div class="upload-speed" id="uploadSpeed">Speed: 0 MB/s</div>';
        echo '</div>';

        // Display message for uploaded file
        echo '<div id="uploadMessage" class="alert" style="display:none;"></div>';
        
        // Add compress.php link
        echo '<div class="mb-4">';
        echo '<a href="../compress.php" class="btn btn-secondary" target="_blank"><i class="fas fa-compress-alt"></i> Compress Files</a>';
        echo '</div>';
        
        // Add refresh button before Files heading
        echo '<div class="d-flex justify-content-between align-items-center mb-4">';
        echo '<h2 class="mb-0">Files</h2>';
        echo '<button onclick="refreshPage()" class="btn btn-outline-primary"><i class="fas fa-sync-alt"></i> Refresh</button>';
        echo '</div>';
        
        echo '<div class="files-section">';
        echo '<ul id="fileList" class="file-list">';

        // Get all files and sort them by date (newest first)
        $files = [];
        if ($dh = opendir($uploadsDir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..' && $file != 'index.php' && $file != 'view.php' && $file != 'compress.php') {
                    $filePath = $uploadsDir . '/' . $file;
                    if (is_file($filePath)) {
                        $files[$file] = [
                            'size' => filesize($filePath),
                            'date' => filemtime($filePath)
                        ];
                    }
                }
            }
            closedir($dh);
        }
        
        // Sort by date, newest first
        uasort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        // Loop through the sorted files
        foreach ($files as $file => $fileInfo) {
            $filePath = $uploadsDir . '/' . $file;
            $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fileSize = formatSizeUnits($fileInfo['size']);
            $fileDate = date('Y-m-d h:i A', $fileInfo['date']);
            
            // Determine file type and icon
            $fileType = 'file';
            $fileIcon = 'fa-file';
            if (in_array($fileExt, $imageTypes)) {
                $fileType = 'image';
                $fileIcon = 'fa-image';
            } elseif (in_array($fileExt, $audioTypes)) {
                $fileType = 'audio';
                $fileIcon = 'fa-music';
            } elseif (in_array($fileExt, $videoTypes)) {
                $fileType = 'video';
                $fileIcon = 'fa-video';
            }
            
            echo '<li class="file-item">';
            
            // Add media preview
            if (in_array($fileType, ['image', 'video', 'audio'])) {
                if ($fileType === 'image') {
                    echo '<img src="' . htmlspecialchars($filePath) . '" alt="' . htmlspecialchars($file) . '" class="media-preview" data-bs-toggle="modal" data-bs-target="#previewModal" data-media-type="image" data-media-src="' . htmlspecialchars($filePath) . '">';
                } elseif ($fileType === 'video') {
                    // Only show inline video player
                    echo '<video class="video-player" controls>';
                    echo '<source src="' . htmlspecialchars($filePath) . '" type="video/' . htmlspecialchars($fileExt) . '">';
                    echo 'Your browser does not support the video element.';
                    echo '</video>';
                } else {
                    echo '<div class="media-preview audio" data-bs-toggle="modal" data-bs-target="#previewModal" data-media-type="audio" data-media-src="' . htmlspecialchars($filePath) . '">';
                    echo '<i class="fas fa-music"></i>';
                    echo '</div>';
                }
            } else {
                echo '<div class="media-preview" style="display: flex; align-items: center; justify-content: center; background: #f8f9fa;">';
                echo '<i class="fas ' . $fileIcon . '" style="font-size: 3em; color: #6c757d;"></i>';
                echo '</div>';
            }
            
            echo '<div class="file-info">';
            echo '<div class="file-name">';
            if (in_array($fileType, ['image', 'audio', 'video'])) {
                echo '<a href="view.php?file=' . urlencode($filePath) . '" target="_blank">' . htmlspecialchars($file) . '</a>';
            } else {
                echo '<a href="' . htmlspecialchars($filePath) . '" target="_blank">' . htmlspecialchars($file) . '</a>';
            }
            echo '<span class="file-type-badge bg-' . getBadgeColor($fileType) . '">' . ucfirst($fileType) . '</span>';
            echo '</div>';
            
            echo '<div class="file-meta">';
            echo '<span class="file-size">' . $fileSize . '</span>';
            echo '<span class="file-date">' . $fileDate . '</span>';
            echo '</div>';
            
            // Add inline audio player for audio files
            if ($fileType === 'audio') {
                echo '<audio class="audio-player" controls>';
                echo '<source src="' . htmlspecialchars($filePath) . '" type="audio/' . htmlspecialchars($fileExt) . '">';
                echo 'Your browser does not support the audio element.';
                echo '</audio>';
            }
            
            echo '</div>';
            
            echo '<div class="file-actions">';
            echo '<button class="btn-delete" data-file="' . htmlspecialchars($file) . '"><i class="fas fa-trash-alt"></i> Delete</button>';
            echo '</div>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
        
        // Add preview modal
        echo '<div class="modal fade preview-modal" id="previewModal" tabindex="-1">';
        echo '<div class="modal-dialog modal-lg modal-dialog-centered">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h5 class="modal-title">Media Preview</h5>';
        echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<div id="previewContent"></div>';
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Add delete confirmation modal
        echo '<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">';
        echo '<div class="modal-dialog modal-dialog-centered">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header bg-danger text-white">';
        echo '<h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>';
        echo '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>';
        echo '</div>';
        echo '<div class="modal-body">';
        echo '<p>Are you sure you want to delete this file?</p>';
        echo '<p class="mb-0 text-muted" id="deleteFileName"></p>';
        echo '</div>';
        echo '<div class="modal-footer">';
        echo '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
        echo '<button type="button" class="btn btn-danger" id="confirmDelete"><i class="fas fa-trash-alt"></i> Delete</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    } else {
        echo '<div class="container">';
        echo '<div class="alert alert-danger">Directory does not exist. Check the folder path.</div>';
        echo '</div>';
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JavaScript for drag and drop, progress bar, speed calculation, and file management
const dropZone = document.getElementById("dropZone");
const form = document.getElementById("uploadForm");
const fileInput = document.getElementById("fileToUpload");
const progressContainer = document.getElementById("progressContainer");
const progressBar = document.querySelector(".progress-bar");
const uploadSpeed = document.getElementById("uploadSpeed");
const uploadMessage = document.getElementById("uploadMessage");
const fileList = document.getElementById("fileList");

// Drag and drop handlers
dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.style.borderColor = "#0d6efd";
    dropZone.style.backgroundColor = "#f1f3f5";
});

dropZone.addEventListener("dragleave", (e) => {
    e.preventDefault();
    dropZone.style.borderColor = "#dee2e6";
    dropZone.style.backgroundColor = "#f8f9fa";
});

dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.style.borderColor = "#dee2e6";
    dropZone.style.backgroundColor = "#f8f9fa";
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        form.dispatchEvent(new Event("submit"));
    }
});

// Clear file input on page load
window.onload = function () {
    fileInput.value = '';
};

// Add click handler for select files button
document.getElementById('selectFilesBtn').addEventListener('click', function() {
    fileInput.click();
});

// Add change handler for file input
fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        form.dispatchEvent(new Event('submit'));
    }
});

form.addEventListener("submit", function (e) {
    e.preventDefault();
    if (!fileInput.files.length) {
        uploadMessage.className = "alert alert-warning";
        uploadMessage.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Please select a file to upload.`;
        uploadMessage.style.display = "block";
        return;
    }

    const formData = new FormData();
    formData.append("fileToUpload", fileInput.files[0]);

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);

    xhr.upload.addEventListener("progress", function (e) {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.style.width = percent + "%";

            const timeElapsed = e.timeStamp / 1000;
            const speed = e.loaded / 1024 / 1024 / timeElapsed;
            uploadSpeed.innerText = `Speed: ${speed.toFixed(2)} MB/s`;
        }
    });

    xhr.upload.addEventListener("loadstart", function () {
        progressContainer.style.display = "block";
        uploadMessage.style.display = "none";
    });

    xhr.upload.addEventListener("load", function () {
        setTimeout(() => {
            progressContainer.style.display = "none";
            progressBar.style.width = "0%";
        }, 1000);
    });

    xhr.onload = function () {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                uploadMessage.className = "alert alert-success";
                uploadMessage.innerHTML = `<i class="fas fa-check-circle"></i> ${response.message}`;
                uploadMessage.style.display = "block";

                // Add the new file to the file list dynamically
                const newFileItem = document.createElement('li');
                newFileItem.className = 'file-item';
                
                // Determine file type and create appropriate preview
                const fileExt = response.fileName.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(fileExt);
                const isVideo = ['mp4', 'webm', 'ogg', 'mkv', 'avi', 'mov', 'flv'].includes(fileExt);
                const isAudio = ['mp3', 'ogg', 'wav', 'flac', 'aac', 'wma'].includes(fileExt);
                
                let previewHtml = '';
                if (isImage) {
                    previewHtml = `<img src="${response.fileName}" alt="${response.fileName}" class="media-preview" data-bs-toggle="modal" data-bs-target="#previewModal" data-media-type="image" data-media-src="${response.fileName}">`;
                } else if (isVideo) {
                    previewHtml = `
                        <video class="video-player" controls>
                            <source src="${response.fileName}" type="video/${fileExt}">
                        </video>
                    `;
                } else if (isAudio) {
                    previewHtml = `
                        <div class="media-preview audio" data-bs-toggle="modal" data-bs-target="#previewModal" data-media-type="audio" data-media-src="${response.fileName}">
                            <i class="fas fa-music"></i>
                        </div>
                        <audio class="audio-player" controls>
                            <source src="${response.fileName}" type="audio/${fileExt}">
                        </audio>
                    `;
                } else {
                    previewHtml = `
                        <div class="media-preview" style="display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                            <i class="fas fa-file" style="font-size: 3em; color: #6c757d;"></i>
                        </div>
                    `;
                }

                newFileItem.innerHTML = `
                    ${previewHtml}
                    <div class="file-info">
                        <div class="file-name">
                            <a href="view.php?file=${encodeURIComponent(response.fileName)}" target="_blank">${response.fileName}</a>
                            <span class="file-type-badge bg-${isImage ? 'success' : isVideo ? 'primary' : isAudio ? 'info' : 'secondary'}">${isImage ? 'Image' : isVideo ? 'Video' : isAudio ? 'Audio' : 'File'}</span>
                        </div>
                        <div class="file-meta">
                            <span class="file-size">Calculating...</span>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button class="btn-delete" data-file="${response.fileName}"><i class="fas fa-trash-alt"></i> Delete</button>
                    </div>
                `;
                
                fileList.insertBefore(newFileItem, fileList.firstChild);
                fileInput.value = '';
            } else {
                uploadMessage.className = "alert alert-danger";
                uploadMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${response.message}`;
                uploadMessage.style.display = "block";
            }
        } catch (error) {
            console.error('Error parsing response:', xhr.responseText);
            uploadMessage.className = "alert alert-danger";
            uploadMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i> Error processing server response.`;
            uploadMessage.style.display = "block";
        }
    };

    xhr.onerror = function() {
        uploadMessage.className = "alert alert-danger";
        uploadMessage.innerHTML = `<i class="fas fa-exclamation-circle"></i> Network error occurred.`;
        uploadMessage.style.display = "block";
    };

    xhr.send(formData);
});

// File deletion with page update
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-delete')) {
        const deleteBtn = e.target.closest('.btn-delete');
        const file = deleteBtn.dataset.file;
        const fileItem = deleteBtn.closest('.file-item');
        
        // Update modal with file name
        document.getElementById('deleteFileName').textContent = file;
        
        // Store the file item reference
        const deleteModal = document.getElementById('deleteModal');
        deleteModal.dataset.fileItem = fileItem.id;
        
        // Show modal
        const bsDeleteModal = new bootstrap.Modal(deleteModal);
        bsDeleteModal.show();
    }
});

// Handle delete confirmation
document.getElementById('confirmDelete').addEventListener('click', function() {
    const deleteModal = document.getElementById('deleteModal');
    const bsDeleteModal = bootstrap.Modal.getInstance(deleteModal);
    const file = document.getElementById('deleteFileName').textContent;
    
    const formData = new FormData();
    formData.append('deleteFile', file);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                // Hide the modal
                bsDeleteModal.hide();
                
                // Find and remove the file item
                const fileItem = document.querySelector(`[data-file="${file}"]`).closest('.file-item');
                if (fileItem) {
                    fileItem.style.opacity = '0';
                    fileItem.style.transform = 'translateY(-20px)';
                    
                    // Remove after animation
                    setTimeout(() => {
                        fileItem.remove();
                        
                        // Show success toast
                        const toast = document.createElement('div');
                        toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed';
                        toast.style.top = '20px';
                        toast.style.right = '20px';
                        toast.style.zIndex = '1050';
                        toast.setAttribute('role', 'alert');
                        toast.setAttribute('aria-live', 'assertive');
                        toast.setAttribute('aria-atomic', 'true');
                        
                        toast.innerHTML = `
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-check-circle me-2"></i>${data.message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        `;
                        
                        document.body.appendChild(toast);
                        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                        bsToast.show();
                        
                        // Remove toast after it's hidden
                        toast.addEventListener('hidden.bs.toast', () => {
                            toast.remove();
                        });
                    }, 300);
                }
            } else {
                throw new Error(data.message || 'Failed to delete file');
            }
        } catch (e) {
            console.error('Error parsing response:', text);
            throw new Error('Invalid server response');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error toast
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-danger border-0 position-fixed';
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1050';
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i>${error.message || 'Error deleting file. Please try again.'}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
        
        // Hide the modal
        bsDeleteModal.hide();
    });
});

// Refresh page function
function refreshPage() {
    window.location.href = window.location.pathname;
}

// Add preview modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const previewModal = document.getElementById('previewModal');
    const previewContent = document.getElementById('previewContent');
    
    previewModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const mediaType = button.getAttribute('data-media-type');
        const mediaSrc = button.getAttribute('data-media-src');
        
        previewContent.innerHTML = '';
        
        if (mediaType === 'image') {
            const img = document.createElement('img');
            img.src = mediaSrc;
            img.alt = 'Preview';
            previewContent.appendChild(img);
        } else if (mediaType === 'video') {
            const video = document.createElement('video');
            video.controls = true;
            video.className = 'w-100';
            const source = document.createElement('source');
            source.src = mediaSrc;
            source.type = 'video/' + mediaSrc.split('.').pop();
            video.appendChild(source);
            previewContent.appendChild(video);
        } else if (mediaType === 'audio') {
            const audio = document.createElement('audio');
            audio.controls = true;
            audio.className = 'w-100';
            const source = document.createElement('source');
            source.src = mediaSrc;
            source.type = 'audio/' + mediaSrc.split('.').pop();
            audio.appendChild(source);
            previewContent.appendChild(audio);
        }
    });
    
    previewModal.addEventListener('hidden.bs.modal', function() {
        const video = previewContent.querySelector('video');
        const audio = previewContent.querySelector('audio');
        if (video) video.pause();
        if (audio) audio.pause();
    });
});
</script>
    <footer class="footer mt-5 py-3">
        <div class="container text-center">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> File Manager - MCGK.SITE</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-0">Powered by <a href="https://cursor.sh" target="_blank">Cursor AI</a></p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>