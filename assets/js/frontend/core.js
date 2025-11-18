/**
 * Core utilities and helpers
 */

// Guest storage helpers
const GuestStorage = {
    LIKED_VIDEOS: 'puna_tiktok_guest_liked_videos',
    SAVED_VIDEOS: 'puna_tiktok_guest_saved_videos',
    LIKED_COMMENTS: 'puna_tiktok_guest_liked_comments',
    COMMENTS: 'puna_tiktok_guest_comments',
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
    
    getAllData: function() {
        return {
            liked_videos: this.getLikedVideos(),
            saved_videos: this.getSavedVideos(),
            liked_comments: this.getLikedComments()
        };
    },
    
    clearAll: function() {
        localStorage.removeItem(this.LIKED_VIDEOS);
        localStorage.removeItem(this.SAVED_VIDEOS);
        localStorage.removeItem(this.LIKED_COMMENTS);
        localStorage.removeItem(this.COMMENTS);
    }
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

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.textContent = message;
    
    const iconMap = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    if (iconMap[type]) {
        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.textContent = iconMap[type];
        toast.insertBefore(icon, toast.firstChild);
    }
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, duration);
}

