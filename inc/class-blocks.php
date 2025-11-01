<?php
/**
 * Gutenberg Blocks (OOP)
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
        add_action('init', array($this, 'register_post_meta'));
        add_action('init', array($this, 'register_blocks'));
        add_filter('block_categories_all', array($this, 'register_block_category'), 10, 2);
    }

    public function register_block_category($categories, $post)
    {
        $exists = false;
        foreach ($categories as $cat) {
            if (isset($cat['slug']) && $cat['slug'] === 'puna') {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $categories[] = array(
                'slug'  => 'puna',
                'title' => __('Puna', 'puna-tiktok'),
                'icon'  => null,
            );
        }
        return $categories;
    }

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
    }

    public function register_blocks()
    {
        $script_rel_path = '/assets/js/backend/hupuna-tiktok-block.js';
        $script_path = PUNA_TIKTOK_THEME_DIR . $script_rel_path;
        $script_uri  = PUNA_TIKTOK_THEME_URI . $script_rel_path;
        $script_ver  = file_exists($script_path) ? filemtime($script_path) : PUNA_TIKTOK_VERSION;
        
        wp_register_script(
            'puna-tiktok-hupuna-block',
            $script_uri,
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-data'),
            $script_ver,
            true
        );

        register_block_type('puna/hupuna-tiktok', array(
            'editor_script'   => 'puna-tiktok-hupuna-block',
            'render_callback' => array($this, 'render_hupuna_block'),
            'attributes'      => array(
                'videoId' => array(
                    'type'   => 'number',
                    'source' => 'meta',
                    'meta'   => '_puna_tiktok_video_file_id',
                ),
                'videoUrl' => array(
                    'type' => 'string',
                ),
            ),
            'category' => 'puna',
            // Yêu cầu context để lấy $post_id
            'uses_context' => [ 'postId', 'postType' ],
        ));
    }

    /**
     * Hàm render_callback ĐÃ ĐƯỢC CẬP NHẬT
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
            return ''; // Không có context của bài đăng
        }

        // Lấy video ID từ attribute (đã được đồng bộ từ meta)
        $video_id = isset($attributes['videoId']) ? intval($attributes['videoId']) : 0;

        // Kiểm tra dự phòng nếu attribute rỗng
        if (!$video_id) {
            $video_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
        }

        // Nếu không có video ID, hoặc video URL không hợp lệ -> không hiển thị gì cả
        if (!$video_id || !wp_get_attachment_url($video_id)) {
            return '';
        }
        
        global $post;
        $post = get_post($post_id); // Lấy đối tượng bài đăng
        if (!$post) {
            return ''; // Bài đăng không tồn tại
        }
        
        setup_postdata($post); // Thiết lập dữ liệu toàn cục

        ob_start();
        
        // Gọi file template part của bạn
        // Đường dẫn 'template-parts/video/content'
        get_template_part('template-parts/video/content');
        
        $output = ob_get_clean();
        
        wp_reset_postdata(); // Dọn dẹp dữ liệu toàn cục

        return $output;
    }
}

new Puna_TikTok_Blocks();

