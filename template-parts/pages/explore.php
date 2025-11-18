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
			<div class="explore-tabs" id="explore-tabs">
				<button class="tab" data-tab="foryou">Dành cho bạn</button>
				<button class="tab active" data-tab="trending">Thịnh hành</button>
				<?php
				// Lấy danh sách categories có video
				$all_categories = get_terms(array(
					'taxonomy' => 'category',
					'hide_empty' => false,
				));
				
				if (!is_wp_error($all_categories) && !empty($all_categories)) {
					foreach ($all_categories as $category) {
						// Kiểm tra xem category có video không
						$video_count_query = new WP_Query(array(
							'post_type' => 'video',
							'post_status' => 'publish',
							'posts_per_page' => 1,
							'tax_query' => array(
								array(
									'taxonomy' => 'category',
									'field' => 'term_id',
									'terms' => $category->term_id,
								),
							),
							'fields' => 'ids',
						));
						
						if ($video_count_query->found_posts > 0) {
							echo '<button class="tab" data-tab="category-' . esc_attr($category->term_id) . '" data-category-id="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</button>';
						}
						wp_reset_postdata();
					}
				}
				?>
			</div>
		</div>

		<div class="explore-grid" id="explore-grid">
			<?php
			$displayed_count = 0;
			$target_count = 12;
			
			// Query trending videos in 7 days
            $trending_query = new WP_Query(array(
                'post_type' => 'video',
				'posts_per_page' => 50,
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


