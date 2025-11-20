<?php
/**
 * Template Name: Tag
 */

get_header();

// Get tag from query var
$tag_id = get_query_var('tag_id') ? intval(get_query_var('tag_id')) : 0;
$tag = null;
if ($tag_id > 0) {
	$tag = get_term($tag_id, 'post_tag');
}

?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>

	<div class="main-content taxonomy-content">
		<div class="taxonomy-header">
			<h2>Tag</h2>
			<div class="taxonomy-tabs" id="taxonomy-tabs">
				<button class="tab" data-tab="foryou">Dành cho bạn</button>
				<button class="tab<?php echo (!$tag) ? ' active' : ''; ?>" data-tab="trending">Thịnh hành</button>
				<?php
				// Lấy danh sách tags có video, sắp xếp theo popularity
				$all_tags = get_terms(array(
					'taxonomy' => 'post_tag',
					'hide_empty' => false,
					'orderby' => 'count',
					'order' => 'DESC',
					'number' => 50, // Limit to top 50 tags
				));
				
				if (!is_wp_error($all_tags) && !empty($all_tags)) {
					foreach ($all_tags as $tag_item) {
						// Kiểm tra xem tag có video không
						$video_count_query = new WP_Query(array(
							'post_type' => 'video',
							'post_status' => 'publish',
							'posts_per_page' => 1,
							'tax_query' => array(
								array(
									'taxonomy' => 'post_tag',
									'field' => 'term_id',
									'terms' => $tag_item->term_id,
								),
							),
							'fields' => 'ids',
						));
						
						if ($video_count_query->found_posts > 0) {
							$is_active = ($tag && $tag->term_id == $tag_item->term_id);
							$active_class = $is_active ? ' active' : '';
							echo '<button class="tab' . $active_class . '" data-tab="tag-' . esc_attr($tag_item->term_id) . '" data-tag-id="' . esc_attr($tag_item->term_id) . '">#' . esc_html($tag_item->name) . '</button>';
						}
						wp_reset_postdata();
					}
				}
				?>
			</div>
		</div>

		<div class="taxonomy-grid" id="taxonomy-grid">
			<?php
			$displayed_count = 0;
			$target_count = 12;
			
			// If a specific tag is selected, show videos for that tag
			if ($tag && !is_wp_error($tag)) {
				$tag_query = new WP_Query(array(
					'post_type' => 'video',
					'posts_per_page' => 50,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC',
					'tax_query' => array(
						array(
							'taxonomy' => 'post_tag',
							'field' => 'term_id',
							'terms' => $tag->term_id,
						),
					),
				));
				
				if ($tag_query->have_posts()) :
					while ($tag_query->have_posts()) : $tag_query->the_post();
						if ($displayed_count >= $target_count) { break; }
						
						$metadata = puna_tiktok_get_video_metadata();
						if (empty($metadata['video_url'])) { continue; }
						
						$displayed_count++;
						get_template_part('template-parts/video-card', null, array(
							'post_id' => get_the_ID(),
							'video_url' => $metadata['video_url'],
							'views' => $metadata['views'],
							'card_class' => 'taxonomy-card'
						));
					endwhile;
					wp_reset_postdata();
				endif;
			} else {
				// Default: Query trending videos in 7 days (same as category page)
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
							'card_class' => 'taxonomy-card'
						));
					endforeach;
				endif;
			}
			
			// Show empty state if no video was displayed
			if ($displayed_count == 0) :
				puna_tiktok_empty_state();
			endif;
			?>
		</div>
	</div>
</div>

<?php get_footer(); ?>

