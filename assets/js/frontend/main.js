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
    // Cho phép mở comments ngay cả khi chưa đăng nhập (chỉ cần đăng nhập để like/reply/comment)
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="comment"]');
        if (actionItem && actionItem.dataset.postId) {
            e.preventDefault();
            e.stopPropagation();
            openCommentsSidebar(actionItem.dataset.postId);
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
    
    // Profile Tabs Switching - Dùng chung cho profile page và author.php
    const profileTabs = document.querySelectorAll('.profile-tab');
    const profileVideosSections = document.querySelectorAll('.profile-videos-section');
    const profileTabContents = document.querySelectorAll('.profile-tab-content');
    
    profileTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update active state
            profileTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide content - Support cả profile-videos-section và profile-tab-content
            profileVideosSections.forEach(section => {
                section.style.display = 'none';
                if (section.id === targetTab + '-tab') {
                    section.style.display = 'block';
                }
            });
            
            profileTabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === targetTab + '-tab') {
                    content.classList.add('active');
                }
            });
        });
    });
    
    // Profile Follow Button
    const profileFollowBtn = document.querySelector('.profile-follow-btn');
    if (profileFollowBtn) {
        profileFollowBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const isFollowing = this.getAttribute('data-is-following') === '1';
            
            // Check login first
            if (!checkLogin(e)) {
                return;
            }
            
            // Toggle follow state (sẽ implement AJAX sau)
            this.classList.toggle('following');
            const newState = !isFollowing;
            this.setAttribute('data-is-following', newState ? '1' : '0');
            
            if (newState) {
                this.innerHTML = '<span>Đã theo dõi</span>';
            } else {
                this.innerHTML = '<span>Theo dõi</span>';
            }
            
            // TODO: Add AJAX call to save follow state
            // sendAjaxRequest('puna_tiktok_toggle_follow', { user_id: userId, follow: newState });
        });
    }
    
    // Comment Follow Button (small buttons in comments)
    document.addEventListener('click', function(e) {
        const commentFollowBtn = e.target.closest('.comment-follow-btn');
        if (commentFollowBtn) {
            e.preventDefault();
            const userId = commentFollowBtn.getAttribute('data-user-id');
            
            // Check login
            if (!checkLogin(e)) {
                return;
            }
            
            // Toggle follow state
            commentFollowBtn.classList.toggle('following');
            
            if (commentFollowBtn.classList.contains('following')) {
                commentFollowBtn.innerHTML = '<i class="fa-solid fa-check"></i> Đã theo dõi';
            } else {
                commentFollowBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Theo dõi';
            }
            
            // TODO: Add AJAX call to save follow state
        }
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
                console.error('Error loading search history:', error);
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
                console.error('Error loading suggestions:', error);
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
                console.error('Error saving search:', error);
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
                        console.error('Error clearing history:', error);
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
                console.error('Error loading related searches:', error);
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
                console.error('Error loading popular searches:', error);
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
                console.error('Error loading popular searches:', error);
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
            // Áp dụng volume state từ trang index
            applyVideoVolumeSettings(watchVideo);
            // Cập nhật lại sau khi apply toàn bộ
            applyVolumeToAllVideos();
            
            // Auto-play
            watchVideo.play().catch(e => {
                console.log('Auto-play prevented:', e);
            });
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
                        console.error('Failed to copy:', err);
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
                        console.error('Fallback copy failed:', err);
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
        const coverImageInput = document.getElementById('coverImageInput');
        const coverPreviewImg = document.getElementById('coverPreviewImg');
        const coverPreview = document.getElementById('coverPreview');
        const editCoverBtn = document.getElementById('editCoverBtn');
        const locationInput = document.getElementById('videoLocation');
        const locationSuggestions = document.getElementById('locationSuggestions');
        const scheduleRadio = document.querySelectorAll('input[name="postSchedule"]');
        const scheduleDateTime = document.getElementById('scheduleDateTime');
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

        let selectedVideoFile = null;
        let videoDuration = 0;
        let xhr = null;

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
                alert('Vui lòng chọn file video hợp lệ');
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

            // Preview video
            const videoURL = URL.createObjectURL(file);
            if (videoPreview) {
                videoPreview.src = videoURL;
                videoPreview.style.display = 'block';
            }
            if (previewPlaceholder) {
                previewPlaceholder.style.display = 'none';
            }

            // Auto extract cover (first frame)
            extractVideoFrame(videoURL, (frameDataUrl) => {
                if (coverPreviewImg) {
                    coverPreviewImg.src = frameDataUrl;
                    coverPreviewImg.style.display = 'block';
                    const placeholder = coverPreview.querySelector('.cover-placeholder');
                    if (placeholder) placeholder.style.display = 'none';
                }
            });

            // Chuyển sang step 2
            if (step1) step1.classList.remove('active');
            if (step2) step2.classList.add('active');
            
            // Enable publish button
            if (publishBtn) {
                publishBtn.disabled = false;
            }
        }

        function extractVideoFrame(videoURL, callback) {
            const video = document.createElement('video');
            video.crossOrigin = 'anonymous';
            video.src = videoURL;
            video.currentTime = 0.1;
            
            video.onloadeddata = () => {
                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                callback(canvas.toDataURL('image/jpeg'));
            };
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

        // Cover image
        if (editCoverBtn) {
            editCoverBtn.addEventListener('click', () => {
                coverImageInput?.click();
            });
        }

        if (coverImageInput) {
            coverImageInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        if (coverPreviewImg) {
                            coverPreviewImg.src = event.target.result;
                            coverPreviewImg.style.display = 'block';
                            const placeholder = coverPreview.querySelector('.cover-placeholder');
                            if (placeholder) placeholder.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Location suggestions (placeholder)
        if (locationInput) {
            locationInput.addEventListener('focus', () => {
                // TODO: Implement location suggestions
                locationSuggestions?.classList.add('active');
            });

            locationInput.addEventListener('blur', () => {
                setTimeout(() => {
                    locationSuggestions?.classList.remove('active');
                }, 200);
            });
        }

        // Schedule toggle
        if (scheduleRadio.length > 0) {
            scheduleRadio.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    if (scheduleDateTime) {
                        scheduleDateTime.style.display = e.target.value === 'schedule' ? 'block' : 'none';
                    }
                });
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
                    alert('Vui lòng chọn video');
                    return;
                }

                publishVideo();
            });
        }

        // Cancel upload
        if (cancelUploadBtn) {
            cancelUploadBtn.addEventListener('click', () => {
                if (xhr) {
                    xhr.abort();
                    xhr = null;
                }
                resetUploadProgress();
            });
        }

        function publishVideo() {
            if (!selectedVideoFile) return;

            const formData = new FormData();
            formData.append('action', 'puna_tiktok_upload_video');
            formData.append('video', selectedVideoFile);
            formData.append('description', descriptionInput?.value || '');
            formData.append('location', locationInput?.value || '');
            formData.append('privacy', document.getElementById('videoPrivacy')?.value || 'public');
            formData.append('schedule', document.querySelector('input[name="postSchedule"]:checked')?.value || 'now');
            formData.append('schedule_date', document.getElementById('scheduleDateInput')?.value || '');
            formData.append('music_copyright_check', document.getElementById('musicCopyrightCheck')?.checked ? '1' : '0');
            formData.append('content_check_lite', document.getElementById('contentCheckLite')?.checked ? '1' : '0');
            
            // Cover image
            if (coverImageInput?.files[0]) {
                formData.append('cover_image', coverImageInput.files[0]);
            }

            formData.append('nonce', puna_tiktok_ajax?.nonce || '');

            // Show upload file info and progress bar
            if (uploadFileInfoElement) {
                uploadFileInfoElement.style.display = 'block';
            }
            
            // Set file name
            if (fileName) {
                fileName.textContent = selectedVideoFile.name;
            }
            
            // Reset and initialize progress bar
            if (uploadProgressFill) {
                uploadProgressFill.style.width = '0%';
            }
            if (uploadProgressText) {
                uploadProgressText.textContent = '0MB / ' + formatFileSize(selectedVideoFile.size);
            }
            if (uploadPercentage) {
                uploadPercentage.textContent = '0%';
            }
            
            // Set initial duration if available
            if (uploadDuration && videoDuration > 0) {
                uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
            }

            // Show loading overlay
            if (uploadLoadingOverlay) {
                uploadLoadingOverlay.style.display = 'flex';
            }

            // Upload progress
            xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable && e.total > 0) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    
                    // Update progress bar
                    if (uploadProgressFill) {
                        uploadProgressFill.style.width = percentComplete + '%';
                    }
                    
                    // Update progress text
                    if (uploadProgressText) {
                        uploadProgressText.textContent = formatFileSize(e.loaded) + ' / ' + formatFileSize(e.total);
                    }
                    
                    // Update percentage
                    if (uploadPercentage) {
                        uploadPercentage.textContent = Math.round(percentComplete) + '%';
                    }
                    
                    // Update duration if not set yet
                    if (uploadDuration && (!uploadDuration.textContent || uploadDuration.textContent === '')) {
                        if (videoDuration > 0) {
                            uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
                        }
                    }
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Redirect to video or profile
                            if (response.data && response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.href = puna_tiktok_ajax?.current_user?.user_id 
                                    ? `/author/${puna_tiktok_ajax.current_user.user_id}/`
                                    : '/';
                            }
                        } else {
                            alert(response.data?.message || 'Có lỗi xảy ra khi upload video');
                            if (uploadLoadingOverlay) {
                                uploadLoadingOverlay.style.display = 'none';
                            }
                            resetUploadProgress();
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Có lỗi xảy ra khi upload video');
                        if (uploadLoadingOverlay) {
                            uploadLoadingOverlay.style.display = 'none';
                        }
                        resetUploadProgress();
                    }
                } else {
                    alert('Có lỗi xảy ra khi upload video');
                    if (uploadLoadingOverlay) {
                        uploadLoadingOverlay.style.display = 'none';
                    }
                    resetUploadProgress();
                }
            });

            xhr.addEventListener('error', () => {
                alert('Có lỗi xảy ra khi upload video');
                if (uploadLoadingOverlay) {
                    uploadLoadingOverlay.style.display = 'none';
                }
                resetUploadProgress();
            });

            xhr.open('POST', puna_tiktok_ajax?.ajax_url || '/wp-admin/admin-ajax.php');
            xhr.send(formData);

            // Enable publish button after upload starts
            if (publishBtn) {
                publishBtn.disabled = true;
            }
        }

        function resetUploadProgress() {
            if (uploadProgressFill) uploadProgressFill.style.width = '0%';
            if (uploadProgressText) uploadProgressText.textContent = '0MB / 0MB';
            if (uploadPercentage) uploadPercentage.textContent = '0%';
            if (publishBtn) publishBtn.disabled = false;
            if (uploadFileInfoElement) {
                uploadFileInfoElement.style.display = 'none';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    }
});