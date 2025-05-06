<?php
//----------------------------------------------------------
// users.php  – People finder page
//----------------------------------------------------------
require_once 'includes/config.php';
if (!isset($_SESSION['username'])) {
    header('Location: access_denied.php');
    exit();
}

function getCurrentUserName(): string
{
    return ucfirst($_SESSION['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>

    <!-- Font Awesome icons -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <?php require_once 'includes/link.php';?>
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <main id="users" class="container full-screen flex-margin">

        <!-- Banner -->
        <div class="center-div card welcome-card text-center mt-1-5"
            style="display:flex;justify-content:center;align-items:center;">
            <i class="fa-solid fa-user fa-bounce fa-lg" 
                style="color: #74C0FC; --fa-bounce-start-scale-x: 1; --fa-bounce-start-scale-y: 1;
                        --fa-bounce-jump-scale-x: 1; --fa-bounce-jump-scale-y: 1; --fa-bounce-land-scale-x: 1; 
                        --fa-bounce-land-scale-y: 1;">
            </i>
            <h1 class="text-bold" style="margin-left:3px;">Users</h1>
        </div>
        <!-- Search / Filter bar -->
        <section class="card filters">
            <div class="search-box">
                <label for="uname-input">
                    <i class="fas fa-user icon-left"></i> Username:
                </label>
                <input type="text"
                    id="uname-input"
                    name="username"
                    placeholder="e.g. shrek"
                    autocomplete="off">
            </div>

            <div class="search-box">
                <label for="domain-input">
                    <i class="fas fa-at icon-left"></i> Email Domain:
                </label>
                <input type="text"
                    id="domain-input"
                    name="domain"
                    placeholder="e.g. gmail.com"
                    autocomplete="off">
            </div>
            <div class="filter-group">
                <label for="order-input">
                    <i class="fas fa-sort-amount-down icon-left"></i> Order:
                </label>
                <select id="order-input" name="order">
                    <option value="ASC">A → Z</option>
                    <option value="DESC">Z → A</option>
                </select>
            </div>
        </section>
        
        <!-- Results list -->
        <section class="card results">
            <div id="results"></div>
        </section>
    </main>

    <!-- Finder logic -->
    <script>
        (function ($) {
            const $results = $('#results');
            const $uname   = $('#uname-input');
            const $domain  = $('#domain-input');
            const $order   = $('#order-input');   // ① grab the new select

            // build POST payload
            function getPayload () {            // ② include it in the payload
                return {
                    username : $uname.val().trim(),
                    domain   : $domain.val().trim(),
                    order    : $order.val()          // <─ NEW
                };
            }

            // draw list of matches – cards are links, text scales on small screens
            function render (list) {
                $results.empty();

                if (!Array.isArray(list) || list.length === 0) {
                    $results.html('<p class="text-center">No matches.</p>');
                    return;
                }

                list.forEach(u => {
                    /* full‑row link */
                    const $link = $('<a>', {
                        href  : `profile.php?uid=${u.UID}`,   // navigate to profile
                        class : 'card-link'
                    });

                    /* single‑line card */
                    const $card = $('<div>', { class: 'games-card card flex-between' })
                        .append(
                            $('<div>', { class: 'games-left flex-center' })
                                .append(
                                    $('<span>', {
                                        class : 'user-text text-bold',  // scalable class
                                        text  : u.email                 // show e‑mail (or username if you add it)
                                    })
                                )
                        );

                    $link.append($card).appendTo($results);
                });
            }


            // call the API
            function fetchUsers () {
                $.post('includes/fetch_users.php', getPayload(), render, 'json')
                .fail(() => $results.html('<p class="error">Server error.</p>'));
            }

            // first load + every keystroke
            $(window).on('load',  fetchUsers);
            $uname.on('input',   fetchUsers);
            $domain.on('input',   fetchUsers);
            $order.on('change', fetchUsers);
        })(jQuery);
    </script>

    <!-- Theme toggle reused from other pages -->
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
                const isRed = $('body').attr('data-theme') === 'red';
                const next  = isRed ? '' : 'red';
                localStorage.setItem('theme', next);
                applyTheme(next);
            };
        })(jQuery);
    </script>

</body>
</html>
