(function ($) {
    /* ---------------------------------- helpers */
    function applyTheme(theme) {
        const body = $('body');
        const btn  = $('#theme-switch');

        if (theme === 'red') {
        body.attr('data-theme', 'red');
        btn.attr('title', 'Switch to Purple Theme');
        } else {
        body.removeAttr('data-theme');
        btn.attr('title', 'Switch to Red Theme');
        }
    }

    /* ---------------------------------- run on load */
    $(document).ready(function () {
        // 1) apply stored preference
        const saved = localStorage.getItem('theme') || '';
        applyTheme(saved);

        // 2) bind button click
        $('#theme-switch').on('click', toggleTheme);
    });

    /* ---------------------------------- global toggle */
    window.toggleTheme = function (e) {
        e.preventDefault();
        const isRed = $('body').attr('data-theme') === 'red';
        const next  = isRed ? '' : 'red';

        localStorage.setItem('theme', next);
        applyTheme(next);
    };
})(jQuery);