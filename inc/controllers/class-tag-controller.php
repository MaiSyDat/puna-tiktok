<?php
/**
 * Tag Controller
 * Handles tag archive pages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Puna_TikTok_Tag_Controller extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_tag', array($this, 'render'));
    }
    
    public function render() {
        $data = $this->get_data();
        $this->views('tag', $data);
    }
    
    protected function get_data() {
        $tag = get_queried_object();
        
        return array(
            'tag' => $tag,
        );
    }
}

new Puna_TikTok_Tag_Controller();

