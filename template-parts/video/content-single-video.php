<?php
/**
 * Single Video Template
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!have_posts()) {
    return;
}

the_post();
$post_id = get_the_ID();

if (get_post_type($post_id) !== 'video') {
    // This should not happen, but if it does, redirect to single view
    // The SingleController will handle non-video posts
    return;
}

$metadata = puna_tiktok_get_video_metadata($post_id);
$video_url = $metadata['video_url'];
$video_source = $metadata['source'];
$youtube_id = $metadata['youtube_id'];
$likes = $metadata['likes'];
$comments_count = $metadata['comments'];
$shares = $metadata['shares'];
$saves = $metadata['saves'];

$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

$is_saved = puna_tiktok_is_saved($post_id);
$saved_class = $is_saved ? 'saved' : '';

$author_id = get_the_author_meta('ID');
$author_name = puna_tiktok_get_user_display_name($author_id);
$author_username = puna_tiktok_get_user_username($author_id);
$author_url = '#';

$is_author = is_user_logged_in() && get_current_user_id() == $author_id;

$caption = puna_tiktok_get_video_description();
$tags = get_the_terms(get_the_ID(), 'video_tag');

global $withcomments;
$withcomments = 1;
?>

<div class="video-watch-page">
    <!-- Back Button -->
    <button class="video-watch-back-btn" id="video-watch-back" title="<?php esc_attr_e('Back', 'puna-tiktok'); ?>">
        <?php echo puna_tiktok_get_icon('close', __('Back', 'puna-tiktok')); ?>
    </button>
    
    <div class="video-watch-container">
        <!-- Left Column: Video Player -->
        <div class="video-watch-player">
            <div class="video-player-wrapper">
                <!-- Video Controls Overlay -->
                <div class="video-top-controls">
                </div>
                
                <!-- Bottom Controls -->
                <div class="video-player-bottom-actions">
                    <!-- Volume Control -->
                    <div class="volume-control-wrapper">
                        <button class="volume-toggle-btn" title="<?php esc_attr_e('Volume', 'puna-tiktok'); ?>">
                            <?php echo puna_tiktok_get_icon('volum', __('Volume', 'puna-tiktok')); ?>
                        </button>
                        <div class="volume-slider-container">
                            <input type="range" class="volume-slider" min="0" max="100" value="100" title="<?php esc_attr_e('Volume', 'puna-tiktok'); ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Video Element -->
                <?php if ($video_source === 'youtube' && !empty($youtube_id)) : ?>
                    <!-- YouTube Video -->
                    <iframe class="tiktok-video youtube-player" 
                            src="<?php echo esc_url($video_url . '?enablejsapi=1&controls=0&rel=0&playsinline=1&loop=1&playlist=' . $youtube_id . '&modestbranding=1&iv_load_policy=3&fs=0&disablekb=1&cc_load_policy=0'); ?>" 
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen
                            data-source="youtube"
                            data-youtube-id="<?php echo esc_attr($youtube_id); ?>"
                            data-post-id="<?php echo esc_attr($post_id); ?>">
                    </iframe>
                <?php else : ?>
                    <!-- Mega.nz Video -->
                    <video class="tiktok-video" 
                           preload="metadata" 
                           playsinline 
                           loop 
                           muted
                           data-post-id="<?php echo esc_attr($post_id); ?>"
                           data-mega-link="<?php echo esc_url($video_url); ?>"
                           data-source="mega">
                        <!-- Mega.nz video will be loaded via JavaScript -->
                        <?php esc_html_e('Your browser does not support video.', 'puna-tiktok'); ?>
                    </video>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column: Info & Comments -->
        <div class="video-watch-info">
            <!-- Video Info Section -->
            <div class="video-info-section">
                <!-- Author Info -->
                <div class="video-info-author">
                    <div class="author-info-wrapper">
                        <?php echo puna_tiktok_get_avatar_html($author_id, 50, 'author-avatar-large'); ?>
                        <div class="author-info">
                            <h3 class="author-name"><?php echo esc_html($author_name); ?></h3>
                            <span class="author-username">@<?php echo esc_html($author_username); ?></span>
                        </div>
                    </div>
                    <div class="video-info-more-menu">
                        <button class="video-info-more-btn" title="<?php esc_attr_e('More', 'puna-tiktok'); ?>">
                            <?php echo puna_tiktok_get_icon('dot', __('More', 'puna-tiktok')); ?>
                        </button>
                        <div class="video-info-more-dropdown">
                            <?php if ($is_author) : ?>
                                <div class="options-item delete-video-item" data-post-id="<?php echo esc_attr($post_id); ?>">
                                    <?php echo puna_tiktok_get_icon('delete', __('Delete video', 'puna-tiktok')); ?>
                                    <span><?php esc_html_e('Delete video', 'puna-tiktok'); ?></span>
                                </div>
                            <?php else : ?>
                                <div class="options-item">
                                    <?php echo puna_tiktok_get_icon('report', __('Report', 'puna-tiktok')); ?>
                                    <span><?php esc_html_e('Report', 'puna-tiktok'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Video Caption -->
                <div class="video-info-caption">
                    <div class="video-caption-content">
                        <?php 
                        if (!empty(trim($caption))) {
                            echo '<span class="caption-text">' . esc_html(trim($caption)) . '</span>';
                        }
                        ?>
                        
                        <!-- Hashtags inline -->
                        <?php if ($tags) : ?>
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo esc_url(home_url('/tag/' . $tag->term_id . '/')); ?>" class="tag">#<?php echo esc_html($tag->name); ?></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Post Date -->
                    <div class="video-post-date">
                        <?php printf(esc_html__('%s ago', 'puna-tiktok'), esc_html(human_time_diff(get_the_time('U'), current_time('timestamp')))); ?>
                    </div>
                </div>
            </div>
            
            <!-- Interaction Stats -->
            <div class="video-interaction-stats">
                <div class="interaction-item <?php echo esc_attr($liked_class); ?>" data-action="like" data-post-id="<?php echo esc_attr($post_id); ?>">
                        <div class="interaction-icon-wrapper">
                        <?php echo puna_tiktok_get_icon('heart', __('Like', 'puna-tiktok')); ?>
                    </div>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($likes); ?></span>
                </div>
                <div class="interaction-item" data-action="comment-toggle" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="interaction-icon-wrapper">
                        <?php echo puna_tiktok_get_icon('comment', __('Comment', 'puna-tiktok')); ?>
                    </div>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($comments_count); ?></span>
                </div>
                <div class="interaction-item <?php echo esc_attr($saved_class); ?>" data-action="save" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="interaction-icon-wrapper">
                        <?php echo puna_tiktok_get_icon('save', __('Save', 'puna-tiktok')); ?>
                    </div>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($saves); ?></span>
                </div>
                <div class="interaction-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>" data-share-url="<?php echo esc_url(get_permalink($post_id)); ?>" data-share-title="<?php echo esc_attr(puna_tiktok_get_video_description()); ?>">
                    <div class="interaction-icon-wrapper">
                        <?php echo puna_tiktok_get_icon('share', __('Share', 'puna-tiktok')); ?>
                    </div>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($shares); ?></span>
                </div>
            </div>
            
            
            <!-- Comments Section with Tabs -->
            <div class="video-comments-section">
                <!-- Tabs -->
                <div class="video-comments-tabs">
                    <button class="comments-tab active" data-tab="comments">
                        <?php printf(esc_html__('Comments (%s)', 'puna-tiktok'), number_format($comments_count)); ?>
                    </button>
                    <button class="comments-tab" data-tab="creator-videos">
                        <?php esc_html_e('Author videos', 'puna-tiktok'); ?>
                    </button>
                </div>
                
                <!-- Comments Tab Content -->
                <div class="comments-tab-content active" id="comments-tab-content">
                    <?php get_template_part('template-parts/comments-video-watch'); ?>
                </div>
                
                <!-- Creator Videos Tab Content -->
                <div class="comments-tab-content" id="creator-videos-tab-content">
                    <?php
                    $author_videos = get_posts(array(
                        'post_type'      => 'video',
                        'author'         => $author_id,
                        'posts_per_page' => -1, // Get all videos, not just 10
                        'post_status'    => 'publish',
                        'post__not_in'   => array($post_id),
                        'meta_query'     => array(
                            'relation' => 'OR',
                            array(
                                'key'     => '_puna_tiktok_video_url',
                                'value'   => '',
                                'compare' => '!=',
                            ),
                            array(
                                'key'     => '_puna_tiktok_video_file_id',
                                'compare' => 'EXISTS',
                            ),
                            array(
                                'key'     => '_puna_tiktok_mega_link',
                                'compare' => 'EXISTS',
                            ),
                            array(
                                'key'     => '_puna_tiktok_youtube_id',
                                'compare' => 'EXISTS',
                            ),
                        ),
                    ));
                    
                    if (!empty($author_videos)) :
                    ?>
                        <div class="creator-videos-grid">
                            <?php foreach ($author_videos as $video_post) : 
                                setup_postdata($video_post);
                                $other_metadata = puna_tiktok_get_video_metadata($video_post->ID);
                                $other_video_url = $other_metadata['video_url'];
                                $other_views = $other_metadata['views'];
                                $other_video_source = $other_metadata['source'];
                                $other_youtube_id = $other_metadata['youtube_id'];
                                
                                // Check for featured image
                                $featured_image_url = '';
                                if (has_post_thumbnail($video_post->ID)) {
                                    $featured_image_url = get_the_post_thumbnail_url($video_post->ID, 'medium');
                                }
                                ?>
                                <a href="<?php echo esc_url(get_permalink($video_post->ID)); ?>" class="creator-video-item">
                                    <div class="creator-video-thumbnail">
                                        <?php if ($featured_image_url) : ?>
                                            <!-- Featured Image -->
                                            <img src="<?php echo esc_url($featured_image_url); ?>" alt="" class="creator-video-preview" loading="lazy">
                                        <?php elseif ($other_video_source === 'youtube' && !empty($other_youtube_id)) : ?>
                                            <!-- YouTube Thumbnail -->
                                            <img src="https://img.youtube.com/vi/<?php echo esc_attr($other_youtube_id); ?>/hqdefault.jpg" alt="" class="creator-video-preview" loading="lazy">
                                        <?php else : ?>
                                            <!-- Mega.nz Video Preview -->
                                            <video class="creator-video-preview" muted playsinline data-mega-link="<?php echo esc_url($other_video_url); ?>">
                                                <!-- Mega.nz video will be loaded via JavaScript -->
                                            </video>
                                        <?php endif; ?>
                                        <div class="creator-video-overlay">
                                            <div class="creator-video-views">
                                                <?php echo puna_tiktok_get_icon('play', __('Views', 'puna-tiktok')); ?>
                                                <span><?php echo puna_tiktok_format_number($other_views); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php 
                            endforeach;
                            wp_reset_postdata();
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="no-creator-videos">
                            <p><?php esc_html_e('Author has no other videos', 'puna-tiktok'); ?></p>
                        </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Share Modal Popup -->
<div class="share-modal" id="shareModal-<?php echo esc_attr($post_id); ?>">
    <div class="share-modal-overlay"></div>
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h2 class="share-modal-title"><?php esc_html_e('Share to', 'puna-tiktok'); ?></h2>
            <button type="button" class="share-modal-close" aria-label="<?php esc_attr_e('Close', 'puna-tiktok'); ?>">
                <?php echo puna_tiktok_get_icon('close', __('Close', 'puna-tiktok')); ?>
            </button>
        </div>
        <div class="share-modal-body">
            <div class="share-options-list">
                <!-- Facebook -->
                <button class="share-option" data-share="facebook" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('facebook', __('Facebook', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Facebook', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Zalo -->
                <button class="share-option" data-share="zalo" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('zalo', __('Zalo', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Zalo', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Copy Link -->
                <button class="share-option" data-share="copy" data-post-id="<?php echo esc_attr($post_id); ?>" data-url="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('link', __('Copy link', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Copy link', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Instagram -->
                <button class="share-option" data-share="instagram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('instagram', __('Instagram', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Instagram', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Email -->
                <button class="share-option" data-share="email" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('email', __('Email', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Email', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Telegram -->
                <button class="share-option" data-share="telegram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <?php echo puna_tiktok_get_icon('telegram', __('Telegram', 'puna-tiktok')); ?>
                    </div>
                    <span class="share-option-label"><?php esc_html_e('Telegram', 'puna-tiktok'); ?></span>
                </button>
            </div>
        </div>
    </div>
</div>


