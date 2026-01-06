<?php
/**
 * Base Theme Class
 * 
 * Provides base functionality for Controllers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Puna_TikTok_Theme {
    
    /**
     * Load a view file
     * 
     * @param string $view_name View name (without .php extension)
     * @param array $data Data to pass to the view
     */
    protected function views($view_name, $data = array()) {
        // Extract data array to variables
        extract($data);
        
        // Get view file path
        $view_file = PUNA_TIKTOK_THEME_DIR . '/inc/views/' . $view_name . '.php';
        
        // Check if view file exists
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            // Fallback: try template-parts directory
            $fallback_file = PUNA_TIKTOK_THEME_DIR . '/template-parts/' . $view_name . '.php';
            if (file_exists($fallback_file)) {
                include $fallback_file;
            } else {
                wp_die(sprintf(esc_html__('View file not found: %s', 'puna-tiktok'), esc_html($view_name)));
            }
        }
    }
    
    /**
     * Get data for view
     * 
     * @return array
     */
    protected function get_data() {
        return array();
    }
    
    /**
     * Get video query data (common for index and archive)
     * 
     * @return array
     */
    protected function get_video_query_data() {
        global $withcomments;
        $withcomments = 1;
        
        $video_query = puna_tiktok_get_video_query();
        
        return array(
            'video_query' => $video_query,
        );
    }
}

