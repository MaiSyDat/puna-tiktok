<?php
/**
 * Customizer Main Class
 * Loads and initializes all customize components
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Customizer {

    /**
     * Path to customize directory
     */
    private $customize_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->customize_dir = get_template_directory() . '/customize/';
        
        // Auto-load all customize components
        $this->load_customize_components();
    }

    /**
     * Auto-load all customize component files
     */
    private function load_customize_components() {
        $customize_files = glob($this->customize_dir . 'customize-*.php');
        
        if ($customize_files) {
            foreach ($customize_files as $file) {
                require_once $file;
            }
        }
    }
}

// Initialize
new Puna_TikTok_Customizer();

