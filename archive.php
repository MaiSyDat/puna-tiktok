<?php
/**
 * Archive template
 * 
 * This file handles archive pages and delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

if (is_category()) {
    do_action('puna_tiktok_category');
} elseif (is_tag()) {
    do_action('puna_tiktok_tag');
} else {
    do_action('puna_tiktok_archive');
}

get_footer();

