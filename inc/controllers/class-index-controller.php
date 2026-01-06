<?php
/**
 * Index Controller
 * Handles homepage and archive pages display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Puna_TikTok_Index_Controller extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_index', array($this, 'render_index'));
        add_action('puna_tiktok_archive', array($this, 'render_archive'));
    }
    
    public function render_index() {
        $data = $this->get_data();
        $this->views('index', $data);
    }
    
    public function render_archive() {
        $data = $this->get_data();
        $this->views('archive', $data);
    }
    
    protected function get_data() {
        return $this->get_video_query_data();
    }
}

new Puna_TikTok_Index_Controller();

