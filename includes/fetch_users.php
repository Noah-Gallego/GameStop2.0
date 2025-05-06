<?php
/*==============================================================
 * includes/fetch_users.php
 * -------------------------------------------------------------
 * Return matching users excluding the current session UID.
 * Supports ASC/DESC ordering and four filter modes.
 *=============================================================*/

session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

/* --- auth guard ------------------------------------------- */
if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not logged in']);
    exit;
}
$uid = (int)$_SESSION['uid'];

/* --- POST parameters -------------------------------------- */
$uname  = trim($_POST['username'] ?? '');      // before @
$domain = trim($_POST['domain']   ?? '');      // after  @
$order  = strtoupper($_POST['order'] ?? 'ASC');
$order  = $order === 'DESC' ? 'DESC' : 'ASC';  // whitelist

/* --- dynamic WHERE clauses -------------------------------- */
$where = ['UID <> :uid'];      // always exclude self
$args  = [':uid' => $uid];

/* filters */
if ($uname !== '' && $domain === '') {
    $where[]           = 'Email LIKE :uname_like';
    $args[':uname_like'] = $uname . '%@%';
} elseif ($uname === '' && $domain !== '') {
    $where[]             = 'Email LIKE :domain_like';
    $args[':domain_like'] = '%@' . $domain . '%';
} elseif ($uname !== '' && $domain !== '') {
    $where[]        = 'Email = :exact';
    $args[':exact'] = $uname . '@' . $domain;
}
/* (when both blank, no extra filter – see Case 1 spec) */

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

/* --- final SQL -------------------------------------------- */
$sql = "SELECT UID, Email AS email
        FROM   Users
        {$whereSql}
        ORDER  BY Email {$order}";

/* --- execute ---------------------------------------------- */
try {
    $pdo  = get_pdo_connection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
