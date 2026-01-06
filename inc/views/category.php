<?php
/**
 * Category View
 * Category archive template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>

    <div class="main-content taxonomy-content">
        <div class="taxonomy-header">
            <h2><?php esc_html_e('Categories', 'puna-tiktok'); ?></h2>
            <div class="taxonomy-tabs" id="taxonomy-tabs">
                <?php
                // Get current tab type and category
                $current_tab = isset($_GET['tab_type']) ? sanitize_text_field($_GET['tab_type']) : 'trending';
                $current_category_id = get_query_var('category_id') ? intval(get_query_var('category_id')) : 0;
                
                // For You tab
                $foryou_url = add_query_arg('tab_type', 'foryou', home_url('/category'));
                $is_foryou_active = ($current_tab === 'foryou' && !$current_category_id);
                ?>
                <a href="<?php echo esc_url($foryou_url); ?>" class="tab<?php echo esc_attr($is_foryou_active ? ' active' : ''); ?>"><?php esc_html_e('For You', 'puna-tiktok'); ?></a>
                
                <?php
                // Trending tab
                $trending_url = add_query_arg('tab_type', 'trending', home_url('/category'));
                $is_trending_active = ($current_tab === 'trending' && !$current_category_id) || (!$current_tab && !$current_category_id);
                ?>
                <a href="<?php echo esc_url($trending_url); ?>" class="tab<?php echo esc_attr($is_trending_active ? ' active' : ''); ?>"><?php esc_html_e('Trending', 'puna-tiktok'); ?></a>
                
                <?php
                // Get list of categories that have videos
                $all_categories = get_terms(array(
                    'taxonomy' => 'video_category',
                    'hide_empty' => false,
                ));
                
                if (!is_wp_error($all_categories) && !empty($all_categories)) {
                    foreach ($all_categories as $category) {
                        // Use term count instead of querying - much more efficient
                        if ($category->count > 0) {
                            $category_url = home_url('/category/' . $category->term_id . '/');
                            $is_category_active = ($current_category_id == $category->term_id);
                            echo '<a href="' . esc_url($category_url) . '" class="tab' . esc_attr($is_category_active ? ' active' : '') . '">' . esc_html($category->name) . '</a>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="taxonomy-grid" id="taxonomy-grid">
            <?php
            $displayed_count = 0;
            $target_count = 12;
            
            // Get current tab type and category
            $current_tab = isset($_GET['tab_type']) ? sanitize_text_field($_GET['tab_type']) : 'trending';
            $current_category_id = get_query_var('category_id') ? intval(get_query_var('category_id')) : 0;
            
            // If a specific category is selected, show videos for that category
            if ($current_category_id > 0) {
                $category = get_term($current_category_id, 'video_category');
                if ($category && !is_wp_error($category)) {
                    $category_query = new WP_Query(array(
                        'post_type' => 'video',
                        'posts_per_page' => 50,
                        'post_status' => 'publish',
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'video_category',
                                'field' => 'term_id',
                                'terms' => $category->term_id,
                            ),
                        ),
                    ));
                    
                    if ($category_query->have_posts()) :
                        while ($category_query->have_posts()) : $category_query->the_post();
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
                }
            } elseif ($current_tab === 'foryou') {
                // For You: Latest videos
                $foryou_query = new WP_Query(array(
                    'post_type' => 'video',
                    'posts_per_page' => 50,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC'
                ));
                
                if ($foryou_query->have_posts()) :
                    while ($foryou_query->have_posts()) : $foryou_query->the_post();
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
                // Trending: Query trending videos in 7 days
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
                        array(
                            'key'     => '_puna_tiktok_youtube_id',
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

