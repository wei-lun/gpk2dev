<?php

// 使用時需要加入以下語法在要加入選單的版塊裡
//  page: 起始頁
//  stype: 主類另選單模式，c：依娛樂城分類，m ：依6大分類
//          設為 c 時只會讀取 casinoid，設為 m 時只會讀取 mainct
//  casinoid: 頁面預設娛樂城
//  mainct: 頁面預設主分類
//  maxiconnum: 每頁最大讀取數量
//  rnd: 是否隨機輸出list，1：隨機輸出，0：依排序輸出

function ui_gametab_gp06($page,$stype,$casinoid,$mainct,$maxiconnum,$rnd)
{
  $gametab_html ='
    <!-- 首頁gamelobby顯示區域 -->
    <div class="section_gamelobby">
        <div class="wrap">
     <script type="text/javascript">
     var global = {
      page: '.$page.',
      stype: "'.$stype.'",
      casinoid: "'.$casinoid.'",
      mainct: "'.$mainct.'",
      maxiconnum: '.$maxiconnum.',
      rnd: "'.$rnd.'"
     }
     </script>
       ';
  echo $gametab_html;
  require_once dirname(dirname(dirname(__DIR__))).'/casino/casino_config.php';
  $gametab_list = home_gamelist() ;

  $gametab_list .='
      </div>
    </div>
     <!-- gamelobby結束 -->';
  echo $gametab_list;
}
?>