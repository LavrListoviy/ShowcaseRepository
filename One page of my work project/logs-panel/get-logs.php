<?php
require('../dbconnection.php');
$html = '';

//Заполнение шапки
if ($_POST['user_id'] === 'placeholder') {
    $html = '<div class="logs__header">
    <span>
        Choose an employee\'s name
    </span>
    |
    <span>
        ID: not found
    </span>
    </div>';
    echo $html;
} else if ($_POST['session_id'] === 'placeholder') {
    $userID = $_POST['user_id'];
    $query = pg_query_params($db, 'SELECT name FROM users WHERE id = $1', [$userID]);
    $name = pg_fetch_result($query, 0, 'name');
    $html = '<div class="logs__header">
    <span>
        ' . $name . '
    </span>
    |
    <span>
        ID: ' . $userID . '
    </span>
    </div>';
    $html .= '<div class="logs__row">
    Chose a session of an employee. It wouldn\'t work without it.
    </div>';
    echo $html;
} else {

    if (!pg_query($db, 'BEGIN')) {
        http_response_code(400);  // bad request
        echo json_encode(['status' => 'error', 'message' => 'Database connection error']);
    }

    try {
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // Header
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $userID = $_POST['user_id'];
        $query = pg_query_params($db, 'SELECT name FROM users WHERE id = $1', [$userID]);
        $name = pg_fetch_result($query, 0, 'name');
        $delay = $_POST['delay'];
        $html = '<div class="logs__header">
                <span>
                    ' . $name . '
                </span>
                |
                <span>
                    ID: ' . $userID . '
                </span>
                </div>';
        $sessionID = $_POST['session_id'];
        $html .= '<div class="logs__row">
                <span>
                    Session ID: ' . $sessionID . '
                </span>
                </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // Session start time, end time, duration
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $sessionInfoQuery = pg_query_params($db, 'SELECT * FROM employee_sessions WHERE id = $1', [$sessionID]);
        $sessionStart = pg_fetch_result($sessionInfoQuery, 0, 'start_time');
        $sessionEnd = pg_fetch_result($sessionInfoQuery, 0, 'end_time');
        $modelID = pg_fetch_result($sessionInfoQuery, 0, 'model_id');

        if ($sessionEnd !== null) {
            // Session already finished
            $sessionLength = strtotime($sessionEnd) - strtotime($sessionStart);
            $days = floor($sessionLength / (60 * 60 * 24));
            $hours = floor(($sessionLength % (60 * 60 * 24)) / (60 * 60));
            $minutes = floor(($sessionLength % (60 * 60)) / 60);
            $seconds = $sessionLength % 60;
            $diff_string = sprintf("%dd:%02dh:%02dm:%02ds", $days, $hours, $minutes, $seconds);

            $sessionStart = date('H:i:s d-m-Y', strtotime($sessionStart));
            $sessionEnd = date('H:i:s d-m-Y', strtotime($sessionEnd));
            $html .= '<div class="logs__row">
                    Session start: ' . $sessionStart . '
                    |
                    Session end:' . $sessionEnd . '
                    |
                    Session duration: ' . $diff_string . '
                    </div>';
            $sessionStart = strtotime($sessionStart);
            $sessionEnd = strtotime($sessionEnd);
        } else {
            // Session still going
            $sessionStart = date('H:i:s d-m-Y', strtotime($sessionStart));
            $html .= '<div class="logs__row">
                Session start: ' . $sessionStart . '
                |
                Session is still going on
                </div>';
            $sessionStart = strtotime($sessionStart);
            $sessionEnd = time();
        }
        // -------------------------------------------------------------------------------------------------

        // Required fields:
        //      1) Sales attempts/ successful attempts;
        //      2) Average attempts on fan;
        //      3) Words/Messages is session;
        //      4) New payers of old/new fans;
        //      5) New fans;                    todo: разобраться с проблемой с таймзонами sql
        //      6) Unanswered subscriptions;
        //      7) Didn't write after greeting;
        //      8) Unread messages;
        //      9) Logs;
        //
        // All data got from table "employee_logs" (hereinafter referred to as Table).
        // Each session consist of pair
        //      employee_id (id of sexter),
        //      model_id (Onlyfans model id from the account that the employee communicates with fan)
        //      and session timings (start, end or present time).
        //
        // Required fields explanation:
        //  1) Pair of numbers (integer / integer).
        //      Count of records in Table with type "sales attempts", count of records in Table with type "paid";
        //  2) Number (float value) >= 1.
        //      Shows the average number of attempts per subscriber.
        //      Only subscribers to whom employee tried to sell something are taken into account.
        //      For example:
        //          1) Sexter (employee) chats with 100 subscribers;
        //          2) He/She make sales attempts for 5 subscribers;
        //          3) Attempts distribution: 10 attempts, 1 attempt, 1 attempt, 1 attempt, 1 attempt;
        //          4) For this case target metric value is (10 + 1 + 1 + 1 + 1 + 1) / 5;
        //  3) Pair of numbers (integer / integer).
        //      Count of words in all sent messages by employee, count of sent messages by employee.
        //      Sent messages is all records in Table with type "outgoing message" and "sales attempt"
        //  4) Pair of numbers (integer / integer).
        //      Count of fans that was subscribed before session and made purchase during selected session,
        //      count of fans that subscribed and made purchase during selected session.
        //  5) Number (integer).
        //      Count of subscriptions during selected session.
        //  6) List of numbers (integer values)
        //      Time between subscription of new fan and first outgoing message or sales attempt from employee.
        //      Only subscription during session counts.
        //  7) List of formatted-strings with pairs (integer, string).
        //      Getting this field requires delay-parameter.
        //      This parameter means how long can employee ignore his client (fan).
        //      If employee start chatting later than the value of delay-parameter
        //          need to show first late message sent by employee.
        //      This instructions must be applied for every fan serviced during selected session.
        //  8) List of string.
        //      All messages than was received on selected session timings and
        //          was not read by employee during this session.
        //      Message don't be shown if received less 10 minutes before session end.
        //      Summary: message will be shown if
        //              a) no response got by fan;
        //              b) sexter had more than 10 minutes for answering;
        //  9) List of string.
        //      All logs corresponding selected session.
        //      If link represent in sub_msg_link field for record in Table, it must add as clickable-link.


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 1) Sales attempts/ successful attempts
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $salesAttemptsQuery = pg_query_params(
            $db,
            "SELECT COUNT(*) 
                AS sales_attempt_count 
                FROM public.employee_logs 
                WHERE type = 'sales attempt' 
                  AND employee_id = ($1)
                  AND model_id = ($2)
                  AND creation_time >= to_timestamp($3) 
                  AND creation_time <= to_timestamp($4)",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );
        $successfulAttemptsQuery = pg_query_params(
            $db,
            "SELECT COUNT(*) 
                AS successful_attempt_count 
                FROM public.employee_logs 
                WHERE type = 'paid' 
                  AND employee_id = ($1)
                  AND model_id = ($2)
                  AND creation_time >= to_timestamp($3) 
                  AND creation_time <= to_timestamp($4)",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        $salesAttemptsCount = pg_fetch_result($salesAttemptsQuery, 0, 'sales_attempt_count');
        $successfulAttemptsCount = pg_fetch_result($successfulAttemptsQuery,0, 'successful_attempt_count');


        $html .= '<div class="logs__row">
            Sales attempts/Successful attempts: ' . $salesAttemptsCount . '/' . $successfulAttemptsCount . '
            </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 2) Average attempts on fan
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $countFansWithAttempts = pg_query_params(
            $db,
            "SELECT COUNT(*) as fans_with_attempts
                    FROM (
                    SELECT DISTINCT fan_id
                    FROM public.employee_logs as el
                    WHERE el.type = 'sales attempt'
                      AND el.employee_id = ($1)
                      AND el.model_id = ($2)
                      AND el.creation_time >= to_timestamp($3)
                      AND el.creation_time <= to_timestamp($4)) AS unique_fans_id",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        try {
            // All sales attempts divided by the number of subscribers they tried to sell to.
            $avgAttempts = $salesAttemptsCount /
                pg_fetch_result($countFansWithAttempts, 0, 'fans_with_attempts');
        } catch (DivisionByZeroError) {
            $avgAttempts = 0;
        }

        $html .= '<div class="logs__row">
            Average attempts on fan: ' . floatval($avgAttempts) . '
            </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 3) Words/Messages is session
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $wordsStatistics = pg_query_params(
            $db,
            "SELECT *  
                FROM public.employee_logs 
                WHERE (type = 'outgoing message' OR type = 'sales attempt')
                  AND employee_id = ($1)
                  AND model_id = ($2)
                  AND creation_time >= to_timestamp($3) 
                  AND creation_time <= to_timestamp($4)",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        $messagesCount = pg_num_rows($wordsStatistics);

        $wordsCounter = 0;

        // Check all sentences in got table.
        // Use php std function str_word_count: https://www.php.net/manual/en/function.str-word-count.php
        $delimiters = " ,.:;\"'!?";
        while ($row = pg_fetch_assoc($wordsStatistics)) {
            $sentence = $row['text'];
            $wordsCounter += str_word_count($sentence, 0);
        }

        $html .= '<div class="logs__row">
            Words/Messages is session: ' . $wordsCounter . '/' . $messagesCount . '
            </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 4) New payers of old/new fans
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // TODO: что происходит когда подписка закончилась/не продлевается? -
        //      желательно наверное удалить запись subscription для этого пользователя,
        //      но оставить историю переписки

        // Query for payers new fans (Assert time in employee_logs.creation_time in GMT)
        $payersNewFans = pg_query_params(
            $db,
            "SELECT COUNT(DISTINCT fan_id) AS new_fan_count
                FROM (SELECT *
                      FROM employee_logs AS el
                      WHERE el.type = 'paid'
                        AND el.employee_id = ($1)
                        AND el.model_id = ($2)
                        AND el.creation_time >= to_timestamp($3)
                        AND el.creation_time <= to_timestamp($4)
                        AND el.fan_id IN (SELECT DISTINCT el2.fan_id
                                          FROM employee_logs AS el2
                                          WHERE el2.type = 'subscription'
                                            AND el2.employee_id = ($1)
                                            AND el2.model_id = ($2)
                                            AND el2.creation_time >= to_timestamp($3)
                                            AND el2.creation_time <= to_timestamp($4))) as filtred_data;",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        // Query for all payers (new fans + old fans)
        $payersAllFans = pg_query_params(
            $db,
            "SELECT COUNT(DISTINCT fan_id) AS all_fan_count
                FROM (SELECT *
                      FROM employee_logs AS el
                      WHERE el.type = 'paid'
                        AND el.employee_id = ($1)
                        AND el.model_id = ($2)
                        AND el.creation_time >= to_timestamp($3)
                        AND el.creation_time <= to_timestamp($4)
                        AND el.fan_id IN (SELECT DISTINCT el2.fan_id
                                          FROM employee_logs AS el2
                                          WHERE el2.type = 'subscription'
                                            AND el2.employee_id = ($1)
                                            AND el2.model_id = ($2))) as filtred_data;",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        // Old payers = All payers - New payers
        $html .= '<div class="logs__row">
            New payers of old/new fans: ' . pg_fetch_result($payersAllFans, 0, 'all_fan_count') -
                    pg_fetch_result($payersNewFans, 0, 'new_fan_count') . '/'
                    . pg_fetch_result($payersNewFans, 0, 'new_fan_count') . '
            </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 5) New fans
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $newFans = pg_query_params(
            $db,
            "SELECT COUNT(*) 
                AS new_fans_count
                FROM (SELECT *
                      FROM employee_logs AS el
                      WHERE el.type = 'subscription'
                        AND el.employee_id = ($1)
                        AND el.model_id = ($2)
                        AND el.creation_time >= to_timestamp($3)
                        AND el.creation_time <= to_timestamp($4)) as subs",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        $html .= '<div class="logs__row">
            New fans: ' . pg_fetch_result($newFans, 0, 'new_fans_count') . '
            </div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 6) Unanswered subscriptions
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // Sales attempts checked as subspecies of outgoing messages.
        $msgTimings = pg_query_params(
            $db,
            "SELECT joined_tables.fan_id
                    FROM (
                             (SELECT el.fan_id AS fan, min(el.creation_time) AS greeting_message_timing
                              FROM employee_logs AS el
                              WHERE (el.type = 'outgoing message' OR el.type = 'sales attempt')
                                  AND el.employee_id = ($1)
                                  AND el.model_id = ($2)
                                  AND el.creation_time >= to_timestamp($3)
                                  AND el.creation_time <= to_timestamp($4)
                                  AND el.fan_id IN (SELECT DISTINCT el.fan_id
                                                    FROM employee_logs AS el
                                                    WHERE el.type = 'subscription'
                                                      AND el.employee_id = ($1)
                                                      AND el.model_id = ($2)
                                                      AND el.creation_time >= to_timestamp($3)
                                                      AND el.creation_time <= to_timestamp($4))
                              GROUP BY fan_id) AS greeting_messages
                                 INNER JOIN (SELECT el.fan_id, el.creation_time AS subscription_time
                                             FROM employee_logs AS el
                                             WHERE el.type = 'subscription'
                                               AND el.employee_id = ($1)
                                               AND el.model_id = ($2)
                                               AND el.creation_time >= to_timestamp($3)
                                               AND el.creation_time <= to_timestamp($4)) AS subscription_timings
                             ON greeting_messages.fan = subscription_timings.fan_id
                             ) as joined_tables",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        $html .= '<div class="logs__row">Unanswered subscriptions:<ul>';
        while ($row = pg_fetch_assoc($msgTimings)) {
            // Add clickable new-page link to unanswered fan
            $unansweredFanLink = '<a href="https://www.onlyfans.com/u' . $row['fan_id'] .
                '" target="_blank">Unanswered fan id=' . $row['fan_id'] . '</a>';
            $html .= '<li>' . $unansweredFanLink . '</li>';
        }
        $html .= '</ul></div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 7) Didn't write after greeting
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $lateMessages = pg_query_params(
            $db,
            "
            SELECT aggregated.fan, aggregated.text
            FROM (
                     (SELECT el.fan_id AS fan, el.creation_time AS subscription_time
                      FROM employee_logs AS el
                      WHERE el.type = 'subscription'
                        AND el.employee_id = ($1)
                        AND el.model_id = ($2)
                        AND el.creation_time >= to_timestamp($3)
                        AND el.creation_time <= to_timestamp($4)) AS subscriptions
                         INNER JOIN 
                                (SELECT DISTINCT ON (el.fan_id) el.fan_id, el.creation_time, text
                                     FROM employee_logs AS el
                                     WHERE (el.type = 'outgoing message' OR el.type = 'sales attempt')
                                         AND el.employee_id = ($1)
                                         AND el.model_id = ($2)
                                         AND el.creation_time >= to_timestamp($3)
                                         AND el.creation_time <= to_timestamp($4)
                                     order by el.fan_id, el.creation_time) AS first_messages
                         ON subscriptions.fan = first_messages.fan_id
                     ) AS aggregated
            WHERE extract(MINUTES FROM(aggregated.creation_time - aggregated.subscription_time)) > ($5);
            ",
            [$userID, $modelID, $sessionStart, $sessionEnd, $delay]
        );

        $html .= '<div class="logs__row">Didn\'t write after greeting:';

        // Show late messages if they are exists
        if (pg_num_rows($lateMessages) != 0) {
            $html .= '<ul>';
            while ($row = pg_fetch_assoc($lateMessages)) {
                $html .=
                    '<li>' .
                    'For fan with id=' . $row['fan'] .
                    ' late message is: "' . $row['text'] . '"' .
                    '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<div>No late messages!</div>';
        }

        $html .= '</div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 8) Unread messages
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $unreadMessageTiming = 10;      // todo: in minutes; should be adjustable

        // get table with columns: fan_id, text
        $unreadMessages = pg_query_params($db,
            "
            SELECT correspondence.fan_id, correspondence.text
            FROM (SELECT DISTINCT ON (el.fan_id) el.fan_id, el.type, el.creation_time, el.text
                  FROM employee_logs as el
                  WHERE (el.type = 'incoming message'
                      OR el.type = 'outgoing message'
                      OR el.type = 'sales attempt')
                    AND el.employee_id = ($1)
                    AND el.model_id = ($2)
                    AND el.creation_time >= to_timestamp($3)
                    AND el.creation_time <= to_timestamp($4)
                  ORDER BY el.fan_id, el.creation_time DESC) AS correspondence
            WHERE correspondence.type = 'incoming message'
              AND extract(MINUTES FROM (to_timestamp($4) - correspondence.creation_time)) >= ($5)
            ",
            [$userID, $modelID, $sessionStart, $sessionEnd, $unreadMessageTiming]
        );

        $html .= '<div class="logs__row">Unanswered messages:';

        // Show late messages if they are exists
        if (pg_num_rows($unreadMessages) != 0) {
            $html .= '<ul>';
            while ($row = pg_fetch_assoc($unreadMessages)) {
                $html .=
                    '<li>' .
                    'For fan with id=' . $row['fan_id'] .
                    ' unread message is: "' . $row['text'] . '"' .
                    '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<div>No unread messages!</div>';
        }

        $html .= '</div>';
        // -------------------------------------------------------------------------------------------------


        ////////////////////////////////////////////////////////////////////////////////////////////////////
        // 9) Logs (all logs):
        ////////////////////////////////////////////////////////////////////////////////////////////////////
        $allSessionsLogs = pg_query_params(
            $db,
            "SELECT *
                    FROM employee_logs as el
                    WHERE el.employee_id = ($1)
                      AND el.model_id = ($2)
                      AND el.creation_time >= to_timestamp($3)
                      AND el.creation_time <= to_timestamp($4)",
            [$userID, $modelID, $sessionStart, $sessionEnd]
        );

        if (pg_num_rows($allSessionsLogs) != 0) {
            $html .= '<ul>';
            while ($row = pg_fetch_assoc($allSessionsLogs)) {
                $html .= '<li>
                    logID='. $row['id'] .
                    ' employeeID=' . $row['employee_id'] .
                    ' modelID=' . $row['model_id'] .
                    ' fanID=' . $row['fan_id'] .
                    ' type=' . $row['type'] .
                    ' creationTime=' . $row['creation_time'] .
                    '</li>';

                if ($row['sub_msg_link']) {
                    // handling content for creation new-page link
                    $pattern = '/<a\s+(?:[^>]*?\s+)?href=("|\')(.*?)\1/';
                    preg_match($pattern, $row['sub_msg_link'], $matches);
                    $url = $matches[2];
                    $newPageLink = '<a href="'. $url . '" target="_blank">Message</a>';

                    $html .= ' link=' . $newPageLink;
                }
                if ($row['spent']) {
                    $html .= ' spent=' . $row['spent'];
                }
                if ($row['text']) {
                    $html .= ' text="' . $row['text'] . '"';
                }
            }
            $html .= '</ul>';
        } else {
            $html .= '<div>There are no logs for this session!</div>';
        }

        $html .= '</div>';
        // -------------------------------------------------------------------------------------------------

        pg_query($db, 'COMMIT');
        echo $html;
    } catch (Exception $e) {
        pg_query($db, 'ROLLBACK');
        http_response_code(400);  // bad request
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}