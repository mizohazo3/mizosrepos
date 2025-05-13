/**
 * Timer XP Animation System
 * Handles displaying XP gains and level up animations for timers
 */

class TimerXPSystem {
    constructor() {
        this.animationQueue = [];
        this.isProcessing = false;
    }

    /**
     * Add an XP gain to the animation queue
     * @param {Object} xpData - XP gain data with timer_id, xp_gain, level_up, etc.
     */
    addXPGain(xpData) {
        this.animationQueue.push(xpData);
        if (!this.isProcessing) {
            this.processNextAnimation();
        }
    }

    /**
     * Process the next animation in the queue
     */
    processNextAnimation() {
        if (this.animationQueue.length === 0) {
            this.isProcessing = false;
            return;
        }

        this.isProcessing = true;
        const xpData = this.animationQueue.shift();
        this.showXPAnimation(xpData);
    }

    /**
     * Show XP gain animation for a timer
     * @param {Object} xpData - XP gain data
     */
    showXPAnimation(xpData) {
        const timerId = xpData.timer_id;
        const xpGain = xpData.xp_gain;
        const levelUp = xpData.level_up;
        const newLevel = xpData.new_level;
        const rank = xpData.rank;
        
        // Find timer element
        const timerElement = document.querySelector(`[data-timer-id="${timerId}"]`);
        if (!timerElement) {
            console.error(`Timer element not found for ID: ${timerId}`);
            this.processNextAnimation();
            return;
        }

        // Create XP gain element
        const xpGainElement = document.createElement('div');
        xpGainElement.className = 'xp-gain';
        xpGainElement.textContent = `+${xpGain} XP`;
        
        // Add to DOM
        timerElement.appendChild(xpGainElement);
        
        // Play animation
        setTimeout(() => {
            xpGainElement.classList.add('show');
            
            // Update XP bar if exists
            const xpBar = timerElement.querySelector('.xp-bar-fill');
            const xpText = timerElement.querySelector('.xp-text');
            
            if (xpBar && xpText) {
                // Update XP display
                const oldXP = parseInt(xpData.old_xp);
                const newXP = parseInt(xpData.new_xp);
                const xpForNextLevel = parseInt(xpData.xp_for_next_level);
                
                // Calculate percentage filled
                const percentage = Math.min(100, (newXP / xpForNextLevel) * 100);
                
                // Animate XP bar
                this.animateProgressBar(xpBar, percentage);
                xpText.textContent = `${newXP}/${xpForNextLevel} XP`;
            }
            
            // Handle level up animation if needed
            if (levelUp) {
                this.showLevelUpAnimation(timerElement, newLevel, rank);
            }
            
            // Remove XP gain notification after animation completes
            setTimeout(() => {
                xpGainElement.classList.remove('show');
                setTimeout(() => {
                    xpGainElement.remove();
                    this.processNextAnimation();
                }, 300);
            }, 2000);
        }, 100);
    }

    /**
     * Show level up animation for a timer
     * @param {HTMLElement} timerElement - The timer DOM element
     * @param {number} newLevel - New level value
     * @param {string} rank - New rank name (optional)
     */
    showLevelUpAnimation(timerElement, newLevel, rank) {
        // Create level up overlay
        const levelUpElement = document.createElement('div');
        levelUpElement.className = 'level-up-overlay';
        
        // Create content
        const content = document.createElement('div');
        content.className = 'level-up-content';
        
        // Add level up text
        const levelUpText = document.createElement('h3');
        levelUpText.textContent = 'LEVEL UP!';
        
        // Add new level text
        const newLevelText = document.createElement('p');
        newLevelText.textContent = `You are now level ${newLevel}`;
        
        // Add rank text if available
        let rankText = null;
        if (rank) {
            rankText = document.createElement('p');
            rankText.className = 'rank-text';
            rankText.textContent = `Rank: ${rank}`;
        }
        
        // Assemble elements
        content.appendChild(levelUpText);
        content.appendChild(newLevelText);
        if (rankText) content.appendChild(rankText);
        levelUpElement.appendChild(content);
        
        // Add to DOM
        timerElement.appendChild(levelUpElement);
        
        // Update level display in timer
        const levelDisplay = timerElement.querySelector('.timer-level');
        if (levelDisplay) {
            levelDisplay.textContent = `Lvl ${newLevel}`;
        }
        
        // Play animation
        setTimeout(() => {
            levelUpElement.classList.add('show');
            
            // Remove level up overlay after animation completes
            setTimeout(() => {
                levelUpElement.classList.remove('show');
                setTimeout(() => {
                    levelUpElement.remove();
                }, 300);
            }, 3000);
        }, 100);
    }

    /**
     * Manually update a timer's XP display without animation
     * @param {number} timerId - Timer ID
     * @param {number} currentXP - Current XP amount
     * @param {number} xpForNextLevel - XP needed for next level
     * @param {number} level - Current level
     */
    updateTimerXPDisplay(timerId, currentXP, xpForNextLevel, level) {
        const timerElement = document.querySelector(`[data-timer-id="${timerId}"]`);
        if (!timerElement) return;
        
        // Update XP bar if exists
        const xpBar = timerElement.querySelector('.xp-bar-fill');
        const xpText = timerElement.querySelector('.xp-text');
        const levelDisplay = timerElement.querySelector('.timer-level');
        
        if (xpBar && xpText) {
            // Calculate percentage filled
            const percentage = Math.min(100, (currentXP / xpForNextLevel) * 100);
            
            // Update XP bar
            this.animateProgressBar(xpBar, percentage);
            xpText.textContent = `${currentXP}/${xpForNextLevel} XP`;
        }
        
        if (levelDisplay) {
            levelDisplay.textContent = `Lvl ${level}`;
        }
    }
    
    /**
     * Animate a progress bar from its current width to target percentage
     * @param {HTMLElement} progressBar - The progress bar element to animate
     * @param {number} targetPercentage - The target percentage (0-100)
     * @param {number} duration - Animation duration in ms (default: 2000ms)
     */
    animateProgressBar(progressBar, targetPercentage, duration = 2000) {
        // Get current width percentage
        const currentWidth = parseFloat(progressBar.style.width) || 0;
        const targetWidth = parseFloat(targetPercentage);
        
        // If already at target, no need to animate
        if (currentWidth === targetWidth) return;
        
        // Clear any existing animation
        if (progressBar._animationTimer) {
            clearInterval(progressBar._animationTimer);
        }
        
        // Calculate how many steps and how much to increment each step
        const totalChange = targetWidth - currentWidth;
        const animationInterval = 50; // Update every 50ms for smooth animation
        const steps = duration / animationInterval;
        const increment = totalChange / steps;
        
        let currentValue = currentWidth;
        let step = 0;
        
        // Start the animation with small increments
        progressBar._animationTimer = setInterval(() => {
            step++;
            currentValue += increment;
            
            // Apply the new width
            progressBar.style.width = `${currentValue}%`;
            
            // Check if animation is complete
            if (step >= steps) {
                clearInterval(progressBar._animationTimer);
                progressBar.style.width = `${targetWidth}%`;
                delete progressBar._animationTimer;
            }
        }, animationInterval);
    }
}

// Create global instance
window.timerXPSystem = new TimerXPSystem(); 