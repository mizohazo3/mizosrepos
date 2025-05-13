let timerInterval;
let startTime;
let pausedTime = 0;
let isRunning = false;

const timerDisplay = document.getElementById('timer');
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const stopBtn = document.getElementById('stopBtn');
const itemCountInput = document.getElementById('itemCount');
const resultDisplay = document.getElementById('result');

// Load paused time from server when page loads
window.onload = function() {
    getPausedTimeFromServer();
};

function getPausedTimeFromServer() {
    fetch('get_timer.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                pausedTime = parseInt(data.paused_time);
                if (pausedTime > 0) {
                    updateTimerDisplay(pausedTime);
                    // Adjust startTime to resume timer
                    startTime = Date.now() - pausedTime;
                   if (isRunning) {
                        timerInterval = setInterval(updateTimer, 10);
                    }
                }
            } else {
                console.log('Error fetching timer data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching timer data:', error);
        });
}


startBtn.addEventListener('click', startTimer);
pauseBtn.addEventListener('click', pauseTimer);
stopBtn.addEventListener('click', stopTimer);
itemCountInput.addEventListener('input', calculatePerHour);

function startTimer() {
    if (!isRunning) {
        isRunning = true;
        startTime = Date.now() - pausedTime;
        timerInterval = setInterval(updateTimer, 10);
    }
}

function pauseTimer() {
    if (isRunning) {
        isRunning = false;
        clearInterval(timerInterval);
        pausedTime = Date.now() - startTime;
        savePausedTimeToServer(pausedTime); // Save paused time to server
        updateTimerDisplay(pausedTime);
        itemCountInput.focus();
        calculatePerHour();
    }
}


function stopTimer() {
    isRunning = false;
    clearInterval(timerInterval);
    pausedTime = 0;
    updateTimerDisplay(0);
    itemCountInput.value = '';
    resultDisplay.textContent = '';
    clearPausedTimeOnServer(); // Clear paused time on server
}

function updateTimer() {
    const elapsedTime = Date.now() - startTime;
    updateTimerDisplay(elapsedTime);
}

function updateTimerDisplay(time) {
    const milliseconds = Math.floor((time % 1000) / 10).toString().padStart(2, '0');
    const seconds = Math.floor(time / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    const displayHours = hours.toString().padStart(2, '0');
    const displayMinutes = (minutes % 60).toString().padStart(2, '0');
    const displaySeconds = (seconds % 60).toString().padStart(2, '0');

    timerDisplay.textContent = `${displayHours}:${displayMinutes}:${displaySeconds}.${milliseconds}`;
}

function calculatePerHour() {
    const itemCount = parseInt(itemCountInput.value);
    if (isNaN(itemCount) || itemCount <= 0) {
        resultDisplay.textContent = 'Please enter a valid number of items.';
        return;
    }

    const elapsedSeconds = Math.floor(pausedTime / 1000); // Convert pausedTime to whole seconds
    if (elapsedSeconds === 0) {
        resultDisplay.textContent = 'Timer has not been started.';
        return;
    }

    const ratePerHour = Math.floor((itemCount / elapsedSeconds) * 3600);
    resultDisplay.textContent = `Items per hour: ${ratePerHour.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
}

function savePausedTimeToServer(pausedTime) {
    console.log('Sending paused time:', pausedTime); // Log the pausedTime value
    fetch('save_timer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ paused_time: pausedTime }),
    })
    .then(response => {
        console.log('Response status:', response.status); // Log the response status
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            console.log('Paused time saved successfully:', pausedTime);
        } else {
            console.error('Error saving paused time:', data.message);
        }
    })
    .catch(error => {
        console.error('Error saving paused time:', error);
    });
}




function clearPausedTimeOnServer() {
    fetch('save_timer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ paused_time: 0 }), // Send 0 to clear paused time
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('Paused time cleared successfully');
        } else {
            console.error('Error clearing paused time:', data.message);
        }
    })
    .catch(error => {
        console.error('Error clearing paused time:', error);
    });
}

