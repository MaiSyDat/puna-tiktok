<?php
/**
 * Video Single View
 * Single video post template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>
    <div class="main-content">
        <?php get_template_part('template-parts/video/content', 'single-video'); ?>
    </div>
</div>

