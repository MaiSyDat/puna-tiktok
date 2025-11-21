<?php
/**
 * 404 Controller
 * Handles 404 error page display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Error404Controller extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_404', array($this, 'render'));
    }
    
    public function render() {
        $this->views('404');
    }
}

new Error404Controller();

