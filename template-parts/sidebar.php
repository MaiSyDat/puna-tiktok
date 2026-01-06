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

$footer_copyright = get_theme_mod('sidebar_footer_copyright', 'Copyright © HUPUNA GROUP');
$footer_copyright = str_replace('[year]', date('Y'), $footer_copyright);
?>
<!-- Sidebar -->
<aside class="sidebar">
    <?php if ($logo_url) : ?>
        <div class="logo">
            <a href="<?php echo esc_url($logo_link); ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('Logo', 'puna-tiktok'); ?>">
            </a>
        </div>
    <?php else : ?>
        <!-- Default Logo -->
        <?php
        $default_logo = puna_tiktok_get_logo_url();
        if ($default_logo) : ?>
            <div class="logo">
                <a href="<?php echo esc_url($logo_link); ?>">
                    <img src="<?php echo esc_url($default_logo); ?>" alt="<?php esc_attr_e('Logo', 'puna-tiktok'); ?>">
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
        <?php echo wp_kses_post(puna_tiktok_get_icon('search', __('Search', 'puna-tiktok'))); ?>
        <span class="search-text"><?php echo esc_html($search_display_text); ?></span>
    </div>

    <ul class="sidebar-menu">
        <?php foreach ($menu_items as $item) : ?>
            <li>
                <a href="<?php echo esc_url($item['url']); ?>" class="nav-link <?php echo esc_attr($item['active'] ? 'active' : ''); ?>">
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
                    echo wp_kses_post(puna_tiktok_get_icon($icon_name, $item['title']));
                    ?>
                    <span class="menu-text"><?php echo esc_html($item['title']); ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Social Media Links -->
    <div class="sidebar-social">
        <a href="https://www.facebook.com/xuonghopcartonhupuna" target="_blank" rel="noopener noreferrer" class="social-link facebook" title="<?php esc_attr_e('Facebook', 'puna-tiktok'); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon('facebook', __('Facebook', 'puna-tiktok'))); ?>
            <span class="social-tooltip"><?php esc_html_e('Facebook', 'puna-tiktok'); ?></span>
        </a>
        <a href="https://www.instagram.com/hupunagroup/" target="_blank" rel="noopener noreferrer" class="social-link instagram" title="<?php esc_attr_e('Instagram', 'puna-tiktok'); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon('instagram', __('Instagram', 'puna-tiktok'))); ?>
            <span class="social-tooltip"><?php esc_html_e('Instagram', 'puna-tiktok'); ?></span>
        </a>
        <a href="https://www.youtube.com/@congtycophanhupunagroup" target="_blank" rel="noopener noreferrer" class="social-link youtube" title="<?php esc_attr_e('YouTube', 'puna-tiktok'); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon('play', __('YouTube', 'puna-tiktok'))); ?>
            <span class="social-tooltip"><?php esc_html_e('YouTube', 'puna-tiktok'); ?></span>
        </a>
    </div>

    <div class="sidebar-footer">
        <?php if ($footer_copyright) : ?>
            <p>
                <?php echo esc_html($footer_copyright); ?>
            </p>
        <?php endif; ?>
    </div>
</aside>

<!-- Bottom Navigation Bar (Mobile Only) -->
<nav class="bottom-nav">
    <?php
    // Get first 2 menu items (Home, Category)
    $bottom_menu_items = array_slice($menu_items, 0, 2);
    foreach ($bottom_menu_items as $item) :
        $icon_name = $item['icon'];
        if (strpos($icon_name, 'fa-') !== false) {
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
    ?>
        <a href="<?php echo esc_url($item['url']); ?>" class="bottom-nav-item <?php echo esc_attr($item['active'] ? 'active' : ''); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon($icon_name, $item['title'])); ?>
        </a>
    <?php endforeach; ?>

    <!-- Logo in Center -->
    <div class="bottom-nav-logo">
        <?php
        $bottom_logo_url = get_theme_mod('sidebar_logo', '');
        $bottom_logo_link = get_theme_mod('sidebar_logo_link', home_url('/'));
        if (empty($bottom_logo_url)) {
            $bottom_logo_url = puna_tiktok_get_logo_url();
        }
        if ($bottom_logo_url) :
        ?>
            <a href="<?php echo esc_url($bottom_logo_link); ?>" class="bottom-nav-logo-link">
                <img src="<?php echo esc_url($bottom_logo_url); ?>" alt="<?php esc_attr_e('Logo', 'puna-tiktok'); ?>" class="bottom-nav-logo-img">
            </a>
        <?php endif; ?>
    </div>

    <?php
    // Get 3rd menu item (Tag) if exists
    if (isset($menu_items[2])) :
        $item = $menu_items[2];
        $icon_name = $item['icon'];
        if (strpos($icon_name, 'fa-') !== false) {
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
    ?>
        <a href="<?php echo esc_url($item['url']); ?>" class="bottom-nav-item <?php echo esc_attr($item['active'] ? 'active' : ''); ?>">
            <?php echo wp_kses_post(puna_tiktok_get_icon($icon_name, $item['title'])); ?>
        </a>
    <?php endif; ?>

    <!-- Search Button -->
    <div class="bottom-nav-item bottom-nav-search" id="bottom-search-trigger">
        <?php echo wp_kses_post(puna_tiktok_get_icon('search', __('Search', 'puna-tiktok'))); ?>
    </div>
</nav>