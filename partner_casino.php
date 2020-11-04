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
if($config['site_style']=='mobile'){
  $navigational_hierarchy_html =<<<HTML
    <a href="{$config['website_baseurl']}home.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
    <span>{$function_title}</span>
    <i></i>
HTML;
}
//header 內文功能列
if($config['site_style']=='mobile') {
  $header_content = '<div class="w-100 header_content"></div>';
}else{
 $header_content = '';
}
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------

$extend_head				= '
<style>

@media (min-width: 768px) {
  .panel-heading {
    display: none;
  }
  .panel {
    border: none;
    box-shadow: none;
  }
  .panel-collapse {
    height: auto;
  }
  .panel-collapse.collapse {
    display: block;
  }
}
@media (max-width: 767px) {
  .tab-content .tab-pane {
    display: block;
  }
  .nav-tabs {
    display: none;
  }
  .panel-title a {
    display: block;
  }
  .panel {
    margin: 0;
    box-shadow: none;
    border-radius: 0;
    margin-top: -2px;
  }
  .tab-pane:first-child .panel {
    border-radius: 5px 5px 0 0;
  }
  .tab-pane:last-child .panel {
    border-radius: 0 0 5px 5px;
  }
}

</style>
';
// $tr['Partner'] = '合作夥伴';
// $tr['DEMO and BBIN']= 'DEMO與BBIN進行技術合作，爲哥斯特黎加合法註冊之博彩公司。我們採用最爲多元、 先進、公正的系統，在衆多博彩網站中，我們自豪能爲會員提供最優惠的回饋、爲代理商創造強勁的營利優勢! DEMO秉持商業聯營、資源整合、利益共享的理念，與合作伙伴攜手打造利多的榮景。 無論您擁有的是網絡資源，或是豐富的人脈，都歡迎您來加入我們的行列，不須負擔任何費用，就可以開拓無上限的營收。 DEMO娛樂絕對是您最聰明的選擇';
// $tr['Agents Registration Regulations']= 一、'代理商註冊規約';
// $tr['We will strictly examine']= '爲防堵不肖業者濫用 DEMO所提供的代理優惠制度，我們將嚴格審覈每位代理商申請註冊時所提供的個人資料(包括 姓名、IP、住址、電郵信箱、電話、支付方式等等)。若經審覈發現代理商有任何不良營利企圖，或與其他代理 商、會員進行合謀套利等行爲， DEMO娛樂公司將關閉該合作代理商之賬戶、扣除賬戶中的本金，並收回該代理商 的所有佣金與優惠。 同一IP/同一姓名/同一收款賬號的會員只能是一個合作代理商的下線，代理商本身不能成爲其他代理商的下線會員';
// $tr['the terms of power']= '二、權責條款';
// $tr['DEMO rights']= 一、'DEMO對聯盟夥伴的權利與義務';
// $tr['DEMO customer service']= 'DEMO的客服部門會登記合作代理商的下線會員並觀察其投注狀況。 代理商及會員皆須同意並遵守 DEMO的會員條例、政策及操作程序。 合作代理商可隨時登入管理端接口觀察其下線會員的下注狀況與活動概況。  DEMO保留所有對合作代理商或會員之賬戶加以拒絕或凍結的權利。  DEMO有權修改合約書上之任何條例(包括:現有的佣金範圍、佣金計劃、付款程序、及參考計劃條例等 等)， DEMO公司會以電郵、網站公告等方法通知合作代理商。若代理商對於任何修改持有異議，可選擇終止合 約、或洽客服人員提出意見。如代理商未提出異議，便視作默認合約修改，必須遵守更改後的相關規定。';
// $tr['Alliance partners rights']= '二、聯盟夥伴對 DEMO的權力及義務';
// $tr['The cooperating agents should do']= '合作代理商應盡其所能，廣泛地宣傳、銷售及推廣 DEMO使代理商本身及  DEMO的利潤最大化。合作 代理商可在不違反法律的情況下，以正面形象宣傳、銷售及推廣 DEMO， 並有責任義務告知旗下會員所有關於 DEMO的相關優惠條件及產品。 合作代理商選擇推廣 DEMO的手法若需付費，則代理商應自行承擔該費用。 任何 DEMO的相關信息(包括：標誌、報表、遊戲畫面、圖樣、文案等)，合作代理商不得私自複製、公開、 分發有關材料， DEMO保留法律追訴權。 如代理商在業務推廣方面需要相關的技術支持， 歡迎隨時洽詢 DEMO客服人員。';
// $tr['The details']=三、 '各項細則';
// $tr['It is not permissible for a co-operative']= '各階層合作代理商不可在未經 DEMO娛樂允許下開設雙/多個代理賬號， 也不可從 DEMO之遊戲賬戶或其他相關人士賺取佣金。 請謹記任何代理商皆不能用代理帳戶下注， DEMO 有權終止並封存賬號及其所有在遊戲中賺取的佣金。';
// $tr['To ensure the privacy and interests']= '爲確保所有 DEMO會員的賬號隱私與權益，  DEMO不會提供任何會員密碼，或會員個人資料。 各階層合作代理商亦不得以任何方式取得會員數據，或任意登入下層會員賬號， 如發現代理商有侵害 DEMO會員隱私的行爲，  DEMO有權取消代理商之紅利，並取消該名代理商之賬號。';
// $tr['A member of a cooperating agency']= '合作代理商旗下的會員不得開設多於一個的賬戶。 DEMO有權要求會員提供有效的身份證明以驗證會員的身份， 並保留以IP判定會員是否重複註冊的權利。如違反上述事項，  DEMO有權終止玩家進行遊戲並封存賬號及所有於遊戲中賺取的佣金。';
// $tr['If a member of a co-operative agent is']= '如合作代理商旗下的會員因違反條例而被禁止使用 DEMO的遊戲， 或 DEMO退回存款給會員，  DEMO將不會分配相應的佣金給代理商。 如合作代理商旗下會員存款用的信用卡、銀行資料須經審覈， DEMO將保留相關佣金直至審覈完畢。';
// $tr['The conditions of the contract will be']= '合約條件將於 DEMO正式接受合作代理商加入後開始生效。  DEMO娛樂公司及代理商可隨時終止此合約。 在任何情況下，代理商若欲終止合約，都必須以書面/電郵方式提早於七日內通知 DEMO。 代理商的表現將會每3個月審覈一次，如代理商已不是現有的合作成員，則本合約書可以在任何時間終止。 如代理商違反合約條例， DEMO有權立即終止合約。';
// $tr['Without the permission of DEMO']= '在沒有 DEMO的許可下， 代理商不能透露及授權 DEMO的相關機密資料， 包括代理商所獲得的回饋、佣金報表、計算方式等；代理商有義務在合約終止後仍執行機密文件及數據的保密。 合約終止之後，代理商及 DEMO將不須履行雙方的權利及義務。 終止合約並不會解除代理商於終止合約前所應履行的義務。';

$registerlink='';
if (!isset($_SESSION['member']) || $_SESSION['member']->therole != 'M') {
//   $registerlink=<<<HTML
//   <li class="flex-fill"><a href="{$config['website_baseurl']}login2page.php" class="w-100">{$tr['apply register']}</a></li>
// HTML;
$registerlink= '';
}else{
  if( $protalsetting["agent_register_switch"] == 'on' ){
  $registerlink=<<<HTML
  <li class="flex-fill"><a href="{$config['website_baseurl']}register_agent.php" class="w-100">{$tr['apply register']}</a></li>
HTML;
  }
}

$showtext_html= '
  <ul class="nav nav-tabs d-flex">
    <li class="active flex-fill"><a href="#partner_id" data-toggle="tab" class="w-100">'.$tr['innovation system'].'</a></li>
    <li class="flex-fill"><a href="#franchise_id" data-toggle="tab" class="w-100">' .$tr['agent to join'] .'</a></li>
    '.$registerlink.'
  </ul>

  <div class="tab-content">
    <div class="tab-pane active" id="partner_id">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title">
            <a data-toggle="collapse" data-parent=".tab-pane" href="#collapseOne">
           创新制度
            </a>
          </h4>
        </div>
        <div id="collapseOne" class="panel-collapse collapse">
          <div class="panel-body">
<h2 id="01">行业首创 无限代返佣方式 传播快 易推广</h2>
    <p>'.$config['companyShortName'].'新推出的全新升级版代理模式堪称全网最吸利的返佣方式，无限级发展代理商让每一级代理都可以自行决定直属下级代理的占佣比，各个代理之间不分级别，所有下级业绩都统筹为自己的业绩，互不影响，佣金后台自动结算，是当前最完美的模式。当您拥有5个下级推广代理， 只要发展到6代， 每个代理都推广<b style="color:red;font-size: large;"> 5个 </b>下线，你将轻松拥有<b style="color:red;font-size: large;"> 19,530 </b>个次级代理推广部队， 坐享源源不绝的被动收入，获利将比传统代理制度等比级数飞跃成长。
    <img src="'.$cdnfullurl.'img/promotion/partner01.jpg" width="100%"></p>
    <p>
    <!-- 试算案例- 打碼佣金 -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#example01">
  试算案例- 打碼佣金
</button>

<!-- Modal -->
<div class="modal fade" id="example01" tabindex="-1" role="dialog" aria-labelledby="example01" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="example01">打碼佣金</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

      <p>只要每人推广<b style="color:red;font-size: large;">5人</b>，6级成员共19,530人，各層級按打码退佣比0.2％，分红占成15％，当天打码佣金收入为：<b style="color:red;font-size: x-large;">¥58,590</b></p>
    <p>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>分佣层级</th>
            <th>人数</th>
            <th>每人打码量</th>
            <th>累计打码量</th>
            <th>佣金比例</th>
            <th>分红占成</th>
            <th>营业分红</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope ="row">你</th>
            <td>1</td>
            <td>10,000</td>
            <td>10,000</td>
            <td></td>
            <td>15%</td>
            <td></td>
          </tr>
          <tr>
            <th scope="row">下1级</th>
            <td>5</td>
            <td>10,000</td>
            <td>50,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥15</td>
          </tr>
          <tr>
            <th scope="row">下2级</th>
            <td>25</td>
            <td>10,000</td>
            <td>250,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥75</td>
            </tr>
          <tr>
            <th scope="row">下3级</th>
            <td>125</td>
            <td>10,000</td>
            <td>1,250,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥375</td>
          </tr>
          <tr>
            <th scope="row">下4级</th>
            <td>625</td>
            <td>10,000</td>
            <td>6,250,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥1,875</td>
            </tr>
            <tr>
            <th scope="row">下5级</th>
            <td>3125</td>
            <td>10,000</td>
            <td>31,250,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥9,375</td>
            </tr>
            <tr>
            <th scope="row">下6级</th>
            <td>15,625</td>
            <td>10,000</td>
            <td>156,250,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥46,875</td>
            </tr>
            <tr>
            <th scope="row">无限...</th>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            </tr>
            <tr>
            <th scope="row">代理总计</th>
            <td>19,530</td>
            <td>10,000</td>
            <td>195,300,000</td>
            <td>0.20%</td>
            <td>15%</td>
            <td>¥58,590</td>
            </tr>
        </tbody>
      </table>
</p>



      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
    </p>
    <p>
    <!-- 试算案例- 损益佣金 -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#example02">
  试算案例- 损益佣金
</button>

<!-- Modal -->
<div class="modal fade bd-example-modal-lg" id="example02" tabindex="-1" role="dialog" aria-labelledby="example02" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="example02">损益佣金</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
            <p>只要每人推广<b style="color:red;font-size: large;">5人</b>，6级成员共19,530人，各層級按打码退佣比0.2％，分红占成15％，当天打码佣金收入为：<b style="color:red;font-size:x-large;">¥58,590</b></p>
    <p>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>分佣层级</th>
            <th>人数</th>
            <th>每人损益</th>
            <th>下级累计损益</th>
            <th>各层抽佣占成</th>
            <th>各层营业分红</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope ="row">你</th>
            <td>1</td>
            <td></td>
            <td>10,000</td>
            <td></td>
            <td></td>
          </tr>
          <tr>
            <th scope="row">下1级</th>
            <td>5</td>
            <td>100</td>
            <td>500</td>
            <td>10%</td>
            <td>¥50</td>
          </tr>
          <tr>
            <th scope="row">下2级</th>
            <td>25</td>
            <td>100</td>
            <td>2500</td>
            <td>10%</td>
            <td>¥250</td>
            </tr>
          <tr>
            <th scope="row">下3级</th>
            <td>125</td>
            <td>100</td>
            <td>12,500</td>
            <td>10%</td>
            <td>¥1,250</td>
          </tr>
          <tr>
            <th scope="row">下4级</th>
            <td>625</td>
            <td>100</td>
            <td>62,500</td>
            <td>10%</td>
            <td>6,250</td>
            </tr>
            <tr>
            <th scope="row">下5级</th>
            <td>3125</td>
            <td>100</td>
            <td>312,500</td>
            <td>10%</td>
            <td>¥31,250</td>
            </tr>
            <tr>
            <th scope="row">下6级</th>
            <td>15,625</td>
            <td>100</td>
            <td>1,562,500</td>
            <td>10%</td>
            <td>¥156,250</td>
            </tr>
            <tr>
            <th scope="row">无限...</th>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            <td>∞</td>
            </tr>
            <tr>
            <th scope="row">代理总计</th>
            <td>19,530</td>
            <td>100</td>
            <td>1,953,000</td>
            <td>10%</td>
            <td>¥195,300</td>
            </tr>
        </tbody>
      </table>
</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
 </p>
<p>
<div class="row">
<div class="col-12 col-md-6">
    <h3>会员.代理双合一介面</br>有效会员比例大增</h3>
    <p>用户可通过上级代理开立帐户即可升格代理，也可由上级代理协助入金，通过单一介面多样化的推广工具， 即可快速拓展团队成员。</p>
</div>
<div class="col-12 col-md-6">
<img src="'.$cdnfullurl.'img/promotion/partner02.jpg" width="100%">
</div>
</div>
</p>
<p>
<div class="row">
<div class="col-12 col-md-6">
<img src="'.$cdnfullurl.'img/promotion/partner03.jpg" width="100%">
</div>
<div class="col-12 col-md-6">
<h3>双重收益 无论盈亏或流水 都有稳定高收入</h3>
<p>'.$config['companyShortName'].'和其他现金网单一的反水方式不同， '.$config['companyShortName'].'推出的全新升级版双模式代理，无论盈亏或流水，前后台同步自动结算，自动发放佣金，让您在无压力下真正享受到丰厚的回报</p>
</div>
</div>
</p>
<p>
<div class="row">
<div class="col-12 col-md-7">
    <h3>日结佣金 每天准时出佣</h3>
    <p>以往佣金结算时间主要是按月计算，而'.$config['companyShortName'].'无限代理模式以当日12:00至次日11:59(北京时间)进行结算，佣金结算完成会自动转到您的会员账户里面，取款快速金额不封顶 。  </p>
    <h3>支持代理商協助入金和出款</h3>
    <p>'.$config['companyShortName'].'无限代理系统，支持<b>代理商转帐功能</b>，您也可以通过线下交收场景转帐至下级会员或代理，透过人脉推广，更快速拓展您的事业版图。</p>
    </div>
<div class="col-12 col-md-5">
<img src="'.$cdnfullurl.'img/promotion/partner04.jpg" width="100%">
</div>
</div>
</p>
    <h3>零成本 低门槛 高回报</h3>
    <p>即日起成为'.$config['companyShortName'].'代理商无需负担任何费用，更不用担心线下盈亏，就可以开始<b>无上限</b>的收入，无论您拥有的是网络资源，或是人脉资源，随时随地都可以轻松赚大钱。 </p>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane" id="franchise_id">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title">
            <a data-toggle="collapse" data-parent=".tab-pane" href="#collapseTwo">
            代理加盟
            </a>
          </h4>
        </div>
        <div id="collapseTwo" class="panel-collapse collapse">
          <div class="panel-body">
     <h2 id="01">'.$tr['partner casino agent title'].'</h2>
    <h3 font-weight-bold>'.$tr['partner casino agent article1'].'</h3>
    <p>'.$tr['partner casino agent article1 content1'].'</p>
    <p>'.$tr['partner casino agent article1 content2'].'</p>
    <p>'.$tr['partner casino agent article1 content3'].'</p>
    <p>'.$tr['partner casino agent article1 content4'].'</p>
    <h3>'.$tr['partner casino agent article2'].'</h3>
    <p>'.$tr['partner casino agent article2 content1'].'</p>
    <p>'.$tr['partner casino agent article2 content2'].'</p>
    <p>'.$tr['partner casino agent article2 content3'].'</p>
    <p>'.$tr['partner casino agent article2 content4'].'</p>
    <p>'.$tr['partner casino agent article2 content5'].'</p>
    <p>'.$tr['partner casino agent article2 content6'].'</p>
    <p>'.$tr['partner casino agent article2 content7'].'</p>
    <p>'.$tr['partner casino agent article2 content8'].'</p></br>
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th colspan="5" style="color:red">'.$tr['partner casino agent table1 title'].'</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th style="text-align:center">'.$tr['partner casino agent table1 1-1'].'</th>
          <th style="text-align:center">'.$tr['partner casino agent table1 1-2'].'</th>
          <th style="text-align:center">'.$tr['partner casino agent table1 1-3'].'</th>
          <th style="text-align:center">'.$tr['partner casino agent table1 1-4'].'</th>
          <th style="text-align:center">'.$tr['partner casino agent table1 1-5'].'</th>
        </tr>
        <tr>
          <td style="text-align:center">1~300000</td>
          <td style="text-align:center">3或以上</td>
          <td style="text-align:center">0.12%</td>
          <td style="text-align:center">300,000</td>
          <td style="text-align:center">360</td>
        </tr>
        <tr>
          <td style="text-align:center">300001~1000000</td>
          <td style="text-align:center">5或以上</td>
          <td style="text-align:center">0.13%</td>
          <td style="text-align:center">1,000,000</td>
          <td style="text-align:center">1,300</td>
        </tr>
        <tr>
          <td style="text-align:center">1000001~3000000</td>
          <td style="text-align:center">8或以上</td>
          <td style="text-align:center">0.15%</td>
          <td style="text-align:center">3,000,000</td>
          <td style="text-align:center">4,500</td>
        </tr>
        <tr>
          <td style="text-align:center">3000001~5000000</td>
          <td style="text-align:center">10或以上</td>
          <td style="text-align:center">0.18%</td>
          <td style="text-align:center">5,000,000</td>
          <td style="text-align:center">9,000</td>
        </tr>
        <tr>
          <td style="text-align:center">5000001以上</td>
          <td style="text-align:center">15或以上</td>
          <td style="text-align:center">0.20%</td>
          <td style="text-align:center">10,000,000</td>
          <td style="text-align:center">20,000</td>
        </tr>
      </tbody>
    </table>
    </div>
    <div class="table-responsive">
    <table class="table table-bordered  table-striped">
      <thead>
        <tr>
          <th colspan="5" style="color:red">'.$tr['partner casino agent table2 title'].'</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th style="text-align:center" rowspan="2">'.$tr['partner casino agent table2 1-1'].'</th>
          <th style="text-align:center" rowspan="2">'.$tr['partner casino agent table2 1-2'].'</th>
          <th colspan="3" style="text-align:center">'.$tr['partner casino agent table2 1-3'].'</th>
        </tr>
      <tr>
        <th style="text-align:center">'.$tr['partner casino agent table2 2-1'].'</td>
        <th style="text-align:center">'.$tr['partner casino agent table2 2-2'].'</td>
        <th style="text-align:center">'.$tr['partner casino agent table2 2-3'].'</td>
      </tr>
        <tr>
          <td style="text-align:center">1~50000</td>
          <td style="text-align:center">5或以上</td>
          <td style="text-align:center">30%</td>
          <td style="text-align:center">0.10%</td>
          <td style="text-align:center">10%</td>
        </tr>
        <tr>
          <td style="text-align:center">50001~300000</td>
          <td style="text-align:center">10或以上</td>
          <td style="text-align:center">35%</td>
          <td style="text-align:center">0.10%</td>
          <td style="text-align:center">10%</td>
        </tr>
        <tr>
          <td style="text-align:center">300001~800000</td>
          <td style="text-align:center">50或以上</td>
          <td style="text-align:center">40%</td>
          <td style="text-align:center">0.10%</td>
          <td style="text-align:center">10%</td>
        </tr>
        <tr>
          <td style="text-align:center">800001~1000000</td>
          <td style="text-align:center">100或以上</td>
          <td style="text-align:center">45%</td>
          <td style="text-align:center">0.10%</td>
          <td style="text-align:center">10%</td>
        </tr>
        <tr>
          <td style="text-align:center">1200001以上</td>
          <td style="text-align:center">200或以上</td>
          <td style="text-align:center">50%</td>
          <td style="text-align:center">0.10%</td>
          <td style="text-align:center">10%</td>
        </tr>
      </tbody>
    </table>
    </div>
    <p>'.$tr['partner casino agent article2 content9'].'</p>
    <p>'.$tr['partner casino agent article2 content10'].'</p></br>

    <p>'.$tr['partner casino agent article2 content11'].'</p>
    <p style="color:blue">'.$tr['partner casino agent article2 content12'].'</p>
    <p>'.$tr['partner casino agent article2 content13'].'</p>
    <p>'.$tr['partner casino agent article2 content14'].'</p></br>
    <h3 font-weight-bold>'.$tr['partner casino agent article3'].'</h3>
    <p>'.$tr['partner casino agent article3 content1'].'</p>
    <p>'.$tr['partner casino agent article3 content2'].'</p>
    <p>'.$tr['partner casino agent article3 content3'].'</p>
    <p>'.$tr['partner casino agent article3 content4'].'</p>
    <p>'.$tr['partner casino agent article3 content5'].'</p></br>
    <h3 font-weight-bold>'.$tr['partner casino agent article4'].'</h3>
    <p>'.$tr['partner casino agent article4 content1'].'</p>
    <p>'.$tr['partner casino agent article4 content2'].'</p></br>
          </div>
        </div>
      </div>
    </div>
  </div>
';

// $showtext_html = '
// <h2 id="01">'.$tr['Partner'].'</h2>
// <p>'.$tr['DEMO and BBIN'].'!</p></br>
// <h3>一、'.$tr['Agents Registration Regulations'].'</h3>
// <p>'.$tr['We will strictly examine'].'。</p></br>
// <h3>'.$tr['the terms of power'].'</h3>
// <h4>一、 '.$tr['DEMO rights'].'</h4>
// <p> '.$tr['DEMO customer service'].'</p></br>
// <h4>二、'.$tr['Alliance partners rights'].'</h4>
// <p>'.$tr['The cooperating agents should do'].'</p></br>
// <h4>三、'.$tr['The details'].'</h4>
// <p>'.$tr['It is not permissible for a co-operative'].'</p>
// <p>'.$tr['To ensure the privacy and interests'].'</p>
// <p>'.$tr['A member of a cooperating agency'].'</p>
// <p>'.$tr['If a member of a co-operative agent is'].'</p>
// <p>'.$tr['The conditions of the contract will be'].'</p>
// <p>'.$tr['Without the permission of DEMO'].'</p></br>
// ';


// 內容填入整理
// 切成 3 欄版面
$indexbody_content = $indexbody_content.$header_content.'
<div class="row">
	<div class="col parner">
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
// 側邊欄MENU var[0]側邊欄組合(lib_menu) var[1]目前頁籤名
$tmpl['sidebar_content'] = ['static','partner'];
// banner標題
$tmpl['banner'] = ['Partner'];
// menu增加active
$tmpl['menu_active'] =['contactus.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/static.tmpl.php");
?>