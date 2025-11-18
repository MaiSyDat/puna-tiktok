<?php get_header(); ?>

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
                'button_url' => puna_tiktok_get_upload_url(),
                'button_text' => 'Thêm Video'
            ));
        endif;
        
        wp_reset_postdata();
        ?>
		<div class="video-nav">
			<button class="video-nav-btn nav-prev" aria-label="Previous video"><i class="fa-solid fa-chevron-up"></i></button>
			<button class="video-nav-btn nav-next" aria-label="Next video"><i class="fa-solid fa-chevron-down"></i></button>
		</div>
    </div>

</div>

<?php get_footer(); ?>

