<?php
/**
 * Colors Customizer Component
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Customize_Colors {

    public function __construct() {
        add_action('customize_register', array($this, 'register_settings'));
        add_action('wp_head', array($this, 'output_dynamic_css'));
        add_action('customize_preview_init', array($this, 'customize_preview_js'));
    }

    /**
     * Register Customizer settings for Colors
     */
    public function register_settings($wp_customize) {

        $wp_customize->add_section('puna_color_section', array(
            'title'       => __('Color', 'puna-tiktok'),
            'description' => __('Cấu hình hệ thống màu toàn cục cho theme', 'puna-tiktok'),
            'priority'    => 20,
            'capability'  => 'manage_options',
        ));

        // Only add controls if user has capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Primary Color
        $wp_customize->add_setting('puna_color_primary', array(
            'default'           => '#06539f',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_primary', array(
            'label'       => __('Primary Color', 'puna-tiktok'),
            'description' => __('Màu chính của theme', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_primary',
        )));

        // Secondary Color
        $wp_customize->add_setting('puna_color_secondary', array(
            'default'           => '#ffc107',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_secondary', array(
            'label'       => __('Secondary Color', 'puna-tiktok'),
            'description' => __('Màu phụ của theme', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_secondary',
        )));

        // Background Color
        $wp_customize->add_setting('puna_color_bg', array(
            'default'           => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_bg', array(
            'label'       => __('Background Color', 'puna-tiktok'),
            'description' => __('Màu nền toàn trang', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_bg',
        )));

        // Text Color
        $wp_customize->add_setting('puna_color_text', array(
            'default'           => '#111827',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_text', array(
            'label'       => __('Text Color', 'puna-tiktok'),
            'description' => __('Màu chữ chung cho toàn trang', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_text',
        )));

        // Link / Accent Color
        $wp_customize->add_setting('puna_color_link', array(
            'default'           => '#0095f6',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_link', array(
            'label'       => __('Link / Accent Color', 'puna-tiktok'),
            'description' => __('Màu liên kết và hiệu ứng hover', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_link',
        )));

        // Border / Muted Color
        $wp_customize->add_setting('puna_color_muted', array(
            'default'           => '#e6e6e6',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_muted', array(
            'label'       => __('Border / Muted Color', 'puna-tiktok'),
            'description' => __('Màu viền và các phần tử mờ', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_muted',
        )));

        // Typography - Font Family
        $wp_customize->add_setting('puna_font_family', array(
            'default'           => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control('puna_font_family', array(
            'label'       => __('Font Family', 'puna-tiktok'),
            'description' => __('Font chữ cho toàn trang (có thể dùng Google Fonts)', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'type'        => 'text',
        ));

        // Add description about reset
        $wp_customize->add_setting('puna_color_reset_info', array(
            'default'           => '',
            'sanitize_callback' => '__return_empty_string',
        ));

        $wp_customize->add_control('puna_color_reset_info', array(
            'label'       => __('Reset Colors', 'puna-tiktok'),
            'description' => __('Để đặt lại tất cả màu về mặc định, hãy xóa từng giá trị màu và nhấn Publish.', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'type'        => 'hidden',
        ));
    }

    /**
     * Enqueue preview JS for live preview
     */
    public function customize_preview_js() {
        wp_enqueue_script(
            'puna-tiktok-customizer-preview',
            get_template_directory_uri() . '/assets/js/customizer/preview.js',
            array('customize-preview', 'jquery'),
            '1.0.0',
            true
        );
    }

    /**
     * Output dynamic CSS with CSS variables
     */
    public function output_dynamic_css() {
        $primary   = get_theme_mod('puna_color_primary', '#06539f');
        $secondary = get_theme_mod('puna_color_secondary', '#ffc107');
        $bg        = get_theme_mod('puna_color_bg', '#ffffff');
        $text      = get_theme_mod('puna_color_text', '#111827');
        $link      = get_theme_mod('puna_color_link', '#0095f6');
        $muted     = get_theme_mod('puna_color_muted', '#e6e6e6');
        $font_family = get_theme_mod('puna_font_family', 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif');

        ?>
        <style id="puna-tiktok-color-dynamic-css">
            :root {
                --puna-primary: <?php echo esc_attr($primary); ?>;
                --puna-secondary: <?php echo esc_attr($secondary); ?>;
                --puna-bg: <?php echo esc_attr($bg); ?>;
                --puna-text: <?php echo esc_attr($text); ?>;
                --puna-link: <?php echo esc_attr($link); ?>;
                --puna-muted: <?php echo esc_attr($muted); ?>;
                --puna-font-family: <?php echo esc_attr($font_family); ?>;
            }
            
            /* Dark theme overrides via CSS variables */
            body.theme-dark {
                --puna-bg: #0f0f11;
                --puna-text: #e5e7eb;
                --puna-link: #7cc4ff;
                --puna-muted: rgba(255,255,255,0.12);
                color: var(--puna-text, #e5e7eb);
            }

            body.theme-dark,
            body.theme-dark .tiktok-app,
            body.theme-dark .main-content,
            body.theme-dark .sidebar,
            body.theme-dark p,
            body.theme-dark span,
            body.theme-dark li,
            body.theme-dark label,
            body.theme-dark input,
            body.theme-dark textarea,
            body.theme-dark select,
            body.theme-dark button,
            body.theme-dark h1,
            body.theme-dark h2,
            body.theme-dark h3,
            body.theme-dark h4,
            body.theme-dark h5,
            body.theme-dark h6,
            body.theme-dark .video-caption,
            body.theme-dark .video-details h4,
            body.theme-dark .video-tags .tag,
            body.theme-dark .comment-author,
            body.theme-dark .comment-text,
            body.theme-dark .comment-date,
            body.theme-dark .reply-link,
            body.theme-dark .comment-likes span,
            body.theme-dark .comment-options-btn,
            body.theme-dark .comment-action-btn,
            body.theme-dark .comment-author-link,
            body.theme-dark .search-suggestion-item,
            body.theme-dark .search-suggestions-list li span,
            body.theme-dark .search-tabs .search-tab {
                color: var(--puna-text, #e5e7eb);
            }

            body.theme-dark a {
                color: var(--puna-link, #7cc4ff);
            }

            body.theme-dark a:hover {
                color: var(--puna-primary, #25F4EE);
            }

            @media (prefers-color-scheme: dark) {
                body:not(.theme-light):not(.theme-dark) {
                    --puna-bg: #0f0f11;
                    --puna-text: #e5e7eb;
                    --puna-link: #7cc4ff;
                    --puna-muted: rgba(255,255,255,0.12);
                }
            }
            
            /* Apply font family globally */
            body {
                font-family: var(--puna-font-family);
            }
            
            /* Apply background color */
            body,
            .tiktok-app {
                background-color: var(--puna-bg);
                color: var(--puna-text);
            }
            
            /* Apply link colors */
            a {
                color: var(--puna-link);
            }
            
            a:hover {
                color: var(--puna-primary);
            }
            
            /* Apply primary color for highlights */
            .liked,
            .active,
            .comment-likes i.fa-solid.liked {
                color: var(--puna-secondary);
            }
            
            /* Apply muted color for borders */
            .comment-item,
            .video-container {
                border-color: var(--puna-muted);
            }
        </style>
        <?php
    }
}

// Initialize
new Puna_TikTok_Customize_Colors();
