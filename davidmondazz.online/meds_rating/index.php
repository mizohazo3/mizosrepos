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
        th:nth-child(1), td:nth-child(1) {
            width: 3%; /* Adjust this percentage as needed */
        }
    </style>
</head>
<body>
    <h2><a href="index.php">Dynamic Weighted Average Rating Calculator</a></h2>
    
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
    $dbname = 'mcgkxyz_meds_rating';
    $username = 'mcgkxyz_masterpop';
    $password = 'aA0109587045';
    
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

        echo "<h3>The weighted average rating for '$drug_name' is: " . round($weightedAverage, 2) . "/10</h3>";
    }

    // Calculate global average and minimum reviews
    $stmt = $pdo->query("SELECT weighted_average, counts FROM drugs");
    $allWeightedAverages = [];
    $totalReviews = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $allWeightedAverages[] = $row['weighted_average'];
        $totalReviews[] = array_sum(json_decode($row['counts'], true));
    }
    $C = (count($allWeightedAverages) > 0) ? array_sum($allWeightedAverages) / count($allWeightedAverages) : 0; // Global average
    $M = (count($totalReviews) > 0) ? array_sum($totalReviews) / count($totalReviews) : 1; // Minimum reviews threshold (average count)

    // Fetch and display drugs with adjusted averages
    $stmt = $pdo->query("SELECT * FROM drugs");
    $drugs = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $R = $row['weighted_average'];
        $V = array_sum(json_decode($row['counts'], true)); // Total reviews for this drug
        $adjustedAverage = ($V > 0) ? (($R * $V) + ($C * $M)) / ($V + $M) : $C; // Bayesian formula
        $row['adjusted_average'] = round($adjustedAverage, 2);
        $row['total_reviews'] = $V;
        $drugs[] = $row;
    }

    // Sort drugs by adjusted average
    usort($drugs, function($a, $b) {
        return $b['adjusted_average'] <=> $a['adjusted_average'];
    });

    // Display the sorted drugs in a table
    echo "<h3>All Drugs and Their Bayesian Adjusted Ratings:</h3>";
    echo "<table><tr><th>#</th><th>Drug Name</th><th>Weighted Average</th><th>Adjusted Average</th><th>Total Reviews</th></tr>";

    $counter = 1;
    foreach ($drugs as $drug) {
        echo "<tr>
            <td>{$counter}</td>
            <td>" . htmlspecialchars($drug['drug_name']) . "</td>
            <td>{$drug['weighted_average']}/10</td>
            <td>{$drug['adjusted_average']}/10</td>
            <td>{$drug['total_reviews']}</td>
        </tr>";
        $counter++;
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
