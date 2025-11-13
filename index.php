<?php get_header(); ?>

<?php if ( is_singular() ) : ?>

    <?php if ( is_page() ) : ?>
        <?php get_template_part('template-parts/page'); ?>
    <?php else : ?>
        <?php 
        // Check if this is a video post
        if (have_posts()) {
            the_post();
            $is_video = has_block('puna/hupuna-tiktok', get_the_ID());
            rewind_posts();
            
            if ($is_video) {
                get_template_part('template-parts/single-video');
            } else {
                get_template_part('template-parts/single');
            }
        } else {
            get_template_part('template-parts/single');
        }
        ?>
    <?php endif; ?>

<?php elseif ( is_search() ) : ?>

    <?php get_template_part('template-parts/search'); ?>

<?php else : ?>

    <div class="tiktok-app">
        <?php get_template_part('template-parts/sidebar'); ?>
        
        <div class="main-content">
            <?php
            global $withcomments;
            $withcomments = 1;
            
            // Query all posts (tag archives are handled by tag.php)
            $video_query = puna_tiktok_get_video_query();
            
            if ( $video_query->have_posts() ) :
                while ( $video_query->have_posts() ) : $video_query->the_post();
                    if ( has_block('puna/hupuna-tiktok', get_the_ID()) ) {
                        get_template_part('template-parts/video/content');
                        comments_template();
                    }
                endwhile;
            else :
                ?>
                <div class="video-loading">
                    <h3>Chưa có video nào</h3>
                    <p>Hãy thêm video đầu tiên của bạn!</p>
                    <a href="<?php echo admin_url('post-new.php'); ?>" class="btn-add-video">Thêm Video</a>
                </div>
                <?php
            endif;
            
            wp_reset_postdata();
            ?>
			<div class="video-nav">
				<button class="video-nav-btn nav-prev" aria-label="Previous video"><i class="fa-solid fa-chevron-up"></i></button>
				<button class="video-nav-btn nav-next" aria-label="Next video"><i class="fa-solid fa-chevron-down"></i></button>
			</div>
        </div>
    </div>

<?php endif; ?>

<?php get_footer(); ?>