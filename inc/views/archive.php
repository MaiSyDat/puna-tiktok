<?php
/**
 * Archive View
 * Archive pages template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use common video feed layout
$layout_file = locate_template('inc/views/partials/video-feed-layout.php');
if ($layout_file) {
    load_template($layout_file, false);
}

