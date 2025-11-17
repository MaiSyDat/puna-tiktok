<?php

/**
 * Functions
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

// Mega configuration (credentials live inside the theme)
require_once get_template_directory() . '/inc/class-mega-config.php';

// Load module files
require_once get_template_directory() . '/inc/class-setup.php';
require_once get_template_directory() . '/inc/class-assets.php';
require_once get_template_directory() . '/inc/class-meta-boxes.php';
require_once get_template_directory() . '/inc/class-ajax-handlers.php';
require_once get_template_directory() . '/inc/class-blocks.php';
require_once get_template_directory() . '/inc/class-video-post-type.php';

require_once get_template_directory() . '/inc/class-customizer.php';

/**
 * Increase limit upload
 */
function puna_tiktok_increase_upload_limits() {
    ini_set('upload_max_filesize', '500M');
    ini_set('post_max_size', '500M');
    ini_set('max_execution_time', 300);
    ini_set('max_input_time', 300);
    ini_set('memory_limit', '512M');
}
add_action('init', 'puna_tiktok_increase_upload_limits');

/**
 * Format number (1.2K, 1.5M, etc.)
 */
function puna_tiktok_format_number($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    return $number;
}

/**
 * Check if user liked a video
 */
function puna_tiktok_is_liked($post_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!is_user_logged_in()) {
        return false;
    }
    
    $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
    if (!is_array($liked_posts)) {
        return false;
    }
    
    return in_array($post_id, $liked_posts);
}

/**
 * Get user's liked videos
 */
function puna_tiktok_get_liked_videos($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!is_user_logged_in()) {
        return array();
    }
    
    $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
    if (!is_array($liked_posts) || empty($liked_posts)) {
        return array();
    }
    
    return $liked_posts;
}

/**
 * Check if video is saved by user
 */
function puna_tiktok_is_saved($post_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!is_user_logged_in()) {
        return false;
    }
    
    $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
    if (!is_array($saved_posts)) {
        return false;
    }
    
    return in_array($post_id, $saved_posts);
}

/**
 * Get user's saved videos
 */
function puna_tiktok_get_saved_videos($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!is_user_logged_in()) {
        return array();
    }
    
    $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
    if (!is_array($saved_posts) || empty($saved_posts)) {
        return array();
    }
    
    return $saved_posts;
}

/**
 * Get video metadata (views, likes, shares, saves, video_url)
 * Returns an array with all video stats
 */
function puna_tiktok_get_video_metadata($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return array(
        'post_id' => $post_id,
        'video_url' => puna_tiktok_get_video_url($post_id),
        'views' => (int) get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0,
        'likes' => (int) get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0,
        'shares' => (int) get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0,
        'saves' => (int) get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0,
        'comments' => (int) get_comments_number($post_id) ?: 0
    );
}

/**
 * Get WP_Query for video posts with optional filters
 * 
 * @param array $args {
 *     @type int    $tag_id      Tag ID to filter by
 *     @type int    $author_id   Author ID to filter by
 *     @type array  $post__in    Array of post IDs to include
 *     @type string $orderby     Order by field (default: 'date')
 *     @type string $order       Order direction (default: 'DESC')
 *     @type int    $posts_per_page Number of posts (default: -1 for all)
 * }
 * @return WP_Query
 */
function puna_tiktok_get_video_query($args = array()) {
    $defaults = array(
        'post_type' => 'video',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_puna_tiktok_mega_link',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_puna_tiktok_video_url',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_puna_tiktok_video_file_id',
                'compare' => 'EXISTS'
            )
        )
    );
    
    $query_args = wp_parse_args($args, $defaults);
    
    // If custom meta_query is provided, use it instead of default
    // Otherwise, use default meta_query to filter videos with URLs
    if (!empty($args['meta_query'])) {
        $query_args['meta_query'] = $args['meta_query'];
    }
    
    // Add tag filter if provided
    if (!empty($args['tag_id'])) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $args['tag_id'],
                'operator' => 'IN'
            )
        );
    }
    
    // Add author filter if provided
    if (!empty($args['author_id'])) {
        $query_args['author'] = $args['author_id'];
    }
    
    // Add post__in if provided
    if (!empty($args['post__in'])) {
        $query_args['post__in'] = $args['post__in'];
        if (empty($query_args['orderby']) || $query_args['orderby'] === 'date') {
            $query_args['orderby'] = 'post__in';
        }
    }
    
    return new WP_Query($query_args);
}

/**
 * Get upload page URL
 * 
 * @return string URL to upload page
 */
function puna_tiktok_get_upload_url() {
    return home_url('/upload/');
}

/**
 * Get user display name (consistent across theme)
 * 
 * @param int|null $user_id User ID (default: current post author)
 * @return string User display name
 */
function puna_tiktok_get_user_display_name($user_id = null) {
    if (!$user_id) {
        $user_id = get_the_author_meta('ID');
    }
    
    if (!$user_id) {
        return '';
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }
    
    // Use display_name, fallback to user_nicename
    $display_name = $user->display_name;
    if (empty($display_name)) {
        $display_name = $user->user_nicename;
    }
    
    return $display_name;
}

/**
 * Get user username (consistent across theme)
 * 
 * @param int|null $user_id User ID (default: current post author)
 * @return string User username (user_nicename)
 */
function puna_tiktok_get_user_username($user_id = null) {
    if (!$user_id) {
        $user_id = get_the_author_meta('ID');
    }
    
    if (!$user_id) {
        return '';
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }
    
    return $user->user_nicename;
}

/**
 * Get avatar HTML with fallback to initials for guests
 * 
 * @param int|string $user_id_or_name User ID (for registered users) or name (for guests)
 * @param int $size Avatar size (default: 50)
 * @param string $class Additional CSS class
 * @param string $guest_id Guest ID for generating initials (optional)
 * @return string Avatar HTML
 */
function puna_tiktok_get_avatar_html($user_id_or_name, $size = 50, $class = '', $guest_id = '') {
    $size = (int) $size;
    $class = esc_attr($class);
    
    // If it's a valid user ID (registered user)
    if (is_numeric($user_id_or_name) && $user_id_or_name > 0) {
        $user_id = (int) $user_id_or_name;
        $user = get_userdata($user_id);
        
        if ($user) {
            // Registered user - use avatar (including Gravatar)
            $avatar_url = get_avatar_url($user_id, array('size' => $size));
            $display_name = puna_tiktok_get_user_display_name($user_id);
            return '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($display_name) . '" class="' . $class . '" style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: cover; border-radius: 50%;">';
        }
    }
    
    // For guests (no user ID or invalid user ID), use name to generate initials
    $name = '';
    if (is_string($user_id_or_name)) {
        $name = $user_id_or_name;
    } elseif (is_numeric($user_id_or_name) && $user_id_or_name == 0) {
        $name = 'Guest';
    } else {
        $name = 'Guest';
    }
    
    if (empty($name)) {
        $name = 'Guest';
    }
    
    $initials = puna_tiktok_get_user_initials($name, $guest_id);
    $bg_color = puna_tiktok_get_avatar_color($name . $guest_id);
    
    return '<div class="avatar-initials ' . $class . '" style="width: ' . $size . 'px; height: ' . $size . 'px; background-color: ' . $bg_color . '; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: ' . ($size * 0.4) . 'px;">' . esc_html($initials) . '</div>';
}

/**
 * Get user initials (first character of name + last 2 characters of guest ID if available)
 * 
 * @param string $name User name
 * @param string $guest_id Guest ID (optional, for guests only)
 * @return string Initials
 */
function puna_tiktok_get_user_initials($name, $guest_id = '') {
    $name = trim($name);
    if (empty($name)) {
        return 'GU';
    }
    
    // Get first character of name
    $first_char = mb_substr($name, 0, 1);
    
    // If guest_id is provided, use first char + last 2 chars of ID
    if (!empty($guest_id)) {
        // Extract ID part (remove "guest_" prefix if exists)
        $id_part = str_replace('guest_', '', $guest_id);
        // Get last 2 characters of ID
        $last_two = mb_substr($id_part, -2, 2);
        return mb_strtoupper($first_char . $last_two);
    }
    
    // For registered users or guests without ID, use first and last character of name
    // Remove extra spaces
    $name = preg_replace('/\s+/', ' ', $name);
    $words = explode(' ', $name);
    
    if (count($words) >= 2) {
        // First character of first word and first character of last word
        $first = mb_substr($words[0], 0, 1);
        $last = mb_substr($words[count($words) - 1], 0, 1);
        return mb_strtoupper($first . $last);
    } else {
        // If only one word, use first and last character
        $first = mb_substr($name, 0, 1);
        $last = mb_substr($name, -1, 1);
        return mb_strtoupper($first . $last);
    }
}

/**
 * Get avatar background color based on name (consistent color for same name)
 * 
 * @param string $name User name
 * @return string Hex color
 */
function puna_tiktok_get_avatar_color($name) {
    $colors = array(
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
        '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52BE80',
        '#E74C3C', '#3498DB', '#9B59B6', '#1ABC9C', '#F39C12',
        '#E67E22', '#34495E', '#16A085', '#27AE60', '#2980B9'
    );
    
    $hash = md5($name);
    $index = hexdec(substr($hash, 0, 2)) % count($colors);
    return $colors[$index];
}

/**
 * Render empty state for video lists
 * 
 * @param array $args {
 *     @type string $icon       Icon class (default: 'fa-video')
 *     @type string $title      Title text (default: 'Chưa có video nào')
 *     @type string $message    Message text (default: 'Hãy đăng video đầu tiên của bạn để bắt đầu!')
 *     @type string $button_url Button URL (optional)
 *     @type string $button_text Button text (optional)
 *     @type string $wrapper_class Additional CSS class for wrapper
 * }
 */
function puna_tiktok_empty_state($args = array()) {
    $defaults = array(
        'icon' => 'fa-video',
        'title' => 'Chưa có video nào',
        'message' => 'Hãy đăng video đầu tiên của bạn để bắt đầu!',
        'button_url' => '',
        'button_text' => '',
        'wrapper_class' => ''
    );
    
    $args = wp_parse_args($args, $defaults);
    $wrapper_class = 'explore-empty-state' . (!empty($args['wrapper_class']) ? ' ' . esc_attr($args['wrapper_class']) : '');
    ?>
    <div class="<?php echo esc_attr($wrapper_class); ?>" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
        <i class="fa-solid <?php echo esc_attr($args['icon']); ?>" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
        <h3 style="color: #666; margin-bottom: 10px;"><?php echo esc_html($args['title']); ?></h3>
        <p style="color: #999;"><?php echo esc_html($args['message']); ?></p>
        <?php if (!empty($args['button_url']) && !empty($args['button_text']) && current_user_can('manage_options')) : ?>
            <a href="<?php echo esc_url($args['button_url']); ?>" class="btn-primary" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #fe2c55; color: #fff; text-decoration: none; border-radius: 4px;">
                <?php echo esc_html($args['button_text']); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}
