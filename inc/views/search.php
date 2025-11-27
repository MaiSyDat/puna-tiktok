<?php
/**
 * Search View
 * Search results template
 */

if (!defined('ABSPATH')) {
    exit;
}

$search_query = get_search_query();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'top';
$valid_tabs = array('top', 'videos');
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'top';
}

global $withcomments;
$withcomments = 1;
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>
    
    <div class="main-content search-results-page">
        <!-- Search Results Title -->
        <div class="search-results-title">
            <div class="video-top-controls">
                <!-- Sidebar toggle button will be added by JavaScript -->
            </div>
            <h1>
                <?php 
                printf(
                    esc_html__('Search results for "%s"', 'puna-tiktok'),
                    '<span class="search-query">' . esc_html($search_query) . '</span>'
                );
                ?>
            </h1>
        </div>
        
        <!-- Search Tabs -->
        <div class="search-tabs">
            <a href="<?php echo esc_url(add_query_arg(array('s' => $search_query, 'tab' => 'top'), home_url('/'))); ?>" 
               class="search-tab <?php echo $active_tab === 'top' ? 'active' : ''; ?>" 
               data-tab="top">
                <?php esc_html_e('Top', 'puna-tiktok'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg(array('s' => $search_query, 'tab' => 'videos'), home_url('/'))); ?>" 
               class="search-tab <?php echo $active_tab === 'videos' ? 'active' : ''; ?>" 
               data-tab="videos">
                <?php esc_html_e('Video', 'puna-tiktok'); ?>
            </a>
        </div>
        
        <!-- Tab Content -->
        <div class="search-tab-content">
            <?php if ($active_tab === 'top' || $active_tab === 'videos') : ?>
                <?php
                // Query all videos first, then filter by search
                $all_video_args = array(
                    'post_type'      => 'video',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
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
                );
                
                $all_videos_query = new WP_Query($all_video_args);
                $all_video_posts = array();
                
                if ($all_videos_query->have_posts()) {
                    while ($all_videos_query->have_posts()) {
                        $all_videos_query->the_post();
                        if (get_post_type(get_the_ID()) === 'video') {
                            $all_video_posts[] = get_the_ID();
                        }
                    }
                    wp_reset_postdata();
                }
                
                // Filter theo search query
                $search_lower = mb_strtolower($search_query);
                $matched_posts = array();
                
                foreach ($all_video_posts as $post_id) {
                    $content = mb_strtolower(get_post_field('post_content', $post_id));
                    $excerpt = mb_strtolower(get_post_field('post_excerpt', $post_id));
                    
                    // Check keyword in content, excerpt (not using title)
                    if (strpos($content, $search_lower) !== false || 
                        strpos($excerpt, $search_lower) !== false) {
                        $matched_posts[] = $post_id;
                    }
                }
                
               // Sort: prioritize content match, then sort by date (latest)
                if (!empty($matched_posts)) : 
                    $sorted_posts = array();
                    $other_posts = array();
                    
                    foreach ($matched_posts as $post_id) {
                        $content = mb_strtolower(get_post_field('post_content', $post_id));
                        if (strpos($content, $search_lower) !== false) {
                            $sorted_posts[] = $post_id;
                        } else {
                            $other_posts[] = $post_id;
                        }
                    }
                    
                    // Sort by date (latest)
                    usort($sorted_posts, function($a, $b) {
                        return get_post_time('U', true, $b) - get_post_time('U', true, $a);
                    });
                    usort($other_posts, function($a, $b) {
                        return get_post_time('U', true, $b) - get_post_time('U', true, $a);
                    });
                    
                    $final_posts = array_merge($sorted_posts, $other_posts);
                    $final_posts = array_slice($final_posts, 0, 24); // Limit 24 posts
                    
                    if (!empty($final_posts)) :
                    ?>
                    <div class="search-videos-grid">
                        <?php foreach ($final_posts as $post_id) : 
                                $post_obj = get_post($post_id);
                                setup_postdata($post_obj);
                                
                                $metadata = puna_tiktok_get_video_metadata($post_id);
                                $video_url = $metadata['video_url'];
                                $likes = $metadata['likes'];
                                
                                // Check for featured image
                                $featured_image_url = '';
                                if (has_post_thumbnail($post_id)) {
                                    $featured_image_url = get_the_post_thumbnail_url($post_id, 'medium');
                                }
                                ?>
                                <a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="search-video-item">
                                    <div class="search-video-thumbnail">
                                        <?php if ($featured_image_url) : ?>
                                            <img src="<?php echo esc_url($featured_image_url); ?>" alt="" class="search-video-preview" loading="lazy">
                                        <?php elseif ($video_url) : ?>
                                            <video class="search-video-preview" muted playsinline loading="lazy" data-mega-link="<?php echo esc_url($video_url); ?>">
                                                <!-- Mega.nz video will be loaded via JavaScript -->
                                            </video>
                                        <?php endif; ?>
                                        <div class="search-video-overlay">
                                            <div class="search-video-views">
                                                <?php echo puna_tiktok_get_icon('play', __('Views', 'puna-tiktok')); ?>
                                                <span><?php echo puna_tiktok_format_number($metadata['views']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="search-video-info">
                                        <h3 class="search-video-title"><?php echo esc_html(puna_tiktok_get_video_description($post_id)); ?></h3>
                                        <div class="search-video-meta">
                                            <span class="search-video-author"><?php echo esc_html(puna_tiktok_get_user_display_name($post_obj->post_author)); ?></span>
                                            <span class="search-video-time"><?php printf(esc_html__('%s ago', 'puna-tiktok'), human_time_diff(get_the_time('U', $post_id), current_time('timestamp'))); ?></span>
                                        </div>
                                    </div>
                                </a>
                            <?php 
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>
                    <?php else : ?>
                        <?php 
                        puna_tiktok_empty_state(array(
                            'icon' => 'fa-search',
                            'title' => __('No videos found', 'puna-tiktok'),
                            'message' => sprintf(__('No videos found for "%s"', 'puna-tiktok'), esc_html($search_query))
                        )); 
                        ?>
                    <?php endif; ?>
                <?php else : ?>
                    <?php 
                    puna_tiktok_empty_state(array(
                        'icon' => 'fa-search',
                        'title' => __('No videos found', 'puna-tiktok'),
                        'message' => sprintf(__('No videos found for "%s"', 'puna-tiktok'), esc_html($search_query))
                    )); 
                    ?>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
        
        <!-- Right Sidebar: Suggested Searches -->
        <aside class="search-suggestions-sidebar">
            <h3><?php esc_html_e('Related searches', 'puna-tiktok'); ?></h3>
            <ul class="search-suggestions-list" id="related-searches-list">
                <li class="related-searches-loading"><?php esc_html_e('Loading...', 'puna-tiktok'); ?></li>
            </ul>
        </aside>
    </div>
</div>

