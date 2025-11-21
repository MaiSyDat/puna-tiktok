<?php
/**
 * Video Feed Partial
 * Reusable video feed template
 */

if (!defined('ABSPATH')) {
    exit;
}

global $withcomments;
$withcomments = 1;

if (isset($video_query) && $video_query->have_posts()) :
    while ($video_query->have_posts()) : $video_query->the_post();
        get_template_part('template-parts/video/content');
        comments_template();
    endwhile;
else :
    puna_tiktok_empty_state(array(
        'icon' => 'fa-video',
        'title' => __('No videos yet', 'puna-tiktok'),
        'message' => __('Add your first video!', 'puna-tiktok'),
        'button_url' => current_user_can('manage_options') ? admin_url('post-new.php?post_type=video') : '',
        'button_text' => current_user_can('manage_options') ? __('Add Video', 'puna-tiktok') : ''
    ));
endif;

wp_reset_postdata();
?>

<div class="video-nav">
    <button class="video-nav-btn nav-prev" aria-label="<?php esc_attr_e('Previous video', 'puna-tiktok'); ?>">
        <?php echo puna_tiktok_get_icon('arrow-up', __('Previous video', 'puna-tiktok')); ?>
    </button>
    <button class="video-nav-btn nav-next" aria-label="<?php esc_attr_e('Next video', 'puna-tiktok'); ?>">
        <?php echo puna_tiktok_get_icon('arrow', __('Next video', 'puna-tiktok')); ?>
    </button>
</div>

