/**
 * Custom icons for timer categories
 * This script adds category-specific icons and modifies the timer display
 */

// Execute when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Save original createTimerHtml function if it exists
    if (typeof window.originalCreateTimerHtml !== 'function' && typeof createTimerHtml === 'function') {
        window.originalCreateTimerHtml = createTimerHtml;
        
        // Replace with enhanced version
        window.createTimerHtml = function(timer) {
            // Get HTML from original function
            let html = window.originalCreateTimerHtml(timer);
            
            // Get appropriate icon based on category name
            let categoryIcon = 'tag';
            const catName = timer.category_name.toLowerCase();
            
            if (catName.includes('work')) {
                categoryIcon = 'briefcase';
            } else if (catName.includes('personal')) {
                categoryIcon = 'user';
            } else if (catName.includes('study') || catName.includes('education')) {
                categoryIcon = 'book';
            } else if (catName.includes('health') || catName.includes('fitness')) {
                categoryIcon = 'heartbeat';
            } else if (catName.includes('entertainment') || catName.includes('fun')) {
                categoryIcon = 'film';
            } else if (catName.includes('food') || catName.includes('meal')) {
                categoryIcon = 'utensils';
            } else if (catName.includes('travel') || catName.includes('trip')) {
                categoryIcon = 'plane';
            } else if (catName.includes('home') || catName.includes('house')) {
                categoryIcon = 'home';
            } else if (catName.includes('meeting') || catName.includes('conference')) {
                categoryIcon = 'users';
            } else if (catName.includes('project')) {
                categoryIcon = 'tasks';
            }
            
            // Replace fa-tag with category-specific icon
            html = html.replace(/fa-tag/g, `fa-${categoryIcon}`);
            
            // Replace history icon with clock for total time
            html = html.replace(/fa-history/g, 'fa-clock');
            
            return html;
        };
    }
});
