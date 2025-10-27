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
        add_action('init', array($this, 'register_post_types'));
    }
    
    /**
     * Đăng ký loại bài đăng video kiểu TikTok
     */
    public function register_post_types()
    {
        register_post_type('puna_tiktok_video', array(
        'labels' => array(
            'name'               => __('Videos', 'puna-tiktok'),
            'singular_name'      => __('Video', 'puna-tiktok'),
            'menu_name'          => __('Videos', 'puna-tiktok'),
            'add_new'            => __('Thêm Video Mới', 'puna-tiktok'),
            'add_new_item'       => __('Thêm Video Mới', 'puna-tiktok'),
            'edit_item'          => __('Chỉnh sửa Video', 'puna-tiktok'),
            'new_item'           => __('Video Mới', 'puna-tiktok'),
            'view_item'          => __('Xem Video', 'puna-tiktok'),
            'search_items'       => __('Tìm kiếm Videos', 'puna-tiktok'),
            'not_found'          => __('Không tìm thấy video nào', 'puna-tiktok'),
            'not_found_in_trash' => __('Không tìm thấy video nào trong thùng rác', 'puna-tiktok'),
        ),
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'video'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-video-alt3',
        'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields', 'comments'),
        'show_in_rest'       => true,
        ));
    }
}

new Puna_TikTok_Post_Types();