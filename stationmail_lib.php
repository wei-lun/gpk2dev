<?php

function sendMail($subject, $message)
{
  global $stationmail;

  $mailcode = getMailCode();

  $sql = <<<SQL
  INSERT INTO root_stationmail 
  (
    msgfrom, msgto, subject, message, mailcode
  )VALUES (
    '{$_SESSION['member']->account}', '{$stationmail['sendto_system_cs']}', '{$subject}', '{$message}', '{$mailcode}'
  );
SQL;

  return runSQL($sql);
}

function deleteMail($mailcode, $source)
{
  global $tr;

  $sql = [];

  $column = ($source == 'inbox') ? 'msgto' : 'msgfrom';

  foreach ($mailcode as $code) {
    $codeStr = explode('_', $code);
    $codeStr = validateMailcodeMailtype($codeStr[0], $codeStr[1]);

    if (!$codeStr) {
      return ['status' => false, 'result' => $tr['Wrong mail code or type']];
    }

    $table = ($codeStr['type'] == 'group') ? 'root_member_groupmail' : 'root_stationmail';

    $sql[] = <<<SQL
    UPDATE {$table}
    SET status = 0
    WHERE mailcode = '{$codeStr['code']}'
    AND {$column} = '{$_SESSION['member']->account}';
SQL;

    if (count($sql) >= 200) {
      $sql = 'BEGIN;'.implode(';', $sql).'COMMIT;';
      $result = runSQLtransactions($sql);

      if (!$result) {
        return ['status' => false, 'result' => $tr['delete mail fail']];
      }

      $sql = [];
    }
  }

  $sql = 'BEGIN;'.implode(';', $sql).'COMMIT;';
  $result = runSQLtransactions($sql);

  if (!$result) {
    return ['status' => false, 'result' => $tr['delete mail fail']];
  }

  return ['status' => true, 'result' => $tr['delete mail success']];
}

function updateReadTime($mailcode, $mailtype)
{
  $table = ($mailtype == 'group') ? 'root_member_groupmail' : 'root_stationmail';

  $sql = <<<SQL
  UPDATE {$table} 
  SET readtime = now()
  WHERE mailcode = '{$mailcode}'
  AND msgto = '{$_SESSION['member']->account}'
  AND readtime IS NULL
  RETURNING to_char((readtime AT TIME ZONE 'posix/Etc/GMT+4') ,'YYYY-MM-DD HH24:MI:SS') AS readtime,
            to_char((sendtime AT TIME ZONE 'posix/Etc/GMT+4') ,'YYYY-MM-DD HH24:MI:SS') AS sendtime,
            subject,
            message,
            template;
SQL;

  $result = runSQLall($sql);

  if (!$result[0]) {
    return false;
  }

  return $result[1];
}

function getReadTime($mailcode, $mailtype)
{
  $table = ($mailtype == 'group') ? 'root_member_groupmail' : 'root_stationmail';

  $sql = <<<SQL
  SELECT readtime
  FROM {$table}
  WHERE mailcode = '{$mailcode}'
  AND msgto = '{$_SESSION['member']->account}'
  AND status = '1';
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  return ($result[1]->readtime == '') ? 'N' : $result[1]->readtime;
}

function getMailData($mailcode, $mailtype, $source)
{
  $table = ($mailtype == 'group') ? 'root_member_groupmail' : 'root_stationmail';
  $column = ($source == 'inbox') ? 'msgto' : 'msgfrom';

  $sql = <<<SQL
  SELECT to_char((readtime AT TIME ZONE 'posix/Etc/GMT+4') ,'YYYY-MM-DD HH24:MI:SS') AS readtime,
        to_char((sendtime AT TIME ZONE 'posix/Etc/GMT+4') ,'YYYY-MM-DD HH24:MI:SS') AS sendtime,
        {$column},
        subject,
        message,
        template
  FROM {$table}
  WHERE mailcode = '{$mailcode}'
  AND {$column} = '{$_SESSION['member']->account}'
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  return $result[1];
}

function getInboxMailData($count = 0, $tzname = 'posix/Etc/GMT+4')
{
  $data = [];

  $sql = <<<SQL
  SELECT root_member_groupmail.subject,
        root_member_groupmail.message,
        to_char((root_member_groupmail.readtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS readtime,
        to_char((root_member_groupmail.sendtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS sendtime,
        root_member_groupmail.mailtype,
        root_member_groupmail.mailcode,
        root_member_groupmail.template
  FROM root_member_groupmail
  WHERE root_member_groupmail.msgto = '{$_SESSION['member']->account}'
  AND status = '1'
  UNION ALL
  SELECT root_stationmail.subject,
        root_stationmail.message,
        to_char((root_stationmail.readtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS readtime,
        to_char((root_stationmail.sendtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS sendtime,
        root_stationmail.mailtype,
        root_stationmail.mailcode,
        root_stationmail.template
  FROM root_stationmail
  WHERE root_stationmail.msgto = '{$_SESSION['member']->account}'
  AND status = '1'
  ORDER BY sendtime DESC
  LIMIT 7 OFFSET {$count};
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  foreach ($result as $k => $v) {
    $data[$k] = [
      'subject' => mb_substr($v->subject, 0, 100),
      'message' => mb_substr(htmlspecialchars_decode($v->message), 0, 100),
      'readtime' => ($v->readtime == '') ? '-' : $v->readtime,
      'sendtime' => $v->sendtime,
      'mailtype' => $v->mailtype,
      'mailcode' => $v->mailcode,
      'template' => $v->template,
      'isRead' => ($v->readtime == '') ? 'unread' : ''
    ];

    if ($v->template != '') {
      $template = json_decode($v->template, true);
      $data[$k]['subject'] = str_replace($template['code'], $template['content'], $data[$k]['subject']);
      $data[$k]['message'] = str_replace($template['code'], $template['content'], $data[$k]['message']);
    }
  }

  return $data;
}

function getSentMailData($count = 0, $tzname = 'posix/Etc/GMT+4')
{
  $data = [];

  $sql = <<<SQL
  SELECT subject,
        message,
        to_char((readtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS readtime,
        to_char((sendtime AT TIME ZONE '{$tzname}') ,'YYYY-MM-DD HH24:MI:SS') AS sendtime,
        mailtype,
        mailcode
  FROM root_stationmail
  WHERE msgfrom = '{$_SESSION['member']->account}'
  AND status = '1'
  ORDER BY sendtime DESC
  LIMIT 7 OFFSET {$count};
SQL;

  $result = runSQLall($sql);

  if (empty($result[0])) {
    return false;
  }

  unset($result[0]);

  foreach ($result as $k => $v) {
    $data[$k] = [
      'subject' => mb_substr($v->subject, 0, 100),
      'message' => mb_substr(htmlspecialchars_decode($v->message), 0, 100),
      // 'readtime' => $v->readtime,
      'sendtime' => $v->sendtime,
      'mailtype' => $v->mailtype,
      'mailcode' => $v->mailcode
    ];
  }

  return $data;
}

function wordLimit($str, $limit)
{
  $strCount = preg_match_all('/\X/u', html_entity_decode($str, ENT_QUOTES, 'UTF-8'));

  if ($strCount > $limit) {
    return false;
  }

  return true;
}

function getMailCode()
{
  $mailcode = 'persona'.date('YmdHis').$_SESSION['member']->salt;

  return sha1($mailcode);
}