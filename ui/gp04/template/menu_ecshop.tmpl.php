<?php
$menu_top_item = '';

$menu_top_item = $menu_top_item . '
    <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/category&path=59" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Food'] . '</a></li>

    <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/category&path=60" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Clothing'] . '</a></li>

    <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/category&path=61" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Live'] . '</a></li>

    <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/category&path=62" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Travel'] . '</a></li>

    <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/category&path=63" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Edu'] . '</a></li>

    <li><a href="' . $config['website_baseurl'] . 'gamelobby.php" target="_self"><span class="glyphicon glyphicon-gift" aria-hidden="true"></span>' . $tr['HomeMenu Fun'] . '</a></li>
  ';

// $tr['About us'] = '關於我們';
// $tr['Promotions'] = '優惠活動';
// $tr['Partner'] = '合作夥伴';
// $tr['How to deposit'] = '如何儲值';
// $tr['How Withdrawal'] = '如何提款';
// $tr['Contact US'] = '聯絡我們';

$host_footer_html = '
    <div style="display:flex;justify-content: center;">
        <ul class="nav navbar-nav">
            <li><a href="' . $config['website_baseurl'] . 'aboutus.php">' . $tr['About us'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'promotions.php">' . $tr['Promotions'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'partner.php">' . $tr['Partner'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'howtodeposit.php">' . $tr['How to deposit'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'howtowithdraw.php">' . $tr['How Withdrawal'] . '</a></li>
            <li><a href="' . $config['website_baseurl'] . 'contactus.php">' . $tr['Contact US'] . '</a></li>
            <li><a href="javascript:void(0);" onclick="msitechg(\'mobile\'); return false;">' . $tr['Mobile Version'] . '</a></li>
        </ul>
    </div>';
