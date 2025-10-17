<?php

/**
 * Nạp các thành phần của theme
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

// Nạp các file mô-đun
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/assets.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/meta-boxes.php';

/**
 * Tăng giới hạn upload
 */
function puna_tiktok_increase_upload_limits() {
    ini_set('upload_max_filesize', '500M');
    ini_set('post_max_size', '500M');
    ini_set('max_execution_time', 300);
    ini_set('max_input_time', 300);
    ini_set('memory_limit', '512M');
}
add_action('init', 'puna_tiktok_increase_upload_limits');
