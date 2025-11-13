<?php
/**
 * Theme-wide comments template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get post id
$post_id = get_the_ID();

// Total comment
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
            // Query only top-level comments
            $top_level_args = array(
                'post_id' => $post_id,
                'status' => 'approve',
                'parent' => 0,
                'orderby' => 'comment_date',
                'order' => 'DESC',
            );
            
            $top_level_comments = get_comments($top_level_args);
            
            if ($top_level_comments) {
                // Get user liked comments if logged in
                $user_id = get_current_user_id();
                $liked_comments = array();
                if ($user_id) {
                    $liked_comments = get_user_meta($user_id, '_puna_tiktok_liked_comments', true);
                    if (!is_array($liked_comments)) {
                        $liked_comments = array();
                    }
                }
                
                // Loop
                foreach ($top_level_comments as $comment) {
                    $comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
                    $comment_meta_likes = get_comment_meta($comment->comment_ID, '_comment_likes', true);
                    $comment_likes = $comment_meta_likes ? $comment_meta_likes : 0;
                    $is_liked = $user_id && in_array($comment->comment_ID, $liked_comments);
                    $current_id = get_current_user_id();
                    
                    // Get all replies 
                    $direct_replies = get_comments(array(
                        'post_id' => $post_id,
                        'status' => 'approve',
                        'parent' => $comment->comment_ID,
                        'orderby' => 'comment_date',
                        'order' => 'ASC',
                    ));
                    
                    // Get all nested replies recursively
                    $all_replies = $direct_replies;
                    $processed_ids = array();
                    
                    // Closure to get nested replies
                    $get_all_nested_replies = function($parent_id, $post_id, &$all_replies, &$processed_ids) use (&$get_all_nested_replies) {
                        $nested = get_comments(array(
                            'post_id' => $post_id,
                            'status' => 'approve',
                            'parent' => $parent_id,
                            'orderby' => 'comment_date',
                            'order' => 'ASC',
                        ));
                        
                        foreach ($nested as $nested_reply) {
                            if (!in_array($nested_reply->comment_ID, $processed_ids)) {
                                $all_replies[] = $nested_reply;
                                $processed_ids[] = $nested_reply->comment_ID;
                                // Recursively get nested replies
                                $get_all_nested_replies($nested_reply->comment_ID, $post_id, $all_replies, $processed_ids);
                            }
                        }
                    };
                    
                    foreach ($direct_replies as $direct_reply) {
                        $processed_ids[] = $direct_reply->comment_ID;
                        $get_all_nested_replies($direct_reply->comment_ID, $post_id, $all_replies, $processed_ids);
                    }
                    
                    // Sort all replies by date
                    usort($all_replies, function($a, $b) {
                        return strtotime($a->comment_date) - strtotime($b->comment_date);
                    });
                    
                    $replies = $all_replies;
                    $replies_count = count($replies);
                    $replies_to_show = array_slice($replies, 0, 3);
                    $remaining_replies = max(0, $replies_count - 3);
                    ?>
                    <div class="comment-item" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php 
                        $comment_author_id = $comment->user_id ? $comment->user_id : 0;
                        $comment_author_url = $comment_author_id ? get_author_posts_url($comment_author_id) : '#';
                        $is_current_user = get_current_user_id() && $comment_author_id == get_current_user_id();
                        ?>
                        <a href="<?php echo esc_url($comment_author_url); ?>" class="comment-avatar-link">
                            <img src="<?php echo get_avatar_url($comment->user_id, array('size' => 40)); ?>" 
                                 alt="<?php echo esc_attr($comment->comment_author); ?>" 
                                 class="comment-avatar">
                        </a>
                        <div class="comment-content">
                            <div class="comment-header">
                                <a href="<?php echo esc_url($comment_author_url); ?>" class="comment-author-link">
                                    <strong class="comment-author"><?php echo esc_html($comment->comment_author); ?></strong>
                                </a>
                                <?php if (!$is_current_user && $comment_author_id) : ?>
                                <?php endif; ?>
                            </div>
                            <p class="comment-text"><?php echo wp_kses_post($comment->comment_content); ?></p>
                            <div class="comment-footer">
                                <span class="comment-date"><?php echo esc_html($comment_date); ?> trước</span>
                                <?php if (is_user_logged_in()) : ?>
                                    <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                        Trả lời
                                    </a>
                                <?php else : ?>
                                    <a href="#" class="reply-link" onclick="openLoginPopup(); return false;" title="Đăng nhập để trả lời">
                                        Trả lời
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="comment-right-actions">
                            <?php if (is_user_logged_in()) : ?>
                                <div class="comment-actions">
                                    <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                    <div class="comment-options-dropdown">
                                        <?php if ($current_id && intval($comment->user_id) === intval($current_id)) : ?>
                                            <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                        <?php else : ?>
                                            <button class="comment-action-report" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                                <i class="fa-solid fa-flag"></i> Báo cáo
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (is_user_logged_in()) : ?>
                                <div class="comment-likes" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                    <i class="<?php echo $is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $is_liked ? ' liked' : ''; ?>"></i>
                                    <span><?php echo esc_html($comment_likes); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="comment-likes" onclick="openLoginPopup(); return false;" style="cursor: pointer;" title="Đăng nhập để thích">
                                    <i class="fa-regular fa-heart"></i>
                                    <span><?php echo esc_html($comment_likes); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Replies Section -->
                    <?php if (!empty($replies_to_show)) : ?>
                        <div class="comment-replies" data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>">
                            <?php foreach ($replies_to_show as $reply) : 
                                $reply_date = human_time_diff(strtotime($reply->comment_date), current_time('timestamp'));
                                $reply_likes = get_comment_meta($reply->comment_ID, '_comment_likes', true) ?: 0;
                                $reply_is_liked = $user_id && in_array($reply->comment_ID, $liked_comments);
                            ?>
                                <div class="comment-item comment-reply" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                    <?php 
                                    $reply_author_id = $reply->user_id ? $reply->user_id : 0;
                                    $reply_author_url = $reply_author_id ? get_author_posts_url($reply_author_id) : '#';
                                    $is_reply_current_user = get_current_user_id() && $reply_author_id == get_current_user_id();
                                    ?>
                                    <a href="<?php echo esc_url($reply_author_url); ?>" class="comment-avatar-link">
                                        <img src="<?php echo get_avatar_url($reply->user_id, array('size' => 40)); ?>" 
                                             alt="<?php echo esc_attr($reply->comment_author); ?>" 
                                             class="comment-avatar">
                                    </a>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <a href="<?php echo esc_url($reply_author_url); ?>" class="comment-author-link">
                                                <strong class="comment-author"><?php echo esc_html($reply->comment_author); ?></strong>
                                            </a>
                                        </div>
                                        <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                                        <div class="comment-footer">
                                            <span class="comment-date"><?php echo esc_html($reply_date); ?> trước</span>
                                            <?php if (is_user_logged_in()) : ?>
                                                <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                    Trả lời
                                                </a>
                                            <?php else : ?>
                                                <a href="#" class="reply-link" onclick="openLoginPopup(); return false;" title="Đăng nhập để trả lời">
                                                    Trả lời
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="comment-right-actions">
                                        <?php if (is_user_logged_in()) : ?>
                                            <div class="comment-actions">
                                                <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                <div class="comment-options-dropdown">
                                                    <?php if ($current_id && intval($reply->user_id) === intval($current_id)) : ?>
                                                        <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                            <i class="fa-solid fa-trash"></i> Xóa
                                                        </button>
                                                    <?php else : ?>
                                                        <button class="comment-action-report" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                            <i class="fa-solid fa-flag"></i> Báo cáo
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (is_user_logged_in()) : ?>
                                            <div class="comment-likes" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                <i class="<?php echo $reply_is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $reply_is_liked ? ' liked' : ''; ?>"></i>
                                                <span><?php echo esc_html($reply_likes); ?></span>
                                            </div>
                                        <?php else : ?>
                                            <div class="comment-likes" onclick="openLoginPopup(); return false;" style="cursor: pointer;" title="Đăng nhập để thích">
                                                <i class="fa-regular fa-heart"></i>
                                                <span><?php echo esc_html($reply_likes); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($remaining_replies > 0) : ?>
                                <button class="show-more-replies-btn" data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>" data-loaded="3" data-total="<?php echo esc_attr($replies_count); ?>">
                                    Xem thêm phản hồi (<?php echo esc_html($remaining_replies); ?>)
                                </button>
                                <div class="more-replies-container" data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>" style="display: none;">
                                    <?php 
                                    $remaining_replies_list = array_slice($replies, 3);
                                    foreach ($remaining_replies_list as $reply) : 
                                        $reply_date = human_time_diff(strtotime($reply->comment_date), current_time('timestamp'));
                                        $reply_likes = get_comment_meta($reply->comment_ID, '_comment_likes', true) ?: 0;
                                        $reply_is_liked = $user_id && in_array($reply->comment_ID, $liked_comments);
                                    ?>
                                        <div class="comment-item comment-reply" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                            <?php 
                                            $more_reply_author_id = $reply->user_id ? $reply->user_id : 0;
                                            $more_reply_author_url = $more_reply_author_id ? get_author_posts_url($more_reply_author_id) : '#';
                                            $is_more_reply_current_user = get_current_user_id() && $more_reply_author_id == get_current_user_id();
                                            ?>
                                            <a href="<?php echo esc_url($more_reply_author_url); ?>" class="comment-avatar-link">
                                                <img src="<?php echo get_avatar_url($reply->user_id, array('size' => 40)); ?>" 
                                                     alt="<?php echo esc_attr($reply->comment_author); ?>" 
                                                     class="comment-avatar">
                                            </a>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <a href="<?php echo esc_url($more_reply_author_url); ?>" class="comment-author-link">
                                                        <strong class="comment-author"><?php echo esc_html($reply->comment_author); ?></strong>
                                                    </a>
                                                </div>
                                                <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                                                <div class="comment-footer">
                                                    <span class="comment-date"><?php echo esc_html($reply_date); ?> trước</span>
                                                    <?php if (is_user_logged_in()) : ?>
                                                        <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                            Trả lời
                                                        </a>
                                                    <?php else : ?>
                                                        <a href="#" class="reply-link" onclick="openLoginPopup(); return false;" title="Đăng nhập để trả lời">
                                                            Trả lời
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="comment-right-actions">
                                                <?php if (is_user_logged_in()) : ?>
                                                    <div class="comment-actions">
                                                        <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                        <div class="comment-options-dropdown">
                                                            <?php if ($current_id && intval($reply->user_id) === intval($current_id)) : ?>
                                                                <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                                    <i class="fa-solid fa-trash"></i> Xóa
                                                                </button>
                                                            <?php else : ?>
                                                                <button class="comment-action-report" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                                    <i class="fa-solid fa-flag"></i> Báo cáo
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (is_user_logged_in()) : ?>
                                                    <div class="comment-likes" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                        <i class="<?php echo $reply_is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $reply_is_liked ? ' liked' : ''; ?>"></i>
                                                        <span><?php echo esc_html($reply_likes); ?></span>
                                                    </div>
                                                <?php else : ?>
                                                    <div class="comment-likes" onclick="openLoginPopup(); return false;" style="cursor: pointer;" title="Đăng nhập để thích">
                                                        <i class="fa-regular fa-heart"></i>
                                                        <span><?php echo esc_html($reply_likes); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
        <?php if (is_user_logged_in()) : ?>
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
                    <div class="comment-submit-actions">
                        <button class="submit-comment-btn" data-post-id="<?php echo esc_attr($post_id); ?>" disabled>
                            Đăng
                        </button>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="comment-input-container">
                <div style="text-align: center; padding: 20px; border-top: 1px solid var(--puna-muted);">
                    <p style="margin: 0; color: var(--puna-text); font-size: 14px;">
                        <a href="#" onclick="openLoginPopup(); return false;" style="color: var(--puna-primary); text-decoration: none; font-weight: 600;">
                            Đăng nhập
                        </a> để bình luận
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


