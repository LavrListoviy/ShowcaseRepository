<?php
require('../db_connection.php');
error_reporting(E_ERROR | E_PARSE);
if (empty($_SESSION['userInfo'])) {
    header("Location: /");
} else if ($_SESSION['userInfo']['accountType'] !== 'admin' && $_SESSION['userInfo']['accountType'] !== 'SUPER_ADMIN' && $_SESSION['userInfo']['accountType'] !== 'manager') {
    echo '<script>alert("access denied")</script>';
} else {
    $userId = $_SESSION['userInfo']['userId'];
    $query = pg_query_params($db, "SELECT employee_id FROM employee_teams WHERE teamlead_id = $1", array($userId));
    $usersIds = pg_fetch_all_columns($query, 0);
    $usersIds = implode(',', $usersIds);

    if (!empty($usersIds)) {
        $nameQuery = pg_query($db, "SELECT name, id FROM users WHERE id IN (SELECT unnest(ARRAY[$usersIds]))");
    } else {
        // get empty table with required columns
        $nameQuery = pg_query($db, "SELECT name, id FROM users WHERE false");
    }
    $res = pg_fetch_all($nameQuery);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMConnector - Create account</title>
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/css/styles.css">
    <script>
        console.log(JSON.stringify(<?php echo json_encode($_SESSION) ?>))
    </script>
</head>

<body>
    <header class="header">
        <div class="header__container">
            <a href="/" class="header__logo">
                <img src="../img/logo.svg" alt="OMConnector - logo">
            </a>
            <div class="header__menu">
                <ul class="header__list">
                    <li class="header__item">
                        <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ&ab_channel=RickAstley" class="header__link" title="Full guide here">
                            How to start
                        </a>
                    </li>
                    <li class="header__item">
                        <a href="https://t.me/OlnyGlobal" class="header__link" title="Just try it">
                            Contact help
                        </a>
                    </li>
                    <li>
                        <a class="header__logout" href="/user-control/logout.php" title="Logout">Log out</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <aside class="aside__menu">
        <nav class="aside__nav">
            <ul class="aside__list">
                <li class="aside__item">
                    <a class="aside__link" href="/create-user">
                        Add user
                    </a>
                </li>
                <li class="aside__item">
                    <a class="aside__link" href="/users-list">
                        Users list
                    </a>
                </li>
                <li class="aside__item">
                    <a class="aside__link  active" href="/logs-panel">
                        Logs
                    </a>
                </li>
                <li class="aside__item">
                    <a class="aside__link" href="/permissions">
                        Permission
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    <details class="container" id="filters__spoiler" open>
        <summary id="summary__button" for="filters__spoiler">Click here to close/open filters</summary>
        <div class="selects__row">
            <div class="select__container">
                <div class="placeholder__title">
                    Logs type
                </div>
                <div class="select">
                    <h3 class="select__title" id="log_type">
                        Session summary
                    </h3>
                    <ul class="select__dropdown">
                        <li class="select__item">
                            <input checked type="radio" id="summary" name="rang" value="summary" class="select__input">
                            <label class="select__label" for="summary">
                                Session summary
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="session_started" value="session started" name="rang" class="select__input">
                            <label class="select__label" for="session_started">
                                Session started
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="session_closed" name="rang" value="session closed" class="select__input">
                            <label class="select__label" for="session_closed">
                                Session closed
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="subscription" name="rang" value="subscription" class="select__input">
                            <label class="select__label" for="subscription">
                                Subscription
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="paid" value="paid" name="rang" class="select__input">
                            <label class="select__label" for="paid">
                                Paid
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="incoming_message" value="incoming_message" name="rang" class="select__input">
                            <label class="select__label" for="incoming_message">
                                Incoming message
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="outgoing_message" value="outgoing_message" name="rang" class="select__input">
                            <label class="select__label" for="outgoing_message">
                                Outgoing message
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="sales_attempts" value="sales attempt" name="rang" class="select__input">
                            <label class="select__label" for="sales_attempts">
                                Sales attempts
                            </label>
                        </li>
                        <li class="select__item">
                            <input type="radio" id="click" value="click" name="rang" class="select__input">
                            <label class="select__label" for="click">
                                Click
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="select__container">
                <div class="placeholder__title">
                    Sexter name
                </div>
                <div class="select">
                    <h3 class="select__title" id="selector_name">
                        All names
                    </h3>
                    <ul class="select__dropdown" id="name__selector_dropdown">
                        <li class="select__item">
                            <input checked type="radio" id="name_placeholder" name="rang" value="placeholder" class="select__input">
                            <label class="select__label" for="name_placeholder">
                                All names
                            </label>
                        </li>
                        <?php
                        foreach ($res as $value) {
                            echo "<li class=\"select__item\">
                                        <input type=\"radio\" id=\"" . $value['name'] . $value['id'] . "\" name=\"rang\" value=\"" . $value['id'] . "\" class=\"select__input\">
                                        <label class=\"select__label\" for=\"" . $value['name'] . $value['id'] . "\">" . $value['name'] . "</label>
                                        </li>";
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="select__container">
                <div class="placeholder__title">
                    Session select
                </div>
                <div class="select" id="session_select">
                    <h3 class="select__title" id="selector_session">
                        Session date
                    </h3>
                    <ul class="select__dropdown" id="dropdown_sessions">
                        <li class="select__item">
                            <input type="radio" id="session_placeholder" name="rang" value="placeholder" class="select__input">
                            <label class="select__label" for="session_placeholder">
                                Session date
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="range__container">
                <div class="placeholder__title">
                    Max Follow-up Delay
                </div>
                <div class="range__item">
                    <input checked type="range" min="0" max="20" step="1" id="max_rem_interval" name="rang" value="0" class="range__input" title="0 for default 10 min">
                    <label class="range_label" id="slider_value" for="max_rem_interval">
                        0
                    </label>
                </div>
            </div>
        </div>
        <div class="select__container" id="filter__button">
            <button class="form__submit">Apply filters</button>
        </div>
    </details>
    <section class="container">
        <div class="logs__container">
        </div>
    </section>
</body>
<script src="/js/logs.js"></script>
<script defer src="/js/scripts.js"></script>

</html>