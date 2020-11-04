<?php
// 找上級
function find_grand_parent($select_parent_id){
    $sql =<<<SQL
       SELECT parent_id FROM root_member WHERE id = '{$select_parent_id}'
SQL;

    $result = runSQLall($sql);
    unset($result[0]);

    return $result;
}

function find_parent($parent){
    $tzonename = 'posix/Etc/GMT-8';

	$parent_sql=<<<SQL
    SELECT agent.id, agent.account, agent.parent_id ,agent.therole,to_char((lastlogin AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS lastlogin,agent.enrollmentdate, (
        SELECT COUNT(parent_id) AS child_count
            FROM root_member
                WHERE root_member.parent_id = agent.id
        ) AS child_count
         FROM (
            SELECT id, account, parent_id,therole,lastlogin,enrollmentdate
                FROM root_member
                WHERE root_member.parent_id = '{$parent}'
    ) AS agent
SQL;

    return $parent_sql;

}

// 查看下級
function find_children($select_children_id){
    $tzonename = 'posix/Etc/GMT-8';

    $child_sql=<<<SQL
     SELECT agent.id, agent.account, agent.parent_id ,agent.therole,to_char((lastlogin AT TIME ZONE '{$tzonename}'),'YYYY/MM/DD HH24:MI') AS lastlogin,agent.enrollmentdate, (
            SELECT COUNT(parent_id) AS child_count
                FROM root_member
                    WHERE root_member.parent_id = agent.id
            ) AS child_count
            FROM (
            SELECT id, account, parent_id,therole,lastlogin,enrollmentdate
                FROM root_member
                WHERE root_member.parent_id = '{$select_children_id}'
            ) AS agent
SQL;
    return $child_sql;
} // end find_children

// 檢查所有下級
function check_children($select_children_id){
    $check_child_sql=<<<SQL
        SELECT * FROM root_member WHERE parent_id = '{$select_children_id}'
SQL;
    return $check_child_sql;
}


//查看所有下級人數
function get_children_total($m_id){
    $recursive_sql = <<<SQL
      WITH RECURSIVE subordinates AS (
        SELECT
          id
          FROM
          root_member
        WHERE
          id = '{$m_id}'
        UNION
          SELECT
            m.id
          FROM
            root_member m
          INNER JOIN subordinates s ON m.parent_id = s.id
      ) SELECT
        count (id)-1 as count
      FROM
        subordinates;
SQL;

    $result = runSQLall($recursive_sql);
    unset($result[0]);

    return $result;
}

?>