<?php get_header(); ?>

<?php if ( is_singular() ) : ?>

    <?php if ( is_page() ) : ?>
        <?php get_template_part('template-parts/page'); ?>
    <?php else : ?>
        <?php 
        if (have_posts()) {
            the_post();
            $is_video = get_post_type(get_the_ID()) === 'video';
            rewind_posts();
            
            if ($is_video) {
                get_template_part('template-parts/video/content', 'single-video');
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
            
            $video_query = puna_tiktok_get_video_query();
            
            if ( $video_query->have_posts() ) :
                while ( $video_query->have_posts() ) : $video_query->the_post();
                    get_template_part('template-parts/video/content');
                    comments_template();
                endwhile;
            else :
                puna_tiktok_empty_state(array(
                    'icon' => 'fa-video',
                    'title' => 'Chưa có video nào',
                    'message' => 'Hãy thêm video đầu tiên của bạn!',
                    'button_url' => current_user_can('manage_options') ? admin_url('post-new.php?post_type=video') : '',
                    'button_text' => current_user_can('manage_options') ? 'Thêm Video' : ''
                ));
            endif;
            
            wp_reset_postdata();
            ?>
			<div class="video-nav">
				<button class="video-nav-btn nav-prev" aria-label="Previous video"><?php echo puna_tiktok_get_icon('arrow-up', 'Video trước'); ?></button>
				<button class="video-nav-btn nav-next" aria-label="Next video"><?php echo puna_tiktok_get_icon('arrow', 'Video tiếp'); ?></button>
			</div>
        </div>
    </div>

<?php endif; ?>

<?php get_footer(); ?>