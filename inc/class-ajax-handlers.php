<?php

/**
 * AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_AJAX_Handlers {
    
    public function __construct() {
        // Like
        add_action('wp_ajax_puna_tiktok_toggle_like', array($this, 'toggle_like'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_like', array($this, 'toggle_like'));

        // Comment
        add_action('wp_ajax_puna_tiktok_add_comment', array($this, 'add_comment'));
        add_action('wp_ajax_nopriv_puna_tiktok_add_comment', array($this, 'add_comment'));

        // View
        add_action('wp_ajax_puna_tiktok_increment_view', array($this, 'increment_view'));
        add_action('wp_ajax_nopriv_puna_tiktok_increment_view', array($this, 'increment_view'));

        // Delete comment
        add_action('wp_ajax_puna_tiktok_delete_comment', array($this, 'delete_comment'));
        add_action('wp_ajax_nopriv_puna_tiktok_delete_comment', array($this, 'delete_comment'));
        add_action('wp_ajax_puna_tiktok_report_comment', array($this, 'report_comment'));

        // Like comment
        add_action('wp_ajax_puna_tiktok_toggle_comment_like', array($this, 'toggle_comment_like'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_comment_like', array($this, 'toggle_comment_like'));
        
        // Search
        add_action('wp_ajax_puna_tiktok_search_suggestions', array($this, 'get_search_suggestions'));
        add_action('wp_ajax_nopriv_puna_tiktok_search_suggestions', array($this, 'get_search_suggestions'));
        
        // Save search history
        add_action('wp_ajax_puna_tiktok_save_search', array($this, 'save_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_save_search', array($this, 'save_search_history'));
        
        // Get search history
        add_action('wp_ajax_puna_tiktok_get_search_history', array($this, 'get_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_search_history', array($this, 'get_search_history'));
        
        // Clear search history
        add_action('wp_ajax_puna_tiktok_clear_search_history', array($this, 'clear_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_clear_search_history', array($this, 'clear_search_history'));
        
        // Popular searches
        add_action('wp_ajax_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        
        // Get related searches
        add_action('wp_ajax_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        
        // increment shares
        add_action('wp_ajax_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        add_action('wp_ajax_nopriv_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        
        // Toggle save
        add_action('wp_ajax_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        
        // Popular hashtags
        add_action('wp_ajax_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        
        // Get reply input HTML
        add_action('wp_ajax_puna_tiktok_get_reply_input', array($this, 'get_reply_input'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_reply_input', array($this, 'get_reply_input'));
    }

    /**
     * Toggle like video
     * Guest-only mode: Frontend JS handles "My Likes" persistence via LocalStorage
     */
    public function toggle_like() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
        }
        
        $current_likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'like';
        
        if ($action === 'unlike') {
            $new_likes = max(0, $current_likes - 1);
            update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => false,
                'likes' => $new_likes
            ));
        } else {
            $new_likes = $current_likes + 1;
            update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => true,
                'likes' => $new_likes
            ));
        }
    }

    /**
     * Add comment
     * Guest-only mode: All comments are from guests
     */
    public function add_comment() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $comment_text = sanitize_text_field($_POST['comment_text']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
        }
        
        if (empty($comment_text)) {
            wp_send_json_error(array('message' => __('Comment cannot be empty.', 'puna-tiktok')));
        }
        
        // Always treat as guest
        $guest_id = isset($_POST['guest_id']) ? sanitize_text_field($_POST['guest_id']) : '';
        if (empty($guest_id)) {
            $guest_id = 'guest_' . md5($_SERVER['REMOTE_ADDR'] . time() . wp_generate_password(8, false));
        }
        
        if (!isset($_COOKIE['puna_tiktok_guest_id'])) {
            setcookie('puna_tiktok_guest_id', $guest_id, time() + (365 * 24 * 60 * 60), '/');
        } else {
            $guest_id = $_COOKIE['puna_tiktok_guest_id'];
        }
        
        $guest_name = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : __('Guest', 'puna-tiktok');
        $comment_author = $guest_name . ' #' . substr($guest_id, 6, 8);
        $comment_email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email']) : '';
        if (empty($comment_email)) {
            $comment_email = $guest_id . '@guest.local';
        }
        
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_email,
            'comment_content' => $comment_text,
            'comment_status' => 'approve',
            'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
            'user_id' => 0,
            'comment_parent' => $parent_id,
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if (!$comment_id) {
            wp_send_json_error(array('message' => __('Cannot add comment.', 'puna-tiktok')));
        }
        
        update_comment_meta($comment_id, '_puna_tiktok_guest_id', $guest_id);
        
        // Get the comment object
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => __('Cannot get comment information.', 'puna-tiktok')));
        }
        
        // Get liked comments (empty for guests)
        $liked_comments = array();
        
        // Render HTML using template part
        ob_start();
        
        if ($parent_id > 0) {
            // This is a reply
            get_template_part('template-parts/components/comments/comment-reply', null, array(
                'reply' => $comment,
                'post_id' => $post_id,
                'liked_comments' => $liked_comments,
            ));
        } else {
            // This is a top-level comment
            get_template_part('template-parts/components/comments/comment-item', null, array(
                'comment' => $comment,
                'post_id' => $post_id,
                'liked_comments' => $liked_comments,
            ));
        }
        
        $html = ob_get_clean();
        $html = trim($html);
        
        // Ensure HTML is not empty
        if (empty($html)) {
            // Fallback: create basic HTML structure
            $comment_author_name = $comment->comment_author;
            $comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
            $comment_likes = get_comment_meta($comment->comment_ID, '_comment_likes', true) ?: 0;
            
            $is_reply_class = $parent_id > 0 ? ' comment-reply' : '';
            
            ob_start();
            ?>
            <div class="comment-item<?php echo esc_attr($is_reply_class); ?>" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                <div class="comment-avatar-wrapper">
                    <?php echo puna_tiktok_get_avatar_html($comment->comment_author, 40, 'comment-avatar', $guest_id); ?>
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author-wrapper">
                            <strong class="comment-author"><?php echo esc_html($comment_author_name); ?></strong>
                        </span>
                    </div>
                    <p class="comment-text"><?php echo wp_kses_post($comment->comment_content); ?></p>
                    <div class="comment-footer">
                        <span class="comment-date"><?php printf(esc_html__('%s ago', 'puna-tiktok'), esc_html($comment_date)); ?></span>
                        <a href="#" class="reply-link" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"><?php esc_html_e('Reply', 'puna-tiktok'); ?></a>
                    </div>
                </div>
                <div class="comment-right-actions">
                    <div class="comment-likes" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php echo puna_tiktok_get_icon('heart-alt', 'Like'); ?>
                        <span><?php echo esc_html($comment_likes); ?></span>
                    </div>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
        }
        
        wp_send_json_success(array(
            'comment_id' => $comment_id,
            'guest_id' => $guest_id,
            'html' => $html,
            'is_reply' => $parent_id > 0
        ));
    }

    /**
     * Increment video view
     */
    public function increment_view() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
        wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
    }
    $new_views = puna_tiktok_increment_video_views($post_id);
    wp_send_json_success(array(
        'views' => $new_views,
        'formatted_views' => puna_tiktok_format_number($new_views)
    ));
    }

    /**
     * Toggle comment like
     * Guest-only mode: Frontend JS handles "My Comment Likes" persistence via LocalStorage
     */
    public function toggle_comment_like() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => __('Missing comment_id.', 'puna-tiktok')));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => __('Comment does not exist.', 'puna-tiktok')));
        }
        
        $current_likes = get_comment_meta($comment_id, '_comment_likes', true) ?: 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'like';
        
        if ($action === 'unlike') {
            $new_likes = max(0, $current_likes - 1);
            update_comment_meta($comment_id, '_comment_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => false,
                'likes' => $new_likes
            ));
        } else {
            $new_likes = $current_likes + 1;
            update_comment_meta($comment_id, '_comment_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => true,
                'likes' => $new_likes
            ));
        }
    }

    /**
     * Delete comment recursively
     */
    private function delete_comment_recursive($comment_id) {
        $replies = get_comments(array(
            'parent' => $comment_id,
            'status' => 'any',
        ));
        
        foreach ($replies as $reply) {
            $this->delete_comment_recursive($reply->comment_ID);
        }
        
        wp_delete_comment($comment_id, true);
    }

    /**
     * Delete comment
     */
    public function delete_comment() {
        // Check nonce for logged in users only
        if (is_user_logged_in()) {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
                wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
                return;
            }
        }
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => __('Missing comment_id.', 'puna-tiktok')));
            return;
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => __('Comment does not exist.', 'puna-tiktok')));
            return;
        }
        
        $user_id = get_current_user_id();
        $is_admin = current_user_can('moderate_comments');
        $can_delete = false;
        
        // Admin can delete any comment
        if ($is_admin) {
            $can_delete = true;
        } 
        // User can delete their own comment
        elseif ($user_id > 0 && $comment->user_id == $user_id) {
            $can_delete = true;
        } 
        // Guest can delete their own comment (by guest_id)
        elseif ($user_id == 0) {
            $comment_guest_id = get_comment_meta($comment_id, '_puna_tiktok_guest_id', true);
            
            // Try to get guest_id from POST request first (from localStorage)
            $current_guest_id = isset($_POST['guest_id']) ? sanitize_text_field($_POST['guest_id']) : '';
            
            // Fallback to cookie if not in POST
            if (empty($current_guest_id)) {
                $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
            }
            
            if (!empty($comment_guest_id) && !empty($current_guest_id) && $comment_guest_id === $current_guest_id) {
                $can_delete = true;
            }
        }
        
        if (!$can_delete) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this comment.', 'puna-tiktok')));
            return;
        }
    
    $total_to_delete = 1;
    $count_replies = function($parent_id) use (&$count_replies) {
        $replies = get_comments(array(
            'parent' => $parent_id,
            'status' => 'any',
            'count' => false,
        ));
        $count = count($replies);
        foreach ($replies as $reply) {
            $count += $count_replies($reply->comment_ID);
        }
        return $count;
    };
    $total_to_delete += $count_replies($comment_id);
    
    $this->delete_comment_recursive($comment_id);
    
    wp_send_json_success(array(
        'deleted_count' => $total_to_delete
    ));
    }

    /**
     * Report comment
     */
    public function report_comment() {
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
        wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
    }
    $comment_id = intval($_POST['comment_id'] ?? 0);
    if (!$comment_id) {
        wp_send_json_error(array('message' => __('Missing comment_id.', 'puna-tiktok')));
    }
    $reports = (int) get_comment_meta($comment_id, '_comment_reports', true);
    update_comment_meta($comment_id, '_comment_reports', $reports + 1);
    wp_send_json_success(array());
    }

    /**
     * Get search suggestions
     */
    public function get_search_suggestions() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query) || strlen($query) < 2) {
            wp_send_json_success(array('suggestions' => array()));
        }
        
        $suggestions = array();
        
        $posts = get_posts(array(
            'post_type' => 'video',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            's' => $query,
            'orderby' => 'relevance',
            'order' => 'DESC'
        ));
        
        foreach ($posts as $post) {
            if (get_post_type($post->ID) === 'video') {
                $description = puna_tiktok_get_video_description($post->ID);
                if (!empty($description)) {
                    $suggestions[] = array(
                        'text' => $description,
                        'type' => 'video'
                    );
                }
            }
        }
        
        $history = $this->get_all_search_history();
        foreach ($history as $item) {
            if (stripos($item['query'], $query) !== false && count($suggestions) < 10) {
                $exists = false;
                foreach ($suggestions as $sug) {
                    if ($sug['text'] === $item['query']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $suggestions[] = array(
                        'text' => $item['query'],
                        'type' => 'history'
                    );
                }
            }
        }
        
        $suggestions = array_slice($suggestions, 0, 10);
        
        wp_send_json_success(array('suggestions' => $suggestions));
    }

    /**
     * Save search history
     * Guest-only mode: Frontend JS must handle persistence via LocalStorage
     */
    public function save_search_history() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Query cannot be empty.', 'puna-tiktok')));
        }
        
        // Do not save to database - tell frontend to use LocalStorage
        wp_send_json_success(array('message' => 'Use LocalStorage'));
    }

    /**
     * Get search history
     * Guest-only mode: Frontend JS must handle persistence via LocalStorage
     */
    public function get_search_history() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        // Do not read from database - tell frontend to use LocalStorage
        wp_send_json_success(array('history' => array(), 'message' => 'Use LocalStorage'));
    }

    /**
     * Get all search history
     */
    private function get_all_search_history() {
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            return array();
        }
        
        $combined = array();
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        
        foreach ($all_history as $identifier => $history) {
            if (is_array($history)) {
                foreach ($history as $item) {
                    if ($item['timestamp'] >= $week_ago) {
                        $combined[] = $item;
                    }
                }
            }
        }
        
        usort($combined, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $combined;
    }

    /**
     * Clear search history
     */
    public function clear_search_history() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $_SERVER['REMOTE_ADDR'];
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (is_array($all_history) && isset($all_history[$identifier])) {
            unset($all_history[$identifier]);
            update_option($option_key, $all_history);
        }
        
        wp_send_json_success(array());
    }

    /**
     * Get popular searches
     */
    public function get_popular_searches() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            wp_send_json_success(array('popular' => array()));
        }
        
        $search_counts = array();
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        
        foreach ($all_history as $identifier => $history) {
            if (is_array($history)) {
                foreach ($history as $item) {
                    if ($item['timestamp'] >= $week_ago && !empty($item['query'])) {
                        $query = trim($item['query']);
                        if (!isset($search_counts[$query])) {
                            $search_counts[$query] = 0;
                        }
                        $search_counts[$query]++;
                    }
                }
            }
        }
        
        arsort($search_counts);
        
        $popular = array();
        $count = 0;
        foreach ($search_counts as $query => $frequency) {
            if ($count >= 10) break;
            $popular[] = array(
                'query' => $query,
                'count' => $frequency
            );
            $count++;
        }
        
        if (empty($popular)) {
            $popular = array();
        }
        
        wp_send_json_success(array('popular' => $popular));
    }

    /**
     * Get related searches
     */
    public function get_related_searches() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $current_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($current_query)) {
            wp_send_json_success(array('related' => array()));
        }
        
        $related_searches = array();
        $current_query_lower = mb_strtolower($current_query);
        $current_words = array_filter(explode(' ', $current_query_lower));
        
        // Get posts that match the search query
        $search_args = array(
            'post_type' => 'video',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Get more posts to extract keywords
            's' => $current_query,
            'orderby' => 'relevance',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => '_puna_tiktok_video_url',
                    'value'   => '',
                    'compare' => '!=',
                ),
                array(
                    'key'     => '_puna_tiktok_video_file_id',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_puna_tiktok_youtube_id',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        
        $posts = get_posts($search_args);
        
        // Extract keywords from descriptions
        $all_keywords = array();
        
        foreach ($posts as $post) {
            $description = puna_tiktok_get_video_description($post->ID);
            
            if (!empty($description)) {
                // Convert to lowercase and remove special characters
                $description_lower = mb_strtolower($description);
                $description_lower = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $description_lower);
                
                // Split into words
                $words = array_filter(explode(' ', $description_lower));
                
                // Extract meaningful phrases (2-4 words)
                foreach ($words as $index => $word) {
                    // Skip very short words
                    if (mb_strlen($word) < 2) {
                        continue;
                    }
                    
                    // Skip if word is already in current query
                    if (in_array($word, $current_words)) {
                        continue;
                    }
                    
                    // Add single word
                    if (mb_strlen($word) >= 2) {
                        if (!isset($all_keywords[$word])) {
                            $all_keywords[$word] = 0;
                        }
                        $all_keywords[$word]++;
                    }
                    
                    // Create 2-word phrases
                    if ($index < count($words) - 1) {
                        $next_word = $words[$index + 1];
                        if (mb_strlen($next_word) >= 2) {
                            $phrase = $word . ' ' . $next_word;
                            if (!isset($all_keywords[$phrase])) {
                                $all_keywords[$phrase] = 0;
                            }
                            $all_keywords[$phrase]++;
                        }
                    }
                    
                    // Create 3-word phrases
                    if ($index < count($words) - 2) {
                        $next_word = $words[$index + 1];
                        $next_word2 = $words[$index + 2];
                        if (mb_strlen($next_word) >= 2 && mb_strlen($next_word2) >= 2) {
                            $phrase = $word . ' ' . $next_word . ' ' . $next_word2;
                            if (!isset($all_keywords[$phrase])) {
                                $all_keywords[$phrase] = 0;
                            }
                            $all_keywords[$phrase]++;
                        }
                    }
                }
            }
        }
        
        // Sort by frequency and create suggestions
        arsort($all_keywords);
        
        $related = array();
        $count = 0;
        $max_length = 30; // Max length for suggestion
        
        foreach ($all_keywords as $keyword => $frequency) {
            if ($count >= 10) break;
            
            // Skip if too short or too long
            if (mb_strlen($keyword) < 2 || mb_strlen($keyword) > $max_length) {
                continue;
            }
            
            // Skip if keyword is exactly the same as current query
            if (mb_strtolower($keyword) === $current_query_lower) {
                continue;
            }
            
            // Skip if keyword is contained in current query
            if (strpos($current_query_lower, mb_strtolower($keyword)) !== false) {
                continue;
            }
            
            // Only include if frequency is at least 2 (appears in at least 2 posts)
            if ($frequency >= 2) {
                $related[] = array(
                    'query' => trim($keyword),
                    'count' => $frequency
                );
                $count++;
            }
        }
        
        // If we don't have enough suggestions, fall back to search history
        if (count($related) < 5) {
            $option_key = 'puna_tiktok_search_history';
            $all_history = get_option($option_key, array());
            
            if (is_array($all_history)) {
                $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
                $history_suggestions = array();
                
                foreach ($all_history as $identifier => $history) {
                    if (is_array($history)) {
                        foreach ($history as $item) {
                            if ($item['timestamp'] >= $week_ago && !empty($item['query'])) {
                                $query = trim($item['query']);
                                $query_lower = mb_strtolower($query);
                                
                                if ($query_lower === $current_query_lower) {
                                    continue;
                                }
                                
                                // Check if query contains any of the current words
                                $has_common_word = false;
                                foreach ($current_words as $word) {
                                    if (strlen($word) >= 2 && strpos($query_lower, $word) !== false) {
                                        $has_common_word = true;
                                        break;
                                    }
                                }
                                
                                if ($has_common_word && !isset($history_suggestions[$query])) {
                                    $history_suggestions[$query] = 1;
                                }
                            }
                        }
                    }
                }
                
                // Add history suggestions
                foreach ($history_suggestions as $query => $freq) {
                    if ($count >= 10) break;
                    $related[] = array(
                        'query' => $query,
                        'count' => $freq
                    );
                    $count++;
                }
            }
        }
        
        wp_send_json_success(array('related' => $related));
    }
    
    /**
     * Increment share count
     */
    public function increment_shares() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
        }
        
        $current_shares = get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0;
        $new_shares = intval($current_shares) + 1;
        update_post_meta($post_id, '_puna_tiktok_video_shares', $new_shares);
        
        wp_send_json_success(array(
            'share_count' => $new_shares
        ));
    }
    
    /**
     * Toggle save video
     * Guest-only mode: Frontend JS handles "My Saves" persistence via LocalStorage
     */
    public function toggle_save() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
        }
        
        $current_saves = get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'save';
        
        if ($action === 'unsave') {
            $new_saves = max(0, $current_saves - 1);
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            wp_send_json_success(array(
                'is_saved' => false,
                'saves' => $new_saves
            ));
        } else {
            $new_saves = $current_saves + 1;
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            wp_send_json_success(array(
                'is_saved' => true,
                'saves' => $new_saves
            ));
        }
    }

    /**
     * Get popular hashtags
     */
    public function get_popular_hashtags() {
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        $tags = get_terms(array(
            'taxonomy' => 'video_tag',
            'hide_empty' => true,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => '_puna_tiktok_hashtag_usage',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (empty($tags) || is_wp_error($tags)) {
            $tags = get_terms(array(
                'taxonomy' => 'video_tag',
                'hide_empty' => true,
                'number' => $limit,
                'orderby' => 'count',
                'order' => 'DESC'
            ));
        }
        
        $hashtags = array();
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $hashtags[] = array(
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'count' => $tag->count,
                    'url' => home_url('/tag/' . $tag->term_id)
                );
            }
        }
        
        wp_send_json_success(array(
            'hashtags' => $hashtags
        ));
    }
    
    /**
     * Get reply input HTML
     */
    public function get_reply_input() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce.', 'puna-tiktok')));
            return;
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $is_reply = isset($_POST['is_reply']) ? (bool) $_POST['is_reply'] : false;
        
        // Validate post_id
        if (!$post_id || $post_id <= 0) {
            // Try to get from current post if on single page
            if (is_singular('video')) {
                $post_id = get_the_ID();
            }
            
            if (!$post_id || $post_id <= 0) {
                wp_send_json_error(array('message' => __('Missing video information.', 'puna-tiktok')));
                return;
            }
        }
        
        // Validate parent_id
        if (!$parent_id || $parent_id <= 0) {
            wp_send_json_error(array('message' => __('Missing parent comment information.', 'puna-tiktok')));
            return;
        }
        
        // Validate post type
        if (get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => __('Invalid video.', 'puna-tiktok')));
            return;
        }
        
        // Render HTML using template part
        ob_start();
        get_template_part('template-parts/components/comments/reply-input', null, array(
            'post_id' => $post_id,
            'parent_id' => $parent_id,
            'is_reply' => $is_reply,
        ));
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html
        ));
    }
    
}

// Initialize the class
new Puna_TikTok_AJAX_Handlers();

