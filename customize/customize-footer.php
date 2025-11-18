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
            'description' => __('Cấu hình Footer', 'puna-tiktok'),
            'priority'    => 30,
            'capability'  => 'manage_options',
        ));

        // Footer Settings
        $wp_customize->add_setting('sidebar_footer_title_1', array(
            'default'           => 'Công ty',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_footer_title_1', array(
            'label'    => __('Footer Title 1', 'puna-tiktok'),
            'section'  => 'puna_footer_section',
            'type'     => 'text',
        ));

        $wp_customize->add_setting('sidebar_footer_title_2', array(
            'default'           => 'Chương trình',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_footer_title_2', array(
            'label'    => __('Footer Title 2', 'puna-tiktok'),
            'section'  => 'puna_footer_section',
            'type'     => 'text',
        ));

        $wp_customize->add_setting('sidebar_footer_title_3', array(
            'default'           => 'Điều khoản & Dịch vụ',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_footer_title_3', array(
            'label'    => __('Footer Title 3', 'puna-tiktok'),
            'section'  => 'puna_footer_section',
            'type'     => 'text',
        ));

        $wp_customize->add_setting('sidebar_footer_copyright', array(
            'default'           => 'Puna TikTok',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('sidebar_footer_copyright', array(
            'label'    => __('Copyright Text', 'puna-tiktok'),
            'section'  => 'puna_footer_section',
            'type'     => 'text',
            'description' => __('Use [year] to display current year', 'puna-tiktok'),
        ));
    }
}

// Initialize
new Puna_TikTok_Customize_Footer();

