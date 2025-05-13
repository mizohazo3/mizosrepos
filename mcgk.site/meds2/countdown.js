$(document).ready(function() {
    // Function to handle button click
    function handleButtonClick($button) {
        var name = $button.parents("span").attr("name");
        var buttonId = $button.attr('id'); // Use the button's ID as the unique identifier

        // Disable the button
        $button.prop('disabled', true);

        // Make the AJAX request to submit the form
        $.ajax({
            url: 'submit.php',
            type: 'GET',
            data: { name: name },
            error: function() {
                alert('Something is wrong');
            },
            success: function() {
                // Trigger the update of the med-details separately
                updateMedDetails();
            }
        });
    }

    // Function to update med-details
    function updateMedDetails() {
        $.ajax({
            url: 'fetch_med_details.php',
            type: 'GET',
            success: function(data) {
                // Replace the #med-details div with the new data
                $('#med-details').html(data);
            },
            error: function() {
                alert('Error fetching updated details.');
            }
        });
    }

    // Event handler for button clicks
    $(document).on('click', "button[id^='takeButton']:not([disabled])", function(event) {
        event.preventDefault(); // Prevent default form submission behavior
        handleButtonClick($(this));
    });

    // Ensure only initially enabled takeButton buttons remain enabled on page load
    $("button[id^='takeButton']").each(function() {
        var $button = $(this);

        // Only re-enable the button if it was not initially disabled
        if (!$button.prop('disabled')) {
            $button.prop('disabled', false);
        }
    });
});
