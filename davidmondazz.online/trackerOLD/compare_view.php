<?php
session_start(); // Start the session at the very beginning

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); // Set response type

    if ($_POST['action'] === 'save_image' && isset($_POST['slot']) && isset($_POST['imageData'])) {
        $slot = $_POST['slot'];
        // Increase upload size limits for potentially large data URLs
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '50M');
        $imageData = $_POST['imageData']; // Data URL

        // Basic validation (ensure it looks like a data URL)
        if (preg_match('/^data:image\/(png|jpeg|gif|webp);base64,/', $imageData)) {
            if ($slot == '1') {
                $_SESSION['image_slot_1'] = $imageData;
                echo json_encode(['success' => true, 'message' => 'Image 1 saved to session.']);
            } elseif ($slot == '2') {
                $_SESSION['image_slot_2'] = $imageData;
                echo json_encode(['success' => true, 'message' => 'Image 2 saved to session.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid slot specified.']);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Invalid image data format.']);
        }
        exit; // Stop script execution after handling AJAX
    }
    elseif ($_POST['action'] === 'clear_images') {
        unset($_SESSION['image_slot_1']);
        unset($_SESSION['image_slot_2']);
        echo json_encode(['success' => true, 'message' => 'Images cleared from session.']);
        exit; // Stop script execution after handling AJAX
    }
    else {
         echo json_encode(['success' => false, 'message' => 'Invalid action or missing parameters.']);
         exit;
    }
}

// --- Normal Page Load ---
date_default_timezone_set("Africa/Cairo"); // Keep timezone consistent

// Get images from session for initial load
$image1_src = isset($_SESSION['image_slot_1']) ? htmlspecialchars($_SESSION['image_slot_1']) : '';
$image2_src = isset($_SESSION['image_slot_2']) ? htmlspecialchars($_SESSION['image_slot_2']) : '';
$image1_alt = $image1_src ? 'Pasted Image 1 (from session)' : 'Paste Image 1 Here';
$image2_alt = $image2_src ? 'Pasted Image 2 (from session)' : 'Paste Image 2 Here';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Comparison & Instant Cropper</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" href="css/fontawesome.css">
    <!-- Cropper.js CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .comparison-container {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-top: 20px;
            margin-bottom: 20px;
            min-height: 300px;
            border: 1px dashed #ccc;
            padding: 10px;
        }
        .image-box {
            width: 48%;
            min-height: 250px;
            border: 1px solid #eee;
            padding: 5px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 10px;
            background-color: #f9f9f9;
            position: relative;
        }
        .image-box .img-container {
             width: 100%;
             min-height: 200px; /* Ensure space for image */
             display: flex;
             justify-content: center;
             align-items: center;
             margin-bottom: 10px;
             /* The container where Cropper will be initialized */
        }
        .image-box img {
            max-width: 100%;
            height: auto;
            display: block;
             /* Important for Cropper.js */
             opacity: 0; /* Hide initially until loaded and cropper ready */
             transition: opacity 0.3s ease-in-out;
        }
         .image-box img.loaded {
             opacity: 1;
         }
        .controls {
            text-align: center;
            margin-top: 15px;
        }
        .image-controls {
            margin-top: 10px;
            width: 100%;
            text-align: center;
        }
        .crop-actions button {
            margin: 0 5px;
        }
         /* Reusing nav styles */
        .cats{ float: right; font-size: 20px; font-weight: bold; padding: 10px; }
        .live-container { display:flex; align-items: center; vertical-align: middle; float:left; height:40px; float:right; font-size:11px; }
        #LiveRefresh { margin-right: 10px; }
        a:link{ text-decoration: none; }
		a:hover{ color: hotpink; }
        /* Cropper specific styles */
        .cropper-container { margin: 0 auto; }
        img.cropper-hidden { display: block !important; }
    </style>
</head>
<body>

<!-- Navigation -->
<div class="cats" style="border:2px solid #a9a9a9;border-radius:20px;margin:5px;">
    <div class="live-container">
        <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
        <span id="LiveNotifications"></span>
    </div>
    <a href="../index.php" class="btn btn-secondary btn-sm" style="margin:5px;">Main</a>
    <a href="categories.php" class="btn btn-light btn-sm" style="margin:5px;">Categories</a>
    <a href="../timeline" style="margin:5px;"><img src="img/timeline_icon.png"></a>
    <a href="../leave.php" class="btn btn-warning btn-sm" style="margin:5px;">Leave!</a>
    <a href="index.php" class="btn btn-primary btn-sm" style="margin:5px;">Tracker Home</a>
</div>
<div style="clear:both;"></div>

<h1>Image Comparison Tool</h1>
<p>Paste images from your clipboard (Ctrl+V or Cmd+V). Crop will start automatically.</p>

<div class="comparison-container">
    <div id="imageContainer1" class="image-box">
        <div class="img-container">
             <img id="image1" src="<?php echo $image1_src; ?>" alt="<?php echo $image1_alt; ?>" class="<?php echo $image1_src ? 'loaded' : ''; ?>">
        </div>
        <div class="image-controls">
            <!-- Crop start button removed -->
            <div class="crop-actions" data-target="1" style="display: none;">
                <button class="btn btn-success btn-sm crop-confirm-btn" data-target="1">Confirm Crop</button>
                <button class="btn btn-secondary btn-sm crop-cancel-btn" data-target="1">Cancel Crop</button>
            </div>
        </div>
    </div>
    <div id="imageContainer2" class="image-box">
         <div class="img-container">
            <img id="image2" src="<?php echo $image2_src; ?>" alt="<?php echo $image2_alt; ?>" class="<?php echo $image2_src ? 'loaded' : ''; ?>">
         </div>
         <div class="image-controls">
             <!-- Crop start button removed -->
             <div class="crop-actions" data-target="2" style="display: none;">
                <button class="btn btn-success btn-sm crop-confirm-btn" data-target="2">Confirm Crop</button>
                <button class="btn btn-secondary btn-sm crop-cancel-btn" data-target="2">Cancel Crop</button>
            </div>
        </div>
    </div>
</div>

<div class="controls">
    <button id="clearButton" class="btn btn-danger">Clear Images</button>
</div>

<script src="js/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
    $(document).ready(function() {
        let cropperInstances = { 1: null, 2: null }; // Store cropper instances

        function getCropperInstance(slot) {
            return cropperInstances[slot];
        }

        function setCropperInstance(slot, instance) {
            cropperInstances[slot] = instance;
        }

        function destroyCropper(slot) {
            let cropper = getCropperInstance(slot);
            if (cropper) {
                console.log("Destroying cropper for slot " + slot);
                try {
                    cropper.destroy();
                } catch (e) {
                    console.warn("Error destroying cropper (might already be destroyed):", e);
                }
                setCropperInstance(slot, null);
                $(`.crop-actions[data-target='${slot}']`).hide();
                $('#image' + slot).removeClass('cropper-hidden'); // Ensure image is visible
            }
        }

        // Function to initialize cropper on an image element
        function initializeCropper(slot) {
            // destroyCropper(slot); // Caller handles destruction

            const imageElement = document.getElementById('image' + slot);
            if (!imageElement || !imageElement.src || imageElement.src === window.location.href || imageElement.src.startsWith('http')) {
                console.log("Cannot initialize cropper: No valid image source for slot " + slot);
                return; // Don't init if no valid src
            }

            console.log("Initializing cropper for slot " + slot);
            // Ensure image is loaded before initializing cropper
            // Ensure image is visible before attempting init
            $(imageElement).addClass('loaded');

            // Use setTimeout to allow the image source to potentially update in the DOM
            setTimeout(() => {
                if (!imageElement.src || imageElement.src === window.location.href || imageElement.src.startsWith('http')) {
                     console.log("Skipping cropper init for slot " + slot + " due to invalid src after timeout.");
                     $(`.crop-actions[data-target='${slot}']`).hide(); // Ensure controls hidden
                     return;
                }
                try {
                    console.log("Attempting Cropper init inside timeout for slot " + slot);
                    const cropper = new Cropper(imageElement, {
                        aspectRatio: NaN,
                        viewMode: 1,
                        autoCropArea: 0.85,
                        movable: true,
                        zoomable: true,
                        rotatable: false,
                        scalable: false,
                        ready: function () {
                            console.log("Cropper ready for slot " + slot);
                            // Ensure controls are shown ONLY when ready and ONLY for this slot
                            $(`.crop-actions[data-target!='${slot}']`).hide(); // Hide other slot's controls
                            $(`.crop-actions[data-target='${slot}']`).show();
                        },
                        crop(event) {
                            // Optional logging
                        },
                    });
                    setCropperInstance(slot, cropper);
                } catch (e) {
                    console.error("Error initializing Cropper for slot " + slot + ":", e);
                    $(`.crop-actions[data-target='${slot}']`).hide(); // Hide controls on error
                    $(imageElement).addClass('loaded'); // Still show image if possible
                }
            }, 100); // Increased delay slightly

            // Remove separate onload/onerror handlers as init is attempted directly
            imageElement.onload = null;
            imageElement.onerror = null; // Let the try/catch handle init errors
        }

        // Function to save image data (now only called on confirm crop)
        function saveCroppedImageToServer(slot, imageData) {
            $.ajax({
                url: 'compare_view.php',
                type: 'POST',
                data: {
                    action: 'save_image',
                    slot: slot,
                    imageData: imageData // Send cropped data
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Server response (save cropped):', response);
                    if (!response.success) {
                        alert('Error saving cropped image to session: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error saving cropped image via AJAX:", status, error, xhr.responseText);
                    alert('An error occurred while trying to save the cropped image to the session.');
                }
            });
        }

        // --- CROP ACTIONS ---
        $('.crop-confirm-btn').on('click', function() {
            const targetSlot = $(this).data('target');
            let cropper = getCropperInstance(targetSlot);

            if (cropper) {
                console.log("Confirming crop for slot " + targetSlot);
                try {
                    const croppedCanvas = cropper.getCroppedCanvas();
                    if (!croppedCanvas) {
                        alert("Could not get cropped canvas.");
                        destroyCropper(targetSlot); // Clean up
                        return;
                    }
                    const croppedImageData = croppedCanvas.toDataURL('image/png');

                    // 1. Update image instantly on page
                    const imgElement = $('#image' + targetSlot);
                    imgElement.attr('src', croppedImageData); // Update with cropped data
                    imgElement.data('original-src', croppedImageData); // Update original src reference

                    // 2. Destroy cropper
                    destroyCropper(targetSlot);

                    // 3. Save cropped image to server (background)
                    saveCroppedImageToServer(targetSlot, croppedImageData);

                } catch (e) {
                    console.error("Error during crop confirm:", e);
                    alert("An error occurred while confirming the crop.");
                    destroyCropper(targetSlot); // Clean up on error
                }
            } else {
                 console.error("No cropper instance found for slot " + targetSlot + " on confirm.");
            }
        });

        $('.crop-cancel-btn').on('click', function() {
            const targetSlot = $(this).data('target');
            console.log("Cancelling crop for slot " + targetSlot);
            // Just destroy the cropper, leave the image as it was when cropper started
            destroyCropper(targetSlot);
        });


        // --- PASTE LOGIC ---
        document.addEventListener('paste', function (event) {
            console.log("Paste event detected");
            const items = (event.clipboardData || event.originalEvent.clipboardData).items;
            let foundImage = false;

            // Determine target slot *before* reading data
            const img1 = $('#image1');
            const img2 = $('#image2');
            let targetSlot = null;

            // Check which slot is visually empty or has no cropper
            // Determine target slot
            const isSlot1Empty = (!img1.attr('src') || img1.attr('src') === '' || img1.attr('src') === window.location.href);
            const isSlot2Empty = (!img2.attr('src') || img2.attr('src') === '' || img2.attr('src') === window.location.href);

            if (isSlot1Empty) {
                targetSlot = 1;
            } else if (isSlot2Empty) {
                targetSlot = 2;
            } else {
                 // Both slots have images. Replace slot 1 by default.
                 targetSlot = 1;
                 console.log("Both slots have images, targeting slot 1 for replacement.");
            }
             console.log("Targeting slot: " + targetSlot);

             // Explicitly destroy ONLY the target slot's cropper *before* setting src
             console.log("Destroying any existing cropper specifically for slot " + targetSlot + " before paste.");
             destroyCropper(targetSlot);


            for (let index in items) {
                const item = items[index];
                if (item.kind === 'file' && item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    const reader = new FileReader();

                    reader.onload = function(readerEvent) {
                        console.log("Image read successfully for slot " + targetSlot);
                        const dataUrl = readerEvent.target.result;
                        const imageElement = $('#image' + targetSlot);

                        // 1. Update image source immediately
                        imageElement.attr('src', dataUrl);
                        imageElement.data('original-src', dataUrl); // Store original
                        // initializeCropper will handle adding 'loaded' class

                        // 2. Initialize Cropper (will handle showing actions)
                        initializeCropper(targetSlot);

                        // 3. DO NOT save original to server here. Save happens on confirm crop.
                    };

                    reader.onerror = function(error) {
                        console.error("Error reading file:", error);
                    };

                    reader.readAsDataURL(blob);
                    foundImage = true;
                    break; // Handle only the first image found
                }
            }

            if (foundImage) {
                event.preventDefault(); // Prevent default paste
            }
        });

        // --- CLEAR BUTTON LOGIC ---
        $('#clearButton').on('click', function() {
            console.log("Clear button clicked");
            // Destroy croppers first
            destroyCropper(1);
            destroyCropper(2);

             $.ajax({
                url: 'compare_view.php',
                type: 'POST',
                data: {
                    action: 'clear_images'
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Server response (clear):', response);
                    if (response.success) {
                        $('#image1').attr('src', '').attr('alt', 'Paste Image 1 Here').removeClass('loaded').removeData('original-src');
                        $('#image2').attr('src', '').attr('alt', 'Paste Image 2 Here').removeClass('loaded').removeData('original-src');
                    } else {
                         alert('Error clearing images from session: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error clearing images via AJAX:", status, error, xhr.responseText);
                    alert('An error occurred while trying to clear the images from the session.');
                }
            });
        });

        // Initialize cropper for images loaded from session
        if ($('#image1').attr('src')) {
            // Don't auto-init cropper on load, wait for paste or explicit action if we add one later
             $('#image1').addClass('loaded'); // Make sure it's visible
        }
         if ($('#image2').attr('src')) {
             $('#image2').addClass('loaded'); // Make sure it's visible
        }

    });
</script>

</body>
</html>