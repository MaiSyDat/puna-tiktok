<?php

/**
 * Assets
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Assets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('script_loader_tag', array($this, 'add_defer_to_scripts'), 10, 3);
    }
    
    /**
     * Add defer attribute to non-critical scripts
     */
    public function add_defer_to_scripts($tag, $handle, $src) {
        $defer_scripts = array('puna-tiktok-video-playback', 'puna-tiktok-video-navigation', 'puna-tiktok-video-actions', 'puna-tiktok-comments', 'puna-tiktok-search', 'puna-tiktok-video-watch', 'puna-tiktok-taxonomy');
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        $css_dir = PUNA_TIKTOK_THEME_URI . '/assets/css/frontend/components/';
        $version = PUNA_TIKTOK_VERSION;
        
        // Detect page type
        $puna_page = get_query_var('puna_page');
        $is_single_video = is_singular('video');
        $is_search_page = is_search();
        $is_home = is_home() || is_front_page();
        $is_category_page = $puna_page === 'category';
        $is_tag_page = $puna_page === 'tag';
        $is_taxonomy_page = $is_category_page || $is_tag_page;
        
        // Base CSS - Always needed
        wp_enqueue_style('puna-tiktok-reset', $css_dir . 'reset.css', array(), $version);
        wp_enqueue_style('puna-tiktok-layout', $css_dir . 'layout.css', array('puna-tiktok-reset'), $version);
        wp_enqueue_style('puna-tiktok-sidebar', $css_dir . 'sidebar.css', array('puna-tiktok-layout'), $version);
        wp_enqueue_style('puna-tiktok-toast', $css_dir . 'toast.css', array('puna-tiktok-layout'), $version);
        
        // Page-specific CSS
        if ($is_search_page) {
            wp_enqueue_style('puna-tiktok-search', $css_dir . 'search.css', array('puna-tiktok-layout'), $version);
        }
        
        if ($is_home || $is_single_video) {
            wp_enqueue_style('puna-tiktok-video-feed', $css_dir . 'video-feed.css', array('puna-tiktok-layout'), $version);
            wp_enqueue_style('puna-tiktok-video-nav', $css_dir . 'video-nav.css', array('puna-tiktok-layout'), $version);
            wp_enqueue_style('puna-tiktok-comments', $css_dir . 'comments.css', array('puna-tiktok-layout'), $version);
        }
        
        if ($is_single_video) {
            wp_enqueue_style('puna-tiktok-video-watch', $css_dir . 'video-watch.css', array('puna-tiktok-layout'), $version);
        }
        
        if ($is_taxonomy_page) {
            wp_enqueue_style('puna-tiktok-taxonomy', $css_dir . 'taxonomy.css', array('puna-tiktok-layout'), $version);
        }
        
        // Responsive CSS - Always needed, but with conditional dependencies
        $responsive_deps = array('puna-tiktok-layout', 'puna-tiktok-sidebar');
        if ($is_home || $is_single_video) {
            $responsive_deps[] = 'puna-tiktok-video-feed';
        }
        if ($is_single_video) {
            $responsive_deps[] = 'puna-tiktok-video-watch';
        }
        if ($is_taxonomy_page) {
            $responsive_deps[] = 'puna-tiktok-taxonomy';
        }
        
        wp_enqueue_style('puna-tiktok-responsive', $css_dir . 'responsive.css', $responsive_deps, $version);

        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), '7.0.1');

        // Load Mega SDK on all pages (needed for video playback from Mega.nz)
        wp_enqueue_script(
            'puna-tiktok-mega-sdk',
            PUNA_TIKTOK_THEME_URI . '/assets/js/libs/mega.browser.js',
            array(),
            PUNA_TIKTOK_VERSION,
            true
        );

        // Core JS - Always needed
        wp_enqueue_script(
            'puna-tiktok-core',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/core.js',
            array(),
            PUNA_TIKTOK_VERSION,
            true
        );

        wp_enqueue_script(
            'puna-tiktok-mega-video',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/mega-video.js',
            array('puna-tiktok-mega-sdk', 'puna-tiktok-core'),
            PUNA_TIKTOK_VERSION,
            true
        );

        // Base JS dependencies
        $base_deps = array('puna-tiktok-mega-sdk', 'puna-tiktok-core', 'puna-tiktok-mega-video');
        
        // Guest state - Always needed
        wp_enqueue_script(
            'puna-tiktok-guest-state',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/guest-state.js',
            array('puna-tiktok-core'),
            PUNA_TIKTOK_VERSION,
            true
        );

        // Dropdowns - Always needed
        wp_enqueue_script(
            'puna-tiktok-dropdowns',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/dropdowns.js',
            array('puna-tiktok-core'),
            PUNA_TIKTOK_VERSION,
            true
        );

        // Video playback - Load on pages with videos
        if ($is_home || $is_single_video || $is_search_page || $is_taxonomy_page) {
            wp_enqueue_script(
                'puna-tiktok-video-playback',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/video-playback.js',
                $base_deps,
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        // Video navigation - Load on pages with video navigation
        if ($is_home || $is_single_video || $is_search_page) {
            wp_enqueue_script(
                'puna-tiktok-video-navigation',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/video-navigation.js',
                array('puna-tiktok-core'),
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        // Video actions - Load on pages with videos
        if ($is_home || $is_single_video || $is_search_page || $is_taxonomy_page) {
            wp_enqueue_script(
                'puna-tiktok-video-actions',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/video-actions.js',
                array('puna-tiktok-core', 'puna-tiktok-mega-video'),
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        // Comments - Load on pages with comments
        if ($is_home || $is_single_video || $is_search_page) {
            wp_enqueue_script(
                'puna-tiktok-comments',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/comments.js',
                array('puna-tiktok-core', 'puna-tiktok-mega-video'),
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        // Search - Always needed (search panel available everywhere)
        wp_enqueue_script(
            'puna-tiktok-search',
            PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/search.js',
            array('puna-tiktok-core'),
            PUNA_TIKTOK_VERSION,
            true
        );

        // Video watch - Load on single video page
        if ($is_single_video) {
            wp_enqueue_script(
                'puna-tiktok-video-watch',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/video-watch.js',
                array('puna-tiktok-core', 'puna-tiktok-mega-video'),
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        // Taxonomy - Load on category and tag pages
        if ($is_taxonomy_page) {
            wp_enqueue_script(
                'puna-tiktok-taxonomy',
                PUNA_TIKTOK_THEME_URI . '/assets/js/frontend/taxonomy.js',
                array('puna-tiktok-core', 'puna-tiktok-mega-video'),
                PUNA_TIKTOK_VERSION,
                true
            );
        }

        $current_user = wp_get_current_user();

        // Localize script - attach to core.js so it's available everywhere
        wp_localize_script('puna-tiktok-core', 'puna_tiktok_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('puna_tiktok_nonce'),
            'like_nonce' => wp_create_nonce('puna_tiktok_like_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'current_user' => array(
                'display_name' => $current_user->display_name,
                'user_id' => $current_user->ID,
            ),
            'avatar_url' => get_avatar_url($current_user->ID, array('size' => 40)),
            'mega' => false,
        ));
    }

}

new Puna_TikTok_Assets();

