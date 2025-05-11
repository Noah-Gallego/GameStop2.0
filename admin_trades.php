<?php
/* admin_trades.php – GameStop 2.0 » Admin Trades */
require_once 'includes/config.php';

if (!isset($_SESSION['uid'], $_SESSION['username'], $_SESSION['admin'])) {
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
    <title>GameStop 2.0 — Admin Trades</title>
    <?php require_once 'includes/link.php'; ?>
</head>
<body>
    <?php require_once 'includes/navbar.php'; ?>

    <main id="admin-trades" class="container full-screen flex-margin">
        <div class="center-div card welcome-card text-center mt-1">
            <h1 class="text-bold">Admin Dashboard 👨‍💻</h1>
            <p class="mt-1">Welcome, <?= $uname; ?>! View and manage all trades.</p>
        </div>

        <!-- Filter Bar -->
        <section class="card filters">
            <div class="search-box">
                <label for="search-input">Search titles / usernames:</label>
                <input type="text" id="search-input" name="search_data" placeholder="Type to search…" autocomplete="off">
            </div>

            <fieldset class="status-box">
                <legend>Status:</legend>
                <?php foreach (['Pending', 'Completed', 'Cancelled', 'Failed'] as $status): ?>
                    <label>
                        <input type="checkbox" name="status" value="<?= $status ?>"> <?= $status ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button onclick="exportToCSV()" class="btn">📥 Export CSV</button>
        </section>

        <!-- Results List -->
        <section class="card results">
            <h2 class="text-center">All Trades</h2>
            <div id="results"></div>
        </section>
    </main>

    <script>
    (function ($) {
        const $results  = $('#results');
        const $search   = $('#search-input');
        const $statusCB = $('input[name="status"]');

        function getPayload() {
            return {
                search_data: $search.val().trim(),
                status: $statusCB.filter(':checked').map((_, el) => el.value).get()
            };
        }

        function render(list) {
            $results.empty();
            if (!Array.isArray(list) || list.length === 0) {
                $results.html('<p>No trades found.</p>');
                return;
            }

            list.forEach(t => {
                $('<div>', { class: 'trade-row' })
                    .append($('<span>', { class: 'game sent', text: `Sent: ${t.SenderGame}` }))
                    .append($('<span>', { class: 'arrow', html: '↔️' }))
                    .append($('<span>', { class: 'game recv', text: `Received: ${t.ReceiverGame}` }))
                    .append($('<span>', { class: 'partner', text: `From: @${t.Sender}` }))
                    .append($('<span>', { class: 'partner', text: `To: @${t.Receiver}` }))
                    .append($('<span>', { class: 'license', text: `License1: ${t.License1 || 'N/A'}` }))
                    .append($('<span>', { class: 'license', text: `License2: ${t.License2 || 'N/A'}` }))
                    .append($('<span>', { class: 'state', text: `Status: Completed!` }))
                    .append($('<span>', { class: 'completed', text: `Completed: ${t.CompletedAt}` }))
                    .appendTo($results);
            });
        }

        function fetchTrades() {
            $.post('includes/admin_fetch_trades.php', getPayload(), render, 'json')
                .fail(() => $results.html('<p class="error">Server error.</p>'));
        }

        function exportToCSV() {
            $.post('includes/admin_fetch_trades.php', getPayload(), function (data) {
                if (!Array.isArray(data) || data.length === 0) return alert('No data to export.');

                const csv = [Object.keys(data[0]).join(',')];
                data.forEach(r => {
                    csv.push(Object.values(r).map(v => `"${v}"`).join(','));
                });

                const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'Admin_Trades.csv';
                a.click();
                URL.revokeObjectURL(url);
            }, 'json');
        }

        $(window).on('load', fetchTrades);
        $search.on('keyup', fetchTrades);
        $statusCB.on('change', fetchTrades);

        window.exportToCSV = exportToCSV;
    })(jQuery);
    </script>
</body>
</html>