<?php

/**
 * Functions
 */

if (! defined('ABSPATH')) {
    exit;
}

// Core functions (must be loaded first)
require_once get_template_directory() . '/inc/core-functions.php';

// Theme base class (must be loaded first)
require_once get_template_directory() . '/inc/class-theme-base.php';

// Theme classes
require_once get_template_directory() . '/inc/class-setup.php';
require_once get_template_directory() . '/inc/class-assets.php';
require_once get_template_directory() . '/inc/class-ajax-handlers.php';
require_once get_template_directory() . '/inc/class-video-post-type.php';
require_once get_template_directory() . '/inc/class-customizer.php';

// Controllers
require_once get_template_directory() . '/inc/controllers/class-index-controller.php';
require_once get_template_directory() . '/inc/controllers/class-single-controller.php';
require_once get_template_directory() . '/inc/controllers/class-search-controller.php';
require_once get_template_directory() . '/inc/controllers/class-category-controller.php';
require_once get_template_directory() . '/inc/controllers/class-tag-controller.php';
require_once get_template_directory() . '/inc/controllers/class-404-controller.php';

// Admin classes
require_once get_template_directory() . '/inc/admin/class-admin-assets.php';
require_once get_template_directory() . '/inc/admin/class-admin-ajax.php';
