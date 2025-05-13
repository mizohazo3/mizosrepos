<?php
// Simple inline mood form example
// Copy and paste this code into your index.php file
?>

<div style="display: flex; align-items: center; padding: 10px; background-color: #f5f7fa; border-radius: 8px; margin: 10px 0; width: 100%; max-width: 450px;">
    <!-- Date and Amount -->
    <div style="font-weight: 600; margin-right: 10px;">
        13 May 2025: <span style="color: #e74c3c; font-weight: 700;">-$10.15</span>
    </div>
    
    <!-- Radio Buttons -->
    <div style="display: flex; margin: 0 5px;">
        <div style="margin: 0 5px;">
            <input type="radio" id="positive" name="selectedFeeling" value="positive" checked>
            <label for="positive">Positive</label>
        </div>
        
        <div style="margin: 0 5px;">
            <input type="radio" id="neutral" name="selectedFeeling" value="neutral">
            <label for="neutral">Neutral</label>
        </div>
        
        <div style="margin: 0 5px;">
            <input type="radio" id="negative" name="selectedFeeling" value="negative">
            <label for="negative">Negative</label>
        </div>
    </div>
    
    <!-- Text Inputs -->
    <input type="text" placeholder="How do you feel?" style="padding: 5px; margin: 0 5px; flex: 1; min-width: 120px;">
    <input type="text" placeholder="My Sus..." style="padding: 5px; margin: 0 5px; flex: 1; min-width: 120px;">
    
    <!-- Submit Button -->
    <button type="submit" style="padding: 5px 10px; background-color: #3a7bd5; color: white; border: none; border-radius: 5px; font-weight: bold;">Submit</button>
</div> 