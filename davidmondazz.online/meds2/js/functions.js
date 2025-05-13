// Function to get search results
function getResults(value) {
    if (value.length == 0) { 
        document.getElementById("results").style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("results");
                resultsDiv.innerHTML = "";
                resultsDiv.style.display = "block";
                for (var i = 0; i < results.length; i++) {
                    var resultDiv = document.createElement("div");
                    resultDiv.innerHTML = results[i];
                    resultDiv.onclick = function() {
                        document.getElementById("sidesText").value = this.innerHTML;
                        document.getElementById("results").style.display = "none";
                    }
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search.php?q=" + value, true);
        xmlhttp.send();
    }
}

// Function to get suspected results
function get_sus_Results(value) {
    if (value.length == 0) { 
        document.getElementById("sus_results").style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("sus_results");
                resultsDiv.innerHTML = "";
                if (results.length > 0) {
                    resultsDiv.style.display = "block";
                    for (var i = 0; i < results.length; i++) {
                        var resultDiv = document.createElement("div");
                        resultDiv.dataset.mySus = results[i].my_sus;
                        resultDiv.dataset.keyword = results[i].keyword;
                        resultDiv.innerHTML = results[i].my_sus;
                        resultDiv.onclick = function() {
                            document.getElementById("susText").value = this.dataset.mySus;
                            document.getElementById("sus_results").style.display = "none";
                        };
                        resultsDiv.appendChild(resultDiv);
                    }
                } else {
                    resultsDiv.style.display = "none";
                }
            }
        };
        xmlhttp.open("GET", "search_mySus_main.php?q=" + encodeURIComponent(value), true);
        xmlhttp.send();
    }
}

// Function to get results with counter
function getResults2(value, counter) {
    if (value.length == 0) { 
        document.getElementById("results" + counter).style.display = "none";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var results = JSON.parse(this.responseText);
                var resultsDiv = document.getElementById("results" + counter);
                resultsDiv.innerHTML = "";
                resultsDiv.style.display = "block";
                for (var i = 0; i < results.length; i++) {
                    var resultDiv = document.createElement("div");
                    resultDiv.innerHTML = results[i];
                    resultDiv.onclick = function() {
                        document.getElementById("sidesText" + counter).value = this.innerHTML;
                        document.getElementById("results" + counter).style.display = "none";
                    }
                    resultsDiv.appendChild(resultDiv);
                }
            }
        };
        xmlhttp.open("GET", "search.php?q=" + value, true);
        xmlhttp.send();
    }
}

// Function to handle countdown
function startCountdown(buttonId, seconds, originalText) {
    const $button = $('#' + buttonId);
    if (!$button.length) return; // Button not found
    
    // Store the end time in localStorage
    const endTime = Date.now() + (seconds * 1000);
    localStorage.setItem('endTime_' + buttonId, endTime.toString());
    
    // Disable the button
    $button.prop('disabled', true);
    
    // Update the button text with the countdown
    const countdownInterval = setInterval(function() {
        const remainingTime = Math.round((endTime - Date.now()) / 1000);
        
        if (remainingTime <= 0) {
            clearInterval(countdownInterval);
            $button.prop('disabled', false);
            $button.text(originalText || $button.data('original-text') || 'Take');
            localStorage.removeItem('endTime_' + buttonId);
        } else {
            $button.text(remainingTime + 's');
        }
    }, 1000);
} 

/**
 * Disable a button for a specified duration
 * @param {HTMLElement|jQuery} button - The button element to disable
 * @param {number} duration - The duration in milliseconds to disable the button
 */
function disableButton(button) {
    const $button = $(button);
    const originalText = $button.text();
    const duration = 3000; // 3 seconds
    
    // Save original text if not already saved
    if (!$button.data('original-text')) {
        $button.data('original-text', originalText);
    }
    
    // Disable the button
    $button.prop('disabled', true);
    
    // Start countdown
    const startTime = Date.now();
    const endTime = startTime + duration;
    
    const updateButtonText = () => {
        const now = Date.now();
        const remainingTime = Math.ceil((endTime - now) / 1000);
        
        if (remainingTime <= 0) {
            clearInterval(intervalId);
            $button.prop('disabled', false);
            $button.text(originalText);
        } else {
            $button.text(`${remainingTime}s`);
        }
    };
    
    // Update immediately
    updateButtonText();
    
    // Then update every 100ms
    const intervalId = setInterval(updateButtonText, 100);
}

/**
 * Show a toast notification
 * @param {string} message - The message to display in the notification
 * @param {string} type - The type of notification (success, info, warning, error)
 * @param {number} duration - How long to show the notification in milliseconds
 */
function showNotification(medName, type = 'success', duration = 3000) {
    // Create toast message
    const message = `Med ${medName} has been taken.`;
    
    // Check if container exists, create if not
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '30px';
        container.style.right = '30px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    // Create the toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;
    
    // Add to container
    container.appendChild(toast);
    
    // Trigger reflow to ensure transition works
    void toast.offsetWidth;
    
    // Show the toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Hide and remove the toast after duration
    setTimeout(() => {
        toast.classList.remove('show');
        
        // Remove from DOM after transition
        setTimeout(() => {
            container.removeChild(toast);
            
            // Remove container if empty
            if (container.children.length === 0) {
                document.body.removeChild(container);
            }
        }, 500);
    }, duration);
}

// Handle lock and unlock button functionality
$(document).ready(function() {
    // Lock button handler
    $(document).on('click', "#lockButton:not([disabled])", function(event) {
        event.preventDefault();
        
        const $button = $(this);
        const name = $button.parents("span").attr("name");
        
        // Disable the button
        disableButton($button);
        
        // Send the lock request
        $.ajax({
            url: 'lock.php',
            type: 'GET',
            data: { name: name },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Lock error:', textStatus, errorThrown);
                showNotification(name + ' lock failed!', 'error');
            },
            success: function(response) {
                showNotification(name + ' locked successfully!', 'info');
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        });
    });
    
    // Unlock button handler
    $(document).on('click', "#unlockButton:not([disabled])", function(event) {
        event.preventDefault();
        
        const $button = $(this);
        const name = $button.parents("span").attr("name");
        
        // Disable the button
        disableButton($button);
        
        // Send the unlock request
        $.ajax({
            url: 'unlock.php',
            type: 'GET',
            data: { name: name },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Unlock error:', textStatus, errorThrown);
                showNotification(name + ' unlock failed!', 'error');
            },
            success: function(response) {
                showNotification(name + ' unlocked successfully!', 'info');
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        });
    });
}); 