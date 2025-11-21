<?php
/**
 * Single template
 * 
 * This file handles single posts/pages and delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

do_action('puna_tiktok_single');

get_footer();

