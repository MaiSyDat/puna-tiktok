/**
 * Main Frontend JavaScript cho Puna TikTok Theme
 * Xử lý tất cả các chức năng tương tác trên frontend
 */

document.addEventListener("DOMContentLoaded", function() {
    // ============================================
    // BIẾN TOÀN CỤC VÀ SELECTORS
    // ============================================
    const videos = document.querySelectorAll('.tiktok-video');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');

    // State quản lý volume toàn cục
    let globalMuted = true;
    let globalVolume = 1; // 0..1
    
    // Theo dõi các video đã xem để tránh tính trùng lặp
    const viewedVideos = new Set();
    
    // State cho login modal
    let currentModal = null;
    
    // State cho scroll optimization
    let isScrolling = false;
    let userGestureHandled = false;

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    
    /**
     * Định dạng số lớn thành dạng ngắn gọn (1K, 1M)
     * @param {number} num - Số cần định dạng
     * @returns {string} - Số đã được định dạng
     */
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    /**
     * Tìm video hiện tại đang trong viewport
     * @returns {HTMLElement|null} - Video element hoặc null
     */
    function getCurrentVideo() {
        const videoList = document.querySelectorAll('.tiktok-video');
        return Array.from(videoList).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
    }

    /**
     * Áp dụng cài đặt volume cho một video
     * @param {HTMLVideoElement} video - Video element
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
     * Gửi AJAX request với error handling
     * @param {string} action - WordPress action
     * @param {Object} params - Các tham số bổ sung
     * @returns {Promise} - Promise từ fetch
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

    // Event listener cho volume toggle button
    document.addEventListener('click', function(e) {
        const volumeToggleBtn = e.target.closest('.volume-toggle-btn');
        if (volumeToggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            globalMuted = !globalMuted;
            if (!globalMuted && globalVolume === 0) {
                globalVolume = 1; // Mặc định 100% khi unmute từ zero
            }
            applyVolumeToAllVideos();
            updateGlobalVolumeUI();
        }
    });

    // Event listener cho volume slider
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
        threshold: 0.8
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                applyVideoVolumeSettings(entry.target);
                entry.target.play().catch(e => {
                    console.log('Trình duyệt chặn autoplay:', e);
                });
                
                // Track view sau 1 giây
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
    }, observerOptions);

    // Khởi tạo videos
    videos.forEach(video => {
        video.muted = true;
        video.setAttribute('muted', '');
        video.playsInline = true;
        video.setAttribute('playsinline', '');
        observer.observe(video);
        
        // Click để play/pause
        video.addEventListener('click', function() {
            if (this.paused) {
                this.play();
            } else {
                this.pause();
            }
        });
        
        // Thêm post_id vào video element
        const videoRow = video.closest('.video-row');
        if (videoRow) {
            const postId = videoRow.querySelector('[data-post-id]')?.dataset.postId;
            if (postId) {
                video.dataset.postId = postId;
            }
        }
    });

    /**
     * Phát video hiện tại sau user interaction đầu tiên
     */
    function playVisibleVideoOnce() {
        if (userGestureHandled) return;
        userGestureHandled = true;
        
        const current = getCurrentVideo();
        if (current) {
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
     * Scroll đến video tiếp theo/trước đó
     * @param {number} direction - -1 cho previous, 1 cho next
     */
    function scrollToSibling(direction) {
        const videoList = document.querySelectorAll('.tiktok-video');
        if (!videoList.length) return;
        
        let currentVideo = getCurrentVideo();
        if (!currentVideo) currentVideo = videoList[0];
        
        const currentIndex = Array.from(videoList).indexOf(currentVideo);
        const nextIndex = currentIndex + direction;
        
        if (nextIndex >= 0 && nextIndex < videoList.length) {
            videoList[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        updateNavDisabledState();
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

    // Cập nhật navigation state khi scroll
    const updateEvents = ['scroll', 'keydown', 'touchend'];
    updateEvents.forEach(evt => {
        (evt === 'scroll' && mainContent ? mainContent : document).addEventListener(evt, function() {
            window.requestAnimationFrame(updateNavDisabledState);
        });
    });

    // Navigation bằng phím mũi tên
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            const direction = e.key === 'ArrowUp' ? -1 : 1;
            scrollToSibling(direction);
        }
    });

    // Navigation bằng swipe trên mobile
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
                const direction = diff > 0 ? 1 : -1;
                scrollToSibling(direction);
            }
        });
    }

    // Khởi tạo navigation state
    updateNavDisabledState();

    // ============================================
    // LOGIN/REGISTER POPUP
    // ============================================
    
    /**
     * Mở popup đăng nhập
     */
    window.openLoginPopup = function() {
        currentModal = 'login';
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    /**
     * Kiểm tra đăng nhập chung cho toàn bộ website
     * @param {Event} [event] - Event object (nếu có sẽ preventDefault)
     * @param {Function} [callback] - Callback function nếu đã đăng nhập
     * @returns {boolean} - true nếu đã đăng nhập, false nếu chưa
     */
    function checkLogin(event, callback) {
        // Kiểm tra nếu puna_tiktok_ajax chưa được định nghĩa
        if (typeof puna_tiktok_ajax === 'undefined') {
            console.warn('puna_tiktok_ajax is not defined');
            return false;
        }

        // Kiểm tra trạng thái đăng nhập
        if (!puna_tiktok_ajax.is_logged_in) {
            // Prevent default action nếu có event
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            // Mở popup đăng nhập
            openLoginPopup();
            return false;
        }

        // Nếu đã đăng nhập và có callback, gọi callback
        if (typeof callback === 'function') {
            callback();
        }

        return true;
    }

    /**
     * Mở popup đăng ký
     */
    window.openSignupPopup = function() {
        currentModal = 'signup';
        const signupModal = document.getElementById('signupModal');
        if (signupModal) {
            signupModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    /**
     * Đóng popup hiện tại
     */
    window.closeModal = function() {
        if (currentModal === 'login') {
            const loginModal = document.getElementById('loginModal');
            if (loginModal) loginModal.classList.remove('show');
        } else if (currentModal === 'signup') {
            const signupModal = document.getElementById('signupModal');
            if (signupModal) signupModal.classList.remove('show');
        }
        currentModal = null;
        document.body.style.overflow = 'auto';
    };

    /**
     * Chuyển đổi tab trong form đăng nhập
     */
    window.switchTab = function(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.login-form').forEach(form => form.classList.remove('active'));
        
        if (event && event.target) {
            event.target.classList.add('active');
        }
        const targetForm = document.getElementById(tab + '-login');
        if (targetForm) targetForm.classList.add('active');
    };

    /**
     * Chuyển sang popup đăng ký
     */
    window.switchToSignup = function() {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) loginModal.classList.remove('show');
        setTimeout(() => openSignupPopup(), 150);
    };

    /**
     * Chuyển sang popup đăng nhập
     */
    window.switchToLogin = function() {
        const signupModal = document.getElementById('signupModal');
        if (signupModal) signupModal.classList.remove('show');
        setTimeout(() => openLoginPopup(), 150);
    };

    /**
     * Chuyển sang đăng nhập bằng mật khẩu
     */
    window.switchToPasswordLogin = function() {
        switchTab('email');
    };

    /**
     * Chuyển sang đăng ký bằng email
     */
    window.switchToEmailSignup = function() {
        alert('Tính năng đăng ký bằng email sẽ được thêm sau');
    };

    // Event listener cho login form submit
    document.addEventListener('click', function(e) {
        const submitBtn = e.target.closest('.login-submit-btn');
        if (!submitBtn || submitBtn.disabled) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const form = submitBtn.closest('.login-form.active') || submitBtn.closest('.login-form');
        if (!form) return;
        
        const isEmailLogin = form.id === 'email-login';
        let username = '';
        let password = '';
        
        if (isEmailLogin) {
            username = form.querySelector('.email-input')?.value.trim() || '';
            password = form.querySelector('.password-input')?.value || '';
        } else {
            const phoneInput = form.querySelector('.phone-input')?.value.trim() || '';
            const codeInput = form.querySelector('.code-input')?.value.trim() || '';
            if (!phoneInput || !codeInput) {
                alert('Vui lòng nhập đầy đủ thông tin đăng nhập.');
                return;
            }
            username = phoneInput;
            password = codeInput;
        }
        
        if (!username || !password) {
            alert('Vui lòng nhập đầy đủ thông tin đăng nhập.');
            return;
        }
        
        // Disable button và hiển thị loading
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang đăng nhập...';
        
        // Gửi AJAX request
        sendAjaxRequest('puna_tiktok_login', {
            username: username,
            password: password,
            remember: true
        })
        .then(data => {
            if (data.success) {
                closeModal();
                window.location.reload();
            } else {
                alert(data.data && data.data.message 
                    ? data.data.message 
                    : 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            alert('Lỗi kết nối. Vui lòng thử lại.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // ============================================
    // LIKE/UNLIKE VIDEO
    // ============================================
    
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="like"]');
        if (!actionItem) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) return;
        
        // Kiểm tra đăng nhập
        if (!checkLogin(e)) return;
        
        // Thêm animation
        actionItem.classList.add('liking');
        setTimeout(() => actionItem.classList.remove('liking'), 300);
        
        // Gửi AJAX request
        sendAjaxRequest('puna_tiktok_toggle_like', { post_id: postId })
        .then(data => {
            if (data.success) {
                const isLiked = data.data.is_liked;
                const likes = data.data.likes;
                
                // Cập nhật UI
                actionItem.classList.toggle('liked', isLiked);
                
                // Cập nhật số lượng likes
                const countElement = actionItem.querySelector('.count');
                if (countElement) {
                    countElement.textContent = formatNumber(likes);
                }
            } else {
                console.error('Like error:', data.data?.message);
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
        });
    });

    // ============================================
    // COMMENTS FUNCTIONALITY
    // ============================================
    
    /**
     * Mở sidebar comments
     * @param {string} postId - ID của post
     */
    function openCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Đóng sidebar comments
     * @param {string} postId - ID của post
     */
    function closeCommentsSidebar(postId) {
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    }

    // Event listener cho nút mở comments
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="comment"]');
        if (actionItem && actionItem.dataset.postId) {
            // Kiểm tra đăng nhập
            if (!checkLogin(e, () => {
                openCommentsSidebar(actionItem.dataset.postId);
            })) return;
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

    // Đóng khi click vào overlay
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
    });

    /**
     * Thêm comment mới vào danh sách
     */
    function addCommentToList(postId, commentText, commentId) {
        const overlay = document.getElementById('comments-overlay-' + postId) || document.querySelector('.comments-overlay');
        if (!overlay) return;
        
        const commentsList = overlay.querySelector('.comments-list');
        if (!commentsList) return;
        
        // Xóa message "no comments" nếu có
        const noComments = commentsList.querySelector('.no-comments');
        if (noComments) noComments.remove();
        
        // Lấy thông tin user hiện tại
        const currentUser = puna_tiktok_ajax.current_user || { display_name: 'Bạn', user_id: 0 };
        const finalCommentId = commentId || ('temp-' + Date.now());
        
        // Tạo comment element
        const commentElement = document.createElement('div');
        commentElement.className = 'comment-item';
        commentElement.setAttribute('data-comment-id', finalCommentId);
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
        const overlay = document.getElementById('comments-overlay-' + postId) || document.querySelector('.comments-overlay');
        if (!overlay) return;
        
        // Tìm hoặc tạo replies section
        let repliesSection = overlay.querySelector(`.comment-replies[data-parent-id="${parentId}"]`);
        
        if (!repliesSection) {
            const parentItem = overlay.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
            if (!parentItem) return;
            
            repliesSection = document.createElement('div');
            repliesSection.className = 'comment-replies';
            repliesSection.setAttribute('data-parent-id', parentId);
            parentItem.parentElement.insertBefore(repliesSection, parentItem.nextSibling);
        }
        
        const currentUser = puna_tiktok_ajax.current_user || { display_name: 'Bạn', user_id: 0 };
        const finalCommentId = commentId || ('temp-' + Date.now());
        
        // Tạo reply element
        const replyElement = document.createElement('div');
        replyElement.className = 'comment-item comment-reply';
        replyElement.setAttribute('data-comment-id', finalCommentId);
        replyElement.innerHTML = `
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
        // Cập nhật trong video sidebar
        const videoSidebar = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count`);
        if (videoSidebar) {
            const currentCount = parseInt(videoSidebar.textContent.replace(/[^\d]/g, '')) || 0;
            videoSidebar.textContent = formatNumber(currentCount + 1);
        }
        
        // Cập nhật trong comments header
        const commentsHeader = document.querySelector(`#comments-overlay-${postId} .comments-header h3`);
        if (commentsHeader) {
            const currentCount = parseInt(commentsHeader.textContent.match(/\d+/)?.[0]) || 0;
            commentsHeader.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
    }

    /**
     * Xử lý reply link click - hiển thị reply input
     */
    document.addEventListener('click', function(e) {
        const replyLink = e.target.closest('.reply-link');
        if (!replyLink || !replyLink.dataset.commentId) return;
        
        const parentCommentId = parseInt(replyLink.dataset.commentId, 10);
        
        // Kiểm tra đăng nhập và xử lý reply
        if (!checkLogin(e, () => {
            const commentItem = replyLink.closest('.comment-item');
        const overlay = replyLink.closest('.comments-overlay');
        const postId = overlay ? overlay.id.replace('comments-overlay-', '') : '';
        
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
        })) return;
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
        
        // Kiểm tra đăng nhập
        if (!checkLogin(e)) return;
        
        const item = delBtn.closest('.comment-item');
        const overlay = delBtn.closest('.comments-overlay');
        const overlayId = overlay ? overlay.id : '';
        const postId = overlayId ? overlayId.replace('comments-overlay-', '') : '';
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
        
        if (postId) {
            const sidebarCount = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count`);
            if (sidebarCount) {
                const current = parseInt(sidebarCount.textContent.replace(/[^\d]/g, ''), 10) || 0;
                const newCount = Math.max(0, current - totalCountToSubtract);
                sidebarCount.textContent = formatNumber(newCount);
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
            console.warn('Invalid comment ID:', delBtn.dataset.commentId);
            return;
        }
        
        // Gửi delete request
        sendAjaxRequest('puna_tiktok_delete_comment', { comment_id: commentId })
        .then(res => {
            if (!res.success) {
                console.error('Delete comment error:', res.data?.message);
            } else {
                console.log('Comment deleted successfully, including', res.data?.deleted_count || 1, 'comment(s)');
            }
        })
        .catch(err => {
            console.error('Delete comment request error:', err);
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
        
        // Kiểm tra đăng nhập
        if (!checkLogin(e)) return;
        if (!commentId) return;
        
        const isLiked = heartIcon.classList.contains('fa-solid');
        const currentLikes = parseInt(span.textContent.replace(/[^\d]/g, ''), 10) || 0;
        
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
        sendAjaxRequest('puna_tiktok_toggle_comment_like', { comment_id: commentId })
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
                console.error('Like comment error:', data.data?.message);
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            // Revert optimistic update
            if (isLiked) {
                heartIcon.classList.remove('fa-regular');
                heartIcon.classList.add('fa-solid', 'liked');
            } else {
                heartIcon.classList.remove('fa-solid', 'liked');
                heartIcon.classList.add('fa-regular');
            }
            span.textContent = formatNumber(currentLikes);
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
                alert('Đã báo cáo bình luận.');
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Không thể báo cáo.');
            }
        })
        .catch(() => alert('Lỗi kết nối.'));
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
                console.log('View count updated:', data.data.views);
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

    // ============================================
    // PROFILE TABS
    // ============================================
    
    const tabs = document.querySelectorAll('.profile-tab');
    const tabContents = document.querySelectorAll('.profile-videos-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.style.display = 'block';
            }
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
            if (realSearchInput) realSearchInput.focus();
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
    // CHECK LOGIN FOR MENU ITEMS
    // ============================================
    
    /**
     * Kiểm tra đăng nhập khi click vào các menu items cần đăng nhập
     * Áp dụng cho: Tải lên, Tin nhắn, Hồ sơ
     */
    function checkLoginForMenuItems() {
        const menuLinks = document.querySelectorAll('.sidebar-menu .nav-link');
        const loginRequiredItems = ['Tải lên', 'Tin nhắn', 'Hồ sơ'];
        
        menuLinks.forEach(link => {
            const menuText = link.querySelector('.menu-text');
            if (!menuText) return;
            
            const text = menuText.textContent.trim();
            const url = link.getAttribute('href');
            
            // Kiểm tra nếu là menu item cần đăng nhập
            const needsLogin = loginRequiredItems.some(item => text === item) ||
                              (url && (url.includes('/messages') || url.includes('/profile') || url.includes('post-new.php')));
            
            if (needsLogin) {
                link.addEventListener('click', function(e) {
                    // Kiểm tra đăng nhập
                    checkLogin(e);
                });
            }
        });
    }
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    // Khởi tạo volume state
    applyVolumeToAllVideos();
    updateGlobalVolumeUI();
    
    // Kiểm tra đăng nhập cho menu items
    checkLoginForMenuItems();
});