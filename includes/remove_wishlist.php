<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not-logged-in']);
    exit;
}

$uid = (int)$_SESSION['uid'];
$gid = (int)($_POST['gid']);

if ($gid === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid GID']);
    exit;
}

try {
    $pdo = get_pdo_connection();

    $stmt = $pdo->prepare('CALL DeleteWant(?, ?)');
    $stmt->execute([$uid, $gid]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}