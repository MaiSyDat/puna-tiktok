<?php
/**
 * Author Profile Page Template
 * Trang profile user giống TikTok
 */

get_header();

// Get author info
$author_id = get_queried_object_id();
$author = get_userdata($author_id);
$current_user_id = get_current_user_id();
$is_own_profile = ($current_user_id && $author_id == $current_user_id);

if (!$author) {
    get_template_part('template-parts/404');
    get_footer();
    exit;
}

// Author data
$author_name = puna_tiktok_get_user_display_name($author_id);
$author_username = puna_tiktok_get_user_username($author_id);
$author_bio = get_user_meta($author_id, 'description', true);
$author_url = get_author_posts_url($author_id);

// Get author stats - count only posts with video block
$author_videos_query = new WP_Query(array(
    'post_type'      => 'video',
    'author'         => $author_id,
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC'
));

// Count only posts with video block
$total_videos = 0;
if ($author_videos_query->have_posts()) {
    foreach ($author_videos_query->posts as $video_post) {
        if (has_block('puna/hupuna-tiktok', $video_post->ID)) {
            $total_videos++;
        }
    }
}

wp_reset_postdata();

// Get user's liked videos
$liked_videos = array();
if ($is_own_profile && $current_user_id) {
    $user_liked = get_user_meta($current_user_id, '_puna_tiktok_liked_posts', true);
    if (is_array($user_liked) && !empty($user_liked)) {
        $liked_videos_query = new WP_Query(array(
            'post_type' => 'video',
            'post__in' => $user_liked,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'post__in'
        ));
        $liked_videos = $liked_videos_query->posts;
        wp_reset_postdata();
    }
}
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>
    
    <div class="main-content profile-content">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-wrapper">
                <?php echo puna_tiktok_get_avatar_html($author_id, 116, 'profile-avatar'); ?>
            </div>
            
            <div class="profile-info">
                <h1 class="profile-username"><?php echo esc_html($author_name); ?></h1>
                <h2 class="profile-usernicename">@<?php echo esc_html($author_username); ?></h2>
                <p class="profile-bio"><?php echo esc_html($author_bio ?: 'Chưa có tiểu sử'); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <strong class="stat-number"><?php echo puna_tiktok_format_number($total_videos); ?></strong>
                        <span class="stat-label">Bài đăng</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Profile Tabs-->
        <div class="profile-tabs">
            <button class="profile-tab active" data-tab="videos">
                <i class="fa-solid fa-grid-3"></i> Video
            </button>
            <?php if ($is_own_profile) : ?>
                <button class="profile-tab" data-tab="liked">
                    <i class="fa-solid fa-heart"></i> Đã thích
                </button>
            <?php endif; ?>
        </div>

        <!-- Videos Tab -->
        <div class="profile-videos-section active" id="videos-tab">
            <?php
            // Query author video 
            $videos_query = new WP_Query(array(
                'post_type' => 'video',
                'author' => $author_id,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if ($videos_query->have_posts()) :
            ?>
                <div class="profile-grid">
                    <?php while ($videos_query->have_posts()) : $videos_query->the_post();
                        get_template_part('template-parts/video-card', null, array(
							'card_class' => 'profile-video-card'
						));
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php else : ?>
                <?php 
                puna_tiktok_empty_state(array(
                    'icon' => 'fa-video-slash',
                    'title' => 'Chưa có video',
                    'message' => $is_own_profile ? 'Đăng video đầu tiên của bạn để bắt đầu!' : 'Người dùng này chưa đăng video nào.',
                    'button_url' => $is_own_profile ? puna_tiktok_get_upload_url() : '',
                    'button_text' => $is_own_profile ? 'Tải video lên' : ''
                )); 
                ?>
            <?php endif; ?>
            
            <!-- Liked Videos Tab (only for own profile) -->
            <?php if ($is_own_profile) : ?>
                <div class="profile-videos-section" id="liked-tab" style="display: none;">
                    <?php
                    // Get liked videos for current user
                    $liked_video_ids = puna_tiktok_get_liked_videos($author_id);
                    
                    if (!empty($liked_video_ids)) {
                        $liked_query = new WP_Query(array(
                            'post_type' => 'video',
                            'post__in' => $liked_video_ids,
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'orderby' => 'post__in',
                            'order' => 'DESC'
                        ));
                        
                        if ($liked_query->have_posts()) : ?>
                            <div class="profile-grid">
                                <?php
                                while ($liked_query->have_posts()) : $liked_query->the_post();
                                    get_template_part('template-parts/video-card', null, array(
										'card_class' => 'profile-video-card'
									));
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        <?php else : ?>
                            <?php 
                            puna_tiktok_empty_state(array(
                                'icon' => 'fa-heart',
                                'title' => 'Chưa có video yêu thích',
                                'message' => 'Video bạn thích sẽ xuất hiện ở đây.'
                            )); 
                            ?>
                        <?php endif;
                    } else { ?>
                        <?php 
                        puna_tiktok_empty_state(array(
                            'icon' => 'fa-heart',
                            'title' => 'Chưa có video yêu thích',
                            'message' => 'Video bạn thích sẽ xuất hiện ở đây.'
                        )); 
                        ?>
                    <?php } ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>

