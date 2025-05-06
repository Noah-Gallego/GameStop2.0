<?php
/* fetch_trades.php – JSON trade list
 * Output fields: SentGame | TradePartner | ReceivedGame | Status
 * ---------------------------------------------------------------- */
require_once '../includes/config.php';
header('Content-Type: application/json');

// session_start();
if (!isset($_SESSION['uid'])) {
    error_log('TRADES-DBG: uid not set in session');
}
if (!isset($_SESSION['uid'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated']);
    exit;
}

$uid      = (int)$_SESSION['uid'];   // authoritative
$username = $_SESSION['username'];   // local-part (unused but kept)

/* ---------- incoming filters ---------- */
$searchTxt   = trim($_POST['search_data'] ?? '');      // free text
$rawStatus   = $_POST['status'] ?? [];                // checkbox group
$statusArr   = (array)$rawStatus;                     // force array

/* keep only valid states, case-insensitive */
$allowed     = ['Pending','Completed','Cancelled','Failed'];
$statusArr   = array_values(
                 array_intersect($allowed,
                     array_map('ucfirst', array_map('strtolower',$statusArr)))
               );

/* decide if any filter is in effect */
$useFilters  = ($searchTxt !== '') || !empty($statusArr);

try {
    $db = get_pdo_connection();

    /* ───────────────────────── 1) no filters → stored proc ─────────────────── */
    if (!$useFilters) {
        $stmt = $db->prepare('CALL GetUserTradeHistory(?)');
        $stmt->bindValue(1, $uid, PDO::PARAM_INT);
        $stmt->execute();
    }
    else {
        /* ───────────────────────── 2) filtered SELECT (single query) ───────────── */
        /* base query */
        $sql = '
        SELECT
            g1.Title AS SentGame,
            CASE
                WHEN t.UID1 = ? THEN SUBSTRING_INDEX(u2.Email,"@",1)
                ELSE                  SUBSTRING_INDEX(u1.Email,"@",1)
            END           AS TradePartner,
            g2.Title       AS ReceivedGame,
            t.State        AS Status
        FROM Trades t
        JOIN Users u1 ON t.UID1 = u1.UID
        JOIN Users u2 ON t.UID2 = u2.UID
        JOIN Games g1 ON t.GID1 = g1.GID
        JOIN Games g2 ON t.GID2 = g2.GID
        WHERE (t.UID1 = ? OR t.UID2 = ?)';

        $params = [$uid, $uid, $uid];   // three ? so far

        /* status IN (...) */
        if ($statusArr) {
            $place = implode(',', array_fill(0, count($statusArr), '?'));
            $sql  .= " AND t.State IN ($place)";
            $params = array_merge($params, $statusArr);
        }

        /* search text on titles + partner name */
        if ($searchTxt !== '') {
            $sql .= '
             AND (
                 g1.Title LIKE ? OR g2.Title LIKE ? OR
                 SUBSTRING_INDEX(u1.Email,"@",1) LIKE ? OR
                 SUBSTRING_INDEX(u2.Email,"@",1) LIKE ?
             )';
            $like   = "%$searchTxt%";
            $params = array_merge($params, [$like,$like,$like,$like]);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
    }

    /* send rows (IDs are never exposed) */
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>