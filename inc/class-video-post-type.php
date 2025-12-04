<?php

/**
 * Video Post Type
 */

if (! defined('ABSPATH')) {
    exit;
}

class Puna_TikTok_Video_Post_Type {
    
    /**
     * Initialize hooks and actions
     */
    public function __construct() {
        add_action('init', array($this, 'register_video_taxonomies'));
        add_action('init', array($this, 'register_video_post_type'));
        add_action('add_meta_boxes', array($this, 'add_video_meta_boxes'));
        add_action('save_post', array($this, 'save_video_meta'));
        add_filter('manage_video_posts_columns', array($this, 'add_video_columns'));
        add_action('manage_video_posts_custom_column', array($this, 'render_video_columns'), 10, 2);
        add_action('after_switch_theme', array($this, 'flush_rewrite_rules'));
        add_action('post_edit_form_tag', array($this, 'add_form_enctype'));
        add_action('wp_insert_post', array($this, 'handle_new_post_file_upload'), 10, 3);
    }
    
    /**
     * Add enctype attribute to post form for file uploads
     */
    public function add_form_enctype() {
        global $post_type;
        if (isset($post_type) && $post_type === 'video') {
            echo ' enctype="multipart/form-data"';
        } elseif (isset($_GET['post_type']) && $_GET['post_type'] === 'video') {
            echo ' enctype="multipart/form-data"';
        } elseif (isset($_GET['post']) && get_post_type($_GET['post']) === 'video') {
            echo ' enctype="multipart/form-data"';
        }
    }
    
    /**
     * Handle file upload for new posts
     * Processes uploads when post_id was 0 during save_video_meta
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update
     */
    public function handle_new_post_file_upload($post_id, $post, $update) {
        if ($post->post_type !== 'video' || $update) {
            return;
        }
        
        if (isset($_FILES['puna_video_file']) && !empty($_FILES['puna_video_file']['name']) && 
            isset($_FILES['puna_video_file']['error']) && $_FILES['puna_video_file']['error'] === UPLOAD_ERR_OK) {
            
            $video_source = get_post_meta($post_id, '_puna_tiktok_video_source', true);
            if ($video_source === 'local') {
                return;
            }
            
            $this->process_local_video_upload($post_id);
        }
    }
    
    /**
     * Register video taxonomies (categories and tags)
     */
    public function register_video_taxonomies() {
        $category_labels = array(
            'name'              => _x('Video Categories', 'taxonomy general name', 'puna-tiktok'),
            'singular_name'     => _x('Video Category', 'taxonomy singular name', 'puna-tiktok'),
            'search_items'      => __('Search Categories', 'puna-tiktok'),
            'all_items'         => __('All Categories', 'puna-tiktok'),
            'parent_item'       => __('Parent Category', 'puna-tiktok'),
            'parent_item_colon' => __('Parent Category:', 'puna-tiktok'),
            'edit_item'         => __('Edit Category', 'puna-tiktok'),
            'update_item'       => __('Update Category', 'puna-tiktok'),
            'add_new_item'      => __('Add New Category', 'puna-tiktok'),
            'new_item_name'     => __('New Category Name', 'puna-tiktok'),
            'menu_name'         => __('Video Categories', 'puna-tiktok'),
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
        $tag_labels = array(
            'name'                       => _x('Video Tags', 'taxonomy general name', 'puna-tiktok'),
            'singular_name'              => _x('Video Tag', 'taxonomy singular name', 'puna-tiktok'),
            'search_items'               => __('Search Tags', 'puna-tiktok'),
            'popular_items'              => __('Popular Tags', 'puna-tiktok'),
            'all_items'                  => __('All Tags', 'puna-tiktok'),
            'edit_item'                  => __('Edit Tag', 'puna-tiktok'),
            'update_item'                => __('Update Tag', 'puna-tiktok'),
            'add_new_item'               => __('Add New Tag', 'puna-tiktok'),
            'new_item_name'              => __('New Tag Name', 'puna-tiktok'),
            'separate_items_with_commas' => __('Separate tags with commas', 'puna-tiktok'),
            'add_or_remove_items'        => __('Add or remove tags', 'puna-tiktok'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'puna-tiktok'),
            'not_found'                  => __('No tags found', 'puna-tiktok'),
            'menu_name'                  => __('Video Tags', 'puna-tiktok'),
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
     * Register video custom post type
     */
    public function register_video_post_type() {
        $labels = array(
            'name'                  => _x('Videos', 'Post type general name', 'puna-tiktok'),
            'singular_name'         => _x('Video', 'Post type singular name', 'puna-tiktok'),
            'menu_name'             => _x('Videos', 'Admin Menu text', 'puna-tiktok'),
            'name_admin_bar'        => _x('Video', 'Add New on Toolbar', 'puna-tiktok'),
            'add_new'               => __('Add New', 'puna-tiktok'),
            'add_new_item'          => __('Add New Video', 'puna-tiktok'),
            'new_item'              => __('New Video', 'puna-tiktok'),
            'edit_item'             => __('Edit Video', 'puna-tiktok'),
            'view_item'             => __('View Video', 'puna-tiktok'),
            'all_items'             => __('All Videos', 'puna-tiktok'),
            'search_items'          => __('Search Videos', 'puna-tiktok'),
            'not_found'             => __('No videos found.', 'puna-tiktok'),
            'not_found_in_trash'    => __('No videos found in Trash.', 'puna-tiktok'),
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
     * Flush rewrite rules on theme switch
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
    
    /**
     * Register video upload meta box
     */
    public function add_video_meta_boxes() {
        add_meta_box(
            'video_upload_meta_box',
            __('Upload Video', 'puna-tiktok'),
            array($this, 'render_video_upload_meta_box'),
            'video',
            'normal',
            'high'
        );
    }
    
    /**
     * Render video upload meta box with tabs
     * Displays three tabs: Local Upload, Mega Video, YouTube Embed
     * 
     * @param WP_Post $post Current post object
     */
    public function render_video_upload_meta_box($post) {
        wp_nonce_field('puna_tiktok_video_meta', 'puna_tiktok_video_meta_nonce');
        
        $video_url = get_post_meta($post->ID, '_puna_tiktok_video_url', true);
        $mega_link = get_post_meta($post->ID, '_puna_tiktok_mega_link', true);
        $mega_node_id = get_post_meta($post->ID, '_puna_tiktok_mega_node_id', true);
        $youtube_id = get_post_meta($post->ID, '_puna_tiktok_youtube_id', true);
        $video_source = get_post_meta($post->ID, '_puna_tiktok_video_source', true);
        
        // Determine active tab
        // On add-new: default to 'upload'
        // On edit: check video_source
        $is_new_post = (empty($post->ID) || get_post_status($post->ID) === 'auto-draft');
        $active_tab = 'upload'; // Default to upload tab
        
        if (!$is_new_post) {
        if ($video_source === 'youtube' || $youtube_id) {
            $active_tab = 'youtube';
            } elseif ($video_source === 'mega' || $mega_link) {
                $active_tab = 'mega';
            } elseif ($video_source === 'local' || (empty($video_source) && !empty($video_url) && strpos($video_url, wp_upload_dir()['baseurl']) !== false)) {
                $active_tab = 'upload';
            }
        }
        
        // Get YouTube URL if exists
        $youtube_url = '';
        if ($youtube_id) {
            $youtube_url = 'https://www.youtube.com/watch?v=' . esc_attr($youtube_id);
        }
        ?>
        <div class="puna-video-upload-admin">
            <div class="video-upload-tabs">
                <ul class="video-upload-tabs-nav">
                    <li>
                        <a href="#" class="video-upload-tab-link <?php echo $active_tab === 'upload' ? 'active' : ''; ?>" data-tab="upload">
                            <?php _e('UPLOAD VIDEO', 'puna-tiktok'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="video-upload-tab-link <?php echo $active_tab === 'mega' ? 'active' : ''; ?>" data-tab="mega">
                            <?php _e('MEGA VIDEO', 'puna-tiktok'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="video-upload-tab-link <?php echo $active_tab === 'youtube' ? 'active' : ''; ?>" data-tab="youtube">
                            <?php _e('EMBED YOUTUBE', 'puna-tiktok'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Tab 1: UPLOAD VIDEO (Local File) -->
            <div class="video-upload-tab-content <?php echo $active_tab === 'upload' ? 'active' : ''; ?>" id="tab-upload">
                <div class="video-upload-section">
                    <h3><?php _e('Video File', 'puna-tiktok'); ?></h3>
                    
                    <div class="video-upload-dropzone video-upload-dropzone-local" id="videoUploadDropzoneLocal">
                        <div class="dropzone-content">
                            <div class="upload-icon">
                                <i class="dashicons dashicons-video-alt3"></i>
                                <i class="dashicons dashicons-arrow-up-alt"></i>
                            </div>
                            <h4><?php _e('Select video to upload', 'puna-tiktok'); ?></h4>
                            <p><?php _e('Or drag and drop video here', 'puna-tiktok'); ?></p>
                        </div>
                        <label for="puna_video_file" class="button button-primary" style="cursor: pointer;">
                            <?php _e('Select video', 'puna-tiktok'); ?>
                        </label>
                        <input 
                            type="file" 
                            id="puna_video_file" 
                            name="puna_video_file" 
                            accept="video/*" 
                            style="display: none;"
                        />
                    </div>
                    
                    <div class="video-preview-container" id="videoPreviewContainerLocal">
                        <video id="videoPreviewLocal" controls></video>
                        <div class="video-info" id="videoInfoLocal"></div>
                    </div>
                    
                    <?php if ($video_source === 'local' && !empty($video_url)): ?>
                    <div class="current-video">
                        <h4><?php _e('Current Video:', 'puna-tiktok'); ?></h4>
                        <p><strong><?php esc_html_e('Video URL:', 'puna-tiktok'); ?></strong> <a href="<?php echo esc_url($video_url); ?>" target="_blank"><?php echo esc_html($video_url); ?></a></p>
                        <input type="hidden" name="video_url" value="<?php echo esc_attr($video_url); ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab 2: MEGA VIDEO -->
            <div class="video-upload-tab-content <?php echo $active_tab === 'mega' ? 'active' : ''; ?>" id="tab-mega">
                <div class="video-upload-section">
                    <h3><?php _e('Video File', 'puna-tiktok'); ?></h3>
                    
                    <div class="video-upload-dropzone" id="videoUploadDropzone">
                        <div class="dropzone-content">
                            <div class="upload-icon">
                                <i class="dashicons dashicons-video-alt3"></i>
                                <i class="dashicons dashicons-arrow-up-alt"></i>
                            </div>
                            <h4><?php _e('Select video to upload', 'puna-tiktok'); ?></h4>
                            <p><?php _e('Or drag and drop video here', 'puna-tiktok'); ?></p>
                        </div>
                        <button type="button" class="button button-primary" id="selectVideoBtn"><?php _e('Select video', 'puna-tiktok'); ?></button>
                        <input type="file" id="videoFileInput" accept="video/*" style="display: none;">
                    </div>
                    
                    <div class="video-preview-container" id="videoPreviewContainer" style="display: none;">
                        <video id="videoPreview" controls style="width: 100%; max-width: 500px; margin-top: 20px;"></video>
                        <div class="video-info" id="videoInfo"></div>
                    </div>
                    
                    <?php if (($video_url || $mega_link) && $video_source !== 'youtube'): ?>
                    <div class="current-video">
                        <h4><?php _e('Current Video:', 'puna-tiktok'); ?></h4>
                        <?php if ($mega_link): ?>
                            <p><strong><?php esc_html_e('MEGA Link:', 'puna-tiktok'); ?></strong> <a href="<?php echo esc_url($mega_link); ?>" target="_blank"><?php echo esc_html($mega_link); ?></a></p>
                            <input type="hidden" name="mega_link" value="<?php echo esc_attr($mega_link); ?>">
                            <input type="hidden" name="mega_node_id" value="<?php echo esc_attr($mega_node_id); ?>">
                        <?php else: ?>
                            <p><strong><?php esc_html_e('Video URL:', 'puna-tiktok'); ?></strong> <a href="<?php echo esc_url($video_url); ?>" target="_blank"><?php echo esc_html($video_url); ?></a></p>
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
            
            <!-- Tab 2: EMBED YOUTUBE -->
            <div class="video-upload-tab-content <?php echo $active_tab === 'youtube' ? 'active' : ''; ?>" id="tab-youtube">
                <div class="youtube-input-section">
                    <label for="youtube_url_input"><?php _e('YouTube Link', 'puna-tiktok'); ?></label>
                    <input 
                        type="text" 
                        id="youtube_url_input" 
                        name="youtube_url" 
                        value="<?php echo esc_attr($youtube_url); ?>" 
                        placeholder="https://www.youtube.com/watch?v=VIDEO_ID or https://www.youtube.com/shorts/VIDEO_ID"
                        class="regular-text"
                    />
                    <p class="description">
                        <?php _e('Enter YouTube link (supports both regular links and Shorts links). Video ID will be automatically extracted when saving the post.', 'puna-tiktok'); ?>
                    </p>
                    
                    <?php if ($youtube_id): ?>
                    <div class="youtube-preview active">
                        <h4><?php _e('Current YouTube Video:', 'puna-tiktok'); ?></h4>
                        <p><strong><?php esc_html_e('Video ID:', 'puna-tiktok'); ?></strong> <?php echo esc_html($youtube_id); ?></p>
                        <p><strong><?php esc_html_e('Preview:', 'puna-tiktok'); ?></strong> <a href="<?php echo esc_url($youtube_url); ?>" target="_blank"><?php echo esc_html($youtube_url); ?></a></p>
                        <input type="hidden" name="youtube_id" value="<?php echo esc_attr($youtube_id); ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Process local video file upload
     * Handles file validation, upload to media library, and thumbnail generation
     * 
     * @param int $post_id Post ID
     * @return bool Success status
     */
    private function process_local_video_upload($post_id) {
        // Validate file upload
        if (!isset($_FILES['puna_video_file']) || empty($_FILES['puna_video_file']['name']) || 
            !isset($_FILES['puna_video_file']['error']) || $_FILES['puna_video_file']['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $uploaded_file = $_FILES['puna_video_file'];
        
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Add filter to sanitize filename
        add_filter('wp_handle_upload_prefilter', array($this, 'sanitize_video_filename_prefilter'));
        
        // Validate video file type
        $file_type = wp_check_filetype($uploaded_file['name'], wp_get_mime_types());
        $video_extensions = array('mp4', 'webm', 'ogg', 'ogv', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'm4v', '3gp', '3g2');
        $is_video = false;
        
        if (!empty($file_type['ext']) && in_array(strtolower($file_type['ext']), $video_extensions)) {
            $is_video = true;
        } elseif (!empty($uploaded_file['type']) && strpos($uploaded_file['type'], 'video/') === 0) {
            $is_video = true;
        } elseif (!empty($file_type['type']) && strpos($file_type['type'], 'video/') === 0) {
            $is_video = true;
        }
        
        if (!$is_video) {
            remove_filter('wp_handle_upload_prefilter', array($this, 'sanitize_video_filename_prefilter'));
            return false;
        }
        
        // Upload to media library
        $attachment_id = media_handle_upload('puna_video_file', $post_id);
        remove_filter('wp_handle_upload_prefilter', array($this, 'sanitize_video_filename_prefilter'));
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        $video_url = wp_get_attachment_url($attachment_id);
        if (!$video_url) {
            return false;
        }
        
        // Save video metadata
        update_post_meta($post_id, '_puna_tiktok_video_url', $video_url);
        update_post_meta($post_id, '_puna_tiktok_video_source', 'local');
        update_post_meta($post_id, '_puna_tiktok_video_file_id', $attachment_id);
        
        // Generate thumbnail
        $this->generate_video_thumbnail($attachment_id, $post_id);
        
        // Clear conflicting meta
        delete_post_meta($post_id, '_puna_tiktok_youtube_id');
        delete_post_meta($post_id, '_puna_tiktok_mega_link');
        delete_post_meta($post_id, '_puna_tiktok_mega_node_id');
        
        return true;
    }
    
    /**
     * Sanitize and truncate video filename before upload
     * Prevents database errors from overly long filenames
     * 
     * @param array $file File array from upload
     * @return array Modified file array
     */
    public function sanitize_video_filename_prefilter($file) {
        if (!isset($file['name']) || empty($file['name'])) {
            return $file;
        }
        
        $original_name = $file['name'];
        $file_type = wp_check_filetype($original_name, wp_get_mime_types());
        $extension = !empty($file_type['ext']) ? $file_type['ext'] : '';
        
        $sanitized_name = sanitize_file_name($original_name);
        
        // Truncate if too long (max 200 chars)
        $max_length = 200;
        if (strlen($sanitized_name) > $max_length) {
            $name_without_ext = pathinfo($sanitized_name, PATHINFO_FILENAME);
            $ext_length = strlen($extension) + 1;
            $max_name_length = max(50, $max_length - $ext_length);
            $sanitized_name = substr($name_without_ext, 0, $max_name_length) . '.' . $extension;
        }
        
        $file['name'] = $sanitized_name;
        return $file;
    }
    
    /**
     * Generate thumbnail from video file and set as featured image
     * Uses FFmpeg if available, otherwise falls back to alternative methods
     * 
     * @param int $video_attachment_id Video attachment ID
     * @param int $post_id Post ID
     * @return bool Success status
     */
    private function generate_video_thumbnail($video_attachment_id, $post_id) {
        if (!$video_attachment_id || !$post_id) {
            return false;
        }
        
        $video_path = get_attached_file($video_attachment_id);
        if (!file_exists($video_path)) {
            return false;
        }
        
        if (has_post_thumbnail($post_id)) {
            return true;
        }
        
        $thumbnail_id = false;
        
        // Try FFmpeg first
        if (function_exists('shell_exec') && $this->is_ffmpeg_available()) {
            $thumbnail_id = $this->generate_thumbnail_with_ffmpeg($video_path, $post_id);
        }
        
        // Fallback method
        if (!$thumbnail_id) {
            $thumbnail_id = $this->generate_thumbnail_fallback($video_attachment_id, $post_id);
        }
        
        // Set featured image
        if ($thumbnail_id && !is_wp_error($thumbnail_id)) {
            set_post_thumbnail($post_id, $thumbnail_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if FFmpeg is available on server
     * 
     * @return bool
     */
    private function is_ffmpeg_available() {
        $disabled = explode(',', ini_get('disable_functions'));
        if (in_array('shell_exec', $disabled)) {
            return false;
        }
        
        $ffmpeg_path = $this->get_ffmpeg_path();
        if (empty($ffmpeg_path)) {
            return false;
        }
        
        $output = @shell_exec($ffmpeg_path . ' -version 2>&1');
        return !empty($output) && strpos($output, 'ffmpeg') !== false;
    }
    
    /**
     * Get FFmpeg executable path
     * 
     * @return string FFmpeg path or empty string
     */
    private function get_ffmpeg_path() {
        $paths = array(
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
        );
        
        foreach ($paths as $path) {
            $output = @shell_exec('which ' . escapeshellarg($path) . ' 2>&1');
            if (!empty($output) && strpos($output, $path) !== false) {
                return $path;
            }
        }
        
        return '';
    }
    
    /**
     * Generate thumbnail using FFmpeg
     * Extracts frame at 1 second and creates attachment
     * 
     * @param string $video_path Video file path
     * @param int $post_id Post ID
     * @return int|false Attachment ID or false on failure
     */
    private function generate_thumbnail_with_ffmpeg($video_path, $post_id) {
        $ffmpeg_path = $this->get_ffmpeg_path();
        if (empty($ffmpeg_path)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $thumbnail_filename = 'video-thumb-' . $post_id . '-' . time() . '.jpg';
        $thumbnail_path = $upload_dir['path'] . '/' . $thumbnail_filename;
        
        // Extract frame at 1 second
        $command = sprintf(
            '%s -i %s -ss 00:00:01 -vframes 1 -q:v 2 %s 2>&1',
            escapeshellarg($ffmpeg_path),
            escapeshellarg($video_path),
            escapeshellarg($thumbnail_path)
        );
        
        @shell_exec($command);
        
        if (!file_exists($thumbnail_path)) {
            return false;
        }
        
        // Create attachment from generated thumbnail
        $file_array = array(
            'name' => $thumbnail_filename,
            'tmp_name' => $thumbnail_path,
        );
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $thumbnail_id = media_handle_sideload($file_array, $post_id);
        
        // Cleanup temp file
        if (file_exists($thumbnail_path)) {
            @unlink($thumbnail_path);
        }
        
        return $thumbnail_id;
    }
    
    /**
     * Fallback thumbnail generation method
     * Placeholder for future implementation
     * 
     * @param int $video_attachment_id Video attachment ID
     * @param int $post_id Post ID
     * @return false Always returns false (not implemented)
     */
    private function generate_thumbnail_fallback($video_attachment_id, $post_id) {
        return false;
    }
    
    /**
     * Extract YouTube video ID from URL
     * Supports regular videos and Shorts
     * 
     * @param string $url YouTube URL
     * @return string Video ID or empty string
     */
    private function extract_youtube_id($url) {
        if (empty($url)) {
            return '';
        }
        
        $url = trim($url);
        
        $patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return '';
    }
    
    /**
     * Save video metadata on post save
     * Handles local upload, YouTube, and MEGA sources with priority order
     * 
     * @param int $post_id Post ID
     */
    public function save_video_meta($post_id) {
        static $saving = false;
        
        if ($saving) {
            return;
        }
        
        // Security checks
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
        
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $saving = true;
        
        // Priority 1: Local file upload
        if (isset($_FILES['puna_video_file']) && !empty($_FILES['puna_video_file']['name']) && 
            isset($_FILES['puna_video_file']['error']) && $_FILES['puna_video_file']['error'] === UPLOAD_ERR_OK) {
            $this->process_local_video_upload($post_id);
        }
        // Priority 2: YouTube URL
        elseif (isset($_POST['youtube_url']) && !empty($_POST['youtube_url'])) {
            $youtube_url = sanitize_text_field($_POST['youtube_url']);
            $youtube_id = $this->extract_youtube_id($youtube_url);
            
            if (!empty($youtube_id)) {
                update_post_meta($post_id, '_puna_tiktok_youtube_id', $youtube_id);
                update_post_meta($post_id, '_puna_tiktok_video_source', 'youtube');
                
                delete_post_meta($post_id, '_puna_tiktok_mega_link');
                delete_post_meta($post_id, '_puna_tiktok_mega_node_id');
                delete_post_meta($post_id, '_puna_tiktok_video_url');
                delete_post_meta($post_id, '_puna_tiktok_video_file_id');
            }
        }
        // Priority 3: MEGA link
        elseif (isset($_POST['mega_link']) && !empty($_POST['mega_link'])) {
                $mega_link = esc_url_raw($_POST['mega_link']);
                update_post_meta($post_id, '_puna_tiktok_mega_link', $mega_link);
                update_post_meta($post_id, '_puna_tiktok_video_url', $mega_link);
                update_post_meta($post_id, '_puna_tiktok_video_source', 'mega');
                
                delete_post_meta($post_id, '_puna_tiktok_youtube_id');
            delete_post_meta($post_id, '_puna_tiktok_video_file_id');
        }
        // Priority 4: Legacy video_url support
        elseif (isset($_POST['video_url']) && !empty($_POST['video_url'])) {
                $video_url = esc_url_raw($_POST['video_url']);
                update_post_meta($post_id, '_puna_tiktok_video_url', $video_url);
            
            if (empty(get_post_meta($post_id, '_puna_tiktok_mega_link', true)) && strpos($video_url, 'mega.nz') !== false) {
                    update_post_meta($post_id, '_puna_tiktok_mega_link', $video_url);
                update_post_meta($post_id, '_puna_tiktok_video_source', 'mega');
            } elseif (strpos($video_url, wp_upload_dir()['baseurl']) !== false) {
                update_post_meta($post_id, '_puna_tiktok_video_source', 'local');
            }
            
            delete_post_meta($post_id, '_puna_tiktok_youtube_id');
        }
        
        // Save MEGA node ID if provided
        if (isset($_POST['mega_node_id']) && !empty($_POST['mega_node_id'])) {
            $mega_node_id = sanitize_text_field($_POST['mega_node_id']);
            update_post_meta($post_id, '_puna_tiktok_mega_node_id', $mega_node_id);
            update_post_meta($post_id, '_puna_tiktok_video_node_id', $mega_node_id);
        }
        
        $saving = false;
    }
    
    /**
     * Add custom columns to video post list
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_video_columns($columns) {
        $new_columns = array();
        $new_columns['cb']          = $columns['cb'];
        $new_columns['title']       = $columns['title'];
        $new_columns['views']       = __('Views', 'puna-tiktok');
        $new_columns['likes']       = __('Likes', 'puna-tiktok');
        $new_columns['comments']    = __('Comments', 'puna-tiktok');
        $new_columns['date']        = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Render custom column content
     * 
     * @param string $column Column name
     * @param int $post_id Post ID
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

