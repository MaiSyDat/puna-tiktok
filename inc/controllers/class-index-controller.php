<?php
/**
 * Index Controller
 * Handles homepage display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class IndexController extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_index', array($this, 'render'));
    }
    
    public function render() {
        $data = $this->get_data();
        $this->views('index', $data);
    }
    
    protected function get_data() {
        return $this->get_video_query_data();
    }
}

new IndexController();

