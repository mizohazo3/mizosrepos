$(document).ready(function() {
    // Function to update med-details
    function updateMedDetails() {
        // Add timestamp parameter to prevent caching
        var timestamp = new Date().getTime();
        
        $.ajax({
            url: 'fetch_med_details.php',
            type: 'GET',
            data: { t: timestamp }, // Add timestamp to prevent browser caching
            cache: false, // Disable AJAX caching
            success: function(data) {
                // Prepend the new content to med-details instead of replacing it
                $('#med-details').prepend(data);
            },
            error: function() {
                alert('Error fetching updated details.');
            }
        });
    }
    
    // Function to start countdown
    function startCountdown(buttonId, seconds, originalText) {
        var $button = $("#" + buttonId);
        
        // Store the end time in localStorage (current time + seconds in milliseconds)
        var endTime = Date.now() + (seconds * 1000);
        localStorage.setItem('endTime_' + buttonId, endTime);
        
        var countdownInterval = setInterval(function() {
            var remainingTime = Math.round((endTime - Date.now()) / 1000);
            
            if (remainingTime <= 0) {
                clearInterval(countdownInterval);
                $button.text(originalText);
                $button.prop('disabled', false);
                localStorage.removeItem('endTime_' + buttonId);
            } else {
                $button.text(remainingTime + 's');
            }
        }, 1000);
    }

    // Ensure only initially enabled takeButton buttons remain enabled on page load
    $("button[id^='takeButton']").each(function() {
        var $button = $(this);

        // Only re-enable the button if it was not initially disabled
        if (!$button.prop('disabled')) {
            $button.prop('disabled', false);
        }
    });
});
