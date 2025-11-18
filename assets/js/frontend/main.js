/**
 * Main Frontend JavaScript
 */

document.addEventListener("DOMContentLoaded", function() {
    const videos = document.querySelectorAll('.tiktok-video, .explore-video, .creator-video-preview');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');

    let globalMuted = true;
    let globalVolume = 1;
    const viewedVideos = new Set();
    let isScrolling = false;
    let userGestureHandled = false;

    /**
     * Guest storage helpers
     */
    const GuestStorage = {
        LIKED_VIDEOS: 'puna_tiktok_guest_liked_videos',
        SAVED_VIDEOS: 'puna_tiktok_guest_saved_videos',
        LIKED_COMMENTS: 'puna_tiktok_guest_liked_comments',
        COMMENTS: 'puna_tiktok_guest_comments',
        GUEST_ID: 'puna_tiktok_guest_id',
        
        getGuestId: function() {
            try {
                let guestId = localStorage.getItem(this.GUEST_ID);
                if (!guestId) {
                    guestId = 'guest_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
                    localStorage.setItem(this.GUEST_ID, guestId);
                }
                return guestId;
            } catch (e) {
                return 'guest_' + Date.now().toString(36);
            }
        },
        
        getLikedVideos: function() {
            try {
                const data = localStorage.getItem(this.LIKED_VIDEOS);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                return [];
            }
        },
        
        setLikedVideos: function(videoIds) {
            try {
                localStorage.setItem(this.LIKED_VIDEOS, JSON.stringify(videoIds));
            } catch (e) {
            }
        },
        
        toggleLikeVideo: function(postId) {
            const liked = this.getLikedVideos();
            const index = liked.indexOf(postId);
            if (index > -1) {
                liked.splice(index, 1);
            } else {
                liked.push(postId);
            }
            this.setLikedVideos(liked);
            return liked.indexOf(postId) > -1;
        },
        
        getSavedVideos: function() {
            try {
                const data = localStorage.getItem(this.SAVED_VIDEOS);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                return [];
            }
        },
        
        setSavedVideos: function(videoIds) {
            try {
                localStorage.setItem(this.SAVED_VIDEOS, JSON.stringify(videoIds));
            } catch (e) {
            }
        },
        
        toggleSaveVideo: function(postId) {
            const saved = this.getSavedVideos();
            const index = saved.indexOf(postId);
            if (index > -1) {
                saved.splice(index, 1);
            } else {
                saved.push(postId);
            }
            this.setSavedVideos(saved);
            return saved.indexOf(postId) > -1;
        },
        
        getLikedComments: function() {
            try {
                const data = localStorage.getItem(this.LIKED_COMMENTS);
                return data ? JSON.parse(data) : [];
            } catch (e) {
                return [];
            }
        },
        
        setLikedComments: function(commentIds) {
            try {
                localStorage.setItem(this.LIKED_COMMENTS, JSON.stringify(commentIds));
            } catch (e) {
            }
        },
        
        toggleLikeComment: function(commentId) {
            const liked = this.getLikedComments();
            const index = liked.indexOf(commentId);
            if (index > -1) {
                liked.splice(index, 1);
            } else {
                liked.push(commentId);
            }
            this.setLikedComments(liked);
            return liked.indexOf(commentId) > -1;
        },
        
        getAllData: function() {
            return {
                liked_videos: this.getLikedVideos(),
                saved_videos: this.getSavedVideos(),
                liked_comments: this.getLikedComments()
            };
        },
        
        clearAll: function() {
            localStorage.removeItem(this.LIKED_VIDEOS);
            localStorage.removeItem(this.SAVED_VIDEOS);
            localStorage.removeItem(this.LIKED_COMMENTS);
            localStorage.removeItem(this.COMMENTS);
        }
    };

    /**
     * Check if user is logged in
     */
    function isLoggedIn() {
        if (typeof puna_tiktok_ajax === 'undefined') return false;
        return puna_tiktok_ajax.is_logged_in === true || puna_tiktok_ajax.is_logged_in === '1' || puna_tiktok_ajax.is_logged_in === 1;
    }

    /**
     * Format number
     */
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    const megaVideoCache = new Map();

    async function ensureMegaVideoSource(video) {
        if (!video) return;
        const megaLink = video.dataset.megaLink;
        if (!megaLink || video.dataset.megaLoaded === '1') {
            return;
        }
        if (video.dataset.megaLoading === '1') {
            return;
        }
        if (typeof mega === 'undefined' || typeof mega.File !== 'function') {
            return;
        }

        video.dataset.megaLoading = '1';

        try {
            if (megaVideoCache.has(megaLink)) {
                video.src = megaVideoCache.get(megaLink);
                video.dataset.megaLoaded = '1';
                video.dataset.megaLoading = '0';
                return;
            }

            const megaFile = await Promise.resolve(mega.File.fromURL(megaLink));
            const buffer = await megaFile.downloadBuffer();
            const mime = video.dataset.megaMime || 'video/mp4';
            const objectUrl = URL.createObjectURL(new Blob([buffer], { type: mime }));
            megaVideoCache.set(megaLink, objectUrl);
            video.src = objectUrl;
            video.dataset.megaLoaded = '1';
            
            video.addEventListener('loadedmetadata', () => {
                video.classList.add('loaded');
            });
        } catch (error) {
            showToast?.('Cannot load video from Mega.nz', 'error');
        } finally {
            video.dataset.megaLoading = '0';
        }
    }

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
     * Send AJAX request
     */
    function sendAjaxRequest(action, params = {}) {
        return fetch(puna_tiktok_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: action,
                nonce: puna_tiktok_ajax.like_nonce,
                ...params
            })
        }).then(response => response.json());
    }

    // ============================================
    // VIDEO VOLUME CONTROL
    // ============================================
    
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

    // ============================================
    // VIDEO AUTOPLAY & INTERSECTION OBSERVER
    // ============================================
    
    const observerOptions = {
        root: mainContent,
        rootMargin: '0px',
        threshold: 0.5
    };
    
    // Observer to ensure video-row is always centered when entering viewport
    let isAutoScrolling = false;
    const videoRowObserver = new IntersectionObserver((entries) => {
        if (isAutoScrolling) return; // Prevent infinite loop
        
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const videoRow = entry.target;
                const rect = videoRow.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const expectedTop = (viewportHeight - rect.height) / 2;
                const offset = Math.abs(rect.top - expectedTop);
                
                // If video-row is not centered (offset > 50px), scroll to center
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
                if (entry.target.dataset.megaLink) {
                    ensureMegaVideoSource(entry.target).then(() => {
                        if (entry.target.classList.contains('tiktok-video')) {
                applyVideoVolumeSettings(entry.target);
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
                } else {
                    if (entry.target.classList.contains('tiktok-video')) {
                        applyVideoVolumeSettings(entry.target);
                        const playPromise = entry.target.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(e => {
                                if (e.name !== 'AbortError') {
                                }
                            });
                        }
                    }
                }
                
                if (entry.target.classList.contains('tiktok-video') && entry.target.dataset.postId && !viewedVideos.has(entry.target.dataset.postId)) {
                    setTimeout(() => {
                        if (entry.isIntersecting) {
                            viewedVideos.add(entry.target.dataset.postId);
                            incrementVideoView(entry.target.dataset.postId);
                        }
                    }, 1000);
                }
            } else {
                entry.target.pause();
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
        
        if (video.dataset.megaLink) {
            ensureMegaVideoSource(video);
        }
        
        if (video.classList.contains('tiktok-video')) {
        observer.observe(video);
        
            const videoRow = video.closest('.video-row');
            if (videoRow) {
                videoRowObserver.observe(videoRow);
            }
        }
        
        video.addEventListener('click', function() {
            if (this.paused) {
                if (this.dataset.megaLink && !this.dataset.megaLoaded) {
                    ensureMegaVideoSource(this).then(() => {
                        const playPromise = this.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(e => {
                                if (e.name !== 'AbortError') {
                                }
                            });
                        }
                    });
                } else {
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
            if (current.dataset.megaLink) {
                ensureMegaVideoSource(current);
            }
            applyVideoVolumeSettings(current);
            current.play().catch(() => {});
        }
    }
    
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, playVisibleVideoOnce, { once: true, passive: true });
    });

    // ============================================
    // VIDEO NAVIGATION
    // ============================================
    
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
            
            // Use smooth scroll with center alignment for TikTok-like experience
            targetElement.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center',
                inline: 'nearest'
            });
            
            // Update navigation state after a short delay to allow scroll to complete
            setTimeout(() => {
                updateNavDisabledState();
            }, 100);
        }
    }

    /**
     * Cập nhật trạng thái disable của navigation buttons
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

    // Event listener cho navigation buttons
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

    // Navigation with swipe on mobile - Simple TikTok-like implementation
    let touchStartY = 0;
    let touchStartTime = 0;
    let lastSwipeTime = 0;
    const SWIPE_THRESHOLD = 50; // Minimum distance for swipe (px)
    const SWIPE_COOLDOWN = 300; // Minimum time between swipes (ms)
    
    if (mainContent) {
        mainContent.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
            touchStartTime = Date.now();
        }, { passive: true });
        
        mainContent.addEventListener('touchend', function(e) {
            const now = Date.now();
            
            // Cooldown check - prevent rapid swipes
            if (now - lastSwipeTime < SWIPE_COOLDOWN) {
                return;
            }
            
            const endY = e.changedTouches[0].clientY;
            const diffY = touchStartY - endY;
            const timeDiff = now - touchStartTime;
            
            // Only handle quick swipes (fast and far enough)
            // Ignore slow scrolls (time > 200ms) or small movements
            if (Math.abs(diffY) > SWIPE_THRESHOLD && timeDiff < 200) {
                const direction = diffY > 0 ? 1 : -1; // Swipe up = next, swipe down = previous
                
                // Update last swipe time
                lastSwipeTime = now;
                
                // Scroll to next/previous video smoothly
                scrollToSibling(direction);
            }
        }, { passive: true });
    }

    // Initialize navigation state
    updateNavDisabledState();

    // ============================================
    // LIKE/UNLIKE VIDEO
    // ============================================
    
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="like"], .interaction-item[data-action="like"]');
        if (!actionItem) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) return;
        
        // Add animation
        actionItem.classList.add('liking');
        setTimeout(() => actionItem.classList.remove('liking'), 300);
        
        // Save to localStorage if guest
        if (!isLoggedIn()) {
            const isLiked = GuestStorage.toggleLikeVideo(postId);
            actionItem.classList.toggle('liked', isLiked);
        }
        
        // Send AJAX request (guests can like)
        sendAjaxRequest('puna_tiktok_toggle_like', { 
            post_id: postId,
            action_type: actionItem.classList.contains('liked') ? 'like' : 'unlike'
        })
        .then(data => {
            if (data.success) {
                const isLiked = data.data.is_liked;
                const likes = data.data.likes;
                
                // Update UI
                actionItem.classList.toggle('liked', isLiked);
                
                // Save to localStorage if guest
                if (!isLoggedIn()) {
                    if (isLiked) {
                        const liked = GuestStorage.getLikedVideos();
                        if (liked.indexOf(postId) === -1) {
                            liked.push(postId);
                            GuestStorage.setLikedVideos(liked);
                        }
                    } else {
                        const liked = GuestStorage.getLikedVideos();
                        const index = liked.indexOf(postId);
                        if (index > -1) {
                            liked.splice(index, 1);
                            GuestStorage.setLikedVideos(liked);
                        }
                    }
                }
                
                // Update likes count (handle both .count and .stat-count)
                const countElement = actionItem.querySelector('.count') || actionItem.querySelector('.stat-count');
                if (countElement) {
                    countElement.textContent = formatNumber(likes);
                }
            } else {
                // Revert if guest
                if (!isLoggedIn()) {
                    GuestStorage.toggleLikeVideo(postId);
                    actionItem.classList.toggle('liked');
                }
            }
        })
        .catch(error => {
            // Revert if guest
            if (!isLoggedIn()) {
                GuestStorage.toggleLikeVideo(postId);
                actionItem.classList.toggle('liked');
            }
        });
    });

    // Event listener for save button (handle both action-item and interaction-item)
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="save"], .interaction-item[data-action="save"]');
        if (!actionItem) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) return;
        
        // Add animation
        actionItem.classList.add('saving');
        setTimeout(() => actionItem.classList.remove('saving'), 300);
        
        // Save to localStorage if guest
        if (!isLoggedIn()) {
            const isSaved = GuestStorage.toggleSaveVideo(postId);
            if (isSaved) {
                actionItem.classList.add('saved');
            } else {
                actionItem.classList.remove('saved');
            }
        }
        
        // Send AJAX request (guests can save)
        sendAjaxRequest('puna_tiktok_toggle_save', { 
            post_id: postId,
            action_type: actionItem.classList.contains('saved') ? 'save' : 'unsave'
        })
        .then(data => {
            if (data.success) {
                const isSaved = data.data.is_saved;
                const saves = data.data.saves;
                
                // Update UI
                if (isSaved) {
                    actionItem.classList.add('saved');
                } else {
                    actionItem.classList.remove('saved');
                }
                
                // Save to localStorage if guest
                if (!isLoggedIn()) {
                    if (isSaved) {
                        const saved = GuestStorage.getSavedVideos();
                        if (saved.indexOf(postId) === -1) {
                            saved.push(postId);
                            GuestStorage.setSavedVideos(saved);
                        }
                    } else {
                        const saved = GuestStorage.getSavedVideos();
                        const index = saved.indexOf(postId);
                        if (index > -1) {
                            saved.splice(index, 1);
                            GuestStorage.setSavedVideos(saved);
                        }
                    }
                }
                
                // Update saves count (handle both .count and .stat-count)
                const countEl = actionItem.querySelector('.count') || actionItem.querySelector('.stat-count');
                if (countEl && saves !== undefined) {
                    countEl.textContent = formatNumber(saves);
                }
            } else {
                showToast(data.data?.message || 'Có lỗi xảy ra', 'error');
                // Revert if guest
                if (!isLoggedIn()) {
                    GuestStorage.toggleSaveVideo(postId);
                    actionItem.classList.toggle('saved');
                }
            }
        })
        .catch(error => {
            showToast('Có lỗi xảy ra khi lưu video', 'error');
            // Revert nếu là guest
            if (!isLoggedIn()) {
                GuestStorage.toggleSaveVideo(postId);
                actionItem.classList.toggle('saved');
            }
        });
    });

    // ============================================
    // COMMENTS FUNCTIONALITY
    // ============================================
    
    /**
     * Open comments sidebar
     */
    function openCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Close comments sidebar
     */
    function closeCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }

    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="comment"], .interaction-item[data-action="comment-toggle"]');
        if (actionItem && actionItem.dataset.postId) {
            e.preventDefault();
            e.stopPropagation();
            
            // Trang watch video không có overlay, chỉ cần scroll đến phần comments
            const isWatchPage = document.querySelector('.video-watch-page');
            if (isWatchPage) {
                // Scroll đến phần comments (cho phép xem mà không cần login)
                const commentsSection = document.querySelector('.video-watch-comments');
                if (commentsSection) {
                    commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                // Trang feed - mở sidebar (cho phép xem mà không cần login)
                openCommentsSidebar(actionItem.dataset.postId);
            }
        }
    });

    // Event listener cho nút đóng comments
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.close-comments-btn');
        if (closeBtn && closeBtn.dataset.postId) {
            e.preventDefault();
            closeCommentsSidebar(closeBtn.dataset.postId);
        }
    });

    // ============================================
    // ============================================
    // SHARE MODAL
    // ============================================
    
    document.addEventListener('click', function(e) {
        const shareBtn = e.target.closest('.action-item[data-action="share"], .interaction-item[data-action="share"]');
        if (shareBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = shareBtn.dataset.postId;
            if (!postId) return;
            
            openShareModal(postId);
        }
    });
    
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.share-modal-close');
        const overlay = e.target.closest('.share-modal-overlay');
        
        if (closeBtn || overlay) {
            const modal = closeBtn ? closeBtn.closest('.share-modal') : overlay.closest('.share-modal');
            if (modal) {
                closeShareModal(modal);
            }
        }
    });
    
    // Xử lý các tùy chọn chia sẻ
    document.addEventListener('click', function(e) {
        const shareOption = e.target.closest('.share-option');
        if (!shareOption) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const shareType = shareOption.dataset.share;
        const postId = shareOption.dataset.postId;
        
        if (!shareType || !postId) return;
        
        // Get share URL and title from share button (xử lý cả action-item và interaction-item)
        const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
        const shareUrl = shareBtn?.dataset.shareUrl || shareOption.dataset.url || window.location.href;
        const shareTitle = shareBtn?.dataset.shareTitle || document.title;
        
        handleShare(shareType, shareUrl, shareTitle, postId);
    });
    
    function openShareModal(postId) {
        const modal = document.getElementById(`shareModal-${postId}`);
        if (!modal) return;
        
        // Get share data from share button (xử lý cả action-item và interaction-item)
        const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
        if (shareBtn) {
            modal.dataset.shareUrl = shareBtn.dataset.shareUrl || window.location.href;
            modal.dataset.shareTitle = shareBtn.dataset.shareTitle || document.title;
        }
        
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeShareModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    function handleShare(type, url, title, postId) {
        const encodedUrl = encodeURIComponent(url);
        const encodedTitle = encodeURIComponent(title);
        
        switch(type) {
            case 'facebook':
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`, '_blank', 'width=600,height=400');
                break;
                
            case 'zalo':
                window.open(`https://zalo.me/share?url=${encodedUrl}`, '_blank', 'width=600,height=400');
                break;
                
            case 'copy':
                copyToClipboard(url);
                showToast('Đã sao chép liên kết!');
                // Update share count
                updateShareCount(postId);
                break;
                
            case 'instagram':
                // Instagram không hỗ trợ direct share link, mở app hoặc hướng dẫn
                if (navigator.userAgent.match(/Instagram/i)) {
                    window.open(`https://www.instagram.com/`, '_blank');
                } else {
                    showToast('Vui lòng mở Instagram app để chia sẻ');
                }
                break;
                
            case 'email':
                window.location.href = `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
                break;
                
            case 'x':
                window.open(`https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`, '_blank', 'width=600,height=400');
                break;
                
            case 'telegram':
                window.open(`https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`, '_blank', 'width=600,height=400');
                break;
        }
        
        // Update share count for all platforms except copy (already handled)
        if (type !== 'copy') {
            updateShareCount(postId);
        }
        
        // Đóng modal sau khi chia sẻ
        setTimeout(() => {
            const modal = document.getElementById(`shareModal-${postId}`);
            if (modal) {
                closeShareModal(modal);
            }
        }, 500);
    }
    
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }
    
    function updateShareCount(postId) {
        sendAjaxRequest('puna_tiktok_increment_shares', { post_id: postId })
            .then(data => {
                if (data.success && data.data.share_count !== undefined) {
                    // Update share count in action item (xử lý cả action-item và interaction-item)
                    const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
                    if (shareBtn) {
                        const countEl = shareBtn.querySelector('.count') || shareBtn.querySelector('.stat-count');
                        if (countEl) {
                            countEl.textContent = formatNumber(data.data.share_count);
                        }
                    }
                }
            })
            .catch(err => { /* Error updating share count */ });
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.textContent = message;
        
        const iconMap = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        if (iconMap[type]) {
            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            icon.textContent = iconMap[type];
            toast.insertBefore(icon, toast.firstChild);
        }
        
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    document.body.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('comments-overlay')) {
            const postId = e.target.id.replace('comments-overlay-', '');
            closeCommentsSidebar(postId);
        }
    });

    // Đóng khi nhấn Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openOverlay = document.querySelector('.comments-overlay.show');
            if (openOverlay) {
                const postId = openOverlay.id.replace('comments-overlay-', '');
                closeCommentsSidebar(postId);
            }
        }
    });

    /**
     * Xử lý input trong comment field
     */
    function handleCommentInput(input) {
        const container = input.closest('.comment-input-container');
        const btn = container?.querySelector('.submit-comment-btn');
        if (btn) {
            btn.disabled = input.value.trim() === '';
        }
    }

    // Event listener cho comment input
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('comment-input')) {
            handleCommentInput(e.target);
        }
    });

    // Event listener cho reply input
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('reply-input-field')) {
            handleCommentInput(e.target);
        }
    });

    /**
     * Submit comment (top-level hoặc reply)
     */
    document.addEventListener('click', function(e) {
        const submitBtn = e.target.closest('.submit-comment-btn');
        if (!submitBtn || submitBtn.disabled || !submitBtn.dataset.postId) return;
        
        e.preventDefault();
        
        const postId = submitBtn.dataset.postId;
        const parentId = submitBtn.dataset.parentId ? parseInt(submitBtn.dataset.parentId, 10) : 0;
        const container = submitBtn.closest('.comment-input-container');
        const input = container?.querySelector('.comment-input');
        const commentText = input?.value.trim();
        
        if (!commentText) return;
        
        // Disable button và hiển thị loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang đăng...';
        
        // Gửi AJAX request
        const params = {
            post_id: postId,
            comment_text: commentText
        };
        if (parentId > 0) {
            params.parent_id = parentId;
        }
        
        // Thêm guest_id nếu là guest
        if (!isLoggedIn()) {
            params.guest_id = GuestStorage.getGuestId();
            params.guest_name = 'Khách';
        }
        
        sendAjaxRequest('puna_tiktok_add_comment', params)
        .then(data => {
            if (data.success) {
                const commentId = data.data?.comment_id || null;
                if (parentId > 0) {
                    addReplyToList(postId, parentId, commentText, commentId);
                } else {
                    addCommentToList(postId, commentText, commentId);
                }
                
                // Clear input và reset button
                if (input) input.value = '';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đăng';
                submitBtn.removeAttribute('data-parent-id');
                
                // Cập nhật số lượng comments
                updateCommentCount(postId);
            } else {
                showToast('Có lỗi xảy ra khi đăng bình luận.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng';
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Đăng';
        });
    });

    /**
     * Thêm comment mới vào danh sách
     */
    function addCommentToList(postId, commentText, commentId) {
        // Tìm comments list từ overlay (feed) hoặc từ video-watch-comments (single page)
        let commentsList = null;
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            commentsList = overlay.querySelector('.comments-list');
        } else {
            // Single video page - tìm từ .video-watch-comments
            const watchComments = document.querySelector('.video-watch-comments');
            if (watchComments) {
                commentsList = watchComments.querySelector('.comments-list');
            }
        }
        
        if (!commentsList) return;
        
        // Xóa message "no comments" nếu có
        const noComments = commentsList.querySelector('.no-comments');
        if (noComments) noComments.remove();
        
        // Lấy thông tin user hiện tại hoặc guest
        let authorName = 'Bạn';
        let avatarHtml = '';
        let isGuest = false;
        let guestId = '';
        
        if (isLoggedIn() && puna_tiktok_ajax.current_user) {
            authorName = puna_tiktok_ajax.current_user.display_name || 'Bạn';
            const avatarUrl = puna_tiktok_ajax.avatar_url || 'https://via.placeholder.com/40';
            avatarHtml = `<img src="${avatarUrl}" alt="${authorName}" class="comment-avatar" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">`;
        } else {
            guestId = GuestStorage.getGuestId();
            const guestIdShort = guestId.substring(6, 14);
            authorName = 'Khách #' + guestIdShort;
            isGuest = true;
            
            const idPart = guestId.replace('guest_', '');
            const lastTwo = idPart.substring(idPart.length - 2);
            const initials = 'K' + lastTwo.toUpperCase();
            
            const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52BE80', '#E74C3C', '#3498DB', '#9B59B6', '#1ABC9C', '#F39C12', '#E67E22', '#34495E', '#16A085', '#27AE60', '#2980B9'];
            const hash = guestId.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
            const bgColor = colors[hash % colors.length];
            
            avatarHtml = `<div class="avatar-initials comment-avatar" style="width: 40px; height: 40px; background-color: ${bgColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 16px;">${initials}</div>`;
        }
        
        const finalCommentId = commentId || ('temp-' + Date.now());
        
        const commentElement = document.createElement('div');
        commentElement.className = 'comment-item';
        commentElement.setAttribute('data-comment-id', finalCommentId);
        commentElement.innerHTML = `
            <a href="#" class="comment-avatar-link">
                ${avatarHtml}
            </a>
            <div class="comment-content">
                <div class="comment-header">
                    <a href="#" class="comment-author-link">
                        <strong class="comment-author">${authorName}</strong>
                    </a>
                </div>
                <p class="comment-text">${commentText}</p>
                <div class="comment-footer">
                    <span class="comment-date">Vừa xong</span>
                    <a href="#" class="reply-link" data-comment-id="${finalCommentId}">Trả lời</a>
                </div>
            </div>
            <div class="comment-right-actions">
                <div class="comment-actions">
                    <button class="comment-options-btn" title="Tùy chọn">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <div class="comment-options-dropdown">
                        <button class="comment-action-delete" data-comment-id="${finalCommentId}">
                            <i class="fa-solid fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                <div class="comment-likes" data-comment-id="${finalCommentId}">
                    <i class="fa-regular fa-heart"></i>
                    <span>0</span>
                </div>
            </div>
        `;
        
        commentsList.insertBefore(commentElement, commentsList.firstChild);
        commentsList.scrollTop = 0;
    }

    /**
     * Thêm reply mới vào danh sách
     */
    function addReplyToList(postId, parentId, commentText, commentId) {
        // Tìm comment item từ overlay (feed) hoặc từ video-watch-comments (single page)
        let parentItem = null;
        let container = null;
        
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            container = overlay;
            parentItem = overlay.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
        } else {
            // Single video page
            const watchComments = document.querySelector('.video-watch-comments');
            if (watchComments) {
                container = watchComments;
                parentItem = watchComments.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
            }
        }
        
        if (!parentItem || !container) return;
        
        // Tìm hoặc tạo replies section
        let repliesSection = container.querySelector(`.comment-replies[data-parent-id="${parentId}"]`);
        
        if (!repliesSection) {
            repliesSection = document.createElement('div');
            repliesSection.className = 'comment-replies';
            repliesSection.setAttribute('data-parent-id', parentId);
            parentItem.parentElement.insertBefore(repliesSection, parentItem.nextSibling);
        }
        
        // Lấy thông tin user hiện tại hoặc guest
        let authorName = 'Bạn';
        let avatarHtml = '';
        
        if (isLoggedIn() && puna_tiktok_ajax.current_user) {
            authorName = puna_tiktok_ajax.current_user.display_name || 'Bạn';
            const avatarUrl = puna_tiktok_ajax.avatar_url || 'https://via.placeholder.com/40';
            avatarHtml = `<img src="${avatarUrl}" alt="${authorName}" class="comment-avatar" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">`;
        } else {
            const guestId = GuestStorage.getGuestId();
            const guestIdShort = guestId.substring(6, 14);
            authorName = 'Khách #' + guestIdShort;
            
            const idPart = guestId.replace('guest_', '');
            const lastTwo = idPart.substring(idPart.length - 2);
            const initials = 'K' + lastTwo.toUpperCase();
            
            const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52BE80', '#E74C3C', '#3498DB', '#9B59B6', '#1ABC9C', '#F39C12', '#E67E22', '#34495E', '#16A085', '#27AE60', '#2980B9'];
            const hash = guestId.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
            const bgColor = colors[hash % colors.length];
            
            avatarHtml = `<div class="avatar-initials comment-avatar" style="width: 40px; height: 40px; background-color: ${bgColor}; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 16px;">${initials}</div>`;
        }
        
        const finalCommentId = commentId || ('temp-' + Date.now());
        
        const replyElement = document.createElement('div');
        replyElement.className = 'comment-item comment-reply';
        replyElement.setAttribute('data-comment-id', finalCommentId);
        replyElement.innerHTML = `
            <a href="#" class="comment-avatar-link">
                ${avatarHtml}
            </a>
            <div class="comment-content">
                <div class="comment-header">
                    <a href="#" class="comment-author-link">
                        <strong class="comment-author">${authorName}</strong>
                    </a>
                </div>
                <p class="comment-text">${commentText}</p>
                <div class="comment-footer">
                    <span class="comment-date">Vừa xong</span>
                    <a href="#" class="reply-link" data-comment-id="${finalCommentId}">Trả lời</a>
                </div>
            </div>
            <div class="comment-right-actions">
                <div class="comment-actions">
                    <button class="comment-options-btn" title="Tùy chọn">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                    <div class="comment-options-dropdown">
                        <button class="comment-action-delete" data-comment-id="${finalCommentId}">
                            <i class="fa-solid fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                <div class="comment-likes" data-comment-id="${finalCommentId}">
                    <i class="fa-regular fa-heart"></i>
                    <span>0</span>
                </div>
            </div>
        `;
        
        // Xóa reply input container
        const replyInput = repliesSection.querySelector('.reply-input-container');
        if (replyInput) replyInput.remove();
        
        // Xử lý show more button
        const showMoreBtn = repliesSection.querySelector('.show-more-replies-btn');
        const moreContainer = repliesSection.querySelector('.more-replies-container');
        
        if (showMoreBtn && moreContainer) {
            repliesSection.insertBefore(replyElement, showMoreBtn);
            
            const currentLoaded = parseInt(showMoreBtn.dataset.loaded, 10) || 0;
            const total = parseInt(showMoreBtn.dataset.total, 10) || 0;
            const newLoaded = currentLoaded + 1;
            const newRemaining = total - newLoaded;
            
            showMoreBtn.dataset.loaded = newLoaded;
            
            if (newRemaining > 0) {
                showMoreBtn.textContent = `Xem thêm phản hồi (${newRemaining})`;
            } else {
                const remainingReplies = moreContainer.querySelectorAll('.comment-item');
                remainingReplies.forEach(r => repliesSection.insertBefore(r, showMoreBtn));
                showMoreBtn.style.display = 'none';
                moreContainer.style.display = 'none';
            }
        } else {
            repliesSection.appendChild(replyElement);
        }
        
        replyElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Cập nhật số lượng comments
     */
    function updateCommentCount(postId) {
        // Cập nhật trong video sidebar (xử lý cả action-item và interaction-item)
        const videoSidebar = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count, .interaction-item[data-action="comment-toggle"][data-post-id="${postId}"] .stat-count`);
        if (videoSidebar) {
            const currentCount = parseInt(videoSidebar.textContent.replace(/[^\d]/g, '')) || 0;
            videoSidebar.textContent = formatNumber(currentCount + 1);
        }
        
        // Cập nhật trong comments header (overlay)
        const commentsHeader = document.querySelector(`#comments-overlay-${postId} .comments-header h3`);
        if (commentsHeader) {
            const currentCount = parseInt(commentsHeader.textContent.match(/\d+/)?.[0]) || 0;
            commentsHeader.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
        
        // Cập nhật trong comments tab (single video page)
        const commentsTab = document.querySelector(`.comments-tab[data-tab="comments"]`);
        if (commentsTab) {
            const currentCount = parseInt(commentsTab.textContent.match(/\d+/)?.[0]) || 0;
            commentsTab.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
    }

    /**
     * Xử lý reply link click - hiển thị reply input
     */
    document.addEventListener('click', function(e) {
        const replyLink = e.target.closest('.reply-link');
        if (!replyLink || !replyLink.dataset.commentId) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const parentCommentId = parseInt(replyLink.dataset.commentId, 10);
        
        // Xử lý reply (guest có thể reply)
            const commentItem = replyLink.closest('.comment-item');
        const overlay = replyLink.closest('.comments-overlay');
        
        // Lấy postId từ nhiều nguồn
        let postId = '';
        if (overlay) {
            postId = overlay.id.replace('comments-overlay-', '');
        } else {
            // Nếu không có overlay (single-video page), tìm từ comment input hoặc video element
            const commentInput = document.querySelector('.comment-input[data-post-id]');
            if (commentInput) {
                postId = commentInput.dataset.postId;
            } else {
                const videoElement = document.querySelector('.tiktok-video[data-post-id]');
                if (videoElement) {
                    postId = videoElement.dataset.postId;
                } else {
                    // Fallback: lấy từ URL hoặc global
                    const urlMatch = window.location.pathname.match(/\/(\d+)\//);
                    if (urlMatch) {
                        postId = urlMatch[1];
                    }
                }
            }
        }
        
        if (!postId) {
            return;
        }
        
        // Xóa reply input hiện tại nếu có
        const existingInput = document.querySelector('.reply-input-container');
        if (existingInput) existingInput.remove();
        
        // Tìm replies section hiện có
        let repliesSection = null;
        let nextSibling = commentItem.nextElementSibling;
        
        while (nextSibling) {
            if (nextSibling.classList?.contains('comment-replies') && 
                nextSibling.dataset.parentId === parentCommentId.toString()) {
                repliesSection = nextSibling;
                break;
            }
            if (nextSibling.classList?.contains('comment-item') && 
                !nextSibling.classList.contains('comment-reply')) {
                break;
            }
            nextSibling = nextSibling.nextElementSibling;
        }
        
        // Tạo reply input container
        const replyContainer = document.createElement('div');
        replyContainer.className = 'reply-input-container';
        replyContainer.style.paddingLeft = commentItem.classList.contains('comment-reply') ? '52px' : '28px';
        replyContainer.innerHTML = `
            <div class="comment-input-container reply-input">
                <input type="text" 
                       class="comment-input reply-input-field" 
                       placeholder="Viết phản hồi..." 
                       data-post-id="${postId}"
                       data-parent-id="${parentCommentId}">
                <div class="comment-input-actions">
                    <button class="comment-action-btn" title="Gắn thẻ người dùng">
                        <i class="fa-solid fa-at"></i>
                    </button>
                    <button class="comment-action-btn" title="Emoji">
                        <i class="fa-regular fa-face-smile"></i>
                    </button>
                    <div class="comment-submit-actions">
                        <button class="submit-comment-btn" 
                                data-post-id="${postId}" 
                                data-parent-id="${parentCommentId}"
                                disabled>Đăng</button>
                        <button class="cancel-reply-btn" title="Hủy">Hủy</button>
                    </div>
                </div>
            </div>
        `;
        
            // Insert reply input
            if (repliesSection) {
                repliesSection.insertBefore(replyContainer, repliesSection.firstChild);
            } else {
                commentItem.parentElement.insertBefore(replyContainer, commentItem.nextSibling);
            }
            
            // Focus vào input
            const input = replyContainer.querySelector('.reply-input-field');
            if (input) setTimeout(() => input.focus(), 100);
            
            // Event listeners cho reply input
            handleCommentInput(replyContainer.querySelector('.reply-input-field'));
    });

    // Hủy reply
    document.addEventListener('click', function(e) {
        const cancelBtn = e.target.closest('.cancel-reply-btn');
        if (cancelBtn) {
            e.preventDefault();
            e.stopPropagation();
            const container = cancelBtn.closest('.reply-input-container');
            if (container) container.remove();
        }
    });

    // Hiển thị thêm replies
    document.addEventListener('click', function(e) {
        const showMoreBtn = e.target.closest('.show-more-replies-btn');
        if (showMoreBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const parentId = showMoreBtn.dataset.parentId;
            const moreContainer = document.querySelector(`.more-replies-container[data-parent-id="${parentId}"]`);
            
            if (moreContainer) {
                moreContainer.classList.add('show');
                moreContainer.style.display = 'block';
                showMoreBtn.style.display = 'none';
            }
        }
    });

    // Submit comment bằng Enter
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' || e.shiftKey) return;
        
        if (e.target.classList.contains('comment-input') && !e.target.classList.contains('reply-input-field')) {
            e.preventDefault();
            const container = e.target.closest('.comment-input-container');
            const submitBtn = container?.querySelector('.submit-comment-btn');
            if (submitBtn && !submitBtn.disabled) submitBtn.click();
        }
        
        if (e.target.classList.contains('reply-input-field')) {
            e.preventDefault();
            const container = e.target.closest('.reply-input-container');
            const submitBtn = container?.querySelector('.submit-comment-btn');
            if (submitBtn && !submitBtn.disabled) submitBtn.click();
        }
    });

    // ============================================
    // COMMENT ACTIONS (DELETE, LIKE, REPORT)
    // ============================================
    
    /**
     * Xóa comment
     */
    document.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.comment-action-delete');
        if (!delBtn) return;
        
        // Không cần kiểm tra đăng nhập nữa vì guest cũng có thể xóa comment của mình
        
        const item = delBtn.closest('.comment-item');
        // Tìm overlay (feed) hoặc video-watch-comments (single page)
        const overlay = delBtn.closest('.comments-overlay');
        const watchComments = delBtn.closest('.video-watch-comments');
        
        let postId = '';
        if (overlay) {
            postId = overlay.id.replace('comments-overlay-', '');
        } else if (watchComments) {
            // Tìm postId từ comment input hoặc video element
            const commentInput = watchComments.querySelector('.comment-input[data-post-id]');
            if (commentInput) {
                postId = commentInput.dataset.postId;
            } else {
                const videoElement = document.querySelector('.tiktok-video[data-post-id]');
                if (videoElement) {
                    postId = videoElement.dataset.postId;
                }
            }
        }
        
        const commentsList = delBtn.closest('.comments-list');
        const sidebar = delBtn.closest('.comments-sidebar');
        
        let commentId = delBtn.dataset.commentId;
        const isTempId = commentId && commentId.toString().startsWith('temp-');
        
        // Đóng dropdown
        const dropdown = delBtn.closest('.comment-options-dropdown');
        if (dropdown) dropdown.classList.remove('show');
        
        const isReply = item?.classList.contains('comment-reply');
        let repliesCount = 0;
        
        // Đếm replies nếu là top-level comment
        if (!isReply && item) {
            let nextSibling = item.nextElementSibling;
            while (nextSibling) {
                if (nextSibling.classList?.contains('comment-replies')) {
                    const replies = nextSibling.querySelectorAll('.comment-item.comment-reply');
                    repliesCount = replies.length;
                    
                    const moreContainer = nextSibling.querySelector('.more-replies-container');
                    if (moreContainer) {
                        const hiddenReplies = moreContainer.querySelectorAll('.comment-item.comment-reply');
                        repliesCount += hiddenReplies.length;
                    }
                    
                    nextSibling.remove();
                    break;
                }
                if (nextSibling.classList?.contains('comment-item') && 
                    !nextSibling.classList.contains('comment-reply')) {
                    break;
                }
                nextSibling = nextSibling.nextElementSibling;
            }
        }
        
        // Xóa khỏi UI ngay lập tức
        if (item) item.remove();
        
        const totalCountToSubtract = 1 + repliesCount;
        
        // Cập nhật counts
        if (sidebar) {
            const header = sidebar.querySelector('.comments-header h3');
            if (header) {
                const m = header.textContent.match(/\d+/);
                if (m) {
                    const newCount = Math.max(0, parseInt(m[0], 10) - totalCountToSubtract);
                    header.textContent = `Bình luận (${formatNumber(newCount)})`;
                }
            }
        }
        
        // Cập nhật trong video sidebar (xử lý cả action-item và interaction-item)
        if (postId) {
            const sidebarCount = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count, .interaction-item[data-action="comment-toggle"][data-post-id="${postId}"] .stat-count`);
            if (sidebarCount) {
                const current = parseInt(sidebarCount.textContent.replace(/[^\d]/g, ''), 10) || 0;
                const newCount = Math.max(0, current - totalCountToSubtract);
                sidebarCount.textContent = formatNumber(newCount);
            }
        }
        
        // Cập nhật trong comments tab (single video page)
        if (watchComments) {
            const commentsTab = document.querySelector(`.comments-tab[data-tab="comments"]`);
            if (commentsTab) {
                const m = commentsTab.textContent.match(/\d+/);
                if (m) {
                    const newCount = Math.max(0, parseInt(m[0], 10) - totalCountToSubtract);
                    commentsTab.textContent = `Bình luận (${formatNumber(newCount)})`;
                }
            }
        }
        
        // Xử lý reply deletion
        if (isReply) {
            const repliesSection = item.closest('.comment-replies');
            if (repliesSection) {
                const remainingReplies = repliesSection.querySelectorAll('.comment-item.comment-reply');
                const showMoreBtn = repliesSection.querySelector('.show-more-replies-btn');
                const moreContainer = repliesSection.querySelector('.more-replies-container');
                
                if (showMoreBtn && remainingReplies.length < 3 && moreContainer) {
                    const hiddenReplies = moreContainer.querySelectorAll('.comment-item');
                    if (hiddenReplies.length > 0) {
                        const nextReply = hiddenReplies[0];
                        repliesSection.insertBefore(nextReply, showMoreBtn);
                        
                        const remaining = hiddenReplies.length - 1;
                        if (remaining > 0) {
                            showMoreBtn.textContent = `Xem thêm phản hồi (${remaining})`;
                            showMoreBtn.dataset.loaded = parseInt(showMoreBtn.dataset.loaded, 10) - 1;
                        } else {
                            showMoreBtn.style.display = 'none';
                            moreContainer.style.display = 'none';
                        }
                    }
                }
                
                if (remainingReplies.length === 0) {
                    repliesSection.remove();
                }
            }
        } else {
            if (commentsList && commentsList.querySelectorAll('.comment-item:not(.comment-reply)').length === 0) {
                const noCommentsMsg = document.createElement('div');
                noCommentsMsg.className = 'no-comments';
                noCommentsMsg.innerHTML = '<p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>';
                commentsList.appendChild(noCommentsMsg);
            }
        }
        
        // Bỏ qua nếu là temp ID
        if (isTempId) return;
        
        commentId = parseInt(commentId, 10);
        if (!commentId || isNaN(commentId)) {
            return;
        }
        
        // Gửi delete request
        // Send guest_id if guest
        const params = { comment_id: commentId };
        if (!isLoggedIn()) {
            params.guest_id = GuestStorage.getGuestId();
        }
        
        sendAjaxRequest('puna_tiktok_delete_comment', params)
        .then(res => {
            if (!res.success) {
                // Delete comment error
            }
        })
        .catch(err => {
            // Delete comment request error
        });
    });

    /**
     * Like/Unlike comment
     */
    document.addEventListener('click', function(e) {
        const likesElement = e.target.closest('.comment-likes');
        if (!likesElement || !likesElement.dataset.commentId) return;
        
        const heartIcon = likesElement.querySelector('i.fa-heart');
        const span = likesElement.querySelector('span');
        if (!heartIcon || (e.target !== heartIcon && e.target !== span && !heartIcon.contains(e.target))) {
            return;
        }
        
        const commentId = parseInt(likesElement.dataset.commentId, 10);
        if (!commentId) return;
        
        const isLiked = heartIcon.classList.contains('fa-solid');
        const currentLikes = parseInt(span.textContent.replace(/[^\d]/g, ''), 10) || 0;
        
        // Lưu vào localStorage nếu là guest
        if (!isLoggedIn()) {
            GuestStorage.toggleLikeComment(commentId);
        }
        
        // Optimistic update
        if (isLiked) {
            heartIcon.classList.remove('fa-solid', 'liked');
            heartIcon.classList.add('fa-regular');
            span.textContent = formatNumber(Math.max(0, currentLikes - 1));
        } else {
            heartIcon.classList.remove('fa-regular');
            heartIcon.classList.add('fa-solid', 'liked');
            span.textContent = formatNumber(currentLikes + 1);
        }
        
        likesElement.classList.add('liking');
        setTimeout(() => likesElement.classList.remove('liking'), 300);
        
        // Gửi AJAX request
        sendAjaxRequest('puna_tiktok_toggle_comment_like', { 
            comment_id: commentId,
            action_type: isLiked ? 'unlike' : 'like'
        })
        .then(data => {
            if (data.success) {
                const isLikedNow = data.data.is_liked;
                const likes = data.data.likes;
                
                if (isLikedNow) {
                    heartIcon.classList.remove('fa-regular');
                    heartIcon.classList.add('fa-solid', 'liked');
                } else {
                    heartIcon.classList.remove('fa-solid', 'liked');
                    heartIcon.classList.add('fa-regular');
                }
                
                // Save to localStorage if guest
                if (!isLoggedIn()) {
                    if (isLikedNow) {
                        const liked = GuestStorage.getLikedComments();
                        if (liked.indexOf(commentId) === -1) {
                            liked.push(commentId);
                            GuestStorage.setLikedComments(liked);
                        }
                    } else {
                        const liked = GuestStorage.getLikedComments();
                        const index = liked.indexOf(commentId);
                        if (index > -1) {
                            liked.splice(index, 1);
                            GuestStorage.setLikedComments(liked);
                        }
                    }
                }
                
                span.textContent = formatNumber(likes);
            } else {
                // Revert optimistic update
                if (isLiked) {
                    heartIcon.classList.remove('fa-regular');
                    heartIcon.classList.add('fa-solid', 'liked');
                } else {
                    heartIcon.classList.remove('fa-solid', 'liked');
                    heartIcon.classList.add('fa-regular');
                }
                span.textContent = formatNumber(currentLikes);
                // Revert localStorage nếu là guest
                if (!isLoggedIn()) {
                    GuestStorage.toggleLikeComment(commentId);
                }
            }
        })
        .catch(error => {
            // Revert optimistic update
            if (isLiked) {
                heartIcon.classList.remove('fa-regular');
                heartIcon.classList.add('fa-solid', 'liked');
            } else {
                heartIcon.classList.remove('fa-solid', 'liked');
                heartIcon.classList.add('fa-regular');
            }
            span.textContent = formatNumber(currentLikes);
            // Revert localStorage nếu là guest
            if (!isLoggedIn()) {
                GuestStorage.toggleLikeComment(commentId);
            }
        });
    });

    /**
     * Báo cáo comment
     */
    document.addEventListener('click', function(e) {
        const repBtn = e.target.closest('.comment-action-report');
        if (!repBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const commentId = repBtn.dataset.commentId;
        sendAjaxRequest('puna_tiktok_report_comment', { comment_id: commentId })
        .then(res => {
            if (res.success) {
                showToast('Đã báo cáo bình luận.', 'success');
            } else {
                showToast(res.data && res.data.message ? res.data.message : 'Không thể báo cáo.', 'error');
            }
        })
        .catch(() => showToast('Lỗi kết nối.', 'error'));
    });

    // ============================================
    // DROPDOWN MENUS
    // ============================================
    
    // Video options menu
    document.addEventListener('click', function(e) {
        const optionsBtn = e.target.closest('.video-options-btn');
        if (optionsBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = optionsBtn.nextElementSibling;
            const isShowing = dropdown?.classList.contains('show');
            
            // Đóng tất cả dropdowns
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
            
            // Toggle current dropdown
            if (!isShowing && dropdown) {
                dropdown.classList.add('show');
            }
        }
    });

    // Comment options menu
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.comment-options-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = btn.nextElementSibling;
            const isShowing = dropdown?.classList.contains('show');
            
            document.querySelectorAll('.comment-options-dropdown').forEach(d => d.classList.remove('show'));
            if (dropdown && !isShowing) {
                dropdown.classList.add('show');
            }
        }
    });

    // Đóng dropdowns khi click bên ngoài
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.comment-actions')) {
            document.querySelectorAll('.comment-options-dropdown').forEach(d => d.classList.remove('show'));
        }
        if (!e.target.closest('.video-options-menu')) {
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
        }
        if (!e.target.closest('.video-info-more-menu')) {
            document.querySelectorAll('.video-info-more-dropdown').forEach(d => d.classList.remove('show'));
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
            
            // Close all other dropdowns
            document.querySelectorAll('.video-info-more-dropdown').forEach(d => d.classList.remove('show'));
            
            // Toggle current dropdown
            if (!isShowing) {
                dropdown.classList.add('show');
            }
        }
    });

    // ============================================
    // VIDEO VIEW COUNT
    // ============================================
    
    /**
     * Tăng số lượt xem video
     */
    function incrementVideoView(postId) {
        if (!postId) return;
        
        sendAjaxRequest('puna_tiktok_increment_view', { post_id: postId })
        .then(data => {
            if (data.success) {
                const viewElement = document.querySelector(`.action-item[data-post-id="${postId}"][data-action="view"] .count`);
                if (viewElement && data.data.formatted_views) {
                    viewElement.textContent = data.data.formatted_views;
                }
            }
        })
        .catch(error => {
            // AJAX error
        });
    }

    // ============================================
    // PROFILE TABS
    // ============================================
    
    // Profile Tabs Switching - Dùng chung cho profile page và author.php
    const profileTabs = document.querySelectorAll('.profile-tab');
    const profileVideosSections = document.querySelectorAll('.profile-videos-section');
    const profileTabContents = document.querySelectorAll('.profile-tab-content');
    
    // Initialize active tab on page load
    function initializeProfileTabs() {
        const activeTab = document.querySelector('.profile-tab.active');
        if (activeTab) {
            const targetTab = activeTab.getAttribute('data-tab');
            
            // Show/hide content based on active tab
            profileVideosSections.forEach(section => {
                if (section.id === targetTab + '-tab') {
                    section.classList.add('active');
                    section.style.display = 'block';
                } else {
                    section.classList.remove('active');
                    section.style.display = 'none';
                }
            });
            
            profileTabContents.forEach(content => {
                if (content.id === targetTab + '-tab') {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        }
    }
    
    // Run on page load
    initializeProfileTabs();
    
    profileTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update active state
            profileTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide content - Support cả profile-videos-section và profile-tab-content
            profileVideosSections.forEach(section => {
                if (section.id === targetTab + '-tab') {
                    section.classList.add('active');
                    section.style.display = 'block';
                } else {
                    section.classList.remove('active');
                    section.style.display = 'none';
                }
            });
            
            profileTabContents.forEach(content => {
                if (content.id === targetTab + '-tab') {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });

    // ============================================
    // SEARCH PANEL
    // ============================================
    
    const searchTrigger = document.getElementById('search-trigger');
    const closeSearchBtn = document.getElementById('close-search');
    const searchPanel = document.getElementById('search-panel');
    const realSearchInput = document.getElementById('real-search-input');

    /**
     * Mở search panel
     */
    function openSearchPanel() {
        document.body.classList.add('search-panel-active');
        setTimeout(() => {
            if (realSearchInput) {
                realSearchInput.focus();
                // Luôn load history khi mở panel (không cần check value)
                loadSearchHistory();
                // Load popular searches
                loadPopularSearches();
            }
        }, 300);
    }

    /**
     * Đóng search panel
     */
    function closeSearchPanel() {
        document.body.classList.remove('search-panel-active');
    }

    // Toggle search panel
    if (searchTrigger) {
        searchTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (document.body.classList.contains('search-panel-active')) {
                closeSearchPanel();
            } else {
                openSearchPanel();
            }
        });
    }

    // Đóng khi click nút close
    if (closeSearchBtn) {
        closeSearchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeSearchPanel();
        });
    }

    // Đóng khi click main-content
    const mainContentEl = document.querySelector('.main-content');
    if (mainContentEl) {
        mainContentEl.addEventListener('click', function(e) {
            if (document.body.classList.contains('search-panel-active')) {
                if (!e.target.closest('.video-nav-btn') && 
                    !e.target.closest('.action-item') &&
                    !e.target.closest('.video-container')) {
                    closeSearchPanel();
                }
            }
        });
    }

    // Ngăn đóng khi click bên trong panel
    if (searchPanel) {
        searchPanel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Đóng bằng phím Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.body.classList.contains('search-panel-active')) {
            closeSearchPanel();
        }
    });

    // ============================================
    // SEARCH SUGGESTIONS & HISTORY
    // ============================================
    
    const searchSuggestionsContainer = document.getElementById('search-suggestions-container');
    const searchSuggestionsList = document.getElementById('search-suggestions-list');
    const searchHistorySection = document.getElementById('search-history-section');
    const searchHistoryList = document.getElementById('search-history-list');
    const searchPopularSection = document.getElementById('search-popular-section');
    const searchLoading = document.getElementById('search-loading');
    const clearHistoryBtn = document.getElementById('clear-history-btn');
    
    let searchDebounceTimer = null;
    let currentSearchQuery = '';
    
    /**
     * Load search history
     */
    function loadSearchHistory() {
        if (!searchHistoryList || !searchHistorySection) return;
        
        // Luôn hiển thị history section
        searchHistorySection.style.display = 'block';
        
        sendAjaxRequest('puna_tiktok_get_search_history', {})
            .then(data => {
                if (data.success && data.data.history && data.data.history.length > 0) {
                    searchHistoryList.innerHTML = '';
                    data.data.history.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'search-history-item';
                        li.innerHTML = `
                            <i class="fa-solid fa-clock"></i>
                            <span>${item.query}</span>
                        `;
                        li.addEventListener('click', function() {
                            if (realSearchInput) {
                                realSearchInput.value = item.query;
                                submitSearch(item.query);
                            }
                        });
                        searchHistoryList.appendChild(li);
                    });
                    if (clearHistoryBtn) clearHistoryBtn.style.display = 'block';
                } else {
                    searchHistoryList.innerHTML = '<li class="search-history-empty">Chưa có lịch sử tìm kiếm</li>';
                    if (clearHistoryBtn) clearHistoryBtn.style.display = 'none';
                }
            })
            .catch(error => {
                // Error loading search history
            });
    }
    
    /**
     * Load search suggestions
     */
    function loadSearchSuggestions(query) {
        if (!query || query.length < 2) {
            searchSuggestionsList.style.display = 'none';
            searchHistorySection.style.display = 'block';
            searchPopularSection.style.display = 'block';
            return;
        }
        
        if (searchLoading) searchLoading.style.display = 'block';
        searchSuggestionsList.style.display = 'none';
        searchHistorySection.style.display = 'none';
        searchPopularSection.style.display = 'none';
        
        sendAjaxRequest('puna_tiktok_search_suggestions', { query: query })
            .then(data => {
                if (searchLoading) searchLoading.style.display = 'none';
                
                if (data.success && data.data.suggestions && data.data.suggestions.length > 0) {
                    searchSuggestionsList.innerHTML = '';
                    data.data.suggestions.forEach(function(suggestion) {
                        const li = document.createElement('li');
                        li.className = 'search-suggestion-item';
                        
                        let icon = 'fa-magnifying-glass';
                        if (suggestion.type === 'user') {
                            icon = 'fa-user';
                        } else if (suggestion.type === 'video') {
                            icon = 'fa-video';
                        } else if (suggestion.type === 'history') {
                            icon = 'fa-clock';
                        }
                        
                        li.innerHTML = `
                            <i class="fa-solid ${icon}"></i>
                            <span>${suggestion.text}</span>
                        `;
                        li.addEventListener('click', function() {
                            realSearchInput.value = suggestion.text;
                            submitSearch(suggestion.text);
                        });
                        searchSuggestionsList.appendChild(li);
                    });
                    if (searchSuggestionsList) searchSuggestionsList.style.display = 'block';
                    // Ẩn history và popular khi có suggestions
                    if (searchHistorySection) searchHistorySection.style.display = 'none';
                    if (searchPopularSection) searchPopularSection.style.display = 'none';
                } else {
                    if (searchSuggestionsList) searchSuggestionsList.style.display = 'none';
                    // Hiển thị lại history và popular khi không có suggestions
                    if (searchHistorySection) searchHistorySection.style.display = 'block';
                    if (searchPopularSection) searchPopularSection.style.display = 'block';
                }
            })
            .catch(error => {
                if (searchLoading) searchLoading.style.display = 'none';
            });
    }
    
    /**
     * Save search to history and submit
     */
    function submitSearch(query) {
        if (!query || !query.trim()) return;
        
        // Save to history
        sendAjaxRequest('puna_tiktok_save_search', { query: query.trim() })
            .then(data => {
                // Redirect to search page
                if (searchForm) {
                    window.location.href = searchForm.action + '?s=' + encodeURIComponent(query.trim());
                }
            })
            .catch(error => {
                // Still redirect even if save fails
                if (searchForm) {
                    window.location.href = searchForm.action + '?s=' + encodeURIComponent(query.trim());
                }
            });
    }
    
    /**
     * Clear search history
     */
    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('Bạn có chắc muốn xóa toàn bộ lịch sử tìm kiếm?')) {
                sendAjaxRequest('puna_tiktok_clear_search_history', {})
                    .then(data => {
                        if (data.success) {
                            searchHistoryList.innerHTML = '<li class="search-history-empty">Chưa có lịch sử tìm kiếm</li>';
                            if (clearHistoryBtn) clearHistoryBtn.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        // Error clearing history
                    });
            }
        });
    }
    
    // Load history when panel opens and on focus
    if (realSearchInput) {
        // Load history on focus (luôn hiển thị)
        realSearchInput.addEventListener('focus', function() {
            loadSearchHistory();
        });
        
        // Load suggestions as user types
        realSearchInput.addEventListener('input', function() {
            const query = realSearchInput.value.trim();
            currentSearchQuery = query;
            
            // Clear previous timer
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }
            
            // Debounce: wait 300ms after user stops typing
            searchDebounceTimer = setTimeout(function() {
                if (query === currentSearchQuery) { // Make sure query hasn't changed
                    loadSearchSuggestions(query);
                }
            }, 300);
        });
    }
    
    // Submit search form
    const searchForm = document.getElementById('search-form');
    if (searchForm && realSearchInput) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchValue = realSearchInput.value.trim();
            if (searchValue) {
                submitSearch(searchValue);
            }
        });
        
        // Submit khi nhấn Enter trong input
        realSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchValue = realSearchInput.value.trim();
                if (searchValue) {
                    submitSearch(searchValue);
                }
            }
        });
    }
    
    /**
     * Load related searches for search results page
     */
    function loadRelatedSearches(currentQuery) {
        const relatedList = document.getElementById('related-searches-list');
        if (!relatedList) return;
        
        if (!currentQuery || currentQuery.trim().length < 2) {
            // Load popular searches as fallback
            loadPopularSearchesForSidebar(relatedList);
            return;
        }
        
        sendAjaxRequest('puna_tiktok_get_related_searches', { query: currentQuery.trim() })
            .then(data => {
                if (data.success && data.data.related && data.data.related.length > 0) {
                    relatedList.innerHTML = '';
                    data.data.related.forEach(function(item) {
                        const li = document.createElement('li');
                        const searchUrl = window.location.pathname + '?s=' + encodeURIComponent(item.query);
                        li.innerHTML = `
                            <a href="${searchUrl}" class="search-suggestion-link">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>${item.query}</span>
                            </a>
                        `;
                        relatedList.appendChild(li);
                    });
                } else {
                    // Fallback to popular searches
                    loadPopularSearchesForSidebar(relatedList);
                }
            })
            .catch(error => {
                // Fallback to popular searches
                loadPopularSearchesForSidebar(relatedList);
            });
    }
    
    /**
     * Load popular searches for sidebar (fallback)
     */
    function loadPopularSearchesForSidebar(container) {
        sendAjaxRequest('puna_tiktok_get_popular_searches', {})
            .then(data => {
                if (data.success && data.data.popular && data.data.popular.length > 0) {
                    container.innerHTML = '';
                    data.data.popular.forEach(function(item) {
                        const li = document.createElement('li');
                        const searchUrl = window.location.pathname + '?s=' + encodeURIComponent(item.query);
                        li.innerHTML = `
                            <a href="${searchUrl}" class="search-suggestion-link">
                                <i class="fa-solid fa-fire"></i>
                                <span>${item.query}</span>
                            </a>
                        `;
                        container.appendChild(li);
                    });
                } else {
                    // Show default
                    container.innerHTML = `
                        <li><a href="${window.location.pathname}?s=Sơn+Tùng+M-TP" class="search-suggestion-link"><i class="fa-solid fa-magnifying-glass"></i><span>Sơn Tùng M-TP</span></a></li>
                        <li><a href="${window.location.pathname}?s=Nhạc+TikTok" class="search-suggestion-link"><i class="fa-solid fa-magnifying-glass"></i><span>Nhạc TikTok</span></a></li>
                        <li><a href="${window.location.pathname}?s=Video+hài" class="search-suggestion-link"><i class="fa-solid fa-magnifying-glass"></i><span>Video hài</span></a></li>
                    `;
                }
            })
            .catch(error => {
                // Error loading popular searches
            });
    }
    
    /**
     * Load popular searches for search panel
     */
    function loadPopularSearches() {
        const popularList = document.getElementById('search-popular-list');
        if (!popularList) return;
        
        sendAjaxRequest('puna_tiktok_get_popular_searches', {})
            .then(data => {
                if (data.success && data.data.popular && data.data.popular.length > 0) {
                    popularList.innerHTML = '';
                    data.data.popular.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'search-popular-item';
                        li.innerHTML = `
                            <i class="fa-solid fa-fire"></i>
                            <span>${item.query}</span>
                        `;
                        li.style.cursor = 'pointer';
                        li.addEventListener('click', function(e) {
                            e.preventDefault();
                            realSearchInput.value = item.query;
                            submitSearch(item.query);
                        });
                        popularList.appendChild(li);
                    });
                } else {
                    // Show default suggestions if no popular searches
                    popularList.innerHTML = `
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Sơn Tùng M-TP</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Nhạc TikTok</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Video hài</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Dance cover</span></li>
                    `;
                    // Attach click handlers to default items
                    const defaultItems = popularList.querySelectorAll('.search-popular-item');
                    defaultItems.forEach(function(item) {
                        item.style.cursor = 'pointer';
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            const searchText = item.querySelector('span')?.textContent.trim();
                            if (searchText && realSearchInput) {
                                realSearchInput.value = searchText;
                                submitSearch(searchText);
                            }
                        });
                    });
                }
            })
            .catch(error => {
                // Show default on error
                if (popularList) {
                    popularList.innerHTML = `
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Sơn Tùng M-TP</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Nhạc TikTok</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Video hài</span></li>
                        <li class="search-popular-item"><i class="fa-solid fa-magnifying-glass"></i><span>Dance cover</span></li>
                    `;
                }
            });
    }
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    // Khởi tạo volume state
    applyVolumeToAllVideos();
    updateGlobalVolumeUI();
    
    // Load related searches if on search results page
    const relatedList = document.getElementById('related-searches-list');
    if (relatedList) {
        // Get search query from URL or input
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('s') || '';
        if (searchQuery) {
            loadRelatedSearches(searchQuery);
        } else {
            // Load popular searches as fallback
            loadPopularSearchesForSidebar(relatedList);
        }
    }
    
    // ============================================
    // VIDEO WATCH PAGE FUNCTIONALITY
    // ============================================
    
    const videoWatchPage = document.querySelector('.video-watch-page');
    if (videoWatchPage) {
        const backBtn = document.getElementById('video-watch-back');
        const commentTabs = document.querySelectorAll('.comments-tab');
        const commentTabContents = document.querySelectorAll('.comments-tab-content');
        const copyLinkBtn = document.querySelector('.copy-link-btn');
        
        // Auto-play video (video sẽ dùng chung hàm với trang index)
        const watchVideo = videoWatchPage.querySelector('.tiktok-video');
        if (watchVideo) {
            // Load Mega video first if needed
            if (watchVideo.dataset.megaLink) {
                ensureMegaVideoSource(watchVideo).then(() => {
                    // Video source loaded, now apply settings and play
            applyVideoVolumeSettings(watchVideo);
                    applyVolumeToAllVideos();
                    
                    // Auto-play after source is loaded
                    const playPromise = watchVideo.play();
                    if (playPromise !== undefined) {
                        playPromise.catch(e => {
                            // Auto-play prevented
                        });
                    }
                }).catch(err => {
                    // Failed to load Mega video
                });
            } else {
                // Regular video - apply settings and play immediately
                applyVideoVolumeSettings(watchVideo);
            applyVolumeToAllVideos();
            
            // Auto-play
                const playPromise = watchVideo.play();
                if (playPromise !== undefined) {
                    playPromise.catch(e => {
                // Auto-play prevented
            });
                }
            }
        }
        
        // Back button
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                if (document.referrer && document.referrer !== window.location.href) {
                    window.history.back();
                } else {
                    window.location.href = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.home_url) ? puna_tiktok_ajax.home_url : '/';
                }
            });
        }
        
        // Comment tabs switching
        commentTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Update active state
                commentTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide content
                commentTabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === targetTab + '-tab-content') {
                        content.classList.add('active');
                    }
                });
            });
        });
        
        // Copy link functionality
        if (copyLinkBtn) {
            copyLinkBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const link = this.getAttribute('data-link') || window.location.href;
                
                // Use Clipboard API
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(link).then(() => {
                        // Show feedback
                        const originalHTML = this.innerHTML;
                        this.innerHTML = '<i class="fa-solid fa-check"></i>';
                        this.style.background = 'var(--puna-primary)';
                        setTimeout(() => {
                            this.innerHTML = originalHTML;
                            this.style.background = '';
                        }, 2000);
                    }).catch(err => {
                        // Failed to copy
                    });
                } else {
                    // Fallback for older browsers
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
                    } catch (err) {
                        // Fallback copy failed
                    }
                    document.body.removeChild(textArea);
                }
            });
        }
        
        // Share options visibility toggle
        const shareBtn = document.querySelector('.interaction-item[data-action="share"]');
        const shareOptions = document.getElementById('video-share-options');
        
        if (shareBtn && shareOptions) {
            shareBtn.addEventListener('click', function() {
                shareOptions.style.display = shareOptions.style.display === 'none' ? 'flex' : 'none';
            });
        }
    }

    // ============================================
    // UPLOAD VIDEO FUNCTIONALITY
    // ============================================
    
    // Chỉ chạy trên trang upload
    if (document.querySelector('.upload-page-wrapper')) {
        initUploadPage();
    }

    function initUploadPage() {
        const dropZone = document.getElementById('uploadDropZone');
        const fileInput = document.getElementById('videoFileInput');
        const selectBtn = document.getElementById('selectVideoBtn');
        const step1 = document.getElementById('uploadStep1');
        const step2 = document.getElementById('uploadStep2');
        const videoPreview = document.getElementById('videoPreview');
        const previewPlaceholder = document.getElementById('previewPlaceholder');
        const descriptionInput = document.getElementById('videoDescription');
        const charCount = document.getElementById('charCount');
        const videoCategorySelect = document.getElementById('videoCategory');
        const publishBtn = document.getElementById('publishVideoBtn');
        const backToStep1Btn = document.getElementById('backToStep1Btn');
        const editVideoBtn = document.getElementById('editVideoBtn');
        const uploadFileInfo = document.getElementById('uploadFileInfo');
        const fileName = document.getElementById('fileName');
        const uploadProgressFill = document.getElementById('uploadProgressFill');
        const uploadProgressText = document.getElementById('uploadProgressText');
        const uploadPercentage = document.getElementById('uploadPercentage');
        const uploadDuration = document.getElementById('uploadDuration');
        const cancelUploadBtn = document.getElementById('cancelUploadBtn');
        const uploadLoadingOverlay = document.getElementById('uploadLoadingOverlay');
        const uploadFileInfoElement = document.getElementById('uploadFileInfo');

        const megaUploader = (typeof window.PunaTikTokMegaUploader === 'function' && puna_tiktok_ajax?.mega)
            ? new window.PunaTikTokMegaUploader(puna_tiktok_ajax.mega)
            : null;

        let selectedVideoFile = null;
        let videoDuration = 0;
        let isUploading = false;

        // Select video button
        if (selectBtn) {
            selectBtn.addEventListener('click', () => {
                fileInput?.click();
            });
        }

        // File input change
        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                handleFileSelect(e.target.files[0]);
            });
        }

        // Drag and drop
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0 && files[0].type.startsWith('video/')) {
                    handleFileSelect(files[0]);
                }
            });
        }

        function handleFileSelect(file) {
            if (!file || !file.type.startsWith('video/')) {
                showToast('Vui lòng chọn file video hợp lệ', 'warning');
                return;
            }

            selectedVideoFile = file;
            
            // Hiển thị file info khi chọn file (nhưng không hiển thị progress bar cho đến khi upload)
            if (fileName) {
                fileName.textContent = file.name;
            }
            
            // Ẩn progress bar khi chọn file (chỉ hiển thị khi upload)
            if (uploadFileInfoElement) {
                uploadFileInfoElement.style.display = 'none';
            }

            // Tạo video element để lấy duration
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = () => {
                window.webkitURL = window.webkitURL || window.URL;
                videoDuration = video.duration;
                if (uploadDuration) {
                    uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
                }
            };
            video.src = URL.createObjectURL(file);

            const videoURL = URL.createObjectURL(file);
            if (videoPreview) {
                videoPreview.src = videoURL;
                videoPreview.style.display = 'block';
            }
            if (previewPlaceholder) {
                previewPlaceholder.style.display = 'none';
            }

            // Chuyển sang step 2
            if (step1) step1.classList.remove('active');
            if (step2) step2.classList.add('active');
            
            // Enable publish button
            if (publishBtn) {
                publishBtn.disabled = false;
            }
        }

        function getVideoResolution(file) {
            // Tạm thời return empty, có thể cải thiện sau
            return '';
        }

        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            if (mins > 0) {
                return `${mins} phút ${secs} giây`;
            }
            return `${secs} giây`;
        }

        // Character counter
        if (descriptionInput && charCount) {
            descriptionInput.addEventListener('input', () => {
                charCount.textContent = descriptionInput.value.length;
            });
        }


        // Back to step 1
        if (backToStep1Btn) {
            backToStep1Btn.addEventListener('click', () => {
                if (step2) step2.classList.remove('active');
                if (step1) step1.classList.add('active');
            });
        }

        // Publish video
        if (publishBtn) {
            publishBtn.addEventListener('click', () => {
                if (!selectedVideoFile) {
                    showToast('Vui lòng chọn video', 'warning');
                    return;
                }

                publishVideo();
            });
        }

        // Cancel upload
        if (cancelUploadBtn) {
            cancelUploadBtn.addEventListener('click', () => {
                if (isUploading) {
                    showToast('Đang tải lên Mega, vui lòng đợi hoàn tất.', 'warning');
                }
                resetUploadProgress();
            });
        }

        async function publishVideo() {
            if (!selectedVideoFile) return;
            if (!megaUploader) {
                showToast('Mega uploader chưa sẵn sàng.', 'error');
                return;
            }
            if (isUploading) return;

            isUploading = true;
            if (publishBtn) {
                publishBtn.disabled = true;
            }

            updateUploadProgress(0, selectedVideoFile.size);
            if (uploadLoadingOverlay) {
                uploadLoadingOverlay.classList.add('show');
            }


            try {
                const megaResult = await megaUploader.uploadFile(selectedVideoFile, (uploaded, total) => {
                    updateUploadProgress(uploaded, total);
                });
                await finalizePost(megaResult);
            } catch (error) {
                showToast(error?.message || 'Không thể tải video lên Mega.nz.', 'error');
                if (uploadLoadingOverlay) {
                    uploadLoadingOverlay.classList.remove('show');
                }
                resetUploadProgress();
            }
        }

        async function finalizePost(megaResult) {
            const formData = new FormData();
            formData.append('action', 'puna_tiktok_upload_video');
            formData.append('nonce', puna_tiktok_ajax?.nonce || '');
            formData.append('mega_link', megaResult?.link || '');
            formData.append('mega_node_id', megaResult?.nodeId || '');
            formData.append('video_name', megaResult?.name || selectedVideoFile.name);
            formData.append('video_size', megaResult?.size || selectedVideoFile.size || 0);
            formData.append('description', descriptionInput?.value || '');
            formData.append('category_id', videoCategorySelect?.value || '');

            try {
                const response = await fetch(puna_tiktok_ajax?.ajax_url || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();

                let payload = null;
                try {
                    payload = JSON.parse(text);
                } catch (parseError) {
                    throw new Error('Máy chủ trả về dữ liệu không hợp lệ. Vui lòng đăng nhập lại và thử lại.');
                }

                if (payload.success) {
                    const redirectUrl = payload.data?.redirect_url;
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else if (puna_tiktok_ajax?.current_user?.user_id) {
                        window.location.href = `/author/${puna_tiktok_ajax.current_user.user_id}/`;
                    } else {
                        window.location.href = '/';
                    }
                } else {
                    showToast(payload.data?.message || 'Có lỗi xảy ra khi lưu video.', 'error');
                    resetUploadProgress();
                }
            } catch (error) {
                showToast('Không thể lưu thông tin video. Vui lòng thử lại.', 'error');
                resetUploadProgress();
            }
        }

        function updateUploadProgress(uploaded, total) {
            if (uploadFileInfoElement) {
                uploadFileInfoElement.style.display = 'block';
            }
            if (fileName) {
                fileName.textContent = selectedVideoFile?.name || 'video.mp4';
            }

            const safeTotal = total || selectedVideoFile?.size || 0;
            const percentComplete = safeTotal > 0 ? (uploaded / safeTotal) * 100 : 0;

                    if (uploadProgressFill) {
                        uploadProgressFill.style.width = percentComplete + '%';
                    }
                    if (uploadProgressText) {
                uploadProgressText.textContent = `${formatFileSize(uploaded)} / ${formatFileSize(safeTotal)}`;
                    }
                    if (uploadPercentage) {
                        uploadPercentage.textContent = Math.round(percentComplete) + '%';
                    }
            if (uploadDuration && videoDuration > 0) {
                            uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
            }
        }

        function resetUploadProgress() {
            isUploading = false;
            if (uploadProgressFill) uploadProgressFill.style.width = '0%';
            if (uploadProgressText) uploadProgressText.textContent = '0MB / 0MB';
            if (uploadPercentage) uploadPercentage.textContent = '0%';
            if (publishBtn) publishBtn.disabled = false;
            if (uploadFileInfoElement) {
                uploadFileInfoElement.style.display = 'none';
            }
            if (uploadLoadingOverlay) {
                uploadLoadingOverlay.classList.remove('show');
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Hashtag functionality
        const hashtagPlaceholder = document.getElementById('hashtagPlaceholder');
        const hashtagDropdown = document.getElementById('hashtagDropdown');
        const hashtagList = document.getElementById('hashtagList');
        const hashtagCloseBtn = document.getElementById('hashtagCloseBtn');
        let allHashtags = [];

        // Load popular hashtags
        function loadPopularHashtags() {
            if (!hashtagList) return Promise.resolve();
            
            hashtagList.innerHTML = '<div class="hashtag-loading">Đang tải...</div>';
            
            return sendAjaxRequest('puna_tiktok_get_popular_hashtags', { limit: 100 })
                .then(data => {
                    if (data.success && data.data.hashtags) {
                        allHashtags = data.data.hashtags;
                        filteredHashtags = allHashtags;
                        return allHashtags;
                    } else {
                        hashtagList.innerHTML = '<div class="hashtag-empty">Không có hashtag nào</div>';
                        return [];
                    }
                })
                .catch(error => {
                    hashtagList.innerHTML = '<div class="hashtag-error">Có lỗi xảy ra khi tải hashtag</div>';
                    return [];
                });
        }

        // Render hashtags
        function renderHashtags(hashtags) {
            if (!hashtagList) return;
            
            if (hashtags.length === 0) {
                hashtagList.innerHTML = '<div class="hashtag-empty">Không tìm thấy hashtag nào</div>';
                return;
            }
            
            hashtagList.innerHTML = hashtags.map(hashtag => `
                <div class="hashtag-item" data-hashtag="${hashtag.name}">
                    <span class="hashtag-name">#${hashtag.name}</span>
                    <span class="hashtag-count">${hashtag.count} video</span>
                </div>
            `).join('');
            
            // Add click handlers
            hashtagList.querySelectorAll('.hashtag-item').forEach(item => {
                item.addEventListener('click', () => {
                    const hashtagName = item.dataset.hashtag;
                    insertHashtag(hashtagName);
                });
            });
        }

        // Insert hashtag into textarea
        function insertHashtag(hashtagName) {
            if (!descriptionInput) return;
            
            const cursorPos = descriptionInput.selectionStart;
            const textBefore = descriptionInput.value.substring(0, cursorPos);
            const textAfter = descriptionInput.value.substring(cursorPos);
            
            // Find the last "#" before cursor
            const lastHashIndex = textBefore.lastIndexOf('#');
            
            if (lastHashIndex === -1) {
                // No "#" found, insert "#" + hashtag name
                let hashtagText = `#${hashtagName}`;
                if (textBefore && !textBefore.match(/\s$/)) {
                    hashtagText = ' ' + hashtagText;
                }
                if (textAfter && !textAfter.match(/^\s/)) {
                    hashtagText = hashtagText + ' ';
                }
                const newText = textBefore + hashtagText + textAfter;
                descriptionInput.value = newText;
                
                if (charCount) {
                    charCount.textContent = newText.length;
                }
                
                const newCursorPos = cursorPos + hashtagText.length;
                descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
            } else {
                // Replace text from "#" to cursor with "#" + hashtag name
                const textBeforeHash = textBefore.substring(0, lastHashIndex);
                const hashtagText = `#${hashtagName}`;
                const spaceAfter = (textAfter && !textAfter.match(/^\s/)) ? ' ' : '';
                const newText = textBeforeHash + hashtagText + spaceAfter + textAfter;
                descriptionInput.value = newText;
                
                if (charCount) {
                    charCount.textContent = newText.length;
                }
                
                const newCursorPos = textBeforeHash.length + hashtagText.length + spaceAfter.length;
                descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
            }
            
            descriptionInput.focus();
            
            // Close dropdown
            if (hashtagDropdown) {
                hashtagDropdown.classList.remove('show');
            }
        }

        // Click hashtag placeholder - only insert "#"
        if (hashtagPlaceholder) {
            hashtagPlaceholder.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (!descriptionInput) return;
                
                // Insert "#" into textarea at cursor position
                const cursorPos = descriptionInput.selectionStart;
                const textBefore = descriptionInput.value.substring(0, cursorPos);
                const textAfter = descriptionInput.value.substring(cursorPos);
                
                // Check if we need to add space before
                let hashtagPrefix = '#';
                if (textBefore && !textBefore.match(/\s$/)) {
                    hashtagPrefix = ' #';
                }
                
                const newText = textBefore + hashtagPrefix + textAfter;
                descriptionInput.value = newText;
                
                // Update character count
                if (charCount) {
                    charCount.textContent = newText.length;
                }
                
                // Set cursor position after "#"
                const newCursorPos = cursorPos + hashtagPrefix.length;
                descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
                descriptionInput.focus();
            });
        }
        
        // Detect hashtag typing and show suggestions
        if (descriptionInput) {
            descriptionInput.addEventListener('input', (e) => {
                checkAndShowHashtagSuggestions();
            });
            
            descriptionInput.addEventListener('keyup', (e) => {
                checkAndShowHashtagSuggestions();
            });
            
            descriptionInput.addEventListener('click', () => {
                checkAndShowHashtagSuggestions();
            });
        }
        
        // Function to check if user is typing hashtag and show suggestions
        function checkAndShowHashtagSuggestions() {
            if (!descriptionInput || !hashtagDropdown) return;
            
            const cursorPos = descriptionInput.selectionStart;
            const textBefore = descriptionInput.value.substring(0, cursorPos);
            
            // Find the last "#" before cursor
            const lastHashIndex = textBefore.lastIndexOf('#');
            
            if (lastHashIndex === -1) {
                // No "#" found, hide dropdown
                hashtagDropdown.classList.remove('show');
                return;
            }
            
            // Check if there's a space or newline after "#" (hashtag is complete)
            const textAfterHash = textBefore.substring(lastHashIndex + 1);
            if (textAfterHash.match(/[\s\n]/)) {
                // Hashtag is complete (has space after), hide dropdown
                hashtagDropdown.classList.remove('show');
                return;
            }
            
            // Get the text after "#" (the search term)
            const searchTerm = textAfterHash.toLowerCase().trim();
            
            // Show dropdown and filter hashtags
            if (allHashtags.length === 0) {
                // Load hashtags first
                loadPopularHashtags().then(() => {
                    filterAndShowHashtags(searchTerm);
                });
            } else {
                filterAndShowHashtags(searchTerm);
            }
        }
        
        // Filter and show hashtags based on search term
        function filterAndShowHashtags(searchTerm) {
            if (!hashtagDropdown || !hashtagList) return;
            
            let filtered = allHashtags;
            
            if (searchTerm) {
                // Filter hashtags that start with search term
                filtered = allHashtags.filter(hashtag => 
                    hashtag.name.toLowerCase().startsWith(searchTerm)
                );
            }
            
            // Show dropdown
            hashtagDropdown.classList.add('show');
            
            // Render filtered hashtags
            renderHashtags(filtered);
        }

        // Close hashtag dropdown
        if (hashtagCloseBtn) {
            hashtagCloseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (hashtagDropdown) {
                    hashtagDropdown.classList.remove('show');
                }
            });
        }

        // Remove search input functionality - we'll use textarea input instead

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (hashtagDropdown && hashtagDropdown.classList.contains('show')) {
                if (!hashtagDropdown.contains(e.target) && !hashtagPlaceholder.contains(e.target)) {
                    hashtagDropdown.classList.remove('show');
                }
            }
        });
    }

    // ============================================
    // DELETE VIDEO
    // ============================================
    
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-video-item');
        if (!deleteBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = deleteBtn.dataset.postId;
        if (!postId) return;
        
        // Confirm deletion
        if (!confirm('Bạn có chắc chắn muốn xóa video này? Hành động này không thể hoàn tác.')) {
            return;
        }
        
        // Close dropdown if open
        const dropdown = deleteBtn.closest('.video-options-dropdown, .video-info-more-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
        
        // Send AJAX request
        sendAjaxRequest('puna_tiktok_delete_video', {
            post_id: postId
        })
        .then(data => {
            if (data.success) {
                // Show success message
                if (data.data?.message) {
                    showToast(data.data.message);
                }
                
                // Redirect to home page or profile
                const redirectUrl = data.data?.redirect_url || (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.home_url) || window.location.origin;
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);
            } else {
                // Show error message
                const errorMsg = data.data?.message || 'Có lỗi xảy ra khi xóa video.';
                showToast(errorMsg, 'error');
            }
        })
        .catch(error => {
            showToast('Có lỗi xảy ra khi xóa video. Vui lòng thử lại.', 'error');
        });
    });

    /**
     * Load guest state from localStorage
     */
    function loadGuestState() {
        if (isLoggedIn()) return; // Chỉ load cho guest
        
        const likedVideos = GuestStorage.getLikedVideos();
        const savedVideos = GuestStorage.getSavedVideos();
        const likedComments = GuestStorage.getLikedComments();
        
        // Update like buttons
        likedVideos.forEach(postId => {
            const likeBtn = document.querySelector(`.action-item[data-action="like"][data-post-id="${postId}"], .interaction-item[data-action="like"][data-post-id="${postId}"]`);
            if (likeBtn) {
                likeBtn.classList.add('liked');
            }
        });
        
        // Update save buttons
        savedVideos.forEach(postId => {
            const saveBtn = document.querySelector(`.action-item[data-action="save"][data-post-id="${postId}"], .interaction-item[data-action="save"][data-post-id="${postId}"]`);
            if (saveBtn) {
                saveBtn.classList.add('saved');
            }
        });
        
        // Update comment like buttons
        likedComments.forEach(commentId => {
            const commentLikes = document.querySelector(`.comment-likes[data-comment-id="${commentId}"]`);
            if (commentLikes) {
                const heartIcon = commentLikes.querySelector('i.fa-heart');
                if (heartIcon) {
                    heartIcon.classList.remove('fa-regular');
                    heartIcon.classList.add('fa-solid', 'liked');
                }
            }
        });
    }
    
    // Load guest state on page load
    loadGuestState();

    /**
     * Explore tabs functionality
     */
    const exploreTabsContainer = document.getElementById('explore-tabs');
    const exploreTabs = document.querySelectorAll('#explore-tabs .tab');
    const exploreGrid = document.getElementById('explore-grid');
    
    // Shared variables for drag detection
    let isDragging = false;
    let dragStartX = 0;
    let dragStartScrollLeft = 0;
    let dragThreshold = 5; // Minimum distance to consider it a drag
    
    // Enable mouse wheel scrolling for tabs on desktop
    if (exploreTabsContainer) {
        // Mouse wheel scrolling (convert vertical to horizontal)
        exploreTabsContainer.addEventListener('wheel', function(e) {
            if (e.deltaY !== 0) {
                e.preventDefault();
                this.scrollLeft += e.deltaY;
            }
        }, { passive: false });
        
        // Drag scrolling - can drag from anywhere including tabs
        exploreTabsContainer.addEventListener('mousedown', function(e) {
            isDragging = false;
            dragStartX = e.pageX;
            dragStartScrollLeft = exploreTabsContainer.scrollLeft;
            exploreTabsContainer.style.cursor = 'grabbing';
        });
        
        exploreTabsContainer.addEventListener('mouseleave', function() {
            isDragging = false;
            dragStartX = 0;
            exploreTabsContainer.style.cursor = 'grab';
        });
        
        exploreTabsContainer.addEventListener('mouseup', function() {
            // Reset after a short delay to allow click event to check isDragging
            setTimeout(() => {
                isDragging = false;
                dragStartX = 0;
            }, 10);
            exploreTabsContainer.style.cursor = 'grab';
        });
        
        exploreTabsContainer.addEventListener('mousemove', function(e) {
            if (dragStartX === 0) return;
            
            const currentX = e.pageX;
            const diffX = Math.abs(currentX - dragStartX);
            
            // If moved more than threshold, it's a drag
            if (diffX > dragThreshold) {
                isDragging = true;
                e.preventDefault();
                const walk = (currentX - dragStartX) * 2;
                exploreTabsContainer.scrollLeft = dragStartScrollLeft - walk;
            }
        });
    }
    
    if (exploreTabs.length > 0 && exploreGrid) {
        exploreTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                // Prevent click if user was dragging
                if (isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                
                const tabType = this.getAttribute('data-tab');
                const categoryId = this.getAttribute('data-category-id');
                
                // Update active tab
                exploreTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show loading state
                exploreGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: #999;"></i></div>';
                
                // Determine tab type
                let requestTabType = 'trending';
                if (tabType === 'foryou') {
                    requestTabType = 'foryou';
                } else if (tabType && tabType.startsWith('category-')) {
                    requestTabType = 'category';
                }
                
                // Send AJAX request
                const formData = new FormData();
                formData.append('action', 'puna_tiktok_get_explore_videos');
                formData.append('tab_type', requestTabType);
                if (categoryId) {
                    formData.append('category_id', categoryId);
                }
                if (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.like_nonce) {
                    formData.append('nonce', puna_tiktok_ajax.like_nonce);
                }
                
                const ajaxUrl = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.ajax_url) ? puna_tiktok_ajax.ajax_url : '/wp-admin/admin-ajax.php';
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data && data.data.videos) {
                        renderExploreVideos(data.data.videos);
                    } else {
                        const errorMsg = data.data?.message || 'Không có video';
                        exploreGrid.innerHTML = '<div class="explore-empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;"><i class="fa-solid fa-video-slash" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i><h3 style="color: #666; margin-bottom: 10px;">Không có video</h3><p style="color: #999;">' + errorMsg + '</p></div>';
                    }
                })
                .catch(() => {
                    exploreGrid.innerHTML = '<div class="explore-empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;"><i class="fa-solid fa-exclamation-triangle" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i><h3 style="color: #666; margin-bottom: 10px;">Có lỗi xảy ra</h3><p style="color: #999;">Vui lòng thử lại sau.</p></div>';
                });
            });
        });
    }
    
    /**
     * Render explore videos
     */
    function renderExploreVideos(videos) {
        if (!videos || videos.length === 0) {
            exploreGrid.innerHTML = '<div class="explore-empty-state" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;"><i class="fa-solid fa-video-slash" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i><h3 style="color: #666; margin-bottom: 10px;">Không có video</h3><p style="color: #999;">Chưa có video nào.</p></div>';
            return;
        }
        
        exploreGrid.innerHTML = '';
        
        videos.forEach(video => {
            const videoCard = createExploreVideoCard(video);
            exploreGrid.appendChild(videoCard);
        });
        
        // Reinitialize video observers for new videos
        const newVideos = exploreGrid.querySelectorAll('.explore-video');
        newVideos.forEach(video => {
            video.muted = true;
            video.setAttribute('muted', '');
            video.playsInline = true;
            video.setAttribute('playsinline', '');
            
            if (video.dataset.megaLink) {
                ensureMegaVideoSource(video);
            }
        });
    }
    
    /**
     * Create explore video card element
     */
    function createExploreVideoCard(video) {
        const card = document.createElement('a');
        card.href = video.permalink || '#';
        card.className = 'explore-card';
        card.setAttribute('aria-label', 'Video');
        
        const isMegaVideo = video.video_url && video.video_url.indexOf('mega.nz') !== -1;
        const videoUrl = video.video_url || '';
        const views = formatNumber(video.views || 0);
        
        card.innerHTML = `
            <div class="media-wrapper ratio-9x16">
                <video class="explore-video" muted playsinline loading="lazy" ${isMegaVideo ? `data-mega-link="${videoUrl}"` : ''}>
                    <source src="${isMegaVideo ? '' : videoUrl}" type="video/mp4" ${isMegaVideo ? `data-mega-src="${videoUrl}"` : ''}>
                </video>
                <div class="video-overlay">
                    <div class="play-icon">
                        <i class="fa-solid fa-play"></i>
                    </div>
                </div>
                <div class="video-views-overlay">
                    <i class="fa-solid fa-play"></i>
                    <span>${views}</span>
                </div>
            </div>
        `;
        
        return card;
    }
});