<?php
$menu_top_item = '';

$menu_top_item = $menu_top_item . '
  <li><a href="' . $config['website_baseurl'] . '" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['homepage'].'</a></li>

  <li><a href="' . $config['website_baseurl'] . 'gamelobby.php" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['Electronic entertainment'].'</a></li>

  <li><a href="' . $config['website_baseurl'] . 'gamelobby.php?mgc=Live" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['Live video'].'</a></li>

  <li><a href="' . $config['website_baseurl'] . 'gamelobby.php?mgc=Lottery" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>' . $tr['Lottery game'] . '</a></li>

  <li><a href="' . $config['website_baseurl'] . 'gamelobby.php?mgc=Fishing" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['Fishing people'].'</a></li>

  <li><a href="' . $config['website_baseurl'] . 'promotions.php" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['Promotions'].'</a></li>

  <li><a href="' . $config['website_baseurl'] . 'contactus.php" target="_self"><span class="glyphicon glyphicon-queen" aria-hidden="true"></span>'.$tr['online service'].'</a></li>
';
// $tr['homepage']                 = '首頁';
// $tr['Electronic entertainment'] = '電子遊藝';
// $tr['Live video']               = '真人視訊';
// $tr['Fishing people']           = '捕魚達人';
// $tr['Promotions']               = '優惠活動';
// $tr['online service']           = '在線客服';
// $tr['About us']                 = '關於我們';
// $tr['Partner']                  = '合作夥伴';
// $tr['How to deposit']           = '如何儲值';
// $tr['How Withdrawal']           = '如何提款';
// $tr['Contact US']               = '聯絡我們';

$host_footer_html = '
    <div style="display:flex;justify-content: center;">
        <ul class="nav navbar-nav">
            <li><a href="' . $config['website_baseurl'] . 'aboutus.php">' . $tr['About us'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'promotions.php">' . $tr['Promotions'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'partner.php">' . $tr['Partner'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'howtodeposit.php">' . $tr['How to deposit'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'howtowithdraw.php">' . $tr['How Withdrawal'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'contactus.php">' . $tr['Contact US'] . '</a></li>
        </ul>
    </div>';