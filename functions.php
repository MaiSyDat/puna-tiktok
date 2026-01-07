<?php

/**
 * Functions
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define theme constants
if (! defined('PUNA_TIKTOK_VERSION')) {
    define('PUNA_TIKTOK_VERSION', '1.1.0');
}
if (! defined('PUNA_TIKTOK_THEME_DIR')) {
    define('PUNA_TIKTOK_THEME_DIR', get_template_directory());
}
if (! defined('PUNA_TIKTOK_THEME_URI')) {
    define('PUNA_TIKTOK_THEME_URI', get_template_directory_uri());
}

// Core functions (must be loaded first)
require_once PUNA_TIKTOK_THEME_DIR . '/inc/core-functions.php';

// Theme base class (must be loaded first)
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-theme-base.php';

// Theme classes
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-setup.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-assets.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-ajax-handlers.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-video-post-type.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/class-customizer.php';

// Controllers
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-index-controller.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-single-controller.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-search-controller.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-category-controller.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-tag-controller.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/controllers/class-404-controller.php';

// Admin classes
require_once PUNA_TIKTOK_THEME_DIR . '/inc/admin/class-admin-assets.php';
require_once PUNA_TIKTOK_THEME_DIR . '/inc/admin/class-admin-ajax.php';
