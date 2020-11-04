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
//menu_ecshop.tmpl.php
// $tr['about chungwang']      = '關於群旺';
// $tr['About us']             = '關於我們';
// $tr['Franchisee']           = '加盟聯營';
// $tr['How to deposit']       = '如何儲值';
// $tr['How Withdrawal']       = '如何提款';
// $tr['Promotions']           = '優惠活動';
// $tr['Shopping guide']       = '購物指南';
// $tr['Brand Zone']           = '品牌專區';
// $tr['bargain']              = '特价商品';
// $tr['Delivery information'] = '配送資訊';
// $tr['privacy_policy']       = '隱私權政策';
// $tr['Terms']                = '條款';
// $tr['Shopping records']     = '購物紀錄';
// $tr['History orders']       = '歷史訂單';
// $tr['Favorites list']       = '收藏列表';
// $tr['Returned goods']       = '商品退換';
// $tr['Member Centre']        = '會員中心';
// $tr['Station letters']      = '站內信件';
// $tr['Online service']       = '線上客服';
  $host_footer_html = '
    <div class="row">
            <div class="col-sm-3">
        <h5>'.$tr['about chungwang'].'</h5>
        <ul class="list-unstyled">
                   <li><a href="'.$config['website_baseurl'].'aboutus.php">'.$tr['About us'].'</a></li>
                    <li><a href="'.$config['website_baseurl'].'partner.php">'.$tr['Franchisee'].'</a></li>
                    <li><a href="'.$config['website_baseurl'].'howtodeposit.php">'.$tr['How to deposit'].'</a></li>
                    <li><a href="'.$config['website_baseurl'].'howtowithdraw.php">'.$tr['How Withdrawal'].'</a></li> 
                    <li><a href="'.$config['website_baseurl'].'promotions.php">'.$tr['Promotions'].'</a></li>
                  </ul>
      </div>
            <div class="col-sm-3">
        <h5>'.$tr['Shopping guide'].'</h5>
        <ul class="list-unstyled">
			
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/manufacturer" target="_self">'.$tr['Brand Zone'].'</a></li>
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=product/special" target="_self">'.$tr['bargain'].'</a></li>
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=information/information&information_id=6" target="_self">'.$tr['Delivery information'].'</a></li>
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=information/information&information_id=3" target="_self">'.$tr['privacy_policy'].'</a></li>
            <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=information/information&information_id=5" target="_self">'.$tr['Terms'].'</a></li>        
          
          
        </ul>
      </div>
      <div class="col-sm-3">
        <h5>'.$tr['Shopping records'].'</h5>
        <ul class="list-unstyled">
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=account/order">'.$tr['History orders'].'</a></li>
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=account/wishlist">'.$tr['Favorites list'].'</a></li>
          <li><a href="' . $config['ec_protocol'] . '://' . $config['ec_host'] . '/index.php?route=account/return/add">'.$tr['Returned goods'].'</a></li>
        </ul>
      </div>
      <div class="col-sm-3">
        <h5>'.$tr['Member Centre'].'</h5>
        <ul class="list-unstyled">
          <li><a href="'.$config['website_baseurl'].'member.php">'.$tr['Member Centre'].'</a></li>
          <li><a href="'.$config['website_baseurl'].'stationmail.php">'.$tr['Station letters'].'</a></li>
          <li><a href="#">'.$tr['Online service'].'</a></li>
                  </ul>
      </div>
    </div>  <hr>';