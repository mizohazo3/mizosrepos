<?php
// Get all PNG files in the directory
$icons = glob('*.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloaded Icons</title>
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
        .icons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .icon-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            padding: 15px;
            background: white;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .icon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .icon-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .icon-name {
            font-size: 14px;
            color: #555;
            word-break: break-all;
            margin-bottom: 10px;
        }
        .no-icons {
            text-align: center;
            margin: 50px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Downloaded Icons</h1>
    
    <a href="../" class="back-link">← Back to Search</a>
    
    <?php if (empty($icons)): ?>
        <div class="no-icons">
            <p>No icons have been downloaded yet.</p>
        </div>
    <?php else: ?>
        <div class="icons-grid">
            <?php foreach ($icons as $icon): ?>
                <div class="icon-card">
                    <img class="icon-image" src="<?php echo htmlspecialchars($icon); ?>" alt="Icon">
                    <div class="icon-name"><?php echo htmlspecialchars($icon); ?></div>
                    <a href="<?php echo htmlspecialchars($icon); ?>" download>Download</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html> 