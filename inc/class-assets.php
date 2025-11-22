<?php

/**
 * Assets
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Puna_TikTok_Assets
 */
if (!class_exists('Puna_TikTok_Assets')) {

    class Puna_TikTok_Assets {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action('wp_enqueue_scripts', array($this, 'frontend_styles'));
            add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
            add_filter('script_loader_tag', array($this, 'add_defer_to_scripts'), 10, 3);
            
            // Allow other code to hook into assets initialization
            do_action('puna_tiktok_assets_initialized', $this);
        }

        /**
         * Get page context
         * 
         * @return array
         */
        private function get_page_context() {
            $puna_page = get_query_var('puna_page');
            $is_single_video = is_singular('video');
            $is_search_page = is_search();
            $is_home = is_home() || is_front_page();
            $is_category_page = $puna_page === 'category';
            $is_tag_page = $puna_page === 'tag';
            $is_taxonomy_page = $is_category_page || $is_tag_page;
            
            return apply_filters('puna_tiktok_page_context', array(
                'is_single_video' => $is_single_video,
                'is_search_page' => $is_search_page,
                'is_home' => $is_home,
                'is_taxonomy_page' => $is_taxonomy_page,
            ));
        }

        /**
         * Enqueue styles
         */
        public function frontend_styles() {
            $css_dir = PUNA_TIKTOK_THEME_URI . '/assets/css/frontend/components/';
            $version = PUNA_TIKTOK_VERSION;
            
            // Get page context
            $page_context = $this->get_page_context();
            
            // Base CSS - Register first
            wp_register_style('puna-tiktok-fonts', $css_dir . 'fonts.css', array(), $version);
            wp_register_style('puna-tiktok-reset', $css_dir . 'reset.css', array('puna-tiktok-fonts'), $version);
            wp_register_style('puna-tiktok-layout', $css_dir . 'layout.css', array('puna-tiktok-reset'), $version);
            wp_register_style('puna-tiktok-sidebar', $css_dir . 'sidebar.css', array('puna-tiktok-layout'), $version);
            
            // Page-specific CSS - Register
            wp_register_style('puna-tiktok-search', $css_dir . 'search.css', array('puna-tiktok-layout'), $version);
            wp_register_style('puna-tiktok-video-feed', $css_dir . 'video-feed.css', array('puna-tiktok-layout'), $version);
            wp_register_style('puna-tiktok-video-nav', $css_dir . 'video-nav.css', array('puna-tiktok-layout'), $version);
            wp_register_style('puna-tiktok-comments', $css_dir . 'comments.css', array('puna-tiktok-layout'), $version);
            wp_register_style('puna-tiktok-video-watch', $css_dir . 'video-watch.css', array('puna-tiktok-layout'), $version);
            wp_register_style('puna-tiktok-taxonomy', $css_dir . 'taxonomy.css', array('puna-tiktok-layout'), $version);
            
            // Responsive CSS dependencies
            $responsive_deps = array('puna-tiktok-layout', 'puna-tiktok-sidebar');
            if ($page_context['is_home'] || $page_context['is_single_video']) {
                $responsive_deps[] = 'puna-tiktok-video-feed';
            }
            if ($page_context['is_single_video']) {
                $responsive_deps[] = 'puna-tiktok-video-watch';
            }
            if ($page_context['is_taxonomy_page']) {
                $responsive_deps[] = 'puna-tiktok-taxonomy';
            }
            $responsive_deps = apply_filters('puna_tiktok_responsive_css_deps', $responsive_deps, $page_context);
            
            wp_register_style('puna-tiktok-responsive', $css_dir . 'responsive.css', $responsive_deps, $version);
            
            // Font Awesome removed - using SVG icons instead
            
            // Enqueue base styles (always needed)
            wp_enqueue_style('puna-tiktok-fonts');
            wp_enqueue_style('puna-tiktok-reset');
            wp_enqueue_style('puna-tiktok-layout');
            wp_enqueue_style('puna-tiktok-sidebar');
            wp_enqueue_style('puna-tiktok-search'); // Search panel is in header, needed on all pages
            
            // Enqueue page-specific styles
            
            if ($page_context['is_home'] || $page_context['is_single_video']) {
                wp_enqueue_style('puna-tiktok-video-feed');
                wp_enqueue_style('puna-tiktok-video-nav');
                wp_enqueue_style('puna-tiktok-comments');
            }
            
            if ($page_context['is_single_video']) {
                wp_enqueue_style('puna-tiktok-video-watch');
            }
            
            if ($page_context['is_taxonomy_page']) {
                wp_enqueue_style('puna-tiktok-taxonomy');
            }
            
            // Enqueue responsive (always needed)
            wp_enqueue_style('puna-tiktok-responsive');
            
            // Allow other code to hook into styles enqueued
            do_action('puna_tiktok_frontend_styles_enqueued', $this, $page_context);
        }

        /**
         * Enqueue scripts
         */
        public function frontend_scripts() {
            $js_dir = PUNA_TIKTOK_THEME_URI . '/assets/js/';
            $version = PUNA_TIKTOK_VERSION;
            
            // Get page context
            $page_context = $this->get_page_context();
            
            // Mega SDK - Register (needed for video playback from Mega.nz)
            wp_register_script('puna-tiktok-mega-sdk', PUNA_TIKTOK_THEME_URI . '/assets/js/libs/mega.browser.js', array(), $version, true);
            
            // Core JS - Register
            wp_register_script('puna-tiktok-core', $js_dir . 'frontend/core.js', array(), $version, true);
            
            // Mega Video - Register
            wp_register_script('puna-tiktok-mega-video', $js_dir . 'frontend/mega-video.js', array('puna-tiktok-mega-sdk', 'puna-tiktok-core'), $version, true);
            
            // Base JS dependencies
            $base_deps = array('puna-tiktok-mega-sdk', 'puna-tiktok-core', 'puna-tiktok-mega-video');
            $base_deps = apply_filters('puna_tiktok_base_js_deps', $base_deps);
            
            // Guest state - Register
            wp_register_script('puna-tiktok-guest-state', $js_dir . 'frontend/guest-state.js', array('puna-tiktok-core'), $version, true);
            
            // Dropdowns - Register
            wp_register_script('puna-tiktok-dropdowns', $js_dir . 'frontend/dropdowns.js', array('puna-tiktok-core'), $version, true);
            
            // Sidebar toggle - Register
            wp_register_script('puna-tiktok-sidebar-toggle', $js_dir . 'frontend/sidebar-toggle.js', array('puna-tiktok-core'), $version, true);
            
            // Video playback - Register
            wp_register_script('puna-tiktok-video-playback', $js_dir . 'frontend/video-playback.js', $base_deps, $version, true);
            
            // Video navigation - Register
            wp_register_script('puna-tiktok-video-navigation', $js_dir . 'frontend/video-navigation.js', array('puna-tiktok-core'), $version, true);
            
            // Video actions - Register
            wp_register_script('puna-tiktok-video-actions', $js_dir . 'frontend/video-actions.js', array('puna-tiktok-core', 'puna-tiktok-mega-video'), $version, true);
            
            // Comments - Register
            wp_register_script('puna-tiktok-comments', $js_dir . 'frontend/comments.js', array('puna-tiktok-core', 'puna-tiktok-mega-video'), $version, true);
            
            // Search - Register
            wp_register_script('puna-tiktok-search', $js_dir . 'frontend/search.js', array('puna-tiktok-core'), $version, true);
            
            // Video watch - Register
            wp_register_script('puna-tiktok-video-watch', $js_dir . 'frontend/video-watch.js', array('puna-tiktok-core', 'puna-tiktok-mega-video'), $version, true);
            
            // Taxonomy - Register
            wp_register_script('puna-tiktok-taxonomy', $js_dir . 'frontend/taxonomy.js', array('puna-tiktok-core', 'puna-tiktok-mega-video'), $version, true);
            
            // Enqueue base scripts (always needed)
            wp_enqueue_script('puna-tiktok-mega-sdk');
            wp_enqueue_script('puna-tiktok-core');
            wp_enqueue_script('puna-tiktok-mega-video');
            wp_enqueue_script('puna-tiktok-guest-state');
            wp_enqueue_script('puna-tiktok-dropdowns');
            wp_enqueue_script('puna-tiktok-sidebar-toggle');
            wp_enqueue_script('puna-tiktok-search');
            
            // Enqueue page-specific scripts
            if ($page_context['is_home'] || $page_context['is_single_video'] || $page_context['is_search_page'] || $page_context['is_taxonomy_page']) {
                wp_enqueue_script('puna-tiktok-video-playback');
            }
            
            if ($page_context['is_home'] || $page_context['is_single_video'] || $page_context['is_search_page']) {
                wp_enqueue_script('puna-tiktok-video-navigation');
            }
            
            if ($page_context['is_home'] || $page_context['is_single_video'] || $page_context['is_search_page'] || $page_context['is_taxonomy_page']) {
                wp_enqueue_script('puna-tiktok-video-actions');
            }
            
            if ($page_context['is_home'] || $page_context['is_single_video'] || $page_context['is_search_page']) {
                wp_enqueue_script('puna-tiktok-comments');
            }
            
            if ($page_context['is_single_video']) {
                wp_enqueue_script('puna-tiktok-video-watch');
            }
            
            if ($page_context['is_taxonomy_page']) {
                wp_enqueue_script('puna-tiktok-taxonomy');
            }
            
            // Localize script - attach to core.js so it's available everywhere
            $current_user = wp_get_current_user();
            
            // Get menu icon URL
            $menu_icon_url = puna_tiktok_get_icon_url('menu');
            
            // Allow filtering of localized script data
            $localize_data = apply_filters('puna_tiktok_localize_script_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('puna_tiktok_nonce'),
                'like_nonce' => wp_create_nonce('puna_tiktok_like_nonce'),
                'theme_uri' => PUNA_TIKTOK_THEME_URI,
                'is_logged_in' => is_user_logged_in(),
                'current_user' => array(
                    'display_name' => $current_user->display_name,
                    'user_id' => $current_user->ID,
                ),
                'avatar_url' => get_avatar_url($current_user->ID, array('size' => 40)),
                'menu_icon_url' => $menu_icon_url,
                'mega' => false,
            ));
            
            wp_localize_script('puna-tiktok-core', 'puna_tiktok_ajax', $localize_data);
            
            // Allow other code to hook into scripts enqueued
            do_action('puna_tiktok_frontend_scripts_enqueued', $this, $page_context);
        }

        /**
         * Add defer attribute to non-critical scripts
         */
        public function add_defer_to_scripts($tag, $handle, $src) {
            $defer_scripts = array('puna-tiktok-video-playback', 'puna-tiktok-video-navigation', 'puna-tiktok-video-actions', 'puna-tiktok-comments', 'puna-tiktok-search', 'puna-tiktok-video-watch', 'puna-tiktok-taxonomy');
            
            // Allow filtering of defer scripts
            $defer_scripts = apply_filters('puna_tiktok_defer_scripts', $defer_scripts, $handle, $src);
            
            if (in_array($handle, $defer_scripts)) {
                $tag = str_replace(' src', ' defer src', $tag);
                $tag = apply_filters('puna_tiktok_script_tag_with_defer', $tag, $handle, $src);
            }
            
            return apply_filters('puna_tiktok_script_loader_tag', $tag, $handle, $src);
        }
    }

    // Init class
    new Puna_TikTok_Assets();
}
