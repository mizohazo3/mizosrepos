<?php
$servername = "localhost";
$username = "mcgkxyz_masterpop";
$password = "aA0109587045";
$dbname = "mcgkxyz_link_tracker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchQuery = isset($_POST['search']) ? $_POST['search'] : '';

// Split the search query into separate words
$keywords = explode(' ', $searchQuery);

// Build the WHERE clause dynamically for each keyword
$whereClauses = [];
foreach ($keywords as $keyword) {
    $whereClauses[] = "name LIKE ?";
}

// Join the WHERE clauses with AND (e.g., 'name LIKE ? AND name LIKE ?')
$whereSql = implode(' AND ', $whereClauses);

// Prepare the SQL statement
$stmt = $conn->prepare("SELECT * FROM links WHERE $whereSql ORDER BY clicks DESC");

// Bind parameters dynamically for each keyword
$types = str_repeat('s', count($keywords)); // Create a string of 's' for binding string parameters
$params = array_merge([$types], array_map(function ($keyword) {
    return "%$keyword%"; // Match any part of the word
}, $keywords));

// Bind parameters
call_user_func_array([$stmt, 'bind_param'], refValues($params));

// Execute and get the results
$stmt->execute();
$result = $stmt->get_result();

$output = '';
while ($row = $result->fetch_assoc()) {
    $output .= '<li data-id="' . $row['id'] . '">
                    <a href="' . $row['link'] . '" class="trackable-link" target="_blank">' . htmlspecialchars($row['name']) . '</a>
                    (<span class="click-count">' . $row['clicks'] . '</span> clicks)
                </li>';
}

echo $output;

$conn->close();

// Function to pass parameters by reference for bind_param
function refValues($arr) {
    $refArray = [];
    foreach ($arr as $key => $value) {
        $refArray[$key] = &$arr[$key];
    }
    return $refArray;
}
?>
