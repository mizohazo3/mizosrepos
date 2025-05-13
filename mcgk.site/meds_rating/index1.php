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

    // Function to calculate the Bayesian weighted average
    function calculateBayesianWeightedAverage($ratings, $counts, $scales, $C, $m) {
        $totalWeight = 0;
        $totalCount = 0;

        foreach ($ratings as $index => $rating) {
            $normalizedRating = ($rating / $scales[$index]) * 10;
            $totalWeight += $normalizedRating * $counts[$index];
            $totalCount += $counts[$index];
        }

        $R = ($totalCount > 0) ? $totalWeight / $totalCount : 0;
        return ($totalCount > 0) ? (($R * $totalCount) + ($C * $m)) / ($totalCount + $m) : 0;
    }

    // Calculate the overall average rating C and the minimum number of reviews m
    $allRatings = []; // Array to store all ratings
    $allCounts = [];  // Array to store all counts

    $stmt = $pdo->query("SELECT ratings, counts FROM drugs");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ratings = json_decode($row['ratings']);
        $counts = json_decode($row['counts']);

        foreach ($ratings as $index => $rating) {
            $normalizedRating = ($rating / $scales[$index]) * 10;
            $allRatings[] = $normalizedRating;
            $allCounts[] = $counts[$index];
        }
    }

    $C = (count($allRatings) > 0) ? array_sum($allRatings) / count($allRatings) : 0;
    $m = (count($allCounts) > 0) ? array_sum($allCounts) / count($allCounts) : 0;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $drug_name = $_POST['drug_name'];
        $ratings = $_POST['ratings'];
        $counts = $_POST['counts'];
        $scales = $_POST['scales'];

        // Calculate the Bayesian weighted average
        $bayesianWeightedAverage = calculateBayesianWeightedAverage($ratings, $counts, $scales, $C, $m);

        // Insert data into the database
        $stmt = $pdo->prepare("INSERT INTO drugs (drug_name, ratings, counts, scales, weighted_average)
                               VALUES (:drug_name, :ratings, :counts, :scales, :weighted_average)");
        $stmt->execute([
            'drug_name' => $drug_name,
            'ratings' => json_encode($ratings),
            'counts' => json_encode($counts),
            'scales' => json_encode($scales),
            'weighted_average' => round($bayesianWeightedAverage, 2)
        ]);

        // Output the result
        echo "<h3>The Bayesian weighted average rating for '$drug_name' is: " . round($bayesianWeightedAverage, 2) . "/10</h3>";
    }

    // Display all drugs and their Bayesian weighted average ratings sorted by the weighted average in descending order
    echo "<h3>All Drugs and Their Bayesian Weighted Average Ratings:</h3>";

    $stmt = $pdo->query("SELECT * FROM drugs ORDER BY weighted_average DESC");

    echo "<table><tr><th>#</th><th>Drug Name</th><th>Weighted Average Rating</th><th>Total Reviews</th></tr>";

    $counter = 1; // Initialize the counter for numbering
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totalReviews = array_sum(json_decode($row['counts'])); // Calculate the total reviews for the drug
        echo "<tr><td>" . $counter++ . "</td><td>" . htmlspecialchars($row['drug_name']) . "</td><td>" . $row['weighted_average'] . "/10</td><td>" . $totalReviews . "</td></tr>";
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