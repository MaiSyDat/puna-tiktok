<?php
/**
 * Theme mode Customizer (Light/Dark/System)
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Customize_Theme_Mode {

    public function __construct() {
        add_action('customize_register', array($this, 'register_settings'));
        add_action('customize_preview_init', array($this, 'customize_preview_js'));
        add_filter('body_class', array($this, 'filter_body_class'));
    }

    public function register_settings($wp_customize) {
        $wp_customize->add_section('puna_theme_section', array(
            'title'       => __('Theme Mode', 'puna-tiktok'),
            'description' => __('Configure light/dark mode for theme', 'puna-tiktok'),
            'priority'    => 19,
            'capability'  => 'manage_options',
        ));

        // Default mode: system / light / dark
        $wp_customize->add_setting('puna_theme_mode', array(
            'default'           => 'system',
            'sanitize_callback' => array($this, 'sanitize_mode'),
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('puna_theme_mode', array(
            'type'        => 'select',
            'section'     => 'puna_theme_section',
            'label'       => __('Default Theme', 'puna-tiktok'),
            'description' => __('Choose default mode for user interface.', 'puna-tiktok'),
            'choices'     => array(
                'system' => __('System', 'puna-tiktok'),
                'light'  => __('Light', 'puna-tiktok'),
                'dark'   => __('Dark', 'puna-tiktok'),
            ),
        ));
    }

    public function sanitize_mode($value) {
        $allowed = array('system', 'light', 'dark');
        return in_array($value, $allowed, true) ? $value : 'system';
    }

    /**
     * Add theme mode class to body without frontend JS
     */
    public function filter_body_class($classes) {
        $mode = get_theme_mod('puna_theme_mode', 'system');
        if ($mode === 'dark') {
            $classes[] = 'theme-dark';
        } elseif ($mode === 'light') {
            $classes[] = 'theme-light';
        }
        return $classes;
    }

    public function customize_preview_js() {
        wp_enqueue_script(
            'puna-tiktok-theme-mode-preview',
            get_template_directory_uri() . '/assets/js/customizer/theme-mode-preview.js',
            array('customize-preview', 'jquery'),
            PUNA_TIKTOK_VERSION,
            true
        );
    }
}

new Puna_TikTok_Customize_Theme_Mode();
