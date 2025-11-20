<?php

/**
 * Functions
 */

if (! defined('ABSPATH')) {
    exit;
}

// Core functions (must be loaded first)
require_once get_template_directory() . '/inc/core-functions.php';

// Theme classes
require_once get_template_directory() . '/inc/class-mega-config.php';
require_once get_template_directory() . '/inc/class-setup.php';
require_once get_template_directory() . '/inc/class-assets.php';
require_once get_template_directory() . '/inc/class-ajax-handlers.php';
require_once get_template_directory() . '/inc/class-video-post-type.php';
require_once get_template_directory() . '/inc/class-customizer.php';

// Admin classes
require_once get_template_directory() . '/inc/admin/class-admin-assets.php';
require_once get_template_directory() . '/inc/admin/class-admin-ajax.php';

/**
 * Increase upload limits - only when needed
 */
if (!function_exists('puna_tiktok_increase_upload_limits')) {
    function puna_tiktok_increase_upload_limits() {
        // Only increase limits on admin video post type
        $is_admin_upload = is_admin() && (isset($_GET['post_type']) && $_GET['post_type'] === 'video');
        
        // Allow filtering of admin upload check
        $is_admin_upload = apply_filters('puna_tiktok_should_increase_upload_limits', $is_admin_upload);
        
        if ($is_admin_upload) {
            // Try to increase, but don't fail if server doesn't allow
            @ini_set('upload_max_filesize', '500M');
            @ini_set('post_max_size', '500M');
            @ini_set('max_execution_time', 300);
            @ini_set('max_input_time', 300);
            
            // Only increase memory if current limit is lower (use WordPress function if available)
            if (function_exists('wp_raise_memory_limit')) {
                wp_raise_memory_limit('admin');
            } else {
                $current_memory = ini_get('memory_limit');
                $current_memory_bytes = wp_convert_hr_to_bytes($current_memory);
                $target_memory_bytes = wp_convert_hr_to_bytes('512M');
                
                if ($current_memory_bytes < $target_memory_bytes) {
                    @ini_set('memory_limit', '512M');
                }
            }
            
            do_action('puna_tiktok_upload_limits_increased');
        }
    }
}
add_action('init', 'puna_tiktok_increase_upload_limits');

