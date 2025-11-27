/**
 * Video navigation functionality with smooth mobile scrolling
 */

document.addEventListener("DOMContentLoaded", function() {
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');
    
    // Check if mobile device
    const isMobile = window.innerWidth <= 768;
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    /**
     * Get current video - uses global function from core.js
     */
    function getCurrentVideo() {
        if (typeof window.getCurrentVideo === 'function') {
            return window.getCurrentVideo();
        }
        // Fallback if core.js hasn't loaded yet
        const videoList = document.querySelectorAll('.tiktok-video');
        return videoList.length > 0 ? videoList[0] : null;
    }

    /**
     * Scroll to sibling video with smooth animation
     */
    function scrollToSibling(direction, useNativeSnap = false) {
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
            
            if (isMobile && useNativeSnap) {
                // On mobile, let CSS scroll-snap handle it naturally
                // Just scroll to the element and let snap do the work
                const targetTop = targetElement.offsetTop;
                const containerHeight = mainContent ? mainContent.clientHeight : window.innerHeight;
                const scrollPosition = targetTop - (containerHeight - targetElement.offsetHeight) / 2;
                
                if (mainContent) {
                    mainContent.scrollTo({
                        top: scrollPosition,
                        behavior: 'smooth'
                    });
                } else {
                    window.scrollTo({
                        top: scrollPosition,
                        behavior: 'smooth'
                    });
                }
            } else {
                // Use scrollIntoView for desktop
                targetElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center',
                    inline: 'nearest'
                });
            }
            
            // Update nav state after scroll completes
            setTimeout(() => {
                updateNavDisabledState();
            }, 300);
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
            scrollToSibling(-1, isMobile);
        } else if (target.classList.contains('nav-next')) {
            scrollToSibling(1, isMobile);
        }
    });

    // Throttle scroll events for better performance
    let scrollTimeout;
    let isScrolling = false;
    
    function handleScroll() {
        if (!isScrolling) {
            isScrolling = true;
        }
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            isScrolling = false;
            updateNavDisabledState();
        }, 150);
        
        // Update during scroll for responsive feel
        window.requestAnimationFrame(updateNavDisabledState);
    }

    // Update navigation state on scroll
    if (mainContent) {
        mainContent.addEventListener('scroll', handleScroll, { passive: true });
    } else {
        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    // Navigation with arrow keys
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            const direction = e.key === 'ArrowUp' ? -1 : 1;
            scrollToSibling(direction, isMobile);
        }
    });

    // Enhanced touch/swipe handling for mobile
    if (isTouchDevice && mainContent) {
        let touchStartY = 0;
        let touchStartX = 0;
        let touchStartTime = 0;
        let touchMoveY = 0;
        let isSwiping = false;
        let lastSwipeTime = 0;
        let scrollStartY = 0;
        
        const SWIPE_THRESHOLD = 80; // Increased threshold for better detection
        const SWIPE_VELOCITY_THRESHOLD = 0.3; // pixels per ms
        const SWIPE_COOLDOWN = 400; // Increased cooldown to prevent double swipes
        const HORIZONTAL_SWIPE_THRESHOLD = 30; // Prevent horizontal swipes from triggering
        
        mainContent.addEventListener('touchstart', function(e) {
            if (e.touches.length !== 1) return;
            
            touchStartY = e.touches[0].clientY;
            touchStartX = e.touches[0].clientX;
            touchStartTime = Date.now();
            scrollStartY = mainContent.scrollTop;
            isSwiping = false;
        }, { passive: true });
        
        mainContent.addEventListener('touchmove', function(e) {
            if (e.touches.length !== 1) return;
            
            const currentY = e.touches[0].clientY;
            const currentX = e.touches[0].clientX;
            touchMoveY = currentY;
            
            const diffY = Math.abs(currentY - touchStartY);
            const diffX = Math.abs(currentX - touchStartX);
            
            // Only consider vertical swipes
            if (diffY > diffX && diffY > 10) {
                isSwiping = true;
            }
        }, { passive: true });
        
        mainContent.addEventListener('touchend', function(e) {
            if (!isSwiping) return;
            
            const now = Date.now();
            const timeDiff = now - touchStartTime;
            
            // Prevent rapid consecutive swipes
            if (now - lastSwipeTime < SWIPE_COOLDOWN) {
                isSwiping = false;
                return;
            }
            
            const endY = e.changedTouches[0].clientY;
            const endX = e.changedTouches[0].clientX;
            const diffY = touchStartY - endY;
            const diffX = Math.abs(endX - touchStartX);
            
            // Only process vertical swipes
            if (diffX > HORIZONTAL_SWIPE_THRESHOLD) {
                isSwiping = false;
                return;
            }
            
            // Calculate velocity
            const velocity = Math.abs(diffY) / timeDiff;
            
            // Check if it's a valid swipe
            const isQuickSwipe = timeDiff < 300 && Math.abs(diffY) > SWIPE_THRESHOLD;
            const isFastSwipe = velocity > SWIPE_VELOCITY_THRESHOLD && Math.abs(diffY) > 50;
            
            if (isQuickSwipe || isFastSwipe) {
                const direction = diffY > 0 ? 1 : -1;
                lastSwipeTime = now;
                isSwiping = false;
                
                // Small delay to let native scroll settle
                setTimeout(() => {
                    scrollToSibling(direction, true);
                }, 50);
            }
            
            isSwiping = false;
        }, { passive: true });
        
        // Prevent bounce/overscroll on iOS
        mainContent.addEventListener('touchmove', function(e) {
            const scrollTop = mainContent.scrollTop;
            const scrollHeight = mainContent.scrollHeight;
            const clientHeight = mainContent.clientHeight;
            
            // Prevent overscroll at top
            if (scrollTop <= 0 && e.touches[0].clientY > touchStartY) {
                e.preventDefault();
            }
            
            // Prevent overscroll at bottom
            if (scrollTop >= scrollHeight - clientHeight - 1 && e.touches[0].clientY < touchStartY) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    // Initialize navigation state
    updateNavDisabledState();
    
    // Update on resize
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            updateNavDisabledState();
        }, 250);
    });
});
