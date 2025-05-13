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

// Handle form submission to add a new link
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['link'])) {
    $link = $_POST['link'];

    // Check if the link already exists in the database
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM links WHERE link = ?");
    $checkStmt->bind_param("s", $link);
    $checkStmt->execute();
    $checkStmt->bind_result($linkCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($linkCount > 0) {
        echo "<script>alert('This link has already been added.');</script>";
    } else {
        // Extract the name from the URL after "item/"
        preg_match("/item\/([^\/]+)/", $link, $matches);
        $name = ucwords(str_replace('-', ' ', $matches[1]));  // Capitalize and replace hyphens with spaces

        $stmt = $conn->prepare("INSERT INTO links (name, link) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $link);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all links from the database
$result = $conn->query("SELECT * FROM links ORDER BY clicks DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>OSRS GE Prices Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        #link-list li {
            margin-bottom: 20px; /* Vertical space between links */
        }
        .trackable-link {
            font-size: 18px; /* Larger font for links */
            text-decoration: none; /* Remove underline by default */
        }
        .trackable-link:hover {
            text-decoration: underline; /* Underline on hover */
            color: #FF5733; /* Change color on hover */
        }
        .click-count {
            font-size: 14px; /* Keep click count the same size */
            color: #555;
        }
        #link {
            width: 30%; /* Make the link input box wider */
            height: 40px;
            font-size: 20px;
        }
        #search {
            width: 15%; /* Make the search input box wider */
            height: 40px;
            font-size: 20px;
        }
        #clear-search {
            height: 45px;
            font-size: 20px;
        }
        #search-results {
            padding-top: 30px;
            margin-top: 20px; /* Space between results and search box */
            margin-bottom: 20px; /* Space between results and search items textbox */
        }
        @media only screen and (max-width: 768px) {
              #search {
            width: 30%; /* Make the search input box wider */
            height: 40px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <h1><a href="index.php">OSRS GE Prices Tracker</a></h1>
    
    <form method="post" action="">
        <label for="link">Link URL:</label>
        <input type="url" id="link" name="link" required>
        <button type="submit" style="height: 45px;">Add Link</button>
    </form>

    <div id="search-results"></div> <!-- Added div for search results -->

    <form id="search-form">
        <label for="search">Search Items:</label>
        <input type="text" id="search" name="search" autocomplete="off" placeholder="Search for links...">
        <button type="button" id="clear-search">Clear</button>
    </form>

    <h2>Most Clicked Links</h2>
    <ul id="link-list">
        <?php while ($row = $result->fetch_assoc()): ?>
            <li data-id="<?= $row['id'] ?>">
                <a href="<?= $row['link'] ?>" class="trackable-link">
                    <?= htmlspecialchars($row['name']) ?>
                </a> 
                (<span class="click-count"><?= $row['clicks'] ?></span> clicks)
            </li>
        <?php endwhile; ?>
    </ul>

    <script>
    $(document).ready(function() {
        // Track click event
        $('.trackable-link').on('click', function(event) {
            event.preventDefault();
            var link = $(this);
            var linkId = link.closest('li').data('id');
            var linkUrl = link.attr('href');

            $.ajax({
                url: 'record_click.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id: linkId }),
                success: function(response) {
                    var clickCount = link.siblings('.click-count');
                    clickCount.text(parseInt(clickCount.text()) + 1);
                    window.open(linkUrl, '');
                }
            });
        });

        // Search functionality
        $('#search').on('keyup', function() {
            var searchQuery = $(this).val();
            
            $.ajax({
                url: 'search_links.php',
                type: 'POST',
                data: { search: searchQuery },
                success: function(response) {
                    $('#search-results').html(response); // Update search results
                    $('html, body').animate({
                        scrollTop: $("#search-results").offset().top
                    }, 200); // Smooth scroll to search results
                }
            });
        });

        // Clear search functionality
        $('#clear-search').on('click', function() {
            $('#search').val('');  // Clear the search input field
            fetchAllLinks();  // Fetch and display all links
        });

        // Function to fetch all links
        function fetchAllLinks() {
            $.ajax({
                url: 'search_links.php',  // Fetch all links without search
                type: 'POST',
                data: { search: '' },  // Empty search query to get all results
                success: function(response) {
                    $('#search-results').html('');  // Clear search results
                    $('#link-list').html(response);  // Update the link list with all results
                }
            });
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>
