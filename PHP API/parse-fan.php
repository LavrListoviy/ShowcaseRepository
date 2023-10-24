<?php
require("../dbconnection.php");
header("Access-Control-Allow-Origin:*");
if (isset($_POST['fan_id']) 
&& isset($_POST['model_id']) 
&& isset($_POST['fan_login'])
&& isset($_POST['list_ids'])
&& isset($_POST['spend'])
&& isset($_POST['free_trial'])
&& isset($_POST['offer_duration'])
&& isset($_POST['trial_sent'])
&& isset($_POST['trial_claimed'])) {
    $fan_id = $_POST['fan_id'];
    $model_id = $_POST['model_id'];
    $fan_login = $_POST['fan_login'];
    $list_ids = $_POST['list_ids'];
    $spend = $_POST['spend'];
    $free_trial = $_POST['free_trial'];
    $offer_duration = $_POST['offer_duration'];
    $trial_sent = $_POST['trial_sent'];
    $trial_claimed = $_POST['trial_claimed'];
    if (pg_query($db, "BEGIN")) {
        try {
            $query = pg_query_params($db,"INSERT INTO fans (of_id, of_login)
                VALUES ($1, $2)
                ON CONFLICT (of_id, of_login)
                DO NOTHING
                RETURNING id;");
            if($query){
                if(pg_affected_rows($query)>0){
                    // Новый подписчик, в системе его не было
                    $fan_inner_id = pg_fetch_result($query, "id");
                    
                }else {
                    // Подписчик уже был в системе
                    $query = pg_query_params($db, "SELECT id FROM fans WHERE of_id = $1 AND of_login = $2", [$fan_id, $fan_login]);
                    $fan_inner_id = pg_fetch_result($query, "id");
                }
                if (!pg_num_rows(pg_query_params($db, "SELECT * FROM model_fans WHERE fan_id = $1 AND model_id = $2", [$fan_inner_id, $model_id]))){
                    $query = pg_query_params($db, "INSERT INTO model_fans (model_id, fan_id, list_ids, total_spend, free_trial, offer_duration, trial_sent, trial_claimed)
                    VALUES ($1,$2,$3,$4,$5,$6,$7,$8)", [$model_id, $fan_id, $list_ids, $spend, $free_trial, $offer_duration, $trial_sent, $trial_claimed]);
                    if($query){
                        pg_query($db, "COMMIT");
                    }else {
                        http_response_code(502);
                        throw new Exception('Couldn\'t insert into 2nd tab');
                    }
                }
            }else {
                http_response_code(501);
                throw new Exception('Couldn\'t write down fan');
            }
        } catch (Exception $e) {
            pg_query($db, "ROLLBACK");
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Couldn\'t connect to DB']);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'not fulfilled']);
}
