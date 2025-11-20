<?php
/**
 * Template part for displaying reply input container
 * 
 * @var int $post_id
 * @var int $parent_id
 * @var bool $is_reply Indicates if this is a reply to a reply (for padding)
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
if (!isset($post_id)) {
    $post_id = 0;
}

if (!isset($parent_id)) {
    $parent_id = 0;
}

$padding_left = isset($is_reply) && $is_reply ? '52px' : '28px';
?>

<div class="reply-input-container" style="padding-left: <?php echo esc_attr($padding_left); ?>;">
    <div class="comment-input-container reply-input">
        <input type="text" 
               class="comment-input reply-input-field" 
               placeholder="<?php esc_attr_e('Viết phản hồi...', 'puna-tiktok'); ?>" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               data-parent-id="<?php echo esc_attr($parent_id); ?>">
        <div class="comment-input-actions">
            <button class="comment-action-btn" title="<?php esc_attr_e('Gắn thẻ người dùng', 'puna-tiktok'); ?>">
                <i class="fa-solid fa-at"></i>
            </button>
            <button class="comment-action-btn" title="<?php esc_attr_e('Emoji', 'puna-tiktok'); ?>">
                <i class="fa-regular fa-face-smile"></i>
            </button>
            <div class="comment-submit-actions">
                <button class="submit-comment-btn" 
                        data-post-id="<?php echo esc_attr($post_id); ?>" 
                        data-parent-id="<?php echo esc_attr($parent_id); ?>"
                        disabled><?php esc_html_e('Đăng', 'puna-tiktok'); ?></button>
                <button class="cancel-reply-btn" title="<?php esc_attr_e('Hủy', 'puna-tiktok'); ?>"><?php esc_html_e('Hủy', 'puna-tiktok'); ?></button>
            </div>
        </div>
    </div>
</div>

