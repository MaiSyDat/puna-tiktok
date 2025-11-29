document.addEventListener("DOMContentLoaded", function() {
    const videos = document.querySelectorAll('.tiktok-video, .taxonomy-video, .creator-video-preview, .search-video-preview');
    const mainContent = document.querySelector('.main-content');
    
    let globalMuted = true;
    let globalVolume = 1;
    const viewedVideos = new Set();
    let userGestureHandled = false;
    
    let youtubeAPIReady = false;
    let youtubePlayersMap = new Map();
    const youtubePendingPlayers = [];

    function loadYouTubeAPI() {
        if (window.YT && window.YT.Player) {
            youtubeAPIReady = true;
            return;
        }
        
        if (document.querySelector('script[src*="youtube.com/iframe_api"]')) {
            return;
        }
        
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
    
    window.onYouTubeIframeAPIReady = function() {
        // Early return if already initialized
        if (youtubeAPIReady) {
            return;
        }
        
        youtubeAPIReady = true;
        
        // Process pending players
        const pendingCopy = [...youtubePendingPlayers];
        youtubePendingPlayers.length = 0;
        pendingCopy.forEach(config => {
            if (config.iframe && config.iframe.dataset.playerInitialized !== 'true' && !youtubePlayersMap.has(config.iframe)) {
                initYouTubePlayer(config.iframe, config.videoId);
            }
        });
        
        // Initialize visible YouTube videos
        document.querySelectorAll('.youtube-player.tiktok-video').forEach(iframe => {
            const videoId = iframe.dataset.youtubeId;
            // Early return checks
            if (!videoId || youtubePlayersMap.has(iframe) || iframe.dataset.playerInitialized === 'true') {
                return;
            }
            
            const rect = iframe.getBoundingClientRect();
            const isInViewport = rect.top >= 0 && 
                               rect.top < window.innerHeight && 
                               rect.bottom > 0;
            
            if (isInViewport) {
                initYouTubePlayer(iframe, videoId);
            }
        });
    };

    function initYouTubePlayer(iframe, videoId) {
        // Early return: Check if already initialized or pending
        if (iframe.dataset.playerInitialized === 'true') {
            return youtubePlayersMap.get(iframe) || null;
        }
        
        if (youtubePlayersMap.has(iframe)) {
            return youtubePlayersMap.get(iframe);
        }
        
        // Early return: API not ready, add to pending
        if (!youtubeAPIReady) {
            const alreadyPending = youtubePendingPlayers.some(p => p.iframe === iframe);
            if (!alreadyPending) {
                youtubePendingPlayers.push({ iframe, videoId });
            }
            return null;
        }
        
        // Mark as initialized before creating player to prevent duplicate initialization
        iframe.dataset.playerInitialized = 'true';
        
        try {
            const player = new YT.Player(iframe, {
                videoId: videoId,
                playerVars: {
                    autoplay: 0,
                    controls: 0,
                    rel: 0,
                    playsinline: 1,
                    loop: 1,
                    playlist: videoId,
                    enablejsapi: 1,
                    mute: globalMuted ? 1 : 0,
                    modestbranding: 1,
                    iv_load_policy: 3,
                    fs: 0,
                    disablekb: 1,
                    cc_load_policy: 0,
                    showinfo: 0
                },
                events: {
                    onReady: function(event) {
                        if (iframe.dataset.playerReady === 'true') {
                            return;
                        }
                        iframe.dataset.playerReady = 'true';
                        
                        try {
                            if (globalMuted) {
                                event.target.mute();
                            } else {
                                event.target.unMute();
                                event.target.setVolume(Math.round(globalVolume * 100));
                            }
                            
                            const rect = iframe.getBoundingClientRect();
                            const isInViewport = rect.top >= 0 && 
                                               rect.top < window.innerHeight && 
                                               rect.bottom > 0;
                            
                            if (isInViewport && iframe.classList.contains('tiktok-video')) {
                                setTimeout(() => {
                                    try {
                                        if (event.target && typeof event.target.playVideo === 'function') {
                                            event.target.playVideo();
                                        }
                                    } catch (e) {}
                                }, 200);
                            }
                        } catch (e) {}
                    },
                    onStateChange: function(event) {
                        if (!event || !event.data || !event.target) {
                            return;
                        }
                        
                        try {
                            if (event.data === YT.PlayerState.ENDED) {
                                if (!iframe.dataset.seeking) {
                                    iframe.dataset.seeking = 'true';
                                    setTimeout(() => {
                                        try {
                                            if (event.target && typeof event.target.seekTo === 'function') {
                                                event.target.seekTo(0);
                                                if (typeof event.target.playVideo === 'function') {
                                                    event.target.playVideo();
                                                }
                                            }
                                        } catch (e) {}
                                        iframe.dataset.seeking = 'false';
                                    }, 100);
                                }
                            }
                        } catch (e) {}
                    }
                }
            });
            
            youtubePlayersMap.set(iframe, player);
            return player;
        } catch (error) {
            return null;
        }
    }

    class VideoPlayerWrapper {
        constructor(element) {
            this.element = element;
            this.isYouTube = this.element.classList.contains('youtube-player');
            this.youtubePlayer = null;
            
            if (this.isYouTube) {
                this.youtubePlayer = youtubePlayersMap.get(this.element);
                
                if (!this.youtubePlayer) {
                    const videoId = this.element.dataset.youtubeId;
                    if (videoId) {
                        this.youtubePlayer = initYouTubePlayer(this.element, videoId);
                    }
                }
            }
        }
        
        isYouTubeReady() {
            if (!this.isYouTube || !this.youtubePlayer) {
                return false;
            }
            
            return typeof this.youtubePlayer.playVideo === 'function' &&
                   typeof this.youtubePlayer.pauseVideo === 'function';
        }
        
        play() {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.playVideo();
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                const playPromise = this.element.play();
                if (playPromise !== undefined) {
                    playPromise.catch(e => {});
                }
            }
        }
        
        pause() {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.pauseVideo();
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                this.element.pause();
            }
        }
        
        mute() {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.mute();
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                this.element.muted = true;
                this.element.setAttribute('muted', '');
            }
        }
        
        unmute() {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.unMute();
                        this.youtubePlayer.setVolume(Math.round(globalVolume * 100));
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                this.element.muted = false;
                this.element.removeAttribute('muted');
                if (typeof this.element.volume === 'number') {
                    this.element.volume = globalVolume;
                }
            }
        }
        
        setVolume(volume) {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.setVolume(Math.round(volume * 100));
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                this.element.volume = volume;
            }
        }
        
        seekTo(time) {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        this.youtubePlayer.seekTo(time, true);
                    } catch (e) {}
                }
            } else if (this.element.tagName === 'VIDEO') {
                this.element.currentTime = time;
            }
        }
        
        getCurrentTime() {
            if (this.isYouTube) {
                if (!this.youtubePlayer || !this.isYouTubeReady()) {
                    this.youtubePlayer = youtubePlayersMap.get(this.element);
                }
                
                if (this.isYouTubeReady()) {
                    try {
                        return this.youtubePlayer.getCurrentTime() || 0;
                    } catch (e) {
                        return 0;
                    }
                }
                return 0;
            } else if (this.element.tagName === 'VIDEO') {
                return this.element.currentTime || 0;
            }
            return 0;
        }
    }

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

    function applyVideoVolumeSettings(videoElement) {
        const wrapper = new VideoPlayerWrapper(videoElement);
        
        if (!wrapper.isYouTube) {
            videoElement.playsInline = true;
            videoElement.setAttribute('playsinline', '');
        }
        
        if (globalMuted) {
            wrapper.mute();
        } else {
            wrapper.unmute();
            wrapper.setVolume(globalVolume);
        }
    }

    function applyVolumeToAllVideos() {
        const videoList = document.querySelectorAll('.tiktok-video');
        videoList.forEach(videoElement => {
            const wrapper = new VideoPlayerWrapper(videoElement);
            
            if (globalMuted) {
                wrapper.mute();
            } else {
                wrapper.unmute();
                wrapper.setVolume(globalVolume);
            }
        });
    }

    function updateGlobalVolumeUI() {
        const wrappers = document.querySelectorAll('.volume-control-wrapper');
        wrappers.forEach(wrapper => {
            wrapper.classList.toggle('muted', globalMuted);
            const btn = wrapper.querySelector('.volume-toggle-btn');
            const slider = wrapper.querySelector('.volume-slider');
            
            if (btn) {
                const iconName = globalMuted ? 'volum-mute' : 'volum';
                const themeUri = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.theme_uri) 
                    ? puna_tiktok_ajax.theme_uri 
                    : '/wp-content/themes/puna-tiktok';
                const iconUrl = `${themeUri}/assets/images/icons/${iconName}.svg`;
                
                const volumeText = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.i18n && puna_tiktok_ajax.i18n.volume) 
                    ? puna_tiktok_ajax.i18n.volume 
                    : 'Volume';
                btn.innerHTML = `<img src="${iconUrl}" alt="${volumeText}" class="icon-svg">`;
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
            
            const wrapper = volumeToggleBtn.closest('.volume-control-wrapper');
            if (wrapper) {
                wrapper.classList.toggle('volume-active');
            }
            
            globalMuted = !globalMuted;
            if (!globalMuted && globalVolume === 0) {
                globalVolume = 1;
            }
            applyVolumeToAllVideos();
            updateGlobalVolumeUI();
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.volume-control-wrapper')) {
            document.querySelectorAll('.volume-control-wrapper').forEach(function(wrapper) {
                wrapper.classList.remove('volume-active');
            });
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('volume-slider')) {
            const slider = e.target;
            const value = Math.max(0, Math.min(100, parseInt(slider.value, 10) || 0));
            globalVolume = value / 100;
            applyVolumeToAllVideos();
            updateGlobalVolumeUI();
        }
    });

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
    
    // Throttle IntersectionObserver callback to improve performance
    let observerCallbackScheduled = false;
    const observer = new IntersectionObserver((entries) => {
        // Throttle callback execution
        if (observerCallbackScheduled) return;
        observerCallbackScheduled = true;
        
        requestAnimationFrame(() => {
            entries.forEach(entry => {
                const videoElement = entry.target;
                
                // Early return if element is not a video
                if (!videoElement || (videoElement.tagName !== 'VIDEO' && !videoElement.classList.contains('youtube-player'))) {
                    return;
                }
                
                const wrapper = new VideoPlayerWrapper(videoElement);
                
                if (entry.isIntersecting) {
                    const currentTime = wrapper.getCurrentTime();
                    if (currentTime > 0) {
                        wrapper.seekTo(0);
                    }
                    
                    if (wrapper.isYouTube) {
                        if (videoElement.classList.contains('tiktok-video')) {
                            applyVideoVolumeSettings(videoElement);
                            wrapper.seekTo(0);
                            
                            const videoId = videoElement.dataset.youtubeId;
                            if (videoId && !youtubePlayersMap.has(videoElement) && videoElement.dataset.playerInitialized !== 'true') {
                                initYouTubePlayer(videoElement, videoId);
                            }
                            
                            // Only try to play if player is already initialized
                            const player = youtubePlayersMap.get(videoElement);
                            if (player && youtubeAPIReady) {
                                const tryPlay = (retryCount = 0) => {
                                    if (retryCount >= 10) return;
                                    
                                    if (player?.playVideo) {
                                        try {
                                            player.playVideo();
                                            return;
                                        } catch (e) {}
                                    }
                                    
                                    setTimeout(() => tryPlay(retryCount + 1), 100);
                                };
                                tryPlay();
                            }
                        }
                    } else {
                        if (typeof ensureMegaVideoSource !== 'undefined') {
                            ensureMegaVideoSource(videoElement).then(() => {
                                if (videoElement.classList.contains('tiktok-video')) {
                                    applyVideoVolumeSettings(videoElement);
                                    videoElement.currentTime = 0;
                                    wrapper.play();
                                }
                            }).catch(() => {});
                        }
                    }
                    
                    const postId = videoElement.dataset.postId || videoElement.closest('[data-post-id]')?.dataset.postId;
                    if (videoElement.classList.contains('tiktok-video') && postId && !viewedVideos.has(postId)) {
                        setTimeout(() => {
                            if (entry.isIntersecting) {
                                viewedVideos.add(postId);
                                if (typeof incrementVideoView !== 'undefined') {
                                    incrementVideoView(postId);
                                }
                            }
                        }, 1000);
                    }
                } else {
                    wrapper.pause();
                    wrapper.seekTo(0);
                }
            });
            
            observerCallbackScheduled = false;
        });
    }, observerOptions);

    const hasYouTubeVideos = document.querySelector('.youtube-player');
    if (hasYouTubeVideos) {
        loadYouTubeAPI();
        
        const initVisibleYouTubeVideos = () => {
            if (!youtubeAPIReady) {
                setTimeout(initVisibleYouTubeVideos, 100);
                return;
            }
            
            document.querySelectorAll('.youtube-player.tiktok-video').forEach(iframe => {
                const videoId = iframe.dataset.youtubeId;
                // Early return checks to prevent duplicate initialization
                if (!videoId || youtubePlayersMap.has(iframe) || iframe.dataset.playerInitialized === 'true') {
                    return;
                }
                
                const rect = iframe.getBoundingClientRect();
                const isInViewport = rect.top >= 0 && 
                                   rect.top < window.innerHeight && 
                                   rect.bottom > 0;
                
                if (isInViewport) {
                    initYouTubePlayer(iframe, videoId);
                }
            });
        };
        
        setTimeout(initVisibleYouTubeVideos, 500);
    }

    // Use event delegation for click handlers to avoid duplicate listeners
    document.addEventListener('click', function(e) {
        const video = e.target.closest('.tiktok-video, .taxonomy-video, .creator-video-preview, .search-video-preview');
        if (!video || video.tagName === 'IMG' || video.tagName === 'IFRAME') {
            return;
        }
        
        const wrapper = new VideoPlayerWrapper(video);
        
        if (wrapper.isYouTube) {
            try {
                const player = youtubePlayersMap.get(video);
                if (player && player.getPlayerState) {
                    const state = player.getPlayerState();
                    if (state === YT.PlayerState.PLAYING) {
                        wrapper.pause();
                    } else {
                        wrapper.play();
                    }
                }
            } catch (error) {}
        } else if (video.tagName === 'VIDEO') {
            if (video.paused) {
                if (!video.dataset.megaLoaded && typeof ensureMegaVideoSource !== 'undefined') {
                    ensureMegaVideoSource(video).then(() => {
                        wrapper.play();
                    });
                } else {
                    wrapper.play();
                }
            } else {
                wrapper.pause();
            }
        }
    });
    
    videos.forEach(video => {
        if (video.tagName === 'IMG') {
            return;
        }
        
        const isYouTube = video.classList.contains('youtube-player');
        
        if (!isYouTube && video.tagName === 'VIDEO') {
            video.muted = true;
            video.setAttribute('muted', '');
            video.playsInline = true;
            video.setAttribute('playsinline', '');
            
            const setAspectRatio = () => {
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
            };
            
            // Use once option to prevent duplicate listeners
            video.addEventListener('loadedmetadata', () => {
                video.classList.add('loaded');
                setAspectRatio();
            }, { once: true });
            
            if (video.readyState >= 1) {
                video.classList.add('loaded');
                setAspectRatio();
            }
            
            if (typeof ensureMegaVideoSource !== 'undefined' && video.dataset.megaLink) {
                if (video.classList.contains('taxonomy-video') || video.classList.contains('search-video-preview')) {
                    ensureMegaVideoSource(video).then(() => {
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
                    ensureMegaVideoSource(video);
                }
            }
        }
        
        if (video.classList.contains('tiktok-video')) {
            observer.observe(video);
            
            const videoRow = video.closest('.video-row');
            if (videoRow) {
                videoRowObserver.observe(videoRow);
            }
        }
        
        if (video.classList.contains('taxonomy-video') && !video.closest('.video-row')) {
            video.dataset.needsPreview = '1';
        }
        
        const videoRow = video.closest('.video-row');
        if (videoRow) {
            const postId = videoRow.querySelector('[data-post-id]')?.dataset.postId;
            if (postId) {
                video.dataset.postId = postId;
            }
        }
    });

    function playVisibleVideoOnce() {
        if (userGestureHandled) return;
        userGestureHandled = true;
        
        const current = getCurrentVideo();
        if (current) {
            const wrapper = new VideoPlayerWrapper(current);
            
            wrapper.seekTo(0);
            
            if (!wrapper.isYouTube && typeof ensureMegaVideoSource !== 'undefined' && current.dataset.megaLink) {
                ensureMegaVideoSource(current).then(() => {
                    wrapper.seekTo(0);
                });
            }
            
            applyVideoVolumeSettings(current);
            wrapper.play();
        }
    }
    
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, playVisibleVideoOnce, { once: true, passive: true });
    });

    applyVolumeToAllVideos();
    updateGlobalVolumeUI();

    const taxonomyVideoObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const video = entry.target;
            
            if (video.tagName !== 'VIDEO') {
                return;
            }
            
            const isSearchVideo = video.classList.contains('search-video-preview');
            const needsPreview = video.dataset.needsPreview === '1' || isSearchVideo;
            
            if (entry.isIntersecting && needsPreview && video.dataset.megaLink && typeof ensureMegaVideoSource !== 'undefined') {
                ensureMegaVideoSource(video).then(() => {
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
                
                if (video.dataset.needsPreview === '1') {
                    video.removeAttribute('data-needs-preview');
                    taxonomyVideoObserver.unobserve(video);
                }
            }
        });
    }, { rootMargin: '100px' });
    
    document.querySelectorAll('.taxonomy-video[data-needs-preview="1"], .search-video-preview').forEach(video => {
        if (video.tagName !== 'VIDEO') {
            return;
        }
        
        if (video.dataset.megaLink && typeof ensureMegaVideoSource !== 'undefined') {
            taxonomyVideoObserver.observe(video);
            
            const rect = video.getBoundingClientRect();
            if (rect.top < window.innerHeight + 100 && rect.bottom > -100) {
                ensureMegaVideoSource(video).then(() => {
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
            }
        }
    });

    window.applyVideoVolumeSettings = applyVideoVolumeSettings;
    window.applyVolumeToAllVideos = applyVolumeToAllVideos;
    window.VideoPlayerWrapper = VideoPlayerWrapper;
});
