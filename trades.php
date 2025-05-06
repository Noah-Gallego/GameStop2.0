<?php
/* trades.php – GameStop 2.0 » My Trades */
require_once 'includes/config.php';

if (!isset($_SESSION['uid'], $_SESSION['username'])) {
    header('Location: access_denied.php');
    exit();
}
$uname = ucfirst($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0">
    <title>GameStop 2.0 — My Trades</title>

    <?php require_once 'includes/link.php'; ?>
</head>
    <body>
        <?php require_once 'includes/navbar.php'; ?>

        <main id="trades" class="container full-screen flex-margin">
            
            <div class="center-div card welcome-card text-center mt-1">
                <h1 class="text-bold">
                    Welcome, <?= $uname; ?>! 👋</h1>
                <p class="mt-1">Here’s your trade history.</p>
            </div>

            <!---- filter bar ---->
            <section class="card filters">
                <div class="search-box">
                    <label for="search-input">Search titles / partner:</label>
                    <input type="text"
                        id="search-input"
                        name="search_data"
                        placeholder="Type to search…"
                        autocomplete="off">
                </div>

                <fieldset class="status-box">
                    <legend>Status:</legend>
                    <label>
                        <input
                            type="checkbox"
                            name="status"
                            value="Pending"> Pending
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            name="status"
                            value="Completed"> Completed
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            name="status"
                            value="Cancelled"> Cancelled
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            name="status"
                            value="Failed"> Failed
                    </label>
                </fieldset>
            </section>

            <!---- results list ---->
            <section class="card results">
                <h2 class="text-center">My Trades</h2>
                <div id="results"></div>
            </section>
        </main>

        <script>
            /* --------------------------------------------------------------
            * Live trade history (search + status filter) – jQuery required
            * ---------------------------------------------------------------*/
            (function ($) {
                const $results  = $('#results');
                const $search   = $('#search-input');
                const $statusCB = $('input[name="status"]');

                function getPayload() {
                    return {
                        search_data: $search.val().trim(),
                        status:      $statusCB.filter(':checked')
                                            .map((_,el)=>el.value).get() // array
                    };
                }

                function render(list) {
                    $results.empty();
                    if (!Array.isArray(list) || list.length === 0) {
                        $results.html('<p>No trades found.</p>');
                        return;
                    }
                    list.forEach(t => {
                        $('<div>', { class:'trade-row' })
                        .append($('<span>',{class:'game sent',
                                            text:`Game sent: ${t.SentGame}`}))
                        .append($('<span>',{class:'arrow',html:'↔️'}))
                        .append($('<span>',{class:'game recv',
                                            text:`Game received: ${t.ReceivedGame}`}))
                        .append($('<span>',{class:'partner',
                                            text:'@'+t.TradePartner}))
                        .append($('<span>',{class:'state',
                                            text:`Status: ${t.Status}`}))
                        .appendTo($results);
                    });
                }

                function fetchTrades() {
                    $.post('includes/fetch_trades.php', getPayload(), render, 'json')
                    .fail(()=> $results.html('<p class="error">Server error.</p>'));
                }

                $(window).on('load', fetchTrades);     // initial load
                $search.on('keyup', ()=>fetchTrades()); // live text
                $statusCB.on('change',()=>fetchTrades());// checkbox click
            })(jQuery);
        </script>
        <!-- Theme toggle script -->
        <script>
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
        </script>
    </body>
</html>
