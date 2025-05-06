<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

function validatePriority($priority)
{
    if ($priority < 1 || $priority > 3)
    {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid Priority']);
        exit;
    }
    return $priority;
}

function addConditions(&$query, &$params, $gid, $start_date, $end_date)
{
    if ($gid)
    {
        $query .= " AND GID = :gid";
        $params[':gid'] = $gid;
    }
    if ($start_date)
    {
        $query .= " AND Date >= :start_date";
        $params[':start_date'] = $start_date;
    }
    if ($end_date)
    {
        $query .= " AND Date <= :end_date";
        $params[':end_date'] = $end_date;
    }
}

try
{
    $gid = $_POST['gid'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $priority = isset($_POST['priority']) ? validatePriority((int)$_POST['priority']) : 1;
    $params = [':uid' => $uid];

    if ($action === 'create')
    {
        validateOneOf([$gid, '$gid']);
        $base_query = "INSERT INTO Wants (GID, UID, Date, Priority) VALUES (:gid, :uid, NOW(), :priority)";
        $params[':priority'] = $priority;
        $params[':gid'] = $gid;

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if ($action === 'update')
    {
        validateOneOf([$gid, '$gid'], [$start_date, '$start_date'], [$end_date, '$end_date']);
        $base_query = "UPDATE Wants SET Priority = :priority, Date = NOW() 
             WHERE UID = :uid";
        $params[':priority'] = $priority;
        addConditions($base_query, $params, $gid, $start_date, $end_date);

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if ($action === 'delete')
    {
        validateOneOf([$gid, '$gid'], [$start_date, '$start_date'], [$end_date, '$end_date']);
        $base_query = "DELETE FROM Wants WHERE UID = :uid";
        addConditions($base_query, $params, $gid, $start_date, $end_date);

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else // default: read
    {
        $base_query = "SELECT UID as uid, GID as gid, Date as date, Priority as priotiry FROM Wants WHERE UID = :uid";
        addConditions($base_query, $params, $gid, $start_date, $end_date);

        $stmt = executeQuery($pdo, $base_query, $params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}