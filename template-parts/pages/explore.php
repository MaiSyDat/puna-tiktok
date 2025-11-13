<?php
/**
 * Template Name: Explore
 */

get_header();
?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>

	<div class="main-content explore-content">
		<div class="explore-header">
			<h2>Khám phá</h2>
			<div class="explore-tabs">
				<button class="tab">Dành cho bạn</button>
				<button class="tab active">Thịnh hành</button>
				<button class="tab">Game</button>
				<button class="tab">Thể thao</button>
				<button class="tab">Âm nhạc</button>
			</div>
		</div>

		<div class="explore-grid">
			<?php
			$displayed_count = 0;
			$target_count = 12;
			
			// Query trending videos in 7 days (sorted by views, highest to lowest)
            $trending_query = new WP_Query(array(
                'post_type' => 'post',
				'posts_per_page' => 50, // Query more to ensure we get enough after filtering
				'post_status' => 'publish',
				'orderby' => 'meta_value_num',
				'meta_key' => '_puna_tiktok_video_views',
				'order' => 'DESC',
				'date_query' => array(
					array(
						'after' => '7 days ago',
					),
				),
				'meta_query' => array(
					array(
						'key' => '_puna_tiktok_video_views',
						'compare' => 'EXISTS'
					),
					array(
						'key' => '_puna_tiktok_video_views',
						'value' => 0,
						'compare' => '>',
						'type' => 'NUMERIC'
					)
				)
			));
			
			// If there is no trending video in 7 days, get the latest video
			if (!$trending_query->have_posts()) {
                $trending_query = new WP_Query(array(
                    'post_type' => 'post',
					'posts_per_page' => 50,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC'
				));
			}
			
			// If still no posts, get all videos sorted by views
			if (!$trending_query->have_posts()) {
                $trending_query = new WP_Query(array(
                    'post_type' => 'post',
					'posts_per_page' => 50,
					'post_status' => 'publish',
					'orderby' => 'meta_value_num',
					'meta_key' => '_puna_tiktok_video_views',
					'order' => 'DESC',
					'meta_query' => array(
						array(
							'key' => '_puna_tiktok_video_views',
							'compare' => 'EXISTS'
						),
						array(
							'key' => '_puna_tiktok_video_views',
							'value' => 0,
							'compare' => '>',
							'type' => 'NUMERIC'
						)
					)
				));
			}
			
			// If still no posts, get any posts with video file
			if (!$trending_query->have_posts()) {
                $trending_query = new WP_Query(array(
                    'post_type' => 'post',
					'posts_per_page' => 50,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC',
					'meta_query' => array(
						array(
							'key' => '_puna_tiktok_video_file_id',
							'compare' => 'EXISTS'
						)
					)
				));
			}
			
			if ($trending_query->have_posts()) :
				// Collect posts with views for sorting
				$posts_with_views = array();
				while ($trending_query->have_posts()) : $trending_query->the_post();
					if ( ! has_block('puna/hupuna-tiktok', get_the_ID()) ) { continue; }
					$video_url = puna_tiktok_get_video_url();
					if (empty($video_url)) { continue; }
					
					$views = get_post_meta(get_the_ID(), '_puna_tiktok_video_views', true);
					$views = $views ? (int)$views : 0;
					
					$posts_with_views[] = array(
						'post_id' => get_the_ID(),
						'views' => $views,
						'video_url' => $video_url
					);
				endwhile;
				wp_reset_postdata();
				
				// Sort by views (highest to lowest)
				usort($posts_with_views, function($a, $b) {
					return $b['views'] - $a['views'];
				});
				
				// Display sorted posts
				foreach ($posts_with_views as $post_data) :
					if ($displayed_count >= $target_count) { break; }
					
					$displayed_count++;
					get_template_part('template-parts/video-card', null, array(
						'post_id' => $post_data['post_id'],
						'video_url' => $post_data['video_url'],
						'views' => $post_data['views'],
						'card_class' => 'explore-card'
					));
				endforeach;
			endif;
			
			// Show placeholder if no video was displayed
			if ($displayed_count == 0) :
				// Show placeholder if no video
				for ($i = 1; $i <= 12; $i++) : ?>
					<a href="#" class="explore-card" aria-label="Explore item">
						<div class="media-wrapper ratio-9x16">
							<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/placeholders/ph-' . (($i % 6) + 1) . '.jpg' ); ?>" alt="placeholder" />
							<div class="video-views-overlay">
								<i class="fa-solid fa-play"></i>
								<span>0</span>
							</div>
						</div>
					</a>
				<?php endfor;
			endif;
			?>
		</div>
	</div>
</div>

<?php get_footer(); ?>


