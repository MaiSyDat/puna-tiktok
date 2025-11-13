<?php

/**
 * Functions
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

// Load module files
require_once get_template_directory() . '/inc/class-setup.php';
require_once get_template_directory() . '/inc/class-assets.php';
require_once get_template_directory() . '/inc/class-meta-boxes.php';
require_once get_template_directory() . '/inc/class-ajax-handlers.php';
require_once get_template_directory() . '/inc/class-blocks.php';

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
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $query_args = wp_parse_args($args, $defaults);
    
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