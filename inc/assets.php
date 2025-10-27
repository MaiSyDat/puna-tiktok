<?php

/**
 * Nạp assets frontend và admin
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Assets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Nạp CSS/JS cho frontend
     */
    public function enqueue_frontend_assets()
    {
        wp_enqueue_style('puna-tiktok-frontend', PUNA_TIKTOK_THEME_URI . '/assets/css/frontend/frontend.css', array(), PUNA_TIKTOK_VERSION);

        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), '7.0.1');

        wp_enqueue_script('puna-tiktok-frontend', PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/main.js', array(), PUNA_TIKTOK_VERSION, true);

        $current_user = wp_get_current_user();
        wp_localize_script('puna-tiktok-frontend', 'puna_tiktok_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puna_tiktok_nonce'),
            'like_nonce' => wp_create_nonce('puna_tiktok_like_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'current_user' => array(
                'display_name' => $current_user->display_name,
                'user_id' => $current_user->ID,
            ),
            'avatar_url' => get_avatar_url($current_user->ID, array('size' => 40)),
        ));
    }

    /**
     * Nạp CSS/JS cho admin
     */
    public function enqueue_admin_assets()
    {
        wp_enqueue_style('puna-tiktok-backend', PUNA_TIKTOK_THEME_URI . '/assets/css/backend/backend.css', array(), PUNA_TIKTOK_VERSION);
        
        // JS admin (chỉ nạp nếu tồn tại)
        $admin_js = PUNA_TIKTOK_THEME_DIR . '/assets/js/backend/admin.js';
        if (file_exists($admin_js)) {
            wp_enqueue_script('puna-tiktok-backend', PUNA_TIKTOK_THEME_URI . '/assets/js/backend/admin.js', array('jquery'), PUNA_TIKTOK_VERSION, true);
        }
    }
}

new Puna_TikTok_Assets();

