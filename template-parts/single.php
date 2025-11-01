<?php get_header(); ?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>
	<div class="main-content">
		<?php 
		if ( have_posts() ) : while ( have_posts() ) : the_post();
			if ( has_block('puna/hupuna-tiktok', get_the_ID()) ) {
				get_template_part('template-parts/video/content');
			} else {
				// Fallback content for non-video posts
				echo '<article id="post-' . get_the_ID() . '" ' . get_post_class('') . '>';
				the_title('<h1 class="entry-title">','</h1>');
				echo '<div class="entry-content">';
				the_content();
				echo '</div></article>';
			}
		endwhile; else :
			// No content
			echo '<p>' . esc_html__('Không có nội dung.', 'puna-tiktok') . '</p>';
		endif;
		?>

		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>

		<div class="video-nav">
			<button class="video-nav-btn nav-prev" aria-label="Previous video"><i class="fa-solid fa-chevron-up"></i></button>
			<button class="video-nav-btn nav-next" aria-label="Next video"><i class="fa-solid fa-chevron-down"></i></button>
		</div>
	</div>
</div>

<?php get_footer(); ?>