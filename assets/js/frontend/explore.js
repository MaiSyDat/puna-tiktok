/**
 * Explore page functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const exploreTabsContainer = document.getElementById('explore-tabs');
    const exploreTabs = document.querySelectorAll('#explore-tabs .tab');
    const exploreGrid = document.getElementById('explore-grid');
    
    if (!exploreTabsContainer || !exploreGrid) return;
    
    // Shared variables for drag detection
    let isDragging = false;
    let dragStartX = 0;
    let dragStartScrollLeft = 0;
    const dragThreshold = 5;
    
    // Mouse wheel scrolling
    exploreTabsContainer.addEventListener('wheel', function(e) {
        if (e.deltaY !== 0) {
            e.preventDefault();
            this.scrollLeft += e.deltaY;
        }
    }, { passive: false });
    
    // Drag scrolling
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
        
        if (diffX > dragThreshold) {
            isDragging = true;
            e.preventDefault();
            const walk = (currentX - dragStartX) * 2;
            exploreTabsContainer.scrollLeft = dragStartScrollLeft - walk;
        }
    });
    
    // Tab click handlers
    if (exploreTabs.length > 0) {
        exploreTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                
                const tabType = this.getAttribute('data-tab');
                const categoryId = this.getAttribute('data-category-id');
                
                exploreTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                exploreGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;"><i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: #999;"></i></div>';
                
                let requestTabType = 'trending';
                if (tabType === 'foryou') {
                    requestTabType = 'foryou';
                } else if (tabType && tabType.startsWith('category-')) {
                    requestTabType = 'category';
                }
                
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
        
        const newVideos = exploreGrid.querySelectorAll('.explore-video');
        newVideos.forEach(video => {
            video.muted = true;
            video.setAttribute('muted', '');
            video.playsInline = true;
            video.setAttribute('playsinline', '');
            
            if (video.dataset.megaLink && typeof ensureMegaVideoSource !== 'undefined') {
                ensureMegaVideoSource(video);
            }
        });
    }
    
    function createExploreVideoCard(video) {
        const card = document.createElement('a');
        card.href = video.permalink || '#';
        card.className = 'explore-card';
        card.setAttribute('aria-label', 'Video');
        
        const isMegaVideo = video.video_url && video.video_url.indexOf('mega.nz') !== -1;
        const videoUrl = video.video_url || '';
        const views = (typeof formatNumber !== 'undefined') ? formatNumber(video.views || 0) : (video.views || 0);
        
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

