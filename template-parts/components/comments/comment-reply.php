<?php
/**
 * Template part for displaying a single comment reply item
 * 
 * @var WP_Comment $reply
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
if (!isset($reply) || !is_object($reply)) {
    return;
}

if (!isset($liked_comments) || !is_array($liked_comments)) {
    $liked_comments = array();
}

if (!isset($post_id)) {
    $post_id = 0;
}

$reply_author_id = $reply->user_id ? $reply->user_id : 0;
$is_reply_current_user = get_current_user_id() && $reply_author_id == get_current_user_id();
$reply_date = human_time_diff(strtotime($reply->comment_date), current_time('timestamp'));
$reply_likes = intval(get_comment_meta($reply->comment_ID, '_comment_likes', true));
$reply_is_liked = get_current_user_id() && in_array($reply->comment_ID, $liked_comments);

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
        <?php echo wp_kses_post(puna_tiktok_get_avatar_html($reply_author_id > 0 ? $reply_author_id : $reply->comment_author, 40, 'comment-avatar', $reply_guest_id)); ?>
    </div>
    <div class="comment-content">
        <div class="comment-header">
            <span class="comment-author-wrapper">
                <strong class="comment-author"><?php echo esc_html($reply_author_id > 0 ? puna_tiktok_get_user_display_name($reply_author_id) : $reply->comment_author); ?></strong>
            </span>
        </div>
        <p class="comment-text"><?php echo wp_kses_post($reply->comment_content); ?></p>
        <div class="comment-footer">
            <span class="comment-date"><?php printf(esc_html__('%s ago', 'puna-tiktok'), esc_html($reply_date)); ?></span>
            <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                <?php esc_html_e('Reply', 'puna-tiktok'); ?>
            </a>
        </div>
    </div>
    <div class="comment-right-actions">
        <?php if ($can_delete_reply) : ?>
            <div class="comment-actions">
                <button class="comment-options-btn" title="<?php esc_attr_e('Options', 'puna-tiktok'); ?>"><?php echo wp_kses_post(puna_tiktok_get_icon('dot', __('Options', 'puna-tiktok'))); ?></button>
                <div class="comment-options-dropdown">
                    <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                        <?php echo wp_kses_post(puna_tiktok_get_icon('delete', __('Delete', 'puna-tiktok'))); ?> <?php esc_html_e('Delete', 'puna-tiktok'); ?>
                    </button>
                </div>
            </div>
        <?php elseif (is_user_logged_in() && !$can_delete_reply) : ?>
            <div class="comment-actions">
                <button class="comment-options-btn" title="<?php esc_attr_e('Options', 'puna-tiktok'); ?>"><?php echo wp_kses_post(puna_tiktok_get_icon('dot', __('Options', 'puna-tiktok'))); ?></button>
                <div class="comment-options-dropdown">
                    <button class="comment-action-report" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
                        <?php echo wp_kses_post(puna_tiktok_get_icon('report', __('Report', 'puna-tiktok'))); ?> <?php esc_html_e('Report', 'puna-tiktok'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        <div class="comment-likes<?php echo esc_attr($reply_is_liked ? ' liked' : ''); ?>" data-comment-id="<?php echo esc_attr($reply->comment_ID); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon('heart-alt', __('Like', 'puna-tiktok'))); ?>
            <span><?php echo esc_html($reply_likes); ?></span>
        </div>
    </div>
</div>

