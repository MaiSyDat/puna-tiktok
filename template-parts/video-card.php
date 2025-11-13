<?php
/**
 * Video Card Template Part
 * Reusable video card for explore, profile, and author pages
 * 
 * @param array $args {
 *     @type int    $post_id    Post ID (default: get_the_ID())
 *     @type string $video_url  Video URL (optional, will be fetched if not provided)
 *     @type int    $views      View count (optional, will be fetched if not provided)
 *     @type string $card_class Additional CSS class for the card (default: 'explore-card')
 * }
 */

$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
$card_class = isset($args['card_class']) ? $args['card_class'] : 'explore-card';

// Get video metadata if not provided
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
?>

<a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="<?php echo esc_attr($card_class); ?>" aria-label="Video">
    <div class="media-wrapper ratio-9x16">
        <video class="explore-video" muted playsinline>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
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

