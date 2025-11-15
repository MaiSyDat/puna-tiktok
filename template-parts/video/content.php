<?php
$post_id = get_the_ID();
$metadata = puna_tiktok_get_video_metadata($post_id);
$video_url = $metadata['video_url'];
$likes = $metadata['likes'];
$comments = $metadata['comments'];
$shares = $metadata['shares'];
$saves = $metadata['saves'];
$views = $metadata['views'];
$mega_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
$is_mega_video = !empty($mega_node_id) && !empty($video_url);

// Check if current user liked this video
$is_liked = puna_tiktok_is_liked($post_id);
$liked_class = $is_liked ? 'liked' : '';

// Check if current user saved this video
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
		
			<video class="tiktok-video" preload="metadata" playsinline loop muted data-post-id="<?php echo esc_attr($post_id); ?>" <?php if ($is_mega_video) : ?>data-mega-link="<?php echo esc_url($video_url); ?>"<?php endif; ?>>
				<?php if ($is_mega_video) : ?>
					<!-- Mega.nz video will be loaded via JavaScript -->
				<?php else : ?>
					<source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
				<?php endif; ?>
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
	
</div>


