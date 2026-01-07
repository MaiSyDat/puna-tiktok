<?php
/**
 * Video Feed Layout Partial
 * Common layout for video feed pages (index, archive)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Set global variable for video feed to access
if (isset($video_query)) {
    global $puna_video_query;
    $puna_video_query = $video_query;
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

