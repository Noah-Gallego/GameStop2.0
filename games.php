<?php
require_once 'includes/config.php';
if (!isset($_SESSION['username'])) {
    header('Location: access_denied.php');
    exit();
}

function getCurrentUserName(): string {
    return ucfirst($_SESSION['username']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Games</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/games.css">
    <?php require_once 'includes/link.php' ?>
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <main id="games" class="container full-screen flex-margin">
        <!-- Welcome Banner -->
        <div class="center-div card welcome-card text-center mt-1-5" style="
            display: flex;
            justify-content: center;
            align-items: center;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" height="30" width="40" viewBox="0 0 640 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com/ License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M480.1 96H160a160 160 0 1 0 114.2 272h91.5A160 160 0 1 0 480.1 96zM248 268a12 12 0 0 1 -12 12h-52v52a12 12 0 0 1 -12 12h-24a12 12 0 0 1 -12-12v-52H84a12 12 0 0 1 -12-12v-24a12 12 0 0 1 12-12h52v-52a12 12 0 0 1 12-12h24a12 12 0 0 1 12 12v52h52a12 12 0 0 1 12 12zm216 76a40 40 0 1 1 40-40 40 40 0 0 1 -40 40zm64-96a40 40 0 1 1 40-40 40 40 0 0 1 -40 40z"></path></svg>
            <h1 class="text-bold" style="margin-left:3px;">Games</h1>
        </div>

        <div id="toast">Game added to inventory!</div>

        <!-- Filter Bar -->
        <section class="card filters">
                <div class="search-box">
                    <label for="title-input">
                        <i class="fas fa-search icon-left"></i> Filter By Title:
                    </label>
                    <input type="text"
                           id="title-input"
                           name="title"
                           placeholder="Title…"
                           autocomplete="off">
                </div>
            <fieldset class="status-box">
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort-amount-down icon-left"></i> Sort:</label>
                    <select name="sort" id="sort">
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                </div>
            </fieldset>
        </section>

        <!-- Games Results -->
        <section class="card results">
            <div id="results"></div>
        </section>
    </main>

    <!-- Games Script -->
    <script>
        (function ($) {
            const $results   = $('#results');
            const $search    = $('#title-input');
            const $sort  = $('#sort');

            const queryString = window.location.search;
            const urlParams = new URLSearchParams(queryString);
            const urlTitle = urlParams.get('title');
            const urlSort = urlParams.get('sort');

            let gid = urlParams.get('gid');

            if (urlSort) $sort.val(urlSort);
            if (urlTitle) $search.val(urlTitle);

            function getPayload()
            {
                return {
                    title: $search.val().trim(),
                    sort: $sort.val(),
                    gid: gid || ''
                };
            }

            function render(list) {
                $results.empty();

                if (!Array.isArray(list) || list.length === 0) {
                    $results.html('<p class="text-center">No games found.</p>');
                    return;
                }

                list.forEach(game => {
                    const card = $('<div>', { class: 'games-card card flex-between' });

                    const left = $('<div>', { class: 'games-left flex-center' })
                        .append(game.image
                            ? $('<img>', { class: 'img' , src: game.image, alt: game.title, loading: 'lazy' })
                            : `<svg width="100px" height="100px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    <path d="m 4 1 c -1.644531 0 -3 1.355469 -3 3 v 1 h 1 v -1 c 0 -1.109375 0.890625 -2 2 -2 h 1 v -1 z m 2 0 v 1 h 4 v -1 z m 5 0 v 1 h 1 c 1.109375 0 2 0.890625 2 2 v 1 h 1 v -1 c 0 -1.644531 -1.355469 -3 -3 -3 z m -5 4 c -0.550781 0 -1 0.449219 -1 1 s 0.449219 1 1 1 s 1 -0.449219 1 -1 s -0.449219 -1 -1 -1 z m -5 1 v 4 h 1 v -4 z m 13 0 v 4 h 1 v -4 z m -4.5 2 l -2 2 l -1.5 -1 l -2 2 v 0.5 c 0 0.5 0.5 0.5 0.5 0.5 h 7 s 0.472656 -0.035156 0.5 -0.5 v -1 z m -8.5 3 v 1 c 0 1.644531 1.355469 3 3 3 h 1 v -1 h -1 c -1.109375 0 -2 -0.890625 -2 -2 v -1 z m 13 0 v 1 c 0 1.109375 -0.890625 2 -2 2 h -1 v 1 h 1 c 1.644531 0 3 -1.355469 3 -3 v -1 z m -8 3 v 1 h 4 v -1 z m 0 0" fill="#2e3434" fill-opacity="0.34902"/>
</svg>`)
                        .append($('<div>', { class: 'games-center' })
                            .append($('<span>', { class: 'game-title text-bold', text: `${game.title}` }))
                            .append(
                                $('<span>', {
                                    class: `game-hw text-bold ${game.has_count > game.want_count ? 'red' : 'green'}`,
                                    text: `${game.has_count}:${game.want_count}`
                                })
                            ));

                    const right = $('<div>', { class: 'games-actions' })
                        .append(game.priority
                            ? $('<i>', {
                            class: `fas fa-star wishlist-icon wishlist green p-${game.priority}`, // wishlisted
                            title: 'Add to wishlist',
                            'data-gid': game.gid })
                            : $('<i>', {
                                class: 'far fa-star wishlist-icon wishlist', // not wishlisted
                                title: 'Add to wishlist',
                                'data-gid': game.gid })
                        )
                        .append($('<i>', {
                            class: 'fas fa-download add-icon add',
                            title: 'Add game',
                            'data-gid': game.gid
                        }));
                
                    card.append(left).append(right).appendTo($results);
                });
            }

            // Wishlist Functionality with priority prompt
            $results.on('click', '.wishlist', function () {
                const $icon = $(this);
                const gid   = $icon.data('gid');

                // Ask user for priority 0–3
                let priority;
                do {
                    priority = prompt(
                        "Set wishlist priority:\n0 = Remove From Wishlist\n1 = Really Want\n2 = Want\n3 = Somewhat Want",
                        "1"
                    );
                    if (priority === null) {
                        return; // user cancelled
                    }
                } while (!/^[0-3]$/.test(priority));  // only allow 1,2,3

                if(Number(priority) === 0)
                {
                    console.log(priority);
                    // Send priority along with gid
                    $.post('api/wants.php',
                        { action: 'delete', gid: gid },
                        function (response) {
                            if (!response.success) {
                                alert("Failed to wishlist game: " + response.error);
                            } else {
                                // reflect chosen priority in the star class (p-1, p-2, or p-3)
                                $icon.removeClass('fas')
                                    .removeClass('p-1 p-2 p-3')
                                    .addClass('far');
                            }
                        },
                        'json'
                    );
                }
                else
                {
                    $.post('api/wants.php', { action: 'read', gid: gid }, function(response)
                    {
                        if (response && response.length > 0)  // update
                        {
                            $.post('api/wants.php',
                                { action: 'update', gid: gid, priority: priority },
                                function (response) {
                                    if (!response.success) {
                                        alert("Failed to wishlist game: " + response.error);
                                    } else {
                                        // reflect chosen priority in the star class (p-1, p-2, or p-3)
                                        $icon
                                            .removeClass('far')
                                            .addClass('fas')
                                            .removeClass('p-1 p-2 p-3')
                                            .addClass(`p-${priority}`);
                                    }
                                },
                                'json'
                            );
                        } else // create
                        {
                            $.post('api/wants.php',
                                { action: 'create', gid: gid, priority: priority },
                                function (response) {
                                    if (!response.success) {
                                        alert("Failed to wishlist game: " + response.error);
                                    } else {
                                        // reflect chosen priority in the star class (p-1, p-2, or p-3)
                                        $icon
                                            .removeClass('far')
                                            .addClass('fas')
                                            .removeClass('p-1 p-2 p-3')
                                            .addClass(`p-${priority}`);
                                    }
                                },
                                'json'
                            );
                        }
                    }, 'json');

                    // Send priority along with gid

                }

            });

            // Add Functionality
            $results.on('click', '.add', function () {
                const gid = $(this).data('gid');

                console.log(gid);
                let license;
                const licenseRegex = /^[0-9a-zA-Z]{5}-[0-9a-zA-Z]{5}-[0-9a-zA-Z]{5}$/;
                do {
                    license = prompt("Enter your license key (format: 12345-ABCDE-6789Z):");
                    if (license === null) return; // User cancelled
                } while (!licenseRegex.test(license));
                console.log(license);
                $.post('api/has.php', { action: 'create', gid: gid, license: license }, function (response) { ///...
                    if (response.success) {
                        showToast("Game added to inventory!");
                    } else {
                        alert("Failed to add game: " + response.error);
                    }
                }, 'json');
            });

            function fetchGames()
            {
                console.log("Calling games.php...");
                $.post('api/games.php', getPayload(), render, 'json')
                .fail(() => {
                    console.error("Fetch failed");
                    $results.html('<p class="error">Server error.</p>');
                });
            }

            function fetchGamesFull()
            {
                console.log("Calling games.php...");
                $.post('api/misc.php', getPayload(), render, 'json')
                .fail(() => {
                    console.error("Fetch failed");
                    $results.html('<p class="error">Server error.</p>');
                });
            }

            function showToast(message) {
                const toast = document.getElementById("toast");
                toast.textContent = message;
                toast.classList.add("show");
                setTimeout(() => {
                    toast.classList.remove("show");
                }, 3000);
            }

            $(window).on('load', fetchGamesFull);
            $search.on('keyup', fetchGamesFull);
            $sort.on('change', function () {
                const sortValue = $(this).val();
                const titleValue = $search.val().trim();
                const newParams = new URLSearchParams(window.location.search);

                if (titleValue) {
                    newParams.set('title', titleValue);
                } else {
                    newParams.delete('title');
                }

                newParams.set('sort', sortValue);

                newParams.delete('gid');
                gid = '';

                const newUrl = `${window.location.pathname}?${newParams.toString()}`;
                window.history.replaceState({}, '', newUrl);

                fetchGamesFull();
            });

            $search.on('input', function ()
            {
                const newTitle = $(this).val().trim();
                const newParams = new URLSearchParams(window.location.search);

                if (newTitle) {
                    newParams.set('title', newTitle);
                } else {
                    newParams.delete('title');
                }

                newParams.delete('gid');
                gid = '';

                const newUrl = `${window.location.pathname}?${newParams.toString()}`;
                window.history.replaceState({}, '', newUrl);
            });

        })(jQuery);
    </script>

    <!-- Theme Toggle Script -->
    <script>
        (function ($) {
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