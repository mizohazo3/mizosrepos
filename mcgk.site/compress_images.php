<?php

// Ensure immediate output
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 'off');
header('Content-Type: text/plain');
ob_implicit_flush(true);
flush();

// File to track progress
$progressFile = __DIR__ . "/progress.txt";
file_put_contents($progressFile, "0");

// Function to compress images
function compressAndResizeImage($source, $destination, $targetSizeKB) {
    if (filesize($source) / 1024 <= $targetSizeKB) {
        return false; // Skip if already optimized
    }

    $quality = 90;
    $minQuality = 10;
    $step = 5;

    $imageInfo = getimagesize($source);
    $mime = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];

    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        default:
            return false;
    }

    do {
        if ($mime == 'image/jpeg') {
            imagejpeg($image, $destination, $quality);
        } elseif ($mime == 'image/png') {
            $compressionLevel = (int)((100 - $quality) / 10);
            imagepng($image, $destination, $compressionLevel);
        }

        $currentSizeKB = filesize($destination) / 1024;
        $quality -= $step;

    } while ($currentSizeKB > $targetSizeKB && $quality >= $minQuality);

    if ($currentSizeKB > $targetSizeKB) {
        $newWidth = $width * 0.8;
        $newHeight = $height * 0.8;

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if ($mime == 'image/jpeg') {
            imagejpeg($resizedImage, $destination, 80);
        } elseif ($mime == 'image/png') {
            imagepng($resizedImage, $destination, 7);
        }

        imagedestroy($resizedImage);
    }

    imagedestroy($image);
    return true;
}

// Function to process images
function optimizeImagesInFolder($folder, $targetSizeKB = 1500) {
    global $progressFile;

    if (!is_dir($folder)) {
        die("Folder does not exist.");
    }

    $files = array_filter(scandir($folder), function($file) use ($folder) {
        return is_file("$folder/$file") && preg_match('/\.(jpg|jpeg|png)$/i', $file);
    });

    $totalFiles = count($files);
    if ($totalFiles === 0) {
        die("No images found in folder.");
    }

    $currentFile = 0;

    foreach ($files as $file) {
        $filePath = $folder . DIRECTORY_SEPARATOR . $file;
        compressAndResizeImage($filePath, $filePath, $targetSizeKB);

        $currentFile++;
        $progress = round(($currentFile / $totalFiles) * 100);

        file_put_contents($progressFile, $progress); // Save progress to file

        flush();
        ob_flush();
        sleep(1); // Simulate time taken per file
    }

    file_put_contents($progressFile, "100"); // Ensure final progress is 100%
}

// Define folder path
$uploadFolder = __DIR__ . "/uploads";
optimizeImagesInFolder($uploadFolder, 1500);

?>
