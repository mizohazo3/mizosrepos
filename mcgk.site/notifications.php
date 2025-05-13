<?php
include 'db.php';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];

$mainDomainURL = $protocol . "://" . $host;

function formatDateTime($dateTimeStr) {
    if(empty($dateTimeStr)) {
        return ""; // Return empty string if date time is empty
    }

    // Create a DateTime object from the date string
    $dateTime = DateTime::createFromFormat('d M, Y g:i a', $dateTimeStr);
    $now = new DateTime();
    $yesterday = (new DateTime())->modify('-1 day');

    // Check if the date is today
    if ($dateTime->format('Y-m-d') === $now->format('Y-m-d')) {
        // Format as 'Today g:i A'
        return 'Today ' . $dateTime->format('g:i A');
    } 
    // Check if the date was yesterday
    else if ($dateTime->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        // Format as 'Yesterday g:i A'
        return 'Yesterday ' . $dateTime->format('g:i A');
    } 
    else {
        // Format as 'd M, Y g:i A'
        return $dateTime->format('d M, Y g:i A');
    }
}




// Fetch count of unseen notifications
$sql_count = "SELECT COUNT(*) as count FROM notifications WHERE status = 'unseen'";
$stmt_count = $connect->prepare($sql_count);
$stmt_count->execute();
$count = $stmt_count->fetchColumn();

// Mark notifications as seen
if (isset($_POST['mark_seen'])) {
    $sql_update = "UPDATE notifications SET status = 'seen' WHERE status = 'unseen'";
    $stmt_update = $connect->prepare($sql_update);
    $stmt_update->execute();
    // Redirect to the same page to update the count
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notification System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  /* Style for the notification icon container */
.notification-container {
    position: relative; /* Ensure the container is positioned relative */
    cursor: pointer;
}

/* Style for the custom image icon */
#custom-icon {
    width: 50px; /* Adjust width as needed */
    height: auto; /* Maintain aspect ratio */
}

/* Style for the notification count badge */
#notification-count {
    position: absolute; /* Position the count badge relative to the notification container */
    top: 5px; /* Adjust top position as needed */
    right: -1px; /* Adjust right position as needed */
    background-color: purple;
    color: white;
    font-size: 12px;
    border-radius: 50%; /* Make it a circle */
    padding: 6px 9px; /* Increase width and height */
    text-align: center;
    line-height: 1; /* Ensure text is centered vertically */
    transition: opacity 0.3s; /* Add a transition effect for smooth visibility */
}


/* Style for the dropdown container */
.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 300px; /* Set a minimum width to ensure content is readable */
    box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
    z-index: 1;
    top: 100%; /* Adjust top position as needed */
    left: -150%; /* Adjust left position as needed */
    transform: translateX(-50%);
}

/* Style for dropdown items */
.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

/* Style for dropdown items on hover */
.dropdown-content a:hover {
    background-color: #f1f1f1;
}

/* Show the dropdown when hovering over the notification icon container */
.notification-container:hover .dropdown-content {
    display: block;
}

/* Hide the notification count when hovering over the custom icon */
#custom-icon:hover + #notification-count {
    opacity: 0; /* Hide the count */
}

.new-notification {
    font-weight: bold; /* Make new notifications bold */
    color: blue; /* Change the color of new notifications */
}

/* Style for the notification date and time */
.date-time {
    font-size: 12px; /* Adjust the font size as needed */
    color: #888; /* Adjust the color as needed */
}
</style>
</head>
<body>

<!-- Notification Container -->
<div class="notification-container">
    <img id="custom-icon" src="<?php echo $mainDomainURL;?>/img/notif_off.png" alt="Notification Icon">
    <?php if ($count > 0): ?>
        <span id="notification-count"><?php echo $count; ?></span>
    <?php endif; ?>

    <!-- Dropdown Content -->
    <div class="dropdown-content">
        <!-- Insert your notification items here -->
        <?php
       // Fetch notifications from the database
        $sql_notifications = "SELECT * FROM notifications ORDER BY id DESC LIMIT 5";
        $stmt_notifications = $connect->prepare($sql_notifications);
        $stmt_notifications->execute();
        $notifications = $stmt_notifications->fetchAll();
        
       // Display notifications
        foreach ($notifications as $notification) {
            $isNew = $notification['status'] === 'unseen'; // Check if notification is new
            $dateTime = formatDateTime($notification['date_time']); // Format date and time
        
            // Add a CSS class for new notifications
            $cssClass = $isNew ? 'new-notification' : '';
        
            // Output the notification message with its formatted date and time
            echo '<a href="#" class="notification ' . $cssClass . '" data-notification-id="' . $notification['id'] . '">' . $notification['message'] . '<br><span class="date-time">' . $dateTime . '</span></a>';
        }


        ?>
    </div>
</div>

<script>

// Add event listener to handle hover and click events on notifications
document.querySelectorAll('.notification').forEach(notification => {
    notification.addEventListener('mouseenter', function() {
        this.classList.remove('new-notification'); // Remove 'new-notification' class on hover
    });
    notification.addEventListener('click', function() {
        this.classList.remove('new-notification'); // Remove 'new-notification' class on click
        // Send an AJAX request to mark this notification as seen in the database
        markNotificationSeen(this.dataset.notificationId);
    });
});

// Function to send AJAX request to mark notifications as seen
function markNotificationsSeen() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '<?php echo $_SERVER["PHP_SELF"]; ?>', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status == 200) {
            // Remove the notification count dynamically
            var notificationCount = document.getElementById('notification-count');
            if (notificationCount) {
                notificationCount.parentNode.removeChild(notificationCount);
            }
        }
    };
    xhr.send('mark_seen=true');
}

// Add event listener to custom icon for hovering
document.getElementById('custom-icon').addEventListener('mouseenter', function() {
    // Call the function to mark notifications as seen
    markNotificationsSeen();
});
</script>

</body>
</html>
