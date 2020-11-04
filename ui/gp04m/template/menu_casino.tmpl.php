<?php

//這邊修改遊戲分類menu的結構
$menu_top_item_tmplate = '<li class="nav-item navi_ mct_id "><a class="nav-link" href=" website_baseurl gamelobby.php?mgc= mct_id " target="_self"> mct_name </a></li>';

//這個修改後面兩項(要再增加其他項在遊戲分類之後改這)
$menu_top_item_base = '

    <a href="'.$config['website_baseurl'].'" class="nbox">
      <div class="icon"><i class="fa fa-home"></i></div>
      <span class="title">'.$tr['home'].'</span></a>

    <a href="'.$config['website_baseurl'].'gamelobby.php" class="nbox">
      <div class="icon"><i class="fa fa-gamepad"></i></div>
      <span class="title">'.$tr['gamelobby'].'</span></a>

    <a href="' . $config['website_baseurl'] . 'contactus.php" target="_self" class="nbox">
      <div class="icon"><i class="fas fa-comments"></i></div>
      <span class="title">'.$tr['footer menu_Contact Us'].'</span></a>
';

/*
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