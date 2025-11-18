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
        
        card.innerHTML = `
            <div class="media-wrapper">
                <video muted playsinline loading="lazy" ${video.video_url && video.video_url.indexOf('mega.nz') !== -1 ? `data-mega-link="${video.video_url}"` : ''}>
                    <source src="${video.video_url && video.video_url.indexOf('mega.nz') === -1 ? video.video_url : ''}" type="video/mp4">
                </video>
                <div class="video-views-overlay">
                    <i class="fa-solid fa-play"></i>
                    <span>${views}</span>
                </div>
            </div>
        `;
        
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

