<?php
/**
 * Template Name: Explore
 * Template Post Type: page
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
			// Query để lấy video thịnh hành
            $trending_query = new WP_Query(array(
                'post_type' => 'post',
				'posts_per_page' => 12,
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
					'relation' => 'OR',
					array(
						'key' => '_puna_tiktok_video_views',
						'compare' => 'EXISTS'
					),
					array(
						'key' => '_puna_tiktok_video_likes',
						'compare' => 'EXISTS'
					)
				)
			));
			
			// Nếu không có video thịnh hành trong 7 ngày, lấy video mới nhất
			if (!$trending_query->have_posts()) {
                $trending_query = new WP_Query(array(
                    'post_type' => 'post',
					'posts_per_page' => 12,
					'post_status' => 'publish',
					'orderby' => 'date',
					'order' => 'DESC'
				));
			}
			
			if ($trending_query->have_posts()) :
                while ($trending_query->have_posts()) : $trending_query->the_post();
                        if ( ! has_block('puna/hupuna-tiktok', get_the_ID()) ) { continue; }
                        $video_url = puna_tiktok_get_video_url();
						$views = get_post_meta(get_the_ID(), '_puna_tiktok_video_views', true);
						$views = $views ? $views : 0;
						$likes = get_post_meta(get_the_ID(), '_puna_tiktok_video_likes', true);
						$likes = $likes ? $likes : 0;
					?>
					<a href="<?php the_permalink(); ?>" class="explore-card" aria-label="Explore item">
						<div class="media-wrapper ratio-9x16">
							<video class="explore-video" muted playsinline>
								<source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
							</video>
							<div class="video-overlay">	
								<div class="play-icon">
									<i class="fa-solid fa-play"></i>
								</div>
							</div>
						</div>
						<div class="card-meta">
							<div class="author">
								<div class="avatar">
									<img src="<?php echo get_avatar_url(get_the_author_meta('ID'), array('size' => 32)); ?>" alt="<?php the_author(); ?>">
								</div>
								<span class="username"><?php the_author(); ?></span>
							</div>
							<div class="stats">
								<i class="fa-solid fa-heart"></i>
								<span><?php echo puna_tiktok_format_number($likes); ?></span>
							</div>
						</div>
					</a>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				// Hiển thị placeholder nếu không có video
				for ($i = 1; $i <= 12; $i++) : ?>
					<a href="#" class="explore-card" aria-label="Explore item">
						<div class="media-wrapper ratio-9x16">
							<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/placeholders/ph-' . (($i % 6) + 1) . '.jpg' ); ?>" alt="placeholder" />
						</div>
						<div class="card-meta">
							<div class="author">
								<div class="avatar"></div>
								<span class="username">username_<?php echo (int) $i; ?></span>
							</div>
							<div class="stats">
								<i class="fa-regular fa-heart"></i>
								<span>12.<?php echo (int) $i; ?>K</span>
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


