<?php
require_once ('includes/config.php');
require_once ('includes/auth.php');

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['SignUp'])) {
        $result = registerUser(
            $_POST['fname'],
            $_POST['lname'],
            $_POST['email'],
            $_POST['password']
        );

        unset($_POST['SignUp']);
    } elseif (isset($_POST['Login'])) {
        $result = loginUser(
            $_POST['email'] ?? '',
            $_POST['password'] ?? ''
        );

        if ($result['success']) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>GameStop2.0 - Login</title>
    <?php require_once 'includes/link.php'?>
    <!-- <link rel="stylesheet" href="assets/css/auth.css" /> -->
</head>
<body>
    <video autoplay muted loop id="myVideo">
        <source src="assets/media/Animated-purple.mp4" type="video/mp4" />
        Your browser does not support HTML5 video.
    </video>
    <div id="videoOverlay"></div>
    <!-- navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <section>
        <div class="container">
            <div class="row full-screen align-items-center">
                <div class="left">
                    <span class="line"></span>
                    <h2>This is <br/><span>GameStop2.0 portal</span></h2>
                    <div class="social-media">
                        <a href="https://github.com/Noah-Gallego/GameStop2.0">
                            <i class="fa-brands fa-github"></i>
                        </a>
                    </div>
                </div>
                <div class="right">
                    <div class="form">
                        <div class="text-center">
                            <h6><span>Log In</span> <span>Sign Up</span></h6>
                            <input type="checkbox" class="checkbox" id="reg-log"/>
                            <label for="reg-log"></label>

                            <div class="card-3d-wrap">
                                <div class="card-3d-wrapper">
                                    <!-- LOGIN -->
                                    <div class="card-front">
                                        <div class="center-wrap">
                                            <form method="POST">
                                                <h4 class="heading">Log In</h4>
                                                <div class="form-group">
                                                    <input name="email" type="email" class="form-style" placeholder="Your Email" required />
                                                    <i class="input-icon material-icons">alternate_email</i>
                                                </div>
                                                <div class="form-group">
                                                    <input name="password" type="password" class="form-style" placeholder="Your Password" required />
                                                    <i class="input-icon material-icons">lock</i>
                                                </div>
                                                <button type="submit" value="Login" name="Login" class="btn">Submit</button>
                                                <p class="text-center"><a href="./reset.php" class="link">Forgot your password?</a></p>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- SIGNUP -->
                                    <div class="card-back">
                                        <div class="center-wrap">
                                            <form method="POST">
                                                <h4 class="heading">Sign Up</h4>
                                                <div class="form-group">
                                                    <input name="fname" type="text" class="form-style" placeholder="First Name" required />
                                                    <i class="input-icon material-icons">person</i>
                                                </div>
                                                <div class="form-group">
                                                    <input name="lname" type="text" class="form-style" placeholder="Last Name" required />
                                                    <i class="input-icon material-icons">person_outline</i>
                                                </div>
                                                <div class="form-group">
                                                    <input name="email" type="email" class="form-style" placeholder="Your Email" required />
                                                    <i class="input-icon material-icons">alternate_email</i>
                                                </div>
                                                <div class="form-group">
                                                    <input name="password" type="password" class="form-style" placeholder="Your Password" required />
                                                    <i class="input-icon material-icons">lock</i>
                                                </div>
                                                <button type="submit" name="SignUp" value ="SignUp"class="btn">Submit</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($error)): ?>
                        <p class="text-center" style="color:red; margin-top:10px;"> <?= htmlspecialchars($error) ?> </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- Theme toggle script -->
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