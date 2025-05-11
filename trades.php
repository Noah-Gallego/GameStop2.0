<?php
/* trades.php — GameStop 2.0 » My Trades (history page) */
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
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>GameStop 2.0 — My Trades</title>

    <?php require_once 'includes/link.php'; ?>
    <style>
        /* ----------------------------------------
        Hover-to-show action buttons
        ---------------------------------------- */
        .trade-row           { position: relative; padding-right: 7rem; }   /* space for buttons */
        .trade-actions       { display:none; position:absolute; top:50%; right:0.5rem;
                            transform:translateY(-50%); gap:0.4rem; }
        .trade-row:hover .trade-actions { display:inline-flex; }

        /* optional: mobile tap – show on focus as well */
        .trade-row:focus-within .trade-actions { display:inline-flex; }

        /* make the action buttons square & icon-only */
        .trade-actions button {
            width: 1.6rem;
            height: 1.6rem;
            padding: 0;
            font-size: 1.2rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;           /* or your existing border style */
            background: transparent; /* you can swap in your .btn-accept colors here */
        }
        .btn-accept  { color: #4caf50; }  /* green check */
        .btn-decline { color: #f44336; }  /* red X     */
        .btn-cancel  { color:rgb(255, 98, 0); }  /* orange X  */
    </style>

</head>
<body>
<?php require_once 'includes/navbar.php'; ?>

<main id="trades" class="container full-screen flex-margin">

    <!-- welcome banner -->
    <div class="center-div card welcome-card text-center mt-1">
        <h1 class="text-bold">Welcome, <?= $uname; ?>! 👋</h1>
        <p class="mt-1">Here's your trade history.</p>
    </div>

    <!-- filter bar -->
    <section class="card filters">
        <div class="search-box">
            <label for="search-input">Search titles / partner:</label>
            <input  type="text"
                    id="search-input"
                    name="search_data"
                    placeholder="Type to search…"
                    autocomplete="off">
        </div>

        <fieldset class="status-box">
            <legend>Status:</legend>
            <?php foreach (['Pending','Completed','Cancelled'] as $state): ?>
                <label>
                    <input  type="checkbox"
                            name="status"
                            value="<?= $state; ?>"> <?= $state; ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </section>

    <section class="card results">
        <div class="sub-card mt-1-5">
            <h2 class="text-center">Pending • Trades You Initiated</h2>
            <div id="initiated-results"></div>
        </div>

        <div class="sub-card mt-1-5">
            <h2 class="text-center">Pending • Trades You Received</h2>
            <div id="received-results"></div>
        </div>

        <div class="sub-card mt-1-5">
            <h2 class="text-center">Completed Trades</h2>
            <div id="completed-results"></div>
        </div>

        <div class="sub-card mt-1-5">
            <h2 class="text-center">Cancelled Trades</h2>
            <div id="cancelled-results"></div>
        </div>
    </section>

</main>

    <!-- ------------------------------------------------
        Live search + filters (jQuery)
        -------------------------------------------------
        This jQuery snippet:

        1. Pulls your trade history from fetch_trades.php (AJAX).
        2. Puts each trade into one of four boxes:
            • Pending you **started**   • Pending you **received**
            • Completed                 • Cancelled
        3. Adds ✓ / ✖ buttons to Pending rows so you can
        Accept / Decline (receiver) or Cancel (sender).
        4. After every action or filter change it refreshes the list,
        so the page updates without re-loading.
    -->
    <script>
        (function ($) {
            /* ---------- cached DOM refs ---------- */
            const uid     = <?= $_SESSION['uid'] ?>;            // your user-id
            const $init   = $('#initiated-results');
            const $recv   = $('#received-results');
            const $comp   = $('#completed-results');
            const $cncl   = $('#cancelled-results');
            const $search = $('#search-input');
            const $status = $('input[name="status"]');

            /* build POST body for search + status filter */
            const payload = () => ({
                search_data : $search.val().trim(),
                status      : $status.filter(':checked').map((_,e)=>e.value).get()
            });

            /* plain row (no buttons) */
            function baseRow(t) {
                return $('<div>', {class:'trade-row', tabindex:0})
                .append($('<span>',{class:'game sent', text:`You sent: ${t.SentGame||'[none]'}`}))
                .append($('<span>',{class:'arrow',     html:'↔️'}))
                .append($('<span>',{class:'game recv', text:`You received: ${t.ReceivedGame||'[none]'}`}))
                .append($('<span>',{class:'partner',   text:'@'+t.TradePartner}))
                .append($('<span>',{class:'state',     text:`Status: ${t.Status}`}));
            }

            /* add ✓ / ✖ buttons for Pending rows */
            function actionButtons(t, role) {
                if (t.Status !== 'Pending') return $();
                const $w = $('<span>',{class:'trade-actions'});
                if (role==='receiver') {
                    $('<button>',{class:'btn-accept',html:'✔'})
                    .on('click',e=>{e.stopPropagation(); decide(t.TID,'Accepted');})
                    .appendTo($w);
                    $('<button>',{class:'btn-decline',html:'✖'})
                    .on('click',e=>{e.stopPropagation(); decide(t.TID,'Declined');})
                    .appendTo($w);
                } else {
                    $('<button>',{class:'btn-cancel',html:'✖'})
                    .on('click',e=>{e.stopPropagation(); cancel(t.TID);})
                    .appendTo($w);
                }
                return $w;
            }
            const buildRow = (t,role)=> baseRow(t).append(actionButtons(t,role));

            /* render server data into the four panels */
            function render(data) {
                // 1) Clear out old rows in each section
                [$init, $recv, $comp, $cncl].forEach($c => $c.empty());

                // 2) Split trades by status and perspective:
                //    • pendInit: pending trades you started
                const pendInit = data.initiated.filter(t => t.Status === 'Pending');
                //    • pendRecv: pending trades you received
                const pendRecv = data.received .filter(t => t.Status === 'Pending');
                //    • done: all completed trades (either side)
                const done     = [...data.initiated, ...data.received]
                                .filter(t => t.Status === 'Completed');
                //    • cncl: all cancelled trades (either side)
                const cncl     = [...data.initiated, ...data.received]
                                .filter(t => t.Status === 'Cancelled');

                // 3) Helper to fill a container or hide it if empty
                //    $c     = jQuery container (e.g. $init)
                //    rows   = array of trades for this section
                //    rowFn  = function to create the HTML for each row
                const fill = ($c, rows, rowFn) => {
                    if (rows.length) {
                        // if we have rows, build & append each one
                        rows.forEach(r => $c.append(rowFn(r)));
                    } else {
                        // otherwise hide the entire sub-card
                        $c.parent('.sub-card').hide();
                    }
                };

                // 4) Render each section:
                fill($init, pendInit, t => buildRow(t, 'sender'));   // your initiated pending
                fill($recv, pendRecv, t => buildRow(t, 'receiver')); // your received pending
                fill($comp, done,     baseRow);                     // completed (no buttons)
                fill($cncl, cncl,     baseRow);                     // cancelled (no buttons)
            }


            /* --- API calls for accept/decline/cancel --- */
            const decide = (tid,choice)=>
                $.post('api/trades.php',{action:'update',tid,uid_receiver:uid,status2:choice})
                .always(fetchTrades);

            const cancel = tid=>
                $.post('api/trades.php',{action:'update',tid,uid_sender:uid,status1:'Declined'})
                .always(fetchTrades);

            /* --- load + re-load list --- */
            const fetchTrades = ()=>
                $.post('includes/fetch_trades.php',payload(),render,'json')
                .fail(()=>$('.card.results div').html('<p class="error">Server error.</p>'));

            $(window).on('load',fetchTrades);
            $search.on('keyup',fetchTrades);
            $status.on('change',fetchTrades);
        })(jQuery);
    </script>


    <!-- theme toggle (unchanged) -->
    <script>
        (function ($) {
            /* helper: apply chosen theme */
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

            /* run once DOM ready */
            $(document).ready(function () {
                const saved = localStorage.getItem('theme') || '';
                applyTheme(saved);
                $('#theme-switch').on('click', toggleTheme);
            });

            /* global toggle for the navbar button */
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
