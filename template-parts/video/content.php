<?php
$post_id = get_the_ID();

$stored_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
$video_url = $stored_id ? wp_get_attachment_url($stored_id) : '';
if (! $video_url) {
	$video_url = puna_tiktok_get_video_url($post_id);
}

// Get real stats
$likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
$comments = get_comments_number($post_id);
$shares = get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0;
$views = get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0;

// Check if current user liked this video
$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';
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
		
		<video class="tiktok-video" preload="metadata" playsinline loop muted data-post-id="<?php echo esc_attr($post_id); ?>">
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

		<div class="action-item" data-action="save">
			<i class="fa-solid fa-bookmark"></i>
			<span class="count"><?php echo puna_tiktok_format_number($shares); ?></span>
		</div>

		<div class="action-item" data-action="share">
			<i class="fa-solid fa-share"></i>
			<span class="count"><?php echo puna_tiktok_format_number($shares); ?></span>
		</div>
	</aside>
	
</div>


