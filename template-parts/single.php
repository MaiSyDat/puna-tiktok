<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>
	<div class="main-content">
		<?php 
		if ( have_posts() ) : while ( have_posts() ) : the_post();
			if ( get_post_type(get_the_ID()) === 'video' ) {
				get_template_part('template-parts/video/content');
			} else {
				echo '<article id="post-' . get_the_ID() . '" class="' . implode(' ', get_post_class('')) . '">';
				the_title('<h1 class="entry-title">','</h1>');
				echo '<div class="entry-content">';
				the_content();
				echo '</div></article>';
			}
		endwhile; else :
			echo '<p>' . esc_html__('No content.', 'puna-tiktok') . '</p>';
		endif;
		?>

		<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>

		<div class="video-nav">
			<button class="video-nav-btn nav-prev" aria-label="Previous video">			<?php echo puna_tiktok_get_icon('home', 'Previous video'); ?></button>
			<button class="video-nav-btn nav-next" aria-label="Next video"><?php echo puna_tiktok_get_icon('home', 'Next video'); ?></button>
		</div>
	</div>
</div>

<?php get_footer(); ?>