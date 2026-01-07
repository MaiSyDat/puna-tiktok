/**
 * Video watch page functionality
 */

(function() {
    'use strict';
    
    function initVideoWatch() {
        const videoWatchPage = document.querySelector('.video-watch-page');
        if (!videoWatchPage) return;

        const backBtn = document.getElementById('video-watch-back');
        const commentTabs = document.querySelectorAll('.comments-tab');
        const commentTabContents = document.querySelectorAll('.comments-tab-content');
        
        // Initialize video playback
        const watchVideo = videoWatchPage.querySelector('.tiktok-video');
        const youtubePlayer = videoWatchPage.querySelector('.youtube-player');
        
        if (watchVideo && watchVideo.tagName === 'VIDEO') {
            // Handle regular HTML5 video
            if (typeof applyVideoVolumeSettings !== 'undefined') {
                applyVideoVolumeSettings(watchVideo);
            }
            if (typeof applyVolumeToAllVideos !== 'undefined') {
                applyVolumeToAllVideos();
            }
            
            // Play video
            if (typeof watchVideo.play === 'function') {
                const playPromise = watchVideo.play();
                if (playPromise !== undefined) {
                    playPromise.catch(e => {
                        // Autoplay was prevented, ignore error
                    });
                }
            }
        } else if (youtubePlayer) {
            // YouTube iframe - autoplay is handled via URL parameters
            // Just apply volume settings to any other videos
            if (typeof applyVolumeToAllVideos !== 'undefined') {
                applyVolumeToAllVideos();
            }
        }
        
        // Back button functionality
        if (backBtn) {
            backBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // If referrer exists AND is not current page AND is NOT from WP Admin
                if (document.referrer && 
                    document.referrer !== window.location.href && 
                    !document.referrer.includes('/wp-admin/') && 
                    !document.referrer.includes('wp-login.php')) {
                    window.history.back();
                } else {
                    // Fallback to Home URL if coming from Admin or direct link
                    const homeUrl = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.home_url) 
                        ? puna_tiktok_ajax.home_url 
                        : '/';
                    window.location.href = homeUrl;
                }
            });
        }
        
        // Comment tabs functionality
        commentTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                commentTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                commentTabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === targetTab + '-tab-content') {
                        content.classList.add('active');
                    }
                });
            });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVideoWatch);
    } else {
        // DOM already loaded
        initVideoWatch();
    }
})();

