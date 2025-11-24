<?php
/**
 * Sidebar Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$menu_items = Puna_TikTok_Customize_Sidebar::get_menu_items();

$logo_url = get_theme_mod('sidebar_logo', '');
$logo_link = get_theme_mod('sidebar_logo_link', home_url('/'));

$footer_title_1 = get_theme_mod('sidebar_footer_title_1', 'Company');
$footer_title_2 = get_theme_mod('sidebar_footer_title_2', 'Programs');
$footer_title_3 = get_theme_mod('sidebar_footer_title_3', 'Terms & Services');
$footer_copyright = get_theme_mod('sidebar_footer_copyright', 'Puna TikTok');
$footer_copyright = str_replace('[year]', date('Y'), $footer_copyright);
?>
<!-- Sidebar -->
<aside class="sidebar">
    <?php if ($logo_url) : ?>
        <div class="logo">
            <a href="<?php echo esc_url($logo_link); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo">
            </a>
        </div>
    <?php else : ?>
        <!-- Default Logo -->
        <?php 
        $default_logo = puna_tiktok_get_logo_url();
        if ($default_logo) : ?>
            <div class="logo">
                <a href="<?php echo esc_url($logo_link); ?>">
                    <img src="<?php echo esc_url($default_logo); ?>" alt="Logo">
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Search Trigger Button -->
    <?php 
    $current_search_query = get_search_query();
    $search_display_text = $current_search_query ? esc_html($current_search_query) : __('Search', 'puna-tiktok');
    ?>
    <div class="sidebar-search-trigger" id="search-trigger">
        <?php echo puna_tiktok_get_icon('search', __('Search', 'puna-tiktok')); ?>
        <span class="search-text"><?php echo $search_display_text; ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <?php foreach ($menu_items as $item) : ?>
            <li>
                <a href="<?php echo esc_url($item['url']); ?>" class="nav-link <?php echo $item['active'] ? 'active' : ''; ?>">
                    <?php 
                    // Extract icon name from Font Awesome class or use directly
                    $icon_name = $item['icon'];
                    if (strpos($icon_name, 'fa-') !== false) {
                        // Map common FA classes to icon files
                        $fa_map = array(
                            'fa-house' => 'home',
                            'fa-home' => 'home',
                            'fa-folder' => 'compass',
                            'fa-hashtag' => 'tag',
                            'fa-tag' => 'tag',
                        );
                        foreach ($fa_map as $fa => $icon) {
                            if (strpos($icon_name, $fa) !== false) {
                                $icon_name = $icon;
                                break;
                            }
                        }
                    }
                    echo puna_tiktok_get_icon($icon_name, $item['title']); 
                    ?>
                    <span class="menu-text"><?php echo esc_html($item['title']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <!-- Buy Now Button -->
    <div class="sidebar-buy-now">
        <a href="https://hupuna.com/hop-carton/" target="_blank" rel="noopener noreferrer" class="buy-now-button">
            <span class="buy-now-text"><?php echo __('Buy Now', 'puna-tiktok'); ?></span>
        </a>
    </div>
    
    <div class="sidebar-footer">
        <?php if ($footer_title_1) : ?><h3><?php echo esc_html($footer_title_1); ?></h3><?php endif; ?>
        <?php if ($footer_title_2) : ?><h3><?php echo esc_html($footer_title_2); ?></h3><?php endif; ?>
        <?php if ($footer_title_3) : ?><h3><?php echo esc_html($footer_title_3); ?></h3><?php endif; ?>
        <?php if ($footer_copyright) : ?>
        <p>
            © <?php echo esc_html($footer_copyright); ?>
        </p>
        <?php endif; ?>
    </div>
</aside>
