/**
 * Video actions (like, save, share, delete)
 */

document.addEventListener("DOMContentLoaded", function() {
    // Helper function to revert guest state
    function revertGuestLikeState(postId, actionItem) {
        if (!isLoggedIn()) {
            if (typeof GuestStateHelpers !== 'undefined') {
                GuestStateHelpers.revertGuestState('like', postId);
            } else {
                GuestStorage.toggleLikeVideo(postId);
            }
            actionItem.classList.toggle('liked');
        }
    }
    
    // Like/Unlike video
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="like"], .interaction-item[data-action="like"]');
        if (!actionItem) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) return;
        
        actionItem.classList.add('liking');
        setTimeout(() => actionItem.classList.remove('liking'), 300);
        
        const wasLiked = actionItem.classList.contains('liked');
        if (!isLoggedIn()) {
            const isLiked = GuestStorage.toggleLikeVideo(postId);
            actionItem.classList.toggle('liked', isLiked);
        }
        
        sendAjaxRequest('puna_tiktok_toggle_like', { 
            post_id: postId,
            action_type: wasLiked ? 'unlike' : 'like'
        })
        .then(data => {
            if (data.success) {
                const isLiked = data.data.is_liked;
                const likes = data.data.likes;
                
                actionItem.classList.toggle('liked', isLiked);
                
                // Show toast notification
                if (typeof Toast !== 'undefined') {
                    Toast.success(isLiked ? 'video_liked' : 'video_unliked');
                }
                
                // Sync guest state
                if (typeof GuestStateHelpers !== 'undefined') {
                    GuestStateHelpers.syncGuestState('like', postId, isLiked);
                }
                
                const countElement = actionItem.querySelector('.count, .stat-count');
                if (countElement) {
                    countElement.textContent = formatNumber(likes);
                }
            } else {
                revertGuestLikeState(postId, actionItem);
            }
        })
        .catch(() => {
            revertGuestLikeState(postId, actionItem);
        });
    });

    // Helper function to revert guest save state
    function revertGuestSaveState(postId, actionItem) {
        if (!isLoggedIn()) {
            if (typeof GuestStateHelpers !== 'undefined') {
                GuestStateHelpers.revertGuestState('save', postId);
            } else {
                GuestStorage.toggleSaveVideo(postId);
            }
            actionItem.classList.toggle('saved');
        }
    }
    
    // Save/Unsave video
    document.addEventListener('click', function(e) {
        const actionItem = e.target.closest('.action-item[data-action="save"], .interaction-item[data-action="save"]');
        if (!actionItem) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = actionItem.dataset.postId;
        if (!postId) return;
        
        actionItem.classList.add('saving');
        setTimeout(() => actionItem.classList.remove('saving'), 300);
        
        const wasSaved = actionItem.classList.contains('saved');
        if (!isLoggedIn()) {
            const isSaved = GuestStorage.toggleSaveVideo(postId);
            actionItem.classList.toggle('saved', isSaved);
        }
        
        sendAjaxRequest('puna_tiktok_toggle_save', { 
            post_id: postId,
            action_type: wasSaved ? 'unsave' : 'save'
        })
        .then(data => {
            if (data.success) {
                const isSaved = data.data.is_saved;
                const saves = data.data.saves;
                
                actionItem.classList.toggle('saved', isSaved);
                
                // Show toast notification
                if (typeof Toast !== 'undefined') {
                    Toast.success(isSaved ? 'video_saved' : 'video_unsaved');
                }
                
                // Sync guest state
                if (typeof GuestStateHelpers !== 'undefined') {
                    GuestStateHelpers.syncGuestState('save', postId, isSaved);
                }
                
                const countEl = actionItem.querySelector('.count, .stat-count');
                if (countEl && saves !== undefined) {
                    countEl.textContent = formatNumber(saves);
                }
            } else {
                revertGuestSaveState(postId, actionItem);
            }
        })
        .catch(() => {
            revertGuestSaveState(postId, actionItem);
        });
    });

    // Share modal
    document.addEventListener('click', function(e) {
        const shareBtn = e.target.closest('.action-item[data-action="share"], .interaction-item[data-action="share"]');
        if (shareBtn) {
            e.preventDefault();
            e.stopPropagation();
            
            const postId = shareBtn.dataset.postId;
            if (!postId) return;
            
            openShareModal(postId);
        }
    });
    
    document.addEventListener('click', function(e) {
        const closeBtn = e.target.closest('.share-modal-close');
        const overlay = e.target.closest('.share-modal-overlay');
        
        if (closeBtn || overlay) {
            const modal = closeBtn ? closeBtn.closest('.share-modal') : overlay.closest('.share-modal');
            if (modal) {
                closeShareModal(modal);
            }
        }
    });
    
    document.addEventListener('click', function(e) {
        const shareOption = e.target.closest('.share-option');
        if (!shareOption) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const shareType = shareOption.dataset.share;
        const postId = shareOption.dataset.postId;
        
        if (!shareType || !postId) return;
        
        const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
        const shareUrl = shareBtn?.dataset.shareUrl || shareOption.dataset.url || window.location.href;
        const shareTitle = shareBtn?.dataset.shareTitle || document.title;
        
        handleShare(shareType, shareUrl, shareTitle, postId);
    });
    
    function openShareModal(postId) {
        const modal = document.getElementById(`shareModal-${postId}`);
        if (!modal) return;
        
        const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
        if (shareBtn) {
            modal.dataset.shareUrl = shareBtn.dataset.shareUrl || window.location.href;
            modal.dataset.shareTitle = shareBtn.dataset.shareTitle || document.title;
        }
        
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeShareModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    function handleShare(type, url, title, postId) {
        const encodedUrl = encodeURIComponent(url);
        const encodedTitle = encodeURIComponent(title);
        
        switch(type) {
            case 'facebook':
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`, '_blank', 'width=600,height=400');
                break;
            case 'zalo':
                window.open(`https://zalo.me/share?url=${encodedUrl}`, '_blank', 'width=600,height=400');
                break;
            case 'copy':
                copyToClipboard(url);
                updateShareCount(postId);
                // Toast for copy is shown in copyToClipboard function
                return;
            case 'instagram':
                if (navigator.userAgent.match(/Instagram/i)) {
                    window.open(`https://www.instagram.com/`, '_blank');
                }
                break;
            case 'email':
                window.location.href = `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
                break;
            case 'x':
                window.open(`https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`, '_blank', 'width=600,height=400');
                break;
            case 'telegram':
                window.open(`https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`, '_blank', 'width=600,height=400');
                break;
        }
        
        // Show toast notification for share
        if (typeof Toast !== 'undefined') {
            Toast.success('video_shared');
        }
        
        if (type !== 'copy') {
            updateShareCount(postId);
        }
        
        setTimeout(() => {
            const modal = document.getElementById(`shareModal-${postId}`);
            if (modal) {
                closeShareModal(modal);
            }
        }, 500);
    }
    
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                // Show toast notification
                if (typeof Toast !== 'undefined') {
                    Toast.success('video_shared');
                }
            }).catch(() => {
                // Fallback to execCommand
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                const success = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (success && typeof Toast !== 'undefined') {
                    Toast.success('video_shared');
                }
            });
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textArea);
            if (success && typeof Toast !== 'undefined') {
                Toast.success('video_shared');
            }
        }
    }
    
    function updateShareCount(postId) {
        sendAjaxRequest('puna_tiktok_increment_shares', { post_id: postId })
            .then(data => {
                if (data.success && data.data.share_count !== undefined) {
                    const shareBtn = document.querySelector(`.action-item[data-action="share"][data-post-id="${postId}"], .interaction-item[data-action="share"][data-post-id="${postId}"]`);
                    if (shareBtn) {
                        const countEl = shareBtn.querySelector('.count') || shareBtn.querySelector('.stat-count');
                        if (countEl) {
                            countEl.textContent = formatNumber(data.data.share_count);
                        }
                    }
                }
            })
            .catch(err => {});
    }

    // Delete video
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.delete-video-item');
        if (!deleteBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const postId = deleteBtn.dataset.postId;
        if (!postId) return;
        
        const confirmMessage = typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.toast_messages && puna_tiktok_ajax.toast_messages.warning_delete_video 
            ? puna_tiktok_ajax.toast_messages.warning_delete_video 
            : 'Are you sure you want to delete this video? This action cannot be undone.';
        if (!confirm(confirmMessage)) {
            return;
        }
        
        const dropdown = deleteBtn.closest('.video-info-more-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
        
        sendAjaxRequest('puna_tiktok_delete_video', {
            post_id: postId
        })
        .then(data => {
            if (data.success) {
                // Show toast notification
                if (typeof Toast !== 'undefined') {
                    Toast.success('video_deleted');
                }
                const redirectUrl = data.data?.redirect_url || (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.home_url) || window.location.origin;
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);
            }
        })
        .catch(error => {
            // Error handling silently
        });
    });

    // Report video - Show fake notification
    document.addEventListener('click', function(e) {
        const reportItem = e.target.closest('.options-item');
        if (!reportItem) return;
        
        // Check if it's the report item (not delete item)
        const reportText = reportItem.querySelector('span');
        if (!reportText) {
            return;
        }
        
        // Check if it's report by checking if it's not delete item
        if (reportItem.classList.contains('delete-video-item')) {
            return;
        }
        
        // Check if it's not the delete item
        if (reportItem.classList.contains('delete-video-item')) {
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // Close dropdown
        const dropdown = reportItem.closest('.video-info-more-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
        
        // Show fake notification using Toast
        if (typeof Toast !== 'undefined') {
            Toast.success('video_reported');
        }
    });

    // Video view count
    function incrementVideoView(postId) {
        if (!postId) return;
        
        sendAjaxRequest('puna_tiktok_increment_view', { post_id: postId })
        .then(data => {
            if (data.success) {
                const viewElement = document.querySelector(`.action-item[data-post-id="${postId}"][data-action="view"] .count`);
                if (viewElement && data.data.formatted_views) {
                    viewElement.textContent = data.data.formatted_views;
                }
            }
        })
        .catch(error => {});
    }

    // Make incrementVideoView available globally
    window.incrementVideoView = incrementVideoView;
});

