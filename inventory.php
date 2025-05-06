<?php
/* inventory.php – GameStop 2.0 */
require_once 'includes/config.php';

if (!isset($_SESSION['username']) ||!isset($_SESSION['uid'])) {
    header('Location: access_denied.php');
    exit();
}
ucfirst($_SESSION['username']);

function getCurrentUserName(): string
{
    return ucfirst($_SESSION['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0">
    <title>GameStop 2.0 — Inventory</title>
    <?php require_once 'includes/link.php'; ?>
</head>
<body>
    <!-- persistent nav -->
    <?php require_once 'includes/navbar.php'; ?>

    <main id="inventory" class="container full-screen flex-margin">

        <!-- welcome card -->
        <div class="center-div card welcome-card text-center mt-1-5">
            <h1 class="text-bold">
                Welcome, <?= getCurrentUserName(); ?>! 👋
            </h1>
            <p class="mt-1">
                Manage your games, trades, and wishlist below.
            </p>
            <a href="logout.php" class="btn logout-btn mt-1">
                <i class="fas fa-sign-out-alt"></i> Log out
            </a>
        </div>

        <!-- my-games live search -->
        <section id="my-games" class="card mt-1">
            <h2 class="text-center">My Games</h2>
            
            <div class="search-group mt-1">
                <label for="search-input-post">Search:</label>
                <input
                    type="text"
                    id="search-input-post"
                    name="search_data"
                    placeholder="Type to search…"
                    autocomplete="off"
                >
            </div>

            <div id="results-post" class="results-container mt-1"></div>
        </section>

    </main>

    <!-- jQuery must be loaded before this -->
    <script>
        (function ($) {
            const $results = $('#results-post');
            const $box     = $('#search-input-post');

            /** render one response */
            const render = list => {
                $results.empty();
                if (!Array.isArray(list) || !list.length)
                    return $results.html('<p>No matches found.</p>');

                list.forEach(g => {
                    $('<div>', { class: 'game-item' })
                        .append(g.Image
                            ? $('<img>', {
                                src: g.Image, alt: g.Title, loading: 'lazy'
                            })
                            : `<svg width="100px" height="100px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    <path d="m 4 1 c -1.644531 0 -3 1.355469 -3 3 v 1 h 1 v -1 c 0 -1.109375 0.890625 -2 2 -2 h 1 v -1 z m 2 0 v 1 h 4 v -1 z m 5 0 v 1 h 1 c 1.109375 0 2 0.890625 2 2 v 1 h 1 v -1 c 0 -1.644531 -1.355469 -3 -3 -3 z m -5 4 c -0.550781 0 -1 0.449219 -1 1 s 0.449219 1 1 1 s 1 -0.449219 1 -1 s -0.449219 -1 -1 -1 z m -5 1 v 4 h 1 v -4 z m 13 0 v 4 h 1 v -4 z m -4.5 2 l -2 2 l -1.5 -1 l -2 2 v 0.5 c 0 0.5 0.5 0.5 0.5 0.5 h 7 s 0.472656 -0.035156 0.5 -0.5 v -1 z m -8.5 3 v 1 c 0 1.644531 1.355469 3 3 3 h 1 v -1 h -1 c -1.109375 0 -2 -0.890625 -2 -2 v -1 z m 13 0 v 1 c 0 1.109375 -0.890625 2 -2 2 h -1 v 1 h 1 c 1.644531 0 3 -1.355469 3 -3 v -1 z m -8 3 v 1 h 4 v -1 z m 0 0" fill="#2e3434" fill-opacity="0.34902"/>
</svg>`)
                        .append($('<span>', { text: g.Title }))
                        .append(g.copies ? $('<em>', { text: ` ×${g.copies}` }) : '')
                        .appendTo($results);
                });
            };

            /** hit the API */
            const fetchGames = term =>
                $.post('includes/fetch_games.php', {search_data: term}, render, 'json')
                .fail(() => $results.html('<p class="error">Service error.</p>'));

            /* first call after the whole window loads */
            $(window).on('load', () => fetchGames(''));

            /* debounced keyup search */
            let timer;
            $box.on('keyup', function () {
                clearTimeout(timer);
                timer = setTimeout(() => fetchGames(this.value.trim()), 250);
            });
        })(jQuery);
    </script>
    <!-- theme toggle script -->
    <script>
        $(document).ready(function ($) {
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

                $(document).ready(function () {
                    const saved = localStorage.getItem('theme') || '';
                    applyTheme(saved);
                    $('#theme-switch').on('click', toggleTheme);
                });

                window.toggleTheme = function (e) {
                    e.preventDefault();
                    const isRed =
                        $('body').attr('data-theme') === 'red';
                    const next = isRed ? '' : 'red';
                    localStorage.setItem('theme', next);
                    applyTheme(next);
                };
            })(jQuery);
    </script>
</body>
</html>
