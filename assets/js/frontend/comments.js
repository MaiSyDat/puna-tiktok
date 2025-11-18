/**
 * Comments functionality
 */

document.addEventListener("DOMContentLoaded", function() {
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
            
            const isWatchPage = document.querySelector('.video-watch-page');
            if (isWatchPage) {
                const commentsSection = document.querySelector('.video-watch-comments');
                if (commentsSection) {
                    commentsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                openCommentsSidebar(actionItem.dataset.postId);
            }
        }
    });

    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.close-comments-btn');
        if (closeBtn && closeBtn.dataset.postId) {
            e.preventDefault();
            closeCommentsSidebar(closeBtn.dataset.postId);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('comments-overlay')) {
            const postId = e.target.id.replace('comments-overlay-', '');
            closeCommentsSidebar(postId);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openOverlay = document.querySelector('.comments-overlay.show');
            if (openOverlay) {
                const postId = openOverlay.id.replace('comments-overlay-', '');
                closeCommentsSidebar(postId);
            }
        }
    });

    function handleCommentInput(input) {
        const container = input.closest('.comment-input-container');
        const btn = container?.querySelector('.submit-comment-btn');
        if (btn) {
            btn.disabled = input.value.trim() === '';
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('comment-input')) {
            handleCommentInput(e.target);
        }
    });

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('reply-input-field')) {
            handleCommentInput(e.target);
        }
    });

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
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang đăng...';
        
        const params = {
            post_id: postId,
            comment_text: commentText
        };
        if (parentId > 0) {
            params.parent_id = parentId;
        }
        
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
                
                if (input) input.value = '';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Đăng';
                submitBtn.removeAttribute('data-parent-id');
                
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

    function addCommentToList(postId, commentText, commentId) {
        let commentsList = null;
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            commentsList = overlay.querySelector('.comments-list');
        } else {
            const watchComments = document.querySelector('.video-watch-comments');
            if (watchComments) {
                commentsList = watchComments.querySelector('.comments-list');
            }
        }
        
        if (!commentsList) return;
        
        const noComments = commentsList.querySelector('.no-comments');
        if (noComments) noComments.remove();
        
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

    function addReplyToList(postId, parentId, commentText, commentId) {
        let parentItem = null;
        let container = null;
        
        const overlay = document.getElementById('comments-overlay-' + postId);
        if (overlay) {
            container = overlay;
            parentItem = overlay.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
        } else {
            const watchComments = document.querySelector('.video-watch-comments');
            if (watchComments) {
                container = watchComments;
                parentItem = watchComments.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
            }
        }
        
        if (!parentItem || !container) return;
        
        let repliesSection = container.querySelector(`.comment-replies[data-parent-id="${parentId}"]`);
        
        if (!repliesSection) {
            repliesSection = document.createElement('div');
            repliesSection.className = 'comment-replies';
            repliesSection.setAttribute('data-parent-id', parentId);
            parentItem.parentElement.insertBefore(repliesSection, parentItem.nextSibling);
        }
        
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
        
        const replyInput = repliesSection.querySelector('.reply-input-container');
        if (replyInput) replyInput.remove();
        
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

    function updateCommentCount(postId) {
        const videoSidebar = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count, .interaction-item[data-action="comment-toggle"][data-post-id="${postId}"] .stat-count`);
        if (videoSidebar) {
            const currentCount = parseInt(videoSidebar.textContent.replace(/[^\d]/g, '')) || 0;
            videoSidebar.textContent = formatNumber(currentCount + 1);
        }
        
        const commentsHeader = document.querySelector(`#comments-overlay-${postId} .comments-header h3`);
        if (commentsHeader) {
            const currentCount = parseInt(commentsHeader.textContent.match(/\d+/)?.[0]) || 0;
            commentsHeader.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
        
        const commentsTab = document.querySelector(`.comments-tab[data-tab="comments"]`);
        if (commentsTab) {
            const currentCount = parseInt(commentsTab.textContent.match(/\d+/)?.[0]) || 0;
            commentsTab.textContent = `Bình luận (${formatNumber(currentCount + 1)})`;
        }
    }

    document.addEventListener('click', function(e) {
        const replyLink = e.target.closest('.reply-link');
        if (!replyLink || !replyLink.dataset.commentId) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const parentCommentId = parseInt(replyLink.dataset.commentId, 10);
        const commentItem = replyLink.closest('.comment-item');
        const overlay = replyLink.closest('.comments-overlay');
        
        let postId = '';
        if (overlay) {
            postId = overlay.id.replace('comments-overlay-', '');
        } else {
            const commentInput = document.querySelector('.comment-input[data-post-id]');
            if (commentInput) {
                postId = commentInput.dataset.postId;
            } else {
                const videoElement = document.querySelector('.tiktok-video[data-post-id]');
                if (videoElement) {
                    postId = videoElement.dataset.postId;
                } else {
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
        
        const existingInput = document.querySelector('.reply-input-container');
        if (existingInput) existingInput.remove();
        
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
        
        if (repliesSection) {
            repliesSection.insertBefore(replyContainer, repliesSection.firstChild);
        } else {
            commentItem.parentElement.insertBefore(replyContainer, commentItem.nextSibling);
        }
        
        const input = replyContainer.querySelector('.reply-input-field');
        if (input) setTimeout(() => input.focus(), 100);
        
        handleCommentInput(replyContainer.querySelector('.reply-input-field'));
    });

    document.addEventListener('click', function(e) {
        const cancelBtn = e.target.closest('.cancel-reply-btn');
        if (cancelBtn) {
            e.preventDefault();
            e.stopPropagation();
            const container = cancelBtn.closest('.reply-input-container');
            if (container) container.remove();
        }
    });

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

    // Delete comment
    document.addEventListener('click', function(e) {
        const delBtn = e.target.closest('.comment-action-delete');
        if (!delBtn) return;
        
        const item = delBtn.closest('.comment-item');
        const overlay = delBtn.closest('.comments-overlay');
        const watchComments = delBtn.closest('.video-watch-comments');
        
        let postId = '';
        if (overlay) {
            postId = overlay.id.replace('comments-overlay-', '');
        } else if (watchComments) {
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
        
        const dropdown = delBtn.closest('.comment-options-dropdown');
        if (dropdown) dropdown.classList.remove('show');
        
        const isReply = item?.classList.contains('comment-reply');
        let repliesCount = 0;
        
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
        
        if (item) item.remove();
        
        const totalCountToSubtract = 1 + repliesCount;
        
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
            const sidebarCount = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count, .interaction-item[data-action="comment-toggle"][data-post-id="${postId}"] .stat-count`);
            if (sidebarCount) {
                const current = parseInt(sidebarCount.textContent.replace(/[^\d]/g, ''), 10) || 0;
                const newCount = Math.max(0, current - totalCountToSubtract);
                sidebarCount.textContent = formatNumber(newCount);
            }
        }
        
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
        
        if (isTempId) return;
        
        commentId = parseInt(commentId, 10);
        if (!commentId || isNaN(commentId)) {
            return;
        }
        
        const params = { comment_id: commentId };
        if (!isLoggedIn()) {
            params.guest_id = GuestStorage.getGuestId();
        }
        
        sendAjaxRequest('puna_tiktok_delete_comment', params)
        .then(res => {
            if (!res.success) {
            }
        })
        .catch(err => {
        });
    });

    // Like/Unlike comment
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
        
        if (!isLoggedIn()) {
            GuestStorage.toggleLikeComment(commentId);
        }
        
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
                if (isLiked) {
                    heartIcon.classList.remove('fa-regular');
                    heartIcon.classList.add('fa-solid', 'liked');
                } else {
                    heartIcon.classList.remove('fa-solid', 'liked');
                    heartIcon.classList.add('fa-regular');
                }
                span.textContent = formatNumber(currentLikes);
                if (!isLoggedIn()) {
                    GuestStorage.toggleLikeComment(commentId);
                }
            }
        })
        .catch(error => {
            if (isLiked) {
                heartIcon.classList.remove('fa-regular');
                heartIcon.classList.add('fa-solid', 'liked');
            } else {
                heartIcon.classList.remove('fa-solid', 'liked');
                heartIcon.classList.add('fa-regular');
            }
            span.textContent = formatNumber(currentLikes);
            if (!isLoggedIn()) {
                GuestStorage.toggleLikeComment(commentId);
            }
        });
    });

    // Report comment
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

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.comment-actions')) {
            document.querySelectorAll('.comment-options-dropdown').forEach(d => d.classList.remove('show'));
        }
    });
});

