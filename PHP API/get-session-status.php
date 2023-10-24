<?php
// Запрос в таком формате
// let body = new FormData;
// body.append('session_id', 3)
// fetch('../api/get-session-status.php', {method: "POST",body: body}).then(r=>r.json())

require("../dbconnection.php");
header('Access-Control-Allow-Origin: *');
if (isset($_POST['session_id'])) {
    try {
        $sessionId = $_POST['session_id'];
        $query = pg_query_params($db, "SELECT * FROM employee_sessions WHERE id=$1", [$sessionId]);
        if (!$query){
            http_response_code(503);
            throw new Exception("couldn't connect to Data Base");
        }else{
            if(pg_num_rows($query)==0){
                http_response_code(403);
                throw new Exception("there are no sessions with this ID");
            }else{
                if(pg_fetch_result($query, 0, 'end_time')==null){
                    $ss = 'opened';
                }else{
                    $ss = 'closed';
                }
            }
        }
        $statusMessage = json_encode(['session status'=>$ss]);
        echo $statusMessage;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'session id not given']);
}
