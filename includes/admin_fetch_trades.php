<?php
/* admin_fetch_trades.php – JSON admin trade list 
 * Output fields: Sender | Receiver | SenderGame | ReceiverGame | License1 | License2 | CompletedAt 
 * ---------------------------------------------------------------- */
require_once '../includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

/* ---------- Incoming Filters ---------- */
$searchTxt = trim($_POST['search_data'] ?? '');  // free text search
$rawStatus = $_POST['status'] ?? [];             // checkbox group for states
$statusArr = (array)$rawStatus;                  // force to array

/* Validate allowed states */
$allowedStates = ['Pending', 'Completed', 'Cancelled', 'Failed'];
$statusArr = array_values(
    array_intersect($allowedStates, 
        array_map('ucfirst', array_map('strtolower', $statusArr)))
);

$useFilters = ($searchTxt !== '') || !empty($statusArr);

try {
    $db = get_pdo_connection();

    $sql = "SELECT * FROM AdminTradeDetails";
    $params = [];

    if ($useFilters) {
        $filters = [];

        /* Filter by trade state */
        if (!empty($statusArr)) {
            $placeholders = implode(',', array_fill(0, count($statusArr), '?'));
            $filters[] = "State IN ($placeholders)";
            $params = array_merge($params, $statusArr);
        }

        /* Free text search on usernames and game titles */
        if ($searchTxt !== '') {
            $filters[] = "(Sender LIKE ? OR Receiver LIKE ? OR SenderGame LIKE ? OR ReceiverGame LIKE ?)";
            $like = "%$searchTxt%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $sql .= " WHERE " . implode(" AND ", $filters);
    }

    $sql .= " ORDER BY CompletedAt DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>