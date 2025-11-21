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
            <h3><?php printf(esc_html__('Comments (%s)', 'puna-tiktok'), number_format($comments_count)); ?></h3>
            <button class="close-comments-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                <?php echo puna_tiktok_get_icon('close', __('Close', 'puna-tiktok')); ?>
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
                
                // Query all approved comments for this post once (optimization)
                $all_post_comments = get_comments(array(
                    'post_id' => $post_id,
                    'status' => 'approve',
                    'orderby' => 'comment_date',
                    'order' => 'ASC',
                ));
                
                if (!is_array($all_post_comments)) {
                    $all_post_comments = array();
                }
                
                // Loop
                foreach ($top_level_comments as $comment) {
                    // Filter to get only direct replies (parent = comment_ID)
                    $direct_replies = array();
                    foreach ($all_post_comments as $post_comment) {
                        if (isset($post_comment->comment_parent) && $post_comment->comment_parent == $comment->comment_ID) {
                            $direct_replies[] = $post_comment;
                        }
                    }
                    
                    // Get all nested replies recursively
                    $all_replies = $direct_replies;
                    $processed_ids = array();
                    
                    // Closure to get nested replies
                    $get_all_nested_replies = function($parent_id, &$all_replies, &$processed_ids, $all_post_comments) use (&$get_all_nested_replies) {
                        // Filter from all comments to get nested replies
                        $nested = array();
                        foreach ($all_post_comments as $post_comment) {
                            if (isset($post_comment->comment_parent) && $post_comment->comment_parent == $parent_id) {
                                $nested[] = $post_comment;
                            }
                        }
                        
                        if (!empty($nested)) {
                        foreach ($nested as $nested_reply) {
                                if (is_object($nested_reply) && !in_array($nested_reply->comment_ID, $processed_ids)) {
                                $all_replies[] = $nested_reply;
                                $processed_ids[] = $nested_reply->comment_ID;
                                // Recursively get nested replies
                                    $get_all_nested_replies($nested_reply->comment_ID, $all_replies, $processed_ids, $all_post_comments);
                                }
                            }
                        }
                    };
                    
                    if (!empty($direct_replies)) {
                    foreach ($direct_replies as $direct_reply) {
                            if (is_object($direct_reply) && !in_array($direct_reply->comment_ID, $processed_ids)) {
                        $processed_ids[] = $direct_reply->comment_ID;
                                $get_all_nested_replies($direct_reply->comment_ID, $all_replies, $processed_ids, $all_post_comments);
                            }
                        }
                    }
                    
                    // Sort all replies by date
                    if (!empty($all_replies)) {
                    usort($all_replies, function($a, $b) {
                        return strtotime($a->comment_date) - strtotime($b->comment_date);
                    });
                    }
                    
                    $replies = is_array($all_replies) ? $all_replies : array();
                    
                    // Render comment item
                    get_template_part('template-parts/components/comments/comment-item', null, array(
                        'comment' => $comment,
                        'post_id' => $post_id,
                        'liked_comments' => $liked_comments,
                    ));
                    
                    // Render replies section if exists
                    // Always render replies section, even if empty, to ensure structure is correct
                    // The template will handle empty case internally
                    get_template_part('template-parts/components/comments/comment-replies', null, array(
                        'parent_id' => $comment->comment_ID,
                        'replies' => $replies,
                        'post_id' => $post_id,
                        'liked_comments' => $liked_comments,
                    ));
                }
            } else {
                ?>
                <div class="no-comments">
                    <p><?php esc_html_e('No comments yet. Be the first to comment!', 'puna-tiktok'); ?></p>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Comment Input -->
        <div class="comment-input-container">
            <input type="text" 
                   class="comment-input" 
                   placeholder="<?php esc_attr_e('Add a comment...', 'puna-tiktok'); ?>" 
                   data-post-id="<?php echo esc_attr($post_id); ?>">
            <div class="comment-input-actions">
                <button class="comment-action-btn" title="<?php esc_attr_e('Tag user', 'puna-tiktok'); ?>">
                    <?php echo puna_tiktok_get_icon('home', __('Tag user', 'puna-tiktok')); ?>
                </button>
                <button class="comment-action-btn" title="<?php esc_attr_e('Emoji', 'puna-tiktok'); ?>">
                    <?php echo puna_tiktok_get_icon('heart', __('Emoji', 'puna-tiktok')); ?>
                </button>
                <div class="comment-submit-actions">
                    <button class="submit-comment-btn" data-post-id="<?php echo esc_attr($post_id); ?>" disabled>
                        <?php esc_html_e('Post', 'puna-tiktok'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


