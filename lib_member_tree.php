<?php
// ----------------------------------------------------------------------------
// Features :    前台 --  member tree LIB
// File Name: lib_member_tree.php
// Author   : Dright
// Related  : preferential_calculation
// Log      :
// ----------------------------------------------------------------------------
// 對應資料表
// 相關的檔案
// 功能說明
// 1. class for member tree manipulation
// update   : yyyy.mm.dd
//
//
//    example:
//
//    $member_list = MemberTreeNode::getMemberListByDate('2017-12-20');
//    $tree_root = $member_list[1];
//    MemberTreeNode::buildMemberTree($tree_root, $member_list);
//    MemberTreeNode::visitBottomUp($tree_root, function($member)  {
//      // do something...
//    }
//

/**
 * [MemberTreeNode description]
 *   public $id               => int(1)
 *   public $account          => string(4) "root"
 *   public $status           => string(1) "1"
 *   public $therole          => string(1) "R"
 *   public $parent_id        => int(1)
 *   public $predecessor_id_list => array(12)
 *   public $commissionrule   => string(7) "default"
 *   public $children         =>   array(12)
 *   public $node_data        =>  object
 */
class MemberTreeNode
{
    public $id;
    public $account;
    public $status;
    public $therole;
    public $parent_id;
    public $commissionrule;
    public $favorablerule;

    public $member_level;
    public $predecessor_id_list;
    public $children;
    public $node_data;

    public static function getPredecessorListByAccount($member_account)
    {
        $recursive_sql = <<<SQL
      WITH RECURSIVE subordinates AS (
        SELECT
          id,
          parent_id,
          account,
          therole,
          status,
          commissionrule,
          favorablerule,
          feedbackinfo
        FROM
          root_member
        WHERE
          account = :account
        UNION
          SELECT
            m.id,
            m.parent_id,
            m.account,
            m.therole,
            m.status,
            m.commissionrule,
            m.favorablerule,
            m.feedbackinfo
          FROM
            root_member m
          INNER JOIN subordinates s ON s.parent_id = m.id
      ) SELECT
        *
      FROM
        subordinates;
SQL;

        $recursive_result = runSQLall_prepared($recursive_sql, [':account' => $member_account], self::class, 0, 'r');

        // print_r($recursive_result);

        return $recursive_result;
    }

    public static function getPredecessorList($member_id)
    {
        $recursive_sql = <<<SQL
      WITH RECURSIVE subordinates AS (
        SELECT
          id,
          parent_id,
          account,
          therole,
          status,
          commissionrule,
          favorablerule,
          feedbackinfo
        FROM
          root_member
        WHERE
          id = :id
        UNION
          SELECT
            m.id,
            m.parent_id,
            m.account,
            m.therole,
            m.status,
            m.commissionrule,
            m.favorablerule,
            m.feedbackinfo
          FROM
            root_member m
          INNER JOIN subordinates s ON s.parent_id = m.id
      ) SELECT
        *
      FROM
        subordinates;
SQL;

        $recursive_result = runSQLall_prepared($recursive_sql, [':id' => $member_id], self::class, 0, 'r');

        // print_r($recursive_result);

        return $recursive_result;
    }

    public static function getSuccessorList($agent_id)
    {
        $recursive_sql = <<<SQL
      WITH RECURSIVE subordinates AS (
        SELECT
          id,
          parent_id,
          account,
          therole,
          status,
          commissionrule,
          favorablerule
        FROM
          root_member
        WHERE
          id = :id
        UNION
          SELECT
            m.id,
            m.parent_id,
            m.account,
            m.therole,
            m.status,
            m.commissionrule,
            m.favorablerule
          FROM
            root_member m
          INNER JOIN subordinates s ON m.parent_id = s.id
      ) SELECT
        *
      FROM
        subordinates;
SQL;

        $recursive_result = runSQLall_prepared($recursive_sql, [':id' => $agent_id], self::class, 0, 'r');

        // print_r($recursive_result);

        return $recursive_result;
    }

    public static function getMemberList($member_id = null, $is_bottom_up = false)
    {
        if (empty($member_id)) {
            $all_member_sql = "SELECT
                id,
                account,
                status,
                therole,
                parent_id,
                commissionrule,
                favorablerule,
                feedbackinfo
              FROM root_member
              ORDER BY parent_id, id
            ;";

            $all_member_sql_result = runSQLall_prepared($all_member_sql, [], self::class, 0, 'r');

        } elseif ($is_bottom_up) {
            $all_member_sql_result = self::getPredecessorList($member_id);
        } else {
            $all_member_sql_result = self::getSuccessorList($member_id);
        }

        /**
         * [$member_list   key is member id]
         * @var array
         */
        $member_list = [];

        // construct member_list
        foreach ($all_member_sql_result as $member) {
            $member_list[(int) $member->id] = $member;
        }

        return $member_list;
    }

    public static function getMemberListByDate($date, $root_id = null)
    {
        $all_member_sql = "SELECT
          member_id as id,
          member_account as account,
          root_member.status,
          member_therole as therole,
          member_parent_id as parent_id,
          root_member.commissionrule,
          root_member.favorablerule
        FROM root_statisticsdailyreport
          LEFT JOIN root_member on root_member.id = root_statisticsdailyreport.member_id
        WHERE root_statisticsdailyreport.dailydate = :date
        ORDER BY parent_id, id
        ;";

        $all_member_sql_result = runSQLall_prepared($all_member_sql, [':date' => $date], self::class, 0, 'r');

        /**
         * [$member_list   key is member id]
         * @var array
         */
        $member_list = [];

        // construct member_list
        // unset($all_member_sql_result[0]);
        foreach ($all_member_sql_result as $member) {
            $member_list[(int) $member->id] = $member;
        }

        return $member_list;
    }

    public static function buildMemberTree($current_node, &$member_list, $init_data = [], $tree_level = 1, $predecessor_id_list = [])
    {

        $current_node->member_level = $tree_level;
        $current_node->predecessor_id_list = $predecessor_id_list;
        $parent_id = $current_node->id;

        if ($current_node->id == $current_node->parent_id || empty($current_node->parent_id)) {
            $current_node->parent = null;
        } else {
            $current_node->parent = $member_list[$current_node->parent_id];
        }

        // init node_data
        $current_node->node_data = null;
        if (is_array($init_data)) {
            $current_node->node_data = (object) $init_data;
        } elseif (is_callable($init_data)) {
            $init_data($current_node);
        }

        $current_node->children = array_filter($member_list, function ($member) use ($parent_id) {
            return $member->parent_id == $parent_id && $member->id != $parent_id;
        });

        array_push($predecessor_id_list, $current_node->id);
        foreach ($current_node->children as $member) {
            self::buildMemberTree($member, $member_list, $init_data, $tree_level + 1, $predecessor_id_list);
        }

    }

    /**
     * [visit_tree_buttom_up description]
     * @param  [type]   $tree_node [description]
     * @param  callable $callback  [description]
     * @return [type]              [description]
     */
    public static function visitBottomUp($tree_node, callable $callback)
    {

        foreach ($tree_node->children as $node) {
            self::visitBottomUp($node, $callback);
        }

        $callback($tree_node);
    }

    /**
     * [visit_tree_top_down description]
     * @param  [type]   $tree_node [description]
     * @param  callable $callback  [description]
     * @return [type]              [description]
     */
    public static function visitTopDown($tree_node, callable $callback)
    {

        $callback($tree_node);

        foreach ($tree_node->children as $node) {
            self::visitTopDown($node, $callback);
        }

    }

    public static function createNodeBySession()
    {
        $member = new self;

        $member->id = $_SESSION['member']->id;
        $member->account = $_SESSION['member']->account;
        $member->status = $_SESSION['member']->status;
        $member->therole = $_SESSION['member']->therole;
        $member->parent_id = $_SESSION['member']->parent_id;
        $member->commissionrule = $_SESSION['member']->commissionrule;
        $member->favorablerule = $_SESSION['member']->favorablerule;

        return $member;
    }

    public static function getPredecessorPreferentialRate($member, &$member_list)
    {
        // get allocables
        $predecessor_allocable_list = [];

        foreach ($member->predecessor_id_list as $predecessor_id) {

            $predecessor = $member_list[$predecessor_id];

            // skip root and disabled member
            if ($predecessor_id == 1 || $predecessor->status != '1') {
                continue;
            }

            // calculate allocable
            $allocable = 0;

            if (!empty($predecessor->feedbackinfo)) {
                $feedbackinfo = json_decode($predecessor->feedbackinfo);
                $allocable = $feedbackinfo->preferential->allocable;
            }

            $predecessor_allocable_list[] = $allocable;
        }

        // calculate preferential rates
        $max_rate = self::getMaxPreferentialRate($member, $member_list);
        $reverse_predecessor_allocable_list = array_reverse($predecessor_allocable_list);
        $predecessor_rate_list = [];

        foreach ($reverse_predecessor_allocable_list as $index => $allocable) {
            if (empty($predecessor_rate_list)) {
                $predecessor_rate_list[] = $allocable;
                continue;
            }

            $occupied = $allocable - $reverse_predecessor_allocable_list[$index - 1];

            if ($occupied < 0) {
                $occupied = 0;
            }

            array_unshift($predecessor_rate_list, $occupied);
        }

        // check last agent rate
        if (count($predecessor_rate_list) > 0) {
            $minRate = self::getLastAgentRate($member, $member_list);

            // var_dump($member->predecessor_id_list);
            // var_dump($minRate);

            if ($predecessor_rate_list[count($predecessor_rate_list) - 1] < $minRate) {
                $predecessor_rate_list[count($predecessor_rate_list) - 1] = $minRate;
            }
        }

        // add self rate
        $predecessor_rate_list[] = self::getSelfPreferentialRate($member, $member_list);

        // add rest rate to first agent
        $predecessor_rate_list[0] += round(1 - array_sum($predecessor_rate_list), 4);

        return $predecessor_rate_list;
    }

    public static function getSelfPreferentialRate($member, &$member_list)
    {
        if ($member->isRoot()) {
            return 0;
        }

        $first_agent = null;
        if ($member->isFirstLevelAgent()) {
            $first_agent = $member;
        } else {
            $first_agent = $member_list[($member->predecessor_id_list)[1]];
        }

        if (!empty($first_agent->feedbackinfo)) {
            $feedbackinfo = json_decode($first_agent->feedbackinfo, true);

            if (isset($feedbackinfo['preferential']['1st_agent']['self_ratio'])) {
                return $feedbackinfo['preferential']['1st_agent']['self_ratio'];
            }
        }

        return 0;
    }

    public static function getMaxPreferentialRate($member, &$member_list)
    {
        if ($member->isRoot()) {
            return 0;
        }

        $first_agent = null;
        if ($member->isFirstLevelAgent()) {
            $first_agent = $member;
        } else {
            $first_agent = $member_list[($member->predecessor_id_list)[1]];
        }

        if (!empty($first_agent->feedbackinfo)) {
            $feedbackinfo = json_decode($first_agent->feedbackinfo, true);

            if (isset($feedbackinfo['preferential']['1st_agent']['child_occupied']['max'])) {
                return $feedbackinfo['preferential']['1st_agent']['child_occupied']['max'];
            }
        }

        return 0;
    }

    public static function getLastAgentRate($member, &$member_list)
    {
        if ($member->isRoot()) {
            return 0;
        }

        $first_agent = null;
        if ($member->isFirstLevelAgent()) {
            $first_agent = $member;
        } else {
            $first_agent = $member_list[($member->predecessor_id_list)[1]];
        }

        if (!empty($first_agent->feedbackinfo)) {
            $feedbackinfo = json_decode($first_agent->feedbackinfo, true);

            if (isset($feedbackinfo['preferential']['1st_agent']['last_occupied'])) {
                return $feedbackinfo['preferential']['1st_agent']['last_occupied'];
            }
        }

        return 0;
    }

    public function isRoot()
    {
        return $this->id == 1;
    }

    public function isFirstLevelAgent()
    {
        return ($this->parent_id == 1 && $this->therole == 'A');
    }

    public function isSuccessor($member_account)
    {
        $predecessor_list = self::getPredecessorListByAccount($member_account);

        foreach ($predecessor_list as $predecessor) {
            if ($predecessor->account == $this->account) {
                return true;
            }
        }

        return false;
    }

    public function isActive()
    {
        return $this->status == 1;
    }
}
