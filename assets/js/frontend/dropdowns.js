/**
 * Dropdown menus functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    // Video options menu
    document.addEventListener('click', function(e) {
        const optionsBtn = e.target.closest('.video-options-btn');
        if (optionsBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = optionsBtn.nextElementSibling;
            const isShowing = dropdown?.classList.contains('show');
            
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
            
            if (!isShowing && dropdown) {
                dropdown.classList.add('show');
            }
        }
    });

    // Video Info More Button (single video page)
    document.addEventListener('click', function(e) {
        const moreBtn = e.target.closest('.video-info-more-btn');
        if (moreBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const menu = moreBtn.closest('.video-info-more-menu');
            const dropdown = menu?.querySelector('.video-info-more-dropdown');
            
            if (!dropdown) return;
            
            const isShowing = dropdown.classList.contains('show');
            
            document.querySelectorAll('.video-info-more-dropdown').forEach(d => d.classList.remove('show'));
            
            if (!isShowing) {
                dropdown.classList.add('show');
            }
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.video-options-menu')) {
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
        }
        if (!e.target.closest('.video-info-more-menu')) {
            document.querySelectorAll('.video-info-more-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
});

