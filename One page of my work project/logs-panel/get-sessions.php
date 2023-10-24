<?php
require('../db_connection.php');
$data = json_decode(file_get_contents('php://input'), true);
$html = '<li class="select__item">
    <input type="radio" id="session_placeholder" name="rang" value="placeholder" class="select__input">
    <label class="select__label" for="session_placeholder">
        Session date
    </label>
</li>';
if (isset($data['userID'])) {
    $user = $data['userID'];
    if ($user === 'placeholder') {
        echo $html;
    } else {
        try {
            if (pg_query($db, 'BEGIN')) {
                $query = pg_query($db, "SELECT * FROM employee_sessions WHERE employee_id = '" . $user . "'");
                $sessionsArray = pg_fetch_all($query);
                if (empty($sessionsArray)) {
                    throw new Exception("The user has not yet had sessions, or an error occurred.");
                }
                foreach ($sessionsArray as $session) {
                    $sessionId = $session['id'];
                    $sessionStart = $session['start_time'];
                    $sessionStart = strtotime($sessionStart);
                    $sessionStart = strtotime(date('Y-m-d H:i', $sessionStart));
                    $sessionStart = date('H:i d-m-Y', $sessionStart);
                    $style = '';
                    if ($session['end_time'] !== null) {
                        $endTime = $session['end_time'];
                        $endTime = strtotime($endTime);
                        $endTime = strtotime(date('Y-m-d H:i', $endTime));
                        $endTime = date('H:i d-m-Y', $endTime);
                    } else {
                        $endTime = '';
                        $style = 'style="color: limegreen;"';
                    }
                    $html .= '<li class="select__item">
                    <input type="radio" id="' . $sessionId . '" name="rang" value="' . $sessionId . '" class="select__input">
                    <label class="select__label" for="' . $sessionId . '" ' . $style . '>
                        ' . $sessionStart . ' — ' . $endTime . '
                    </label>
                </li>';
                }
                echo ($html);
                pg_query($db, 'COMMIT');
            } else {
                throw new Exception('Failed to start transaction');
            }
        } catch (Exception $e) {
            http_response_code(400);  // bad request
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        };
    }
} else {
    echo $html;
}
