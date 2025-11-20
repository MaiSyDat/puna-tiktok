<?php

/**
 * Video Post Type
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Video_Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_video_taxonomies'));
        add_action('init', array($this, 'register_video_post_type'));
        add_action('add_meta_boxes', array($this, 'add_video_meta_boxes'));
        add_action('save_post', array($this, 'save_video_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('manage_video_posts_columns', array($this, 'add_video_columns'));
        add_action('manage_video_posts_custom_column', array($this, 'render_video_columns'), 10, 2);
        add_action('after_switch_theme', array($this, 'flush_rewrite_rules'));
    }
    
    /**
     * Register video taxonomies
     */
    public function register_video_taxonomies() {
        // Register video category taxonomy
        $category_labels = array(
            'name'              => _x('Video Categories', 'taxonomy general name', 'puna-tiktok'),
            'singular_name'     => _x('Video Category', 'taxonomy singular name', 'puna-tiktok'),
            'search_items'      => __('Tìm kiếm danh mục', 'puna-tiktok'),
            'all_items'         => __('Tất cả danh mục', 'puna-tiktok'),
            'parent_item'       => __('Danh mục cha', 'puna-tiktok'),
            'parent_item_colon' => __('Danh mục cha:', 'puna-tiktok'),
            'edit_item'         => __('Chỉnh sửa danh mục', 'puna-tiktok'),
            'update_item'       => __('Cập nhật danh mục', 'puna-tiktok'),
            'add_new_item'      => __('Thêm danh mục mới', 'puna-tiktok'),
            'new_item_name'     => __('Tên danh mục mới', 'puna-tiktok'),
            'menu_name'         => __('Danh mục Video', 'puna-tiktok'),
        );

        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'video-category'),
            'show_in_rest'      => false,
        );

        register_taxonomy('video_category', array('video'), $category_args);

        // Register video tag taxonomy
        $tag_labels = array(
            'name'                       => _x('Video Tags', 'taxonomy general name', 'puna-tiktok'),
            'singular_name'              => _x('Video Tag', 'taxonomy singular name', 'puna-tiktok'),
            'search_items'               => __('Tìm kiếm tag', 'puna-tiktok'),
            'popular_items'              => __('Tag phổ biến', 'puna-tiktok'),
            'all_items'                  => __('Tất cả tag', 'puna-tiktok'),
            'edit_item'                  => __('Chỉnh sửa tag', 'puna-tiktok'),
            'update_item'                => __('Cập nhật tag', 'puna-tiktok'),
            'add_new_item'               => __('Thêm tag mới', 'puna-tiktok'),
            'new_item_name'              => __('Tên tag mới', 'puna-tiktok'),
            'separate_items_with_commas' => __('Phân cách tag bằng dấu phẩy', 'puna-tiktok'),
            'add_or_remove_items'        => __('Thêm hoặc xóa tag', 'puna-tiktok'),
            'choose_from_most_used'      => __('Chọn từ tag được dùng nhiều nhất', 'puna-tiktok'),
            'not_found'                  => __('Không tìm thấy tag', 'puna-tiktok'),
            'menu_name'                  => __('Tag Video', 'puna-tiktok'),
        );

        $tag_args = array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => array('slug' => 'video-tag'),
            'show_in_rest'          => false,
        );

        register_taxonomy('video_tag', array('video'), $tag_args);
    }

    /**
     * Register video post type
     */
    public function register_video_post_type() {
        $labels = array(
            'name'                  => _x('Videos', 'Post type general name', 'puna-tiktok'),
            'singular_name'         => _x('Video', 'Post type singular name', 'puna-tiktok'),
            'menu_name'             => _x('Videos', 'Admin Menu text', 'puna-tiktok'),
            'name_admin_bar'        => _x('Video', 'Add New on Toolbar', 'puna-tiktok'),
            'add_new'               => __('Thêm mới', 'puna-tiktok'),
            'add_new_item'          => __('Thêm video mới', 'puna-tiktok'),
            'new_item'              => __('Video mới', 'puna-tiktok'),
            'edit_item'             => __('Chỉnh sửa video', 'puna-tiktok'),
            'view_item'             => __('Xem video', 'puna-tiktok'),
            'all_items'             => __('Tất cả videos', 'puna-tiktok'),
            'search_items'          => __('Tìm kiếm videos', 'puna-tiktok'),
            'not_found'             => __('Không tìm thấy videos nào.', 'puna-tiktok'),
            'not_found_in_trash'    => __('Không tìm thấy videos nào trong thùng rác.', 'puna-tiktok'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'menu_position'      => 5, // Below Posts
            'menu_icon'          => 'dashicons-video-alt3',
            'query_var'          => true,
            'rewrite'            => array('slug' => 'video'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'supports'           => array('title', 'editor', 'thumbnail', 'comments'),
            'show_in_rest'       => false,
            'taxonomies'         => array('video_category', 'video_tag'),
        );

        register_post_type('video', $args);
        
        if (get_option('puna_tiktok_video_post_type_registered') !== 'yes') {
            flush_rewrite_rules();
            update_option('puna_tiktok_video_post_type_registered', 'yes');
        }
    }
    
    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
    
    /**
     * Add meta boxes
     */
    public function add_video_meta_boxes() {
        add_meta_box(
            'video_upload_meta_box',
            __('Tải video lên', 'puna-tiktok'),
            array($this, 'render_video_upload_meta_box'),
            'video',
            'normal',
            'high'
        );
    }
    
    /**
     * Render upload meta box
     */
    public function render_video_upload_meta_box($post) {
        wp_nonce_field('puna_tiktok_video_meta', 'puna_tiktok_video_meta_nonce');
        
        $video_url = get_post_meta($post->ID, '_puna_tiktok_video_url', true);
        $mega_link = get_post_meta($post->ID, '_puna_tiktok_mega_link', true);
        $mega_node_id = get_post_meta($post->ID, '_puna_tiktok_mega_node_id', true);
        ?>
        <div class="puna-video-upload-admin">
            <div class="video-upload-section">
                <h3><?php _e('Video File', 'puna-tiktok'); ?></h3>
                
                <div class="video-upload-dropzone" id="videoUploadDropzone">
                    <div class="dropzone-content">
                        <div class="upload-icon">
                            <i class="dashicons dashicons-video-alt3"></i>
                            <i class="dashicons dashicons-arrow-up-alt"></i>
                        </div>
                        <h4><?php _e('Chọn video để tải lên', 'puna-tiktok'); ?></h4>
                        <p><?php _e('Hoặc kéo thả video vào đây', 'puna-tiktok'); ?></p>
                    </div>
                    <button type="button" class="button button-primary" id="selectVideoBtn"><?php _e('Chọn video', 'puna-tiktok'); ?></button>
                    <input type="file" id="videoFileInput" accept="video/*" style="display: none;">
                </div>
                
                <div class="video-preview-container" id="videoPreviewContainer" style="display: none;">
                    <video id="videoPreview" controls style="width: 100%; max-width: 500px; margin-top: 20px;"></video>
                    <div class="video-info" id="videoInfo"></div>
                </div>
                
                <?php if ($video_url || $mega_link): ?>
                <div class="current-video">
                    <h4><?php _e('Video hiện tại:', 'puna-tiktok'); ?></h4>
                    <?php if ($mega_link): ?>
                        <p><strong>MEGA Link:</strong> <a href="<?php echo esc_url($mega_link); ?>" target="_blank"><?php echo esc_html($mega_link); ?></a></p>
                        <input type="hidden" name="mega_link" value="<?php echo esc_attr($mega_link); ?>">
                        <input type="hidden" name="mega_node_id" value="<?php echo esc_attr($mega_node_id); ?>">
                    <?php else: ?>
                        <p><strong>Video URL:</strong> <a href="<?php echo esc_url($video_url); ?>" target="_blank"><?php echo esc_html($video_url); ?></a></p>
                        <input type="hidden" name="video_url" value="<?php echo esc_attr($video_url); ?>">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="upload-progress" id="uploadProgress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <div class="progress-text" id="progressText">0%</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save video meta
     */
    public function save_video_meta($post_id) {
        static $saving = false;
        
        if ($saving) {
            return;
        }
        
        if (!isset($_POST['puna_tiktok_video_meta_nonce']) || !wp_verify_nonce($_POST['puna_tiktok_video_meta_nonce'], 'puna_tiktok_video_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'video') {
            return;
        }
        
        $saving = true;
        
        if (isset($_POST['mega_link'])) {
            $mega_link = esc_url_raw($_POST['mega_link']);
            update_post_meta($post_id, '_puna_tiktok_mega_link', $mega_link);
            update_post_meta($post_id, '_puna_tiktok_video_url', $mega_link);
        }
        
        if (isset($_POST['mega_node_id'])) {
            $mega_node_id = sanitize_text_field($_POST['mega_node_id']);
            update_post_meta($post_id, '_puna_tiktok_mega_node_id', $mega_node_id);
            update_post_meta($post_id, '_puna_tiktok_video_node_id', $mega_node_id);
        }
        
        if (isset($_POST['video_url'])) {
            $video_url = esc_url_raw($_POST['video_url']);
            update_post_meta($post_id, '_puna_tiktok_video_url', $video_url);
            if (empty(get_post_meta($post_id, '_puna_tiktok_mega_link', true))) {
                update_post_meta($post_id, '_puna_tiktok_mega_link', $video_url);
            }
        }
        
        $saving = false;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if ($post_type !== 'video' || !in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        wp_enqueue_script(
            'puna-tiktok-mega-sdk-admin',
            get_template_directory_uri() . '/assets/js/libs/mega.browser.js',
            array(),
            PUNA_TIKTOK_VERSION,
            true
        );
        
        wp_enqueue_script(
            'puna-tiktok-mega-uploader-admin',
            get_template_directory_uri() . '/assets/js/frontend/mega-uploader.js',
            array('puna-tiktok-mega-sdk-admin'),
            PUNA_TIKTOK_VERSION,
            true
        );
        
        wp_enqueue_style(
            'puna-tiktok-video-admin',
            get_template_directory_uri() . '/assets/css/admin/video-admin.css',
            array(),
            PUNA_TIKTOK_VERSION
        );
        
        wp_enqueue_script(
            'puna-tiktok-video-admin',
            get_template_directory_uri() . '/assets/js/admin/video-admin.js',
            array('jquery', 'puna-tiktok-mega-uploader-admin'),
            PUNA_TIKTOK_VERSION,
            true
        );
        
        wp_localize_script('puna-tiktok-video-admin', 'puna_tiktok_video_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('puna_tiktok_video_upload'),
            'mega' => array(
                'email' => Puna_TikTok_Mega_Config::get_email(),
                'password' => Puna_TikTok_Mega_Config::get_password(),
                'folder' => Puna_TikTok_Mega_Config::get_upload_folder(),
            ),
        ));
    }
    
    /**
     * Add columns
     */
    public function add_video_columns($columns) {
        $new_columns = array();
        $new_columns['cb']          = $columns['cb'];
        $new_columns['title']       = $columns['title'];
        $new_columns['views']       = __('Lượt xem', 'puna-tiktok');
        $new_columns['likes']       = __('Lượt thích', 'puna-tiktok');
        $new_columns['comments']    = __('Bình luận', 'puna-tiktok');
        $new_columns['date']        = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Render columns
     */
    public function render_video_columns($column, $post_id) {
        switch ($column) {
            case 'views':
                $views = get_post_meta($post_id, '_puna_tiktok_video_views', true) ?: 0;
                echo number_format($views);
                break;
            case 'likes':
                $likes = get_post_meta($post_id, '_puna_tiktok_video_likes', true) ?: 0;
                echo number_format($likes);
                break;
            case 'comments':
                $comments_count = get_comments_number($post_id);
                echo number_format($comments_count);
                break;
        }
    }
}

new Puna_TikTok_Video_Post_Type();

