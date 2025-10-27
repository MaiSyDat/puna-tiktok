<?php
/**
 * Comments Sidebar Template
 */
$post_id = isset($post_id) ? $post_id : get_the_ID();
$comments_count = get_comments_number($post_id);
?>

<div class="comments-overlay" id="comments-overlay-<?php echo esc_attr($post_id); ?>">
    <div class="comments-sidebar">
        <!-- Header -->
        <div class="comments-header">
            <h3>Bình luận (<?php echo number_format($comments_count); ?>)</h3>
            <button class="close-comments-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Comments List -->
        <div class="comments-list">
            <?php
            $comments_args = array(
                'post_id' => $post_id,
                'status' => 'approve',
                'orderby' => 'comment_date',
                'order' => 'DESC',
            );
            
            $comments = get_comments($comments_args);
            
            if ($comments) {
                foreach ($comments as $comment) {
                    $comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
                    $comment_meta_likes = get_comment_meta($comment->comment_ID, '_comment_likes', true);
                    $comment_likes = $comment_meta_likes ? $comment_meta_likes : 0;
                    ?>
                    <div class="comment-item" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <img src="<?php echo get_avatar_url($comment->user_id, array('size' => 40)); ?>" 
                             alt="<?php echo esc_attr($comment->comment_author); ?>" 
                             class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-header">
                                <strong class="comment-author"><?php echo esc_html($comment->comment_author); ?></strong>
                            </div>
                            <p class="comment-text"><?php echo wp_kses_post($comment->comment_content); ?></p>
                            <div class="comment-footer">
                                <span class="comment-date"><?php echo esc_html($comment_date); ?> trước</span>
                                <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                    Trả lời
                                </a>
                            </div>
                        </div>
                        <div class="comment-likes">
                            <i class="fa-regular fa-heart"></i>
                            <span><?php echo esc_html($comment_likes); ?></span>
                        </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="no-comments">
                    <p>Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</p>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Comment Input -->
        <div class="comment-input-container">
            <input type="text" 
                   class="comment-input" 
                   placeholder="Thêm bình luận..." 
                   data-post-id="<?php echo esc_attr($post_id); ?>">
            <div class="comment-input-actions">
                <button class="comment-action-btn" title="Gắn thẻ người dùng">
                    <i class="fa-solid fa-at"></i>
                </button>
                <button class="comment-action-btn" title="Emoji">
                    <i class="fa-regular fa-face-smile"></i>
                </button>
                <button class="submit-comment-btn" data-post-id="<?php echo esc_attr($post_id); ?>" disabled>
                    Đăng
                </button>
            </div>
        </div>
    </div>
</div>

