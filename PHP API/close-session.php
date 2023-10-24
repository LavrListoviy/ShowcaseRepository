<?php
require("../dbconnection.php");
header('Access-Control-Allow-Origin: *');
if (isset($_POST['session_id'])) {
    try {
        $sessionId = $_POST['session_id'];
        if (pg_query($db, "BEGIN")) {
            $query = pg_query_params($db, "UPDATE employee_sessions SET end_time = CURRENT_TIMESTAMP WHERE id = $1  AND end_time IS NULL RETURNING end_time, start_time", [$sessionId]);
            if (pg_affected_rows($query) > 0) {
                $endTime = pg_fetch_result($query, 0, "end_time");
                $startTime = pg_fetch_result($query, 0, "start_time");
                $startTime = date('H:i:s Y-m-d', strtotime($startTime));
                $endTime = date('H:i:s Y-m-d', strtotime($endTime));
                $sessionLength = strtotime($endTime) - strtotime($startTime);
                $days = floor($sessionLength / (60 * 60 * 24));
                $hours = floor(($sessionLength % (60 * 60 * 24)) / (60 * 60));
                $minutes = floor(($sessionLength % (60 * 60)) / 60);
                $seconds = $sessionLength % 60;
                $diff_string = sprintf("%dd:%02dh:%02dm:%02ds", $days, $hours, $minutes, $seconds);
                $statusMessage = json_encode(['status' => 'success', 'session_start_time' => $startTime, 'session_end_time' => $endTime, 'session_length' => $diff_string]);
                pg_query($db, 'COMMIT');
            } else {
                pg_query($db, 'ROLLBACK');
                http_response_code(401);
                throw new Exception('Смена уже закрыта или id смены неверный');
            }
        } else {
            http_response_code(500);
            throw new Exception("Проблемы с подключением к БД");
        }
        echo $statusMessage;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'not fulfilled']);
}
