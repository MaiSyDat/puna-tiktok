/**
 * Taxonomy page functionality (Category & Tag pages)
 */

document.addEventListener("DOMContentLoaded", function() {
    const taxonomyTabsContainer = document.getElementById('taxonomy-tabs');
    const taxonomyTabs = document.querySelectorAll('#taxonomy-tabs .tab');
    const taxonomyGrid = document.getElementById('taxonomy-grid');
    
    if (!taxonomyTabsContainer || !taxonomyGrid) return;
    
    // Mark that this tab content was loaded from PHP (server-side)
    // This prevents auto-loading if PHP already rendered content
    let phpContentLoaded = false;
    
    // Wait a bit to check if PHP has already rendered content
    setTimeout(function() {
        const hasVideoCards = taxonomyGrid.querySelectorAll('a.taxonomy-card').length > 0;
        const hasEmptyState = taxonomyGrid.querySelectorAll('.taxonomy-empty-state').length > 0;
        
        if (hasVideoCards || hasEmptyState) {
            phpContentLoaded = true;
            return; // PHP has already rendered content, don't auto-load
        }
        
        // Only auto-load if grid is truly empty AND no PHP content was loaded
        // Check again after a delay to ensure PHP had time to render
        setTimeout(function() {
            const stillHasVideoCards = taxonomyGrid.querySelectorAll('a.taxonomy-card').length > 0;
            const stillHasEmptyState = taxonomyGrid.querySelectorAll('.taxonomy-empty-state').length > 0;
            
            if (!phpContentLoaded && !stillHasVideoCards && !stillHasEmptyState) {
                const activeTab = taxonomyTabsContainer.querySelector('.tab.active');
                if (activeTab && !activeTab.classList.contains('loading')) {
                    // Mark that we're loading to prevent multiple triggers
                    activeTab.classList.add('loading');
                    
                    // Only auto-load for trending or foryou tabs, not specific category/tag tabs
                    const tabType = activeTab.getAttribute('data-tab');
                    const isSpecificTab = tabType && (tabType.startsWith('category-') || tabType.startsWith('tag-'));
                    
                    if (!isSpecificTab) {
                        // Trigger load via AJAX directly without clicking (to avoid re-triggering handlers)
                        const formData = new FormData();
                        formData.append('action', 'puna_tiktok_get_taxonomy_videos');
                        formData.append('tab_type', tabType === 'foryou' ? 'foryou' : 'trending');
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
                            activeTab.classList.remove('loading');
                            if (data.success && data.data && data.data.videos) {
                                renderTaxonomyVideos(data.data.videos);
                            } else {
                                const errorMsg = data.data?.message || 'No videos';
                                renderEmptyState(taxonomyGrid, errorMsg);
                            }
                        })
                        .catch(() => {
                            activeTab.classList.remove('loading');
                            renderErrorState(taxonomyGrid);
                        });
                    } else {
                        activeTab.classList.remove('loading');
                    }
                }
            }
        }, 500);
    }, 100);
    
    // Shared variables for drag detection
    let isDragging = false;
    let dragStartX = 0;
    let dragStartScrollLeft = 0;
    const dragThreshold = 5;
    
    // Mouse wheel scrolling
    taxonomyTabsContainer.addEventListener('wheel', function(e) {
        if (e.deltaY !== 0) {
            e.preventDefault();
            this.scrollLeft += e.deltaY;
        }
    }, { passive: false });
    
    // Drag scrolling
    taxonomyTabsContainer.addEventListener('mousedown', function(e) {
        isDragging = false;
        dragStartX = e.pageX;
        dragStartScrollLeft = taxonomyTabsContainer.scrollLeft;
        taxonomyTabsContainer.style.cursor = 'grabbing';
    });
    
    taxonomyTabsContainer.addEventListener('mouseleave', function() {
        isDragging = false;
        dragStartX = 0;
        taxonomyTabsContainer.style.cursor = 'grab';
    });
    
    taxonomyTabsContainer.addEventListener('mouseup', function() {
        setTimeout(() => {
            isDragging = false;
            dragStartX = 0;
        }, 10);
        taxonomyTabsContainer.style.cursor = 'grab';
    });
    
    taxonomyTabsContainer.addEventListener('mousemove', function(e) {
        if (dragStartX === 0) return;
        
        const currentX = e.pageX;
        const diffX = Math.abs(currentX - dragStartX);
        
        if (diffX > dragThreshold) {
            isDragging = true;
            e.preventDefault();
            const walk = (currentX - dragStartX) * 2;
            taxonomyTabsContainer.scrollLeft = dragStartScrollLeft - walk;
        }
    });
    
    // Tab click handlers
    if (taxonomyTabs.length > 0) {
        taxonomyTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                
                const tabType = this.getAttribute('data-tab');
                const categoryId = this.getAttribute('data-category-id');
                const tagId = this.getAttribute('data-tag-id');
                
                // Check if this tab is already active
                const isAlreadyActive = this.classList.contains('active');
                
                // If already active and grid has content, don't reload
                if (isAlreadyActive) {
                    const hasVideoCards = taxonomyGrid.querySelectorAll('a.taxonomy-card').length > 0;
                    const hasEmptyState = taxonomyGrid.querySelectorAll('.taxonomy-empty-state').length > 0;
                    if (hasVideoCards || hasEmptyState) {
                        return; // Don't reload if content is already there
                    }
                }
                
                taxonomyTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                renderLoadingState(taxonomyGrid);
                
                // Determine request type based on page and tab
                const isTagPage = document.body.classList.contains('tag-page') || window.location.pathname.includes('/tag');
                let requestTabType = 'trending';
                
                if (tabType === 'foryou') {
                    requestTabType = 'foryou';
                } else if (categoryId) {
                    requestTabType = 'category';
                } else if (tagId) {
                    requestTabType = 'tag';
                }
                
                const formData = new FormData();
                formData.append('action', 'puna_tiktok_get_taxonomy_videos');
                formData.append('tab_type', requestTabType);
                if (categoryId) {
                    formData.append('category_id', categoryId);
                }
                if (tagId) {
                    formData.append('tag_id', tagId);
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
                    // Remove loading class
                    this.classList.remove('loading');
                    
                    if (data.success && data.data && data.data.videos) {
                        renderTaxonomyVideos(data.data.videos);
                        phpContentLoaded = false; // Mark that we loaded via AJAX now
                    } else {
                        const errorMsg = data.data?.message || 'No videos';
                        renderEmptyState(taxonomyGrid, errorMsg);
                    }
                })
                .catch(() => {
                    // Remove loading class
                    this.classList.remove('loading');
                    renderErrorState(taxonomyGrid);
                });
            });
        });
    }
    
    function renderTaxonomyVideos(videos) {
        if (!videos || videos.length === 0) {
            renderEmptyState(taxonomyGrid, 'No videos yet.');
            return;
        }
        
        taxonomyGrid.innerHTML = '';
        
        videos.forEach(video => {
            const videoCard = createTaxonomyVideoCard(video);
            taxonomyGrid.appendChild(videoCard);
        });
        
        const newVideos = taxonomyGrid.querySelectorAll('.taxonomy-video');
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
    
    function renderLoadingState(container) {
        container.innerHTML = '';
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'taxonomy-loading-state';
        loadingDiv.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 60px 20px;';
        
        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
        const iconImg = document.createElement('img');
        iconImg.src = `${themeUri}/assets/images/icons/play.svg`;
        iconImg.alt = 'Loading';
        iconImg.className = 'icon-svg taxonomy-loading-icon';
        
        loadingDiv.appendChild(iconImg);
        container.appendChild(loadingDiv);
    }
    
    function renderEmptyState(container, message) {
        container.innerHTML = '';
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'taxonomy-empty-state';
        emptyDiv.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 60px 20px;';
        
        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
        const iconImg = document.createElement('img');
        iconImg.src = `${themeUri}/assets/images/icons/play.svg`;
        iconImg.alt = 'No videos';
        iconImg.className = 'icon-svg';
        iconImg.style.cssText = 'width: 64px; height: 64px; color: #ccc; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;';
        
        const h3 = document.createElement('h3');
        h3.textContent = 'No videos';
        h3.style.cssText = 'color: #666; margin-bottom: 10px;';
        
        const p = document.createElement('p');
        p.textContent = message || 'No videos yet.';
        p.style.cssText = 'color: #999;';
        
        emptyDiv.appendChild(iconImg);
        emptyDiv.appendChild(h3);
        emptyDiv.appendChild(p);
        container.appendChild(emptyDiv);
    }
    
    function renderErrorState(container) {
        container.innerHTML = '';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'taxonomy-empty-state';
        errorDiv.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 60px 20px;';
        
        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
        const iconImg = document.createElement('img');
        iconImg.src = `${themeUri}/assets/images/icons/home.svg`;
        iconImg.alt = 'Error';
        iconImg.className = 'icon-svg';
        iconImg.style.cssText = 'width: 64px; height: 64px; color: #ccc; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;';
        
        const h3 = document.createElement('h3');
        h3.textContent = 'An error occurred';
        h3.style.cssText = 'color: #666; margin-bottom: 10px;';
        
        const p = document.createElement('p');
        p.textContent = 'Please try again later.';
        p.style.cssText = 'color: #999;';
        
        errorDiv.appendChild(iconImg);
        errorDiv.appendChild(h3);
        errorDiv.appendChild(p);
        container.appendChild(errorDiv);
    }
    
    function createTaxonomyVideoCard(video) {
        const card = document.createElement('a');
        card.href = video.permalink || '#';
        card.className = 'taxonomy-card';
        card.setAttribute('aria-label', 'Video');
        
        const videoUrl = video.video_url || '';
        const views = (typeof formatNumber !== 'undefined') ? formatNumber(video.views || 0) : (video.views || 0);
        const featuredImageUrl = video.featured_image_url || '';
        
        const mediaWrapper = document.createElement('div');
        mediaWrapper.className = 'media-wrapper ratio-9x16';
        
        // Use featured image if available, otherwise use video
        let mediaElement;
        if (featuredImageUrl) {
            mediaElement = document.createElement('img');
            mediaElement.src = featuredImageUrl;
            mediaElement.alt = '';
            mediaElement.className = 'taxonomy-video';
            mediaElement.loading = 'lazy';
        } else {
            mediaElement = document.createElement('video');
            mediaElement.className = 'taxonomy-video';
            mediaElement.muted = true;
            mediaElement.setAttribute('muted', '');
            mediaElement.playsInline = true;
            mediaElement.setAttribute('playsinline', '');
            mediaElement.loading = 'lazy';
            if (videoUrl) {
                mediaElement.setAttribute('data-mega-link', videoUrl);
            }
        }
        
        const videoOverlay = document.createElement('div');
        videoOverlay.className = 'video-overlay';
        
        const playIcon = document.createElement('div');
        playIcon.className = 'play-icon';
        
        const themeUri = (window.puna_tiktok_ajax && window.puna_tiktok_ajax.theme_uri) ? window.puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
        const playIconImg = document.createElement('img');
        playIconImg.src = `${themeUri}/assets/images/icons/play.svg`;
        playIconImg.alt = 'Play';
        playIconImg.className = 'icon-svg';
        playIcon.appendChild(playIconImg);
        videoOverlay.appendChild(playIcon);
        
        const videoViewsOverlay = document.createElement('div');
        videoViewsOverlay.className = 'video-views-overlay';
        
        const viewsIconImg = document.createElement('img');
        viewsIconImg.src = `${themeUri}/assets/images/icons/play.svg`;
        viewsIconImg.alt = 'Views';
        viewsIconImg.className = 'icon-svg';
        videoViewsOverlay.appendChild(viewsIconImg);
        
        const viewsSpan = document.createElement('span');
        viewsSpan.textContent = views;
        videoViewsOverlay.appendChild(viewsSpan);
        
        mediaWrapper.appendChild(mediaElement);
        mediaWrapper.appendChild(videoOverlay);
        mediaWrapper.appendChild(videoViewsOverlay);
        card.appendChild(mediaWrapper);
        
        return card;
    }
});

