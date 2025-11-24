/**
 * Dropdown menus functionality
 */

document.addEventListener("DOMContentLoaded", function() {
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
        if (!e.target.closest('.video-info-more-menu')) {
            document.querySelectorAll('.video-info-more-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
});

