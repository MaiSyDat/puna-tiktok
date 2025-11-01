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
}

// Initialize the class
new Puna_TikTok_AJAX_Handlers();

