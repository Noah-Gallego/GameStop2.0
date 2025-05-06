<?php
session_start();
require_once '../includes/config.php';
header('Content-Type: application/json');

$uid = $_SESSION['uid'];

if (empty($uid))
{
    http_response_code(401);
    echo json_encode(['error' => 'not-logged-in']);
    exit;
}

$action = fromPostOrFirst('action', ['read', 'create', 'update', 'delete']);

$pdo = get_pdo_connection();

function bindParams($stmt, $params)
{
    foreach ($params as $key => $value)
    {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
}

function validateOneOf(...$pairs)
{
    $allInvalid = true;
    $invalidNames = [];

    foreach ($pairs as [$value, $name]) {
        if ($value)
        {
            $allInvalid = false;
            break;
        }
        else
        {
            $invalidNames[] = $name;
        }
    }

    if ($allInvalid)
    {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing or invalid parameters: ' . implode(', ', $invalidNames)
        ]);
        exit;
    }
}

function fromPostOrFirst($input, array $allowed): string
{
    if(!isset($_POST[$input]))
    {
        return $allowed[0];
    }

    $input = $_POST[$input];
    
    return in_array(strtolower($input), array_map('strtolower', $allowed)) ? $input : $allowed[0];
}

function executeQuery(PDO $pdo, string $query, array $params): PDOStatement
{
    $stmt = $pdo->prepare($query);
    bindParams($stmt, $params);
    $stmt->execute();
    return $stmt;
}