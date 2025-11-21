/**
 * Load guest state from localStorage
 */

document.addEventListener("DOMContentLoaded", function() {
    function loadGuestState() {
        if (isLoggedIn()) return;
        
        const likedVideos = GuestStorage.getLikedVideos();
        const savedVideos = GuestStorage.getSavedVideos();
        const likedComments = GuestStorage.getLikedComments();
        
        likedVideos.forEach(postId => {
            const likeBtn = document.querySelector(`.action-item[data-action="like"][data-post-id="${postId}"], .interaction-item[data-action="like"][data-post-id="${postId}"]`);
            if (likeBtn) {
                likeBtn.classList.add('liked');
            }
        });
        
        savedVideos.forEach(postId => {
            const saveBtn = document.querySelector(`.action-item[data-action="save"][data-post-id="${postId}"], .interaction-item[data-action="save"][data-post-id="${postId}"]`);
            if (saveBtn) {
                saveBtn.classList.add('saved');
            }
        });
        
        likedComments.forEach(commentId => {
            const commentLikes = document.querySelector(`.comment-likes[data-comment-id="${commentId}"]`);
            if (commentLikes) {
                commentLikes.classList.add('liked');
            }
        });
    }
    
    loadGuestState();
});

