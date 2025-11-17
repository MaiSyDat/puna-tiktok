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
                'post_type' => 'video',
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
                    'post_type' => 'video',
					'posts_per_page' => 50,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC'
				));
			}
			
			// If still no posts, get all videos sorted by views
			if (!$trending_query->have_posts()) {
                $trending_query = new WP_Query(array(
                    'post_type' => 'video',
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
                    'post_type'      => 'video',
					'posts_per_page' => 50,
					'post_status'    => 'publish',
					'orderby'        => 'date',
					'order'          => 'DESC',
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
			}
			
			if ($trending_query->have_posts()) :
				// Collect posts with views for sorting
				$posts_with_views = array();
				while ($trending_query->have_posts()) : $trending_query->the_post();
					$metadata = puna_tiktok_get_video_metadata();
					if (empty($metadata['video_url'])) { continue; }
					
					$posts_with_views[] = array(
						'post_id' => get_the_ID(),
						'views' => $metadata['views'],
						'video_url' => $metadata['video_url']
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
			
			// Show empty state if no video was displayed
			if ($displayed_count == 0) :
				puna_tiktok_empty_state();
			endif;
			?>
		</div>
	</div>
</div>

<?php get_footer(); ?>


