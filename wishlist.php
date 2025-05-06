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
    <title><?= getCurrentUserName(); ?>'s Wishlist</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/wishlist.css">
    <?php require_once 'includes/link.php' ?>
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <main id="wishlist" class="container full-screen flex-margin">
        
        <!-- Welcome Banner -->
        <div class="center-div card welcome-card text-center mt-1">
            <h1 class="text-bold">
                <i class="fas fa-heart icon-pink"></i> <?= getCurrentUserName(); ?>'s Wishlist
            </h1>
            <p class="mt-1">Here are some games that have been on your mind.</p>
        </div>

        <!-- Filter Bar -->
        <section class="card black-div filters">
            <div class="search-box">
                <label for="search-input">
                    <i class="fas fa-search icon-left"></i> Filter By Title:
                </label>
                <input type="text"
                       id="search-input"
                       name="search_data"
                       placeholder="Type to search…"
                       autocomplete="off">
            </div>

            <fieldset class="status-box">
                <legend><i class="fas fa-filter icon-left"></i> Filters:</legend>

                <div class="filter-group">
                    <label for="priority"><i class="fas fa-sort-amount-down icon-left"></i> Priority:</label>
                    <select name="priority" id="priority">
                        <option value="">Select</option>
                        <option value="1">1 - Really Want</option>
                        <option value="2">2 - Want</option>
                        <option value="3">3 - Somewhat Want</option>
                    </select>
                </div>
            </fieldset>
        </section>

        <!-- Wishlist Results -->
        <section class="card results black-div">
            <h2 class="text-center">
                <i class="fas fa-list-ul icon-left"></i> My Wishlist
            </h2>
            <div id="results"></div>
        </section>
    </main>

    <!-- Wishlist Script -->
    <script>
        (function ($) {
            /* cache DOM nodes */
            const $results  = $('#results');
            const $search   = $('#search-input');
            const $priority = $('#priority');    // global filter

            /* build payload for fetch_wishlist.php */
            function getPayload() {
                return {
                    search_data: $search.val().trim(),
                    priority   : $priority.val()
                };
            }

            /* render each wishlist item as a card */
            function render(list) {
                $results.empty();

                if (!Array.isArray(list) || list.length === 0) {
                    $results.html('<p class="text-center">No wishlist items found.</p>');
                    return;
                }

                list.forEach(item => {
                    // card container
                    const $card = $('<div>', {
                        class: 'wishlist-card card flex-between',
                        id   : item.GID
                    });

                    // left block: icon + title
                    const $left = $('<div>', { class: 'wishlist-left flex-center' })
                        .append($('<i>', { class: 'fas fa-gamepad fa-lg icon-left' }))
                        .append($('<div>', { class: 'title-block' })
                            .append($('<span>', {
                                class: 'game-title',
                                text : item.Title
                            }))
                        );

                    // middle block: styled priority + date
                    const $prioritySelect = $('<select>', {
                        class    : `priority-select p-${item.Priority}`,  // add p-1, p-2 or p-3
                        'data-gid': item.GID
                    })

                        .append('<option value="1" style= "color: black;">1 - Really Want</option>')
                        .append('<option value="2" style= "color: black;">2 - Want</option>')
                        .append('<option value="3" style= "color: black;">3 - Somewhat Want</option>')
                        .val(item.Priority);

                    const $priorityBox = $('<div>', { class: 'priority-box' })
                        .append($('<span>', { class: 'priority-label', text: 'Priority:' }))
                        .append($prioritySelect);

                    const $middle = $('<div>', { class: 'wishlist-meta' })
                        .append($priorityBox)
                        .append($('<span>', { text: `Added: ${item.Date}` }));

                    // right block: delete icon
                    const $right = $('<div>', { class: 'wishlist-actions' })
                        .append($('<i>', {
                            class    : 'fas fa-trash-alt delete-icon remove',
                            title    : 'Remove from wishlist',
                            'data-gid': item.GID
                        }));

                    // assemble and append
                    $card.append($left, $middle, $right).appendTo($results);
                });
            }

            /* delete handler */
            $results.on('click', '.remove', function () {
                const gid = $(this).data('gid');
                if (!confirm('Remove this game from your wishlist?')) return;

                $.post('includes/remove_wishlist.php',
                    { gid },
                    res => res.success ? fetchWishlist()
                                        : alert('Failed: ' + res.error),
                    'json');
            });

            /* priority-change handler */
            $results.on('change', '.priority-select', function () {
                const gid      = $(this).data('gid');
                const priority = $(this).val();

                $.post('includes/update_wishlist.php',
                    { gid, priority },
                    res => res.success ? fetchWishlist()
                                        : alert('Update failed: ' + res.error),
                    'json');
            });

            /* fetch + render the list */
            function fetchWishlist() {
                $.post('includes/fetch_wishlist.php',
                    getPayload(),
                    render,
                    'json')
                .fail(() => {
                    console.error('Fetch failed');
                    $results.html('<p class="error">Server error.</p>');
                });
            }

            /* wire up live filters */
            $(window).on('load',  fetchWishlist);
            $search .on('keyup',  fetchWishlist);
            $priority.on('change', fetchWishlist);
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