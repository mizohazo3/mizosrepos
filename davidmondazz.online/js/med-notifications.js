/**
 * Medication Notifications JavaScript
 * Handles notifications for medication tracking
 */

// Helper function to format dates
function formatDate(date) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(date).toLocaleDateString('en-US', options);
}

// Check for notification permissions
function checkNotificationPermission() {
    if (!("Notification" in window)) {
        console.log("This browser does not support notifications");
        return false;
    }
    
    if (Notification.permission === "granted") {
        return true;
    } 
    
    if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function (permission) {
            return permission === "granted";
        });
    }
    
    return false;
}

// Send medication notification
function sendMedicationNotification(medName, lastDose, halfLife) {
    if (!checkNotificationPermission()) return;
    
    const title = "Medication Reminder";
    const options = {
        body: `${medName} is due soon. Last dose was ${lastDose}`,
        icon: '/img/icon.png',
        badge: '/img/icon.png',
        tag: `med-${medName.replace(/\s+/g, '-').toLowerCase()}`,
        renotify: true,
        data: {
            medication: medName,
            timestamp: new Date().getTime()
        }
    };
    
    const notification = new Notification(title, options);
    
    notification.onclick = function(event) {
        event.preventDefault();
        window.focus();
        notification.close();
    };
}

// Initialize notifications
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permission on page load
    checkNotificationPermission();
    
    // Log that notifications are ready
    console.log("Medication notification system initialized");
});