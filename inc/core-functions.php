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
 * Optimized: Batch load all meta keys in one query
 */
if (!function_exists('puna_tiktok_get_video_metadata')) {
    function puna_tiktok_get_video_metadata($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!$post_id) {
            return apply_filters('puna_tiktok_get_video_metadata', array(), $post_id);
        }
        
        // Batch load all meta keys in one query for better performance
        $meta_keys = array(
            '_puna_tiktok_video_source',
            '_puna_tiktok_youtube_id',
            '_puna_tiktok_video_views',
            '_puna_tiktok_video_likes',
            '_puna_tiktok_video_shares',
            '_puna_tiktok_video_saves',
            '_puna_tiktok_mega_link',
            '_puna_tiktok_video_url'
        );
        
        $meta_values = array();
        foreach ($meta_keys as $key) {
            $meta_values[$key] = get_post_meta($post_id, $key, true);
        }
        
        $video_source = !empty($meta_values['_puna_tiktok_video_source']) 
            ? $meta_values['_puna_tiktok_video_source'] 
            : 'mega';
        
        $metadata = array(
            'post_id' => $post_id,
            'video_url' => puna_tiktok_get_video_url($post_id),
            'source' => $video_source,
            'youtube_id' => $meta_values['_puna_tiktok_youtube_id'] ?: '',
            'views' => (int) ($meta_values['_puna_tiktok_video_views'] ?: 0),
            'likes' => (int) ($meta_values['_puna_tiktok_video_likes'] ?: 0),
            'shares' => (int) ($meta_values['_puna_tiktok_video_shares'] ?: 0),
            'saves' => (int) ($meta_values['_puna_tiktok_video_saves'] ?: 0),
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
                ),
                array(
                    'key' => '_puna_tiktok_youtube_id',
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
 * Disable WordPress user registration
 */
if (!function_exists('puna_tiktok_disable_registration')) {
    function puna_tiktok_disable_registration() {
        add_filter('option_users_can_register', '__return_false');
        add_action('login_init', function() {
            if (isset($_GET['action']) && $_GET['action'] === 'register') {
                wp_redirect(wp_login_url());
                exit;
            }
        });
    }
    puna_tiktok_disable_registration();
}

/**
 * Get avatar HTML
 * Users/Admin: Always use logo
 * Guests: Use initials with colored background
 */
if (!function_exists('puna_tiktok_get_avatar_html')) {
    function puna_tiktok_get_avatar_html($user_id_or_name, $size = 50, $class = '', $guest_id = '') {
        $size = (int) $size;
        $class = esc_attr($class);
        
        // If numeric ID > 0, it's a registered user/admin - always use logo
        if (is_numeric($user_id_or_name) && $user_id_or_name > 0) {
            $user_id = (int) $user_id_or_name;
            $user = get_userdata($user_id);
            
            if ($user) {
                $display_name = puna_tiktok_get_user_display_name($user_id);
                
                // Always use logo for users/admin
                $logo_url = get_template_directory_uri() . '/assets/images/logos/hupuna-logo-800.png';
                
                $html = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($display_name) . '" class="' . $class . '" style="width: ' . $size . 'px; height: ' . $size . 'px; object-fit: contain; border-radius: 50%;">';
                return apply_filters('puna_tiktok_get_avatar_html_user', $html, $user_id, $size, $class);
            }
        }
        
        $name = '';
        if (is_string($user_id_or_name)) {
            $name = $user_id_or_name;
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
 * Optimized: Early return pattern to reduce nesting
 */
if (!function_exists('puna_tiktok_get_video_url')) {
    function puna_tiktok_get_video_url($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!$post_id) {
            return apply_filters('puna_tiktok_get_video_url', '', $post_id);
        }

        // Batch load meta keys for better performance
        $video_source = get_post_meta($post_id, '_puna_tiktok_video_source', true);
        $youtube_id = get_post_meta($post_id, '_puna_tiktok_youtube_id', true);
        $mega_link = get_post_meta($post_id, '_puna_tiktok_mega_link', true);
        $video_url_meta = get_post_meta($post_id, '_puna_tiktok_video_url', true);
        
        // Early return: YouTube source
        if ($video_source === 'youtube' && !empty($youtube_id)) {
            $url = 'https://www.youtube.com/embed/' . esc_attr($youtube_id);
            return apply_filters('puna_tiktok_get_video_url_youtube', $url, $post_id, $youtube_id);
        }

        // Early return: Local source
        if ($video_source === 'local' && !empty($video_url_meta)) {
            $url = esc_url($video_url_meta);
            return apply_filters('puna_tiktok_get_video_url_local', $url, $post_id);
        }

        // Early return: Mega link
        if (!empty($mega_link)) {
            $url = esc_url($mega_link);
            return apply_filters('puna_tiktok_get_video_url_mega', $url, $post_id);
        }

        // Early return: Video URL meta
        if (!empty($video_url_meta)) {
            $url = esc_url($video_url_meta);
            return apply_filters('puna_tiktok_get_video_url_meta', $url, $post_id);
        }

        // Backward compatibility: check old meta key
        $old_mega_node_id = get_post_meta($post_id, '_puna_tiktok_video_node_id', true);
        if (!empty($old_mega_node_id) && !empty($video_url_meta) && strpos($video_url_meta, 'mega.nz') !== false) {
            $url = esc_url($video_url_meta);
            return apply_filters('puna_tiktok_get_video_url_legacy', $url, $post_id);
        }

        return apply_filters('puna_tiktok_get_video_url', '', $post_id);
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
            'title' => __('No videos yet', 'puna-tiktok'),
            'message' => __('Upload your first video to get started!', 'puna-tiktok'),
            'button_url' => '',
            'button_text' => '',
            'wrapper_class' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        $args = apply_filters('puna_tiktok_empty_state_args', $args);
        
        $wrapper_class = 'taxonomy-empty-state' . (!empty($args['wrapper_class']) ? ' ' . esc_attr($args['wrapper_class']) : '');
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            <?php 
            // Map Font Awesome icons to SVG icons
            $icon_map = array(
                'fa-video' => 'play',
                'fa-video-slash' => 'play',
                'fa-search' => 'search',
                'fa-user' => 'home',
                'fa-folder' => 'compass',
                'fa-hashtag' => 'tag',
                'fa-tag' => 'tag',
                'fa-house' => 'home',
                'fa-home' => 'home',
            );
            $icon_name = isset($icon_map[$args['icon']]) ? $icon_map[$args['icon']] : 'home';
            echo puna_tiktok_get_icon($icon_name, $args['title']);
            ?>
            <h3><?php echo esc_html($args['title']); ?></h3>
            <p><?php echo esc_html($args['message']); ?></p>
            <?php if (!empty($args['button_url']) && !empty($args['button_text'])) : ?>
                <a href="<?php echo esc_url($args['button_url']); ?>" class="empty-state-btn">
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

/**
 * Get icon URL (supports both SVG and PNG)
 * 
 * @param string $icon_name Icon filename without extension (e.g., 'heart' for heart.svg or heart.png)
 * @return string Icon URL or empty string if not found
 */
if (!function_exists('puna_tiktok_get_icon_url')) {
    function puna_tiktok_get_icon_url($icon_name) {
        // Remove extension if provided
        $icon_name = preg_replace('/\.(svg|png)$/i', '', $icon_name);
        
        // Try SVG first, then PNG
        $extensions = array('svg', 'png');
        
        foreach ($extensions as $ext) {
            $icon_path = get_template_directory() . '/assets/images/icons/' . $icon_name . '.' . $ext;
            $icon_url = get_template_directory_uri() . '/assets/images/icons/' . $icon_name . '.' . $ext;
            
            if (file_exists($icon_path)) {
                return apply_filters('puna_tiktok_icon_url', $icon_url, $icon_name);
            }
        }
        
        // Return empty if not found
        return apply_filters('puna_tiktok_icon_url', '', $icon_name);
    }
}

/**
 * Get icon HTML
 * 
 * @param string $icon_name Icon filename without extension (e.g., 'heart' for heart.svg or heart.png)
 * @param string $alt Alt text for the icon
 * @param string $class Additional CSS classes
 * @return string Icon HTML img tag or empty string if not found
 */
if (!function_exists('puna_tiktok_get_icon')) {
    function puna_tiktok_get_icon($icon_name, $alt = '', $class = '') {
        $icon_url = puna_tiktok_get_icon_url($icon_name);
        
        if (empty($icon_url)) {
            return '';
        }
        
        // Determine if it's SVG or PNG
        $is_svg = (substr($icon_url, -4) === '.svg');
        $icon_class = $is_svg ? 'icon-svg' : 'icon-img';
        
        // Add additional classes if provided
        if (!empty($class)) {
            $icon_class .= ' ' . esc_attr($class);
        }
        
        return '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($alt) . '" class="' . $icon_class . '">';
    }
}

/**
 * Get logo URL (supports both SVG and PNG)
 */
if (!function_exists('puna_tiktok_get_logo_url')) {
    function puna_tiktok_get_logo_url() {
        $logo_url = get_theme_mod('sidebar_logo', '');
        
        // If no custom logo, check for default logo files
        if (empty($logo_url)) {
            $logo_dir = get_template_directory() . '/assets/images/logos/';
            $logo_uri = get_template_directory_uri() . '/assets/images/logos/';
            
            // Try PNG first, then SVG
            $extensions = array('png', 'svg');
            foreach ($extensions as $ext) {
                $logo_files = glob($logo_dir . '*.' . $ext);
                if (!empty($logo_files)) {
                    $logo_file = basename($logo_files[0]);
                    $logo_url = $logo_uri . $logo_file;
                    break;
                }
            }
        }
        
        return apply_filters('puna_tiktok_logo_url', $logo_url);
    }
}

/**
 * Get toast messages
 * All messages use text domain for translation
 */
if (!function_exists('puna_tiktok_get_toast_messages')) {
    function puna_tiktok_get_toast_messages() {
        static $cached_messages = null;
        
        if ($cached_messages !== null) {
            return apply_filters('puna_tiktok_toast_messages', $cached_messages);
        }
        
        $messages = array(
            // Video actions
            'video_liked' => __('Video liked', 'puna-tiktok'),
            'video_unliked' => __('Video unliked', 'puna-tiktok'),
            'video_saved' => __('Video saved', 'puna-tiktok'),
            'video_unsaved' => __('Video unsaved', 'puna-tiktok'),
            'video_shared' => __('Video shared', 'puna-tiktok'),
            'video_reported' => __('Thank you for reporting. We will review this video.', 'puna-tiktok'),
            'video_deleted' => __('Video deleted', 'puna-tiktok'),
            
            // Comments
            'comment_added' => __('Comment added', 'puna-tiktok'),
            'comment_deleted' => __('Comment deleted', 'puna-tiktok'),
            'comment_liked' => __('Comment liked', 'puna-tiktok'),
            'comment_unliked' => __('Comment unliked', 'puna-tiktok'),
            'comment_reported' => __('Comment reported', 'puna-tiktok'),
            
            // Errors
            'error_generic' => __('An error occurred. Please try again.', 'puna-tiktok'),
            'error_not_logged_in' => __('Please log in to perform this action.', 'puna-tiktok'),
            'error_permission' => __('You do not have permission to perform this action.', 'puna-tiktok'),
            'error_video_not_found' => __('Video not found.', 'puna-tiktok'),
            'error_comment_not_found' => __('Comment not found.', 'puna-tiktok'),
            
            // Success
            'success_generic' => __('Success!', 'puna-tiktok'),
            'success_saved' => __('Saved successfully!', 'puna-tiktok'),
            
            // Info
            'info_loading' => __('Loading...', 'puna-tiktok'),
            'info_processing' => __('Processing...', 'puna-tiktok'),
            
            // Warning
            'warning_confirm' => __('Are you sure?', 'puna-tiktok'),
            'warning_clear_history' => __('Are you sure you want to clear all search history?', 'puna-tiktok'),
            'warning_delete_video' => __('Are you sure you want to delete this video? This action cannot be undone.', 'puna-tiktok'),
            
            // History
            'history_cleared' => __('Search history cleared', 'puna-tiktok'),
        );
        
        $cached_messages = $messages;
        return apply_filters('puna_tiktok_toast_messages', $messages);
    }
}

/**
 * Get toast message by key
 */
if (!function_exists('puna_tiktok_get_toast_message')) {
    function puna_tiktok_get_toast_message($key, $default = '') {
        $messages = puna_tiktok_get_toast_messages();
        return isset($messages[$key]) ? $messages[$key] : $default;
    }
}

