<?php
include 'db.php'; // Database connection

if (isset($_GET['q'])) {
    $query = $_GET['q'];
    $keywords = explode(' ', $query); // Split the search query into individual keywords

    // Construct the base SQL query
    $sql = "SELECT id, note_title, note_content, sticky, update_date, date_added 
            FROM list WHERE ";

    // Create an array to hold individual conditions for the keywords
    $conditions = [];

    // Create an array to hold the corresponding parameters for each condition
    $params = [];

    // Loop through each keyword and create the LIKE condition
    foreach ($keywords as $keyword) {
        $conditions[] = "(note_title LIKE ? OR note_content LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%"; // Match keyword in both title and content
    }

    // Combine the conditions with AND to ensure all keywords are matched
    $sql .= implode(' AND ', $conditions);

    try {
        // Prepare and execute the query with the dynamic conditions
        $stmt = $con->prepare($sql);
        $stmt->execute($params);

        // Check if there are results
        if ($stmt->rowCount() > 0) {
            // Loop through the results and generate the HTML
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Highlight the search term in note_title and note_content
                $highlightedTitle = preg_replace("/(" . implode('|', $keywords) . ")/i", '<span class="highlight">$1</span>', $row['note_title']);
                $highlightedContent = preg_replace("/(" . implode('|', $keywords) . ")/i", '<span class="highlight">$1</span>', $row['note_content']);

                // Apply nl2br to convert newlines to <br> tags
                $highlightedContent = nl2br($highlightedContent);

                // For sticky status
                if ($row['sticky'] == 1) {
                    $stickStatus = '<button onclick="changeSticky(' . $row['id'] . ', 0)" class="image-button"><img src="img/unpinned.png" style="text-decoration: none;"></button>';
                    echo "<blockquote class='text-center'>
                            <h5 class='bg-warning p-2'>" . $formStart . '<b style="color:white;"><a href="index.php?showID='.$row['id'].'">' . $highlightedTitle . '</a></b>' . $refreshICON . " <p class='icons'>
                            <a href='index.php?showID=" . $row['id'] . "&editID=" . $row['id'] . "' style='text-decoration: none;'>
                            <img src='img/edit.png' style='opacity: 0.5;'></a>$stickStatus$deleteICON</p></h5>
                            <p>" . $highlightedContent . "</p>$submitButton $editMsg $formEnd
                            <div id='spacer' style='width: 200px; height: 70px; margin-right:0px;'></div>
                            <p class='date_added_Show'>
                            <font color='green'>Last Updated: " . $row['update_date'] . "</font><br>
                            <font color='red'>Added: " . $row['date_added'] . "</font></p>
                            </blockquote>";
                } else {
                    $stickStatus = '<button onclick="changeSticky(' . $row['id'] . ', 1)" class="image-button"><img src="img/sticky.png" style="text-decoration: none;"></button>';
                    echo "<blockquote class='text-center'>
                            <h5 class='bg-light p-2'>" . $formStart . '<a href="index.php?showID='.$row['id'].'">'.$highlightedTitle.'</a>'. $refreshICON . " <p class='icons'>
                            <a href='index.php?showID=" . $row['id'] . "&editID=" . $row['id'] . "' style='text-decoration: none;'>
                            <img src='img/edit.png' style='opacity: 0.5;'></a>$stickStatus$deleteICON</p></h5>
                            <p>" . $highlightedContent . "</p>$submitButton $editMsg $formEnd
                            <div id='spacer' style='width: 200px; height: 70px; margin-right:0px;'></div>
                            <p class='date_added_Show'>
                            <font color='green'>Last Updated: " . $row['update_date'] . "</font><br>
                            <font color='red'>Added: " . $row['date_added'] . "</font></p>
                            </blockquote>";
                }
            }
        } else {
            echo '<p>No results found for "' . htmlspecialchars($query) . '"</p>';
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
?>

<style>
    .highlight {
        background-color: yellow; /* You can customize this color */
    }
</style>
