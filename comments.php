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
                            <?php 
                            $guest_id = '';
                            if (!$comment_author_id) {
                                $guest_id = get_comment_meta($comment->comment_ID, '_puna_tiktok_guest_id', true);
                            }
                            echo puna_tiktok_get_avatar_html($comment_author_id > 0 ? $comment_author_id : $comment->comment_author, 40, 'comment-avatar', $guest_id); 
                            ?>
                        </a>
                        <div class="comment-content">
                            <div class="comment-header">
                                <a href="<?php echo esc_url($comment_author_url); ?>" class="comment-author-link">
                                    <strong class="comment-author"><?php echo esc_html($comment_author_id > 0 ? puna_tiktok_get_user_display_name($comment_author_id) : $comment->comment_author); ?></strong>
                                </a>
                                <?php if (!$is_current_user && $comment_author_id) : ?>
                                <?php endif; ?>
                            </div>
                            <p class="comment-text"><?php echo wp_kses_post($comment->comment_content); ?></p>
                            <div class="comment-footer">
                                <span class="comment-date"><?php echo esc_html($comment_date); ?> trước</span>
                                <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                    Trả lời
                                </a>
                            </div>
                        </div>
                        <div class="comment-right-actions">
                            <?php 
                            // Check if current user can delete this comment
                            $can_delete_comment = false;
                            if (current_user_can('moderate_comments')) {
                                // Admin can delete any comment
                                $can_delete_comment = true;
                            } elseif ($is_current_user && $comment_author_id > 0) {
                                // Registered user can delete own comment
                                $can_delete_comment = true;
                            } elseif (!$comment_author_id) {
                                // Guest can delete own comment
                                $comment_guest_id = get_comment_meta($comment->comment_ID, '_puna_tiktok_guest_id', true);
                                $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                if (!empty($comment_guest_id) && !empty($current_guest_id) && $comment_guest_id === $current_guest_id) {
                                    $can_delete_comment = true;
                                }
                            }
                            ?>
                            <?php if ($can_delete_comment || (!current_user_can('moderate_comments') && !$can_delete_comment && is_user_logged_in())) : ?>
                                <div class="comment-actions">
                                    <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                    <div class="comment-options-dropdown">
                                        <?php if ($can_delete_comment) : ?>
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
                            <?php elseif (!$comment_author_id) : ?>
                                <?php 
                                // Guest can delete own comment
                                $comment_guest_id = get_comment_meta($comment->comment_ID, '_puna_tiktok_guest_id', true);
                                $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                if (!empty($comment_guest_id) && !empty($current_guest_id) && $comment_guest_id === $current_guest_id) : ?>
                                    <div class="comment-actions">
                                        <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                        <div class="comment-options-dropdown">
                                            <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                                <i class="fa-solid fa-trash"></i> Xóa
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="comment-likes" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                                <i class="<?php echo $is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $is_liked ? ' liked' : ''; ?>"></i>
                                <span><?php echo esc_html($comment_likes); ?></span>
                            </div>
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
                                        <?php 
                                        $reply_guest_id = '';
                                        if (!$reply_author_id) {
                                            $reply_guest_id = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                        }
                                        echo puna_tiktok_get_avatar_html($reply_author_id > 0 ? $reply_author_id : $reply->comment_author, 40, 'comment-avatar', $reply_guest_id); 
                                        ?>
                                    </a>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <a href="<?php echo esc_url($reply_author_url); ?>" class="comment-author-link">
                                                <strong class="comment-author"><?php echo esc_html($reply_author_id > 0 ? puna_tiktok_get_user_display_name($reply_author_id) : $reply->comment_author); ?></strong>
                                            </a>
                                        </div>
                                        <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                                        <div class="comment-footer">
                                            <span class="comment-date"><?php echo esc_html($reply_date); ?> trước</span>
                                            <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                Trả lời
                                            </a>
                                        </div>
                                    </div>
                                    <div class="comment-right-actions">
                                        <?php 
                                        // Check if current user can delete this reply
                                        $can_delete_reply = false;
                                        if (current_user_can('moderate_comments')) {
                                            // Admin can delete any reply
                                            $can_delete_reply = true;
                                        } elseif ($is_reply_current_user && $reply_author_id > 0) {
                                            // Registered user can delete own reply
                                            $can_delete_reply = true;
                                        } elseif (!$reply_author_id) {
                                            // Guest can delete own reply
                                            $reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                            $current_guest_id_check = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                            if (!empty($reply_guest_id_check) && !empty($current_guest_id_check) && $reply_guest_id_check === $current_guest_id_check) {
                                                $can_delete_reply = true;
                                            }
                                        }
                                        ?>
                                        <?php if ($can_delete_reply || (!current_user_can('moderate_comments') && !$can_delete_reply && is_user_logged_in())) : ?>
                                            <div class="comment-actions">
                                                <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                <div class="comment-options-dropdown">
                                                    <?php if ($can_delete_reply) : ?>
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
                                        <?php elseif (!$reply_author_id) : ?>
                                            <?php 
                                            // Guest can delete own reply
                                            $reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                            $current_guest_id_check = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                            if (!empty($reply_guest_id_check) && !empty($current_guest_id_check) && $reply_guest_id_check === $current_guest_id_check) : ?>
                                                <div class="comment-actions">
                                                    <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                    <div class="comment-options-dropdown">
                                                        <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                            <i class="fa-solid fa-trash"></i> Xóa
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <div class="comment-likes" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                            <i class="<?php echo $reply_is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $reply_is_liked ? ' liked' : ''; ?>"></i>
                                            <span><?php echo esc_html($reply_likes); ?></span>
                                        </div>
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
                                                <?php 
                                                $more_reply_guest_id = '';
                                                if (!$more_reply_author_id) {
                                                    $more_reply_guest_id = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                                }
                                                echo puna_tiktok_get_avatar_html($more_reply_author_id > 0 ? $more_reply_author_id : $reply->comment_author, 40, 'comment-avatar', $more_reply_guest_id); 
                                                ?>
                                            </a>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <a href="<?php echo esc_url($more_reply_author_url); ?>" class="comment-author-link">
                                                        <strong class="comment-author"><?php echo esc_html($more_reply_author_id > 0 ? puna_tiktok_get_user_display_name($more_reply_author_id) : $reply->comment_author); ?></strong>
                                                    </a>
                                                </div>
                                                <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                                                <div class="comment-footer">
                                                    <span class="comment-date"><?php echo esc_html($reply_date); ?> trước</span>
                                                    <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                        Trả lời
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="comment-right-actions">
                                                <?php 
                                                // Check if current user can delete this reply
                                                $can_delete_more_reply = false;
                                                if (current_user_can('moderate_comments')) {
                                                    // Admin can delete any reply
                                                    $can_delete_more_reply = true;
                                                } elseif ($is_more_reply_current_user && $more_reply_author_id > 0) {
                                                    // Registered user can delete own reply
                                                    $can_delete_more_reply = true;
                                                } elseif (!$more_reply_author_id) {
                                                    // Guest can delete own reply
                                                    $more_reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                                    $current_guest_id_check_more = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                                    if (!empty($more_reply_guest_id_check) && !empty($current_guest_id_check_more) && $more_reply_guest_id_check === $current_guest_id_check_more) {
                                                        $can_delete_more_reply = true;
                                                    }
                                                }
                                                ?>
                                                <?php if ($can_delete_more_reply || (!current_user_can('moderate_comments') && !$can_delete_more_reply && is_user_logged_in())) : ?>
                                                    <div class="comment-actions">
                                                        <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                        <div class="comment-options-dropdown">
                                                            <?php if ($can_delete_more_reply) : ?>
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
                                                <?php elseif (!$more_reply_author_id) : ?>
                                                    <?php 
                                                    // Guest can delete own reply
                                                    $more_reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                                                    $current_guest_id_check_more = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                                                    if (!empty($more_reply_guest_id_check) && !empty($current_guest_id_check_more) && $more_reply_guest_id_check === $current_guest_id_check_more) : ?>
                                                        <div class="comment-actions">
                                                            <button class="comment-options-btn" title="Tùy chọn"><i class="fa-solid fa-ellipsis"></i></button>
                                                            <div class="comment-options-dropdown">
                                                                <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                                    <i class="fa-solid fa-trash"></i> Xóa
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <div class="comment-likes" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                                    <i class="<?php echo $reply_is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $reply_is_liked ? ' liked' : ''; ?>"></i>
                                                    <span><?php echo esc_html($reply_likes); ?></span>
                                                </div>
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

        <!-- Comment Input (guest có thể comment) -->
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
    </div>
</div>


