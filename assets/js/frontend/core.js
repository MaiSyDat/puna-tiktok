/**
 * Core utilities and helpers
 */

// Guest storage helpers
const GuestStorage = {
    LIKED_VIDEOS: 'puna_tiktok_guest_liked_videos',
    SAVED_VIDEOS: 'puna_tiktok_guest_saved_videos',
    LIKED_COMMENTS: 'puna_tiktok_guest_liked_comments',
    GUEST_ID: 'puna_tiktok_guest_id',
    
    getGuestId: function() {
        try {
            let guestId = localStorage.getItem(this.GUEST_ID);
            if (!guestId) {
                guestId = 'guest_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
                localStorage.setItem(this.GUEST_ID, guestId);
            }
            return guestId;
        } catch (e) {
            return 'guest_' + Date.now().toString(36);
        }
    },
    
    getLikedVideos: function() {
        try {
            const data = localStorage.getItem(this.LIKED_VIDEOS);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    },
    
    setLikedVideos: function(videoIds) {
        try {
            localStorage.setItem(this.LIKED_VIDEOS, JSON.stringify(videoIds));
        } catch (e) {
        }
    },
    
    toggleLikeVideo: function(postId) {
        const liked = this.getLikedVideos();
        const index = liked.indexOf(postId);
        if (index > -1) {
            liked.splice(index, 1);
        } else {
            liked.push(postId);
        }
        this.setLikedVideos(liked);
        return liked.indexOf(postId) > -1;
    },
    
    getSavedVideos: function() {
        try {
            const data = localStorage.getItem(this.SAVED_VIDEOS);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    },
    
    setSavedVideos: function(videoIds) {
        try {
            localStorage.setItem(this.SAVED_VIDEOS, JSON.stringify(videoIds));
        } catch (e) {
        }
    },
    
    toggleSaveVideo: function(postId) {
        const saved = this.getSavedVideos();
        const index = saved.indexOf(postId);
        if (index > -1) {
            saved.splice(index, 1);
        } else {
            saved.push(postId);
        }
        this.setSavedVideos(saved);
        return saved.indexOf(postId) > -1;
    },
    
    getLikedComments: function() {
        try {
            const data = localStorage.getItem(this.LIKED_COMMENTS);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    },
    
    setLikedComments: function(commentIds) {
        try {
            localStorage.setItem(this.LIKED_COMMENTS, JSON.stringify(commentIds));
        } catch (e) {
        }
    },
    
    toggleLikeComment: function(commentId) {
        const liked = this.getLikedComments();
        const index = liked.indexOf(commentId);
        if (index > -1) {
            liked.splice(index, 1);
        } else {
            liked.push(commentId);
        }
        this.setLikedComments(liked);
        return liked.indexOf(commentId) > -1;
    },
    
};

// Check if user is logged in
function isLoggedIn() {
    if (typeof puna_tiktok_ajax === 'undefined') return false;
    return puna_tiktok_ajax.is_logged_in === true || puna_tiktok_ajax.is_logged_in === '1' || puna_tiktok_ajax.is_logged_in === 1;
}

// Format number
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Send AJAX request
function sendAjaxRequest(action, params = {}) {
    // Determine which nonce to use based on action type
    let nonce = puna_tiktok_ajax.like_nonce; // Default nonce
    
    // Comment-related actions use comment_nonce
    if (action.includes('comment') || action.includes('reply')) {
        nonce = puna_tiktok_ajax.comment_nonce || puna_tiktok_ajax.like_nonce;
    }
    // Search-related actions use search_nonce
    else if (action.includes('search') || action.includes('hashtag')) {
        nonce = puna_tiktok_ajax.search_nonce || puna_tiktok_ajax.like_nonce;
    }
    
    return fetch(puna_tiktok_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: action,
            nonce: nonce,
            ...params
        })
    }).then(response => response.json());
}

// Initialize toast messages if available
if (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.toast_messages) {
    if (typeof Toast !== 'undefined' && Toast.setMessages) {
        Toast.setMessages(puna_tiktok_ajax.toast_messages);
    }
}

// Global error handler to catch errors from external scripts
window.addEventListener('error', function(event) {
    // Catch insertBefore null errors
    if (event.message && event.message.includes('insertBefore')) {
        event.preventDefault();
        return true;
    }
}, true);

// Catch errors from unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    if (event.reason && event.reason.message && event.reason.message.includes('insertBefore')) {
        event.preventDefault();
    }
});

// Get current video (most visible in viewport)
function getCurrentVideo() {
    const videoList = document.querySelectorAll('.tiktok-video');
    if (!videoList.length) return null;
    
    // Find video that is most visible in viewport
    let mostVisible = null;
    let maxVisible = 0;
    
    videoList.forEach(video => {
        const rect = video.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const visibleTop = Math.max(0, rect.top);
        const visibleBottom = Math.min(viewportHeight, rect.bottom);
        const visibleHeight = Math.max(0, visibleBottom - visibleTop);
        const visibleRatio = visibleHeight / Math.min(rect.height, viewportHeight);
        
        if (visibleRatio > maxVisible) {
            maxVisible = visibleRatio;
            mostVisible = video;
        }
    });
    
    // Fallback: find video closest to top of viewport
    if (!mostVisible) {
        return Array.from(videoList).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        }) || videoList[0];
    }
    
    return mostVisible;
}

// Export to window for other modules
window.getCurrentVideo = getCurrentVideo;

// Guest state helpers
const GuestStateHelpers = {
    /**
     * Update guest liked/saved state in localStorage after server response
     * @param {string} type - 'like' or 'save'
     * @param {string} postId - Post ID
     * @param {boolean} isActive - Whether the state is active
     */
    syncGuestState: function(type, postId, isActive) {
        if (isLoggedIn()) return;
        
        if (type === 'like') {
            const liked = GuestStorage.getLikedVideos();
            if (isActive && liked.indexOf(postId) === -1) {
                liked.push(postId);
                GuestStorage.setLikedVideos(liked);
            } else if (!isActive) {
                const index = liked.indexOf(postId);
                if (index > -1) {
                    liked.splice(index, 1);
                    GuestStorage.setLikedVideos(liked);
                }
            }
        } else if (type === 'save') {
            const saved = GuestStorage.getSavedVideos();
            if (isActive && saved.indexOf(postId) === -1) {
                saved.push(postId);
                GuestStorage.setSavedVideos(saved);
            } else if (!isActive) {
                const index = saved.indexOf(postId);
                if (index > -1) {
                    saved.splice(index, 1);
                    GuestStorage.setSavedVideos(saved);
                }
            }
        }
    },
    
    /**
     * Revert guest state if server request fails
     * @param {string} type - 'like' or 'save'
     * @param {string} postId - Post ID
     */
    revertGuestState: function(type, postId) {
        if (isLoggedIn()) return;
        
        if (type === 'like') {
            GuestStorage.toggleLikeVideo(postId);
        } else if (type === 'save') {
            GuestStorage.toggleSaveVideo(postId);
        }
    },
    
    /**
     * Sync guest comment like state
     * @param {string} commentId - Comment ID
     * @param {boolean} isLiked - Whether the comment is liked
     */
    syncCommentLikeState: function(commentId, isLiked) {
        if (isLoggedIn()) return;
        
        const liked = GuestStorage.getLikedComments();
        if (isLiked && liked.indexOf(commentId) === -1) {
            liked.push(commentId);
            GuestStorage.setLikedComments(liked);
        } else if (!isLiked) {
            const index = liked.indexOf(commentId);
            if (index > -1) {
                liked.splice(index, 1);
                GuestStorage.setLikedComments(liked);
            }
        }
    },
    
    /**
     * Revert guest comment like state
     * @param {string} commentId - Comment ID
     */
    revertCommentLikeState: function(commentId) {
        if (isLoggedIn()) return;
        GuestStorage.toggleLikeComment(commentId);
    }
};

// Export to window
window.GuestStateHelpers = GuestStateHelpers;

