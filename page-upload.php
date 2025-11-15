<?php
/**
 * Template Name: Upload Video
 */

if (!defined('ABSPATH')) {
    exit;
}

// Chỉ admin mới được upload video
if (!current_user_can('manage_options')) {
    wp_die('Chỉ quản trị viên mới được đăng video.', 'Không có quyền truy cập', array('response' => 403));
}

get_header();
?>

<div class="upload-page-wrapper">
    <div class="upload-container">
        <?php get_template_part('template-parts/upload-form'); ?>
    </div>
</div>

<?php get_footer(); ?>

