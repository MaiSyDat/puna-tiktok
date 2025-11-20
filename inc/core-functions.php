<?php
/**
 * Core Functions
 * 
 * Contains reusable functions used throughout the theme.
 * All functions are wrapped with function_exists checks to prevent conflicts.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format number (K, M)
 */
if (!function_exists('puna_tiktok_format_number')) {
    function puna_tiktok_format_number($number) {
        $number = apply_filters('puna_tiktok_format_number_value', $number);
        
        if ($number >= 1000000) {
            $formatted = round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            $formatted = round($number / 1000, 1) . 'K';
        } else {
            $formatted = $number;
        }
        
        return apply_filters('puna_tiktok_format_number', $formatted, $number);
    }
}

/**
 * Check if user liked a video
 */
if (!function_exists('puna_tiktok_is_liked')) {
    function puna_tiktok_is_liked($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_user_logged_in()) {
            return apply_filters('puna_tiktok_is_liked_guest', false, $post_id);
        }
        
        $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
        if (!is_array($liked_posts)) {
            return false;
        }
        
        $is_liked = in_array($post_id, $liked_posts);
        return apply_filters('puna_tiktok_is_liked', $is_liked, $post_id, $user_id);
    }
}

/**
 * Get user's liked videos
 */
if (!function_exists('puna_tiktok_get_liked_videos')) {
    function puna_tiktok_get_liked_videos($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_user_logged_in()) {
            return apply_filters('puna_tiktok_get_liked_videos_guest', array(), $user_id);
        }
        
        $liked_posts = get_user_meta($user_id, '_puna_tiktok_liked_videos', true);
        if (!is_array($liked_posts) || empty($liked_posts)) {
            return array();
        }
        
        return apply_filters('puna_tiktok_get_liked_videos', $liked_posts, $user_id);
    }
}

/**
 * Check if video is saved by user
 */
if (!function_exists('puna_tiktok_is_saved')) {
    function puna_tiktok_is_saved($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_user_logged_in()) {
            return apply_filters('puna_tiktok_is_saved_guest', false, $post_id);
        }
        
        $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
        if (!is_array($saved_posts)) {
            return false;
        }
        
        $is_saved = in_array($post_id, $saved_posts);
        return apply_filters('puna_tiktok_is_saved', $is_saved, $post_id, $user_id);
    }
}

/**
 * Get user's saved videos
 */
if (!function_exists('puna_tiktok_get_saved_videos')) {
    function puna_tiktok_get_saved_videos($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!is_user_logged_in()) {
            return apply_filters('puna_tiktok_get_saved_videos_guest', array(), $user_id);
        }
        
        $saved_posts = get_user_meta($user_id, '_puna_tiktok_saved_videos', true);
        if (!is_array($saved_posts) || empty($saved_posts)) {
            return array();
        }
        
        return apply_filters('puna_tiktok_get_saved_videos', $saved_posts, $user_id);
    }
}

/**
 * Get video metadata
 */
if (!function_exists('puna_tiktok_get_video_metadata')) {
    function puna_tiktok_get_video_metadata($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $metadata = array(
            'post_id' => $post_id,
            'video_url' => puna_tiktok_get_video_url($post_id),
            'views' => (int) get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0,
            'likes' => (int) get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0,
            'shares' => (int) get_post_meta($post_id, '_puna_tiktok_video_shares', true) ?: 0,
            'saves' => (int) get_post_meta($post_id, '_puna_tiktok_video_saves', true) ?: 0,
            'comments' => (int) get_comments_number($post_id) ?: 0
        );
        
        return apply_filters('puna_tiktok_get_video_metadata', $metadata, $post_id);
    }
}

/**
 * Get video query
 */
if (!function_exists('puna_tiktok_get_video_query')) {
    function puna_tiktok_get_video_query($args = array()) {
        $defaults = array(
            'post_type' => 'video',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_puna_tiktok_mega_link',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_puna_tiktok_video_url',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $query_args = wp_parse_args($args, $defaults);
        
        if (!empty($args['meta_query'])) {
            $query_args['meta_query'] = $args['meta_query'];
        }
        
        if (!empty($args['tag_id'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'video_tag',
                    'field' => 'term_id',
                    'terms' => $args['tag_id'],
                    'operator' => 'IN'
                )
            );
        }
        
        if (!empty($args['author_id'])) {
            $query_args['author'] = $args['author_id'];
        }
        
        if (!empty($args['post__in'])) {
            $query_args['post__in'] = $args['post__in'];
            if (empty($query_args['orderby']) || $query_args['orderby'] === 'date') {
                $query_args['orderby'] = 'post__in';
            }
        }
        
        $query_args = apply_filters('puna_tiktok_get_video_query_args', $query_args, $args);
        
        return new WP_Query($query_args);
    }
}

/**
 * Get user display name
 */
if (!function_exists('puna_tiktok_get_user_display_name')) {
    function puna_tiktok_get_user_display_name($user_id = null) {
        if (!$user_id) {
            $user_id = get_the_author_meta('ID');
        }
        
        if (!$user_id) {
            return apply_filters('puna_tiktok_get_user_display_name_empty', '', $user_id);
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return apply_filters('puna_tiktok_get_user_display_name_invalid', '', $user_id);
        }
        
        // Use display_name, fallback to user_nicename
        $display_name = $user->display_name;
        if (empty($display_name)) {
            $display_name = $user->user_nicename;
        }
        
        return apply_filters('puna_tiktok_get_user_display_name', $display_name, $user_id);
    }
}

/**
 * Get user username
 */
if (!function_exists('puna_tiktok_get_user_username')) {
    function puna_tiktok_get_user_username($user_id = null) {
        if (!$user_id) {
            $user_id = get_the_author_meta('ID');
        }
        
        if (!$user_id) {
            return apply_filters('puna_tiktok_get_user_username_empty', '', $user_id);
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return apply_filters('puna_tiktok_get_user_username_invalid', '', $user_id);
        }
        
        $username = $user->user_nicename;
        return apply_filters('puna_tiktok_get_user_username', $username, $user_id);
    }
}

/**
 * Get avatar HTML
 */
if (!function_exists('puna_tiktok_get_avatar_html')) {
    function puna_tiktok_get_avatar_html($user_id_or_name, $size = 50, $class = '', $guest_id = '') {
        $size = (int) $size;
        $class = esc_attr($class);
        
        if (is_numeric($user_id_or_name) && $user_id_or_name > 0) {
            $user_id = (int) $user_id_or_name;
            $user = get_userdata($user_id);
            
            if ($user) {
                $avatar_url = get_avatar_url($user_id, array('size' => $size));
                $display_name = puna_tiktok_get_user_display_name($user_id);
                $html = '<img src="' . esc_url($avatar_url) . '" alt="' . esc_attr($display_name) . '" class="' . $class . '" style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: cover; border-radius: 50%;">';
                return apply_filters('puna_tiktok_get_avatar_html_user', $html, $user_id, $size, $class);
            }
        }
        
        $name = '';
        if (is_string($user_id_or_name)) {
            $name = $user_id_or_name;
        } elseif (is_numeric($user_id_or_name) && $user_id_or_name == 0) {
            $name = 'Guest';
        } else {
            $name = 'Guest';
        }
        
        if (empty($name)) {
            $name = 'Guest';
        }
        
        $initials = puna_tiktok_get_user_initials($name, $guest_id);
        $bg_color = puna_tiktok_get_avatar_color($name . $guest_id);
        
        $html = '<div class="avatar-initials ' . $class . '" style="width: ' . $size . 'px; height: ' . $size . 'px; background-color: ' . $bg_color . '; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: ' . ($size * 0.4) . 'px;">' . esc_html($initials) . '</div>';
        return apply_filters('puna_tiktok_get_avatar_html_guest', $html, $name, $size, $class, $guest_id);
    }
}

/**
 * Get user initials
 */
if (!function_exists('puna_tiktok_get_user_initials')) {
    function puna_tiktok_get_user_initials($name, $guest_id = '') {
        $name = trim($name);
        if (empty($name)) {
            return apply_filters('puna_tiktok_get_user_initials_empty', 'GU', $guest_id);
        }
        
        $first_char = mb_substr($name, 0, 1);
        
        if (!empty($guest_id)) {
            $id_part = str_replace('guest_', '', $guest_id);
            $last_two = mb_substr($id_part, -2, 2);
            $initials = mb_strtoupper($first_char . $last_two);
            return apply_filters('puna_tiktok_get_user_initials_guest', $initials, $name, $guest_id);
        }
        
        $name = preg_replace('/\s+/', ' ', $name);
        $words = explode(' ', $name);
        
        if (count($words) >= 2) {
            $first = mb_substr($words[0], 0, 1);
            $last = mb_substr($words[count($words) - 1], 0, 1);
            $initials = mb_strtoupper($first . $last);
        } else {
            $first = mb_substr($name, 0, 1);
            $last = mb_substr($name, -1, 1);
            $initials = mb_strtoupper($first . $last);
        }
        
        return apply_filters('puna_tiktok_get_user_initials', $initials, $name, $guest_id);
    }
}

/**
 * Get avatar color
 */
if (!function_exists('puna_tiktok_get_avatar_color')) {
    function puna_tiktok_get_avatar_color($name) {
        $colors = array(
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
            '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B739', '#52BE80',
            '#E74C3C', '#3498DB', '#9B59B6', '#1ABC9C', '#F39C12',
            '#E67E22', '#34495E', '#16A085', '#27AE60', '#2980B9'
        );
        
        $colors = apply_filters('puna_tiktok_avatar_colors', $colors);
        
        $hash = md5($name);
        $index = hexdec(substr($hash, 0, 2)) % count($colors);
        $color = $colors[$index];
        
        return apply_filters('puna_tiktok_get_avatar_color', $color, $name);
    }
}

/**
 * Get video URL
 */
if (!function_exists('puna_tiktok_get_video_url')) {
    function puna_tiktok_get_video_url($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        // All videos are Mega videos - prioritize Mega link
        $mega_link = get_post_meta($post_id, '_puna_tiktok_mega_link', true);
        if (!empty($mega_link)) {
            $url = esc_url($mega_link);
            return apply_filters('puna_tiktok_get_video_url_mega', $url, $post_id);
        }

        // Fallback to video_url (which should also be Mega link)
        $video_url_meta = get_post_meta($post_id, '_puna_tiktok_video_url', true);
        if (!empty($video_url_meta)) {
            $url = esc_url($video_url_meta);
            return apply_filters('puna_tiktok_get_video_url_meta', $url, $post_id);
        }

        // Backward compatibility: check old meta key
        $old_mega_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
        if (!empty($old_mega_node_id)) {
            $old_mega_link = get_post_meta($post_id, '_puna_tiktok_video_url', true);
            if (!empty($old_mega_link) && strpos($old_mega_link, 'mega.nz') !== false) {
                $url = esc_url($old_mega_link);
                return apply_filters('puna_tiktok_get_video_url_legacy', $url, $post_id);
            }
        }

        $url = '';
        return apply_filters('puna_tiktok_get_video_url', $url, $post_id);
    }
}

/**
 * Increment video views
 */
if (!function_exists('puna_tiktok_increment_video_views')) {
    function puna_tiktok_increment_video_views($post_id) {
        $current_views = get_post_meta($post_id, '_puna_tiktok_video_views', true);
        $new_views = $current_views ? $current_views + 1 : 1;
        
        $new_views = apply_filters('puna_tiktok_increment_video_views_before_update', $new_views, $post_id, $current_views);
        
        update_post_meta($post_id, '_puna_tiktok_video_views', $new_views);
        
        return apply_filters('puna_tiktok_increment_video_views', $new_views, $post_id);
    }
}

/**
 * Render empty state
 */
if (!function_exists('puna_tiktok_empty_state')) {
    function puna_tiktok_empty_state($args = array()) {
        $defaults = array(
            'icon' => 'fa-video',
            'title' => 'Chưa có video nào',
            'message' => 'Hãy đăng video đầu tiên của bạn để bắt đầu!',
            'button_url' => '',
            'button_text' => '',
            'wrapper_class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        $args = apply_filters('puna_tiktok_empty_state_args', $args);
        
        $wrapper_class = 'taxonomy-empty-state' . (!empty($args['wrapper_class']) ? ' ' . esc_attr($args['wrapper_class']) : '');
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>" style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
            <i class="fa-solid <?php echo esc_attr($args['icon']); ?>" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;"><?php echo esc_html($args['title']); ?></h3>
            <p style="color: #999;"><?php echo esc_html($args['message']); ?></p>
            <?php if (!empty($args['button_url']) && !empty($args['button_text']) && current_user_can('manage_options')) : ?>
                <a href="<?php echo esc_url($args['button_url']); ?>" class="btn-primary" style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #fe2c55; color: #fff; text-decoration: none; border-radius: 4px;">
                    <?php echo esc_html($args['button_text']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        do_action('puna_tiktok_empty_state_after', $args);
    }
}

/**
 * Get video description (caption)
 */
if (!function_exists('puna_tiktok_get_video_description')) {
    function puna_tiktok_get_video_description($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $description = get_post_field('post_content', $post_id);
        
        // Clean up description
        if (!empty($description)) {
            // Remove hashtags
            $description = preg_replace('/#[\p{L}\p{N}_]+/u', '', $description);
            // Normalize whitespace
            $description = preg_replace('/\s+/', ' ', trim($description));
            // Strip HTML tags
            $description = strip_tags($description);
        }
        
        // If empty, try excerpt
        if (empty(trim($description))) {
            $description = get_post_field('post_excerpt', $post_id);
            if (!empty($description)) {
                $description = strip_tags($description);
                $description = trim($description);
            }
        }
        
        return apply_filters('puna_tiktok_get_video_description', $description, $post_id);
    }
}

