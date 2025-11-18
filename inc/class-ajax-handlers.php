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
        
        // Populer searcher
        add_action('wp_ajax_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_searches', array($this, 'get_popular_searches'));
        
        // Get related searches
        add_action('wp_ajax_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_related_searches', array($this, 'get_related_searches'));
        
        // Upload video
        add_action('wp_ajax_puna_tiktok_upload_video', array($this, 'upload_video'));
        
        // increment shares
        add_action('wp_ajax_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        add_action('wp_ajax_nopriv_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        
        // Toggle save
        add_action('wp_ajax_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        
        // Delete video
        add_action('wp_ajax_puna_tiktok_delete_video', array($this, 'delete_video'));
        
        // Popular hashtags
        add_action('wp_ajax_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
    }

    /**
     * Toggle like video
     */
    public function toggle_like() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    
    $current_likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
    $is_logged_in = is_user_logged_in();
    $user_id = $is_logged_in ? get_current_user_id() : 0;
    
    if ($is_logged_in) {
    $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
    if (!is_array($liked_posts)) {
        $liked_posts = array();
    }
    
    $is_liked = in_array($post_id, $liked_posts);
    
    if ($is_liked) {
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
    } else {
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'like';
        
        if ($action === 'unlike') {
            $new_likes = max(0, $current_likes - 1);
            update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => false,
                'likes' => $new_likes,
                'message' => 'Đã bỏ thích video'
            ));
        } else {
            $new_likes = $current_likes + 1;
            update_post_meta($post_id, '_puna_tiktok_video_likes', $new_likes);
            wp_send_json_success(array(
                'is_liked' => true,
                'likes' => $new_likes,
                'message' => 'Đã thích video'
            ));
        }
    }
}

    /**
     * Add comment
     */
    public function add_comment() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $comment_text = sanitize_text_field($_POST['comment_text']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    
    if (empty($comment_text)) {
        wp_send_json_error(array('message' => 'Bình luận không được để trống.'));
    }
    
    $is_logged_in = is_user_logged_in();
    $user_id = $is_logged_in ? get_current_user_id() : 0;
    
    if ($is_logged_in) {
    $user = get_userdata($user_id);
        $comment_author = $user->display_name;
        $comment_email = $user->user_email;
    } else {
        $guest_id = isset($_POST['guest_id']) ? sanitize_text_field($_POST['guest_id']) : '';
        if (empty($guest_id)) {
            $guest_id = 'guest_' . md5($_SERVER['REMOTE_ADDR'] . time() . wp_generate_password(8, false));
        }
        
        if (!isset($_COOKIE['puna_tiktok_guest_id'])) {
            setcookie('puna_tiktok_guest_id', $guest_id, time() + (365 * 24 * 60 * 60), '/');
        } else {
            $guest_id = $_COOKIE['puna_tiktok_guest_id'];
        }
        
        $guest_name = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : 'Khách';
        $comment_author = $guest_name . ' #' . substr($guest_id, 6, 8);
        $comment_email = isset($_POST['guest_email']) ? sanitize_email($_POST['guest_email']) : '';
        if (empty($comment_email)) {
            $comment_email = $guest_id . '@guest.local';
        }
    }
    
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_author' => $comment_author,
        'comment_author_email' => $comment_email,
        'comment_content' => $comment_text,
        'comment_status' => 'approve',
        'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
        'user_id' => $user_id,
        'comment_parent' => $parent_id,
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Không thể thêm bình luận.'));
    }
    
    if (!$is_logged_in && !empty($guest_id)) {
        update_comment_meta($comment_id, '_puna_tiktok_guest_id', $guest_id);
    }
    
    wp_send_json_success(array(
        'message' => 'Bình luận đã được thêm.',
        'comment_id' => $comment_id,
        'guest_id' => isset($guest_id) ? $guest_id : ''
    ));
}

    /**
     * Increment video view
     */
    public function increment_view() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    $new_views = puna_tiktok_increment_video_views($post_id);
    wp_send_json_success(array(
        'views' => $new_views,
        'formatted_views' => puna_tiktok_format_number($new_views)
    ));
    }

    /**
     * Toggle comment like
     */
    public function toggle_comment_like() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => 'Thiếu comment_id.'));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Bình luận không tồn tại.'));
        }
        
        $current_likes = get_comment_meta($comment_id, '_comment_likes', true) ?: 0;
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;
        
        if ($is_logged_in) {
        $liked_comments = get_user_meta($user_id, '_puna_tiktok_liked_comments', true);
        if (!is_array($liked_comments)) {
            $liked_comments = array();
        }
        
        $is_liked = in_array($comment_id, $liked_comments);
        
        if ($is_liked) {
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
        } else {
            $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'like';
            
            if ($action === 'unlike') {
                $new_likes = max(0, $current_likes - 1);
                update_comment_meta($comment_id, '_comment_likes', $new_likes);
                wp_send_json_success(array(
                    'is_liked' => false,
                    'likes' => $new_likes,
                    'message' => 'Đã bỏ thích bình luận'
                ));
            } else {
                $new_likes = $current_likes + 1;
                update_comment_meta($comment_id, '_comment_likes', $new_likes);
                wp_send_json_success(array(
                    'is_liked' => true,
                    'likes' => $new_likes,
                    'message' => 'Đã thích bình luận'
                ));
            }
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
    if (is_user_logged_in()) {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
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
    $is_admin = current_user_can('moderate_comments');
    $can_delete = false;
    
    if ($is_admin) {
        $can_delete = true;
    } elseif ($user_id > 0 && $comment->user_id == $user_id) {
        $can_delete = true;
    } elseif ($user_id == 0) {
        $comment_guest_id = get_comment_meta($comment_id, '_puna_tiktok_guest_id', true);
        $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
        
        if (!empty($comment_guest_id) && !empty($current_guest_id) && $comment_guest_id === $current_guest_id) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete) {
        wp_send_json_error(array('message' => 'Bạn không có quyền xóa bình luận này.'));
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
        'message' => 'Đã xóa bình luận.',
        'deleted_count' => $total_to_delete
    ));
    }

    /**
     * Report comment
     */
    public function report_comment() {
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
     * Get search suggestions
     */
    public function get_search_suggestions() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
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
                $suggestions[] = array(
                    'text' => $post->post_title,
                    'type' => 'video'
                );
            }
        }
        
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
     */
    public function save_search_history() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
            return;
        }
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($query)) {
            wp_send_json_error(array('message' => 'Query không được để trống.'));
        }
        
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $identifier = $user_id > 0 ? 'user_' . $user_id : 'ip_' . $_SERVER['REMOTE_ADDR'];
        
        $option_key = 'puna_tiktok_search_history';
        $all_history = get_option($option_key, array());
        
        if (!is_array($all_history)) {
            $all_history = array();
        }
        
        if (!isset($all_history[$identifier])) {
            $all_history[$identifier] = array();
        }
        
        $all_history[$identifier] = array_filter($all_history[$identifier], function($item) use ($query) {
            return $item['query'] !== $query;
        });
        
        array_unshift($all_history[$identifier], array(
            'query' => $query,
            'timestamp' => current_time('timestamp')
        ));
        
        $all_history[$identifier] = array_slice($all_history[$identifier], 0, 50);
        
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        foreach ($all_history[$identifier] as $key => $item) {
            if ($item['timestamp'] < $week_ago) {
                unset($all_history[$identifier][$key]);
            }
        }
        $all_history[$identifier] = array_values($all_history[$identifier]);
        
        update_option($option_key, $all_history);
        
        wp_send_json_success(array('message' => 'Đã lưu lịch sử tìm kiếm.'));
    }

    /**
     * Get search history
     */
    public function get_search_history() {
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
        
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        $history = array_filter($all_history[$identifier], function($item) use ($week_ago) {
            return $item['timestamp'] >= $week_ago;
        });
        
        $history = array_values($history);
        $history = array_slice($history, 0, 10);
        
        wp_send_json_success(array('history' => $history));
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
     * Get popular searches
     */
    public function get_popular_searches() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
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
        
        $related_searches = array();
        $week_ago = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        $current_query_lower = mb_strtolower($current_query);
        $current_words = array_filter(explode(' ', $current_query_lower));
        
        foreach ($all_history as $identifier => $history) {
            if (is_array($history)) {
                foreach ($history as $item) {
                    if ($item['timestamp'] >= $week_ago && !empty($item['query'])) {
                        $query = trim($item['query']);
                        $query_lower = mb_strtolower($query);
                        
                        if ($query_lower === $current_query_lower) {
                            continue;
                        }
                        
                        $similarity = 0;
                        $query_words = array_filter(explode(' ', $query_lower));
                        
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
                        
                        if (strpos($query_lower, $current_query_lower) !== false) {
                            $similarity += 5;
                        }
                        if (strpos($current_query_lower, $query_lower) !== false) {
                            $similarity += 3;
                        }
                        
                        if ($similarity > 0) {
                            if (!isset($related_searches[$query])) {
                                $related_searches[$query] = array(
                                    'query' => $query,
                                    'score' => $similarity,
                                    'count' => 0
                                );
                            }
                            $related_searches[$query]['count']++;
                            $related_searches[$query]['score'] += $related_searches[$query]['count'];
                        }
                    }
                }
            }
        }
        
        usort($related_searches, function($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return $b['count'] - $a['count'];
        });
        
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
     * Upload video
     */
    public function upload_video() {
        check_ajax_referer('puna_tiktok_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Chỉ quản trị viên mới được đăng video.'));
        }
        
        $mega_link = isset($_POST['mega_link']) ? esc_url_raw(wp_unslash($_POST['mega_link'])) : '';
        $mega_node_id = isset($_POST['mega_node_id']) ? sanitize_text_field(wp_unslash($_POST['mega_node_id'])) : '';
        $video_name = isset($_POST['video_name']) ? sanitize_file_name(wp_unslash($_POST['video_name'])) : '';
        $video_size = isset($_POST['video_size']) ? absint($_POST['video_size']) : 0;
        
        if (empty($mega_link) || empty($mega_node_id) || empty($video_name)) {
            wp_send_json_error(array('message' => 'Thiếu thông tin video từ Mega.nz.'));
        }
        
        $existing_link = get_posts(array(
            'post_type'      => 'video',
            'post_status'    => 'any',
            'meta_key'       => '_puna_tiktok_mega_link',
            'meta_value'     => $mega_link,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ));
        
        if (empty($existing_link)) {
            $existing_link = get_posts(array(
                'post_type'      => 'video',
                'post_status'    => 'any',
                'meta_key'       => '_puna_tiktok_video_url',
                'meta_value'     => $mega_link,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ));
        }

        if (!empty($existing_link)) {
            wp_send_json_error(array('message' => 'Video này đã tồn tại trong hệ thống.'));
        }
        
        $mega_upload = array(
            'link'   => $mega_link,
            'nodeId' => $mega_node_id,
            'name'   => $video_name,
            'size'   => $video_size,
        );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $cover_image_id = null;
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $cover_file = $_FILES['cover_image'];
            $cover_upload = wp_handle_upload($cover_file, array('test_form' => false));
            
            if (!isset($cover_upload['error'])) {
                $cover_attachment_data = array(
                    'post_mime_type' => $cover_upload['type'],
                    'post_title'     => sanitize_file_name(pathinfo($cover_file['name'], PATHINFO_FILENAME)),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                
                $cover_image_id = wp_insert_attachment($cover_attachment_data, $cover_upload['file']);
                
                if (!is_wp_error($cover_image_id)) {
                    $cover_attach_data = wp_generate_attachment_metadata($cover_image_id, $cover_upload['file']);
                    wp_update_attachment_metadata($cover_image_id, $cover_attach_data);
                } else {
                    $cover_image_id = null;
                }
            }
        }

        $description   = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $category_id   = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        $hashtags = array();
        $clean_description = $description;
        if (!empty($description)) {
            preg_match_all('/#([\p{L}\p{N}_]+)/u', $description, $matches);
            if (!empty($matches[1])) {
                $found_hashtags = $matches[1];
                foreach ($found_hashtags as $tag) {
                    $tag_lower = mb_strtolower($tag, 'UTF-8');
                    if (!in_array($tag_lower, $hashtags, true)) {
                        $hashtags[] = $tag_lower;
                    }
                }

                foreach ($found_hashtags as $hashtag) {
                    $pattern = '/#' . preg_quote($hashtag, '/') . '(?=\s|$|[^\p{L}\p{N}_])/iu';
                    $clean_description = preg_replace($pattern, '', $clean_description);
                }

                $clean_description = preg_replace('/\s+/', ' ', trim($clean_description));
            }
        }

        $post_status = 'publish';
        
        $post_data = array(
            'post_title'   => wp_trim_words($clean_description, 10, '...') ?: 'Video ' . date('Y-m-d H:i:s'),
            'post_content' => $clean_description,
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id(),
            'post_type'    => 'video',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Không thể tạo post: ' . $post_id->get_error_message()));
        }

        if (!empty($hashtags)) {
            wp_set_post_tags($post_id, $hashtags, false);
        }

        if ($category_id > 0) {
            $term_exists = term_exists($category_id, 'category');
            if ($term_exists && !is_wp_error($term_exists)) {
                wp_set_post_terms($post_id, array((int) $term_exists['term_id']), 'category', false);
            }
        }

        update_post_meta($post_id, '_puna_tiktok_mega_link', esc_url_raw($mega_upload['link']));
        update_post_meta($post_id, '_puna_tiktok_mega_node_id', sanitize_text_field($mega_upload['nodeId'] ?? ''));
        update_post_meta($post_id, '_puna_tiktok_video_url', esc_url_raw($mega_upload['link']));
        update_post_meta($post_id, '_puna_tiktok_video_node_id', sanitize_text_field($mega_upload['nodeId'] ?? ''));
        
        if (!empty($clean_description)) {
            update_post_meta($post_id, '_puna_tiktok_video_description', $clean_description);
        }
        update_post_meta($post_id, '_puna_tiktok_video_likes', 0);
        update_post_meta($post_id, '_puna_tiktok_video_views', 0);
        update_post_meta($post_id, '_puna_tiktok_video_shares', 0);
        update_post_meta($post_id, '_puna_tiktok_video_saves', 0);
        
        if ($cover_image_id) {
            update_post_meta($post_id, '_puna_tiktok_video_cover_id', $cover_image_id);
        }
        
        $redirect_url = get_permalink($post_id);
        if (!$redirect_url) {
            $redirect_url = get_author_posts_url(get_current_user_id());
        }
        
        wp_send_json_success(array(
            'message'      => 'Upload video thành công!',
            'post_id'      => $post_id,
            'redirect_url' => $redirect_url,
            'mega_link'    => esc_url_raw($mega_upload['link']),
        ));
    }
    
    /**
     * Increment share count
     */
    public function increment_shares() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
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
     */
    public function toggle_save() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
        }
        
        $current_saves = get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0;
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;
        
        if ($is_logged_in) {
        $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
        if (!is_array($saved_posts)) {
            $saved_posts = array();
        }
        
        $is_saved = in_array($post_id, $saved_posts);
        
        if ($is_saved) {
            $saved_posts = array_diff($saved_posts, array($post_id));
            $saved_posts = array_values($saved_posts);
            update_user_meta($user_id, '_puna_tiktok_saved_videos', $saved_posts);
            
            $new_saves = max(0, $current_saves - 1);
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            
            wp_send_json_success(array(
                'is_saved' => false,
                'saves' => $new_saves,
                'message' => 'Đã bỏ lưu video'
            ));
        } else {
            $saved_posts[] = $post_id;
            update_user_meta($user_id, '_puna_tiktok_saved_videos', $saved_posts);
            
            $new_saves = $current_saves + 1;
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            
            wp_send_json_success(array(
                'is_saved' => true,
                'saves' => $new_saves,
                'message' => 'Đã lưu video'
            ));
        }
    } else {
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'save';
        
        if ($action === 'unsave') {
            $new_saves = max(0, $current_saves - 1);
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            wp_send_json_success(array(
                'is_saved' => false,
                'saves' => $new_saves,
                'message' => 'Đã bỏ lưu video'
            ));
        } else {
            $new_saves = $current_saves + 1;
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            wp_send_json_success(array(
                'is_saved' => true,
                'saves' => $new_saves,
                'message' => 'Đã lưu video'
            ));
        }
    }
}
    


    
    /**
     * Delete video
     */
    public function delete_video() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Bạn không có quyền xóa video này.'));
        }
        
        $video_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
        $video_url     = get_post_meta($post_id, '_puna_tiktok_video_url', true);
        $video_file_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
        $cover_image_id = get_post_meta($post_id, '_puna_tiktok_video_cover_id', true);

        if ($video_file_id && is_numeric($video_file_id)) {
            wp_delete_attachment($video_file_id, true);
        }

        $deleted = wp_delete_post($post_id, true);
        
        if (!$deleted) {
            wp_send_json_error(array('message' => 'Không thể xóa video. Vui lòng thử lại.'));
        }
        
        if ($cover_image_id && is_numeric($cover_image_id)) {
            wp_delete_attachment($cover_image_id, true);
        }
        
        wp_send_json_success(array(
            'message' => 'Đã xóa video thành công.',
            'redirect_url' => home_url('/')
        ));
    }


    
    /**
     * Get popular hashtags
     */
    public function get_popular_hashtags() {
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        $tags = get_terms(array(
            'taxonomy' => 'post_tag',
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
                'taxonomy' => 'post_tag',
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
                    'url' => get_tag_link($tag->term_id)
                );
            }
        }
        
        wp_send_json_success(array(
            'hashtags' => $hashtags
        ));
    }
    
}

// Initialize the class
new Puna_TikTok_AJAX_Handlers();



