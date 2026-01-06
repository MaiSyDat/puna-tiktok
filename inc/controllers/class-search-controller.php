<?php
/**
 * Search Controller
 * Handles search results display
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Puna_TikTok_Search_Controller extends Puna_TikTok_Theme {
    
    public function __construct() {
        add_action('puna_tiktok_search', array($this, 'render'));
    }
    
    public function render() {
        $data = $this->get_data();
        $this->views('search', $data);
    }
    
    protected function get_data() {
        $search_query = get_search_query();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'top';
        $valid_tabs = array('top', 'users', 'videos');
        if (!in_array($active_tab, $valid_tabs)) {
            $active_tab = 'top';
        }
        
        global $withcomments;
        $withcomments = 1;
        
        return array(
            'search_query' => $search_query,
            'active_tab' => $active_tab,
        );
    }
}

new Puna_TikTok_Search_Controller();

