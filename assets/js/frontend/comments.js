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

    /**
     * Helper function to find postId from various sources
     */
    function findPostId(element) {
        // Try element's dataset
        if (element?.dataset?.postId && element.dataset.postId !== '0') {
            return parseInt(element.dataset.postId, 10);
        }
        
        // Try closest overlay
        const overlay = element?.closest('.comments-overlay');
        if (overlay?.id) {
            const overlayId = overlay.id.replace('comments-overlay-', '');
            if (overlayId && overlayId !== '0') {
                return parseInt(overlayId, 10);
            }
        }
        
        // Try watch comments container
        const watchComments = element?.closest('.video-watch-comments');
        if (watchComments) {
            const commentInput = watchComments.querySelector('.comment-input[data-post-id]');
            if (commentInput?.dataset.postId && commentInput.dataset.postId !== '0') {
                return parseInt(commentInput.dataset.postId, 10);
            }
        }
        
        // Try video element
        const videoElement = document.querySelector('.tiktok-video[data-post-id]');
        if (videoElement?.dataset.postId && videoElement.dataset.postId !== '0') {
            return parseInt(videoElement.dataset.postId, 10);
        }
        
        // Try URL
        const urlMatch = window.location.pathname.match(/\/(\d+)\//);
        if (urlMatch?.[1] && urlMatch[1] !== '0') {
            return parseInt(urlMatch[1], 10);
        }
        
        return null;
    }

    function handleCommentInput(input) {
        const container = input.closest('.comment-input-container');
        const btn = container?.querySelector('.submit-comment-btn');
        if (btn) {
            btn.disabled = input.value.trim() === '';
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('comment-input') || e.target.classList.contains('reply-input-field')) {
            handleCommentInput(e.target);
        }
    });

    document.addEventListener('click', function(e) {
        const submitBtn = e.target.closest('.submit-comment-btn');
        if (!submitBtn || submitBtn.disabled) return;
        
        e.preventDefault();
        
        const container = submitBtn.closest('.comment-input-container');
        
        // Check if this is a reply input container
        const replyContainer = submitBtn.closest('.reply-input-container');
        const isReplyContainer = !!replyContainer;
        
        // Find input - prioritize reply-input-field if in reply container
        let input = null;
        if (isReplyContainer) {
            input = container?.querySelector('.reply-input-field');
        }
        if (!input) {
            input = container?.querySelector('.comment-input:not(.reply-input-field)') || container?.querySelector('.comment-input');
        }
        
        // Get postId using helper function
        let postId = findPostId(submitBtn) || findPostId(input);
        
        if (!postId || postId === 0 || isNaN(postId)) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Post';
            return;
        }
        
        // Get parentId from submitBtn or input field
        let parentId = 0;
        
        // Check if this is a reply input
        const isReplyInput = isReplyContainer || 
                            container?.classList.contains('reply-input') || 
                            input?.classList.contains('reply-input-field');
        
        if (isReplyInput) {
            // For reply, get parentId from dataset or find from closest comment item
            if (submitBtn.dataset.parentId) {
                parentId = parseInt(submitBtn.dataset.parentId, 10);
            } else if (input?.dataset.parentId) {
                parentId = parseInt(input.dataset.parentId, 10);
            } else if (replyContainer) {
                // Find the comment item that this reply is for
                let prevSibling = replyContainer.previousElementSibling;
                while (prevSibling) {
                    if (prevSibling.classList?.contains('comment-item')) {
                        parentId = parseInt(prevSibling.dataset.commentId, 10);
                        break;
                    }
                    prevSibling = prevSibling.previousElementSibling;
                }
            }
        } else if (submitBtn.dataset.parentId) {
            parentId = parseInt(submitBtn.dataset.parentId, 10);
        } else if (input?.dataset.parentId) {
            parentId = parseInt(input.dataset.parentId, 10);
        }
        
        const commentText = input?.value.trim();
        
        if (!commentText) return;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Posting...';
        
        const params = {
            post_id: postId,
            comment_text: commentText
        };
        if (parentId > 0) {
            params.parent_id = parentId;
        }
        
        if (!isLoggedIn()) {
            params.guest_id = GuestStorage.getGuestId();
            params.guest_name = 'Guest';
        }
        
        sendAjaxRequest('puna_tiktok_add_comment', params)
        .then(data => {
            if (data.success) {
                const html = data.data?.html || '';
                const isReply = data.data?.is_reply || false;
                
                if (!html || !html.trim()) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    return;
                }
                
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
                
                if (!commentsList) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    return;
                }
                
                const noComments = commentsList.querySelector('.no-comments');
                if (noComments) noComments.remove();
                
                // Create a temporary container to parse HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html.trim();
                
                // Get the first element (should be comment-item)
                let commentElement = tempDiv.firstElementChild;
                
                // If first child is text node (whitespace), get next element
                while (commentElement && commentElement.nodeType !== 1) {
                    commentElement = commentElement.nextSibling;
                }
                
                if (!commentElement) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    return;
                }
                
                // Insert comment into DOM
                if (isReply && parentId > 0) {
                    // Find the parent comment - search in entire document
                    let parentItem = null;
                    
                    // First try to find in the same container as commentsList
                    parentItem = commentsList.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
                    
                    // If not found, search in entire document
                    if (!parentItem) {
                        const watchComments = document.querySelector('.video-watch-comments');
                        const searchContainer = overlay || watchComments || document;
                        parentItem = searchContainer.querySelector(`.comment-item[data-comment-id="${parentId}"]`);
                    }
                    
                    if (!parentItem) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                        return;
                    }
                    
                    let repliesSection = null;
                    let nextSibling = parentItem.nextElementSibling;
                    
                    // Look for existing replies section
                    while (nextSibling) {
                        if (nextSibling.classList?.contains('comment-replies') && 
                            nextSibling.dataset.parentId === parentId.toString()) {
                            repliesSection = nextSibling;
                            break;
                        }
                        if (nextSibling.classList?.contains('comment-item') && 
                            !nextSibling.classList.contains('comment-reply')) {
                            break;
                        }
                        nextSibling = nextSibling.nextElementSibling;
                    }
                    
                    if (repliesSection) {
                        // Remove reply input if exists
                        const replyInput = repliesSection.querySelector('.reply-input-container');
                        if (replyInput) replyInput.remove();
                        
                        const showMoreBtn = repliesSection.querySelector('.show-more-replies-btn');
                        const moreContainer = repliesSection.querySelector('.more-replies-container');
                        
                        if (showMoreBtn && moreContainer) {
                            repliesSection.insertBefore(commentElement, showMoreBtn);
                            
                            // Update show more button count
                            const currentLoaded = parseInt(showMoreBtn.dataset.loaded, 10) || 0;
                            const total = parseInt(showMoreBtn.dataset.total, 10) || 0;
                            const newLoaded = currentLoaded + 1;
                            const newRemaining = total - newLoaded;
                            
                            showMoreBtn.dataset.loaded = newLoaded;
                            
                            if (newRemaining > 0) {
                                showMoreBtn.textContent = `View more replies (${newRemaining})`;
                            } else {
                                const remainingReplies = moreContainer.querySelectorAll('.comment-item');
                                remainingReplies.forEach(r => repliesSection.insertBefore(r, showMoreBtn));
                                showMoreBtn.style.display = 'none';
                                moreContainer.style.display = 'none';
                            }
                        } else {
                            repliesSection.appendChild(commentElement);
                        }
                    } else {
                        // Create new replies section
                        repliesSection = document.createElement('div');
                        repliesSection.className = 'comment-replies';
                        repliesSection.setAttribute('data-parent-id', parentId);
                        repliesSection.appendChild(commentElement);
                        if (parentItem.parentElement) {
                            parentItem.parentElement.insertBefore(repliesSection, parentItem.nextSibling);
                        } else {
                            parentItem.parentNode?.appendChild(repliesSection);
                        }
                    }
                    
                    commentElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    // Top-level comment - insert at the beginning
                    if (commentsList && commentsList.firstChild) {
                        commentsList.insertBefore(commentElement, commentsList.firstChild);
                    } else if (commentsList) {
                        commentsList.appendChild(commentElement);
                    }
                    commentsList.scrollTop = 0;
                }
                
                // Remove reply input if exists (must be done after inserting comment)
                if (isReply && parentId > 0) {
                    const replyInput = document.querySelector('.reply-input-container');
                    if (replyInput) replyInput.remove();
                }
                
                // Clear input and reset button
                if (input) {
                    input.value = '';
                    handleCommentInput(input);
                }
                
                // Reset submit button
                submitBtn.disabled = true;
                submitBtn.textContent = 'Post';
                if (submitBtn.hasAttribute('data-parent-id')) {
                    submitBtn.removeAttribute('data-parent-id');
                }
                
                // Update comment count
                updateCommentCount(postId);
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post';
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Post';
        });
    });

    function updateCommentCount(postId) {
        const videoSidebar = document.querySelector(`.action-item[data-action="comment"][data-post-id="${postId}"] .count, .interaction-item[data-action="comment-toggle"][data-post-id="${postId}"] .stat-count`);
        if (videoSidebar) {
            const currentCount = parseInt(videoSidebar.textContent.replace(/[^\d]/g, '')) || 0;
            videoSidebar.textContent = formatNumber(currentCount + 1);
        }
        
        const commentsHeader = document.querySelector(`#comments-overlay-${postId} .comments-header h3`);
        if (commentsHeader) {
            const currentCount = parseInt(commentsHeader.textContent.match(/\d+/)?.[0]) || 0;
            commentsHeader.textContent = `Comments (${formatNumber(currentCount + 1)})`;
        }
        
        const commentsTab = document.querySelector(`.comments-tab[data-tab="comments"]`);
        if (commentsTab) {
            const currentCount = parseInt(commentsTab.textContent.match(/\d+/)?.[0]) || 0;
            commentsTab.textContent = `Comments (${formatNumber(currentCount + 1)})`;
        }
    }

    document.addEventListener('click', function(e) {
        const replyLink = e.target.closest('.reply-link');
        if (!replyLink || !replyLink.dataset.commentId) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const parentCommentId = parseInt(replyLink.dataset.commentId, 10);
        const commentItem = replyLink.closest('.comment-item');
        
        const postId = findPostId(replyLink);
        if (!postId) {
            return;
        }
        
        const existingInput = document.querySelector('.reply-input-container');
        if (existingInput) existingInput.remove();
        
        // Get reply input HTML from server
        const isReply = commentItem.classList.contains('comment-reply');
        sendAjaxRequest('puna_tiktok_get_reply_input', {
            post_id: postId,
            parent_id: parentCommentId,
            is_reply: isReply
        })
        .then(data => {
            if (data.success && data.data?.html) {
                const html = data.data.html;
                
                // Create a temporary container to parse HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html.trim();
                const replyContainer = tempDiv.firstElementChild;
                
                if (replyContainer) {
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
                    
                    if (repliesSection) {
                        repliesSection.insertBefore(replyContainer, repliesSection.firstChild);
                    } else if (commentItem.parentElement) {
                        commentItem.parentElement.insertBefore(replyContainer, commentItem.nextSibling);
                    } else {
                        // Fallback: append to comment item if parent doesn't exist
                        commentItem.parentNode?.appendChild(replyContainer);
                    }
                    
                    const input = replyContainer.querySelector('.reply-input-field');
                    if (input) setTimeout(() => input.focus(), 100);
                    
                    handleCommentInput(input);
                }
            }
        })
        .catch(() => {
            // Silently fail - user can try again
        });
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
        const postId = findPostId(delBtn);
        
        const commentsList = delBtn.closest('.comments-list');
        const sidebar = delBtn.closest('.comments-sidebar');
        const watchComments = delBtn.closest('.video-watch-comments');
        
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
                    header.textContent = `Comments (${formatNumber(newCount)})`;
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
                    commentsTab.textContent = `Comments (${formatNumber(newCount)})`;
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
                            if (hiddenReplies.length > 0 && repliesSection) {
                                const nextReply = hiddenReplies[0];
                                if (showMoreBtn.parentNode === repliesSection) {
                                    repliesSection.insertBefore(nextReply, showMoreBtn);
                                } else {
                                    repliesSection.appendChild(nextReply);
                                }
                        
                        const remaining = hiddenReplies.length - 1;
                        if (remaining > 0) {
                            showMoreBtn.textContent = `View more replies (${remaining})`;
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
            // Comment deleted successfully
        })
        .catch(() => {
            // Error handling silently
        });
    });

    // Like/Unlike comment
    document.addEventListener('click', function(e) {
        const likesElement = e.target.closest('.comment-likes');
        if (!likesElement || !likesElement.dataset.commentId) return;
        
        // Prevent double click
        if (likesElement.dataset.processing === 'true') return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const commentId = parseInt(likesElement.dataset.commentId, 10);
        if (!commentId) return;
        
        const span = likesElement.querySelector('span');
        if (!span) return;
        
        const isLiked = likesElement.classList.contains('liked');
        const currentLikes = parseInt(span.textContent.replace(/[^\d]/g, ''), 10) || 0;
        
        // Mark as processing
        likesElement.dataset.processing = 'true';
        
        // Optimistic update
        if (isLiked) {
            likesElement.classList.remove('liked');
            span.textContent = formatNumber(Math.max(0, currentLikes - 1));
        } else {
            likesElement.classList.add('liked');
            span.textContent = formatNumber(currentLikes + 1);
        }
        
        likesElement.classList.add('liking');
        setTimeout(() => likesElement.classList.remove('liking'), 300);
        
        if (!isLoggedIn()) {
            GuestStorage.toggleLikeComment(commentId);
        }
        
        sendAjaxRequest('puna_tiktok_toggle_comment_like', { 
            comment_id: commentId,
            action_type: isLiked ? 'unlike' : 'like'
        })
        .then(data => {
            // Remove processing flag
            likesElement.dataset.processing = 'false';
            
            if (data.success) {
                const isLikedNow = data.data.is_liked;
                const likes = data.data.likes;
                
                // Update class based on server response
                if (isLikedNow) {
                    likesElement.classList.add('liked');
                } else {
                    likesElement.classList.remove('liked');
                }
                
                if (!isLoggedIn()) {
                    const liked = GuestStorage.getLikedComments();
                    if (isLikedNow && liked.indexOf(commentId) === -1) {
                        liked.push(commentId);
                        GuestStorage.setLikedComments(liked);
                    } else if (!isLikedNow) {
                        const index = liked.indexOf(commentId);
                        if (index > -1) {
                            liked.splice(index, 1);
                            GuestStorage.setLikedComments(liked);
                        }
                    }
                }
                
                // Update count from server
                span.textContent = formatNumber(likes);
            } else {
                // Revert optimistic update
                if (isLiked) {
                    likesElement.classList.add('liked');
                } else {
                    likesElement.classList.remove('liked');
                }
                span.textContent = formatNumber(currentLikes);
                if (!isLoggedIn()) {
                    GuestStorage.toggleLikeComment(commentId);
                }
            }
        })
        .catch(() => {
            // Remove processing flag
            likesElement.dataset.processing = 'false';
            
            // Revert optimistic update
            if (isLiked) {
                likesElement.classList.add('liked');
            } else {
                likesElement.classList.remove('liked');
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
            // Comment reported successfully
        })
        .catch(() => {
            // Error handling silently
        });
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

