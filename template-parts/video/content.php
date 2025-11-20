<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$metadata = puna_tiktok_get_video_metadata($post_id);
$video_url = $metadata['video_url'];

// Skip if no video URL
if (empty($video_url) || $video_url === 'https://v16-webapp.tiktok.com/video-sample.mp4') {
    return;
}

$likes = $metadata['likes'];
$comments = $metadata['comments'];
$shares = $metadata['shares'];
$saves = $metadata['saves'];
$views = $metadata['views'];

// All videos are Mega videos

$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

$is_saved = puna_tiktok_is_saved($post_id);
$saved_class = $is_saved ? 'saved' : '';
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
		
			<video class="tiktok-video" preload="metadata" playsinline loop muted data-post-id="<?php echo esc_attr($post_id); ?>" data-mega-link="<?php echo esc_url($video_url); ?>">
				<!-- Mega.nz video will be loaded via JavaScript -->
				Trình duyệt của bạn không hỗ trợ video.
			</video>

		<div class="video-overlay">
			<div class="video-details">
				<h4><?php echo esc_html(puna_tiktok_get_user_display_name()); ?></h4>
				<?php
				$caption = puna_tiktok_get_video_description();
				?>
				<p class="video-caption"><?php echo esc_html($caption); ?></p>

				<?php
				$tags = get_the_terms(get_the_ID(), 'video_tag');
				if ($tags) : ?>
					<div class="video-tags">
						<?php foreach ($tags as $tag) : ?>
							<a href="<?php echo esc_url(home_url('/tag/' . $tag->term_id)); ?>" class="tag">#<?php echo esc_html($tag->name); ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<aside class="video-sidebar" aria-hidden="false">
	<div class="author-avatar-wrapper">
		<?php echo puna_tiktok_get_avatar_html(get_the_author_meta('ID'), 50, 'author-avatar'); ?>
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

		<div class="action-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>" data-share-url="<?php echo esc_url(get_permalink($post_id)); ?>" data-share-title="<?php echo esc_attr(puna_tiktok_get_video_description()); ?>">
			<i class="fa-solid fa-share"></i>
			<span class="count"><?php echo puna_tiktok_format_number($shares); ?></span>
		</div>
	</aside>
	
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


