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
            'description' => __('Configure global color system for theme', 'puna-tiktok'),
            'priority'    => 20,
            'capability'  => 'manage_options',
        ));

        // Only add controls if user has capability
        if (!current_user_can('manage_options')) {
            return;
        }

        // Primary Color
        $wp_customize->add_setting('puna_color_primary', array(
            'default'           => '#3165FF',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'postMessage',
        ));

        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'puna_color_primary', array(
            'label'       => __('Primary Color', 'puna-tiktok'),
            'description' => __('Main color of the theme', 'puna-tiktok'),
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
            'description' => __('Secondary color of the theme', 'puna-tiktok'),
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
            'description' => __('Background color for entire page', 'puna-tiktok'),
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
            'description' => __('Text color for entire page', 'puna-tiktok'),
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
            'description' => __('Link color and hover effects', 'puna-tiktok'),
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
            'description' => __('Border color and muted elements', 'puna-tiktok'),
            'section'     => 'puna_color_section',
            'settings'    => 'puna_color_muted',
        )));

        // Add description about reset
        $wp_customize->add_setting('puna_color_reset_info', array(
            'default'           => '',
            'sanitize_callback' => '__return_empty_string',
        ));

        $wp_customize->add_control('puna_color_reset_info', array(
            'label'       => __('Reset Colors', 'puna-tiktok'),
            'description' => __('To reset all colors to default, delete each color value and click Publish.', 'puna-tiktok'),
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
        $primary   = get_theme_mod('puna_color_primary', '#3165FF');
        $secondary = get_theme_mod('puna_color_secondary', '#ffc107');
        $bg        = get_theme_mod('puna_color_bg', '#ffffff');
        $text      = get_theme_mod('puna_color_text', '#111827');
        $link      = get_theme_mod('puna_color_link', '#0095f6');
        $muted     = get_theme_mod('puna_color_muted', '#e6e6e6');
        $font_family = get_theme_mod('puna_font_family', 'Roboto, sans-serif');
        // Ensure font family always has default value if empty
        $font_family = !empty($font_family) ? $font_family : 'Roboto, sans-serif';

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
                color: var(--puna-text);
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
            body.theme-dark .comment-author-link,
            body.theme-dark .search-suggestion-item,
            body.theme-dark .search-suggestions-list li span,
            body.theme-dark .search-tabs .search-tab,
            body.theme-dark .taxonomy-header,
            body.theme-dark .taxonomy-header h2,
            body.theme-dark .taxonomy-header .tab {
                color: var(--puna-text);
            }

            body.theme-dark a:hover {
                color: var(--puna-primary);
            }

            /* Taxonomy header in dark mode */
            body.theme-dark .taxonomy-header {
                background: rgba(var(--puna-bg-rgb), 0.98);
                border-bottom-color: rgba(255, 255, 255, 0.12);
            }

            body.theme-dark .taxonomy-tabs .tab {
                border-color: rgba(255, 255, 255, 0.15);
                color: var(--puna-text);
            }

            body.theme-dark .taxonomy-tabs .tab:hover {
                border-color: rgba(255, 255, 255, 0.25);
                background: rgba(255, 255, 255, 0.05);
            }

            body.theme-dark .taxonomy-tabs .tab.active {
                color: var(--puna-bg);
            }

            /* Change all icons to white in dark mode */
            body.theme-dark .icon-svg,
            body.theme-dark .icon-img,
            body.theme-dark img.icon-svg,
            body.theme-dark img.icon-img {
                filter: brightness(0) invert(1);
                color: #fff;
            }

            @media (prefers-color-scheme: dark) {
                body:not(.theme-light):not(.theme-dark) {
                    --puna-bg: #0f0f11;
                    --puna-text: #e5e7eb;
                    --puna-link: #7cc4ff;
                    --puna-muted: rgba(255,255,255,0.12);
                }

                /* Change all icons to white in system dark mode */
                body:not(.theme-light):not(.theme-dark) .icon-svg,
                body:not(.theme-light):not(.theme-dark) .icon-img,
                body:not(.theme-light):not(.theme-dark) img.icon-svg,
                body:not(.theme-light):not(.theme-dark) img.icon-img {
                    filter: brightness(0) invert(1);
                    color: #fff;
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
