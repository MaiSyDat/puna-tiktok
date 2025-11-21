<?php
/**
 * Archive Controller
 * Handles archive pages display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ArchiveController extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_archive', array($this, 'render'));
    }
    
    public function render() {
        $data = $this->get_data();
        $this->views('archive', $data);
    }
    
    protected function get_data() {
        return $this->get_video_query_data();
    }
}

new ArchiveController();

