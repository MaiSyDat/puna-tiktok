/**
 * Video navigation functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');

    /**
     * Get current video
     */
    function getCurrentVideo() {
        if (typeof window.getCurrentVideo === 'function') {
            return window.getCurrentVideo();
        }
        const videoList = document.querySelectorAll('.tiktok-video');
        return Array.from(videoList).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
    }

    /**
     * Scroll to sibling video
     */
    function scrollToSibling(direction) {
        const videoList = document.querySelectorAll('.tiktok-video');
        if (!videoList.length) return;
        
        let currentVideo = getCurrentVideo();
        if (!currentVideo) currentVideo = videoList[0];
        
        const currentIndex = Array.from(videoList).indexOf(currentVideo);
        const nextIndex = currentIndex + direction;
        
        if (nextIndex >= 0 && nextIndex < videoList.length) {
            const nextVideo = videoList[nextIndex];
            const videoRow = nextVideo.closest('.video-row');
            const targetElement = videoRow || nextVideo;
            
            targetElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest'
            });
            
            setTimeout(() => {
                updateNavDisabledState();
            }, 100);
        }
    }

    /**
     * Update disabled state of navigation buttons
     */
    function updateNavDisabledState() {
        const videoList = document.querySelectorAll('.tiktok-video');
        if (!videoList.length) return;
        
        let current = getCurrentVideo();
        if (!current) current = videoList[0];
        
        const idx = Array.from(videoList).indexOf(current);
        const atTop = idx <= 0;
        const atBottom = idx >= videoList.length - 1;
        
        if (navPrevBtn) navPrevBtn.classList.toggle('is-disabled', atTop);
        if (navNextBtn) navNextBtn.classList.toggle('is-disabled', atBottom);
    }

    // Event listener for navigation buttons
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.video-nav-btn');
        if (!target) return;
        
        e.preventDefault();
        if (target.classList.contains('nav-prev')) {
            scrollToSibling(-1);
        } else if (target.classList.contains('nav-next')) {
            scrollToSibling(1);
        }
    });

    // Update navigation state on scroll
    const updateEvents = ['scroll', 'keydown', 'touchend'];
    updateEvents.forEach(evt => {
        (evt === 'scroll' && mainContent ? mainContent : document).addEventListener(evt, function() {
            window.requestAnimationFrame(updateNavDisabledState);
        });
    });

    // Navigation with arrow keys
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            const direction = e.key === 'ArrowUp' ? -1 : 1;
            scrollToSibling(direction);
        }
    });

    // Navigation with swipe on mobile
    let touchStartY = 0;
    let touchStartTime = 0;
    let lastSwipeTime = 0;
    const SWIPE_THRESHOLD = 50;
    const SWIPE_COOLDOWN = 300;
    
    if (mainContent) {
        mainContent.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
            touchStartTime = Date.now();
        }, { passive: true });
        
        mainContent.addEventListener('touchend', function(e) {
            const now = Date.now();
            
            if (now - lastSwipeTime < SWIPE_COOLDOWN) {
                return;
            }
            
            const endY = e.changedTouches[0].clientY;
            const diffY = touchStartY - endY;
            const timeDiff = now - touchStartTime;
            
            if (Math.abs(diffY) > SWIPE_THRESHOLD && timeDiff < 200) {
                const direction = diffY > 0 ? 1 : -1;
                lastSwipeTime = now;
                scrollToSibling(direction);
            }
        }, { passive: true });
    }

    // Initialize navigation state
    updateNavDisabledState();
});

