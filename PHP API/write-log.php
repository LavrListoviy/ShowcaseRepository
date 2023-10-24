<?php
header('Access-Control-Allow-Origin: *');
require('../dbconnection.php');
if (isset($_POST['employee_id']) && isset($_POST['model_id']) && isset($_POST['fan_id']) && isset($_POST['type'])) {
    $employeeId = $_POST['employee_id'];
    $modelId = $_POST['model_id'];
    $fanId = $_POST['fan_id'];
    $logType = $_POST['type'];
    if ($logType === 'subscription'){
        $of_id = $fanId;
        $of_login = $_POST['of_login'];
    }
    if (isset($_POST['sub_msg_link'])) {
        $link = $_POST['sub_msg_link'];
    } else {
        $link = Null;
    }
    if (isset($_POST['spent'])) {
        $money = $_POST['spent'];
    } else {
        $money = Null;
    }
    if (isset($_POST['text'])) {
        $text = $_POST['text'];
    } else {
        $text = Null;
    }
    if (pg_query($db, "BEGIN")) {
        try {
            if ($logType === "subscription") {
                $query = pg_query_params($db, "INSERT INTO fans (of_id, of_login) VALUES ($1, $2)",[$of_id,$of_login]);
                if(!$query){
                    throw new Exception('Error when writing a new user to the fans table');
                }
            }
            $query = pg_query_params(
                $db,
                'INSERT INTO employee_logs (employee_id, model_id, fan_id, type, sub_msg_link, spent, creation_time, text) 
            VALUES ($1,$2,$3,$4,$5,$6, CURRENT_TIMESTAMP, $7)',
                [$employeeId, $modelId, $fanId, $logType, $link, $money, $text]
            );
            if ($query) {
                pg_query($db, "COMMIT");
                echo json_encode(['status' => 'successful']);
            } else {
                http_response_code(403);
                throw new Exception('No record was made, check the data. Last error: '. pg_last_error($db));
            }
        } catch (Exception $e) {
            pg_query($db, 'ROLLBACK');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(502);
        echo json_encode(['status' => 'error', 'message' => 'Problem with database connection']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'not fulfilled', 'data sent' => $_POST]);
}
