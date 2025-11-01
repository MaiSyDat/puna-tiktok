<?php

/**
 * Đăng ký các loại bài đăng tùy chỉnh (custom post types)
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Post_Types {
    
    public function __construct() {
        // Deprecated: using Gutenberg block on regular posts instead of CPT
        // add_action('init', array($this, 'register_post_types'));
    }
    
    /**
     * Đăng ký loại bài đăng video kiểu TikTok
     */
    public function register_post_types() {}
}

new Puna_TikTok_Post_Types();

