<?php
/**
 * Video Card Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
$card_class = isset($args['card_class']) ? $args['card_class'] : 'taxonomy-card';

if (isset($args['video_url']) && isset($args['views'])) {
    $video_url = $args['video_url'];
    $views = $args['views'];
} else {
    $metadata = puna_tiktok_get_video_metadata($post_id);
    $video_url = $metadata['video_url'];
    $views = $metadata['views'];
}

if (empty($video_url)) {
    return;
}

// All videos are Mega videos
?>

<a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="<?php echo esc_attr($card_class); ?>" aria-label="Video">
    <div class="media-wrapper ratio-9x16">
        <video class="taxonomy-video" muted playsinline loading="lazy" data-mega-link="<?php echo esc_url($video_url); ?>">
            <!-- Mega.nz video will be loaded via JavaScript -->
        </video>
        <div class="video-overlay">	
            <div class="play-icon">
                <i class="fa-solid fa-play"></i>
            </div>
        </div>
        <div class="video-views-overlay">
            <i class="fa-solid fa-play"></i>
            <span><?php echo puna_tiktok_format_number($views); ?></span>
        </div>
    </div>
</a>

