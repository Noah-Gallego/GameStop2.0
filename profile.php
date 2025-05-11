<?php
require_once 'includes/config.php';

$uid = $_SESSION['uid'] ?? null;
$isOwnProfile = true;

if (isset($_GET['uid']) && (!isset($_SESSION['uid']) || $_GET['uid'] != $_SESSION['uid'])) {
    $uid = intval($_GET['uid']);
    $isOwnProfile = false;
}

if (!$uid) {
    header("Location: access_denied.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <?php require_once 'includes/link.php'; ?>
</head>

<body>
    <?php require_once 'includes/navbar.php'; ?>
    <main class="dashboard container full-screen">
        <section class="profile-card">
            <?php if ($isOwnProfile): ?>
                <div class="card-header">
                    <h1>🔒 Update Your Account</h1>
                    <p class="subtext">You can update your email and change your password here.</p>
                </div>

                <form id="update-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-row">
                            <input type="email" id="email" name="email" />
                            <button type="button" id="change-email" class="btn">Change</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-row">
                            <input type="password" id="password" name="password" />
                            <button class="btn invisible-btn" disabled>Change</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="input-row">
                            <input type="password" id="confirm-password" name="confirm-password" />
                            <button type="button" id="change-password" class="btn">Change</button>
                        </div>
                    </div>

                    <div id="status-container">
                        <div id="status"></div>
                    </div>
                </form>
            <?php else: ?>
                <div class="user-public-profile">
                    <div class="avatar-circle" id="avatar-initial">?</div>
                    <h2 id="public-username">Username</h2>
                    <button id="trade-btn" class="btn">Trade</button>
                    <h3 class="wishlist-header">Wishlist</h3>
                    <div id="legend">
                        Text
                    </div>
                    <ul class="wishlist-items" id="wishlist-items">
                        <li>Loading wishlist...</li>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- ─────────── TRADE OVERLAY ─────────── -->
    <div id="trade-overlay" class="search-overlay">
        <div class="overlay-box trade-box">
            <button id="trade-close" class="overlay-close" title="Close">✕</button>

            <h2 class="text-center mb-1">Propose a Trade</h2>

            <div class="games-wrapper">
            <!-- current-user column (LEFT) -->
            <div class="games-column">
                <h3 class="column-header text-center">Your games</h3>
                <ul id="your-games"></ul>
            </div>

            <!-- profile-owner column (RIGHT) -->
            <div class="games-column">
                <h3 class="column-header text-center">Their games</h3>
                <ul id="their-games"></ul>
            </div>
            </div>

            <button id="trade-confirm" class="btn mt-1-5 full-width" disabled>
            Send trade request
            </button>
        </div>
    </div>
    <!-- ──────────────────────────────────── -->

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

    <script>
        const uid = <?= json_encode($uid); ?>;
        const isOwnProfile = <?= json_encode($isOwnProfile); ?>;
        const status = $('#status');

        $.post('api/users.php', { action: 'read', uid_other: uid }, function (data) {
            if (!Array.isArray(data) || data.length === 0) {
                if (status.length) showStatus('Error loading profile.', false);
                return;
            }

            const user = data[0];

            if (isOwnProfile) {
                $('#email').val(user.email);
            } else {
                const username = user.email.split('@')[0];
                const initial = username.charAt(0).toUpperCase();
                const legend = `<div class = "fas fa-star wishlist-icon"></div><div id = "key-username"> &nbsp; = &nbsp; Game you have in your inventory on ${username}'s wishlist.</div>`
                $('#avatar-initial').text(initial);
                $('#public-username').text(username);
                $('#legend').html(legend);

                $.post('includes/fetch_wishlist.php', { uid_other: uid }, function (res) {
                    const container = $('#wishlist-items');
                    if (Array.isArray(res) && res.length > 0) {
                        $.post('includes/fetch_games.php', {}, function (ownedGames) {
                            const ownedTitles = new Set((ownedGames || []).map(g => g.Title));
                            container.html(res.map(item => {
                                const hasGame = ownedTitles.has(item.Title);
                                const icon = hasGame ? '<i class="fas fa-star wishlist-icon"></i>' : '';
                                const gid = item.GID ?? '';
                                return `<li><a href="games.php?gid=${gid}">${icon} ${item.Title}</a></li>`;
                            }).join(''));
                        }, 'json');
                    } else {
                        container.html('<li>No wishlist items found.</li>');
                    }
                }, 'json');
            }
        }, 'json');

        if (isOwnProfile) {
            $('#change-email').click(function () {
                $.post('api/users.php', {
                    action: 'update',
                    uid: uid,
                    uid_other: uid,
                    new_email: $('#email').val()
                }, function (res) {
                    showStatus(res.success ? 'Email updated successfully!' : 'Error updating email.', res.success);
                }, 'json');
            });

            $('#update-form').on('submit', function (e) {
                e.preventDefault();
            });

            $('#change-password').click(function () {
                if ($('#password').val() === $('#confirm-password').val()) {
                    $.post('api/users.php', {
                        action: 'update',
                        uid: uid,
                        uid_other: uid,
                        new_password: $('#password').val()
                    }, function (res) {
                        showStatus(res.success ? 'Password updated successfully!' : 'Error updating password.', res.success);
                    }, 'json');
                } else {
                    showStatus("Passwords do not match. Please try again.", false);
                }
            });

            function showStatus(message, isSuccess = true) {
                const icon = isSuccess
                    ? '<i class="fas fa-check-circle"></i>'
                    : '<i class="fas fa-times-circle"></i>';
                status.removeClass('status-success status-error')
                    .addClass(isSuccess ? 'status-success' : 'status-error')
                    .hide()
                    .html(`${icon} ${message}`)
                    .fadeIn(300);
                setTimeout(() => status.fadeOut(400), 5000);
            }
        }
    </script>


    <!-- TRADE OVERLAY FUNCTIONALITY BEGIN-->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* ——— don’t show Trade overlay on your own profile ——— */
            if (isOwnProfile) return;

            /* element refs */
            const tradeBtn   = document.querySelector('.user-public-profile .btn'); // Trade button
            const overlay    = document.getElementById('trade-overlay');
            const closeBtn   = document.getElementById('trade-close');
            const confirmBtn = document.getElementById('trade-confirm');
            const theirUL    = document.getElementById('their-games');
            const yourUL     = document.getElementById('your-games');

            let pickTheirs = null;   // {gid, title}
            let pickYours  = null;

            /* ——— open / close ——— */
            function openOvl() {
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
                loadLists();
            }
            function closeOvl() {
                overlay.classList.remove('open');
                document.body.style.overflow = '';
                theirUL.innerHTML = yourUL.innerHTML = '';
                pickTheirs = pickYours = null;
                confirmBtn.disabled = true;
            }
            tradeBtn.addEventListener('click', openOvl);
            closeBtn.addEventListener('click', closeOvl);
            overlay .addEventListener('click', e => { if (e.target === overlay) closeOvl(); });

            /* ——— fetch both inventories ——— */
            function loadLists() {
                fetch('api/trade_overlay.php', {
                method :'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body   :'uid_them='+encodeURIComponent(uid)
                })
                .then(r=>r.json())
                .then(d => {
                if (d.error) throw d.error;
                renderList(yourUL , d.yours , 'your' );  // current user left
                renderList(theirUL, d.theirs, 'their');  // profile owner right
                })
                .catch(err => {
                yourUL .innerHTML = theirUL.innerHTML = '<li>Error loading games</li>';
                console.error(err);
                });
            }

            /* ——— render helpers ——— */
            function renderList(ul, arr, side) {
                ul.innerHTML = arr.map(g=>`
                <li data-side="${side}" data-gid="${g.GID}" data-title="${g.Title}">
                    ${g.Image ? `<img src="${g.Image}" alt="">`
                            : `<svg width="800px" height="800px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
    <path d="m 4 1 c -1.644531 0 -3 1.355469 -3 3 v 1 h 1 v -1 c 0 -1.109375 0.890625 -2 2 -2 h 1 v -1 z m 2 0 v 1 h 4 v -1 z m 5 0 v 1 h 1 c 1.109375 0 2 0.890625 2 2 v 1 h 1 v -1 c 0 -1.644531 -1.355469 -3 -3 -3 z m -5 4 c -0.550781 0 -1 0.449219 -1 1 s 0.449219 1 1 1 s 1 -0.449219 1 -1 s -0.449219 -1 -1 -1 z m -5 1 v 4 h 1 v -4 z m 13 0 v 4 h 1 v -4 z m -4.5 2 l -2 2 l -1.5 -1 l -2 2 v 0.5 c 0 0.5 0.5 0.5 0.5 0.5 h 7 s 0.472656 -0.035156 0.5 -0.5 v -1 z m -8.5 3 v 1 c 0 1.644531 1.355469 3 3 3 h 1 v -1 h -1 c -1.109375 0 -2 -0.890625 -2 -2 v -1 z m 13 0 v 1 c 0 1.109375 -0.890625 2 -2 2 h -1 v 1 h 1 c 1.644531 0 3 -1.355469 3 -3 v -1 z m -8 3 v 1 h 4 v -1 z m 0 0" fill="#2e3434" fill-opacity="0.34902"/>
</svg>`}
                    ${g.Title}
                </li>
                `).join('');
                ul.addEventListener('click', pickHandler);
            }

            function pickHandler(e) {
                const li = e.target.closest('li'); if (!li) return;
                const side = li.dataset.side;
                const gid  = li.dataset.gid;
                const ttl  = li.dataset.title;

                (side==='your'?yourUL:theirUL)
                .querySelectorAll('li.selected').forEach(x=>x.classList.remove('selected'));
                li.classList.add('selected');

                side==='your' ? pickYours  = {gid,ttl} : pickTheirs = {gid,ttl};
                confirmBtn.disabled = !(pickYours || pickTheirs);   // one is enough
            }

            /* ——— create trade ——— */
            confirmBtn.addEventListener('click', () => {
                if (confirmBtn.disabled) return;

                fetch('api/trades.php', {
                method :'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body   : new URLSearchParams({
                    action      :'create',
                    uid_sender  : <?= json_encode($_SESSION['uid']); ?>,
                    uid_receiver: uid,
                    gid1 : pickYours  ? pickYours.gid  : '',   // sender’s game or NULL
                    gid2 : pickTheirs ? pickTheirs.gid : ''    // receiver’s game or NULL
                })
                })
                .then(r=>r.json())
                .then(res=>{
                alert(res.success ? 'Trade request sent!' : 'Error: '+(res.error||''));
                if (res.success) closeOvl();
                })
                .catch(()=>alert('Server error'));
            });
        });
    </script>
    <!-- TRADE OVERLAY FUNCTIONALITY ENDS-->

</body>

</html>