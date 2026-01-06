<?php
/**
 * Category Controller
 * Handles category archive pages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Puna_TikTok_Category_Controller extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_category', array($this, 'render'));
    }
    
    public function render() {
        $data = $this->get_data();
        $this->views('category', $data);
    }
    
    protected function get_data() {
        $category = get_queried_object();
        
        return array(
            'category' => $category,
        );
    }
}

new Puna_TikTok_Category_Controller();

