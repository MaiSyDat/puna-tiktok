<?php
/**
 * Template Name: Profile
 */

get_header();

$user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>

	<div class="main-content profile-content">
		<?php if (!$is_logged_in) : ?>
			<div class="explore-header">
				<h2>Hồ sơ</h2>
				<div class="not-logged-in-message">
					<p>Vui lòng đăng nhập để xem hồ sơ của bạn.</p>
				</div>
			</div>
		<?php else : ?>
			<!-- Header Profile -->
			<div class="profile-header">
				<div class="profile-avatar-wrapper">
					<?php echo puna_tiktok_get_avatar_html($user_id, 116, 'profile-avatar'); ?>
				</div>
				<div class="profile-info">
					<h1 class="profile-username"><?php echo esc_html(puna_tiktok_get_user_display_name($user_id)); ?></h1>
					<h2 class="profile-usernicename">@<?php echo esc_html(puna_tiktok_get_user_username($user_id)); ?></h2>
					<p class="profile-bio"><?php echo esc_html(get_user_meta($user_id, 'description', true) ?: 'Chưa có tiểu sử'); ?></p>
					
					<div class="profile-stats">
						<div class="stat-item">
							<strong class="stat-number"><?php 
                                $user_videos = new WP_Query(array(
                                    'post_type' => 'video',
									'author' => $user_id,
									'posts_per_page' => -1,
                                    'post_status' => 'publish',
									'orderby' => 'date',
									'order' => 'DESC'
								));
								
								// Count videos
								$video_count = $user_videos->found_posts;
								wp_reset_postdata();
								
								echo puna_tiktok_format_number($video_count);
							?></strong>
							<span class="stat-label">Bài đăng</span>
						</div>
					</div>
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
				<button class="profile-tab" data-tab="saved">
					<i class="fa-solid fa-bookmark"></i> Đã lưu
				</button>
			</div>

			<!-- Video Grid -->
			<div class="profile-videos-section active" id="videos-tab">
				<?php
				// Query video của người dùng đăng nhập
                $user_videos_query = new WP_Query(array(
                    'post_type' => 'video',
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
                            get_template_part('template-parts/video-card', null, array(
								'card_class' => 'profile-video-card'
							));
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php else : ?>
					<?php 
					puna_tiktok_empty_state(array(
						'icon' => 'fa-video-slash',
						'title' => 'Chưa có video nào',
						'message' => 'Đăng video đầu tiên của bạn để bắt đầu!',
						'button_url' => puna_tiktok_get_upload_url(),
						'button_text' => 'Tải video lên'
					)); 
					?>
				<?php endif; ?>
			</div>

			<!-- Liked Videos Tab -->
			<div class="profile-videos-section" id="liked-tab">
				<?php
				$liked_video_ids = puna_tiktok_get_liked_videos($user_id);
				
				if (!empty($liked_video_ids)) {
                    $liked_query = new WP_Query(array(
                        'post_type' => 'video',
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
                                get_template_part('template-parts/video-card', null, array(
									'card_class' => 'profile-video-card'
								));
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php else : ?>
						<?php 
						puna_tiktok_empty_state(array(
							'icon' => 'fa-heart',
							'title' => 'Chưa có video yêu thích',
							'message' => 'Video bạn thích sẽ xuất hiện ở đây.'
						)); 
						?>
					<?php endif;
				} else { ?>
					<?php 
					puna_tiktok_empty_state(array(
						'icon' => 'fa-heart',
						'title' => 'Chưa có video yêu thích',
						'message' => 'Video bạn thích sẽ xuất hiện ở đây.'
					)); 
					?>
				<?php } ?>
			</div>

			<!-- Saved Videos Tab -->
			<div class="profile-videos-section" id="saved-tab">
				<?php
				$saved_video_ids = puna_tiktok_get_saved_videos($user_id);
				
				if (!empty($saved_video_ids)) {
                    $saved_query = new WP_Query(array(
                        'post_type' => 'video',
						'post__in' => $saved_video_ids,
						'posts_per_page' => -1,
						'post_status' => 'publish',
						'orderby' => 'post__in',
						'order' => 'DESC'
					));
					
					if ($saved_query->have_posts()) : ?>
						<div class="profile-grid">
							<?php
                            while ($saved_query->have_posts()) : $saved_query->the_post();
                                get_template_part('template-parts/video-card', null, array(
									'card_class' => 'profile-video-card'
								));
							endwhile;
							wp_reset_postdata();
							?>
						</div>
					<?php else : ?>
						<?php 
						puna_tiktok_empty_state(array(
							'icon' => 'fa-bookmark',
							'title' => 'Chưa có video đã lưu',
							'message' => 'Video bạn lưu sẽ xuất hiện ở đây.'
						)); 
						?>
					<?php endif;
				} else { ?>
					<?php 
					puna_tiktok_empty_state(array(
						'icon' => 'fa-bookmark',
						'title' => 'Chưa có video đã lưu',
						'message' => 'Video bạn lưu sẽ xuất hiện ở đây.'
					)); 
					?>
				<?php } ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>


