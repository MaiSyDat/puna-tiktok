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
        
        // Delete video
        add_action('wp_ajax_puna_tiktok_delete_video', array($this, 'delete_video'));
        
        // Popular hashtags
        add_action('wp_ajax_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        
        // Get taxonomy videos (category & tag)
        add_action('wp_ajax_puna_tiktok_get_taxonomy_videos', array($this, 'get_taxonomy_videos'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_taxonomy_videos', array($this, 'get_taxonomy_videos'));
        
        // Get reply input HTML
        add_action('wp_ajax_puna_tiktok_get_reply_input', array($this, 'get_reply_input'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_reply_input', array($this, 'get_reply_input'));
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
    
    // Get the comment object
    $comment = get_comment($comment_id);
    if (!$comment) {
        wp_send_json_error(array('message' => 'Không thể lấy thông tin bình luận.'));
    }
    
    // Refresh comment object to ensure all data is up to date
    $comment = get_comment($comment_id);
    
    // Get liked comments for current user - same logic as in comments.php
    $liked_comments = array();
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $liked_comments = get_user_meta($current_user_id, '_puna_tiktok_liked_comments', true);
        if (!is_array($liked_comments)) {
            $liked_comments = array();
        }
    }
    
    // Render HTML using template part - same as in comments.php
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
    
    // Normalize HTML to match page load output
    // Only remove excessive whitespace, preserve structure
    // Remove whitespace between tags (but keep text content spacing)
    $html = preg_replace('/>\s*\n\s*</', '><', $html);
    // Normalize multiple spaces to single space (but not in text content)
    $html = preg_replace('/\s{2,}/', ' ', $html);
    $html = trim($html);
    
    // Ensure HTML is not empty
    if (empty($html) || trim($html) === '') {
        // Fallback: create basic HTML structure
        $comment_author_id = $comment->user_id ? $comment->user_id : 0;
        $comment_author_name = $comment_author_id > 0 ? puna_tiktok_get_user_display_name($comment_author_id) : $comment->comment_author;
        $comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));
        $comment_likes = get_comment_meta($comment->comment_ID, '_comment_likes', true) ?: 0;
        
        $is_reply_class = $parent_id > 0 ? ' comment-reply' : '';
        $html = sprintf(
            '<div class="comment-item%s" data-comment-id="%d">
                <div class="comment-avatar-wrapper">%s</div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author-wrapper">
                            <strong class="comment-author">%s</strong>
                        </span>
                    </div>
                    <p class="comment-text">%s</p>
                    <div class="comment-footer">
                        <span class="comment-date">%s trước</span>
                        <a href="#" class="reply-link" data-comment-id="%d">Trả lời</a>
                    </div>
                </div>
                <div class="comment-right-actions">
                    <div class="comment-likes" data-comment-id="%d">
                        <i class="fa-regular fa-heart"></i>
                        <span>%d</span>
                    </div>
                </div>
            </div>',
            $is_reply_class,
            $comment->comment_ID,
            puna_tiktok_get_avatar_html($comment_author_id > 0 ? $comment_author_id : $comment->comment_author, 40, 'comment-avatar', ''),
            esc_html($comment_author_name),
            wp_kses_post($comment->comment_content),
            esc_html($comment_date),
            $comment->comment_ID,
            $comment->comment_ID,
            $comment_likes
        );
    }
    
    wp_send_json_success(array(
        'message' => 'Bình luận đã được thêm.',
        'comment_id' => $comment_id,
        'guest_id' => isset($guest_id) ? $guest_id : '',
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
        // Check nonce for logged in users only
        if (is_user_logged_in()) {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
                wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
                return;
            }
        }
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => 'Thiếu comment_id.'));
            return;
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Bình luận không tồn tại.'));
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
            wp_send_json_error(array('message' => 'Bạn không có quyền xóa bình luận này.'));
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
    
    /** Get taxonomy videos (category & tag) */
    public function get_taxonomy_videos() {
        if (isset($_POST['nonce'])) {
            if (!wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
                wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
                return;
            }
        }
        
        $tab_type = isset($_POST['tab_type']) ? sanitize_text_field($_POST['tab_type']) : 'trending';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
        
        $args = array(
            'post_type' => 'video',
            'post_status' => 'publish',
            'posts_per_page' => 50,
        );
        
        if ($tab_type === 'category' && $category_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'video_category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            );
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ($tab_type === 'tag' && $tag_id > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'video_tag',
                    'field' => 'term_id',
                    'terms' => $tag_id,
                ),
            );
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ($tab_type === 'trending') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = '_puna_tiktok_video_views';
            $args['order'] = 'DESC';
            $args['date_query'] = array(
                array(
                    'after' => '7 days ago',
                ),
            );
            $args['meta_query'] = array(
                array(
                    'key' => '_puna_tiktok_video_views',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_puna_tiktok_video_views',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC'
                )
            );
        } elseif ($tab_type === 'foryou') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts() && $tab_type === 'trending') {
            $args = array(
                'post_type' => 'video',
                'post_status' => 'publish',
                'posts_per_page' => 50,
                'orderby' => 'date',
                'order' => 'DESC',
            );
            $query = new WP_Query($args);
        }
        
        $videos = array();
        if ($query->have_posts()) {
            $posts_with_views = array();
            while ($query->have_posts()) {
                $query->the_post();
                $metadata = puna_tiktok_get_video_metadata();
                if (empty($metadata['video_url'])) { continue; }
                
                $posts_with_views[] = array(
                    'post_id' => get_the_ID(),
                    'views' => $metadata['views'],
                    'video_url' => $metadata['video_url']
                );
            }
            wp_reset_postdata();
            
            if ($tab_type === 'trending') {
                usort($posts_with_views, function($a, $b) {
                    return $b['views'] - $a['views'];
                });
            }
            
            foreach ($posts_with_views as $post_data) {
                $videos[] = array(
                    'post_id' => $post_data['post_id'],
                    'video_url' => $post_data['video_url'],
                    'views' => $post_data['views'],
                    'permalink' => get_permalink($post_data['post_id']),
                );
            }
        }
        
        wp_send_json_success(array(
            'videos' => $videos,
            'count' => count($videos)
        ));
    }
    
    /**
     * Get reply input HTML
     */
    public function get_reply_input() {
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_like_nonce')) {
            wp_send_json_error(array('message' => 'Nonce không hợp lệ.'));
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
                wp_send_json_error(array('message' => 'Thiếu thông tin video.'));
                return;
            }
        }
        
        // Validate parent_id
        if (!$parent_id || $parent_id <= 0) {
            wp_send_json_error(array('message' => 'Thiếu thông tin bình luận gốc.'));
            return;
        }
        
        // Validate post type
        if (get_post_type($post_id) !== 'video') {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
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

