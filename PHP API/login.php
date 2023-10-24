<?php
header('Access-Control-Allow-Origin: *');
require('../dbconnection.php');
if (isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $query = pg_query_params($db, "SELECT * FROM users WHERE login = $1", [$login]);
    if ($query) {
        $hashResult = pg_unescape_bytea(pg_fetch_result($query, 0, 'password_hash'));
        $accountType = pg_fetch_result($query, 0, "type");
        if ($accountType === "employee") {
            $userID = pg_fetch_result($query, 0, "id");
            $query = pg_query_params($db, 'SELECT rank FROM employees WHERE id = $1', [$userID]);
            $rank = pg_fetch_result($query, 0, "rank");
            if ($rank === 'sexter') {
                if (password_verify($password, $hashResult)) {
                    $statusMessage = 'success';
                } else {
                    http_response_code(401);
                    $statusMessage = 'wrong password';
                }
            } else {
                http_response_code(403);
                $statusMessage = 'access denied';
            }
        }
    } else {
        http_response_code(404);
        $statusMessage = 'wrong login';
    }
    echo json_encode(['status' => $statusMessage, 'employee_id' => $userID]);
}else{
    http_response_code(400);
    echo json_encode(['status' => 'not fulfilled']);
}
