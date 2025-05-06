<?php
/* search.php – live “anything” search*/
session_start();                         // ← start session
require_once '../includes/config.php';
header('Content-Type: application/json');

/* ----------------------------------------------------------
   1. Input guard
---------------------------------------------------------- */
$q = trim($_POST['search_data'] ?? '');
if ($q === '') {
    echo json_encode(['users' => [], 'games' => []]);
    exit;
}

$like = "%$q%";
$uid  = $_SESSION['uid'] ?? 0;          // 0 works even if not logged in

/* ----------------------------------------------------------
   2. Database work
---------------------------------------------------------- */
try {
    $pdo = get_pdo_connection();

    /* ---- games (title + image only) -------------------- */
    $gStmt = $pdo->prepare(
        'SELECT GID, Title AS title, Image AS image
         FROM   Games
         WHERE  Title LIKE :like
         LIMIT  50'
    );
    $gStmt->execute([':like' => $like]);
    $games = $gStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---- users (exclude current UID) ------------------- */
    $uStmt = $pdo->prepare(
        'SELECT UID, SUBSTRING_INDEX(Email,"@",1) AS name
         FROM   Users
         WHERE  Email LIKE :like
           AND  UID <> :uid
         LIMIT  50'
    );
    $uStmt->execute([
        ':like' => $like,
        ':uid'  => $uid
    ]);
    $users = $uStmt->fetchAll(PDO::FETCH_ASSOC);

    /* ---------------------------------------------------- */
    echo json_encode(
        ['users' => $users, 'games' => $games],
        JSON_UNESCAPED_UNICODE
    );

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
