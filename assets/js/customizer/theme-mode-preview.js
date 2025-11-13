(function($) {
    'use strict';

    function applyThemeMode(mode) {
        var docEl = document.documentElement;
        var body = document.body;

        docEl.classList.remove('theme-light', 'theme-dark');
        if (body) {
            body.classList.remove('theme-light', 'theme-dark');
        }

        if (mode === 'dark') {
            docEl.classList.add('theme-dark');
            if (body) {
                body.classList.add('theme-dark');
            }
        } else if (mode === 'light') {
            docEl.classList.add('theme-light');
            if (body) {
                body.classList.add('theme-light');
            }
        }

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('punaThemeModeChanged'));
        }
    }

    wp.customize('puna_theme_mode', function(value) {
        value.bind(function(newVal) {
            applyThemeMode(newVal);
        });
    });
})(jQuery);

