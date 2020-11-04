<?php
require_once dirname(__FILE__) ."/lib_member_tree.php";

class lib_agentarea
{
  private $memberTreeObj;

  public function __construct()
  {
    $this->memberTreeObj = new MemberTreeNode();
  }

  /**
   * 計算團隊成員總數
   *
   * @param $id - 會員id
   * @return int
   */
  public function getSuccessorCount($id)
  {
    $successorList = $this->memberTreeObj::getSuccessorList($id);

    return (count($successorList) - 1);
  }

  /**
   * 計算指定區間累積佣金
   *
   * @param $dateRange - 時間範圍, 距今多少天前
   * @return int
   */
  public function getAgentCommission($dateRange)
  {
    $commission = 0;
    $today = gmdate('Y-m-d',time() + -4*3600);

    $sql = <<<SQL
    SELECT agent_commission
    FROM root_commission_dailyreport
    WHERE member_account = '{$_SESSION['member']->account}'
    AND (dailydate BETWEEN date '{$today}' - integer '{$dateRange}' AND '{$today}');
SQL;

    $result = runSQLall($sql);

    if (empty($result[0])) {
      return false;
    }

    unset($result[0]);

    foreach ($result as $v) {
      $commission += $v->agent_commission;
    }

    return round($commission, 2);
  }
}
