<?php

/**
 * assets
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Assets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue frontend CSS/JS
     */
    public function enqueue_frontend_assets()
    {
        wp_enqueue_style('puna-tiktok-frontend', PUNA_TIKTOK_THEME_URI . '/assets/css/frontend/frontend.css', array(), PUNA_TIKTOK_VERSION);

        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), '7.0.1');

        wp_enqueue_script(
            'puna-tiktok-mega-sdk',
            PUNA_TIKTOK_THEME_URI . '/assets/js/libs/mega.browser.js',
            array(),
            PUNA_TIKTOK_VERSION,
            true
        );

        wp_enqueue_script(
            'puna-tiktok-mega-uploader',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/mega-uploader.js',
            array('puna-tiktok-mega-sdk'),
            PUNA_TIKTOK_VERSION,
            true
        );

        wp_enqueue_script(
            'puna-tiktok-main',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/main.js',
            array('puna-tiktok-mega-uploader'),
            PUNA_TIKTOK_VERSION,
            true
        );

        $current_user = wp_get_current_user();
        wp_localize_script('puna-tiktok-main', 'puna_tiktok_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puna_tiktok_nonce'),
            'like_nonce' => wp_create_nonce('puna_tiktok_like_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'current_user' => array(
                'display_name' => $current_user->display_name,
                'user_id' => $current_user->ID,
            ),
            'avatar_url' => get_avatar_url($current_user->ID, array('size' => 40)),
            'mega' => array(
                'email'   => Puna_TikTok_Mega_Config::get_email(),
                'password'=> Puna_TikTok_Mega_Config::get_password(),
                'folder'  => Puna_TikTok_Mega_Config::get_upload_folder(),
            ),
        ));
    }

}

new Puna_TikTok_Assets();

