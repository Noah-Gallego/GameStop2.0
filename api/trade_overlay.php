<?php
/* api/trade_overlay.php – return both inventories */
session_start();
require_once '../includes/config.php';
header('Content-Type: application/json');

try {
    $uidSelf = intval($_SESSION['uid'] ?? 0);
    $uidThem = intval($_POST['uid_them'] ?? 0);
    if (!$uidSelf || !$uidThem) throw new Exception('Missing UID(s)');

    $pdo = get_pdo_connection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   // <- debug-friendly

    $stmt = $pdo->prepare(
        'SELECT GID, Title, Image
         FROM   Has       /* user ↔ owns */
         NATURAL JOIN Games
         WHERE  UID = :uid'
    );

    $stmt->execute([':uid' => $uidSelf]);
    $yourGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt->execute([':uid' => $uidThem]);
    $theirGames = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['yours' => $yourGames, 'theirs' => $theirGames]);

} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}
