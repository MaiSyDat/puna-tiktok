<?php
/**
 * Sidebar Customizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Customize_Sidebar {

    public function __construct() {
        add_action('customize_register', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'output_dynamic_css'));
        add_action('customize_preview_init', array($this, 'customize_preview_js'));
    }

    /**
     * Register Customizer settings for Sidebar
     */
    public function register_settings($wp_customize) {
        // Check capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add Sidebar Section (top-level, no panel)
        $wp_customize->add_section('puna_sidebar_section', array(
            'title'       => __('Sidebar', 'puna-tiktok'),
            'description' => __('Configure Sidebar (Logo, Menu)', 'puna-tiktok'),
            'priority'    => 25,
            'capability'  => 'manage_options',
        ));

        // Logo Settings
        $wp_customize->add_setting('sidebar_logo', array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ));

        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'sidebar_logo', array(
            'label'    => __('Logo', 'puna-tiktok'),
            'section'  => 'puna_sidebar_section',
            'settings' => 'sidebar_logo',
        )));

        $wp_customize->add_setting('sidebar_logo_link', array(
            'default'           => home_url('/'),
            'sanitize_callback' => 'esc_url_raw',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_logo_link', array(
            'label'    => __('Logo Link', 'puna-tiktok'),
            'section'  => 'puna_sidebar_section',
            'type'     => 'url',
        ));

    }

    /**
     * Live preview JS for logo size/link
     */
    public function customize_preview_js() {
        wp_enqueue_script(
            'puna-tiktok-sidebar-customizer-preview',
            get_template_directory_uri() . '/assets/js/customizer/preview.js',
            array('customize-preview', 'jquery'),
            '1.0.0',
            true
        );
    }

    /**
     * Output dynamic CSS for logo dimensions
     */
    public function output_dynamic_css() {
        // Logo dimensions are now handled by static CSS
        // No dynamic CSS needed
    }

    /**
     * Enqueue sidebar scripts
     */
    public function enqueue_scripts() {
        // Sidebar toggle is now handled by assets/js/frontend/sidebar-toggle.js
        // No inline scripts needed here
    }

    /**
     * Get sidebar menu items
     * Supports WordPress menu system first, falls back to default menu
     */
    public static function get_menu_items() {
        $menu_items = array();

        // Check if custom menu is assigned to sidebar location
        $locations = get_nav_menu_locations();
        if (isset($locations['puna-tiktok-sidebar'])) {
            $menu = wp_get_nav_menu_object($locations['puna-tiktok-sidebar']);
            if ($menu) {
                $menu_items_obj = wp_get_nav_menu_items($menu->term_id);
                foreach ($menu_items_obj as $item) {
                    // Get icon from menu item meta or CSS classes
                    $icon = get_post_meta($item->ID, '_menu_item_icon', true);
                    
                    // Check if icon is in CSS classes (common pattern)
                    if (!$icon && !empty($item->classes)) {
                        foreach ($item->classes as $class) {
                            if (strpos($class, 'fa-') === 0 || strpos($class, 'icon-') === 0) {
                                $icon = $class;
                                break;
                            }
                        }
                    }
                    
                    // Also check description field (some plugins use this)
                    if (!$icon && !empty($item->description)) {
                        $icon = $item->description;
                    }
                    
                    if (!$icon) {
                        // Default icons based on title
                        $icon = self::get_default_icon($item->title);
                    }
                    
                    // Determine active state
                    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    $is_active = false;
                    
                    // Check exact match
                    if ($item->url == $current_url || (is_home() && $item->url == home_url('/'))) {
                        $is_active = true;
                    }
                    
                    // Check if current page is a child of menu item
                    if (!$is_active && $item->type === 'post_type') {
                        global $post;
                        if (isset($post) && $post->ID == $item->object_id) {
                            $is_active = true;
                        }
                    }
                    
                    $menu_items[] = array(
                        'title' => $item->title,
                        'url' => $item->url,
                        'icon' => $icon,
                        'active' => $is_active,
                    );
                }
                return $menu_items;
            }
        }

        // Default menu items
        $menu_items = array(
            array(
                'title' => __('Home', 'puna-tiktok'),
                'url' => home_url('/'),
                'icon' => 'fa-solid fa-house',
                'active' => (is_home() || is_front_page()),
            ),
            array(
                'title' => __('Categories', 'puna-tiktok'),
                'url' => home_url('/category'),
                'icon' => 'fa-solid fa-folder',
                'active' => get_query_var('puna_page') === 'category',
            ),
            array(
                'title' => __('Tags', 'puna-tiktok'),
                'url' => home_url('/tag'),
                'icon' => 'fa-solid fa-hashtag',
                'active' => get_query_var('puna_page') === 'tag',
            ),
        );
        
        return $menu_items;
    }

    /**
     * Get default icon based on menu title
     */
    private static function get_default_icon($title) {
        $icon_map = array(
            'home' => 'fa-solid fa-house',
            'categories' => 'fa-solid fa-folder',
            'category' => 'fa-solid fa-folder',
            'tag' => 'fa-solid fa-hashtag',
        );
        
        $title_lower = strtolower($title);
        foreach ($icon_map as $key => $icon) {
            if (strpos($title_lower, $key) !== false) {
                return $icon;
            }
        }
        
        return 'fa-solid fa-circle';
    }
}

// Initialize
new Puna_TikTok_Customize_Sidebar();

