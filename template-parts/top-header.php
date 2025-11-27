<?php
/**
 * Top Header Template
 * Fixed top action bar
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="fixed-top-container" class="fixed-top-container">
    <div class="top-right-container">
        <div id="top-right-action-bar" class="action-bar-container">
            <!-- Contact -->
            <div class="action-bar-item">
                <button class="action-bar-button" id="top-contact-button" aria-label="<?php esc_attr_e('Contact', 'puna-tiktok'); ?>" data-phone="0889736889">
                    <div class="action-bar-icon">
                        <?php echo puna_tiktok_get_icon('phone', __('Contact', 'puna-tiktok')); ?>
                    </div>
                    <span class="action-bar-text"><?php esc_html_e('Contact', 'puna-tiktok'); ?></span>
                </button>
            </div>

            
            <!-- News -->
            <div class="action-bar-item action-bar-news">
                <a href="https://hupuna.com/tin-tuc-va-su-kien/" class="action-bar-button action-bar-news-btn" aria-label="<?php esc_attr_e('News', 'puna-tiktok'); ?>">
                    <div class="action-bar-icon">
                        <?php echo puna_tiktok_get_icon('news', __('News', 'puna-tiktok')); ?>
                    </div>
                    <span class="action-bar-text"><?php esc_html_e('News', 'puna-tiktok'); ?></span>
                </a>
            </div>
            
            <!-- Web -->
            <div class="action-bar-item">
                <a href="https://hupuna.com/" class="action-bar-button" aria-label="<?php esc_attr_e('View Web Site', 'puna-tiktok'); ?>">
                    <div class="action-bar-icon">
                        <?php echo puna_tiktok_get_icon('link', __('Web', 'puna-tiktok')); ?>
                    </div>
                    <span class="action-bar-text"><?php esc_html_e('View Web Site', 'puna-tiktok'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

