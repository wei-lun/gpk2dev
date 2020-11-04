<?php
$menu_top_item_tmplate = '<li class="nav-item navi_ mct_id "><a class="nav-link" href=" website_baseurl gamelobby.php?mgc= mct_id " target="_self"> mct_name </a></li>';
/*
$menu_top_item_base = '
  main_category_item

  <li class="nav-item navi_promotions"><a class="nav-link" href="' . $config['website_baseurl'] . 'promotions.php" target="_self">'.$tr['Promotions'].'</a></li>

  <li class="nav-item navi_service"><a class="nav-link" href="' . $config['website_baseurl'] . 'contactus.php" target="_self">'.$tr['online service'].'</a></li>
';*/
// <li><a href="' . $config['website_baseurl'] . '" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['homepage'].'</a></li>
// menu_casino.tmpl.php、menu_casino.tmpl.php
// $tr['homepage']                 = '首頁';
// $tr['Electronic entertainment'] = '電子遊藝';
// $tr['Live video']               = '真人視訊';
// $tr['Fishing people']           = '捕魚達人';
// $tr['Promotions']               = '優惠活動';
// $tr['online service']           = '在線客服';
// $tr['About us']                 = '關於我們';
// $tr['Partner']                  = '合作夥伴';
// $tr['How deposit']           = '如何儲值';
// $tr['How Withdrawal']           = '如何提款';
// $tr['Contact US']               = '聯絡我們';


$host_footer_html = '
    <div style="display:flex;justify-content: center;">
        <ul class="nav navbar-nav">
            <li class="navi_f_about"><a href="' . $config['website_baseurl'] . 'aboutus.php">' . $tr['About us'] . '</a></li>
            <li class="navi_f_promotions"><a href="' . $config['website_baseurl'] . 'promotions.php">' . $tr['Promotions'] . '</a></li>
            <li class="navi_f_partner"><a href="' . $config['website_baseurl'] . 'partner.php">' . $tr['Partner'] . '</a></li>
            <li class="navi_f_howtodeposit"><a href="' . $config['website_baseurl'] . 'howtodeposit.php">' . $tr['How deposit'] . '</a></li>
            <li class="navi_f_howtowithdraw"><a href="' . $config['website_baseurl'] . 'howtowithdraw.php">' . $tr['How Withdrawal'] . '</a></li>
            <li class="navi_f_contactus"><a href="' . $config['website_baseurl'] . 'contactus.php">' . $tr['Contact US'] . '</a></li>
            <li class="navi_f_mobile"><a href="javascript:void(0);" onclick="msitechg(\'mobile\'); return false;">' . $tr['Mobile Version'] . '</a></li>
        </ul>
    </div>';
