<?php

/**
 * Nạp assets frontend và admin
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Nạp CSS/JS cho frontend
 */
function puna_tiktok_enqueue_frontend_assets()
{
    wp_enqueue_style('puna-tiktok-frontend', PUNA_TIKTOK_THEME_URI . '/assets/css/frontend/frontend.css', array(), PUNA_TIKTOK_VERSION);

    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), '7.0.1');

    wp_enqueue_script('puna-tiktok-frontend', PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/main.js', array(), PUNA_TIKTOK_VERSION, true);

    wp_localize_script('puna-tiktok-frontend', 'puna_tiktok_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('puna_tiktok_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'puna_tiktok_enqueue_frontend_assets');

/**
 * Nạp CSS/JS cho admin
 */
function puna_tiktok_enqueue_admin_assets()
{
    wp_enqueue_style('puna-tiktok-backend', PUNA_TIKTOK_THEME_URI . '/assets/css/backend/backend.css', array(), PUNA_TIKTOK_VERSION);
    // JS admin (chỉ nạp nếu tồn tại)
    $admin_js = PUNA_TIKTOK_THEME_DIR . '/assets/js/backend/admin.js';
    if (file_exists($admin_js)) {
        wp_enqueue_script('puna-tiktok-backend', PUNA_TIKTOK_THEME_URI . '/assets/js/backend/admin.js', array('jquery'), PUNA_TIKTOK_VERSION, true);
    }
}
add_action('admin_enqueue_scripts', 'puna_tiktok_enqueue_admin_assets');


