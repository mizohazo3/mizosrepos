<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Free Icon Finder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        form {
            display: flex;
            margin: 30px 0;
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
        .features {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .feature {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .feature h3 {
            color: #4285f4;
            margin-top: 0;
        }
        .feature p {
            color: #666;
            font-size: 14px;
        }
        footer {
            margin-top: 50px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Free Icon Finder</h1>
    <p class="subtitle">Search Google for free PNG icons with transparent backgrounds</p>
    
    <form action="search.php" method="GET">
        <input type="text" name="query" placeholder="Search for free icons (e.g., 'apple', 'user', 'car')..." required>
        <button type="submit">Search</button>
    </form>
    
    <div class="features">
        <div class="feature">
            <h3>Free Icons</h3>
            <p>Find free PNG icons from various sources across the web</p>
        </div>
        <div class="feature">
            <h3>Transparent</h3>
            <p>All icons come with transparent backgrounds ready to use</p>
        </div>
        <div class="feature">
            <h3>Easy Download</h3>
            <p>Download icons with a single click, no registration required</p>
        </div>
    </div>
    
    <footer>
        <p>This tool searches the web for free PNG icons. For commercial use, always check the license of each icon.</p>
    </footer>
</body>
</html> 