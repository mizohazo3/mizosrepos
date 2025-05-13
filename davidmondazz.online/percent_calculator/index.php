<?php
// Database connection and session setup
session_start();

// MySQL Database Configuration
$db_host = 'localhost';
$db_user = 'mcgkxyz_masterpop';  // Change to your MySQL username
$db_pass = 'aA0109587045';      // Change to your MySQL password
$db_name = 'mcgkxyz_percent_calculator';  // Database name

$conn = null;

try {
    // Connect to MySQL database
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS calculations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        likes FLOAT NOT NULL,
        views FLOAT NOT NULL,
        percentage FLOAT NOT NULL,
        timestamp INT NOT NULL
    )");
    
    // Delete records older than 24 hours
    $yesterday = time() - (24 * 60 * 60);
    $stmt = $conn->prepare("DELETE FROM calculations WHERE timestamp < :yesterday");
    $stmt->bindParam(':yesterday', $yesterday);
    $stmt->execute();
    
} catch(PDOException $e) {
    // Store database error for display
    $db_error = "DB Error: " . $e->getMessage();
    $conn = null;
}

// Keep PHP processing for initial values or fallback
$result = '';
$percentage = 0;
$likes = '';
$views = '';
$error = '';
$saved = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get input values
    $likes = $_POST['likes'];
    $views = $_POST['views'];
    
    // Validate inputs (only if both are provided)
    if (!empty($likes) && !empty($views)) {
        if (!is_numeric($likes) || !is_numeric($views)) {
            $error = 'Invalid number format';
        } elseif ($views == 0) {
            $error = 'Views cannot be zero';
        } else {
            // Calculate percentage
            $percentage = ($likes / $views) * 100;
            $result = number_format($percentage, 2) . '%';
            
            // Check for duplicate calculation before saving
            $isDuplicate = false;
            if ($conn) {
                try {
                    $stmt = $conn->prepare("SELECT * FROM calculations WHERE likes = :likes AND views = :views ORDER BY timestamp DESC LIMIT 1");
                    $stmt->bindParam(':likes', $likes);
                    $stmt->bindParam(':views', $views);
                    $stmt->execute();
                    $lastCalc = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // If we found a record with the same values and it's recent (within last 5 minutes), skip saving
                    if ($lastCalc && (time() - $lastCalc['timestamp'] < 300)) {
                        $isDuplicate = true;
                    }
                } catch(PDOException $e) {
                    // Silently handle errors
                }
            }
            
            // Save to database if connection is available and not a duplicate
            if ($conn && !$isDuplicate) {
                try {
                    $stmt = $conn->prepare("INSERT INTO calculations (likes, views, percentage, timestamp) 
                                          VALUES (:likes, :views, :percentage, :timestamp)");
                    $timestamp = time();
                    $stmt->bindParam(':likes', $likes);
                    $stmt->bindParam(':views', $views);
                    $stmt->bindParam(':percentage', $percentage);
                    $stmt->bindParam(':timestamp', $timestamp);
                    $stmt->execute();
                    $saved = true;
                } catch(PDOException $e) {
                    // Silently handle DB errors
                }
            }
        }
    }
}

// Get all calculations
$all_calculations = [];
if ($conn) {
    try {
        // Limit to only the most recent 5 calculations
        $stmt = $conn->query("SELECT * FROM calculations ORDER BY timestamp DESC LIMIT 5");
        $all_calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Silently handle errors
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Like Rate Calculator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2196F3;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .input-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 5px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            text-align: center;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        input[type="text"]:focus,
        input[type="number"]:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        button {
            padding: 8px 16px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1.5;
            transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        button:hover {
            opacity: 0.9;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-danger {
            background-color: #f44336;
        }
        button:focus {
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .result {
            margin-top: 20px;
            text-align: center;
        }
        .result-value {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
        }
        .error {
            color: #f44336;
            margin-top: 10px;
            text-align: center;
        }
        .details {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .details h3 {
            margin-top: 0;
            color: #2196F3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f0;
        }
        .no-data {
            text-align: center;
            color: #777;
            font-style: italic;
            padding: 10px 0;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Percent Rate Calculator</h1>
    
    <?php if (isset($db_error)): ?>
    <div style="padding: 8px 15px; margin-bottom: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;">
        <?php echo $db_error; ?>
    </div>
    <?php endif; ?>
    
    <form id="calculator-form" method="post">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div style="width: 45%;">
                <div class="form-group">
                    <div class="input-container">
                        <label for="likes" style="margin-right: 10px;">Amount:</label>
                        <input type="text" id="likes" name="likes" value="<?php echo $likes; ?>" placeholder="Enter amount" inputmode="numeric" style="width: 56%;">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-container">
                        <label for="views" style="margin-right: 10px;">From:</label>
                        <input type="text" id="views" name="views" value="<?php echo $views; ?>" placeholder="Enter from" inputmode="numeric" style="width: 70%;">
                    </div>
                </div>
                
                <div class="text-center" style="margin-top: 15px;">
                    <button type="button" id="clear-button" class="btn-danger">Clear</button>
                </div>
            </div>
            
            <div style="width: 45%;">
                <div class="form-group">
                    <p class="text-center">Amount Rate:</p>
                    <div id="result" class="result-value" style="text-align: center; cursor: pointer;" onclick="enableRateEditing()"><?php echo $result ? $result : '0.00%'; ?></div>
                    <input type="text" id="custom-rate" style="display: none; width: 70%; margin: 10px auto; text-align: center;" placeholder="Enter custom rate %" inputmode="decimal">
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <p class="text-center">Apply Rate From:</p>
                    <div style="display: flex; justify-content: center; margin-bottom: 5px;">
                        <input type="text" id="apply-to" name="apply-to" placeholder="Enter a value" inputmode="numeric" style="width: 70%;">
                    </div>
                    <div id="applied-result" class="result-value" style="color: #9C27B0; font-size: 24px; text-align: center;">0 likes</div>
                </div>
            </div>
        </div>
        
        <div id="error-message" class="error"><?php echo $error; ?></div>
        
        <!-- Hidden submit button for form submission -->
        <button type="submit" id="calculate-button" style="display: none;">Calculate</button>
    </form>
    
    <div class="details">
        <div class="details-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Your Calculations</h3>
            <?php if (!empty($all_calculations)): ?>
                <button type="button" id="clear-history-button" class="btn-danger">Clear History</button>
            <?php endif; ?>
        </div>
        <?php if (empty($all_calculations)): ?>
            <div class="no-data">No calculations yet</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Rate</th>
                        <th>Likes</th>
                        <th>Views</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_calculations as $calc): ?>
                        <tr>
                            <td><?php echo number_format($calc['percentage'], 2) . '%'; ?></td>
                            <td><?php echo number_format($calc['likes']); ?></td>
                            <td><?php echo number_format($calc['views']); ?></td>
                            <td><?php echo date('M j, g:i a', $calc['timestamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        // Track previous values to prevent duplicate submissions
        let prevLikes = '';
        let prevViews = '';
        
        // Function to format numbers with commas
        function formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        }
        
        // Function to parse formatted numbers (removes commas)
        function parseFormattedNumber(str) {
            return parseFloat(str.replace(/,/g, ''));
        }
        
        // Function to format input values with commas
        function formatInputWithCommas(input) {
            // Only proceed if the value is not empty and is a valid number
            if (input.value.trim() === '' || isNaN(input.value.replace(/,/g, ''))) {
                return;
            }
            
            // Store cursor position relative to the end
            const cursorFromEnd = input.value.length - input.selectionEnd;
            
            // Parse the number (remove existing commas) and format it
            const numValue = parseFloat(input.value.replace(/,/g, ''));
            const formatted = formatNumber(numValue);
            
            // Update the input value
            input.value = formatted;
            
            // Restore cursor position from the end
            const newCursorPos = Math.max(0, formatted.length - cursorFromEnd);
            input.setSelectionRange(newCursorPos, newCursorPos);
        }
        
        // Function to calculate percentage without submitting the form
        function calculatePercentage() {
            const likesInput = document.getElementById('likes');
            const viewsInput = document.getElementById('views');
            const resultElement = document.getElementById('result');
            const errorElement = document.getElementById('error-message');
            
            // Get raw values and remove commas
            const likes = likesInput.value.trim().replace(/,/g, '');
            const views = viewsInput.value.trim().replace(/,/g, '');
            
            // Clear previous error
            errorElement.textContent = '';
            
            // Only validate if both fields have values
            if (likes === '' || views === '') {
                // Don't show any error, just leave the result as is
                return;
            }
            
            if (isNaN(likes) || isNaN(views)) {
                errorElement.textContent = 'Invalid number format';
                resultElement.textContent = '0.00%';
                return;
            }
            
            const likesNum = parseFloat(likes);
            const viewsNum = parseFloat(views);
            
            if (viewsNum === 0) {
                errorElement.textContent = 'Views cannot be zero';
                resultElement.textContent = '0.00%';
                return;
            }
            
            // Calculate percentage
            const percentage = (likesNum / viewsNum) * 100;
            resultElement.textContent = percentage.toFixed(2) + '%';
            
            // Apply the current rate to the value if provided
            applyRateToValue(percentage);
            
            // Auto-submit the form after a short delay if values are valid and have changed
            if (!errorElement.textContent && (likes !== prevLikes || views !== prevViews)) {
                clearTimeout(window.submitTimer);
                window.submitTimer = setTimeout(function() {
                    // Update previous values before submitting
                    prevLikes = likes;
                    prevViews = views;
                    // Use AJAX instead of form submission
                    saveCalculation(likesNum, viewsNum, percentage);
                }, 1000); // Submit after 1 second of no typing
            }
        }
        
        // Function to save calculation via AJAX
        function saveCalculation(likes, views, percentage) {
            // Create form data
            const formData = new FormData();
            formData.append('likes', likes);
            formData.append('views', views);
            
            // Send AJAX request
            fetch('save_calculation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update calculations table without refreshing the page
                    updateCalculationsTable();
                }
            })
            .catch(error => {
                console.log('Error saving calculation:', error);
            });
        }
        
        // Function to update calculations table via AJAX
        function updateCalculationsTable() {
            fetch('get_calculations.php')
            .then(response => response.text())
            .then(html => {
                const tableContainer = document.querySelector('.details');
                if (tableContainer) {
                    tableContainer.innerHTML = html;
                    // Re-attach event listener to the new Clear History button
                    const clearHistoryButton = document.getElementById('clear-history-button');
                    if (clearHistoryButton) {
                        clearHistoryButton.addEventListener('click', function() {
                            clearHistory();
                        });
                    }
                }
            })
            .catch(error => {
                console.log('Error updating calculations:', error);
            });
        }
        
        // Function to clear history via AJAX
        function clearHistory() {
            fetch('clear_history.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Instead of fetching, just clear the table now
                    const tableContainer = document.querySelector('.details');
                    if (tableContainer) {
                        tableContainer.innerHTML = `
                            <div class="details-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0;">Your Calculations</h3>
                            </div>
                            <div class="no-data">No calculations yet</div>
                        `;
                    }
                } else {
                    console.error('Failed to clear history:', data.error);
                }
            })
            .catch(error => {
                console.error('Error clearing history:', error);
            });
        }
        
        // Function to apply the current rate to a value
        function applyRateToValue(rate) {
            const applyToInput = document.getElementById('apply-to');
            const appliedResultElement = document.getElementById('applied-result');
            
            // Get raw value and remove commas
            const applyToValue = applyToInput.value.trim().replace(/,/g, '');
            
            if (applyToValue === '' || isNaN(applyToValue)) {
                appliedResultElement.textContent = '0 likes';
                return;
            }
            
            const value = parseFloat(applyToValue);
            const result = (value * rate) / 100;
            appliedResultElement.textContent = formatNumber(result.toFixed(2)) + ' likes';
        }
        
        // Add event listeners for input blur to format with commas
        document.getElementById('likes').addEventListener('blur', function() {
            formatInputWithCommas(this);
        });
        
        document.getElementById('views').addEventListener('blur', function() {
            formatInputWithCommas(this);
        });
        
        document.getElementById('apply-to').addEventListener('blur', function() {
            formatInputWithCommas(this);
        });
        
        // Add event listeners for focus to select all text for easy editing
        document.getElementById('likes').addEventListener('focus', function() {
            this.select();
        });
        
        document.getElementById('views').addEventListener('focus', function() {
            this.select();
        });
        
        document.getElementById('apply-to').addEventListener('focus', function() {
            this.select();
        });
        
        // Add event listeners for input to handle number formatting during typing
        document.getElementById('likes').addEventListener('input', function() {
            // Store cursor position
            const cursorPos = this.selectionStart;
            
            // Get raw value by removing commas
            const rawValue = this.value.replace(/,/g, '');
            
            if (rawValue !== '' && !isNaN(rawValue)) {
                // Count commas before cursor position to adjust cursor later
                const beforeCursor = this.value.substring(0, cursorPos);
                const commasBefore = (beforeCursor.match(/,/g) || []).length;
                
                // Format the number with commas
                if (rawValue.length > 3) {
                    const formatted = formatNumber(rawValue);
                    this.value = formatted;
                    
                    // Calculate new cursor position, accounting for added commas
                    const newCommasBefore = (formatted.substring(0, cursorPos + 1).match(/,/g) || []).length;
                    const newPosition = cursorPos + (newCommasBefore - commasBefore);
                    
                    // Set cursor position
                    setTimeout(() => {
                        this.setSelectionRange(newPosition, newPosition);
                    }, 0);
                }
                
                calculatePercentage();
            }
        });
        
        document.getElementById('views').addEventListener('input', function() {
            // Store cursor position
            const cursorPos = this.selectionStart;
            
            // Get raw value by removing commas
            const rawValue = this.value.replace(/,/g, '');
            
            if (rawValue !== '' && !isNaN(rawValue)) {
                // Count commas before cursor position to adjust cursor later
                const beforeCursor = this.value.substring(0, cursorPos);
                const commasBefore = (beforeCursor.match(/,/g) || []).length;
                
                // Format the number with commas
                if (rawValue.length > 3) {
                    const formatted = formatNumber(rawValue);
                    this.value = formatted;
                    
                    // Calculate new cursor position, accounting for added commas
                    const newCommasBefore = (formatted.substring(0, cursorPos + 1).match(/,/g) || []).length;
                    const newPosition = cursorPos + (newCommasBefore - commasBefore);
                    
                    // Set cursor position
                    setTimeout(() => {
                        this.setSelectionRange(newPosition, newPosition);
                    }, 0);
                }
                
                calculatePercentage();
            }
        });
        
        document.getElementById('apply-to').addEventListener('input', function() {
            // Store cursor position
            const cursorPos = this.selectionStart;
            
            // Get raw value by removing commas
            const rawValue = this.value.replace(/,/g, '');
            
            if (rawValue !== '' && !isNaN(rawValue)) {
                // Count commas before cursor position to adjust cursor later
                const beforeCursor = this.value.substring(0, cursorPos);
                const commasBefore = (beforeCursor.match(/,/g) || []).length;
                
                // Format the number with commas
                if (rawValue.length > 3) {
                    const formatted = formatNumber(rawValue);
                    this.value = formatted;
                    
                    // Calculate new cursor position, accounting for added commas
                    const newCommasBefore = (formatted.substring(0, cursorPos + 1).match(/,/g) || []).length;
                    const newPosition = cursorPos + (newCommasBefore - commasBefore);
                    
                    // Set cursor position
                    setTimeout(() => {
                        this.setSelectionRange(newPosition, newPosition);
                    }, 0);
                }
                
                // Get the current percentage rate
                const resultText = document.getElementById('result').textContent;
                const percentage = parseFloat(resultText);
                // Apply to the value
                applyRateToValue(isNaN(percentage) ? 0 : percentage);
            }
        });
        
        // Add a specific handler for form submission
        document.getElementById('calculator-form').addEventListener('submit', function(e) {
            // Always prevent the default form submission
            e.preventDefault();
            
            // Only perform calculation if both fields are filled and valid
            const likes = document.getElementById('likes').value.trim();
            const views = document.getElementById('views').value.trim();
            
            if (likes !== '' && views !== '' && 
                document.getElementById('error-message').textContent === '') {
                const likesNum = parseFloat(likes);
                const viewsNum = parseFloat(views);
                const percentage = (likesNum / viewsNum) * 100;
                saveCalculation(likesNum, viewsNum, percentage);
            }
        });
        
        // Also attach event listeners to the clear history button
        document.addEventListener('DOMContentLoaded', function() {
            // Initial calculation if values are present
            if (document.getElementById('likes').value && document.getElementById('views').value) {
                calculatePercentage();
            }
            
            // Set up event listener for the clear history button
            const clearHistoryButton = document.getElementById('clear-history-button');
            if (clearHistoryButton) {
                clearHistoryButton.addEventListener('click', function() {
                    clearHistory();
                });
            }
        });
        
        // Function to clear inputs
        function clearInputs() {
            document.getElementById('likes').value = '';
            document.getElementById('views').value = '';
            document.getElementById('apply-to').value = '';
            document.getElementById('result').textContent = '0.00%';
            document.getElementById('applied-result').textContent = '0 likes';
            document.getElementById('error-message').textContent = '';
            // Focus on likes input for quick entry
            document.getElementById('likes').focus();
            // Cancel any pending auto-submit
            clearTimeout(window.submitTimer);
        }
        
        document.getElementById('clear-button').addEventListener('click', clearInputs);

        // Focus on likes input when page loads
        window.onload = function() {
            document.getElementById('likes').focus();
        };
        
        // Function to enable manual rate editing
        function enableRateEditing() {
            const resultElement = document.getElementById('result');
            const customRateInput = document.getElementById('custom-rate');
            
            // Hide the result and show the input
            resultElement.style.display = 'none';
            customRateInput.style.display = 'block';
            
            // Set initial value (remove % sign)
            const currentRate = resultElement.textContent.replace('%', '');
            customRateInput.value = currentRate;
            
            // Focus on the input
            customRateInput.focus();
            customRateInput.select();
            
            // Add event listener for enter key and blur
            customRateInput.addEventListener('keypress', handleCustomRateKeyPress);
            customRateInput.addEventListener('blur', applyCustomRate);
        }
        
        // Handle keypresses in the custom rate input
        function handleCustomRateKeyPress(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur(); // Will trigger the blur event which applies the rate
            }
        }
        
        // Apply the custom rate value
        function applyCustomRate() {
            const resultElement = document.getElementById('result');
            const customRateInput = document.getElementById('custom-rate');
            
            let rateValue = customRateInput.value.trim();
            
            // Validate input
            if (rateValue === '' || isNaN(rateValue)) {
                // Just hide the input and show the original value
                resultElement.style.display = 'block';
                customRateInput.style.display = 'none';
                return;
            }
            
            // Parse and format the rate
            rateValue = parseFloat(rateValue);
            resultElement.textContent = rateValue.toFixed(2) + '%';
            
            // Hide the input and show the result
            resultElement.style.display = 'block';
            customRateInput.style.display = 'none';
            
            // Apply this rate to the current value in "Apply Rate To Value"
            const applyToInput = document.getElementById('apply-to');
            if (applyToInput.value.trim() !== '') {
                applyRateToValue(rateValue);
            }
            
            // Remove event listeners
            customRateInput.removeEventListener('keypress', handleCustomRateKeyPress);
            customRateInput.removeEventListener('blur', applyCustomRate);
        }
    </script>
</body>
</html>