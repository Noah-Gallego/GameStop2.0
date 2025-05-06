<?php
require_once("includes/config.php");

// Remove Variables From Session
unset($_SESSION["logged_in"]);
unset($_SESSION["person_id"]);
unset($_SESSION['username']);

function logout() {
    header("refresh:3;url=index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <?php require_once 'includes/link.php'?>
    <link rel="stylesheet" href="assets/css/logout.css">
    <meta http-equiv="refresh" content="5;url=index.php">
</head>
<body onload="countdown(5)">
    <div class="logout-card"> <!-- NEW WRAPPER -->
        <div class="logout-container">
            <i class="fas fa-right-from-bracket logout-icon"></i>
            <h1 class="logout-text" id="text">Logging Out Now...</h1>
        </div>
    </div>

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

        const text = document.getElementById("text");
        async function countdown(x) {
            for (let i = x; i > 0; i--) {
                text.innerHTML = `
                    <span style="color: var(--mainColor); font-weight: bold;">Logging out now!</span><br>
                    Returning to Home in <span style="color: var(--mainColor); font-weight: bold;">${i}</span> seconds...
                `;
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }
    </script>
</body>
</html>