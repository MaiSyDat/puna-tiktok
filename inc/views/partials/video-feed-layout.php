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
        <?php
        $video_feed_file = locate_template('inc/views/partials/video-feed.php');
        if ($video_feed_file) {
            load_template($video_feed_file, false);
        }
        ?>
    </div>
</div>

