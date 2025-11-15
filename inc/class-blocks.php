<?php
/**
 * Gutenberg Blocks
 *
 * @package puna-tiktok
 */

if (!defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Blocks
{

    public function __construct()
    {
        // Register post meta for video data (still needed for AJAX handlers)
        add_action('init', array($this, 'register_post_meta'));
        
        // Register block type for rendering only (existing posts need to render)
        // Block is no longer available in Gutenberg editor - upload is done via frontend
        add_action('init', array($this, 'register_blocks_render_only'));
    }

    /**
     * Register post meta for video data
     * Still needed for AJAX handlers and data storage
     */
    public function register_post_meta()
    {
        register_post_meta('post', '_puna_tiktok_video_file_id', array(
            'single'        => true,
            'type'          => 'integer',
            'show_in_rest'  => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        register_post_meta('post', '_puna_tiktok_video_url', array(
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        register_post_meta('post', '_puna_tiktok_video_node_id', array(
            'single'        => true,
            'type'          => 'string',
            'show_in_rest'  => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));
    }
    
    /**
     * Register block type for rendering only
     * Block is NOT available in Gutenberg editor - only for rendering existing posts
     */
    public function register_blocks_render_only()
    {
        // Register block type without editor script - only for rendering existing posts
        register_block_type('puna/hupuna-tiktok', array(
            'render_callback' => array($this, 'render_hupuna_block'),
            'attributes'      => array(
                'videoId' => array(
                    'type'   => 'number',
                    'source' => 'meta',
                    'meta'   => '_puna_tiktok_video_file_id',
                ),
                'videoUrl' => array(
                    'type'   => 'string',
                    'source' => 'meta',
                    'meta'   => '_puna_tiktok_video_url',
                ),
                'videoNodeId' => array(
                    'type'   => 'string',
                    'source' => 'meta',
                    'meta'   => '_puna_tiktok_video_node_id',
                ),
            ),
        ));
    }

    /**
     * render_callback
     *
     * @param array $attributes
     * @param string $content
     * @param WP_Block $block
     * @return string
     */
    public function render_hupuna_block($attributes, $content, $block)
    {
        $post_id = isset($block->context['postId']) ? intval($block->context['postId']) : 0;
        if (!$post_id) {
            return '';
        }

        $video_id  = isset($attributes['videoId']) ? intval($attributes['videoId']) : 0;
        $video_url = '';

        if (!empty($attributes['videoUrl'])) {
            $video_url = esc_url($attributes['videoUrl']);
        }

        if (!$video_url) {
            $video_url = get_post_meta($post_id, '_puna_tiktok_video_url', true);
            if ($video_url) {
                $video_url = esc_url($video_url);
            }
        }

        if (!$video_url && !$video_id) {
            $video_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
        }

        if (!$video_url && $video_id) {
            $attachment_url = wp_get_attachment_url($video_id);
            if ($attachment_url) {
                $video_url = esc_url($attachment_url);
            }
        }

        if (!$video_url) {
            return '';
        }
        
        global $post;
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        setup_postdata($post);

        ob_start();
        
        get_template_part('template-parts/video/content');
        
        $output = ob_get_clean();
        
        wp_reset_postdata();

        return $output;
    }
}

new Puna_TikTok_Blocks();

