<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Access Denied - GameStop2.0</title>

    <?php require_once 'includes/link.php'?>
</head>
<body>

    <video autoplay muted loop id="myVideo">
        <source id="video" src="assets/media/Animated-purple.mp4" type="video/mp4" />
        Your browser does not support HTML5 video.
    </video>

    <div id="videoOverlay"></div>

    <section>
        <div class="container">
            <div class="row full-screen align-items-center justify-content-center">
                <div class="center-wrap" style="text-align: center;">

                    <!-- DISCONNECTED ICON -->
                    <i class="fas fa-plug-circle-xmark" style="font-size: 90px; color: var(--mainColor); margin-bottom: 20px;"></i>
                    
                    <!-- HEADLINE -->
                    <h2 style="font-size: 48px; color: var(--white); margin-bottom: 20px;">Access <span style="color: var(--mainColor);">Denied</span></h2>

                    <!-- SUBTEXT -->
                    <p style="color: var(--whiteSmoke); font-size: 18px; margin-bottom: 30px;">
                        Connection lost. Please log in to reconnect.
                    </p>

                    <!-- BUTTON -->
                    <a href="index.php" class="btn">Return to Login</a>

                </div>
            </div>
        </div>
    </section>

    <script>
            (function($) {
                function applyTheme(theme) {
                    const $body   = $('body');
                    const $btn    = $('#theme-switch');
                    const $src    = $('video source');

                    if (theme === 'red') {
                        $body.attr('data-theme', 'red');
                        $btn.attr('title', 'Switch to Purple Theme');
                        $src.attr('src', 'assets/media/Animated-purple.mp4');
                    } else {
                        $body.attr('data-theme', 'purple');
                        $btn.attr('title', 'Switch to Red Theme');
                        $src.attr('src', 'assets/media/Animated-red.mp4');
                    }

                    const vid = $src.closest('video')[0];
                    if (vid) vid.load();
                }

                $(function() {
                    const saved = localStorage.getItem('theme') || 'purple';
                    applyTheme(saved);
                    $('#theme-switch').on('click', function(e) {
                        e.preventDefault();
                        const current = $('body').attr('data-theme');
                        const next    = current === 'red' ? 'purple' : 'red';
                        localStorage.setItem('theme', next);
                        applyTheme(next);
                    });
                });
            })(jQuery);
        </script>
</body>
</html>