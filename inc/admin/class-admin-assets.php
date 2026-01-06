<?php

/**
 * Admin Assets
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_Assets
 */
if (!class_exists('Admin_Assets')) {

    class Admin_Assets {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            
            // Allow other code to hook into admin assets initialization
            do_action('puna_tiktok_admin_assets_initialized', $this);
        }

        /**
         * Enqueue admin styles
         */
        public function admin_styles() {
            $version = PUNA_TIKTOK_VERSION;
            
            // Get current screen
            $screen = get_current_screen();
            
            // Allow filtering of screen check
            $should_enqueue = apply_filters('puna_tiktok_should_enqueue_admin_styles', false, $screen);
            
            // Only enqueue on video post type edit screens
            if (!$should_enqueue && (!$screen || $screen->post_type !== 'video' || !in_array($screen->base, array('post', 'post-new')))) {
                return;
            }
            
            // Allow filtering of admin styles
            $admin_styles = apply_filters('puna_tiktok_admin_styles', array(
                'puna-tiktok-video-admin' => array(
                    'src' => get_template_directory_uri() . '/assets/css/admin/video-admin.css',
                    'deps' => array(),
                    'version' => $version,
                ),
            ), $screen);
            
            // Register all admin styles
            foreach ($admin_styles as $handle => $style) {
                if (!wp_style_is($handle, 'registered')) {
                    wp_register_style($handle, $style['src'], $style['deps'], $style['version']);
                }
            }
            
            // Enqueue all admin styles
            foreach ($admin_styles as $handle => $style) {
                wp_enqueue_style($handle);
            }
            
            // Allow other code to hook into admin styles enqueued
            do_action('puna_tiktok_admin_styles_enqueued', $this, $screen);
        }

        /**
         * Enqueue admin scripts
         */
        public function admin_scripts($hook) {
            global $post_type;
            
            $version = PUNA_TIKTOK_VERSION;
            
            // Only enqueue on video post type edit screens
            if ($post_type !== 'video' || !in_array($hook, array('post.php', 'post-new.php'))) {
                return;
            }
            
            // Allow filtering of admin scripts condition
            $should_enqueue = apply_filters('puna_tiktok_should_enqueue_admin_scripts', true, $hook, $post_type);
            
            if (!$should_enqueue) {
                return;
            }
            
            // Allow filtering of admin scripts
            $admin_scripts = apply_filters('puna_tiktok_admin_scripts', array(
                'puna-tiktok-video-admin' => array(
                    'src' => get_template_directory_uri() . '/assets/js/admin/video-admin.js',
                    'deps' => array('jquery'),
                    'version' => $version,
                    'in_footer' => true,
                ),
            ), $hook, $post_type);
            
            // Register all admin scripts
            foreach ($admin_scripts as $handle => $script) {
                if (!wp_script_is($handle, 'registered')) {
                    wp_register_script(
                        $handle,
                        $script['src'],
                        $script['deps'],
                        $script['version'],
                        isset($script['in_footer']) ? $script['in_footer'] : true
                    );
                }
            }
            
            // Enqueue all admin scripts
            foreach ($admin_scripts as $handle => $script) {
                wp_enqueue_script($handle);
            }
            
            // Localize script data
            $localize_data = apply_filters('puna_tiktok_admin_localize_script_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('puna_tiktok_video_upload'),
                'strings' => array(
                    'current_youtube' => __('Current YouTube Video:', 'puna-tiktok'),
                    'video_id' => __('Video ID:', 'puna-tiktok'),
                    'preview' => __('Preview:', 'puna-tiktok'),
                    'video_not_uploaded' => __('Video has not been uploaded. Do you want to continue saving the post? Video will not be displayed until it is uploaded.', 'puna-tiktok'),
                    'youtube_url_invalid' => __('Invalid YouTube URL. Do you want to continue saving the post?', 'puna-tiktok'),
                    'file_information' => __('File Information:', 'puna-tiktok'),
                    'name' => __('Name:', 'puna-tiktok'),
                    'size' => __('Size:', 'puna-tiktok'),
                    'type' => __('Type:', 'puna-tiktok'),
                    'note_upload_on_save' => __('Note: Video will be uploaded when you save the post.', 'puna-tiktok'),
                ),
            ), $hook, $post_type);
            
            wp_localize_script('puna-tiktok-video-admin', 'puna_tiktok_video_admin', $localize_data);
            
            // Allow other code to hook into admin scripts enqueued
            do_action('puna_tiktok_admin_scripts_enqueued', $this, $hook, $post_type);
        }
    }

    // Init class
    new Admin_Assets();
}

