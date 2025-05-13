<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Marketplace Item - Timer System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=JetBrains+Mono:wght@400;500;700&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Form Styling */
        .container.form-container {
            max-width: 600px; /* Narrower container for form */
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px 15px;
            background-color: var(--bg-light);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
         .form-group input:focus,
         .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(97, 218, 251, 0.2);
        }
         .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
         .form-group input[type="file"] {
            padding: 8px 10px; /* Slightly different padding for file input */
         }
        .submit-button {
            background-color: var(--accent-secondary);
            color: var(--bg-dark);
            border: none;
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .submit-button:hover {
             background-color: #4caf50;
        }
         .status-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
         }
        .status-message.success {
            background-color: rgba(94, 236, 179, 0.2); /* accent-secondary */
            border: 1px solid var(--accent-secondary);
            color: var(--accent-secondary);
        }
        .status-message.error {
            background-color: rgba(255, 107, 107, 0.2); /* accent-error */
             border: 1px solid var(--accent-error);
             color: var(--accent-error);
        }
        .form-links {
            margin-top: 25px;
            text-align: center;
        }
        .form-links a {
            color: var(--accent-primary);
            text-decoration: none;
            margin: 0 10px;
        }
         .form-links a:hover {
            text-decoration: underline;
        }
        .fetch-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        #image_preview {
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            border: 1px dashed var(--border-color);
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.2);
        }
        #preview_img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        .fetch-image-button {
            background-color: #4285f4; /* Google blue */
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 1em;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            display: block;
            width: 100%;
            margin-top: 8px;
            transition: background-color 0.2s ease;
        }
        .fetch-image-button:hover {
            background-color: #357ae8;
        }
        .fetch-image-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container form-container">
        <header style="border-bottom: none; margin-bottom: 20px; padding-bottom: 0; justify-content: center;">
            <h1>Add New Marketplace Item</h1>
        </header>

        <?php
        
        include_once 'timezone_config.php';
        // Display feedback message if redirected with status
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $message = htmlspecialchars($_GET['message'] ?? ''); // Get message safely
            if ($status === 'success') {
                echo "<div class='status-message success'>Success! Item added successfully. ($message)</div>";
            } elseif ($status === 'error') {
                echo "<div class='status-message error'>Error: Failed to add item. ($message)</div>";
            }
        }
        ?>

        <form action="api/add_marketplace_item.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="item_name">Item Name:</label>
                <input type="text" id="item_name" name="item_name" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="item_description">Description:</label>
                <textarea id="item_description" name="item_description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label for="item_price">Price ($):</label>
                <input type="number" id="item_price" name="item_price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="item_image">Image (Required, Recommended: Square, max 1MB):</label>
                <input type="file" id="item_image" name="item_image" accept="image/png, image/jpeg, image/gif">
                <button type="button" id="fetch_image" class="fetch-image-button">Fetch Icon for Item</button>
                <div id="image_preview" style="display: none;">
                    <img id="preview_img">
                    <input type="hidden" id="base64_image" name="base64_image">
                </div>
            </div>

            <!-- Optional: Add stock input -->
            <!--
            <div class="form-group">
                <label for="stock">Stock (Enter -1 for infinite):</label>
                <input type="number" id="stock" name="stock" value="-1" required>
            </div>
             -->

            <button type="submit" class="submit-button">Add Item to Marketplace</button>
        </form>

        <div class="form-links">
            <a href="marketplace.php">View Marketplace</a> |
            <a href="index.php">Back to Timers</a>
        </div>
    </div>

    <script>
        // Update button text when item name changes
        document.getElementById('item_name').addEventListener('input', function() {
            const fetchButton = document.getElementById('fetch_image');
            if (this.value) {
                fetchButton.textContent = `Fetch Icon for "${this.value}"`;
            } else {
                fetchButton.textContent = 'Fetch Icon for Item';
            }
        });
        
        document.getElementById('fetch_image').addEventListener('click', function() {
            const itemName = document.getElementById('item_name').value;
            
            if (!itemName) {
                alert('Please enter an item name first');
                return;
            }
            
            // Show loading state
            this.textContent = 'Fetching Icon...';
            this.disabled = true;
            
            // Generate a random number to ensure we get different images each time
            const randomSeed = Math.floor(Math.random() * 10000);
            
            // Make AJAX request to fetch image
            fetch(`api/fetch_image.php?query=${encodeURIComponent(itemName)}&rand=${randomSeed}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Display the image
                        document.getElementById('preview_img').src = data.image;
                        document.getElementById('base64_image').value = data.image;
                        document.getElementById('image_preview').style.display = 'block';
                        
                        // Mark file input as not required since we have a base64 image
                        document.getElementById('item_image').removeAttribute('required');
                    } else {
                        alert('Failed to fetch icon: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                })
                .finally(() => {
                    // Reset button
                    const itemName = document.getElementById('item_name').value;
                    if (itemName) {
                        this.textContent = `Fetch Icon for "${itemName}"`;
                    } else {
                        this.textContent = 'Fetch Icon for Item';
                    }
                    this.disabled = false;
                });
        });
        
        // Trigger the input event to update button text on page load
        window.addEventListener('DOMContentLoaded', function() {
            const event = new Event('input');
            document.getElementById('item_name').dispatchEvent(event);
        });
        
        // Handle file input change to show preview for manually uploaded files too
        document.getElementById('item_image').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('preview_img').src = e.target.result;
                    document.getElementById('base64_image').value = '';
                    document.getElementById('image_preview').style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>

    <?php
    // Simple redirect to the new marketplace management page
    header("Location: market_manage.php");
    exit;
    ?>
</body>
</html>