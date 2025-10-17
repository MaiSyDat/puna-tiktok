<?php
/**
 * Template Name: Explore
 * Template Post Type: page
 * Mô tả: Giao diện Khám phá (UI tĩnh)
 */

get_header();
?>

<div class="tiktok-app">
	<?php get_template_part('template-parts/sidebar'); ?>

	<div class="main-content explore-content">
		<div class="explore-header">
			<h2>Khám phá</h2>
			<div class="explore-tabs">
				<button class="tab active">Dành cho bạn</button>
				<button class="tab">Thịnh hành</button>
				<button class="tab">Game</button>
				<button class="tab">Thể thao</button>
				<button class="tab">Âm nhạc</button>
			</div>
		</div>

		<div class="explore-grid">
			<?php for ($i = 1; $i <= 12; $i++) : ?>
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
			<?php endfor; ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>


