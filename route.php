<?php
// example:
// http://test.gpk17.com/route.php?home/test --> correct
// http://test.gpk17.com/route.php?home/test/ --> error
// http://test.gpk17.com/route.php?wallets --> error , session 衝突的問題, 移除就可以.
//
// 把 http://test.gpk17.com/route.php --> 換成  http://test.gpk17.com/ 應該就可以了。




var_dump($_SERVER);
//var_dump($_GET);



try {
  $QUERY_STRING = str_replace("/", "_", $_SERVER['QUERY_STRING']);
  $php_script = '/'.$QUERY_STRING.'.php';
  $filename  = dirname(__FILE__) ."$php_script";

  // 暴力解法
  if($_SERVER['QUERY_STRING'] == 'lobby/mggameh5?gc=Roulette') {
    $filename = 'lobby_mggameh5.php';
  }

  if($e = file_exists($filename)) {
    require_once $filename;
  }

  throw new Exception($e);

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
    echo "<p>URL error!!  $QUERY_STRING  </p>";
}



 ?>
