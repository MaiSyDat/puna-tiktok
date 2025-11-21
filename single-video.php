<?php
/**
 * Template for displaying single video posts
 * 
 * This file is required for WordPress to recognize the single video template
 * It delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

do_action('puna_tiktok_single');

get_footer();

