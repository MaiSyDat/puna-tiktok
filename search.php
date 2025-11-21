<?php
/**
 * Search template
 * 
 * This file handles search results and delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

do_action('puna_tiktok_search');

get_footer();

