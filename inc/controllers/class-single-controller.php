<?php
/**
 * Single Controller
 * Handles single post/page display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SingleController extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_single', array($this, 'render'));
    }
    
    public function render() {
        if (is_page()) {
            $this->views('page');
        } else {
            // Check if we have posts
            if (have_posts()) {
                the_post();
                $is_video = get_post_type(get_the_ID()) === 'video';
                rewind_posts();
                
                if ($is_video) {
                    $this->views('video-single');
                } else {
                    $this->views('single');
                }
            } else {
                // No posts found - show 404
                $this->views('404');
            }
        }
    }
}

new SingleController();

