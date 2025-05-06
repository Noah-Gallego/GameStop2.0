<?php
// update_wishlist.php – change priority safely
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

/* ---------- auth ---------- */
if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not-logged-in']);
    exit;
}

$uid      = (int) $_SESSION['uid'];
$gid      = $_POST['gid']      ?? '';   // keep original type (string ok)
$priority = $_POST['priority'] ?? '';   // keep as string for validation

/* ---------- validation ---------- */
if ($gid === '' || !in_array($priority, ['1','2','3'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid-input']);
    exit;
}

/* ---------- update ---------- */
$sql = "UPDATE Wants
        SET Priority = :priority
        WHERE UID = :uid AND GID = :gid";


try {
    $pdo = get_pdo_connection();
    $stm = $pdo->prepare($sql);
    $stm->execute([
        ':priority' => (int)$priority,
        ':uid'      => $uid,
        ':gid'      => $gid
    ]);

    $affected = $stm->rowCount();      // how many rows were actually touched?

    if ($affected === 0) {
        // nothing matched – most common cause is GID type mismatch
        echo json_encode(['success' => false, 'error' => 'no-row-updated']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
