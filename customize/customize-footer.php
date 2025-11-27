<?php
/**
 * Footer Customizer Component
 * Handles footer settings
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Customize_Footer {

    public function __construct() {
        add_action('customize_register', array($this, 'register_settings'));
    }

    /**
     * Register Customizer settings for Footer
     */
    public function register_settings($wp_customize) {
        // Check capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add Footer Section (top-level, no panel)
        $wp_customize->add_section('puna_footer_section', array(
            'title'       => __('Footer', 'puna-tiktok'),
            'description' => __('Configure Footer', 'puna-tiktok'),
            'priority'    => 30,
            'capability'  => 'manage_options',
        ));

        // Footer Copyright Settings
        $wp_customize->add_setting('sidebar_footer_copyright', array(
            'default'           => 'Copyright © HUPUNA GROUP',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_footer_copyright', array(
            'label'       => __('Copyright Text', 'puna-tiktok'),
            'section'     => 'puna_footer_section',
            'type'        => 'text',
            'description' => __('Use [year] to display current year', 'puna-tiktok'),
        ));
    }
}

// Initialize
new Puna_TikTok_Customize_Footer();

