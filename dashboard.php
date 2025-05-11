<?php
/* dashboard.php – GameStop 2.0 */
require_once 'includes/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: access_denied.php');
    exit();
}
$admin = isset($_SESSION['admin']);

function getCurrentUserName(): string {
    return ucfirst($_SESSION['username']);
}

function fetchCount(string $table, int $uid): int {
    $allowed = ['Has', 'Wants', 'Trades']; // prevent SQL injection
    if (!in_array($table, $allowed)) {
        return 0;
    }

    $db = get_pdo_connection();

    $sql = '';
    if ($table == 'Trades') {
        $sql = "SELECT COUNT(*) FROM $table WHERE uid1 = :uid OR uid2 = :uid";
    } else {
        $sql = "SELECT COUNT(*) FROM $table WHERE UID = :uid";
    }


    $stmt = $db->prepare($sql);
    $stmt->execute([':uid' => $uid]);
    return (int) $stmt->fetchColumn();
}

?>
<!DOCTYPE html>
<html lang="en">
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width,initial-scale=1.0">
    <title>GameStop 2.0 — Dashboard</title>

    <link rel = "stylesheet" href = "assets/css/dashboard.css" type = "text/css">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <?php require_once 'includes/link.php'?>
</head>
<body>

    <!-- Persistent nav -->
    <?php require_once 'includes/navbar.php'; ?>

    <main class="dashboard container full-screen">

        <!-- Welcome banner with action tiles inside -->
        <section cla ss="mt-1-5">
            <div class="card welcome-card text-center">
                <div class = "intro-content">
                    <div class="intro-text">
                        <h1 class="text-bold">Welcome back, <?= getCurrentUserName(); ?> 👋</h1>
                        <p class="subtext">You're back — the games missed you.</p>
                    </div>
                    <div class = "intro-logout">
                        <a href="logout.php" class="btn logout-btn mt-1">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </a>
                        <a href = "profile.php" class = "profile">
                            <p class = "profile-initial"><?= getCurrentUserName()[0] ?></p>
                        </a>
                    </div>
                </div>

                <!-- Modern quick-action tiles -->
                <div class="dashboard-tiles">
                    <a href="inventory.php" class="stat-card">
                        <i class="fas fa-gamepad fa-2x icon"></i>
                        <span class="stat-label">Inventory</span>
                        <span class="stat-value">
                            <?= fetchCount("Has", $_SESSION['uid']); ?>
                        </span>
                    </a>

                    <a href="wishlist.php" class="stat-card">
                        <i class="fas fa-heart fa-2x icon"></i>
                        <span class="stat-label">Wishlist</span>
                        <span class="stat-value">
                            <?= fetchCount("Wants", $_SESSION['uid']); ?>
                        </span>
                    </a>

                    <a href="trades.php" class="stat-card">
                        <i class="fas fa-exchange-alt fa-2x icon"></i>
                        <span class="stat-label">Trades</span>
                        <span class="stat-value">
                            <?= fetchCount("Trades", $_SESSION['uid']); ?>
                        </span>
                    </a>

                    <a href="games.php" class="stat-card">
                        <i class="fas fa-star fa-2x icon"></i>
                        <span class="stat-label">Explore</span>
                        <span class="stat-value">4.5</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Pie charts -->
        <section class="grid grid-3 mt-1 dashboard-charts">

            <div class="card text-center">
                <h3 class="text-bold mb-0-5">Top 20 Games in Inventory</h3>
                <canvas id="chartHas" height="240"></canvas>
            </div>

            <div class="card text-center">
                <h3 class="text-bold mb-0-5">Trades – Started vs Received</h3>
                <canvas id="chartTradeDir" height="240"></canvas>
            </div>

            <div class="card text-center">
                <h3 class="text-bold mb-0-5">Trade Status</h3>
                <canvas id="chartTradeState" height="240"></canvas>
            </div>

        </section>
    </main>
    <!-- theme toggle script -->
    <script>
        $(function ($) {
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
            });
    </script>
        <?php
            /* ---------- fetch counts from the views ---------- */
            $uid = (int)$_SESSION['uid'];
            $pdo = get_pdo_connection();

            /* inventory */
            $stmt = $pdo->prepare("SELECT Title, cnt FROM v_user_has_counts WHERE UID = :uid LIMIT 20");
            $stmt->execute([':uid'=>$uid]);
            $has   = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);  // [title => cnt]

            /* trade direction */
            $stmt = $pdo->prepare("SELECT label, cnt FROM v_user_trade_direction WHERE uid = :uid");
            $stmt->execute([':uid'=>$uid]);
            $dir   = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);  // ['Started'=> n , 'Received'=> m]

            /* trade states */
            $stmt = $pdo->prepare("SELECT label, cnt FROM v_user_trade_states WHERE uid = :uid");
            $stmt->execute([':uid'=>$uid]);
            $state = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);  // ['Pending'=>..,'Completed'=>..]

            /* helper */
            function jsKeys($a){ return json_encode(array_keys($a)); }
            function jsVals($a){ return json_encode(array_values($a)); }
        ?>

        <script>
        (() => {
        /* -------- data from PHP -------- */
        const hasLabels  = <?= jsKeys($has)   ?>;
        const hasCounts  = <?= jsVals($has)   ?>;

        const dirLabels  = <?= jsKeys($dir)   ?>;
        const dirCounts  = <?= jsVals($dir)   ?>;

        const stLabels   = <?= jsKeys($state) ?>;
        const stCounts   = <?= jsVals($state) ?>;

        /* -------- basic colour helper --- */
        function makeColors(n){
            const base=['#5f27cd','#10ac84','#ff6b6b','#ffa502',
                        '#54a0ff','#ff9f43','#1dd1a1','#f368e0'];
            return [...Array(n)].map((_,i)=>base[i%base.length]);
        }
        const opts = {
            responsive: true,
            maintainAspectRatio: true,      // keep 1:1 ratio
            plugins:{
                legend:{
                position:'bottom',
                labels:{
                    boxWidth:15,              // smaller legend boxes
                    font:{ size:11 }          // smaller text on phones
                }
                }
            },
            layout:{ padding:12 }           // breathing room on tiny screens
        };

        new Chart(chartHas,{
            type:'pie',
            data:{labels:hasLabels,datasets:[{data:hasCounts,
                    backgroundColor:makeColors(hasCounts.length)}]},
            options:opts});

        new Chart(chartTradeDir,{
            type:'pie',
            data:{labels:dirLabels,datasets:[{data:dirCounts,
                    backgroundColor:makeColors(dirCounts.length)}]},
            options:opts});

        new Chart(chartTradeState,{
            type:'pie',
            data:{labels:stLabels,datasets:[{data:stCounts,
                    backgroundColor:makeColors(stCounts.length)}]},
            options:opts});
        })();

        function getRandomColor() {
            const min = 40;   // Avoid very dark colors
            const max = 200;  // Avoid very light colors (255 is white)

            const r = Math.floor(Math.random() * (max - min) + min);
            const g = Math.floor(Math.random() * (max - min) + min);
            const b = Math.floor(Math.random() * (max - min) + min);

            return `#${((1 << 24) + (r << 16) + (g << 8) + b)
                .toString(16)
                .slice(1)}`;
        }

        let randomColor = getRandomColor();
        document.documentElement.style.setProperty('--main-profile-color', randomColor)
    </script>
</body>
</html>