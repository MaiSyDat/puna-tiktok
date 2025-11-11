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
$author_name = $author->display_name;
$author_username = $author->user_login;
$author_avatar = get_avatar_url($author_id, array('size' => 100));
$author_bio = get_user_meta($author_id, 'description', true);
$author_url = get_author_posts_url($author_id);

// Get author stats
$author_videos_query = new WP_Query(array(
    'post_type' => 'post',
    'author' => $author_id,
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_puna_tiktok_video_file_id',
            'compare' => 'EXISTS'
        )
    )
));

$total_videos = $author_videos_query->found_posts;
$total_likes = 0;
$total_followers = get_user_meta($author_id, '_puna_tiktok_followers', true) ?: 0;
$total_following = get_user_meta($author_id, '_puna_tiktok_following', true) ?: 0;

// Calculate total likes from all videos
foreach ($author_videos_query->posts as $video_post) {
    $likes = get_post_meta($video_post->ID, '_puna_tiktok_video_likes', true) ?: 0;
    $total_likes += $likes;
}

wp_reset_postdata();

// Check if current user is following this author
$is_following = false;
if ($current_user_id && !$is_own_profile) {
    $user_following = get_user_meta($current_user_id, '_puna_tiktok_following_list', true);
    if (is_array($user_following)) {
        $is_following = in_array($author_id, $user_following);
    }
}

// Get user's liked videos
$liked_videos = array();
if ($is_own_profile && $current_user_id) {
    $user_liked = get_user_meta($current_user_id, '_puna_tiktok_liked_posts', true);
    if (is_array($user_liked) && !empty($user_liked)) {
        $liked_videos_query = new WP_Query(array(
            'post_type' => 'post',
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
                <img src="<?php echo esc_url($author_avatar); ?>" 
                     alt="<?php echo esc_attr($author_name); ?>" 
                     class="profile-avatar">
            </div>
            
            <div class="profile-info">
                <h1 class="profile-username"><?php echo esc_html($author_name); ?></h1>
                <h2 class="profile-usernicename"><?php echo esc_html($author_username); ?></h2>
                <p class="profile-bio"><?php echo esc_html($author_bio ?: 'Chưa có tiểu sử'); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <strong class="stat-number"><?php echo puna_tiktok_format_number($total_videos); ?></strong>
                        <span class="stat-label">Bài đăng</span>
                    </div>
                    <div class="stat-item">
                        <strong class="stat-number"><?php echo puna_tiktok_format_number($total_followers); ?></strong>
                        <span class="stat-label">Người theo dõi</span>
                    </div>
                    <div class="stat-item">
                        <strong class="stat-number"><?php echo puna_tiktok_format_number($total_following); ?></strong>
                        <span class="stat-label">Đang theo dõi</span>
                    </div>
                </div>
                
                <?php if (!$is_own_profile && $current_user_id) : ?>
                    <button class="profile-follow-btn edit-profile-btn <?php echo $is_following ? 'following' : ''; ?>" 
                            data-user-id="<?php echo esc_attr($author_id); ?>" 
                            data-is-following="<?php echo $is_following ? '1' : '0'; ?>">
                        <?php if ($is_following) : ?>
                            Đã theo dõi
                        <?php else : ?>
                            Theo dõi
                        <?php endif; ?>
                    </button>
                <?php elseif ($is_own_profile) : ?>
                    <a href="<?php echo admin_url('profile.php'); ?>" class="edit-profile-btn">
                        Chỉnh sửa hồ sơ
                    </a>
                <?php endif; ?>
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
        <div class="profile-videos-section" id="videos-tab">
            <?php
            // Query author video 
            $videos_query = new WP_Query(array(
                'post_type' => 'post',
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
                        if (!has_block('puna/hupuna-tiktok', get_the_ID())) {
                            continue;
                        }
                        
                        $video_id = get_the_ID();
                        $video_url = puna_tiktok_get_video_url($video_id);
                        $views = get_post_meta($video_id, '_puna_tiktok_video_views', true) ?: 0;
                        $likes = get_post_meta($video_id, '_puna_tiktok_video_likes', true) ?: 0;
                    ?>
                        <a href="<?php echo esc_url(get_permalink($video_id)); ?>" class="profile-video-card">
                            <div class="media-wrapper ratio-9x16">
                                <video class="explore-video" muted playsinline>
                                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                </video>
                                <div class="video-overlay">
                                    <div class="play-icon">
                                        <i class="fa-solid fa-play"></i>
                                    </div>
                                </div>
                                <div class="video-stats-overlay">
                                    <div class="stat-badge">
                                        <i class="fa-solid fa-play"></i>
                                        <span><?php echo puna_tiktok_format_number($views); ?></span>
                                    </div>
                                    <div class="stat-badge">
                                        <i class="fa-solid fa-heart"></i>
                                        <span><?php echo puna_tiktok_format_number($likes); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="video-title"><?php echo esc_html(get_the_title()); ?></div>
                        </a>
                    <?php 
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            <?php else : ?>
                <div class="no-videos-message">
                    <div class="no-videos-icon">
                        <i class="fa-solid fa-video-slash"></i>
                    </div>
                    <h3>Chưa có video</h3>
                    <p><?php echo $is_own_profile ? 'Đăng video đầu tiên của bạn để bắt đầu!' : 'Người dùng này chưa đăng video nào.'; ?></p>
                    <?php if ($is_own_profile) : ?>
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="upload-video-btn">
                            <i class="fa-solid fa-square-plus"></i> Tải video lên
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            
            <!-- Liked Videos Tab (only for own profile) -->
            <?php if ($is_own_profile) : ?>
                <div class="profile-videos-section" id="liked-tab" style="display: none;">
                    <?php
                    // Get liked videos for current user
                    $liked_video_ids = puna_tiktok_get_liked_videos($author_id);
                    
                    if (!empty($liked_video_ids)) {
                        $liked_query = new WP_Query(array(
                            'post_type' => 'post',
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
                                    if (!has_block('puna/hupuna-tiktok', get_the_ID())) {
                                        continue;
                                    }
                                    $video_url = puna_tiktok_get_video_url();
                                    $views = get_post_meta(get_the_ID(), '_puna_tiktok_video_views', true) ?: 0;
                                    $likes = get_post_meta(get_the_ID(), '_puna_tiktok_video_likes', true) ?: 0;
                                ?>
                                    <a href="<?php the_permalink(); ?>" class="profile-video-card">
                                        <div class="media-wrapper ratio-9x16">
                                            <video class="explore-video" muted playsinline>
                                                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                                            </video>
                                            <div class="video-overlay">
                                                <div class="play-icon">
                                                    <i class="fa-solid fa-play"></i>
                                                </div>
                                            </div>
                                            <div class="video-stats-overlay">
                                                <div class="stat-badge">
                                                    <i class="fa-solid fa-play"></i>
                                                    <span><?php echo puna_tiktok_format_number($views); ?></span>
                                                </div>
                                                <div class="stat-badge liked">
                                                    <i class="fa-solid fa-heart"></i>
                                                    <span><?php echo puna_tiktok_format_number($likes); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="video-title"><?php echo esc_html(get_the_title()); ?></div>
                                    </a>
                                <?php
                                endwhile;
                                wp_reset_postdata();
                                ?>
                            </div>
                        <?php else : ?>
                            <div class="no-videos-message">
                                <div class="no-videos-icon">
                                    <i class="fa-solid fa-heart"></i>
                                </div>
                                <h3>Chưa có video yêu thích</h3>
                                <p>Video bạn thích sẽ xuất hiện ở đây.</p>
                            </div>
                        <?php endif;
                    } else { ?>
                        <div class="no-videos-message">
                            <div class="no-videos-icon">
                                <i class="fa-solid fa-heart"></i>
                            </div>
                            <h3>Chưa có video yêu thích</h3>
                            <p>Video bạn thích sẽ xuất hiện ở đây.</p>
                        </div>
                    <?php } ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>

