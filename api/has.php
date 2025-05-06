<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

function addConditions(&$query, &$params, $gid, $license)
{
    if($gid)
    {
        $query .= " AND GID = :gid";
        $params[':gid'] = $gid;
    }

    if($license)
    {
        $query .= " AND License = :license";
        $params[':license'] = $license;
    }
}

try
{
    $license = $_POST['license'] ?? null;
    $gid = $_POST['gid'] ?? null;
    $params = [':uid' => $uid];

    if ($action === 'create')
    {
        validateOneOf([$gid, '$gid']);
        validateOneOf([$license, '$license']);

        $base_query = "INSERT INTO Has (GID, UID, License) VALUES (:gid, :uid, :license)";
        $params[':license'] = $license;
        $params[':gid'] = $gid;

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if ($action === 'update')
    {
        validateOneOf([$gid, '$gid']);
        validateOneOf([$license, '$license']);
        $base_query = "UPDATE Has SET License = :license 
           WHERE UID = :uid 
             AND GID = :gid";
        $params[':license'] = $license;
        $params[':gid'] = $gid;

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if ($action === 'delete')
    {
        validateOneOf([$gid, '$gid'], [$license, '$license']);
        $base_query = "DELETE FROM Has WHERE UID = :uid";
        addConditions($base_query, $params, $gid, $license);

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else // default: read
    {
        $base_query = "SELECT UID as uid, GID as gid, License as license FROM Has WHERE UID = :uid";
        addConditions($base_query, $params, $gid, $license);

        $stmt = executeQuery($pdo, $base_query, $params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}