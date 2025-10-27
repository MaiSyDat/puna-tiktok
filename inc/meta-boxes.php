<?php

/**
 * Meta box và các hàm xử lý lưu trữ
 *
 * @package puna-tiktok
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Đăng ký meta box để tải lên video/nhập URL
 */
function puna_tiktok_add_meta_boxes()
{
    add_meta_box(
        'puna_tiktok_video_upload',
        __('Video Upload', 'puna-tiktok'),
        'puna_tiktok_video_upload_callback',
        'puna_tiktok_video',
        'normal',
        'high'
    );
    
    add_meta_box(
        'puna_tiktok_video_stats',
        __('Video Statistics', 'puna-tiktok'),
        'puna_tiktok_video_stats_callback',
        'puna_tiktok_video',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'puna_tiktok_add_meta_boxes');

/**
 * Hiển thị nội dung của meta box tải lên video
 */
function puna_tiktok_video_upload_callback($post)
{
    wp_nonce_field('puna_tiktok_save_video_upload', 'puna_tiktok_video_upload_nonce');
    $video_file_id = get_post_meta($post->ID, '_puna_tiktok_video_file_id', true);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="puna_tiktok_video_file"><?php _e('Upload Video', 'puna-tiktok'); ?></label>
            </th>
            <td>
                <input type="file" id="puna_tiktok_video_file" name="puna_tiktok_video_file" accept="video/*" />
                <p class="description"><?php _e('Tải lên tệp video từ máy tính của bạn (MP4, WebM, OGG, MOV).', 'puna-tiktok'); ?></p>

                <?php if ($video_file_id) :
                    $video_file_url = wp_get_attachment_url($video_file_id);
                ?>
                    <div class="current-video">
                        <p><strong><?php _e('Video hiện tại:', 'puna-tiktok'); ?></strong></p>
                        <video controls style="max-width: 300px; height: auto;">
                            <source src="<?php echo esc_url($video_file_url); ?>" type="video/mp4">
                        </video>
                        <p><a href="<?php echo esc_url($video_file_url); ?>" target="_blank"><?php _e('Xem video đầy đủ', 'puna-tiktok'); ?></a></p>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Lưu dữ liệu meta box cho việc tải lên video/nhập URL
 */
function puna_tiktok_save_video_upload($post_id)
{
    // Các bước kiểm tra bảo mật
    if (! isset($_POST['puna_tiktok_video_upload_nonce'])) {
        return;
    }
    if (! wp_verify_nonce($_POST['puna_tiktok_video_upload_nonce'], 'puna_tiktok_save_video_upload')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['post_type']) && 'puna_tiktok_video' == $_POST['post_type']) {
        if (! current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Xử lý tệp video được tải lên
    if (! empty($_FILES['puna_tiktok_video_file']['name'])) {
        $uploaded_file = $_FILES['puna_tiktok_video_file'];

        $allowed_types = array('video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo');
        if (! in_array($uploaded_file['type'], $allowed_types)) {
            wp_die('Loại tệp không hợp lệ. Vui lòng chỉ tải lên tệp MP4, WebM, OGG, MOV, hoặc AVI.');
        }

        if ($uploaded_file['size'] > 500 * 1024 * 1024) {
            wp_die('Tệp quá lớn. Kích thước tối đa là 500MB.');
        }

        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && ! isset($movefile['error'])) {
            $attachment = array(
                'post_mime_type' => $uploaded_file['type'],
                'post_title'     => sanitize_file_name($uploaded_file['name']),
                'post_content'   => '',
                'post_status'    => 'inherit'
            );

            $attachment_id = wp_insert_attachment($attachment, $movefile['file'], $post_id);

            if (! is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                update_post_meta($post_id, '_puna_tiktok_video_file_id', $attachment_id);
                delete_post_meta($post_id, '_puna_tiktok_video_url');
            }
        } else {
            wp_die('Tải lên thất bại: ' . $movefile['error']);
        }
    }

}
add_action('save_post', 'puna_tiktok_save_video_upload');


/**
 * Hàm hỗ trợ: Lấy URL video từ tệp hoặc từ URL meta
 */
function puna_tiktok_get_video_url($post_id = null)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }

    $video_file_id = get_post_meta($post_id, '_puna_tiktok_video_file_id', true);
    if ($video_file_id) {
        $video_url = wp_get_attachment_url($video_file_id);
        if ($video_url) {
            return $video_url;
        }
    }

    // Trả về video mẫu nếu chưa có tệp được tải lên
    return 'https://v16-webapp.tiktok.com/video-sample.mp4';
}

/**
 * Hiển thị nội dung của meta box thống kê video
 */
function puna_tiktok_video_stats_callback($post)
{
    $views = get_post_meta($post->ID, '_puna_tiktok_video_views', true);
    $likes = get_post_meta($post->ID, '_puna_tiktok_video_likes', true);
    $comments = get_post_meta($post->ID, '_puna_tiktok_video_comments', true);
    $shares = get_post_meta($post->ID, '_puna_tiktok_video_shares', true);
    
    // Khởi tạo giá trị mặc định nếu chưa có
    if (!$views) $views = 0;
    if (!$likes) $likes = 0;
    if (!$comments) $comments = 0;
    if (!$shares) $shares = 0;
    ?>
    <div class="video-stats">
        <p><strong><?php _e('Views:', 'puna-tiktok'); ?></strong> <?php echo puna_tiktok_format_number($views); ?></p>
        <p><strong><?php _e('Likes:', 'puna-tiktok'); ?></strong> <?php echo puna_tiktok_format_number($likes); ?></p>
        <p><strong><?php _e('Comments:', 'puna-tiktok'); ?></strong> <?php echo puna_tiktok_format_number($comments); ?></p>
        <p><strong><?php _e('Shares:', 'puna-tiktok'); ?></strong> <?php echo puna_tiktok_format_number($shares); ?></p>
        
        <hr style="margin: 15px 0;">
        
        <label for="puna_tiktok_video_views"><?php _e('Views:', 'puna-tiktok'); ?></label>
        <input type="number" id="puna_tiktok_video_views" name="puna_tiktok_video_views" value="<?php echo esc_attr($views); ?>" min="0" />
        
        <label for="puna_tiktok_video_likes"><?php _e('Likes:', 'puna-tiktok'); ?></label>
        <input type="number" id="puna_tiktok_video_likes" name="puna_tiktok_video_likes" value="<?php echo esc_attr($likes); ?>" min="0" />
        
        <label for="puna_tiktok_video_comments"><?php _e('Comments:', 'puna-tiktok'); ?></label>
        <input type="number" id="puna_tiktok_video_comments" name="puna_tiktok_video_comments" value="<?php echo esc_attr($comments); ?>" min="0" />
        
        <label for="puna_tiktok_video_shares"><?php _e('Shares:', 'puna-tiktok'); ?></label>
        <input type="number" id="puna_tiktok_video_shares" name="puna_tiktok_video_shares" value="<?php echo esc_attr($shares); ?>" min="0" />
    </div>
    <?php
}

/**
 * Lưu dữ liệu thống kê video
 */
function puna_tiktok_save_video_stats($post_id)
{
    if (!isset($_POST['post_type']) || 'puna_tiktok_video' != $_POST['post_type']) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Lưu thống kê
    if (isset($_POST['puna_tiktok_video_views'])) {
        update_post_meta($post_id, '_puna_tiktok_video_views', intval($_POST['puna_tiktok_video_views']));
    }
    
    if (isset($_POST['puna_tiktok_video_likes'])) {
        update_post_meta($post_id, '_puna_tiktok_video_likes', intval($_POST['puna_tiktok_video_likes']));
    }
    
    if (isset($_POST['puna_tiktok_video_comments'])) {
        update_post_meta($post_id, '_puna_tiktok_video_comments', intval($_POST['puna_tiktok_video_comments']));
    }
    
    if (isset($_POST['puna_tiktok_video_shares'])) {
        update_post_meta($post_id, '_puna_tiktok_video_shares', intval($_POST['puna_tiktok_video_shares']));
    }
}
add_action('save_post', 'puna_tiktok_save_video_stats');

/**
 * Tăng lượt xem video
 */
function puna_tiktok_increment_video_views($post_id)
{
    $current_views = get_post_meta($post_id, '_puna_tiktok_video_views', true);
    $new_views = $current_views ? $current_views + 1 : 1;
    update_post_meta($post_id, '_puna_tiktok_video_views', $new_views);
    return $new_views;
}
