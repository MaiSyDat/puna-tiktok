<?php
/**
 * Main template file
 * 
 * This file handles all page types and delegates to controllers via action hooks
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

// Check for custom pages first (puna_page query var)
$puna_page = get_query_var('puna_page');
if ($puna_page === 'category') {
    do_action('puna_tiktok_category');
} elseif ($puna_page === 'tag') {
    do_action('puna_tiktok_tag');
} elseif (is_404()) {
    do_action('puna_tiktok_404');
} elseif (is_singular()) {
    do_action('puna_tiktok_single');
} elseif (is_search()) {
    do_action('puna_tiktok_search');
} elseif (is_category()) {
    do_action('puna_tiktok_category');
} elseif (is_tag()) {
    do_action('puna_tiktok_tag');
} elseif (is_archive()) {
    do_action('puna_tiktok_archive');
} else {
    do_action('puna_tiktok_index');
}

get_footer();