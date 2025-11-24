/**
 * Video watch page functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const videoWatchPage = document.querySelector('.video-watch-page');
    if (!videoWatchPage) return;

    const backBtn = document.getElementById('video-watch-back');
    const commentTabs = document.querySelectorAll('.comments-tab');
    const commentTabContents = document.querySelectorAll('.comments-tab-content');
    
    const watchVideo = videoWatchPage.querySelector('.tiktok-video');
    if (watchVideo) {
        // All videos are Mega videos
        if (typeof ensureMegaVideoSource !== 'undefined' && watchVideo.dataset.megaLink) {
            ensureMegaVideoSource(watchVideo).then(() => {
                if (typeof applyVideoVolumeSettings !== 'undefined') {
                    applyVideoVolumeSettings(watchVideo);
                }
                if (typeof applyVolumeToAllVideos !== 'undefined') {
                    applyVolumeToAllVideos();
                }
                
                const playPromise = watchVideo.play();
                if (playPromise !== undefined) {
                    playPromise.catch(e => {});
                }
            }).catch(err => {});
        }
    }
    
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.history.back();
            } else {
                window.location.href = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.home_url) ? puna_tiktok_ajax.home_url : '/';
            }
        });
    }
    
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
});

