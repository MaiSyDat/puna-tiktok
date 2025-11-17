<?php

/**
 * Meta box helper functions
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get video URL from post meta or attachment
 *
 * @param int|null $post_id Post ID
 * @return string Video URL
 */
function puna_tiktok_get_video_url($post_id = null)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }

    // Check _puna_tiktok_mega_link first (from admin upload)
    $mega_link = get_post_meta($post_id, '_puna_tiktok_mega_link', true);
    if (!empty($mega_link)) {
        return esc_url($mega_link);
    }

    // Check _puna_tiktok_video_url (backward compatibility and frontend upload)
    $video_url_meta = get_post_meta($post_id, '_puna_tiktok_video_url', true);
    if (!empty($video_url_meta)) {
        return esc_url($video_url_meta);
    }

    // Check attachment file
    $video_file_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
    if ($video_file_id) {
        $video_url = wp_get_attachment_url($video_file_id);
        if ($video_url) {
            return $video_url;
        }
    }
    return 'https://v16-webapp.tiktok.com/video-sample.mp4';
}

/**
 * Increment video view count
 *
 * @param int $post_id Post ID
 * @return int New view count
 */
function puna_tiktok_increment_video_views($post_id)
{
    $current_views = get_post_meta($post_id, '_puna_tiktok_video_views', true);
    $new_views = $current_views ? $current_views + 1 : 1;
    update_post_meta($post_id, '_puna_tiktok_video_views', $new_views);
    return $new_views;
}

