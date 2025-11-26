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
	<div class="video-row-inner">
		<section class="video-container">
		<!-- Video Controls -->
		<div class="video-top-controls">
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
		
			<video class="tiktok-video" preload="metadata" playsinline loop muted data-post-id="<?php echo esc_attr($post_id); ?>" data-mega-link="<?php echo esc_url($video_url); ?>">
				<!-- Mega.nz video will be loaded via JavaScript -->
				<?php esc_html_e('Your browser does not support video.', 'puna-tiktok'); ?>
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
								<a href="<?php echo esc_url(home_url('/tag/' . $tag->term_id . '/')); ?>" class="tag">#<?php echo esc_html($tag->name); ?></a>
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
				<div class="action-icon-wrapper">
					<?php echo puna_tiktok_get_icon('heart', 'Like'); ?>
				</div>
				<span class="count"><?php echo puna_tiktok_format_number($likes); ?></span>
			</div>

			<div class="action-item" data-action="comment" data-post-id="<?php echo esc_attr($post_id); ?>">
				<div class="action-icon-wrapper">
					<?php echo puna_tiktok_get_icon('comment', 'Comment'); ?>
				</div>
				<span class="count"><?php echo puna_tiktok_format_number($comments); ?></span>
			</div>

			<div class="action-item <?php echo esc_attr($saved_class); ?>" data-action="save" data-post-id="<?php echo esc_attr($post_id); ?>">
				<div class="action-icon-wrapper">
					<?php echo puna_tiktok_get_icon('save', 'Save'); ?>
				</div>
				<span class="count"><?php echo puna_tiktok_format_number($saves); ?></span>
			</div>

			<div class="action-item" data-action="share" data-post-id="<?php echo esc_attr($post_id); ?>" data-share-url="<?php echo esc_url(get_permalink($post_id)); ?>" data-share-title="<?php echo esc_attr(puna_tiktok_get_video_description()); ?>">
				<div class="action-icon-wrapper">
					<?php echo puna_tiktok_get_icon('share', 'Share'); ?>
				</div>
				<span class="count"><?php echo puna_tiktok_format_number($shares); ?></span>
			</div>
		</aside>
	</div>
</div>

<!-- Share Modal Popup -->
<div class="share-modal" id="shareModal-<?php echo esc_attr($post_id); ?>">
    <div class="share-modal-overlay"></div>
    <div class="share-modal-content">
		<div class="share-modal-header">
			<h2 class="share-modal-title"><?php esc_html_e('Share to', 'puna-tiktok'); ?></h2>
            <button type="button" class="share-modal-close" aria-label="<?php esc_attr_e('Close', 'puna-tiktok'); ?>">
				<?php echo puna_tiktok_get_icon('close', 'Close'); ?>
            </button>
        </div>
        <div class="share-modal-body">
            <div class="share-options-list">
                <!-- Facebook -->
                <button class="share-option" data-share="facebook" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('facebook', 'Facebook'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Facebook', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Zalo -->
                <button class="share-option" data-share="zalo" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('zalo', 'Zalo'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Zalo', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Copy Link -->
                <button class="share-option" data-share="copy" data-post-id="<?php echo esc_attr($post_id); ?>" data-url="<?php echo esc_url(get_permalink($post_id)); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('link', 'Copy link'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Copy link', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Instagram -->
                <button class="share-option" data-share="instagram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('instagram', 'Instagram'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Instagram', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Email -->
                <button class="share-option" data-share="email" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('email', 'Email'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Email', 'puna-tiktok'); ?></span>
                </button>
                
                <!-- Telegram -->
                <button class="share-option" data-share="telegram" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="share-option-icon">
						<?php echo puna_tiktok_get_icon('telegram', 'Telegram'); ?>
                    </div>
					<span class="share-option-label"><?php esc_html_e('Telegram', 'puna-tiktok'); ?></span>
                </button>
            </div>
        </div>
    </div>
</div>


