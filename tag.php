<?php get_header(); ?>

<div class="tiktok-app">

    <?php get_template_part('template-parts/sidebar'); ?>
    
    <div class="main-content">
        <?php
        // Allow comments_template on non-singular templates
        global $withcomments;
        $withcomments = 1;

        // Get current tag
        $tag = get_queried_object();
        
        // Query posts with this tag (or all if tag not found)
        $tag_id = ($tag && isset($tag->term_id)) ? $tag->term_id : null;
        $video_query = puna_tiktok_get_video_query(array(
            'tag_id' => $tag_id
        ));
        
        if ( $video_query->have_posts() ) :
            while ( $video_query->have_posts() ) : $video_query->the_post();
                get_template_part('template-parts/video/content');
                comments_template();
            endwhile;
        else :
            ?>
            <div class="video-loading">
                <h3>Chưa có video nào với tag này</h3>
                <p>Không tìm thấy video nào với tag "<?php echo esc_html($tag->name ?? ''); ?>"</p>
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

<?php get_footer(); ?>

