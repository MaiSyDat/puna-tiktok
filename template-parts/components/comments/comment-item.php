<?php
/**
 * Template part for displaying a single comment item
 * 
 * @var WP_Comment $comment
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
if (!isset($comment) || !is_object($comment)) {
    return;
}

if (!isset($liked_comments) || !is_array($liked_comments)) {
    $liked_comments = array();
}

if (!isset($post_id)) {
    $post_id = 0;
}

$comment_author_id = $comment->user_id ? $comment->user_id : 0;
$is_current_user = get_current_user_id() && $comment_author_id == get_current_user_id();
$comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
$comment_likes = get_comment_meta($comment->comment_ID, '_comment_likes', true) ?: 0;
$is_liked = get_current_user_id() && in_array($comment->comment_ID, $liked_comments);

// Check if current user can delete this comment
$can_delete_comment = false;
if (current_user_can('moderate_comments')) {
    // Admin can delete any comment
    $can_delete_comment = true;
} elseif ($is_current_user && $comment_author_id > 0) {
    // User can delete their own comment
    $can_delete_comment = true;
} elseif (!$comment_author_id) {
    // Guest comment - check if current guest can delete
    $comment_guest_id = get_comment_meta($comment->comment_ID, '_puna_tiktok_guest_id', true);
    $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
    
    // If comment has guest_id, allow deletion (JavaScript will verify with localStorage)
    // This allows guest to see delete button, actual permission checked in AJAX handler
    if (!empty($comment_guest_id)) {
        // Show delete button if cookie matches OR if no cookie (will be checked via AJAX with localStorage)
        if (!empty($current_guest_id) && $comment_guest_id === $current_guest_id) {
            $can_delete_comment = true;
        } elseif (empty($current_guest_id)) {
            // No cookie but comment has guest_id - show button, let AJAX verify with localStorage
            $can_delete_comment = true;
        }
    }
}

$guest_id = '';
if (!$comment_author_id) {
    $guest_id = get_comment_meta($comment->comment_ID, '_puna_tiktok_guest_id', true);
}
?>

<div class="comment-item" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
    <div class="comment-avatar-wrapper">
        <?php echo puna_tiktok_get_avatar_html($comment_author_id > 0 ? $comment_author_id : $comment->comment_author, 40, 'comment-avatar', $guest_id); ?>
    </div>
    <div class="comment-content">
        <div class="comment-header">
            <span class="comment-author-wrapper">
                <strong class="comment-author"><?php echo esc_html($comment_author_id > 0 ? puna_tiktok_get_user_display_name($comment_author_id) : $comment->comment_author); ?></strong>
            </span>
        </div>
        <p class="comment-text"><?php echo wp_kses_post($comment->comment_content); ?></p>
        <div class="comment-footer">
            <span class="comment-date"><?php printf(esc_html__('%s ago', 'puna-tiktok'), esc_html($comment_date)); ?></span>
            <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                <?php esc_html_e('Reply', 'puna-tiktok'); ?>
            </a>
        </div>
    </div>
    <div class="comment-right-actions">
        <?php if ($can_delete_comment) : ?>
            <div class="comment-actions">
                <button class="comment-options-btn" title="<?php esc_attr_e('Options', 'puna-tiktok'); ?>"><?php echo puna_tiktok_get_icon('dot', __('Options', 'puna-tiktok')); ?></button>
                <div class="comment-options-dropdown">
                    <button class="comment-action-delete" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php echo puna_tiktok_get_icon('delete', __('Delete', 'puna-tiktok')); ?> <?php esc_html_e('Delete', 'puna-tiktok'); ?>
                    </button>
                </div>
            </div>
        <?php elseif (is_user_logged_in() && !$can_delete_comment) : ?>
            <div class="comment-actions">
                <button class="comment-options-btn" title="<?php esc_attr_e('Options', 'puna-tiktok'); ?>"><?php echo puna_tiktok_get_icon('dot', __('Options', 'puna-tiktok')); ?></button>
                <div class="comment-options-dropdown">
                    <button class="comment-action-report" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php echo puna_tiktok_get_icon('report', __('Report', 'puna-tiktok')); ?> <?php esc_html_e('Report', 'puna-tiktok'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        <div class="comment-likes<?php echo $is_liked ? ' liked' : ''; ?>" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
            <?php echo puna_tiktok_get_icon('heart-alt', __('Like', 'puna-tiktok')); ?>
            <span><?php echo esc_html($comment_likes); ?></span>
        </div>
    </div>
</div>

