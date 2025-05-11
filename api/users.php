<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

try
{
    $params = [':uid' => $uid];
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $uid_other = $_POST['uid_other'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $new_email = $_POST['new_email'] ?? null;

    if ($action === 'create')
    {
        echo json_encode(['error' => 'Creating user not supported here']);
        // auth.php#47
    }
    else if ($action === 'update')
    {
        if($uid_other !== $uid)
        {
            echo json_encode(['error' => 'Cannot edit other user.']);
        }
        else
        {
            if($new_password)
            {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $base_query = "UPDATE Users SET Password = :password WHERE UID = :uid";
                $params[':password'] = $hash;
                $params[':uid'] = $uid;

                executeQuery($pdo, $base_query, $params);
                echo json_encode(['success' => true]);
            }

            if($new_email)
            {
                $base_query = "UPDATE Users SET Email = :email WHERE UID = :uid";
                $params[':email'] = $new_email;
                $params[':uid'] = $uid;

                executeQuery($pdo, $base_query, $params);
                echo json_encode(['success' => true]);
            }
        }
    }
    else if ($action === 'delete')
    {
        echo json_encode(['error' => 'Deleting user not supported']);
    }
    else // default: read
    {
        $base_query = "SELECT UID AS uid,
        Email AS email
        FROM Users";

        $params = [];

        if($uid_other)
        {
            $base_query .= " WHERE UID = :uid";
            $params[':uid'] = $uid_other;
        }
        else
        {
            if($email && $username)
            {
                $base_query .= " WHERE Email LIKE %:username%@:email";
                $params[':email'] = "$email";
                $params[':username'] = "$email";
            }
            else if($email)
            {
                $base_query .= " WHERE Email LIKE :email";
                $params[':email'] = "%@$email%";
            }
            else if($username)
            {
                $base_query .= " WHERE Email LIKE :username";
                $params[':username'] = "%$username%";
            }
        }

        $stmt = executeQuery($pdo, $base_query, $params);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($games);
    }
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}