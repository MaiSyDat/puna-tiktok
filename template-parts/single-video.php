<?php
/**
 * Single Video Watch Page Template
 */

if (!have_posts()) {
    return;
}

the_post();
$post_id = get_the_ID();

if (get_post_type($post_id) !== 'video') {
    get_template_part('template-parts/single');
    return;
}

// Get video data
$metadata = puna_tiktok_get_video_metadata($post_id);
$video_url = $metadata['video_url'];
$likes = $metadata['likes'];
$comments_count = $metadata['comments'];
$shares = $metadata['shares'];
$saves = $metadata['saves'];
$views = $metadata['views'];

// Check if this is a Mega.nz video
$mega_node_id = get_post_meta($post_id, '_puna_tiktok_mega_node_id', true);
if (empty($mega_node_id)) {
    $mega_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true); // backward compatibility
}
$is_mega_video = !empty($mega_node_id) || (strpos($video_url, 'mega.nz') !== false);

// Check if liked
$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

// Check if saved
$is_saved = puna_tiktok_is_saved($post_id);
$saved_class = $is_saved ? 'saved' : '';

// Author data
$author_id = get_the_author_meta('ID');
$author_name = puna_tiktok_get_user_display_name($author_id);
$author_username = puna_tiktok_get_user_username($author_id);
$author_url = get_author_posts_url($author_id);

// Check if current user is the author of this video
$is_author = is_user_logged_in() && get_current_user_id() == $author_id;

// Content
$post_content = get_the_content();
$caption = $post_content;
// Remove any remaining hashtags from caption (in case they weren't removed during upload)
if (!empty($caption)) {
    $caption = preg_replace('/#[\p{L}\p{N}_]+/u', '', $caption);
    $caption = preg_replace('/\s+/', ' ', trim($caption));
}
if (empty(trim(strip_tags($caption)))) {
    $caption = get_the_title();
}
$tags = get_the_tags();

// Music info
$music_name = get_post_meta($post_id, '_puna_tiktok_music_name', true);
$music_artist = get_post_meta($post_id, '_puna_tiktok_music_artist', true);

// Allow comments
global $withcomments;
$withcomments = 1;
?>

<div class="video-watch-page">
    <!-- Back Button -->
    <button class="video-watch-back-btn" id="video-watch-back" title="Quay lại">
        <i class="fa-solid fa-xmark"></i>
    </button>
    
    <div class="video-watch-container">
        <!-- Left Column: Video Player -->
        <div class="video-watch-player">
            <div class="video-player-wrapper">
                <!-- Video Controls Overlay -->
                <div class="video-top-controls">
                    <!-- Options Menu -->
                    <div class="video-options-menu">
                        <button class="video-options-btn" title="Tùy chọn">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <div class="video-options-dropdown">
                            <div class="options-item">
                                <i class="fa-solid fa-arrow-up-down"></i>
                                <span>Cuộn tự động</span>
                                <label class="toggle-switch">
                                    <input type="checkbox" class="autoscroll-toggle">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <?php if ($is_author) : ?>
                                <div class="options-item delete-video-item" data-post-id="<?php echo esc_attr($post_id); ?>">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Xóa video</span>
                                </div>
                            <?php else : ?>
                                <div class="options-item">
                                    <i class="fa-solid fa-heart-crack"></i>
                                    <span>Không quan tâm</span>
                                </div>
                                <div class="options-item">
                                    <i class="fa-solid fa-flag"></i>
                                    <span>Báo cáo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom Controls -->
                <div class="video-player-bottom-actions">
                    <!-- Volume Control -->
                    <div class="volume-control-wrapper">
                        <button class="volume-toggle-btn" title="Âm lượng">
                            <i class="fa-solid fa-volume-high"></i>
                        </button>
                        <div class="volume-slider-container">
                            <input type="range" class="volume-slider" min="0" max="100" value="100" title="Âm lượng">
                        </div>
                    </div>
                </div>
                
                <!-- Video Element -->
                <video class="tiktok-video" 
                       preload="metadata" 
                       playsinline 
                       loop 
                       muted
                       data-post-id="<?php echo esc_attr($post_id); ?>"
                       <?php if ($is_mega_video) : ?>data-mega-link="<?php echo esc_url($video_url); ?>"<?php endif; ?>>
                    <?php if ($is_mega_video) : ?>
                        <!-- Mega.nz video will be loaded via JavaScript -->
                    <?php else : ?>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    <?php endif; ?>
                    Trình duyệt của bạn không hỗ trợ video.
                </video>
            </div>
        </div>
        
        <!-- Right Column: Info & Comments -->
        <div class="video-watch-info">
            <!-- Video Info Section -->
            <div class="video-info-section">
                <!-- Author Info -->
                <div class="video-info-author">
                    <a href="<?php echo esc_url($author_url); ?>" class="author-link">
                        <?php echo puna_tiktok_get_avatar_html($author_id, 60, 'author-avatar-large'); ?>
                        <div class="author-info">
                            <h3 class="author-name"><?php echo esc_html($author_name); ?></h3>
                            <span class="author-username">@<?php echo esc_html($author_username); ?></span>
                        </div>
                    </a>
                    <div class="video-info-more-menu">
                        <button class="video-info-more-btn" title="Thêm">
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>
                        <div class="video-info-more-dropdown">
                            <?php if ($is_author) : ?>
                                <div class="options-item delete-video-item" data-post-id="<?php echo esc_attr($post_id); ?>">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Xóa video</span>
                                </div>
                            <?php else : ?>
                                <div class="options-item">
                                    <i class="fa-solid fa-flag"></i>
                                    <span>Báo cáo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Video Caption -->
                <div class="video-info-caption">
                    <?php 
                    // render caption
                    if (!empty(trim($caption))) {
                        echo wp_kses_post(wpautop($caption));
                    }
                    ?>
                    
                    <!-- Hashtags -->
                    <?php if ($tags) : ?>
                        <div class="video-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo get_tag_link($tag->term_id); ?>" class="tag">#<?php echo esc_html($tag->name); ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Music Info -->
                    <?php if ($music_name || $music_artist) : ?>
                        <div class="video-music-info">
                            <i class="fa-solid fa-music"></i>
                            <span>
                                <?php 
                                if ($music_name && $music_artist) {
                                    echo esc_html($music_name . ' - ' . $music_artist);
                                } elseif ($music_name) {
                                    echo esc_html($music_name);
                                } elseif ($music_artist) {
                                    echo esc_html($music_artist);
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Post Date -->
                    <div class="video-post-date">
                        <?php echo esc_html(get_the_date('Y-m-d')); ?>
                    </div>
                </div>
            </div>
            
            <!-- Interaction Stats -->
            <div class="video-interaction-stats">
                <div class="interaction-item <?php echo esc_attr($liked_class); ?>" data-action="like" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fa-solid fa-heart"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($likes); ?></span>
                </div>
                <div class="interaction-item" data-action="comment-toggle" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fa-solid fa-comment"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($comments_count); ?></span>
                </div>
                <div class="interaction-item <?php echo esc_attr($saved_class); ?>" data-action="save" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fa-solid fa-bookmark"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($saves); ?></span>
                </div>
                <div class="interaction-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>" data-share-url="<?php echo esc_url(get_permalink($post_id)); ?>" data-share-title="<?php echo esc_attr(get_the_title()); ?>">
                    <i class="fa-solid fa-share"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($shares); ?></span>
                </div>
            </div>
            
            
            <!-- Comments Section with Tabs -->
            <div class="video-comments-section">
                <!-- Tabs -->
                <div class="video-comments-tabs">
                    <button class="comments-tab active" data-tab="comments">
                        Bình luận (<?php echo number_format($comments_count); ?>)
                    </button>
                    <button class="comments-tab" data-tab="creator-videos">
                        Video của tác giả
                    </button>
                </div>
                
                <!-- Comments Tab Content -->
                <div class="comments-tab-content active" id="comments-tab-content">
                    <?php get_template_part('template-parts/comments-video-watch'); ?>
                </div>
                
                <!-- Creator Videos Tab Content -->
                <div class="comments-tab-content" id="creator-videos-tab-content">
                    <?php
                    // Get other videos from same author
                    $author_videos = get_posts(array(
                        'post_type'      => 'post',
                        'author'         => $author_id,
                        'posts_per_page' => 10,
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
                        ),
                    ));
                    
                    if (!empty($author_videos)) :
                    ?>
                        <div class="creator-videos-grid">
                            <?php foreach ($author_videos as $video_post) : 
                                setup_postdata($video_post);
                                $other_metadata = puna_tiktok_get_video_metadata($video_post->ID);
                                $other_video_url = $other_metadata['video_url'];
                                $other_likes = $other_metadata['likes'];
                                $other_mega_node_id = get_post_meta($video_post->ID, '_puna_tiktok_video_node_id', true);
                                $other_is_mega = !empty($other_mega_node_id) || (strpos($other_video_url, 'mega.nz') !== false);
                                ?>
                                <a href="<?php echo esc_url(get_permalink($video_post->ID)); ?>" class="creator-video-item">
                                    <div class="creator-video-thumbnail">
                                        <video class="creator-video-preview" muted playsinline <?php if ($other_is_mega) : ?>data-mega-link="<?php echo esc_url($other_video_url); ?>"<?php endif; ?>>
                                            <?php if ($other_is_mega) : ?>
                                                <!-- Mega.nz video will be loaded via JavaScript -->
                                            <?php else : ?>
                                                <source src="<?php echo esc_url($other_video_url); ?>" type="video/mp4">
                                            <?php endif; ?>
                                        </video>
                                        <div class="creator-video-overlay">
                                            <div class="creator-video-likes">
                                                <i class="fa-solid fa-heart"></i>
                                                <span><?php echo puna_tiktok_format_number($other_likes); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <h4 class="creator-video-title"><?php echo esc_html(get_the_title($video_post->ID)); ?></h4>
                                </a>
                            <?php 
                            endforeach;
                            wp_reset_postdata();
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="no-creator-videos">
                            <p>Tác giả chưa có video nào khác</p>
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
            <h2 class="share-modal-title">Share to</h2>
            <button type="button" class="share-modal-close" aria-label="Đóng">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="share-modal-body">
            <div class="share-options-list">
                <!-- Facebook -->
                <button class="share-option" data-share="facebook" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-brands fa-facebook-f"></i>
                    </div>
                    <span class="share-option-label">Facebook</span>
                </button>
                
                <!-- Zalo -->
                <button class="share-option" data-share="zalo" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-solid fa-message"></i>
                    </div>
                    <span class="share-option-label">Zalo</span>
                </button>
                
                <!-- Copy Link -->
                <button class="share-option" data-share="copy" data-post-id="<?php echo esc_attr($post_id); ?>" data-url="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <div class="share-option-icon">
                        <i class="fa-solid fa-link"></i>
                    </div>
                    <span class="share-option-label">Copy link</span>
                </button>
                
                <!-- Instagram -->
                <button class="share-option" data-share="instagram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-brands fa-instagram"></i>
                    </div>
                    <span class="share-option-label">Instagram</span>
                </button>
                
                <!-- Email -->
                <button class="share-option" data-share="email" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <span class="share-option-label">Email</span>
                </button>
                
                <!-- X (Twitter) -->
                <button class="share-option" data-share="x" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-brands fa-x-twitter"></i>
                    </div>
                    <span class="share-option-label">X</span>
                </button>
                
                <!-- Telegram -->
                <button class="share-option" data-share="telegram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
                        <i class="fa-brands fa-telegram"></i>
                    </div>
                    <span class="share-option-label">Telegram</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php 
get_template_part('template-parts/login-popup'); 
?>

