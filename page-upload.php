<?php
/**
 * Template Name: Upload Video
 * 
 * Trang upload video frontend - giống TikTok Studio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Kiểm tra đăng nhập
if (!is_user_logged_in()) {
    $current_url = home_url($_SERVER['REQUEST_URI']);
    wp_redirect(wp_login_url($current_url));
    exit;
}

get_header();
?>

<div class="upload-page-wrapper">
    <div class="upload-container">
        <?php get_template_part('template-parts/upload-form'); ?>
    </div>
</div>

<?php get_footer(); ?>

