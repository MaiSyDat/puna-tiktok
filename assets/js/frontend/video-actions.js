/**
 * Video actions (like, save, share, delete)
 */

document.addEventListener("DOMContentLoaded", function() {
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
        
        if (!isLoggedIn()) {
            const isLiked = GuestStorage.toggleLikeVideo(postId);
            actionItem.classList.toggle('liked', isLiked);
        }
        
        sendAjaxRequest('puna_tiktok_toggle_like', { 
            post_id: postId,
            action_type: actionItem.classList.contains('liked') ? 'like' : 'unlike'
        })
        .then(data => {
            if (data.success) {
                const isLiked = data.data.is_liked;
                const likes = data.data.likes;
                
                actionItem.classList.toggle('liked', isLiked);
                
                if (!isLoggedIn()) {
                    if (isLiked) {
                        const liked = GuestStorage.getLikedVideos();
                        if (liked.indexOf(postId) === -1) {
                            liked.push(postId);
                            GuestStorage.setLikedVideos(liked);
                        }
                    } else {
                        const liked = GuestStorage.getLikedVideos();
                        const index = liked.indexOf(postId);
                        if (index > -1) {
                            liked.splice(index, 1);
                            GuestStorage.setLikedVideos(liked);
                        }
                    }
                }
                
                const countElement = actionItem.querySelector('.count') || actionItem.querySelector('.stat-count');
                if (countElement) {
                    countElement.textContent = formatNumber(likes);
                }
            } else {
                if (!isLoggedIn()) {
                    GuestStorage.toggleLikeVideo(postId);
                    actionItem.classList.toggle('liked');
                }
            }
        })
        .catch(error => {
            if (!isLoggedIn()) {
                GuestStorage.toggleLikeVideo(postId);
                actionItem.classList.toggle('liked');
            }
        });
    });

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
        
        if (!isLoggedIn()) {
            const isSaved = GuestStorage.toggleSaveVideo(postId);
            if (isSaved) {
                actionItem.classList.add('saved');
            } else {
                actionItem.classList.remove('saved');
            }
        }
        
        sendAjaxRequest('puna_tiktok_toggle_save', { 
            post_id: postId,
            action_type: actionItem.classList.contains('saved') ? 'save' : 'unsave'
        })
        .then(data => {
            if (data.success) {
                const isSaved = data.data.is_saved;
                const saves = data.data.saves;
                
                if (isSaved) {
                    actionItem.classList.add('saved');
                } else {
                    actionItem.classList.remove('saved');
                }
                
                if (!isLoggedIn()) {
                    if (isSaved) {
                        const saved = GuestStorage.getSavedVideos();
                        if (saved.indexOf(postId) === -1) {
                            saved.push(postId);
                            GuestStorage.setSavedVideos(saved);
                        }
                    } else {
                        const saved = GuestStorage.getSavedVideos();
                        const index = saved.indexOf(postId);
                        if (index > -1) {
                            saved.splice(index, 1);
                            GuestStorage.setSavedVideos(saved);
                        }
                    }
                }
                
                const countEl = actionItem.querySelector('.count') || actionItem.querySelector('.stat-count');
                if (countEl && saves !== undefined) {
                    countEl.textContent = formatNumber(saves);
                }
            } else {
                if (!isLoggedIn()) {
                    GuestStorage.toggleSaveVideo(postId);
                    actionItem.classList.toggle('saved');
                }
            }
        })
        .catch(error => {
            if (!isLoggedIn()) {
                GuestStorage.toggleSaveVideo(postId);
                actionItem.classList.toggle('saved');
            }
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
                break;
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
            navigator.clipboard.writeText(text);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
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
        
        if (!confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
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

