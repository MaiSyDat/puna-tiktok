/**
 * Video playback functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const videos = document.querySelectorAll('.tiktok-video, .taxonomy-video, .creator-video-preview');
    const mainContent = document.querySelector('.main-content');
    
    let globalMuted = true;
    let globalVolume = 1;
    const viewedVideos = new Set();
    let userGestureHandled = false;

    /**
     * Get current video
     */
    function getCurrentVideo() {
        const videoList = document.querySelectorAll('.tiktok-video');
        return Array.from(videoList).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
    }

    /**
     * Apply video volume
     */
    function applyVideoVolumeSettings(video) {
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        
        if (globalMuted) {
            video.muted = true;
            video.setAttribute('muted', '');
        } else {
            video.muted = false;
            video.removeAttribute('muted');
            if (typeof video.volume === 'number') {
                video.volume = globalVolume;
            }
        }
    }

    /**
     * Áp dụng volume state cho tất cả video
     */
    function applyVolumeToAllVideos() {
        const videoList = document.querySelectorAll('.tiktok-video');
        videoList.forEach(video => {
            video.muted = globalMuted;
            if (globalMuted) {
                video.setAttribute('muted', '');
            } else {
                video.removeAttribute('muted');
            }
            if (!globalMuted && typeof video.volume === 'number') {
                video.volume = globalVolume;
            }
        });
    }

    /**
     * Cập nhật UI của volume controls
     */
    function updateGlobalVolumeUI() {
        const wrappers = document.querySelectorAll('.volume-control-wrapper');
        wrappers.forEach(wrapper => {
            wrapper.classList.toggle('muted', globalMuted);
            const btn = wrapper.querySelector('.volume-toggle-btn');
            const slider = wrapper.querySelector('.volume-slider');
            
            if (btn) {
                btn.innerHTML = globalMuted
                    ? '<i class="fa-solid fa-volume-xmark"></i>'
                    : (Math.round(globalVolume * 100) < 50
                        ? '<i class="fa-solid fa-volume-low"></i>'
                        : '<i class="fa-solid fa-volume-high"></i>');
            }
            if (slider) {
                const targetVal = globalMuted ? 0 : Math.round(globalVolume * 100);
                if (String(slider.value) !== String(targetVal)) {
                    slider.value = targetVal;
                }
            }
        });
    }

    document.addEventListener('click', function(e) {
        const volumeToggleBtn = e.target.closest('.volume-toggle-btn');
        if (volumeToggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            globalMuted = !globalMuted;
            if (!globalMuted && globalVolume === 0) {
                globalVolume = 1;
            }
            applyVolumeToAllVideos();
            updateGlobalVolumeUI();
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('volume-slider')) {
            const slider = e.target;
            const value = Math.max(0, Math.min(100, parseInt(slider.value, 10) || 0));
            globalVolume = value / 100;
            globalMuted = value === 0;
            applyVolumeToAllVideos();
            updateGlobalVolumeUI();
        }
    });

    // Video autoplay & intersection observer
    const observerOptions = {
        root: mainContent,
        rootMargin: '0px',
        threshold: 0.5
    };
    
    let isAutoScrolling = false;
    const videoRowObserver = new IntersectionObserver((entries) => {
        if (isAutoScrolling) return;
        
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const videoRow = entry.target;
                const rect = videoRow.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const expectedTop = (viewportHeight - rect.height) / 2;
                const offset = Math.abs(rect.top - expectedTop);
                
                if (offset > 50) {
                    isAutoScrolling = true;
                    videoRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        isAutoScrolling = false;
                    }, 500);
                }
            }
        });
    }, {
        root: mainContent,
        rootMargin: '0px',
        threshold: 0.3
    });
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Reset video to the beginning when returning to viewport
                if (entry.target.currentTime > 0) {
                    entry.target.currentTime = 0;
                }
                
                // All videos are Mega videos
                if (typeof ensureMegaVideoSource !== 'undefined') {
                    ensureMegaVideoSource(entry.target).then(() => {
                        if (entry.target.classList.contains('tiktok-video')) {
                            applyVideoVolumeSettings(entry.target);
                            // Make sure the video starts from the beginning
                            entry.target.currentTime = 0;
                            const playPromise = entry.target.play();
                            if (playPromise !== undefined) {
                                playPromise.catch(e => {
                                    if (e.name !== 'AbortError') {
                                    }
                                });
                            }
                        }
                    }).catch(err => {
                    });
                }
                
                if (entry.target.classList.contains('tiktok-video') && entry.target.dataset.postId && !viewedVideos.has(entry.target.dataset.postId)) {
                    setTimeout(() => {
                        if (entry.isIntersecting) {
                            viewedVideos.add(entry.target.dataset.postId);
                            if (typeof incrementVideoView !== 'undefined') {
                                incrementVideoView(entry.target.dataset.postId);
                            }
                        }
                    }, 1000);
                }
            } else {
                // When the video leaves the viewport: pause and reset to the beginning
                entry.target.pause();
                entry.target.currentTime = 0;
            }
        });
    }, observerOptions);

    videos.forEach(video => {
        video.muted = true;
        video.setAttribute('muted', '');
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        
        video.addEventListener('loadedmetadata', () => {
            video.classList.add('loaded');
            
            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            if (videoWidth && videoHeight) {
                const aspectRatio = videoWidth / videoHeight;
                if (aspectRatio > 1.2) {
                    video.dataset.aspectRatio = 'landscape';
                } else if (aspectRatio < 0.8) {
                    video.dataset.aspectRatio = 'portrait';
                } else {
                    video.dataset.aspectRatio = 'square';
                }
            }
        });
        
        if (video.readyState >= 1) {
            video.classList.add('loaded');
            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            if (videoWidth && videoHeight) {
                const aspectRatio = videoWidth / videoHeight;
                if (aspectRatio > 1.2) {
                    video.dataset.aspectRatio = 'landscape';
                } else if (aspectRatio < 0.8) {
                    video.dataset.aspectRatio = 'portrait';
                } else {
                    video.dataset.aspectRatio = 'square';
                }
            }
        }
        
        // All videos are Mega videos - always load via Mega
        if (typeof ensureMegaVideoSource !== 'undefined' && video.dataset.megaLink) {
            // For taxonomy-video and profile cards, load preview (first frame)
            if (video.classList.contains('taxonomy-video') || video.closest('.profile-video-card')) {
                ensureMegaVideoSource(video).then(() => {
                    // Set to first frame for thumbnail preview
                    if (video.readyState >= 2) {
                        video.currentTime = 0.1;
                        video.pause();
                    } else {
                        video.addEventListener('loadedmetadata', () => {
                            video.currentTime = 0.1;
                            video.pause();
                        }, { once: true });
                    }
                }).catch(() => {});
            } else {
                // For main feed videos, just load source
                ensureMegaVideoSource(video);
            }
        }
        
        if (video.classList.contains('tiktok-video')) {
            observer.observe(video);
            
            const videoRow = video.closest('.video-row');
            if (videoRow) {
                videoRowObserver.observe(videoRow);
            }
        }
        
        // Mark taxonomy-video for lazy preview loading
        if (video.classList.contains('taxonomy-video') && !video.closest('.video-row')) {
            video.dataset.needsPreview = '1';
        }
        
        video.addEventListener('click', function() {
            if (this.paused) {
                // Reset to the beginning when user clicks to play
                this.currentTime = 0;
                
                // All videos are Mega videos
                if (!this.dataset.megaLoaded && typeof ensureMegaVideoSource !== 'undefined') {
                    ensureMegaVideoSource(this).then(() => {
                        this.currentTime = 0;
                        const playPromise = this.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(e => {
                                if (e.name !== 'AbortError') {
                                }
                            });
                        }
                    });
                } else {
                    this.currentTime = 0;
                    const playPromise = this.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(e => {
                            if (e.name !== 'AbortError') {
                            }
                        });
                    }
                }
            } else {
                this.pause();
            }
        });
        
        const videoRow = video.closest('.video-row');
        if (videoRow) {
            const postId = videoRow.querySelector('[data-post-id]')?.dataset.postId;
            if (postId) {
                video.dataset.postId = postId;
            }
        }
    });

    /**
     * Play visible video once
     */
    function playVisibleVideoOnce() {
        if (userGestureHandled) return;
        userGestureHandled = true;
        
        const current = getCurrentVideo();
        if (current) {
            // Reset to the beginning when playing for the first time
            current.currentTime = 0;
            
            // All videos are Mega videos
            if (typeof ensureMegaVideoSource !== 'undefined' && current.dataset.megaLink) {
                ensureMegaVideoSource(current).then(() => {
                    current.currentTime = 0;
                });
            }
            applyVideoVolumeSettings(current);
            current.play().catch(() => {});
        }
    }
    
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, playVisibleVideoOnce, { once: true, passive: true });
    });

    // Initialize volume state
    applyVolumeToAllVideos();
    updateGlobalVolumeUI();

    // Lazy load previews for taxonomy-video cards
    const taxonomyVideoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.target.dataset.needsPreview === '1' && entry.target.dataset.megaLink && typeof ensureMegaVideoSource !== 'undefined') {
                ensureMegaVideoSource(entry.target).then(() => {
                    if (entry.target.readyState >= 2) {
                        entry.target.currentTime = 0.1;
                        entry.target.pause();
                    } else {
                        entry.target.addEventListener('loadedmetadata', () => {
                            entry.target.currentTime = 0.1;
                            entry.target.pause();
                        }, { once: true });
                    }
                }).catch(() => {});
                entry.target.removeAttribute('data-needs-preview');
                taxonomyVideoObserver.unobserve(entry.target);
            }
        });
    }, { rootMargin: '100px' });
    
    // Observe all taxonomy-videos that need preview
    document.querySelectorAll('.taxonomy-video[data-needs-preview="1"]').forEach(video => {
        taxonomyVideoObserver.observe(video);
    });

    // Export functions for other modules
    window.applyVideoVolumeSettings = applyVideoVolumeSettings;
    window.applyVolumeToAllVideos = applyVolumeToAllVideos;
    window.getCurrentVideo = getCurrentVideo;
});

