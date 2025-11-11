<?php
$video_url = puna_tiktok_get_video_url();
$post_id = get_the_ID();

// Get real stats
$likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
$comments = get_comments_number($post_id);
$shares = get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0;
$saves = get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0;
$views = get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0;

// Check if current user liked this video
$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

// Check if current user saved this video
$is_saved = puna_tiktok_is_saved($post_id);
$saved_class = $is_saved ? 'saved' : '';

// Check if current user is the author of this video
$is_author = is_user_logged_in() && get_current_user_id() == get_the_author_meta('ID');
?>

<div class="video-row">
    <section class="video-container">
        <!-- Video Controls -->
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
        
        <video class="tiktok-video" loop playsinline>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            Trình duyệt của bạn không hỗ trợ video.
        </video>

        <div class="video-overlay">
            <div class="video-details">
                <h4><?php the_author(); ?></h4>
                <p class="video-caption"><?php the_title(); ?></p>

                <?php
                $tags = get_the_tags();
                if ($tags) : ?>
                    <div class="video-tags">
                        <?php foreach ($tags as $tag) : ?>
                            <a href="<?php echo get_tag_link($tag->term_id); ?>" class="tag">#<?php echo $tag->name; ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <aside class="video-sidebar" aria-hidden="false">
        <div class="author-avatar-wrapper">
            <img src="<?php echo get_avatar_url(get_the_author_meta('ID'), array('size' => 50)); ?>" alt="<?php the_author(); ?>" class="author-avatar">
            <div class="follow-icon"><i class="fa-solid fa-plus"></i></div>
        </div>

        <div class="action-item <?php echo esc_attr($liked_class); ?>" data-action="like" data-post-id="<?php echo esc_attr($post_id); ?>">
            <i class="fa-solid fa-heart"></i>
            <span class="count"><?php echo puna_tiktok_format_number($likes); ?></span>
        </div>

        <div class="action-item" data-action="comment" data-post-id="<?php echo esc_attr($post_id); ?>">
            <i class="fa-solid fa-comment"></i>
            <span class="count"><?php echo puna_tiktok_format_number($comments); ?></span>
        </div>

        <div class="action-item <?php echo esc_attr($saved_class); ?>" data-action="save" data-post-id="<?php echo esc_attr($post_id); ?>">
            <i class="fa-solid fa-bookmark"></i>
            <span class="count"><?php echo puna_tiktok_format_number($saves); ?></span>
        </div>

        <div class="action-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>" data-share-url="<?php echo esc_url(get_permalink($post_id)); ?>" data-share-title="<?php echo esc_attr(get_the_title()); ?>">
            <i class="fa-solid fa-share"></i>
            <span class="count"><?php echo puna_tiktok_format_number($shares); ?></span>
        </div>
    </aside>
    
    <?php get_template_part('template-parts/comments-sidebar', null, array('post_id' => $post_id)); ?>
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