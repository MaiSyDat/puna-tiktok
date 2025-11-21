/**
 * Customizer Preview Script
 * Handles live preview for color changes
 *
 */

(function($) {
    'use strict';

    // Primary Color
    wp.customize('puna_color_primary', function(value) {
        value.bind(function(newval) {
            $(':root').css('--puna-primary', newval);
        });
    });

    // Secondary Color
    wp.customize('puna_color_secondary', function(value) {
        value.bind(function(newval) {
            $(':root').css('--puna-secondary', newval);
        });
    });

    // Background Color
    wp.customize('puna_color_bg', function(value) {
        value.bind(function(newval) {
            $('body, .tiktok-app').css('background-color', newval);
            $(':root').css('--puna-bg', newval);
        });
    });

    // Text Color
    wp.customize('puna_color_text', function(value) {
        value.bind(function(newval) {
            $('body, .tiktok-app').css('color', newval);
            $(':root').css('--puna-text', newval);
        });
    });

    // Link / Accent Color
    wp.customize('puna_color_link', function(value) {
        value.bind(function(newval) {
            $('a').css('color', newval);
            $(':root').css('--puna-link', newval);
        });
    });

    // Border / Muted Color
    wp.customize('puna_color_muted', function(value) {
        value.bind(function(newval) {
            $('.comment-item, .video-container').css('border-color', newval);
            $(':root').css('--puna-muted', newval);
        });
    });

    // Font Family
    wp.customize('puna_font_family', function(value) {
        value.bind(function(newval) {
            $('body').css('font-family', newval);
            $(':root').css('--puna-font-family', newval);
        });
    });

    // Footer Settings
    wp.customize('sidebar_footer_title_1', function(value) {
        value.bind(function(newval) {
            $('.sidebar-footer h3:first').text(newval || 'Company');
        });
    });

    wp.customize('sidebar_footer_title_2', function(value) {
        value.bind(function(newval) {
            $('.sidebar-footer h3:nth-child(2)').text(newval || 'Programs');
        });
    });

    wp.customize('sidebar_footer_title_3', function(value) {
        value.bind(function(newval) {
            $('.sidebar-footer h3:nth-child(3)').text(newval || 'Terms & Services');
        });
    });

    wp.customize('sidebar_footer_copyright', function(value) {
        value.bind(function(newval) {
            var year = new Date().getFullYear();
            var copyright = newval ? newval.replace('[year]', year) : 'Puna TikTok';
            $('.sidebar-footer p').html('© ' + copyright);
        });
    });


})(jQuery);

