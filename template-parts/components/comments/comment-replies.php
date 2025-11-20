<?php
/**
 * Template part for displaying comment replies section
 * 
 * @var int $parent_id
 * @var array $replies
 * @var int $post_id
 * @var array $liked_comments
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract variables from args if passed via get_template_part
// WordPress get_template_part passes args as $args variable
if (isset($args) && is_array($args)) {
    extract($args);
}

// Ensure required variables exist
if (!isset($replies) || !is_array($replies)) {
    $replies = array();
}

if (!isset($liked_comments) || !is_array($liked_comments)) {
    $liked_comments = array();
}

if (!isset($post_id)) {
    $post_id = 0;
}

if (!isset($parent_id)) {
    $parent_id = 0;
}


// Only render if there are replies
if (empty($replies) || !is_array($replies) || count($replies) === 0) {
    return;
}

$replies_count = count($replies);
$replies_to_show = array_slice($replies, 0, 3);
$remaining_replies = max(0, $replies_count - 3);
$remaining_replies_list = array_slice($replies, 3);
?>

<div class="comment-replies" data-parent-id="<?php echo esc_attr($parent_id); ?>">
    <?php foreach ($replies_to_show as $reply) : 
        $reply_date = human_time_diff(strtotime($reply->comment_date), current_time('timestamp'));
        $reply_likes = get_comment_meta($reply->comment_ID, '_comment_likes', true) ?: 0;
        $reply_is_liked = get_current_user_id() && in_array($reply->comment_ID, $liked_comments);
        
        $reply_author_id = $reply->user_id ? $reply->user_id : 0;
        $is_reply_current_user = get_current_user_id() && $reply_author_id == get_current_user_id();
        
        // Check if current user can delete this reply
        $can_delete_reply = false;
        if (current_user_can('moderate_comments')) {
            // Admin can delete any reply
            $can_delete_reply = true;
        } elseif ($is_reply_current_user && $reply_author_id > 0) {
            // User can delete their own reply
            $can_delete_reply = true;
        } elseif (!$reply_author_id) {
            // Guest reply - check if current guest can delete
            $reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
            $current_guest_id_check = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
            
            // If reply has guest_id, allow deletion (JavaScript will verify with localStorage)
            if (!empty($reply_guest_id_check)) {
                if (!empty($current_guest_id_check) && $reply_guest_id_check === $current_guest_id_check) {
                    $can_delete_reply = true;
                } elseif (empty($current_guest_id_check)) {
                    // No cookie but reply has guest_id - show button, let AJAX verify with localStorage
                    $can_delete_reply = true;
                }
            }
        }
        
        $reply_guest_id = '';
        if (!$reply_author_id) {
            $reply_guest_id = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
        }
    ?>
        <div class="comment-item comment-reply" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
            <div class="comment-avatar-wrapper">
                <?php echo puna_tiktok_get_avatar_html($reply_author_id > 0 ? $reply_author_id : $reply->comment_author, 40, 'comment-avatar', $reply_guest_id); ?>
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <span class="comment-author-wrapper">
                        <strong class="comment-author"><?php echo esc_html($reply_author_id > 0 ? puna_tiktok_get_user_display_name($reply_author_id) : $reply->comment_author); ?></strong>
                    </span>
                </div>
                <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                <div class="comment-footer">
                    <span class="comment-date"><?php printf(esc_html__('%s trước', 'puna-tiktok'), esc_html($reply_date)); ?></span>
                    <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                        <?php esc_html_e('Trả lời', 'puna-tiktok'); ?>
                    </a>
                </div>
            </div>
            <div class="comment-right-actions">
                <?php if ($can_delete_reply) : ?>
                    <div class="comment-actions">
                        <button class="comment-options-btn" title="<?php esc_attr_e('Tùy chọn', 'puna-tiktok'); ?>"><i class="fa-solid fa-ellipsis"></i></button>
                        <div class="comment-options-dropdown">
                            <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                <i class="fa-solid fa-trash"></i> <?php esc_html_e('Xóa', 'puna-tiktok'); ?>
                            </button>
                        </div>
                    </div>
                <?php elseif (is_user_logged_in() && !$can_delete_reply) : ?>
                    <div class="comment-actions">
                        <button class="comment-options-btn" title="<?php esc_attr_e('Tùy chọn', 'puna-tiktok'); ?>"><i class="fa-solid fa-ellipsis"></i></button>
                        <div class="comment-options-dropdown">
                            <button class="comment-action-report" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                <i class="fa-solid fa-flag"></i> <?php esc_html_e('Báo cáo', 'puna-tiktok'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="comment-likes" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                    <i class="<?php echo $reply_is_liked ? 'fa-solid' : 'fa-regular'; ?> fa-heart<?php echo $reply_is_liked ? ' liked' : ''; ?>"></i>
                    <span><?php echo esc_html($reply_likes); ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if ($remaining_replies > 0) : ?>
        <button class="show-more-replies-btn" data-parent-id="<?php echo esc_attr($parent_id); ?>" data-loaded="3" data-total="<?php echo esc_attr($replies_count); ?>">
            <?php printf(esc_html__('Xem thêm phản hồi (%s)', 'puna-tiktok'), esc_html($remaining_replies)); ?>
        </button>
        <div class="more-replies-container" data-parent-id="<?php echo esc_attr($parent_id); ?>" style="display: none;">
            <?php foreach ($remaining_replies_list as $reply) : 
                $reply_date = human_time_diff(strtotime($reply->comment_date), current_time('timestamp'));
                $reply_likes = get_comment_meta($reply->comment_ID, '_comment_likes', true) ?: 0;
                $reply_is_liked = get_current_user_id() && in_array($reply->comment_ID, $liked_comments);
                
                $more_reply_author_id = $reply->user_id ? $reply->user_id : 0;
                $is_more_reply_current_user = get_current_user_id() && $more_reply_author_id == get_current_user_id();
                
                // Check if current user can delete this reply
                $can_delete_more_reply = false;
                if (current_user_can('moderate_comments')) {
                    // Admin can delete any reply
                    $can_delete_more_reply = true;
                } elseif ($is_more_reply_current_user && $more_reply_author_id > 0) {
                    // User can delete their own reply
                    $can_delete_more_reply = true;
                } elseif (!$more_reply_author_id) {
                    // Guest reply - check if current guest can delete
                    $more_reply_guest_id_check = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                    $current_guest_id_check_more = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
                    
                    // If reply has guest_id, allow deletion (JavaScript will verify with localStorage)
                    if (!empty($more_reply_guest_id_check)) {
                        if (!empty($current_guest_id_check_more) && $more_reply_guest_id_check === $current_guest_id_check_more) {
                            $can_delete_more_reply = true;
                        } elseif (empty($current_guest_id_check_more)) {
                            // No cookie but reply has guest_id - show button, let AJAX verify with localStorage
                            $can_delete_more_reply = true;
                        }
                    }
                }
                
                $more_reply_guest_id = '';
                if (!$more_reply_author_id) {
                    $more_reply_guest_id = get_comment_meta($reply->comment_ID, '_puna_tiktok_guest_id', true);
                }
            ?>
                <div class="comment-item comment-reply" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                    <div class="comment-avatar-wrapper">
                        <?php echo puna_tiktok_get_avatar_html($more_reply_author_id > 0 ? $more_reply_author_id : $reply->comment_author, 40, 'comment-avatar', $more_reply_guest_id); ?>
                    </div>
                    <div class="comment-content">
                        <div class="comment-header">
                            <span class="comment-author-wrapper">
                                <strong class="comment-author"><?php echo esc_html($more_reply_author_id > 0 ? puna_tiktok_get_user_display_name($more_reply_author_id) : $reply->comment_author); ?></strong>
                            </span>
                        </div>
                        <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
                        <div class="comment-footer">
                            <span class="comment-date"><?php printf(esc_html__('%s trước', 'puna-tiktok'), esc_html($reply_date)); ?></span>
                            <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                <?php esc_html_e('Trả lời', 'puna-tiktok'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="comment-right-actions">
                        <?php if ($can_delete_more_reply) : ?>
                            <div class="comment-actions">
                                <button class="comment-options-btn" title="<?php esc_attr_e('Tùy chọn', 'puna-tiktok'); ?>"><i class="fa-solid fa-ellipsis"></i></button>
                                <div class="comment-options-dropdown">
                                    <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                        <i class="fa-solid fa-trash"></i> <?php esc_html_e('Xóa', 'puna-tiktok'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php elseif (is_user_logged_in() && !$can_delete_more_reply) : ?>
                            <div class="comment-actions">
                                <button class="comment-options-btn" title="<?php esc_attr_e('Tùy chọn', 'puna-tiktok'); ?>"><i class="fa-solid fa-ellipsis"></i></button>
                                <div class="comment-options-dropdown">
                                    <button class="comment-action-report" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                                        <i class="fa-solid fa-flag"></i> <?php esc_html_e('Báo cáo', 'puna-tiktok'); ?>
                                    </button>
                                </div>
                            </div>
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

