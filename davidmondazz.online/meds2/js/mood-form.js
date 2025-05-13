/**
 * Modern Mood Recording Form Functionality
 */
$(document).ready(function() {
    // Initialize the mood form for each date
    initMoodForms();
    
    // Handle mood form submission
    $(document).on('submit', '.mood-form', function(e) {
        e.preventDefault();
        submitMoodForm($(this));
    });
    
    // Handle radio button styling
    $(document).on('change', '.mood-radio-input', function() {
        const name = $(this).attr('name');
        $(`.mood-radio-input[name="${name}"]`).each(function() {
            const $label = $(this).next('.mood-radio-label');
            if (this.checked) {
                $label.addClass('active');
            } else {
                $label.removeClass('active');
            }
        });
    });
});

/**
 * Initialize all mood forms on the page
 */
function initMoodForms() {
    // Convert existing mood forms to the new format
    $('.day-mood-section').each(function() {
        const $section = $(this);
        const dateText = $section.data('date');
        const amount = $section.data('amount');
        
        // Create the new form markup
        const $form = createMoodForm(dateText, amount);
        
        // Replace the old section with the new form
        $section.replaceWith($form);
    });
}

/**
 * Create a new mood form element
 * @param {string} dateText - The date text to display
 * @param {string} amount - The amount to display
 * @returns {jQuery} The form element
 */
function createMoodForm(dateText, amount) {
    const formId = 'mood-form-' + Date.now();
    
    const $form = $('<form>', {
        class: 'mood-form',
        id: formId
    });
    
    // Date display
    const $dateSection = $('<div>', { class: 'mood-date' }).text(dateText + ':');
    if (amount) {
        $dateSection.append($('<span>', { class: 'mood-date-amount' }).text(amount));
    }
    $form.append($dateSection);
    
    // Input field
    const $inputWrapper = $('<div>', { class: 'mood-input-wrapper' });
    $inputWrapper.append($('<input>', {
        type: 'text',
        class: 'mood-input',
        name: 'mood-text',
        placeholder: 'How do you feel?'
    }));
    $form.append($inputWrapper);
    
    // Mood options
    const $options = $('<div>', { class: 'mood-options' });
    
    // Radio group
    const $radioGroup = $('<div>', { class: 'mood-radio-group' });
    
    // Positive option
    const $positiveWrapper = $('<div>', { class: 'mood-radio-wrapper' });
    const $positiveInput = $('<input>', {
        type: 'radio',
        name: 'mood-' + formId,
        id: 'mood-positive-' + formId,
        value: 'positive',
        class: 'mood-radio-input',
        checked: true
    });
    const $positiveLabel = $('<label>', {
        for: 'mood-positive-' + formId,
        class: 'mood-radio-label positive active'
    }).text('Positive');
    $positiveWrapper.append($positiveInput, $positiveLabel);
    $radioGroup.append($positiveWrapper);
    
    // Neutral option
    const $neutralWrapper = $('<div>', { class: 'mood-radio-wrapper' });
    const $neutralInput = $('<input>', {
        type: 'radio',
        name: 'mood-' + formId,
        id: 'mood-neutral-' + formId,
        value: 'neutral',
        class: 'mood-radio-input'
    });
    const $neutralLabel = $('<label>', {
        for: 'mood-neutral-' + formId,
        class: 'mood-radio-label neutral'
    }).text('Neutral');
    $neutralWrapper.append($neutralInput, $neutralLabel);
    $radioGroup.append($neutralWrapper);
    
    // Negative option
    const $negativeWrapper = $('<div>', { class: 'mood-radio-wrapper' });
    const $negativeInput = $('<input>', {
        type: 'radio',
        name: 'mood-' + formId,
        id: 'mood-negative-' + formId,
        value: 'negative',
        class: 'mood-radio-input'
    });
    const $negativeLabel = $('<label>', {
        for: 'mood-negative-' + formId,
        class: 'mood-radio-label negative'
    }).text('Negative');
    $negativeWrapper.append($negativeInput, $negativeLabel);
    $radioGroup.append($negativeWrapper);
    
    $options.append($radioGroup);
    
    // My SUS input
    const $susWrapper = $('<div>', { class: 'mood-sus-wrapper' });
    const $susInput = $('<input>', {
        type: 'text',
        class: 'mood-input',
        name: 'mood-sus',
        placeholder: 'My Sus...'
    });
    $susWrapper.append($susInput);
    $options.append($susWrapper);
    
    $form.append($options);
    
    // Submit button
    const $submit = $('<button>', {
        type: 'submit',
        class: 'mood-submit'
    }).text('Submit');
    $form.append($submit);
    
    return $form;
}

/**
 * Submit a mood form
 * @param {jQuery} $form - The form element
 */
function submitMoodForm($form) {
    const formId = $form.attr('id');
    const moodText = $form.find('input[name="mood-text"]').val();
    const moodValue = $form.find(`input[name="mood-${formId}"]:checked`).val();
    const moodSus = $form.find('input[name="mood-sus"]').val();
    
    if (!moodText) {
        // Show error if no mood text
        alert('Please enter how you feel');
        return;
    }
    
    // Get the date from the form (you might need to adjust this based on your actual data structure)
    const dateText = $form.find('.mood-date').text().replace(':', '');
    
    // Prepare data for submission
    const data = {
        sidesText: moodText,
        selectedFeeling: moodValue,
        susText: moodSus,
        daytime: dateText
    };
    
    // Submit the form via AJAX
    $.ajax({
        url: 'sides.php',
        type: 'GET',
        data: data,
        success: function(response) {
            // Show success message
            showNotification('Mood recorded successfully', 'success');
            
            // Clear the form
            $form.find('input[name="mood-text"]').val('');
            $form.find('input[name="mood-sus"]').val('');
            
            // Optionally reload to show the updated data
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error("Error submitting mood:", error);
            showNotification('Error recording mood', 'error');
        }
    });
} 