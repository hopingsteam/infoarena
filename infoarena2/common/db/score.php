<?php

// Update a score.
// user/task/round can be null.
function score_update($rank_id, $user_id, $task_id, $round_id, $value)
{
    log_assert($rank_id);
    $query = sprintf("
            INSERT INTO ia_score (`id`, `score`, `user_id`, `task_id`, `round_id`)
            VALUES ('%s', %s, %s, %s, %s) ON DUPLICATE KEY UPDATE `id`=`id`",
            db_escape($rank_id), $value,
            ($user_id === null ? 'NULL' : $user_id),
            ($task_id === null ? 'NULL' : "'".db_escape($task_id)."'"),
            ($round_id === null ? 'NULL' : "'".db_escape($round_id)."'"));
    //log_print($query);
    return db_query($query);
}

function db_escape_array($array)
{
    $ret = '';
    log_print($array);
    foreach ($array as $element) {
        if ($ret) {
            $ret .= ", ";
        }
        $ret .= "'" . db_escape($element) . "'";
    }
    return $ret;
}

// Get scores.
// $user, $task, $round can be null.
function score_get($rank_id, $user, $task, $round, $start, $count, $groupby = "user_id")
{
    // Build WHERE.
    $where = array();
/*    if ($user != null) {
        if (is_array($user) && count($user) > 0) {
            $where[] = "(`user_id` IN (" . db_escape_array($user) . "))";
        } else if (is_string($user)) {
            $where[] = sprintf("(`user_id` == '%s')", $user);
        }
    }*/
    if ($task != null) {
        if (is_array($task) && count($task) > 0) {
            $where[] = "(`task_id` IN (" . db_escape_array($task) . "))";
        } else if (is_string($task)) {
            $where[] = sprintf("(`task_id` = '%s')", $task);
        }
    }
    if ($round != null) {
        if (is_array($round) && count($round) > 0) {
            log_print(count($round));
            $where[] = "(`round_id` IN (" . db_escape_array($round) . "))";
        } else if (is_string($round)) {
            $where[] = sprintf("(`round_id` = '%s')", $round);
        }
    }

    log_assert($rank_id !== null);
    $where[] = sprintf("ia_score.`id` = '%s'", db_escape($rank_id));
    $query = sprintf("
            SELECT ia_score.`id` as `rank_id`, `user_id`, `task_id`, `round_id`, SUM(`score`) as score, 
                ia_user.username as user_name, ia_user.full_name as user_full
            FROM ia_score 
                LEFT JOIN ia_user ON ia_user.id = ia_score.user_id
            WHERE %s GROUP BY %s 
            ORDER BY `score` DESC LIMIT %s, %s",
            join($where, " AND "), $groupby, $start, $count);
    log_print($query);
    return db_fetch_all($query);
}

?>