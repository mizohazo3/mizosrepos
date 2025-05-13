function createTimerElement(timer) {
    const timerElement = document.createElement('div');
    timerElement.className = `timer-card ${timer.status}`;
    timerElement.id = `timer-${timer.id}`;
    timerElement.setAttribute('data-id', timer.id);
    
    // Add entrance animation class
    timerElement.classList.add('timer-entrance');
    setTimeout(() => {
        timerElement.classList.remove('timer-entrance');
    }, 500);
    
    // ... existing code ...
}

function updateTimerStatus(timerId, status) {
    // ... existing code ...
    
    // Add transition animation class
    const timerElement = document.getElementById(`timer-${timerId}`);
    if (timerElement) {
        timerElement.classList.add('status-changing');
        setTimeout(() => {
            timerElement.className = `timer-card ${status} ${getTimerCardClass(timerId)}`;
            setTimeout(() => {
                timerElement.classList.remove('status-changing');
            }, 300);
        }, 50);
    }
    
    // ... existing code ...
} 