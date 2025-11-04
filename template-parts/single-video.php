<?php
/**
 * Single Video Watch Page Template
 * Layout 2 cột: Video (70%) | Info + Comments (30%)
 */

if (!have_posts()) {
    return;
}

the_post();
$post_id = get_the_ID();

// Chỉ hiển thị nếu có block video
if (!has_block('puna/hupuna-tiktok', $post_id)) {
    get_template_part('template-parts/single');
    return;
}

// Get video data
$stored_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
$video_url = $stored_id ? wp_get_attachment_url($stored_id) : '';
if (!$video_url) {
    $video_url = puna_tiktok_get_video_url($post_id);
}

// Get stats
$likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
$comments_count = get_comments_number($post_id);
$shares = get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0;
$views = get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0;

// Check if liked
$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

// Author data
$author_id = get_the_author_meta('ID');
$author_name = get_the_author_meta('display_name');
$author_url = get_author_posts_url($author_id);
$author_avatar = get_avatar_url($author_id, array('size' => 60));

// Content
$caption = get_the_content();
$tags = get_the_tags();

// Music info (if available)
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
        <!-- Left Column: Video Player (70%) -->
        <div class="video-watch-player">
            <div class="video-player-wrapper">
                <!-- Video Controls Overlay - Dùng chung với trang index -->
                <div class="video-top-controls">
                    <!-- Volume Control -->
                    <div class="volume-control-wrapper">
                        <button class="volume-toggle-btn" title="Âm lượng">
                            <i class="fa-solid fa-volume-high"></i>
                        </button>
                        <div class="volume-slider-container">
                            <input type="range" class="volume-slider" min="0" max="100" value="100" title="Âm lượng">
                        </div>
                    </div>
                    
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
                            <div class="options-item">
                                <i class="fa-solid fa-heart-crack"></i>
                                <span>Không quan tâm</span>
                            </div>
                            <div class="options-item">
                                <i class="fa-solid fa-flag"></i>
                                <span>Báo cáo</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Video Element - Dùng chung class với trang index -->
                <video class="tiktok-video" 
                       preload="metadata" 
                       playsinline 
                       loop 
                       muted
                       data-post-id="<?php echo esc_attr($post_id); ?>">
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    Trình duyệt của bạn không hỗ trợ video.
                </video>
            </div>
        </div>
        
        <!-- Right Column: Info & Comments (30%) -->
        <div class="video-watch-info">
            <!-- Video Info Section -->
            <div class="video-info-section">
                <!-- Author Info -->
                <div class="video-info-author">
                    <a href="<?php echo esc_url($author_url); ?>" class="author-link">
                        <img src="<?php echo esc_url($author_avatar); ?>" 
                             alt="<?php echo esc_attr($author_name); ?>" 
                             class="author-avatar-large">
                        <div class="author-info">
                            <h3 class="author-name"><?php echo esc_html($author_name); ?></h3>
                            <span class="author-username"><?php echo esc_html(get_the_author_meta('user_login')); ?></span>
                        </div>
                    </a>
                    <button class="follow-btn" title="Theo dõi">
                        <i class="fa-solid fa-plus"></i> Theo dõi
                    </button>
                    <button class="video-info-more-btn" title="Thêm">
                        <i class="fa-solid fa-ellipsis"></i>
                    </button>
                </div>
                
                <!-- Video Caption -->
                <div class="video-info-caption">
                    <?php 
                    if ($caption) {
                        echo wp_kses_post(wpautop($caption));
                    } else {
                        echo '<p>' . esc_html(get_the_title()) . '</p>';
                    }
                    ?>
                    
                    <!-- Hashtags -->
                    <?php if ($tags) : ?>
                        <div class="video-hashtags">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?php echo get_tag_link($tag->term_id); ?>" class="hashtag">#<?php echo esc_html($tag->name); ?></a>
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
                <div class="interaction-item" data-action="save" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fa-solid fa-bookmark"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($shares); ?></span>
                </div>
                <div class="interaction-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <i class="fa-solid fa-share"></i>
                    <span class="stat-count"><?php echo puna_tiktok_format_number($shares); ?></span>
                </div>
            </div>
            
            <!-- Share Options -->
            <div class="video-share-options" id="video-share-options" style="display: none;">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink($post_id)); ?>" 
                   target="_blank" 
                   class="share-option-btn" 
                   title="Chia sẻ Facebook">
                    <i class="fa-brands fa-facebook"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink($post_id)); ?>&text=<?php echo urlencode(get_the_title()); ?>" 
                   target="_blank" 
                   class="share-option-btn" 
                   title="Chia sẻ X (Twitter)">
                    <i class="fa-brands fa-x-twitter"></i>
                </a>
                <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink($post_id)); ?>" 
                   target="_blank" 
                   class="share-option-btn" 
                   title="Chia sẻ WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
                <button class="share-option-btn copy-link-btn" 
                        data-link="<?php echo esc_url(get_permalink($post_id)); ?>" 
                        title="Sao chép liên kết">
                    <i class="fa-solid fa-link"></i>
                </button>
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
                        'post_type' => 'post',
                        'author' => $author_id,
                        'posts_per_page' => 10,
                        'post_status' => 'publish',
                        'post__not_in' => array($post_id),
                        'meta_query' => array(
                            array(
                                'key' => '_puna_tiktok_video_file_id',
                                'compare' => 'EXISTS'
                            )
                        )
                    ));
                    
                    if (!empty($author_videos)) :
                    ?>
                        <div class="creator-videos-grid">
                            <?php foreach ($author_videos as $video_post) : 
                                setup_postdata($video_post);
                                $other_video_url = get_post_meta($video_post->ID, '_puna_tiktok_video_file_id', true) 
                                    ? wp_get_attachment_url(get_post_meta($video_post->ID, '_puna_tiktok_video_file_id', true))
                                    : '';
                                if (!$other_video_url) {
                                    $other_video_url = puna_tiktok_get_video_url($video_post->ID);
                                }
                                $other_likes = get_post_meta($video_post->ID, '_puna_tiktok_video_likes', true) ?: 0;
                                ?>
                                <a href="<?php echo esc_url(get_permalink($video_post->ID)); ?>" class="creator-video-item">
                                    <div class="creator-video-thumbnail">
                                        <video class="creator-video-preview" muted playsinline>
                                            <source src="<?php echo esc_url($other_video_url); ?>" type="video/mp4">
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
    </div>
</div>

