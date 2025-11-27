<?php
/**
 * 404 View
 * 404 error page template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tiktok-app">
    <?php get_template_part('template-parts/sidebar'); ?>
    
    <div class="main-content">
        <?php
        puna_tiktok_empty_state(array(
            'icon' => 'fa-search',
            'title' => __('404 - Page Not Found', 'puna-tiktok'),
            'message' => __('Sorry, the content you are looking for does not exist or has been deleted.', 'puna-tiktok'),
            'button_url' => home_url('/'),
            'button_text' => __('Go to Home', 'puna-tiktok'),
            'wrapper_class' => 'error-404-empty-state'
        ));
        ?>
    </div>
</div>

