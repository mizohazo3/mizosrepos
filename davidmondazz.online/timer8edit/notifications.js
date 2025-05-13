// notifications.js - Shared Notification Functionality

// --- Configuration ---
const DEFAULT_NOTIFICATION_DURATION = 4000;

// --- DOM Element (Assumes container exists in the HTML) ---
const notificationContainer = document.getElementById('notification-container');

/**
 * Displays a notification message on the screen.
 * @param {string} message The message text to display.
 * @param {'info'|'success'|'warning'|'error'|'level-up'} type The type of notification, controlling style/icon.
 * @param {number} duration Duration in milliseconds before auto-close. 0 for persistent.
 * @returns {HTMLElement|null} The created notification element or null if container is missing.
 */
function displayNotification(message, type = 'info', duration = DEFAULT_NOTIFICATION_DURATION) {
    if (!notificationContainer) {
        console.warn("Notification container element (#notification-container) not found in the HTML!");
        return null; // Cannot display notification
    }

    const notification = document.createElement('div');
    notification.classList.add('notification', `notification-${type}`);

    // --- Icon ---
    const iconSpan = document.createElement('span');
    iconSpan.classList.add('notification-icon');
    switch (type) {
        case 'success': iconSpan.textContent = '✓'; break;
        case 'error': iconSpan.textContent = '✕'; break;
        case 'warning': iconSpan.textContent = '!'; break;
        case 'info': iconSpan.textContent = 'ℹ'; break;
        case 'level-up': iconSpan.textContent = '⭐'; break;
        default: iconSpan.textContent = '•';
    }

    // --- Content ---
    const contentDiv = document.createElement('div');
    contentDiv.classList.add('notification-content');
    const messageP = document.createElement('p');
    messageP.classList.add('notification-message');
    messageP.textContent = message;
    contentDiv.appendChild(messageP);

    // --- Close Button ---
    const closeButton = document.createElement('button');
    closeButton.classList.add('notification-close');
    closeButton.innerHTML = '×'; // Multiplication sign (X)
    closeButton.title = 'Close notification'; // Accessibility
    let autoCloseTimer = null; // Keep track of the timer
    closeButton.onclick = () => {
        clearTimeout(autoCloseTimer); // Stop auto-close if manually closed
        notification.style.opacity = '0';
        // Remove after fade out transition completes
        notification.addEventListener('transitionend', () => notification.remove(), { once: true });
        // Fallback removal if transition doesn't fire (e.g., if hidden)
        setTimeout(() => { if (notification.parentNode) notification.remove(); }, 500);
    };


    // --- Timeout Bar (Optional Visual Cue) ---
    const timeoutBar = document.createElement('div');
    timeoutBar.classList.add('notification-timeout-bar');

    // --- Assemble ---
    notification.appendChild(iconSpan);
    notification.appendChild(contentDiv);
    notification.appendChild(closeButton);
    notification.appendChild(timeoutBar);

    // Add to container (prepend to show newest at top)
    notificationContainer.insertBefore(notification, notificationContainer.firstChild);

    // --- Animate In & Timeout Bar---
    // Use requestAnimationFrame to ensure the element is in the DOM before adding 'show' class
    requestAnimationFrame(() => {
        notification.classList.add('show');
        if (duration > 0) {
            // Animate the timeout bar width
             timeoutBar.style.transition = `transform ${duration / 1000}s linear`;
            requestAnimationFrame(() => { // Second RAF ensures transition property is applied
                 timeoutBar.style.transform = 'scaleX(0)';
            });
        } else {
            // If duration is 0, hide the bar completely
            timeoutBar.style.display = 'none';
        }
    });

    // --- Auto-Close Timer ---
    if (duration > 0) {
         autoCloseTimer = setTimeout(() => {
            // Trigger the close logic automatically
             closeButton.click();
        }, duration);
    }

    return notification; // Return the element in case caller wants to interact with it
}

// --- Make the function globally accessible ---
// You might wrap this in a module or namespace in a larger app,
// but for this setup, making it global is simplest.
// window.displayNotification = displayNotification; // Uncomment if using modules or need explicit global