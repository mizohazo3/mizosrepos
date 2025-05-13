<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Weighted Average Rating Calculator</title>
    <style>
        .rating-entry {
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h2>Dynamic Weighted Average Rating Calculator</h2>
    
    <!-- Form to add drug ratings -->
    <form id="ratingForm" method="post">
        <label>Drug Name: </label>
        <input type="text" name="drug_name" required><br><br>

        <div id="ratingEntries">
            <div class="rating-entry">
                <label>Rating: </label><input type="number" name="ratings[]" step="0.1" min="0" max="10" required>
                <label>Scale (e.g. 5, 10): </label><input type="number" name="scales[]" min="1" required>
                <label>Total Reviews: </label><input type="number" name="counts[]" min="0" required>
                
            </div>
        </div>

        <button type="button" onclick="addRatingEntry()">Add New Rating</button><br><br>
        <input type="submit" value="Calculate Weighted Average">
    </form>

    <?php
    // Database connection (change your DB credentials here)
    $host = 'localhost';
    $dbname = 'meds_rating';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }

    // Function to calculate the weighted average
    function calculateWeightedAverage($ratings, $counts, $scales) {
        $totalWeight = 0;
        $totalCount = 0;
        
        foreach ($ratings as $index => $rating) {
            $normalizedRating = ($rating / $scales[$index]) * 10;
            $totalWeight += $normalizedRating * $counts[$index];
            $totalCount += $counts[$index];
        }
        
        return ($totalCount > 0) ? $totalWeight / $totalCount : 0;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $drug_name = $_POST['drug_name'];
        $ratings = $_POST['ratings'];
        $counts = $_POST['counts'];
        $scales = $_POST['scales'];

        // Calculate the weighted average
        $weightedAverage = calculateWeightedAverage($ratings, $counts, $scales);

        // Insert data into the database
        $stmt = $pdo->prepare("INSERT INTO drugs (drug_name, ratings, counts, scales, weighted_average) 
                               VALUES (:drug_name, :ratings, :counts, :scales, :weighted_average)");
        $stmt->execute([
            'drug_name' => $drug_name,
            'ratings' => json_encode($ratings),
            'counts' => json_encode($counts),
            'scales' => json_encode($scales),
            'weighted_average' => round($weightedAverage, 2)
        ]);

        // Output the result
        echo "<h3>The weighted average rating for '$drug_name' is: " . round($weightedAverage, 2) . "/10</h3>";
    }

    // Display all drugs and their weighted average ratings
    echo "<h3>All Drugs and Their Weighted Average Ratings:</h3>";

    $stmt = $pdo->query("SELECT * FROM drugs");
    echo "<table><tr><th>Drug Name</th><th>Weighted Average Rating</th></tr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>" . htmlspecialchars($row['drug_name']) . "</td><td>" . $row['weighted_average'] . "/10</td></tr>";
    }

    echo "</table>";
    ?>

    <script>
        function addRatingEntry() {
            const container = document.getElementById('ratingEntries');
            const newEntry = document.createElement('div');
            newEntry.classList.add('rating-entry');
            newEntry.innerHTML = `
                <label>Rating: </label><input type="number" name="ratings[]" step="0.1" min="0" max="10" required>
                <label>Scale (e.g. 5, 10): </label><input type="number" name="scales[]" min="1" required>
                <label>Total Reviews: </label><input type="number" name="counts[]" min="0" required>
                
            `;
            container.appendChild(newEntry);
        }
    </script>
</body>
</html>
