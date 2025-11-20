<?php

/**
 * Admin AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin_AJAX
 */
if (!class_exists('Admin_AJAX')) {

    class Admin_AJAX {

        /**
         * Constructor.
         */
        public function __construct() {
            // Video upload AJAX
            add_action('wp_ajax_puna_tiktok_video_upload', array($this, 'video_upload'));
            
            // Allow other code to hook into admin AJAX initialization
            do_action('puna_tiktok_admin_ajax_initialized', $this);
        }

        /**
         * Handle video upload via AJAX
         */
        public function video_upload() {
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'puna_tiktok_video_upload')) {
                wp_send_json_error(array('message' => __('Nonce không hợp lệ.', 'puna-tiktok')));
                return;
            }
            
            // Check user capabilities
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => __('Bạn không có quyền thực hiện hành động này.', 'puna-tiktok')));
                return;
            }
            
            // Allow filtering of upload handler
            $upload_result = apply_filters('puna_tiktok_admin_video_upload_before', null, $_POST);
            
            if ($upload_result !== null) {
                wp_send_json($upload_result);
                return;
            }
            
            // Handle file upload
            if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(array('message' => __('Không có file được tải lên hoặc có lỗi xảy ra.', 'puna-tiktok')));
                return;
            }
            
            // Process upload (this would typically use Mega uploader)
            // For now, return success with placeholder data
            $file_name = sanitize_file_name($_FILES['video_file']['name']);
            $file_size = $_FILES['video_file']['size'];
            
            // Allow filtering of upload result
            $result = apply_filters('puna_tiktok_admin_video_upload_result', array(
                'success' => true,
                'data' => array(
                    'file_name' => $file_name,
                    'file_size' => $file_size,
                    'message' => __('Video đã được tải lên thành công.', 'puna-tiktok'),
                ),
            ), $_FILES);
            
            wp_send_json($result);
        }
    }

    // Init class
    new Admin_AJAX();
}

