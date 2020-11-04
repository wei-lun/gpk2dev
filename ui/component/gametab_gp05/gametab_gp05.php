<?php
// 使用時需要加入以下語法在要加入選單的版塊裡
//  page: 起始頁
//  stype: 主類另選單模式，c：依娛樂城分類，m ：依6大分類
//          設為 c 時只會讀取 casinoid，設為 m 時只會讀取 mainct
//  casinoid: 頁面預設娛樂城
//  mainct: 頁面預設主分類
//  maxiconnum: 每頁最大讀取數量
//  rnd: 是否隨機輸出list，1：隨機輸出，0：依排序輸出
?>
 <!-- 首頁gamelobby顯示區域 -->
<div class="row section_gamelobby" style="height: 660px;">
    <div class="wrap">
        <div class="topInner clearfix">
          <div class="img">
            <ul class="topUl clearfix">
              <li><a href="<?php echo $config['website_baseurl'];?>register.php"><img src="<?php echo $cdnfullurl ?>img/home/top_img01.png" alt=""></a></li>
              <li><a href="<?php echo $config['website_baseurl'];?>promotions.php"><img src="<?php echo $cdnfullurl ?>img/home/top_img02.png" alt=""></a></li>
              <li><a href="<?php echo $config['website_baseurl'];?>gamelobby.php"><img src="<?php echo $cdnfullurl ?>img/home/top_img03.png" alt=""></a></li>
              <li><a href="<?php echo $config['website_baseurl'];?>howtowithdraw.php"><img src="<?php echo $cdnfullurl ?>img/home/top_img04.png" alt=""></a></li>
              <li><a href="<?php echo $config['website_baseurl'];?>partner.php"><img src="<?php echo $cdnfullurl ?>img/home/top_img05.png" alt=""></a></li>
            </ul>
          </div>
        </div>

          <div class="subInfo"><a href="javascript:void(0);" onclick="msitechg('mobile'); return false;"><img src="<?php echo $cdnfullurl ?>img/home/mobile.png" alt=""></a></div>
 <script type="text/javascript">
 var global = {
  page: 1,
  stype: 'c',
  casinoid: '',
  mainct: '',
  maxiconnum: 6,
  rnd: '1'
 }
 </script>
   <?php require_once dirname(dirname(dirname(__DIR__))).'/casino/casino_config.php';
   home_gamelist(); ?>

        <div class="r_subInfo"><img src="<?php echo $cdnfullurl ?>img/home/side_ad.png" alt=""></div>

  </div>
</div>
 <!-- gamelobby結束 -->