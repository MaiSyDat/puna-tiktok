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
            'description' => __('Cấu hình Sidebar (Logo, Menu)', 'puna-tiktok'),
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

        $wp_customize->add_setting('sidebar_logo_width', array(
            'default'           => 118,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_logo_width', array(
            'label'    => __('Logo Width (px)', 'puna-tiktok'),
            'section'  => 'puna_sidebar_section',
            'type'     => 'number',
        ));

        $wp_customize->add_setting('sidebar_logo_height', array(
            'default'           => 42,
            'sanitize_callback' => 'absint',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_logo_height', array(
            'label'    => __('Logo Height (px)', 'puna-tiktok'),
            'section'  => 'puna_sidebar_section',
            'type'     => 'number',
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
        $logo_w = absint(get_theme_mod('sidebar_logo_width', 220));
        $logo_h = absint(get_theme_mod('sidebar_logo_height', 0)); // 0 = auto
        ?>
        <style id="puna-tiktok-sidebar-dynamic-css">
            .sidebar .logo img {
                max-width: <?php echo $logo_w ? $logo_w : 220; ?>px;
                height: <?php echo $logo_h ? $logo_h . 'px' : 'auto'; ?>;
            }
            .sidebar.collapsed .logo img {
                max-width: 40px;
                height: auto;
            }
        </style>
        <?php
    }

    /**
     * Enqueue sidebar scripts
     */
    public function enqueue_scripts() {
        // Script for mobile toggle and responsive behavior
        if (wp_script_is('puna-tiktok-main', 'enqueued')) {
            wp_add_inline_script('puna-tiktok-main', '
                (function() {
                    // Sidebar toggle for mobile
                    const sidebarToggle = document.createElement("button");
                    sidebarToggle.className = "sidebar-toggle-btn";
                    sidebarToggle.innerHTML = "<i class=\"fa-solid fa-bars\"></i>";
                    sidebarToggle.setAttribute("aria-label", "Toggle Sidebar");
                    document.body.appendChild(sidebarToggle);
                    
                    // Toggle sidebar
                    sidebarToggle.addEventListener("click", function() {
                        const sidebar = document.querySelector(".sidebar");
                        if (sidebar) {
                            sidebar.classList.toggle("collapsed");
                            document.body.classList.toggle("sidebar-open");
                        }
                    });
                    
                    // Close sidebar when clicking outside on mobile
                    document.addEventListener("click", function(e) {
                        const sidebar = document.querySelector(".sidebar");
                        if (sidebar && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            if (window.innerWidth < 768 && sidebar.classList.contains("collapsed")) {
                                sidebar.classList.remove("collapsed");
                                document.body.classList.remove("sidebar-open");
                            }
                        }
                    });
                    
                    // Responsive check
                    function checkResponsive() {
                        if (window.innerWidth < 768) {
                            sidebarToggle.style.display = "block";
                            const sidebar = document.querySelector(".sidebar");
                            if (sidebar) {
                                sidebar.classList.add("mobile-sidebar");
                            }
                        } else {
                            sidebarToggle.style.display = "none";
                            const sidebar = document.querySelector(".sidebar");
                            if (sidebar) {
                                sidebar.classList.remove("mobile-sidebar", "collapsed");
                            }
                            document.body.classList.remove("sidebar-open");
                        }
                    }
                    
                    window.addEventListener("resize", checkResponsive);
                    checkResponsive();
                })();
            ');
        }
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
                'title' => 'Trang chủ',
                'url' => home_url('/'),
                'icon' => 'fa-solid fa-house',
                'active' => (is_home() || is_front_page()),
            ),
            array(
                'title' => 'Khám phá',
                'url' => home_url('/explore'),
                'icon' => 'fa-regular fa-compass',
                'active' => get_query_var('puna_page') === 'explore',
            ),
        );
        
        // admin
        if (is_user_logged_in()) {
            $menu_items[] = array(
                'title' => 'Hồ sơ',
                'url' => home_url('/profile'),
                'icon' => 'fa-regular fa-user',
                'active' => get_query_var('puna_page') === 'profile',
            );
        }
        
        return $menu_items;
    }

    /**
     * Get default icon based on menu title
     */
    private static function get_default_icon($title) {
        $icon_map = array(
            'trang chủ' => 'fa-solid fa-house',
            'home' => 'fa-solid fa-house',
            'khám phá' => 'fa-regular fa-compass',
            'explore' => 'fa-regular fa-compass',
            'tải lên' => 'fa-solid fa-square-plus',
            'upload' => 'fa-solid fa-square-plus',
            'hồ sơ' => 'fa-regular fa-user',
            'profile' => 'fa-regular fa-user',
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

