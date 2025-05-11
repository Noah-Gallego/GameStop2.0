<?php
/* ------------------------------------------------------------------
 *  fetch_trades.php — return “initiated” and “received” trade lists
 * ------------------------------------------------------------------ */

require_once '../includes/config.php';
header('Content-Type: application/json');

/* ---------- 1) Auth ---------- */
if (!isset($_SESSION['uid'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}
$uid = (int) $_SESSION['uid'];

/* ---------- 2) Filters ---------- */
$searchTxt = trim($_POST['search_data'] ?? '');
$statusArr = array_values(
    array_intersect(
        ['Pending','Completed','Cancelled'],                     // « Failed » removed
        array_map('ucfirst', array_map('strtolower', (array)($_POST['status'] ?? [])))
    )
);
$applyStatus = !empty($statusArr);
$applySearch = ($searchTxt !== '');

/* helper to stitch extra WHERE + params */
function addFilters(string &$sql, array &$p, array $statusArr, string $searchTxt): void
{
    if ($statusArr) {
        $sql .= " AND t.State IN (" . implode(',', array_fill(0,count($statusArr),'?')) . ")";
        $p   = array_merge($p, $statusArr);
    }
    if ($searchTxt !== '') {
        $sql .= '
            AND (
                g1.Title LIKE ?  OR g2.Title LIKE ?  OR
                SUBSTRING_INDEX(u1.Email,"@",1) LIKE ? OR
                SUBSTRING_INDEX(u2.Email,"@",1) LIKE ?
            )';
        $like = "%$searchTxt%";
        $p   = array_merge($p, [$like,$like,$like,$like]);
    }
}

try {
    $pdo = get_pdo_connection();

    /* ---------- 3) Initiated trades (you are UID1) ---------- */
    $sqlInit = '
        SELECT
            t.TID,
            g1.Title                         AS SentGame,          -- you sent
            SUBSTRING_INDEX(u2.Email,"@",1)  AS TradePartner,
            g2.Title                         AS ReceivedGame,      -- you received
            t.State                          AS Status
        FROM Trades t
        JOIN Users u1 ON t.UID1 = u1.UID
        JOIN Users u2 ON t.UID2 = u2.UID
        LEFT JOIN Games g1 ON t.GID1 = g1.GID
        LEFT JOIN Games g2 ON t.GID2 = g2.GID
        WHERE t.UID1 = ?
    ';
    $pInit = [$uid];
    addFilters($sqlInit, $pInit, $statusArr, $searchTxt);
    $stmt = $pdo->prepare($sqlInit);
    $stmt->execute($pInit);
    $initiated = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- 4) Received trades (you are UID2) ---------- */
    $sqlRecv = '
        SELECT
            t.TID,
            g2.Title                         AS SentGame,          -- you sent
            SUBSTRING_INDEX(u1.Email,"@",1)  AS TradePartner,
            g1.Title                         AS ReceivedGame,      -- you received
            t.State                          AS Status
        FROM Trades t
        JOIN Users u1 ON t.UID1 = u1.UID
        JOIN Users u2 ON t.UID2 = u2.UID
        LEFT JOIN Games g1 ON t.GID1 = g1.GID
        LEFT JOIN Games g2 ON t.GID2 = g2.GID
        WHERE t.UID2 = ?
    ';
    $pRecv = [$uid];
    addFilters($sqlRecv, $pRecv, $statusArr, $searchTxt);
    $stmt = $pdo->prepare($sqlRecv);
    $stmt->execute($pRecv);
    $received = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------- 5) Ship JSON ---------- */
    echo json_encode([
        'initiated' => $initiated,
        'received'  => $received
    ]);

} catch (PDOException $ex) {
    error_log('TRADES-DBG: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}