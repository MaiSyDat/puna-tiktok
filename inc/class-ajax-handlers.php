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
        
        add_action('wp_ajax_puna_tiktok_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_puna_tiktok_register', array($this, 'handle_register'));
        
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
        
        // Increment share count
        add_action('wp_ajax_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        add_action('wp_ajax_nopriv_puna_tiktok_increment_shares', array($this, 'increment_shares'));
        
        // Toggle save video
        add_action('wp_ajax_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        add_action('wp_ajax_nopriv_puna_tiktok_toggle_save', array($this, 'toggle_save'));
        
        add_action('wp_ajax_puna_tiktok_delete_video', array($this, 'delete_video'));
        
        // Get popular hashtags
        add_action('wp_ajax_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        add_action('wp_ajax_nopriv_puna_tiktok_get_popular_hashtags', array($this, 'get_popular_hashtags'));
        
        // Migrate guest data to user account
        add_action('wp_ajax_puna_tiktok_migrate_guest_data', array($this, 'migrate_guest_data'));
    }

    /**
     * Like/Unlike Video - AJAX Handler
     */
    public function toggle_like() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
        wp_send_json_error(array('message' => 'Video không hợp lệ.'));
    }
    
    // Get current likes
    $current_likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
    
    // Check if user is logged in
    $is_logged_in = is_user_logged_in();
    $user_id = $is_logged_in ? get_current_user_id() : 0;
    
    // For logged in users, track in user meta
    if ($is_logged_in) {
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
    } else {
        // For guests, just increment/decrement likes (no tracking)
        // Frontend will handle state via localStorage/cookie
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
     * Add comment via AJAX
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
    
    // Get user info or use guest info
    if ($is_logged_in) {
    $user = get_userdata($user_id);
        $comment_author = $user->display_name;
        $comment_email = $user->user_email;
    } else {
        // Guest comment - tạo ID duy nhất cho guest
        $guest_id = isset($_POST['guest_id']) ? sanitize_text_field($_POST['guest_id']) : '';
        if (empty($guest_id)) {
            // Tạo guest ID từ IP và timestamp
            $guest_id = 'guest_' . md5($_SERVER['REMOTE_ADDR'] . time() . wp_generate_password(8, false));
        }
        
        // Lưu guest ID vào session/cookie để tái sử dụng
        if (!isset($_COOKIE['puna_tiktok_guest_id'])) {
            setcookie('puna_tiktok_guest_id', $guest_id, time() + (365 * 24 * 60 * 60), '/'); // 1 năm
        } else {
            $guest_id = $_COOKIE['puna_tiktok_guest_id'];
        }
        
        $guest_name = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : 'Khách';
        $comment_author = $guest_name . ' #' . substr($guest_id, 6, 8); // Hiển thị tên + 8 ký tự đầu của ID
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
        'comment_parent' => $parent_id, // Set parent for replies
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if (!$comment_id) {
        wp_send_json_error(array('message' => 'Không thể thêm bình luận.'));
    }
    
    // Lưu guest ID vào comment meta nếu là guest
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
     * Increment video view count via AJAX
     */
    public function increment_view() {
    check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!$post_id || get_post_type($post_id) !== 'video') {
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
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        if (!$comment_id) {
            wp_send_json_error(array('message' => 'Thiếu comment_id.'));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Bình luận không tồn tại.'));
        }
        
        // Get current likes
        $current_likes = get_comment_meta($comment_id, '_comment_likes', true) ?: 0;
        
        // Check if user is logged in
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;
        
        if ($is_logged_in) {
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
        } else {
            // For guests, just increment/decrement likes
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
    // Nonce optional for guests
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
    
    // Check if user can delete: admin can delete any, user can delete own, guest can delete own
    $can_delete = false;
    
    if ($is_admin) {
        // Admin can delete any comment
        $can_delete = true;
    } elseif ($user_id > 0 && $comment->user_id == $user_id) {
        // Registered user can delete own comment
        $can_delete = true;
    } elseif ($user_id == 0) {
        // Guest - check if comment belongs to this guest
        $comment_guest_id = get_comment_meta($comment_id, '_puna_tiktok_guest_id', true);
        $current_guest_id = isset($_COOKIE['puna_tiktok_guest_id']) ? sanitize_text_field($_COOKIE['puna_tiktok_guest_id']) : '';
        
        if (!empty($comment_guest_id) && !empty($current_guest_id) && $comment_guest_id === $current_guest_id) {
            $can_delete = true;
        }
    }
    
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
     * Report a comment
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
     * Register new user - AJAX Handler
     */
    public function handle_register() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $birthday_month = isset($_POST['birthday_month']) ? intval($_POST['birthday_month']) : 0;
        $birthday_day = isset($_POST['birthday_day']) ? intval($_POST['birthday_day']) : 0;
        $birthday_year = isset($_POST['birthday_year']) ? intval($_POST['birthday_year']) : 0;
        
        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Vui lòng nhập đầy đủ thông tin.'));
        }
        
        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Email không hợp lệ.'));
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => 'Mật khẩu phải có ít nhất 6 ký tự.'));
        }
        
        // Validate username
        if (strlen($username) < 3) {
            wp_send_json_error(array('message' => 'Tên người dùng phải có ít nhất 3 ký tự.'));
        }
        
        // Check if username already exists
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Tên người dùng đã được sử dụng.'));
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email đã được sử dụng.'));
        }
        
        // Validate birthday (optional but if provided, must be valid)
        $birthday = null;
        if ($birthday_month > 0 && $birthday_day > 0 && $birthday_year > 0) {
            if (!checkdate($birthday_month, $birthday_day, $birthday_year)) {
                wp_send_json_error(array('message' => 'Ngày sinh không hợp lệ.'));
            }
            
            // Check age (must be at least 13 years old)
            $age = date('Y') - $birthday_year;
            if ($age < 13) {
                wp_send_json_error(array('message' => 'Bạn phải ít nhất 13 tuổi để đăng ký.'));
            }
            
            $birthday = sprintf('%04d-%02d-%02d', $birthday_year, $birthday_month, $birthday_day);
        }
        
        // Create user with subscriber role
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        // Set user role to subscriber
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        // Save birthday if provided
        if ($birthday) {
            update_user_meta($user_id, 'birthday', $birthday);
        }
        
        // Auto login after registration
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        wp_send_json_success(array(
            'message' => 'Đăng ký thành công!',
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
        
        // 1. Get matching video titles
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
        
        // Chỉ admin mới được upload video
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
        
        // Duplicate check using link and filename
        $existing_link = get_posts(array(
            'post_type'      => 'video',
            'post_status'    => 'any',
            'meta_key'       => '_puna_tiktok_mega_link',
            'meta_value'     => $mega_link,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ));
        
        // Also check _puna_tiktok_video_url for backward compatibility
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
        
        // Handle cover image if provided (still stored in WP media library)
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
            'post_content' => $clean_description, // Save description as post content
            'post_status'  => $post_status,
            'post_author'  => get_current_user_id(),
            'post_type'    => 'video',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Không thể tạo post: ' . $post_id->get_error_message()));
        }
        
        // For video post type, we don't need block - just save meta
        // Post content is already set in $post_data

        if (!empty($hashtags)) {
            wp_set_post_tags($post_id, $hashtags, false);
        }

        if ($category_id > 0) {
            $term_exists = term_exists($category_id, 'category');
            if ($term_exists && !is_wp_error($term_exists)) {
                wp_set_post_terms($post_id, array((int) $term_exists['term_id']), 'category', false);
            }
        }

        // Save MEGA link and node ID
        update_post_meta($post_id, '_puna_tiktok_mega_link', esc_url_raw($mega_upload['link']));
        update_post_meta($post_id, '_puna_tiktok_mega_node_id', sanitize_text_field($mega_upload['nodeId'] ?? ''));
        // Also save as video_url for backward compatibility
        update_post_meta($post_id, '_puna_tiktok_video_url', esc_url_raw($mega_upload['link']));
        update_post_meta($post_id, '_puna_tiktok_video_node_id', sanitize_text_field($mega_upload['nodeId'] ?? ''));
        
        // Save description to meta
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
     * Increment Share Count - AJAX Handler
     */
    public function increment_shares() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || ! has_block('puna/hupuna-tiktok', $post_id)) {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
        }
        
        // Get current share count
        $current_shares = get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0;
        
        // Increment share count
        $new_shares = intval($current_shares) + 1;
        update_post_meta($post_id, '_puna_tiktok_video_shares', $new_shares);
        
        wp_send_json_success(array(
            'share_count' => $new_shares
        ));
    }
    
    /**
     * Save/Unsave Video - AJAX Handler
     */
    public function toggle_save() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || ! has_block('puna/hupuna-tiktok', $post_id)) {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
        }
        
        // Get current saves count
        $current_saves = get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0;
        
        // Check if user is logged in
        $is_logged_in = is_user_logged_in();
        $user_id = $is_logged_in ? get_current_user_id() : 0;
        
        if ($is_logged_in) {
        // Get user's saved videos
        $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
        if (!is_array($saved_posts)) {
            $saved_posts = array();
        }
        
        // Check if already saved
        $is_saved = in_array($post_id, $saved_posts);
        
        if ($is_saved) {
            // Unsave: remove from array
            $saved_posts = array_diff($saved_posts, array($post_id));
            $saved_posts = array_values($saved_posts); // Re-index array
            update_user_meta($user_id, '_puna_tiktok_saved_videos', $saved_posts);
            
            // Decrease saves count
            $new_saves = max(0, $current_saves - 1);
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            
            wp_send_json_success(array(
                'is_saved' => false,
                'saves' => $new_saves,
                'message' => 'Đã bỏ lưu video'
            ));
        } else {
            // Save: add to array
            $saved_posts[] = $post_id;
            update_user_meta($user_id, '_puna_tiktok_saved_videos', $saved_posts);
            
            // Increase saves count
            $new_saves = $current_saves + 1;
            update_post_meta($post_id, '_puna_tiktok_video_saves', $new_saves);
            
            wp_send_json_success(array(
                'is_saved' => true,
                'saves' => $new_saves,
                'message' => 'Đã lưu video'
            ));
        }
    } else {
        // For guests, just increment/decrement saves (no tracking)
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
     * Delete Video - AJAX Handler
     */
    public function delete_video() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || !has_block('puna/hupuna-tiktok', $post_id)) {
            wp_send_json_error(array('message' => 'Video không hợp lệ.'));
        }
        
        // Check if current user is the author
        $post = get_post($post_id);
        if (!$post || $post->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => 'Bạn không có quyền xóa video này.'));
        }
        
        $video_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
        $video_url     = get_post_meta($post_id, '_puna_tiktok_video_url', true);
        $video_file_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
        $cover_image_id = get_post_meta($post_id, '_puna_tiktok_video_cover_id', true);

        if ($video_file_id && is_numeric($video_file_id)) {
            // Backward compatibility for older posts stored in Media Library
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
     * Get Popular Hashtags - AJAX Handler
     */
    public function get_popular_hashtags() {
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        // Get all tags with post count
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
        
        // If no tags with meta, get by count
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
    
    /**
     * Migrate guest data to user account
     */
    public function migrate_guest_data() {
        check_ajax_referer('puna_tiktok_like_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'));
        }
        
        $user_id = get_current_user_id();
        $liked_videos = isset($_POST['liked_videos']) ? array_map('intval', $_POST['liked_videos']) : array();
        $saved_videos = isset($_POST['saved_videos']) ? array_map('intval', $_POST['saved_videos']) : array();
        $liked_comments = isset($_POST['liked_comments']) ? array_map('intval', $_POST['liked_comments']) : array();
        
        // Migrate liked videos
        if (!empty($liked_videos)) {
            $existing_liked = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
            if (!is_array($existing_liked)) {
                $existing_liked = array();
            }
            // Merge và loại bỏ trùng lặp
            $merged_liked = array_unique(array_merge($existing_liked, $liked_videos));
            update_user_meta($user_id, '_puna_tiktok_liked_videos', array_values($merged_liked));
        }
        
        // Migrate saved videos
        if (!empty($saved_videos)) {
            $existing_saved = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
            if (!is_array($existing_saved)) {
                $existing_saved = array();
            }
            // Merge và loại bỏ trùng lặp
            $merged_saved = array_unique(array_merge($existing_saved, $saved_videos));
            update_user_meta($user_id, '_puna_tiktok_saved_videos', array_values($merged_saved));
        }
        
        // Migrate liked comments
        if (!empty($liked_comments)) {
            $existing_liked_comments = get_user_meta($user_id, '_puna_tiktok_liked_comments', true);
            if (!is_array($existing_liked_comments)) {
                $existing_liked_comments = array();
            }
            // Merge và loại bỏ trùng lặp
            $merged_liked_comments = array_unique(array_merge($existing_liked_comments, $liked_comments));
            update_user_meta($user_id, '_puna_tiktok_liked_comments', array_values($merged_liked_comments));
        }
        
        wp_send_json_success(array(
            'message' => 'Đã chuyển dữ liệu thành công.',
            'migrated' => array(
                'liked_videos' => count($liked_videos),
                'saved_videos' => count($saved_videos),
                'liked_comments' => count($liked_comments)
            )
        ));
    }
}

// Initialize the class
new Puna_TikTok_AJAX_Handlers();



