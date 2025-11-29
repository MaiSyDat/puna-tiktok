<?php
/**
 * Video Card Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
$card_class = isset($args['card_class']) ? $args['card_class'] : 'taxonomy-card';

// Get full metadata including source and youtube_id
$metadata = puna_tiktok_get_video_metadata($post_id);
$video_url = isset($args['video_url']) ? $args['video_url'] : $metadata['video_url'];
$views = isset($args['views']) ? $args['views'] : $metadata['views'];
$video_source = $metadata['source'];
$youtube_id = $metadata['youtube_id'];

if (empty($video_url)) {
    return;
}

// Check for featured image
$featured_image_url = '';
if (has_post_thumbnail($post_id)) {
    $featured_image_url = get_the_post_thumbnail_url($post_id, 'medium');
}
?>

<a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="<?php echo esc_attr($card_class); ?>" aria-label="<?php esc_attr_e('Video', 'puna-tiktok'); ?>">
    <div class="media-wrapper ratio-9x16">
        <?php if ($featured_image_url) : ?>
            <!-- Featured Image -->
            <img src="<?php echo esc_url($featured_image_url); ?>" alt="" class="taxonomy-video" loading="lazy">
        <?php elseif ($video_source === 'youtube' && !empty($youtube_id)) : ?>
            <!-- YouTube Thumbnail -->
            <img src="https://img.youtube.com/vi/<?php echo esc_attr($youtube_id); ?>/hqdefault.jpg" alt="" class="taxonomy-video" loading="lazy">
        <?php else : ?>
            <!-- Mega.nz Video Preview -->
            <video class="taxonomy-video" muted playsinline loading="lazy" data-mega-link="<?php echo esc_url($video_url); ?>">
                <!-- Mega.nz video will be loaded via JavaScript -->
            </video>
        <?php endif; ?>
        <div class="video-overlay">	
            <div class="play-icon">
                <?php echo puna_tiktok_get_icon('play', __('Play', 'puna-tiktok')); ?>
            </div>
        </div>
        <div class="video-views-overlay">
            <?php echo puna_tiktok_get_icon('play', __('Views', 'puna-tiktok')); ?>
            <span><?php echo puna_tiktok_format_number($views); ?></span>
        </div>
    </div>
</a>

