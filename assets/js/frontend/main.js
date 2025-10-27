document.addEventListener("DOMContentLoaded", function() {
    const videos = document.querySelectorAll('.tiktok-video');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');

    const options = {
        root: mainContent,
        rootMargin: '0px',
        threshold: 0.8
    };

    // Theo dõi các video đã xem để tránh tính trùng lặp
    const viewedVideos = new Set();
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.play().catch(e => {
                    console.log('Trình duyệt chặn autoplay:', e);
                });
                
                // Track view after 1 second
                if (!viewedVideos.has(entry.target.dataset.postId)) {
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
    }, options);

    videos.forEach(video => {
        observer.observe(video);
        video.addEventListener('click', function() {
            if (this.paused) {
                this.play();
            } else {
                this.pause();
            }
        });
        
        // Add post_id to video element
        const videoRow = video.closest('.video-row');
        if (videoRow) {
            const postId = videoRow.querySelector('[data-post-id]')?.dataset.postId;
            if (postId) {
                video.dataset.postId = postId;
            }
        }
    });

    let isScrolling = false;
    if (mainContent) {
        mainContent.addEventListener('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        const videos = document.querySelectorAll('.tiktok-video');
        const currentVideo = Array.from(videos).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });

        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            const direction = e.key === 'ArrowUp' ? -1 : 1;
            const currentIndex = Array.from(videos).indexOf(currentVideo);
            const nextIndex = currentIndex + direction;

            if (nextIndex >= 0 && nextIndex < videos.length) {
                videos[nextIndex].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });

    function scrollToSibling(direction) {
        const videos = document.querySelectorAll('.tiktok-video');
        if (!videos.length) return;
        const currentVideo = Array.from(videos).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
        const currentIndex = Array.from(videos).indexOf(currentVideo);
        const nextIndex = currentIndex + direction;
        if (nextIndex >= 0 && nextIndex < videos.length) {
            videos[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        updateNavDisabledState();
    }

    // Bắt sự kiện click nút điều hướng
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

    // Cập nhật trạng thái vô hiệu nút
    function updateNavDisabledState() {
        const list = document.querySelectorAll('.tiktok-video');
        if (!list.length) return;
        const current = Array.from(list).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
        const idx = Array.from(list).indexOf(current);
        const atTop = idx <= 0;
        const atBottom = idx === list.length - 1 || idx < 0;
        if (navPrevBtn) navPrevBtn.classList.toggle('is-disabled', atTop);
        if (navNextBtn) navNextBtn.classList.toggle('is-disabled', atBottom);
    }

    // Khởi tạo trạng thái sau khi tải trang
    updateNavDisabledState();

    // Cập nhật khi scroll, nhấn phím, vuốt
    const updateEvents = ['scroll', 'keydown', 'touchend'];
    updateEvents.forEach(evt => {
        (evt === 'scroll' && mainContent ? mainContent : document).addEventListener(evt, function() {
            window.requestAnimationFrame(updateNavDisabledState);
        });
    });

    // Vuốt để chuyển video
    let startY = 0;
    let endY = 0;
    if (mainContent) {
        mainContent.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
        });
        mainContent.addEventListener('touchend', function(e) {
            endY = e.changedTouches[0].clientY;
            const diff = startY - endY;
            if (Math.abs(diff) > 50) {
                const videos = document.querySelectorAll('.tiktok-video');
                const currentVideo = Array.from(videos).find(video => {
                    const rect = video.getBoundingClientRect();
                    return rect.top >= 0 && rect.top < window.innerHeight / 2;
                });
                const currentIndex = Array.from(videos).indexOf(currentVideo);
                const direction = diff > 0 ? 1 : -1;
                const nextIndex = currentIndex + direction;
                if (nextIndex >= 0 && nextIndex < videos.length) {
                    videos[nextIndex].scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    }

    // Login Popup
    let currentModal = null;

    // Mở popup đăng nhập
    window.openLoginPopup = function() {
        currentModal = 'login';
        document.getElementById('loginModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    // Mở popup đăng ký
    window.openSignupPopup = function() {
        currentModal = 'signup';
        document.getElementById('signupModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    // Đóng popup
    window.closeModal = function() {
        if (currentModal === 'login') {
            document.getElementById('loginModal').classList.remove('show');
        } else if (currentModal === 'signup') {
            document.getElementById('signupModal').classList.remove('show');
        }
        currentModal = null;
        document.body.style.overflow = 'auto';
    };

    // Chuyển đổi tab trong form đăng nhập
    window.switchTab = function(tab) {
        // Xóa active class từ tất cả tabs và forms
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));
        
        // Thêm active class cho tab và form được chọn
        event.target.classList.add('active');
        document.getElementById(tab + '-login').classList.add('active');
    };

    // Chuyển sang popup đăng ký
    window.switchToSignup = function() {
        document.getElementById('loginModal').classList.remove('show');
        setTimeout(() => {
            openSignupPopup();
        }, 150);
    };

    // Chuyển sang popup đăng nhập
    window.switchToLogin = function() {
        document.getElementById('signupModal').classList.remove('show');
        setTimeout(() => {
            openLoginPopup();
        }, 150);
    };

    // Chuyển sang đăng nhập bằng mật khẩu
    window.switchToPasswordLogin = function() {
        switchTab('email');
    };

    // Chuyển sang đăng ký bằng email
    window.switchToEmailSignup = function() {
        alert('Tính năng đăng ký bằng email sẽ được thêm sau');
    };

    // Format number helper
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }
    
    // Like/Unlike Video
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="like"]');
        if (!actionItem) {
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) {
            return;
        }
        
        // Check login
        if (!puna_tiktok_ajax.is_logged_in) {
            openLoginPopup();
            return;
        }
        
        // Add animation
        actionItem.classList.add('liking');
        setTimeout(() => actionItem.classList.remove('liking'), 300);
        
        // Send AJAX request
        fetch(puna_tiktok_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'puna_tiktok_toggle_like',
                post_id: postId,
                nonce: puna_tiktok_ajax.like_nonce,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const isLiked = data.data.is_liked;
                const likes = data.data.likes;
                
                // Update UI
                if (isLiked) {
                    actionItem.classList.add('liked');
                } else {
                    actionItem.classList.remove('liked');
                }
                
                // Update count
                const countElement = actionItem.querySelector('.count');
                if (countElement) {
                    countElement.textContent = formatNumber(likes);
                }
            } else {
                console.error('Like error:', data.data.message);
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
        });
    });

    // Comments Sidebar Functionality
    // Open comments sidebar
    function openCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    // Close comments sidebar
    function closeCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }

    // Handle comment button click
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="comment"]');
        if (actionItem && actionItem.dataset.postId) {
            e.preventDefault();
            e.stopPropagation();
            
            // Check if user is logged in
            if (!puna_tiktok_ajax.is_logged_in) {
                openLoginPopup();
                return;
            }
            
            openCommentsSidebar(actionItem.dataset.postId);
        }
    });

    // Close comments button
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.close-comments-btn');
        if (closeBtn && closeBtn.dataset.postId) {
            e.preventDefault();
            closeCommentsSidebar(closeBtn.dataset.postId);
        }
    });

    // Close when clicking overlay
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('comments-overlay')) {
            const postId = e.target.id.replace('comments-overlay-', '');
            closeCommentsSidebar(postId);
        }
    });

    // Handle comment input
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('comment-input')) {
            const container = e.target.closest('.comment-input-container');
            const btn = container.querySelector('.submit-comment-btn');
            if (btn) {
                btn.disabled = e.target.value.trim() === '';
            }
        }
    });

    // Submit comment
    document.addEventListener('click', function(e) {
        const submitBtn = e.target.closest('.submit-comment-btn');
        if (submitBtn && !submitBtn.disabled && submitBtn.dataset.postId) {
            e.preventDefault();
            
            const postId = submitBtn.dataset.postId;
            const container = submitBtn.closest('.comment-input-container');
            const input = container.querySelector('.comment-input');
            const commentText = input.value.trim();
            
            if (!commentText) {
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang đăng...';
            
            // Submit comment via AJAX
            fetch(puna_tiktok_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'puna_tiktok_add_comment',
                    post_id: postId,
                    comment_text: commentText,
                    nonce: puna_tiktok_ajax.like_nonce,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new comment to the list without reload
                    addCommentToList(postId, commentText);
                    
                    // Clear input and reset button
                    input.value = '';
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Đăng';
                    
                    // Update comment count
                    updateCommentCount(postId);
                } else {
                    alert('Có lỗi xảy ra khi đăng bình luận.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Đăng';
                }
            })
            .catch(error => {
                console.error('AJAX error:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng';
            });
        }
    });

    // Handle Escape key to close comments
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openOverlay = document.querySelector('.comments-overlay.show');
            if (openOverlay) {
                const postId = openOverlay.id.replace('comments-overlay-', '');
                closeCommentsSidebar(postId);
            }
        }
    });

    // Video Volume Control
    document.addEventListener('click', function(e) {
        const volumeToggleBtn = e.target.closest('.volume-toggle-btn');
        if (volumeToggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const volumeWrapper = volumeToggleBtn.closest('.volume-control-wrapper');
            const video = volumeWrapper.closest('.video-container').querySelector('.tiktok-video');
            
            if (video.muted) {
                video.muted = false;
                volumeWrapper.classList.remove('muted');
                volumeToggleBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
            } else {
                video.muted = true;
                volumeWrapper.classList.add('muted');
                volumeToggleBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
            }
        }
    });

    // Volume Slider
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('volume-slider')) {
            const slider = e.target;
            const value = slider.value;
            const video = slider.closest('.video-container').querySelector('.tiktok-video');
            
            video.volume = value / 100;
            
            // Update icon based on volume
            const volumeToggleBtn = slider.closest('.volume-control-wrapper').querySelector('.volume-toggle-btn');
            if (value == 0) {
                video.muted = true;
                volumeToggleBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
            } else {
                video.muted = false;
                if (value < 50) {
                    volumeToggleBtn.innerHTML = '<i class="fa-solid fa-volume-low"></i>';
                } else {
                    volumeToggleBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
                }
            }
        }
    });

    // Video Options Menu
    document.addEventListener('click', function(e) {
        const optionsBtn = e.target.closest('.video-options-btn');
        if (optionsBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const dropdown = optionsBtn.nextElementSibling;
            const isShowing = dropdown.classList.contains('show');
            
            // Close all dropdowns
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
            
            // Toggle current dropdown
            if (!isShowing) {
                dropdown.classList.add('show');
            }
        }
    });

    // Close options menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.video-options-menu')) {
            document.querySelectorAll('.video-options-dropdown').forEach(d => d.classList.remove('show'));
        }
    });

    // Initialize video volumes
    document.querySelectorAll('.tiktok-video').forEach(video => {
        video.volume = 1;
        video.muted = false;
    });

    // Increment video view count
    function incrementVideoView(postId) {
        if (!postId) {
            return;
        }
        
        fetch(puna_tiktok_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'puna_tiktok_increment_view',
                post_id: postId,
                nonce: puna_tiktok_ajax.like_nonce,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('View count updated:', data.data.views);
                // Optionally update the view count in the UI
                const viewElement = document.querySelector(`.action-item[data-post-id="${postId}"][data-action="view"] .count`);
                if (viewElement && data.data.formatted_views) {
                    viewElement.textContent = data.data.formatted_views;
                }
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
        });
    }

    // Add new comment to the list
    function addCommentToList(postId, commentText) {
        const commentsList = document.querySelector(`#comments-overlay-${postId} .comments-list`);
        const noComments = commentsList.querySelector('.no-comments');
        
        // Remove "no comments" message if exists
        if (noComments) {
            noComments.remove();
        }
        
        // Get current user info
        const currentUser = puna_tiktok_ajax.current_user || { display_name: 'Bạn', user_id: 0 };
        
        // Create new comment element
        const commentElement = document.createElement('div');
        commentElement.className = 'comment-item';
        commentElement.innerHTML = `
            <img src="${puna_tiktok_ajax.avatar_url || 'https://via.placeholder.com/40'}" 
                 alt="${currentUser.display_name}" 
                 class="comment-avatar">
            <div class="comment-content">
                <div class="comment-header">
                    <strong class="comment-author">${currentUser.display_name}</strong>
                </div>
                <p class="comment-text">${commentText}</p>
                <div class="comment-footer">
                    <span class="comment-date">Vừa xong</span>
                    <a href="#" class="reply-link" data-comment-id="new">
                        Trả lời
                    </a>
                </div>
            </div>
            <div class="comment-likes">
                <i class="fa-regular fa-heart"></i>
                <span>0</span>
            </div>
        `;
        
        // Add to top of comments list
        commentsList.insertBefore(commentElement, commentsList.firstChild);
        
        // Scroll to top to show new comment
        commentsList.scrollTop = 0;
    }

    // Update comment count
    function updateCommentCount(postId) {
        console.log('Updating comment count for post:', postId);
        
        // Update count in video sidebar - fix selector
        const videoSidebar = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count`);
        console.log('Video sidebar element:', videoSidebar);
        
        if (videoSidebar) {
            const currentCount = parseInt(videoSidebar.textContent.replace(/[^\d]/g, '')) || 0;
            console.log('Current count:', currentCount);
            videoSidebar.textContent = formatNumber(currentCount + 1);
            console.log('New count:', videoSidebar.textContent);
        } else {
            console.log('Video sidebar not found, trying alternative selector...');
            // Try alternative selector
            const altSelector = document.querySelector(`[data-post-id="${postId}"] .action-item[data-action="comment"] .count`);
            console.log('Alternative selector result:', altSelector);
            if (altSelector) {
                const currentCount = parseInt(altSelector.textContent.replace(/[^\d]/g, '')) || 0;
                altSelector.textContent = formatNumber(currentCount + 1);
            }
        }
        
        // Update count in comments header
        const commentsHeader = document.querySelector(`#comments-overlay-${postId} .comments-header h3`);
        if (commentsHeader) {
            const currentCount = parseInt(commentsHeader.textContent.match(/\d+/)[0]) || 0;
            commentsHeader.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
    }

    // Profile tabs functionality
    const tabs = document.querySelectorAll('.profile-tab');
    const tabContents = document.querySelectorAll('.profile-videos-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show target tab content
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.style.display = 'block';
            }
        });
    });
});

