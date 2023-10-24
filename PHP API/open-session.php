<?php
require('../dbconnection.php');
if (isset($_POST['login']) && isset($_POST['model_id'])&& isset($_POST['session_type'])) {
    $login = $_POST['login'];
    $modelId = $_POST['model_id'];
    $type = $_POST['session_type'];
    try {
        if (pg_query($db, 'BEGIN')) {
            $query = pg_query_params($db, 'SELECT id FROM users WHERE login = $1', [$login]);
            $userId = pg_fetch_result($query, 0, 'id');
            if ($userId) {
                $query = pg_query_params($db, 'INSERT INTO employee_sessions (employee_id, model_id, type, start_time) VALUES ($1,$2,$3, CURRENT_TIMESTAMP) RETURNING id, start_time', [$userId,$modelId,$type]);
                if($query){
                    $sessionId = pg_fetch_result($query, 0, 'id');
                    $startTime = pg_fetch_result($query, 0, 'start_time');
                    $startTime = date('H:i:s Y-m-d', strtotime($startTime));
                    pg_query($db,'COMMIT');
                    echo json_encode(['status'=>'successful', "session_id" => $sessionId, 'session_start_time' => $startTime]);
                }else {
                    throw new Exception('Open session error');
                }
            } else {
                throw new Exception('Unknown id retrieval error');
            }
        }else {
            throw new Exception('Problem with database connection');
        }
    } catch (Exception $e) {
        pg_query($db, 'ROLLBACK');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}else{
    http_response_code(400);
    echo json_encode(['status' => 'not fulfilled']);
}