<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 合作伙伴
// File Name:	partner.php
// Author:		Barkley
// Related:
// Log:
// 2016.10.20
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
//合作伙伴
$function_title 		= $tr['partner'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// 初始化變數 end
// ----------------------------------------------------------------------------
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------

$extend_head				= '
<link rel="stylesheet" href="'.$cdnfullurl.'css/style_partner.css">
';
// 狀態列顯示
//联营协议
//代理註冊
//代理加盟
$partner_step_html = '
<nav id="00">
  <ul>
    <li>
      <a href="#01">'.$tr['venture agreement'].'</a>
    </li>
    <li>
      <a href="#02">'.$tr['Joining process'].'</a>
    </li>
    <li>
      <a href="#03">'.$tr['bonus system'] .'</a>
    </li>
	    <li>
      <a href="#04">'.$tr['Q&A'].'</a>
    </li>
    <li>
      <a href="#05">'.$tr['proxy registration'].'</a>
    </li>
  </ul>
</nav>
';

//联营协议
// $tr['Joining process']='加盟流程';
// $tr['Affiliate affiliate bonus system']='加盟聯營分紅制度';
// $tr['Affiliate affiliate bonus system']='加盟聯營分紅制度';
// $tr['Introduction to dividend system']='分紅制度簡介';
// $tr['Join shareholders high dividend'] = '加盟股東高額分紅，4種獎勵方式，投入一萬讓您輕鬆<span class="text-red"> 月入百萬 </span>，零風險，高回報，信譽保證';
// $tr['Dividends dividend irregular:']='股利分紅/不定期：';
// $tr['Occasional distribution'] = '不定期發放，年度結算公司淨利(加盟金+損益)按股份配發';
// $tr['Profit sharing monthly'] = '營利分紅/每月:';
// $tr['Each monthtotal consumption'] = '每人每月商城消費總額達40,000以上，最高享有四級成員消費總額2%現金回饋。按下層一至四級30%,10%,10%,10%分潤。';
// $tr['Business dividend weekly'] = '營業分紅/每週:';
// $tr['Per person per week'] = '每人每週打碼量達10,000以上，回饋四級成員總打碼量<span class="text-red"> 2% </span>利潤。按下層一至四級30%,10%,10%,10%分潤。';
// $tr['Commission bonus'] = '佣金分紅/一次領(週結):';
// $tr['Investment 1w shareholder'] = '投資一萬元立即成為加盟股東,享有下層一至四級30%,10%,10%,10%, 4級分潤。';
// $tr['340 members at 4 levels'] = '每人推薦4人，四級成員共340人(人數固定)，您的佣金分紅總收入將高達<span class="text-red"> $348,000 </span>';
// $tr['Refer member  affiliate partner'] = '推薦會員成為加盟聯營股東，您將立即享有<span class="text-red"> 30% </span>的佣金。';
// $tr['One to four members'] = '一至四層成員只要有人再推薦，您輕輕鬆鬆獲得<span class="text-red"> 10% </span>佣金。';

$showtext_html = '
<h2 id="01">
'.$tr['venture agreement'].'
</h2>
<p>'.$tr['venture agreement content 1'].'</p>
<h3>'.$tr['venture agreement content 2'].'</h3>
<p>'.$tr['venture agreement content 3'].'</p>
<h3>'.$tr['venture agreement content 4'].'</h3>
<p>'.$tr['venture agreement content 5'].'</p>

<h4>'.$tr['agent registration protocol'] .'</h4>
<ol>
  <li>'.$tr['agent registration protocol content 1'].'</li>
  <li>'.$tr['agent registration protocol content 2'].'</li>
</ol>
<h4>'.$tr['the terms of power'].'</h4>
<ol>
  <li>'.$tr['the terms of power content 1'].'</li>
  <li>'.$tr['the terms of power content 2'].'</li>
  <li>'.$tr['the terms of power content 3'].'</li>
  <li>'.$tr['the terms of power content 4'].'</li>
  <li>'.$tr['the terms of power content 5'].'</li>
  <li>'.$tr['the terms of power content 6'].'</li>
  <li style="color:red;">'.$tr['the terms of power content 7'].'</li>
</ol>

<p class="backtop">
  <a href="#00"><span class="glyphicon glyphicon-chevron-up"></span></a>
</p>

<h2 id="02">'.$tr['Joining process'].'</h2>
<p><img src="'.$cdnfullurl.'img/promotion/promo-01.png" width="100%"></p>
<p class="backtop">
  <a href="#00"><span class="glyphicon glyphicon-chevron-up"></span></a>
</p>
<h2 id="03">'.$tr['Affiliate affiliate bonus system'].'</h2>
<p><img src="'.$cdnfullurl.'img/promotion/promo-02.png" width="100%"></p>
<h3>'.$tr['Introduction to dividend system'].'</h3>
<div class="row">
  <div class="col-xs-5 col-md-5">
    <img src="'.$cdnfullurl.'img/promotion/promo-2.jpg" width="90%" />
    <p class="tag">'.$tr['Join shareholders high dividend'].'</p>
  </div>
  <div class="col-xs-7 col-md-7">
    <h4>'.$tr['Dividends dividend irregular:'].'</h4>'.$tr['Occasional distribution'].'
    <h4>'.$tr['Profit sharing monthly'].'</h4>'.$tr['Each monthtotal consumption'].'
    <h4>'.$tr['Business dividend weekly'].'</h4>'.$tr['Per person per week'].'
    <h4>'.$tr['Commission bonus'].'</h4>'.$tr['Investment 1w shareholder'].'
  </div>
</div>

<h3>'.$tr['Commission bonus'].'</h3>
<h4>'.$tr['340 members at 4 levels'].'</h4>
<ol>
  <li>'.$tr['Refer member  affiliate partner'].'</li>
  <li>'.$tr['One to four members'].'
    <p>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>'.$tr['Bonus level'].'</th>
            <th>'.$tr['Number of people'].'</th>
            <th>'.$tr['Initial Franchise Fee'].'</th>
            <th>'.$tr['Dividend ratio'].'</th>
            <th>'.$tr['You get a commission bonus on all levels'].'</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row">'.$tr['Lower first level'].'</th>
            <td>4人</td>
            <td>10,000</td>
            <td>30%</td>
            <td>12,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower second level'].'</th>
            <td>4X4=16人</td>
            <td>10,000</td>
            <td>10%</td>
            <td>16,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower third level'].'</th>
            <td>16X4=64人</td>
            <td>10,000</td>
            <td>10%</td>
            <td>64,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower fourth level'].'</th>
            <td>64X4=256人</td>
            <td>10,000</td>
            <td>10%</td>
            <td>256,000</td>
          </tr>
          <tr>
            <td colspan="5"><span class="text-left text-red ">'.$tr['Commission dividends total revenue'].'</span><span class="text-right text-red ">$348,000</span></td>
          </tr>
        </tbody>
      </table>
    </p>
  </li>
</ol>


<h3>'.$tr['Business dividend weekly'].'</h3>
<h4>'.$tr['your weekly business dividends'].'<span class="text-red"> $28,240 </span></h4>
<ol>
  <li>'.$tr['Per person per week to play more than 10,000 code'].'</li>
  <li>'.$tr['2% Profit Dividends are distributed'].'
    <p>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>'.$tr['Bonus level'].'</th>
            <th>'.$tr['Number of people'].'</th>
            <th>'.$tr['The amount of code per person'].'</th>
            <th>'.$tr['operating profit'].'</th>
            <th>'.$tr['Dividend ratio'].'</th>
            <th>'.$tr['You get dividends week'].'</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope ="row">'.$tr['Lower first level'].'</th>
            <td>4人</td>
            <td>10,000</td>
            <td>2%</td>
            <td>30%</td>
            <td>12,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower second level'].'</th>
            <td>4X4=16人</td>
            <td>10,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>16,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower third level'].'</th>
            <td>16X4=64人</td>
            <td>10,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>64,000</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower fourth level'].'</th>
            <td>64X4=256人</td>
            <td>10,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>256,000</td>
          </tr>
          <tr>
            <td colspan="6"><span class="text-left text-red">'.$tr['Weekly business dividends total revenue'].'<br>('.$tr['Monthly dividend income'].'</span><span class="text-right text-red">$7,060/'.$tr['week'].'<br> $28,240/'.$tr['month'].')</span></td>
          </tr>
        </tbody>
      </table>
    </p>
  </li>
</ol>


<h3>'.$tr['Profit sharing monthly'].'</h3>
<h4>'.$tr['total monthly profit-sharing profit'].'<span class="text-red"> $27,840 </span></h4>
<ol>
  <li>'.$tr['mall total spending more than'].'</span></li>
  <li>'.$tr['cash repayment'].'
    <p>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>'.$tr['Bonus level'].'</th>
            <th>'.$tr['Number of people'].'</th>
            <th>'.$tr['Total consumption of each mall'].'</th>
            <th>'.$tr['Cash back'].'</th>
            <th>'.$tr['Dividend ratio'].'</th>
            <th>'.$tr['You get profit distribution for each tier'].'/'.$tr['month'].'</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row">'.$tr['Lower first level'].'</th>
            <td>4人</td>
            <td>40,000</td>
            <td>2%</td>
            <td>30%</td>
            <td>960</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower second level'].'</th>
            <td>4X4=16人</td>
            <td>40,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>1280</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower third level'].'</th>
            <td>16X4=64人</td>
            <td>40,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>5120</td>
          </tr>
          <tr>
            <th scope="row">'.$tr['Lower fourth level'].'</th>
            <td>64X4=256人</td>
            <td>40,000</td>
            <td>2%</td>
            <td>10%</td>
            <td>20480</td>
          </tr>
          <tr>
            <td colspan="6"><span class="text-left text-red">'.$tr['Monthly profit-sharing revenue'].'</span><span class="text-right text-red">$27,840</span></td>
          </tr>
        </tbody>
      </table>
    </p>
  </li>
</ol>


<h3>'.$tr['Dividend distribution'].'
<h3>'.$tr['Total monthly dividend income'].'</h3>
<h4>'.$tr['Each recommended 4 people'].'</span></h4>
<div class="row">
  <div class="col-xs-5 col-md-5">
    <img src="'.$cdnfullurl.'img/promotion/promo-05.png" width="100%">
  </div>
  <div class="col-xs-7 col-md-7">
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>'.$tr['Dividend items'].'</th>
          <th>'.$tr['Total monthly dividend income'].'('.$tr['Four members of a total of 340 people'].')</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th scope="row">'.$tr['Commission bonus (a collar / week knot)'].'</th>
          <td>$348,000</td>
        </tr>
        <tr>
          <th scope="row">'.$tr['Business dividend (week ending)'].'</th>
          <td>$28,240</td>
        </tr>
        <tr>
          <th scope="row">'.$tr['Profit-sharing (monthly)'].'</th>
          <td>$27,840</td>
        </tr>
        <tr>
          <th scope="row">'.$tr['dividend bonus'].'</th>
          <td>'.$tr['From time to time'].'</td>
        </tr>
        <tr>
          <td colspan="2"><span class="text-left text-red">'.$tr['Total monthly dividend income'].'</span><span class="text-right text-red">$404,080</span></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>


<p class="backtop">
  <a href="#00"><span class="glyphicon glyphicon-chevron-up"></span></a>
</p>
<h2 id="04">'.$tr['Q&A'].'</h2>
<p>
  <a class="btn btn-info" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">'.$tr['Q1'].'</a>
  <div class="collapse" id="collapseExample">
    <div class="card card-block">
      '.$tr['Q1A'].'
    </div>
  </div>
</p>
<p>
  <a class="btn btn-info" data-toggle="collapse" href="#collapseExample2" aria-expanded="false" aria-controls="collapseExample">
    '.$tr['Q2'].'
  </a>
  <div class="collapse" id="collapseExample2">
    <div class="card card-block">
      '.$tr['Q2A'].'
    </div>
  </div>
</p>
<p>
  <a class="btn btn-info" data-toggle="collapse" href="#collapseExample3" aria-expanded="false" aria-controls="collapseExample">'.$tr['Q3'].'</a>
  <div class="collapse" id="collapseExample3">
    <div class="card card-block">'.$tr['Q3A'].'</div>
  </div>
</p>
<p>
  <a class="btn btn-info" data-toggle="collapse" href="#collapseExample4" aria-expanded="false" aria-controls="collapseExample">'.$tr['Q4'].'</a>
  <div class="collapse" id="collapseExample4">
    <div class="card card-block">
      '.$tr['Q4A'].'
      <ol>
        <li>'.$tr['Commission bonus (a collar / week knot)'].'</li>
        <li>'.$tr['Business dividend (week ending)'].'</li>
        <li>'.$tr['Profit-sharing (monthly)'].'</li>
        <li>'.$tr['Dividends dividend (irregular)'].'</li>
      </ol>
      '.$tr['Q4A2'].'
    </div>
  </div>
</p>
<p>
  <a class="btn btn-info" data-toggle="collapse" href="#collapseExample5" aria-expanded="false" aria-controls="collapseExample">'.$tr['Q5'].'</a>
  <div class="collapse" id="collapseExample5">
    <div class="card card-block">'.$tr['Q5A'].'</div>
  </div>
</p>
<p class="backtop">
  <a href="#00"><span class="glyphicon glyphicon-chevron-up"></span></a>
</p>

';


// 註冊代理
$showtext_html = $showtext_html.'<p id="register_agent_tag"></p>';

// 會員才顯示 , 非會員則提示先註冊
if(isset($_SESSION['member']) AND $_SESSION['member']->therole != 'T' ) {
  //我同意以上條款，註冊申請成為代理商。
	$showtext_html = $showtext_html.'<p id="05" align="center"><a href="register_agent.php" target="_BLANK" class="btn btn-primary btn-lg btn-block" role="button">'.$tr['agree and apply to agent'].'</a></p>';
}else{
  //你需要先註冊成為會員，才可以申請成為代理商。
	$showtext_html = $showtext_html.'<p id="05" align="center"><a href="register.php" target="_BLANK" class="btn btn-warning btn-lg btn-block" role="button">'.$tr['register first to apply agent'].'</a></p>';
}
$showtext_html = $showtext_html.'';

// 不論身份都可以觀看。


// 內容填入整理
// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
<div class="row">
	<div class="col-md-10 offset-md-1">
	'.$partner_step_html.'
	</div>
</div>
<div class="row">
	<div class="col-md-10 offset-md-1">
'.$showtext_html.'
	</div>
</div>
<div class="row">
	<div class="col-md-10 offset-md-1">
		<div id="preview"></div>
	</div>
</div>
';


// ----------------------------------------------------------------------------
// MAIN  END
// ----------------------------------------------------------------------------



// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] 		= $config['companyShortName'];
$tmpl['html_meta_author']	 				= $config['companyShortName'];
$tmpl['html_meta_title'] 					= $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message']									= $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head']							= $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js']								= $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] 			= $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content']				= $indexbody_content;


// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");
?>