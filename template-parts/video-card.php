<?php
/**
 * Video Card Template
 */

$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
$card_class = isset($args['card_class']) ? $args['card_class'] : 'explore-card';

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

$mega_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
$is_mega_video = !empty($mega_node_id) || (strpos($video_url, 'mega.nz') !== false);
?>

<a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="<?php echo esc_attr($card_class); ?>" aria-label="Video">
    <div class="media-wrapper ratio-9x16">
        <video class="explore-video" muted playsinline <?php if ($is_mega_video) : ?>data-mega-link="<?php echo esc_url($video_url); ?>"<?php endif; ?>>
            <source src="<?php echo $is_mega_video ? '' : esc_url($video_url); ?>" type="video/mp4" <?php if ($is_mega_video) : ?>data-mega-src="<?php echo esc_url($video_url); ?>"<?php endif; ?>>
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

