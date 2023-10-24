<?php
require("../dbconnection.php");
require("../functions.php");
error_reporting(E_ERROR | E_PARSE);
if (isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $name = ucfirst($_POST['name']) . " " . ucfirst($_POST['last_name']);
    $tg = $_POST['telegram'];
    $rang = $_POST['rang'];
    if ($rang == 'model') {
        $type = 'model';
    } else {
        $type = 'employee';
    }
    $hashedPsw = password_hash($password, PASSWORD_DEFAULT);
    $query = pg_query_params($db, "SELECT * FROM users WHERE login = $1 OR telegram_id = $2", array($login, $tg));
    $res = pg_fetch_all($query);

    if ($res[0]) {
        $errorMessage = 'Such Login or Telegram ID has already been registered.';
        http_response_code(409);
        echo json_encode(['error' => $errorMessage]);
    } else {
        if ($type === 'employee') {
            pg_query($db, "BEGIN");

            try {
                $userID = writeUser($db, $login, $hashedPsw, $name, $tg, $type);
                $query = pg_query_params($db, "INSERT INTO employees (id, rank) VALUES ($1, $2) RETURNING rank", array($userID, $rang));
                if ($query) {
                    $rank = pg_fetch_result($query, 0, 'rank');
                    if ($rank === 'sexter') {
                        pg_query($db, 'INSERT INTO employee_computers (employee_id) VALUES ( '.$userID.')');
                    }
                } else {
                    throw new Exception("Ошибка при вставке данных сотрудника");
                }

                pg_query($db, "COMMIT");
                echo json_encode(['status' => 'successful']);
            } catch (Exception $e) {
                pg_query($db, "ROLLBACK");
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }
} else {
    echo json_encode(['status' => 'not fulfilled']);
}
