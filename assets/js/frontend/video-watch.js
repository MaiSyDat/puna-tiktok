/**
 * Video watch page functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const videoWatchPage = document.querySelector('.video-watch-page');
    if (!videoWatchPage) return;

    const backBtn = document.getElementById('video-watch-back');
    const commentTabs = document.querySelectorAll('.comments-tab');
    const commentTabContents = document.querySelectorAll('.comments-tab-content');
    const copyLinkBtn = document.querySelector('.copy-link-btn');
    
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
    
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const link = this.getAttribute('data-link') || window.location.href;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fa-solid fa-check"></i>';
                    this.style.background = 'var(--puna-primary)';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.background = '';
                    }, 2000);
                }).catch(err => {});
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = link;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fa-solid fa-check"></i>';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {}
                document.body.removeChild(textArea);
            }
        });
    }
    
    const shareBtn = document.querySelector('.interaction-item[data-action="share"]');
    const shareOptions = document.getElementById('video-share-options');
    
    if (shareBtn && shareOptions) {
        shareBtn.addEventListener('click', function() {
            shareOptions.style.display = shareOptions.style.display === 'none' ? 'flex' : 'none';
        });
    }
});

