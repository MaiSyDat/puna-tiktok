<?php
/**
 * Template for displaying single video posts
 * 
 * This file is required for WordPress to recognize the single video template
 * It loads the actual template from template-parts/video/content-single-video.php
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (have_posts()) {
    get_template_part('template-parts/video/content', 'single-video');
} else {
    get_template_part('template-parts/404');
}

get_footer();

