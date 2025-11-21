<?php
/**
 * Video Feed Layout Partial
 * Common layout for video feed pages (index, archive)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>
    
    <div class="main-content">
        <?php include get_template_directory() . '/inc/views/partials/video-feed.php'; ?>
    </div>
</div>

