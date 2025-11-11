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