<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

try
{
    $sort = fromPostOrFirst('sort', ['ASC', 'DESC']);
    $title = $_POST['title'] ?? null;
    $gid = $_POST['gid'] ?? null;

    $base_query = "SELECT 
    Games.GID AS gid,
    Games.Title AS title,
    Games.Image AS image,
    UserWants.Priority AS priority,
    COUNT(DISTINCT AllWants.UID) AS want_count,
    COUNT(DISTINCT Has.License) AS has_count
FROM Games
LEFT JOIN Wants AS UserWants 
    ON Games.GID = UserWants.GID AND UserWants.UID = :uid
LEFT JOIN Wants AS AllWants 
    ON Games.GID = AllWants.GID
LEFT JOIN Has 
    ON Games.GID = Has.GID";

    $params[':uid'] = $uid;

    if($gid)
    {
        $base_query .= " 
        WHERE Games.GID = :gid";
        $params[':gid'] = $gid;
    }
    else if($title)
    {
        $base_query .= " 
        WHERE Games.Title LIKE :title";
        $params[':title'] = "%$title%";
    }

    $base_query .= " 
    GROUP BY Games.GID, Games.Title, Games.Image, UserWants.Priority
    ORDER BY Title $sort
    LIMIT 50";
    $stmt = executeQuery($pdo, $base_query, $params);
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($games);
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}