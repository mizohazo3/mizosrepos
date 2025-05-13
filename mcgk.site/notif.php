
<script src="js/jquery.min.js"></script>

<style>
    .live-container {
    display: flex; /* Use flexbox layout */
    align-items: center; /* Align items vertically */
    padding-left: 250px; /* Add padding to the left side */
}

/* Optional: Adjust spacing between elements */
#LiveRefresh {
    margin-right: 10px; /* Adjust margin as needed */
}
</style>

<div class="live-container">
    <span id="LiveRefresh" style="animation: flash 4s infinite;"></span>
    <span id="LiveNotifications"></span>
</div>



<script>
  

var lastData = null; // Variable to store the last received data

function loadNotif() {
    $.ajax({
        url: 'notiftest.php',
        type: 'GET',
        success: function(data) {
            // Only update the content if the data has changed
            if (data !== lastData) {
                $('#LiveNotifications').html(data);
                lastData = data; // Update the last received data
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX request failed: ' + textStatus);
        }
    });
}

// Call the function immediately when the page loads
loadNotif();

// Then call the function every second
setInterval(loadNotif, 1000);

</script>