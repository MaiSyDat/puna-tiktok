<?php
/**
 * Search Panel Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Search Panel -->
<aside class="search-panel" id="search-panel">
    <div class="search-panel-header">
        <h3><?php esc_html_e('Search', 'puna-tiktok'); ?></h3>
        <button id="close-search" class="close-search-btn">
            <?php echo puna_tiktok_get_icon('close', __('Close', 'puna-tiktok')); ?>
        </button>
    </div>
    <div class="search-panel-input-wrapper">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form" id="search-form">
            <input type="search" 
                   id="real-search-input" 
                   name="s" 
                   value="<?php echo esc_attr(get_search_query()); ?>" 
                   placeholder="<?php esc_attr_e('Search...', 'puna-tiktok'); ?>"
                   autocomplete="off">
        </form>
    </div>
    <!-- Search Suggestions & History Container -->
    <div class="search-suggestions-container" id="search-suggestions-container">
        <!-- Suggestions -->
        <ul class="search-suggestions" id="search-suggestions-list">
            <!-- Suggestions will be loaded using AJAX -->
        </ul>
        
        <!-- Search History (shown when input is empty) -->
        <div class="search-history-section" id="search-history-section">
            <div class="search-history-header">
                <h4><?php esc_html_e('Search history', 'puna-tiktok'); ?></h4>
                <button class="clear-history-btn" id="clear-history-btn">
                    <?php echo puna_tiktok_get_icon('delete', __('Clear history', 'puna-tiktok')); ?> <?php esc_html_e('Clear history', 'puna-tiktok'); ?>
                </button>
            </div>
            <ul class="search-history-list" id="search-history-list">
                <!-- History will be loaded using AJAX -->
                <li class="search-history-loading"><?php esc_html_e('Loading history...', 'puna-tiktok'); ?></li>
            </ul>
        </div>
        
        <!-- Popular Searches -->
        <div class="search-popular-section" id="search-popular-section">
            <h4><?php esc_html_e('Popular searches', 'puna-tiktok'); ?></h4>
            <ul class="search-popular-list" id="search-popular-list">
                <li class="search-popular-loading"><?php esc_html_e('Loading...', 'puna-tiktok'); ?></li>
            </ul>
        </div>
    </div>
</aside>

