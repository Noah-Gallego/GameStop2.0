<?php
require_once '../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
}

$uid = (int) ($_POST['uid'] ?? 0);

if ($uid <= 0) {
    echo json_encode(['error' => 'Invalid User ID']);
    exit;
}

try {
    $db = get_pdo_connection();
    $stmt = $db->prepare("UPDATE Users SET IsBanned = 1 WHERE UID = ?");
    $stmt->execute([$uid]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error']);
}
?>