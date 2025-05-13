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
    </style>
</head>
<body>
    <div class="container form-container">
        <header style="border-bottom: none; margin-bottom: 20px; padding-bottom: 0; justify-content: center;">
            <h1>Add New Marketplace Item</h1>
        </header>

        <?php
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
                <input type="number" id="item_price" name="item_price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="item_image">Image (Required, Recommended: Square, max 1MB):</label>
                <input type="file" id="item_image" name="item_image" accept="image/png, image/jpeg, image/gif" required>
                <!-- You might want to add JavaScript preview here -->
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
</body>
</html>