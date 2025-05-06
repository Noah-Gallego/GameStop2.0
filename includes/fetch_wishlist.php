<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// Debug: check session
if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not-logged-in']);
    exit;
}

$uid      = (int)$_SESSION['uid'];
$search   = trim($_POST['search_data'] ?? '');
$priority = $_POST['priority'] ?? null;

// Build SQL
$sql = <<<SQL
SELECT G.GID, G.Title, G.Image, W.Priority, W.Date
FROM Wants W
JOIN Games G ON W.GID = G.GID
WHERE W.UID = :uid
SQL;

$params = [':uid' => $uid];

if (!empty($search)) {
    $sql .= " AND G.Title LIKE :like";
    $params[':like'] = "%$search%";
}

if (!empty($priority) && is_numeric($priority)) {
    $sql .= " AND W.Priority = :priority";
    $params[':priority'] = $priority;
}

try {
    $pdo = get_pdo_connection();
    $stm = $pdo->prepare($sql);
    $stm->execute($params);
    $results = $stm->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}