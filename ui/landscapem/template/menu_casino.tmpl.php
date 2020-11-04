<?php

//這邊修改遊戲分類menu的結構
$menu_top_item_tmplate = '<div class="f_menu footer_ mct_id "><a href=" website_baseurl gamelobby.php?mgc= mct_id " target="_self"><img src=" cdnfullurl img/home/footer_ mct_id .png" alt=""></a></div>';
//mct_name 
//<img src=" cdnfullurl img/home/footer_ mct_id .png" alt="">
//這個修改後面兩項(要再增加其他項在遊戲分類之後改這)
if(isset($_SESSION['member'])){
$menu_top_item_base = '
    <div class="fast-store"><a  onclick="open_md_page(this)" data-target="'.$config['website_baseurl'].'deposit.php"><img src=" cdnfullurl img/home/footer_button9.png" alt=""></a></div>

     main_category_item 
    
';
}else{
  $menu_top_item_base = '
    <div class="fast-store"><a href="'.$config['website_baseurl'].'login2page.php"><img src=" cdnfullurl img/home/footer_button9.png" alt=""></a></div>

     main_category_item 
    
';
}

/*
    <!--<a href="'.$config['website_baseurl'].'" class="nbox">
      <div class="icon"><i class="fa fa-home"></i></div>
      <span class="title">首页</span></a>

    <a href="'.$config['website_baseurl'].'gamelobby.php" class="nbox">
      <div class="icon"><i class="fa fa-gamepad"></i></div>
      <span class="title">游戏大厅</span></a>

    <a href="' . $config['website_baseurl'] . 'contactus.php" target="_self" class="nbox">
      <div class="icon"><i class="fas fa-comments"></i></div>
      <span class="title">'.$tr['footer menu_Contact Us'].'</span></a>-->
      
//修改footer
$host_footer_mobile_html ='<div class="menu-bottom">
  <div class="row">
	        <div class="col game">
                <i class="fa fa-gamepad" aria-hidden="true"></i>
				<a href="./gamelobby.php">游戏</a>
			</div>
			<div class="col wallet">
				<i class="far fa-money-bill-alt" aria-hidden="true"></i>
				<a href="./wallets.php">帐务</a>
			</div>
            <div class="col chat">
				<i class="far fa-comment-dots" aria-hidden="true"></i>
				<a href="#">客服</a>
			</div>
			<div class="col promo">
               <i class="fa fa-gift" aria-hidden="true"></i>
               <a href="./promotions.php">优惠</a>
			</div>
			<div class="col home">
				<i class="fa fa-user" aria-hidden="true"></i>
				<a href="./member.php">我的</a>
			</div>
  </div>
  </div>';
*/
  ?>