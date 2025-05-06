<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

try
{
    $gid = $_POST['gid'] ?? null;
    $title = $_POST['title'] ?? null;
    $image = $_POST['image'] ?? null;
    $limit = $_POST['limit'] ?? 50;
    $page = $_POST['page'] ?? 1;
    $sort = fromPostOrFirst('sort', ['ASC', 'DESC']);

    if ($action === 'create')
    {
        validateOneOf([$gid, '$gid']);
        validateOneOf([$title, '$title']);
        $base_query = "INSERT INTO Games (GID, Title, Image) VALUES (:gid, :title, :image)";
        $params = [':gid' =>  $gid];
        $params[':title'] = $title;
        $params[':image'] = $image;

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if($action === 'update')
    {
        validateOneOf([$gid, '$gid']);
        validateOneOf([$title, '$title'], [$image, '$image']);
        $params = [':gid' => $gid];
        $base_query = "UPDATE Games SET ";

        if($title)
        {
            $base_query .= " Title = :title";
            $params[':title'] = $title;
        }

        if($title && $image)
        {
            $base_query .= ", ";
        }

        if($image)
        {
            $base_query .= " Image = :image";
            $params[':image'] = $image;
        }

        $base_query .= " WHERE GID = :gid";

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else if($action === 'delete')
    {
        validateOneOf([$gid, 'gid']);
        $params = [':gid' => $gid];
        $base_query = "DELETE FROM Games WHERE GID = :gid"; // A trigger needs to coalesce this delete to has and wants entries

        executeQuery($pdo, $base_query, $params);
        echo json_encode(['success' => true]);
    }
    else
    {
        $base_query = "SELECT GID AS gid,
        Title AS title,
        Image AS image
        FROM Games";

        $params = [];

        if($gid)
        {
            $base_query .= " WHERE GID = :gid";
            $params[':gid'] = $gid;
        }
        else if($title)
        {
            $base_query .= " WHERE Title LIKE :title";
            $params[':title'] = "%$title%";
        }

        $base_query .= " 
        ORDER BY Title $sort
        Limit 50";

        $stmt = executeQuery($pdo, $base_query, $params);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($games);
    }
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
