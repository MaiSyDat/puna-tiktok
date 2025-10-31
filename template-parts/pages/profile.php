<?php
/**
 * Template Name: Profile
 * Template Post Type: page
 */

get_header();

// Lấy thông tin người dùng hiện tại
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$is_logged_in = is_user_logged_in();

?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>

	<div class="main-content profile-content">
		<?php if (!$is_logged_in) : ?>
			<!-- Thông báo chưa đăng nhập -->
			<div class="explore-header">
				<h2>Hồ sơ</h2>
				<div class="not-logged-in-message">
					<p>Vui lòng đăng nhập để xem hồ sơ của bạn.</p>
					<button class="login-btn" onclick="openLoginPopup()">Đăng nhập</button>
				</div>
			</div>
		<?php else : ?>
			<!-- Header Profile -->
			<div class="profile-header">
				<div class="profile-avatar-wrapper">
					<img src="<?php echo get_avatar_url($user_id, array('size' => 116)); ?>" alt="<?php echo esc_attr($current_user->user_nicename); ?>" class="profile-avatar">
				</div>
				<div class="profile-info">
					<h1 class="profile-username"><?php echo esc_html($current_user->display_name); ?></h1>
					<h2 class="profile-usernicename"><?php echo esc_html($current_user->user_nicename); ?></h2>
					<p class="profile-bio"><?php echo esc_html(get_user_meta($user_id, 'description', true) ?: 'Chưa có tiểu sử'); ?></p>
					
					<div class="profile-stats">
						<div class="stat-item">
							<strong class="stat-number"><?php 
                                $user_videos = new WP_Query(array(
                                    'post_type' => 'post',
									'author' => $user_id,
									'posts_per_page' => -1,
                                    'post_status' => 'publish'
								));
								echo number_format($user_videos->post_count);
							?></strong>
							<span class="stat-label">Bài đăng</span>
						</div>
						<div class="stat-item">
							<strong class="stat-number">-</strong>
							<span class="stat-label">Người theo dõi</span>
						</div>
						<div class="stat-item">
							<strong class="stat-number">-</strong>
							<span class="stat-label">Đang theo dõi</span>
						</div>
					</div>
					
					<button class="edit-profile-btn">Chỉnh sửa hồ sơ</button>
				</div>
			</div>

			<!-- Tabs -->
			<div class="profile-tabs">
				<button class="profile-tab active" data-tab="videos">
					<i class="fa-solid fa-grid-3"></i> Video
				</button>
				<button class="profile-tab" data-tab="liked">
					<i class="fa-solid fa-heart"></i> Đã thích
				</button>
			</div>

			<!-- Video Grid -->
			<div class="profile-videos-section" id="videos-tab">
				<?php
				// Query video của người dùng đăng nhập
                $user_videos_query = new WP_Query(array(
                    'post_type' => 'post',
					'author' => $user_id,
					'posts_per_page' => -1,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC'
				));
				
				if ($user_videos_query->have_posts()) : ?>
					<div class="profile-grid">
						<?php
                        while ($user_videos_query->have_posts()) : $user_videos_query->the_post();
                            if ( ! has_block('puna/hupuna-tiktok', get_the_ID()) ) { continue; }
                            $video_url = puna_tiktok_get_video_url();
							$views = get_post_meta(get_the_ID(), '_puna_tiktok_video_views', true);
							$likes = get_post_meta(get_the_ID(), '_puna_tiktok_video_likes', true);
							$views = $views ? $views : 0;
							$likes = $likes ? $likes : 0;
						?>
							<a href="<?php the_permalink(); ?>" class="profile-video-card">
								<div class="media-wrapper ratio-9x16">
									<video class="explore-video" muted playsinline>
										<source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
									</video>
									<div class="video-overlay">
										<div class="play-icon">
											<i class="fa-solid fa-play"></i>
										</div>
									</div>
									<div class="video-stats-overlay">
										<div class="stat-badge">
											<i class="fa-solid fa-play"></i>
											<span><?php echo puna_tiktok_format_number($views); ?></span>
										</div>
										<div class="stat-badge">
											<i class="fa-solid fa-heart"></i>
											<span><?php echo puna_tiktok_format_number($likes); ?></span>
										</div>
									</div>
								</div>
								<div class="video-title"><?php echo esc_html(get_the_title()); ?></div>
							</a>
						<?php
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php else : ?>
					<div class="no-videos-message">
						<div class="no-videos-icon">
							<i class="fa-solid fa-video-slash"></i>
						</div>
						<h3>Chưa có video nào</h3>
						<p>Đăng video đầu tiên của bạn để bắt đầu!</p>
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="upload-video-btn">
							<i class="fa-solid fa-square-plus"></i> Tải video lên
						</a>
					</div>
				<?php endif; ?>
			</div>

			<!-- Liked Videos Tab -->
			<div class="profile-videos-section" id="liked-tab" style="display: none;">
				<?php
				// Get liked videos for current user
				$liked_video_ids = puna_tiktok_get_liked_videos($user_id);
				
				if (!empty($liked_video_ids)) {
                    $liked_query = new WP_Query(array(
                        'post_type' => 'post',
						'post__in' => $liked_video_ids,
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'orderby' => 'post__in',
						'order' => 'DESC'
					));
					
					if ($liked_query->have_posts()) : ?>
						<div class="profile-grid">
							<?php
                            while ($liked_query->have_posts()) : $liked_query->the_post();
                                if ( ! has_block('puna/hupuna-tiktok', get_the_ID()) ) { continue; }
                                $video_url = puna_tiktok_get_video_url();
								$views = get_post_meta(get_the_ID(), '_puna_tiktok_video_views', true);
								$likes = get_post_meta(get_the_ID(), '_puna_tiktok_video_likes', true);
								$views = $views ? $views : 0;
								$likes = $likes ? $likes : 0;
							?>
								<a href="<?php the_permalink(); ?>" class="profile-video-card">
									<div class="media-wrapper ratio-9x16">
										<video class="explore-video" muted playsinline>
											<source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
										</video>
										<div class="video-overlay">
											<div class="play-icon">
												<i class="fa-solid fa-play"></i>
											</div>
										</div>
										<div class="video-stats-overlay">
											<div class="stat-badge">
												<i class="fa-solid fa-play"></i>
												<span><?php echo puna_tiktok_format_number($views); ?></span>
											</div>
											<div class="stat-badge liked">
												<i class="fa-solid fa-heart"></i>
												<span><?php echo puna_tiktok_format_number($likes); ?></span>
											</div>
										</div>
									</div>
									<div class="video-title"><?php echo esc_html(get_the_title()); ?></div>
								</a>
							<?php
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php else : ?>
						<div class="no-videos-message">
							<div class="no-videos-icon">
								<i class="fa-solid fa-heart"></i>
							</div>
							<h3>Chưa có video yêu thích</h3>
							<p>Video bạn thích sẽ xuất hiện ở đây.</p>
						</div>
					<?php endif;
				} else { ?>
					<div class="no-videos-message">
						<div class="no-videos-icon">
							<i class="fa-solid fa-heart"></i>
						</div>
						<h3>Chưa có video yêu thích</h3>
						<p>Video bạn thích sẽ xuất hiện ở đây.</p>
					</div>
				<?php } ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>


