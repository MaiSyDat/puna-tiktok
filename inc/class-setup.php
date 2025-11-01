<?php

/**
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

// Định nghĩa hằng số theme
if (! defined('PUNA_TIKTOK_VERSION')) {
    define('PUNA_TIKTOK_VERSION', '1.0.0');
}
if (! defined('PUNA_TIKTOK_THEME_DIR')) {
    define('PUNA_TIKTOK_THEME_DIR', get_template_directory());
}
if (! defined('PUNA_TIKTOK_THEME_URI')) {
    define('PUNA_TIKTOK_THEME_URI', get_template_directory_uri());
}

class Puna_TikTok_Setup {
    
    public function __construct() {
        add_action('after_setup_theme', array($this, 'setup'));
        add_action('after_switch_theme', array($this, 'create_required_pages'));
    }
    
    /**
     * Khởi tạo hỗ trợ của theme
     */
    public function setup()
    {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        ));
    
    // Register menu location for sidebar
    register_nav_menus(array(
        'puna-tiktok-sidebar' => __('TikTok Sidebar Menu', 'puna-tiktok'),
    ));
    }

    /**
     * Tạo các trang cần thiết khi kích hoạt theme và gán template
     */
    public function create_required_pages()
    {
    $pages = array(
        array(
            'title'    => 'Explore',
            'slug'     => 'explore',
            'template' => 'template-parts/pages/explore.php',
        ),
        array(
            'title'    => 'Followed',
            'slug'     => 'followed',
            'template' => 'template-parts/pages/followed.php',
        ),
        array(
            'title'    => 'Friends',
            'slug'     => 'friends',
            'template' => 'template-parts/pages/friends.php',
        ),
        array(
            'title'    => 'Messages',
            'slug'     => 'messages',
            'template' => 'template-parts/pages/messages.php',
        ),
        array(
            'title'    => 'Profile',
            'slug'     => 'profile',
            'template' => 'template-parts/pages/profile.php',
        ),
    );

    foreach ($pages as $page) {
        $existing = get_page_by_path($page['slug']);
        if ($existing instanceof WP_Post) {
            // Đảm bảo gán đúng template nếu trang đã tồn tại
            update_post_meta($existing->ID, '_wp_page_template', $page['template']);
            continue;
        }

        $page_id = wp_insert_post(array(
            'post_title'   => $page['title'],
            'post_name'    => $page['slug'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ));

        if (! is_wp_error($page_id) && $page_id) {
            update_post_meta($page_id, '_wp_page_template', $page['template']);
        }
    }

        // Làm mới permalink để hoạt động ngay
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
        }
    }
}

new Puna_TikTok_Setup();

