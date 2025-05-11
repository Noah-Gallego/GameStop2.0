<?php
/* returns [{Title,Image,copies}, …] for the logged-in user */
session_start();                             // ① always start session first
require_once __DIR__.'/config.php';          // ← adjust if cfg lives elsewhere
header('Content-Type: application/json');

if (empty($_SESSION['uid'])) {               // ② uid guard
    http_response_code(401);
    echo json_encode(['error' => 'not-logged-in']);
    exit;
}

$uid    = (int)$_SESSION['uid'];
$search = trim($_POST['search_data'] ?? '');

$sql = <<<SQL
SELECT g.Title, g.Image, g.GID as gid, COUNT(h.License) AS copies
FROM   Games g
JOIN   Has   h ON h.GID = g.GID
WHERE  h.UID = :uid
  AND  (:search = '' OR g.Title LIKE :like)
GROUP  BY g.GID, g.Title, g.Image
ORDER  BY g.Title
SQL;

try {
    $pdo = get_pdo_connection();
    $stm = $pdo->prepare($sql);
    $stm->execute([
        ':uid'    => $uid,
        ':search' => $search,
        ':like'   => "%$search%"
    ]);
    echo json_encode($stm->fetchAll(PDO::FETCH_ASSOC));   // 200 by default
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
