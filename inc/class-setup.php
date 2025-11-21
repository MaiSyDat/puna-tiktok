<?php

/**
 * Theme Setup
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define theme constants
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
    
    /**
     * Get custom pages config
     */
    private function get_custom_pages() {
        return array(
            'category' => 'index.php',
            'tag'      => 'index.php',
        );
    }
    
    /**
     * Get page titles
     */
    private function get_page_titles() {
        return array(
            'category' => 'Categories',
            'tag'      => 'Tag',
        );
    }
    
    public function __construct() {
        add_action('after_setup_theme', array($this, 'setup'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_filter('template_include', array($this, 'handle_custom_pages'));
        add_filter('pre_get_document_title', array($this, 'custom_page_title'));
        add_action('after_switch_theme', array($this, 'flush_rewrite_rules_on_activation'));
        add_action('admin_init', array($this, 'maybe_flush_rewrite_rules'));
    }
    
    /**
     * Setup theme
     */
    public function setup()
    {
        // Load theme textdomain for translations
        load_theme_textdomain('puna-tiktok', get_template_directory() . '/languages');
        
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ));
        
        register_nav_menus(array(
            'puna-tiktok-sidebar' => __('TikTok Sidebar Menu', 'puna-tiktok'),
        ));
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        $custom_pages = $this->get_custom_pages();
        
        foreach ($custom_pages as $slug => $template) {
            add_rewrite_rule(
                '^' . $slug . '/?$',
                'index.php?puna_page=' . $slug,
                'top'
            );
        }
        
        // Add rewrite rule for tag with ID: /tag/123
        add_rewrite_rule(
            '^tag/([0-9]+)/?$',
            'index.php?puna_page=tag&tag_id=$matches[1]',
            'top'
        );
    }

    /**
     * Register query vars
     */
    public function register_query_vars($vars) {
        $vars[] = 'puna_page';
        $vars[] = 'tag_id';
        return $vars;
    }

    /**
     * Handle custom pages
     */
    public function handle_custom_pages($template) {
        global $wp_query;
        
        $puna_page = get_query_var('puna_page');
        
        if (!$puna_page) {
            return $template;
        }
        
        $page_templates = $this->get_custom_pages();
        
        if (isset($page_templates[$puna_page])) {
            $template_file = $page_templates[$puna_page];
            $found_template = locate_template($template_file);
            
            if ($found_template) {
                $page_titles = $this->get_page_titles();
                $page_title = isset($page_titles[$puna_page]) ? $page_titles[$puna_page] : ucfirst($puna_page);
                
                // Set query flags for custom pages
                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_404 = false;
                $wp_query->is_home = false;
                $wp_query->is_front_page = false;
                
                // For category/tag pages, set appropriate flags
                if ($puna_page === 'category') {
                    $wp_query->is_category = true;
                    $wp_query->is_archive = true;
                } elseif ($puna_page === 'tag') {
                    $wp_query->is_tag = true;
                    $wp_query->is_archive = true;
                }
                
                $fake_post = new stdClass();
                $fake_post->ID = 0;
                $fake_post->post_author = get_current_user_id();
                $fake_post->post_date = current_time('mysql');
                $fake_post->post_date_gmt = current_time('mysql', 1);
                $fake_post->post_content = '';
                $fake_post->post_title = $page_title;
                $fake_post->post_excerpt = '';
                $fake_post->post_status = 'publish';
                $fake_post->comment_status = 'closed';
                $fake_post->ping_status = 'closed';
                $fake_post->post_password = '';
                $fake_post->post_name = $puna_page;
                $fake_post->to_ping = '';
                $fake_post->pinged = '';
                $fake_post->post_modified = current_time('mysql');
                $fake_post->post_modified_gmt = current_time('mysql', 1);
                $fake_post->post_content_filtered = '';
                $fake_post->post_parent = 0;
                $fake_post->guid = home_url('/' . $puna_page . '/');
                $fake_post->menu_order = 0;
                $fake_post->post_type = 'page';
                $fake_post->post_mime_type = '';
                $fake_post->comment_count = 0;
                $fake_post->filter = 'raw';
                
                $wp_query->queried_object = $fake_post;
                $wp_query->queried_object_id = 0;
                $wp_query->posts = array($fake_post);
                $wp_query->post_count = 1;
                $wp_query->found_posts = 1;
                $wp_query->max_num_pages = 1;
                $wp_query->post = $fake_post;
                $GLOBALS['post'] = $fake_post;
                
                return $found_template;
            }
        }
        
        return $template;
    }

    /**
     * Custom page title
     */
    public function custom_page_title($title) {
        $puna_page = get_query_var('puna_page');
        
        if (!$puna_page) {
            return $title;
        }
        
        $page_titles = $this->get_page_titles();
        $page_title = isset($page_titles[$puna_page]) ? $page_titles[$puna_page] : ucfirst($puna_page);
        $site_name = get_bloginfo('name');
        
        if (!empty($site_name)) {
            return sprintf('%s | %s', $page_title, $site_name);
        }
        
        return $page_title;
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules_on_activation() {
        flush_rewrite_rules();
    }

    /**
     * Maybe flush rewrite rules
     */
    public function maybe_flush_rewrite_rules() {
        $rules = get_option('rewrite_rules', array());
        $custom_pages = $this->get_custom_pages();
        $all_rules_exist = true;
        
        foreach (array_keys($custom_pages) as $slug) {
            if (!isset($rules['^' . $slug . '/?$'])) {
                $all_rules_exist = false;
                break;
            }
        }
        
        if (!$all_rules_exist) {
            flush_rewrite_rules(false);
        }
    }
}

new Puna_TikTok_Setup();

