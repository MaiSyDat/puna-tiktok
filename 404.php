<?php
/**
 * 404 template
 * 
 * This file handles 404 error pages and delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

do_action('puna_tiktok_404');

get_footer();

