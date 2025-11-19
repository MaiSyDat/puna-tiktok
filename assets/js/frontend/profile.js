/**
 * Profile page functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const profileTabs = document.querySelectorAll('.profile-tab');
    const profileVideosSections = document.querySelectorAll('.profile-videos-section');
    const profileTabContents = document.querySelectorAll('.profile-tab-content');
    
    function initializeProfileTabs() {
        const activeTab = document.querySelector('.profile-tab.active');
        if (activeTab) {
            const targetTab = activeTab.getAttribute('data-tab');
            
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
    
    initializeProfileTabs();
    
    // Load video previews for all profile video cards
    function loadProfileVideoPreviews() {
        const profileVideos = document.querySelectorAll('.profile-video-card .explore-video, .profile-video-card video[data-mega-link]');
        profileVideos.forEach((video, index) => {
            if (video.dataset.megaLink && typeof ensureMegaVideoSource !== 'undefined') {
                // Stagger loading to avoid overwhelming the browser
                setTimeout(() => {
                    ensureMegaVideoSource(video).then(() => {
                        // Set video to first frame for thumbnail preview
                        if (video.readyState >= 2) {
                            video.currentTime = 0.1;
                            video.pause();
                        } else {
                            video.addEventListener('loadedmetadata', () => {
                                video.currentTime = 0.1;
                                video.pause();
                            }, { once: true });
                            
                            // Also try to load first frame after canplay
                            video.addEventListener('canplay', () => {
                                video.currentTime = 0.1;
                                video.pause();
                            }, { once: true });
                        }
                    }).catch(() => {});
                }, index * 50); // Stagger by 50ms per video
            }
        });
    }
    
    // Load previews on page load (wait a bit for DOM to be ready)
    setTimeout(() => {
        loadProfileVideoPreviews();
    }, 200);
    
    // Also load when tabs are switched
    profileTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            profileTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
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
            
            // Load video previews when tab is switched
            setTimeout(() => {
                loadProfileVideoPreviews();
            }, 100);
        });
    });

    // Guest profile loading
    if (!isLoggedIn() && document.querySelector('.guest-profile-section')) {
        loadGuestProfile();
    }

    function loadGuestProfile() {
        const likedVideos = GuestStorage.getLikedVideos();
        const savedVideos = GuestStorage.getSavedVideos();
        
        if (likedVideos.length === 0 && savedVideos.length === 0) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'puna_tiktok_get_guest_profile');
        formData.append('liked_videos', JSON.stringify(likedVideos));
        formData.append('saved_videos', JSON.stringify(savedVideos));
        
        if (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.like_nonce) {
            formData.append('nonce', puna_tiktok_ajax.like_nonce);
        }
        
        const ajaxUrl = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.ajax_url) ? puna_tiktok_ajax.ajax_url : '/wp-admin/admin-ajax.php';
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const likedContainer = document.getElementById('guest-liked-videos');
                const savedContainer = document.getElementById('guest-saved-videos');
                
                if (likedContainer && data.data.liked_videos) {
                    likedContainer.innerHTML = '';
                    data.data.liked_videos.forEach(video => {
                        const card = createVideoCard(video);
                        likedContainer.appendChild(card);
                    });
                }
                
                if (savedContainer && data.data.saved_videos) {
                    savedContainer.innerHTML = '';
                    data.data.saved_videos.forEach(video => {
                        const card = createVideoCard(video);
                        savedContainer.appendChild(card);
                    });
                }
            }
        })
        .catch(error => {
        });
    }

    function createVideoCard(video) {
        const card = document.createElement('a');
        card.href = video.permalink || '#';
        card.className = 'profile-video-card';
        
        const views = (typeof formatNumber !== 'undefined') ? formatNumber(video.views || 0) : (video.views || 0);
        const videoUrl = video.video_url || '';
        
        // All videos are Mega videos
        card.innerHTML = `
            <div class="media-wrapper ratio-9x16">
                <video class="explore-video" muted playsinline loading="lazy" data-mega-link="${videoUrl}">
                    <!-- Mega.nz video will be loaded via JavaScript -->
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
        
        // Load video preview when card is created
        const videoElement = card.querySelector('video');
        if (videoElement && typeof ensureMegaVideoSource !== 'undefined' && videoElement.dataset.megaLink) {
            // Load video source for preview
            ensureMegaVideoSource(videoElement).then(() => {
                // Set video to first frame for thumbnail
                if (videoElement.readyState >= 2) {
                    videoElement.currentTime = 0.1;
                }
            }).catch(() => {});
        }
        
        return card;
    }

    function getAvatarColor(name) {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52BE80', '#E74C3C', '#3498DB', '#9B59B6', '#1ABC9C', '#F39C12', '#E67E22', '#34495E', '#16A085', '#27AE60', '#2980B9'];
        const hash = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return colors[hash % colors.length];
    }

    window.createVideoCard = createVideoCard;
    window.getAvatarColor = getAvatarColor;
});

