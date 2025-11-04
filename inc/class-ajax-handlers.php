<?php

/**
 * AJAX Handlers
 *
 * @package puna-tiktok
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_AJAX_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_puna_tiktok_toggle_like', array($this, 'toggle_like'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_like', array($this, 'toggle_like'));

        add_action('wp_ajax_puna_tiktok_add_comment', array($this, 'add_comment'));
        add_action('wp_ajax_nopriv_puna_tiktok_add_comment', array($this, 'add_comment'));

        add_action('wp_ajax_puna_tiktok_increment_view', array($this, 'increment_view'));
        add_action('wp_ajax_nopriv_puna_tiktok_increment_view', array($this, 'increment_view'));

        add_action('wp_ajax_puna_tiktok_delete_comment', array($this, 'delete_comment'));
        add_action('wp_ajax_puna_tiktok_report_comment', array($this, 'report_comment'));

        add_action('wp_ajax_puna_tiktok_toggle_comment_like', array($this, 'toggle_comment_like'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_comment_like', array($this, 'toggle_comment_like'));
        
        add_action('wp_ajax_puna_tiktok_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_puna_tiktok_login', array($this, 'handle_login'));
        
        // Search functionality
        add_action('wp_ajax_puna_tiktok_search_suggestions', array($this, 'get_search_suggestions'));
        add_action('wp_ajax_nopriv_puna_tiktok_search_suggestions', array($this, 'get_search_suggestions'));
        
        add_action('wp_ajax_puna_tiktok_save_search', array($this, 'save_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_save_search', array($this, 'save_search_history'));
        
        add_action('wp_ajax_puna_tiktok_get_search_history', array($this, 'get_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_search_history', array($this, 'get_search_history'));
        
        add_action('wp_ajax_puna_tiktok_clear_search_history', array($this, 'clear_search_history'));
        add_action('wp_ajax_nopriv_puna_tiktok_clear_search_history', array($this, 'clear_search_history'));
        
        add_action('wp_ajax_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        
        add_action('wp_ajax_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        
        // Upload video
        add_action('wp_ajax_puna_tiktok_upload_video', array($this, 'upload_video'));
    }

    /**
     * Like/Unlike Video - AJAX Handler
     */
    public function toggle_like() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'));
    }
    
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();
    
    if (!$post_id || ! has_block('puna/hupuna-tiktok', $post_id)) {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    
    // Get current likes
    $current_likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
    
    // Get user liked posts
    $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
    if (!is_array($liked_posts)) {
        $liked_posts = array();
    }
    
    $is_liked = in_array($post_id, $liked_posts);
    
    if ($is_liked) {
        // Unlike
        $liked_posts = array_values(array_diff($liked_posts, array($post_id)));
        update_user_meta($user_id, '_puna_tiktok_liked_videos', $liked_posts);
        $new_likes = max(0, $current_likes - 1);
        update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
        
        wp_send_json_success(array(
            'is_liked' => false,
            'likes' => $new_likes,
            'message' => 'Đã bỏ thích video'
        ));
    } else {
        // Like
        $liked_posts[] = $post_id;
        update_user_meta($user_id, '_puna_tiktok_liked_videos', $liked_posts);
        $new_likes = $current_likes + 1;
        update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
        
        wp_send_json_success(array(
            'is_liked' => true,
            'likes' => $new_likes,
            'message' => 'Đã thích video'
        ));
    }
}

    /**
     * Add comment via AJAX
     */
    public function add_comment() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập để bình luận.'));
    }
    
    $post_id = intval($_POST['post_id']);
    $comment_text = sanitize_text_field($_POST['comment_text']);
    
    if (!$post_id || ! has_block('puna/hupuna-tiktok', $post_id)) {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    
    if (empty($comment_text)) {
        wp_send_json_error(array('message' => 'Bình luận không được để trống.'));
    }
    
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content' => $comment_text,
        'comment_status' => 'approve',
        'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
        'user_id' => $user_id,
        'comment_parent' => $parent_id, // Set parent for replies
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Không thể thêm bình luận.'));
    }
    
    wp_send_json_success(array(
        'message' => 'Bình luận đã được thêm.',
        'comment_id' => $comment_id
    ));
}

    /**
     * Increment video view count via AJAX
     */
    public function increment_view() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || ! has_block('puna/hupuna-tiktok', $post_id)) {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    // Increment view count
    $new_views = puna_tiktok_increment_video_views($post_id);
    wp_send_json_success(array(
        'views' => $new_views,
        'formatted_views' => puna_tiktok_format_number($new_views)
    ));
    }

    /**
     * Like/Unlike Comment - AJAX Handler
     */
    public function toggle_comment_like() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'));
        }
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => 'Thiếu comment_id.'));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Bình luận không tồn tại.'));
        }
        
        $user_id = get_current_user_id();
        
        // Get current likes
        $current_likes = get_comment_meta($comment_id, '_comment_likes', true) ?: 0;
        
        // Get user liked comments
        $liked_comments = get_user_meta($user_id, '_puna_tiktok_liked_comments', true);
        if (!is_array($liked_comments)) {
            $liked_comments = array();
        }
        
        $is_liked = in_array($comment_id, $liked_comments);
        
        if ($is_liked) {
            // Unlike
            $liked_comments = array_values(array_diff($liked_comments, array($comment_id)));
            update_user_meta($user_id, '_puna_tiktok_liked_comments', $liked_comments);
            $new_likes = max(0, $current_likes - 1);
            update_comment_meta($comment_id, '_comment_likes', $new_likes);
            
            wp_send_json_success(array(
                'is_liked' => false,
                'likes' => $new_likes,
                'message' => 'Đã bỏ thích bình luận'
            ));
        } else {
            // Like
            $liked_comments[] = $comment_id;
            update_user_meta($user_id, '_puna_tiktok_liked_comments', $liked_comments);
            $new_likes = $current_likes + 1;
            update_comment_meta($comment_id, '_comment_likes', $new_likes);
            
            wp_send_json_success(array(
                'is_liked' => true,
                'likes' => $new_likes,
                'message' => 'Đã thích bình luận'
            ));
        }
    }

    /**
     * Delete a comment and all its replies recursively (owner or moderator)
     */
    private function delete_comment_recursive($comment_id) {
        // Get all direct replies
        $replies = get_comments(array(
            'parent' => $comment_id,
            'status' => 'any', // Get all statuses to ensure deletion
        ));
        
        // Recursively delete all child comments
        foreach ($replies as $reply) {
            $this->delete_comment_recursive($reply->comment_ID);
        }
        
        // Delete the comment itself (force delete)
        wp_delete_comment($comment_id, true);
    }

    /**
     * Delete a comment (owner or moderator)
     */
    public function delete_comment() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Bạn cần đăng nhập.'));
    }
    $comment_id = intval($_POST['comment_id'] ?? 0);
    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Thiếu comment_id.'));
    }
    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(array('message' => 'Bình luận không tồn tại.'));
    }
    $user_id = get_current_user_id();
    $can_delete = ($comment->user_id == $user_id) || current_user_can('moderate_comments');
    if (!$can_delete) {
        wp_send_json_error(array('message' => 'Bạn không có quyền xóa bình luận này.'));
    }
    
    // Count total comments to be deleted (including replies)
    $total_to_delete = 1; // The comment itself
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
    
    // Delete comment and all its replies recursively
    $this->delete_comment_recursive($comment_id);
    
    wp_send_json_success(array(
        'message' => 'Đã xóa bình luận.',
        'deleted_count' => $total_to_delete
    ));
    }

    /**
     * Report a comment (anyone)
     */
    public function report_comment() {
    // Nonce optional for nopriv? We accept if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
        wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
    }
    $comment_id = intval($_POST['comment_id'] ?? 0);
    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Thiếu comment_id.'));
    }
    $reports = (int) get_comment_meta($comment_id, '_comment_reports', true);
    update_comment_meta($comment_id, '_comment_reports', $reports + 1);
    wp_send_json_success(array('message' => 'Đã báo cáo bình luận.'));
    }

    /**
     * Handle user login via AJAX
     */
    public function handle_login() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($username) || empty($password)) {
        wp_send_json_error(array('message' => 'Vui lòng nhập đầy đủ thông tin.'));
    }
    
    $credentials = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => $user->get_error_message()));
    }
    
    wp_send_json_success(array(
        'message' => 'Đăng nhập thành công!',
        'redirect' => home_url()
    ));
    }

    /**
     * Get search suggestions based on query
     */
    public function get_search_suggestions() {
        // Nonce is optional for search suggestions (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query) || strlen($query) < 2) {
            wp_send_json_success(array('suggestions' => array()));
        }
        
        $suggestions = array();
        
        // 1. Get matching post titles
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            's' => $query,
            'orderby' => 'relevance',
            'order' => 'DESC'
        ));
        
        foreach ($posts as $post) {
            if (has_block('puna/hupuna-tiktok', $post->ID)) {
                $suggestions[] = array(
                    'text' => $post->post_title,
                    'type' => 'video'
                );
            }
        }
        
        // 2. Get matching users
        $users = get_users(array(
            'search' => '*' . $query . '*',
            'search_columns' => array('user_login', 'display_name', 'user_nicename'),
            'number' => 3
        ));
        
        foreach ($users as $user) {
            $suggestions[] = array(
                'text' => $user->display_name,
                'type' => 'user'
            );
        }
        
        // 3. Get popular searches that match
        $history = $this->get_all_search_history();
        foreach ($history as $item) {
            if (stripos($item['query'], $query) !== false && count($suggestions) < 10) {
                // Avoid duplicates
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
        
        // Limit to 10 suggestions
        $suggestions = array_slice($suggestions, 0, 10);
        
        wp_send_json_success(array('suggestions' => $suggestions));
    }

    /**
     * Save search to history
     */
    public function save_search_history() {
        // Nonce is optional for saving search (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Query không được để trống.'));
        }
        
        // Get user ID (or use IP for guests)
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $_SERVER['REMOTE_ADDR'];
        
        // Get existing history
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            $all_history = array();
        }
        
        // Check if already exists for this identifier
        if (!isset($all_history[$identifier])) {
            $all_history[$identifier] = array();
        }
        
        // Remove if already exists (to move to top)
        $all_history[$identifier] = array_filter($all_history[$identifier], function($item) use ($query) {
            return $item['query'] !== $query;
        });
        
        // Add to beginning
        array_unshift($all_history[$identifier], array(
            'query' => $query,
            'timestamp' => current_time('timestamp')
        ));
        
        // Keep only last 50 searches per user
        $all_history[$identifier] = array_slice($all_history[$identifier], 0, 50);
        
        // Clean old searches (older than 7 days)
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        foreach ($all_history[$identifier] as $key => $item) {
            if ($item['timestamp'] < $week_ago) {
                unset($all_history[$identifier][$key]);
            }
        }
        $all_history[$identifier] = array_values($all_history[$identifier]);
        
        // Save
        update_option($option_key, $all_history);
        
        wp_send_json_success(array('message' => 'Đã lưu lịch sử tìm kiếm.'));
    }

    /**
     * Get search history for current user
     */
    public function get_search_history() {
        // Nonce is optional for getting history (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $_SERVER['REMOTE_ADDR'];
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history) || !isset($all_history[$identifier])) {
            wp_send_json_success(array('history' => array()));
        }
        
        // Clean old searches
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        $history = array_filter($all_history[$identifier], function($item) use ($week_ago) {
            return $item['timestamp'] >= $week_ago;
        });
        
        // Re-index array
        $history = array_values($history);
        
        // Limit to 10 most recent
        $history = array_slice($history, 0, 10);
        
        wp_send_json_success(array('history' => $history));
    }

    /**
     * Get all search history (for suggestions)
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
        
        // Sort by timestamp (newest first)
        usort($combined, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $combined;
    }

    /**
     * Clear search history for current user
     */
    public function clear_search_history() {
        // Nonce is optional for clearing history (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
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
        
        wp_send_json_success(array('message' => 'Đã xóa lịch sử tìm kiếm.'));
    }

    /**
     * Get popular searches based on search frequency
     */
    public function get_popular_searches() {
        // Nonce is optional for getting popular searches (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            wp_send_json_success(array('popular' => array()));
        }
        
        // Count search frequency
        $search_counts = array();
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        
        // Loop through all users' search history
        foreach ($all_history as $identifier => $history) {
            if (is_array($history)) {
                foreach ($history as $item) {
                    // Only count searches from last 7 days
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
        
        // Sort by count (descending)
        arsort($search_counts);
        
        // Get top 10 most popular searches
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
        
        // If no popular searches, return empty array or default suggestions
        if (empty($popular)) {
            // Return empty - let frontend handle default display
            $popular = array();
        }
        
        wp_send_json_success(array('popular' => $popular));
    }

    /**
     * Get related searches based on current search query
     * Ưu tiên các từ khóa có key tương tự
     */
    public function get_related_searches() {
        // Nonce is optional for getting related searches (public feature)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $current_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($current_query)) {
            wp_send_json_success(array('related' => array()));
        }
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            wp_send_json_success(array('related' => array()));
        }
        
        // Count search frequency and calculate similarity
        $related_searches = array();
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        $current_query_lower = mb_strtolower($current_query);
        $current_words = array_filter(explode(' ', $current_query_lower));
        
        // Loop through all users' search history
        foreach ($all_history as $identifier => $history) {
            if (is_array($history)) {
                foreach ($history as $item) {
                    // Only count searches from last 7 days
                    if ($item['timestamp'] >= $week_ago && !empty($item['query'])) {
                        $query = trim($item['query']);
                        $query_lower = mb_strtolower($query);
                        
                        // Skip exact match
                        if ($query_lower === $current_query_lower) {
                            continue;
                        }
                        
                        // Calculate similarity score
                        $similarity = 0;
                        $query_words = array_filter(explode(' ', $query_lower));
                        
                        // Check word matches
                        foreach ($current_words as $word) {
                            if (strlen($word) >= 2) {
                                foreach ($query_words as $qword) {
                                    if (strpos($qword, $word) !== false || strpos($word, $qword) !== false) {
                                        $similarity += 2;
                                    }
                                }
                                if (strpos($query_lower, $word) !== false) {
                                    $similarity += 1;
                                }
                            }
                        }
                        
                        // Check if query contains current query or vice versa
                        if (strpos($query_lower, $current_query_lower) !== false) {
                            $similarity += 5;
                        }
                        if (strpos($current_query_lower, $query_lower) !== false) {
                            $similarity += 3;
                        }
                        
                        // Only include if has some similarity
                        if ($similarity > 0) {
                            if (!isset($related_searches[$query])) {
                                $related_searches[$query] = array(
                                    'query' => $query,
                                    'score' => $similarity,
                                    'count' => 0
                                );
                            }
                            $related_searches[$query]['count']++;
                            // Boost score by frequency
                            $related_searches[$query]['score'] += $related_searches[$query]['count'];
                        }
                    }
                }
            }
        }
        
        // Sort by score (highest first), then by count
        usort($related_searches, function($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return $b['count'] - $a['count'];
        });
        
        // Get top 10 related searches
        $related = array();
        $count = 0;
        foreach ($related_searches as $item) {
            if ($count >= 10) break;
            $related[] = array(
                'query' => $item['query'],
                'count' => $item['count']
            );
            $count++;
        }
        
        wp_send_json_success(array('related' => $related));
    }

    /**
     * Upload Video - AJAX Handler
     */
    public function upload_video() {
        check_ajax_referer('puna_tiktok_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bạn cần đăng nhập để upload video.'));
        }
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => 'Bạn không có quyền upload video.'));
        }
        
        // Kiểm tra file video
        if (empty($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'Không thể upload file video. Vui lòng thử lại.'));
        }
        
        $file = $_FILES['video'];
        
        // Validate file type
        $allowed_types = array('video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Định dạng file không được hỗ trợ. Vui lòng chọn file video (.mp4, .mov, .avi, .webm).'));
        }
        
        // Validate file size (max 30GB)
        $max_size = 30 * 1024 * 1024 * 1024; // 30GB
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => 'File quá lớn. Kích thước tối đa là 30GB.'));
        }
        
        // Upload video file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => 'Lỗi upload: ' . $upload['error']));
        }
        
        // Create attachment
        $attachment_data = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => 'Không thể tạo attachment: ' . $attachment_id->get_error_message()));
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
        
        // Handle cover image if provided
        $cover_image_id = null;
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $cover_file = $_FILES['cover_image'];
            $cover_upload = wp_handle_upload($cover_file, array('test_form' => false));
            
            if (!isset($cover_upload['error'])) {
                $cover_attachment_data = array(
                    'post_mime_type' => $cover_upload['type'],
                    'post_title' => sanitize_file_name(pathinfo($cover_file['name'], PATHINFO_FILENAME)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                
                $cover_image_id = wp_insert_attachment($cover_attachment_data, $cover_upload['file']);
                
                if (!is_wp_error($cover_image_id)) {
                    $cover_attach_data = wp_generate_attachment_metadata($cover_image_id, $cover_upload['file']);
                    wp_update_attachment_metadata($cover_image_id, $cover_attach_data);
                }
            }
        }
        
        // Get form data
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $privacy = isset($_POST['privacy']) ? sanitize_text_field($_POST['privacy']) : 'public';
        $schedule = isset($_POST['schedule']) ? sanitize_text_field($_POST['schedule']) : 'now';
        $schedule_date = isset($_POST['schedule_date']) ? sanitize_text_field($_POST['schedule_date']) : '';
        
        // Determine post status based on privacy and schedule
        $post_status = 'publish';
        if ($schedule === 'schedule' && !empty($schedule_date)) {
            // Will be handled by post date
            $post_status = 'future';
        } elseif ($privacy === 'private') {
            $post_status = 'private';
        }
        
        // Create post
        $post_data = array(
            'post_title' => wp_trim_words($description, 10, '...') ?: 'Video ' . date('Y-m-d H:i:s'),
            'post_content' => '',
            'post_status' => $post_status,
            'post_author' => get_current_user_id(),
            'post_type' => 'post',
        );
        
        // Set scheduled date if needed
        if ($schedule === 'schedule' && !empty($schedule_date)) {
            $post_data['post_date'] = $schedule_date;
            $post_data['post_date_gmt'] = get_gmt_from_date($schedule_date);
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_delete_attachment($attachment_id, true);
            if ($cover_image_id) {
                wp_delete_attachment($cover_image_id, true);
            }
            wp_send_json_error(array('message' => 'Không thể tạo post: ' . $post_id->get_error_message()));
        }
        
        // Add video block to post content
        $video_url = wp_get_attachment_url($attachment_id);
        $block_content = '<!-- wp:puna/hupuna-tiktok {"videoId":' . $attachment_id . ',"videoUrl":"' . esc_url($video_url) . '"} /-->';
        
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $block_content
        ));
        
        // Save video meta
        update_post_meta($post_id, '_puna_tiktok_video_file_id', $attachment_id);
        update_post_meta($post_id, '_puna_tiktok_video_likes', 0);
        update_post_meta($post_id, '_puna_tiktok_video_views', 0);
        update_post_meta($post_id, '_puna_tiktok_video_shares', 0);
        
        // Save cover image if provided
        if ($cover_image_id) {
            update_post_meta($post_id, '_puna_tiktok_video_cover_id', $cover_image_id);
        }
        
        // Save location if provided
        if ($location) {
            update_post_meta($post_id, '_puna_tiktok_video_location', $location);
        }
        
        // Save privacy
        update_post_meta($post_id, '_puna_tiktok_video_privacy', $privacy);
        
        // Save description as post excerpt
        if ($description) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => $description
            ));
        }
        
        // Get redirect URL
        $redirect_url = get_permalink($post_id);
        if (!$redirect_url) {
            $redirect_url = get_author_posts_url(get_current_user_id());
        }
        
        wp_send_json_success(array(
            'message' => 'Upload video thành công!',
            'post_id' => $post_id,
            'redirect_url' => $redirect_url
        ));
    }
}

// Initialize the class
new Puna_TikTok_AJAX_Handlers();

