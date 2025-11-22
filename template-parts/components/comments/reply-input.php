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
               placeholder="<?php esc_attr_e('Write a reply...', 'puna-tiktok'); ?>" 
               data-post-id="<?php echo esc_attr($post_id); ?>"
               data-parent-id="<?php echo esc_attr($parent_id); ?>">
        <div class="comment-input-actions">
            <div class="comment-submit-actions">
                <button class="submit-comment-btn" 
                        data-post-id="<?php echo esc_attr($post_id); ?>" 
                        data-parent-id="<?php echo esc_attr($parent_id); ?>"
                        disabled><?php esc_html_e('Post', 'puna-tiktok'); ?></button>
                <button class="cancel-reply-btn" title="<?php esc_attr_e('Cancel', 'puna-tiktok'); ?>"><?php esc_html_e('Cancel', 'puna-tiktok'); ?></button>
            </div>
        </div>
    </div>
</div>

