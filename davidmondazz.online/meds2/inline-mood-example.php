<?php
// Example file showing how to implement the inline mood form
// Copy and paste this code into your index.php file where you want the form to appear
?>

<style>
/* Inline Mood Form Styles */
.inline-mood-form {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: nowrap;
    gap: 8px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 10px 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin: 10px 0;
    width: 100%;
    max-width: 450px;
}

.inline-mood-date {
    font-weight: 600;
    color: #2c3e50;
    white-space: nowrap;
    margin-right: 10px;
}

.inline-mood-date-amount {
    color: #e74c3c;
    font-weight: 700;
}

.inline-mood-radio-group {
    display: flex;
    margin: 0 5px;
    white-space: nowrap;
}

.inline-mood-radio-wrapper {
    margin: 0 2px;
}

.inline-mood-radio-input {
    position: absolute;
    opacity: 0;
}

.inline-mood-radio-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 10px;
    font-weight: 600;
    font-size: 14px;
    border-radius: 20px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
}

.inline-mood-radio-label.positive {
    background: rgba(46, 204, 113, 0.1);
    color: #27ae60;
}

.inline-mood-radio-label.neutral {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
}

.inline-mood-radio-label.negative {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.inline-mood-radio-input:checked + .inline-mood-radio-label.positive {
    background: rgba(46, 204, 113, 0.2);
    border-color: #27ae60;
}

.inline-mood-radio-input:checked + .inline-mood-radio-label.neutral {
    background: rgba(52, 152, 219, 0.2);
    border-color: #3498db;
}

.inline-mood-radio-input:checked + .inline-mood-radio-label.negative {
    background: rgba(231, 76, 60, 0.2);
    border-color: #e74c3c;
}

.inline-mood-input {
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    flex: 1;
    min-width: 120px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.inline-mood-submit {
    background: linear-gradient(135deg, #3a7bd5, #00d2ff);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

@media (max-width: 768px) {
    .inline-mood-form {
        flex-wrap: wrap;
        width: 100%;
    }
    
    .inline-mood-radio-group {
        margin: 5px 0;
    }
    
    .inline-mood-input {
        margin: 5px 0;
    }
}
</style>

<!-- Inline Mood Form -->
<form class="inline-mood-form" method="GET" action="sides.php">
    <div class="inline-mood-date">
        13 May 2025: <span class="inline-mood-date-amount">-$10.15</span>
    </div>
    
    <div class="inline-mood-radio-group">
        <div class="inline-mood-radio-wrapper">
            <input type="radio" name="selectedFeeling" id="mood-positive" value="positive" class="inline-mood-radio-input" checked>
            <label for="mood-positive" class="inline-mood-radio-label positive">Positive</label>
        </div>
        
        <div class="inline-mood-radio-wrapper">
            <input type="radio" name="selectedFeeling" id="mood-neutral" value="neutral" class="inline-mood-radio-input">
            <label for="mood-neutral" class="inline-mood-radio-label neutral">Neutral</label>
        </div>
        
        <div class="inline-mood-radio-wrapper">
            <input type="radio" name="selectedFeeling" id="mood-negative" value="negative" class="inline-mood-radio-input">
            <label for="mood-negative" class="inline-mood-radio-label negative">Negative</label>
        </div>
    </div>
    
    <input type="text" class="inline-mood-input" name="sidesText" placeholder="How do you feel?" required>
    <input type="text" class="inline-mood-input" name="susText" placeholder="My Sus...">
    
    <!-- Hidden field for the date -->
    <input type="hidden" name="daytime" value="13 May 2025">
    
    <button type="submit" class="inline-mood-submit">Submit</button>
</form>

<script>
    // Add event listeners for radio buttons
    document.querySelectorAll('.inline-mood-radio-input').forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Remove active class from all labels
            document.querySelectorAll('.inline-mood-radio-label').forEach(function(label) {
                label.classList.remove('active');
            });
            
            // Add active class to selected label
            if (this.checked) {
                this.nextElementSibling.classList.add('active');
            }
        });
    });
</script> 