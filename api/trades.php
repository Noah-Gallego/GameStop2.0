<?php
require_once 'api_scaffold.php';
/* @var $action
 * @var $pdo
 * @var $uid */

try
{
    $tid = $_POST['tid'] ?? null;
    $uid_sender = $_POST['uid_sender'] ?? $uid;
    $uid_receiver = $_POST['uid_receiver'] ?? null;
    $gid1 = $_POST['gid1'] ?? null;
    $gid2 = $_POST['gid2'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $trade_state = fromPostOrFirst('trade_state', ['', 'Pending', 'Completed', 'Cancelled']);;
    $sender_status = fromPostOrFirst('status1', ['Accepted', 'Declined']);
    $receiver_status = fromPostOrFirst('status2', ['Pending', 'Accepted', 'Declined']);

    if ($action === 'create')
    {
        if($uid_sender != $uid)
        {
            echo json_encode(['error' => 'Cannot make trade for other user.']);
        }
        else
        {
            validateOneOf([$uid_receiver, '$uid_receiver']);
            validateOneOf([$gid2, '$gid1'], [$gid1, '$gid1']); // either game can be null, but one must be set
            $base_query = "INSERT INTO 
                Trades (UID1, GID1, Status1, UID2, GID2, Status2, State, Timestamp) 
                VALUES (:uid_sender, :gid1, 'Accepted', :uid_receciver, :gid2, 'Pending', 'Pending', NOW())";
            $params[':uid_sender'] = $uid_sender;
            $params[':gid1'] = $gid1;
            $params[':uid_receiver'] = $uid_receiver;
            $params[':gid2'] = $gid2;

            executeQuery($pdo, $base_query, $params);
            echo json_encode(['success' => true]);
        }
    }
    else if ($action === 'update')
    {
        validateOneOf([$tid, '$tid']);
        if($uid_sender == $uid)
        {
            $base_query = "UPDATE Trades SET 
                  State = :state, 
                  Status1 = :sender_status
                  WHERE UID1 = :uid_sender
                  AND TID = :tid";
            $params[':uid_sender'] = $uid_sender;
            $params[':sender_status'] = $sender_status;
            $params[':tid'] = $tid;
            $params[':state'] = $sender_status === 'Declined' ? 'Cancelled' : 'Pending';

            executeQuery($pdo, $base_query, $params);
            echo json_encode(['success' => true]);
        }
        else if($uid_receiver == $uid)
        {
            $base_query = "UPDATE Trades SET 
                  State = :state, 
                  Status2 = :receiver_status
                  WHERE UID1 = :uid_receiver
                  AND TID = :tid";
            $params[':uid_receiver'] = $uid_receiver;
            $params[':receiver_status'] = $receiver_status;
            $params[':tid'] = $tid;
            
            if($receiver_status === 'Declined')
            {
                $params[':state'] = 'Declined';
            }
            else if($receiver_status === 'Pending')
            {
                $params[':state'] = 'Pending';
            }
            else
            {
                $sub_query = "SELECT State FROM Trades WHERE TID = :tid";
                $sub_params[':tid'] = $tid;
                $sub_stmt = executeQuery($pdo, $sub_query, $sub_params);
                $sub_results = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($results) && $results[0]['State'] === 'Pending')
                {
                    $params[':state'] = 'Accepted';
                }
            }

            executeQuery($pdo, $base_query, $params);
            echo json_encode(['success' => true]);
        }
        else
        {
            echo json_encode(['error' => 'Cannot edit trade on other users.']);
        }
    }
    else if ($action === 'delete')
    {
        echo json_encode(['error' => 'Trades cannot be deleted.']);
    }
    else // default: read
    {
        $base_query = "SELECT 
    TID as tid, 
    GID1 as gid1, 
    UID1 as uid1, 
    Status1 AS status1, 
    GID2 as gid2, 
    UID2 as uid2, 
    Status2 AS status2, 
    State AS state, 
    Timestamp AS timestamp FROM Trades WHERE UID1 = :uid_sender";
        $params[':uid_sender'] = $uid_sender;

        if($uid_receiver)
        {
            $base_query .= " AND UID2 = :uid_receiver";
            $params[':uid_receiver'] = $uid_receiver;
        }

        if($gid1 && $gid2)
        {
            $base_query .= " AND ((GID1 = :gid1 AND GID2 = :gid2) or (GID1 = :gid2 AND GID2 = :gid1))";
            $params[':gid1'] = $gid1;
            $params[':gid2'] = $gid2;
        }
        else if($gid1)
        {
            $base_query .= " AND (GID1 = :gid1 OR GID2 = :gid1)";
            $params[':gid1'] = $gid1;
        }
        else if($gid2)
        {
            $base_query .= " AND (GID1 = :gid2 OR GID2 = :gid2)";
            $params[':gid2'] = $gid2;
        }

        if ($start_date)
        {
            $base_query .= " AND Timestamp >= :start_date";
            $params[':start_date'] = $start_date;
        }

        if ($end_date)
        {
            $base_query .= " AND Timestamp <= :end_date";
            $params[':end_date'] = $end_date;
        }

        if($trade_state)
        {
            $base_query .= " AND State = :trade_state";
            $params[':trade_state'] = $trade_state;
        }

        if($sender_status)
        {
            $base_query .= " AND Status1 = :sender_status";
            $params[':sender_status'] = $sender_status;
        }

        if($receiver_status)
        {
            $base_query .= " AND Status2 = :receiver_status";
            $params[':receiver_status'] = $receiver_status;
        }

        if($tid)
        {
            $base_query .= " AND TID = :tid";
            $params[':tid'] = $tid;
        }

        $stmt = executeQuery($pdo, $base_query, $params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
catch (PDOException $ex)
{
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}