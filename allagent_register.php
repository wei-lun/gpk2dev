<?php
// ----------------------------------------------------------------------------
// Features:	前端 -- 申請全民代理
// File Name:	agent_guide.php
// Author:		orange
// Related:
// Log:
// 2019.04.30
// ----------------------------------------------------------------------------



// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_allagent_register'];
// 擴充 head 內的 css or js
$extend_head				= '';
// 放在結尾的 js
$extend_js					= '';
// body 內的主要內容
$indexbody_content	= '';
// 系統訊息選單
$messages 					= '';
// ----------------------------------------------------------------------------
// 初始化變數 end
// ----------------------------------------------------------------------------
//未登入跳轉至登入頁
if (!isset($_SESSION['member'])) {
	die('<script>document.location.href="./login2page.php";</script>');
}

//非代理商才能進入
if (!isset($_SESSION['member']) || $_SESSION['member']->therole == 'T'|| $_SESSION['member']->therole == 'A' || $_SESSION['member']->therole == 'R') {
	die('<script>document.location.href="./home.php";</script>');
  }
  //全民代理關閉時跳轉至一般代理申請
if(!isset($protalsetting['national_agent_isopen']) || $protalsetting['national_agent_isopen'] != 'on'){
  die('<script>document.location.href="./register_agent.php";</script>');
}
  
// 導覽列
$navigational_hierarchy_html = '
<ul class="breadcrumb">
  <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
  <li class="active">'.$function_title.'</li>
</ul>
';
if($config['site_style']=='mobile'){
	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}menu_admin.php?gid=agent"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}
// ----------------------------------------------------------------------------



// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------
if($config['website_type'] == 'ecshop'){
	
	// 網站名稱
	$company_name = '群旺國際世界名牌商城';
	$showtext_html = '<h2>'.$tr['About us'].'</h2>';

	// $tr['Spend less and buy better'] = '花得更少，買得更好';
	// $tr['Qunwang International World Famous Mall'] = '群旺國際世界名牌商城是亞洲地區首屈一指的電商平台，在東南亞六國皆有佈局（新加坡、馬來西亞、泰國、印尼、越南、菲律賓），簡易方便的操作介面讓你隨時隨地都能輕鬆購物！ 群旺國際世界名牌商城 擁有完整的金流、物流服務，提供安全的線上購物環境，更有蝦皮承諾保障你的交易安全，啟動第三方支付託管交易款項，無須擔心收不到訂購的商品、或是拿不到退還的金額。商品評價和評論透明呈現在你眼前，你可以快速挑選出商品受歡迎、並提供良好服務、得到買家一致推薦的賣家。現在就來加入 群旺國際世界名牌商城 ，享受最獨一無二的網路購物體驗！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Spend less and buy better'].'</h3>
	<p>'.$tr['Qunwang International World Famous Mall'].'</p>';

	// $tr['One-stop boutique mall'] = '一站式精品商城';
	// $tr['You can meet the needs of your boutique purchase'] = '在群旺國際世界名牌商城購物，你能夠滿足你精品採購的需求。從每日「限時特賣」專區，你可以找到各式好康下殺品；「每日新發現」貼心的相關商品推薦為你整理出更多好選擇。群旺國際世界名牌商城網羅國內外各大知名大牌進駐，所有商品均享有100%正品的保證，種類繁多、品牌齊全，包括3M、鍋寶、NIVEA、acer、樹德收納、MOTHER-K等，別忘了還有蝦幣回饋，只要在優選賣家賣場和蝦皮商城消費即可獲得蝦幣累積和進行折抵，買越多賺越多，線上購物從未這麼簡單！ 不知道要買什麼？快來「熱門搜尋」一覽時下最夯熱門話題商品，瞧瞧大家都在瘋什麼！或是直接瀏覽美妝保健、女生衣著、女生配件、女鞋、女生包包、嬰幼童與母親、男生衣著、男生包包與配件、男鞋、寵物、美食伴手禮、娛樂收藏、遊戲王、居家生活、手機平板與周邊、3C、家電影音、戶外運動用品、汽機車零件百貨、服務票券、代買代購和其他類別，強大的搜尋功幫助你找尋心中好物，趕快加入 群旺國際世界名牌商城 挖掘最新熱門好貨！';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['One-stop boutique mall'].'</h3>
	<p>'.$tr['You can meet the needs of your boutique purchase'].'</p>';

	// $tr['Meet your food, clothing, shelter, transportation, education, music'] = '滿足你的食、衣、住、行、育、樂';
	// $tr['QunWant International World Brand Mall was established in 2007 to diversified boutique'] = '群旺國際世界名牌商城成立於2007年，以多元精品、娛樂產業起家。在2017年轉進娛樂精品市場，透過優異的網路核心技術提供玩家休閒遊戲、精品購物為核心理念，打造亞洲第一的綜合網路精品商城服務。群旺國際世界名牌商城一路不斷精進，希望能成為買家、玩家正面能量的補充管道，隨時透過精品商城，讓購物滿足內的快感；心情不好時，透過娛樂的爽快讓您再次微笑、煩惱減半。我們更希望能成為玩家真誠相待的長久朋友。';
	$showtext_html = $showtext_html.'
	<h3>'.$tr['Meet your food, clothing, shelter, transportation, education, music'].'</h3>
	<p>'.$tr['QunWant International World Brand Mall was established in 2007 to diversified boutique'].'</p>';
}else{
	if(isset($ui_data['copy']['aboutus'][$_SESSION['lang']])&&$ui_data['copy']['aboutus'][$_SESSION['lang']] !="" ){
		$showtext_html = $ui_data['copy']['aboutus'][$_SESSION['lang']];
	}
	else{
		$showtext_html =<<<HTML
HTML;
  }
		$cooperationAgreementHtml =<<<HTML
		<div class="row my-3">        
			<div class="col-12">
					<img class="w-100" src="{$cdnfullurl_js}img/promotion/allagent_promote.png" alt="">
			</div>
    </div>
    <div class="row">
      <div class="col-12">
        <div class="application_p">{$tr['register agent step-enter password']}</div>
          <form>
            <div class="form-group application_input">
                <div class="row">                 
                <div class="col-4"><i class="fa fa-asterisk mr-1"></i>{$tr['member password']}</div>
                <div class="col-8">
                  <input type="password" class="form-control" id="password" placeholder="{$tr['please enter password']}">
                </div>
                </div>
            </div>
          </form>
      </div>
    </div>
    <button type="button" class="send_btn btn-block" id="registerAgentBtn">{$tr['register agent title']}</button>
    <div class="row">        
			<div class="col-12">
			<div class="checkbox register_checkbox text-center">
				<label>
				<input type="checkbox" class="user_check" id="agreementAgree" checked>
				{$tr['i agreed below']}
				<button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#cooperationAgreementModal">{$tr['cooperation registration']}</button>
				</label>
			</div>
			</div>
    </div>
HTML;
//合作协议
  if(isset($ui_data['copy']['partner'][$_SESSION['lang']]) && $ui_data['copy']['partner'][$_SESSION['lang']] != '') {
      $cooperationAgreementContent = htmlspecialchars_decode($ui_data['copy']['partner'][$_SESSION['lang']]);
  }else{
      $cooperationAgreementContent='
        <h2 id="01"> 合作协议</h2>
        <p> 本站与GPK进行技术合作，为柬埔寨合法注册之博采公司。我们采用最为多元、先进、公正的系统，在众多博彩网站中， 我们自豪能为会员提供最优惠的回馈、为代理商创造强劲的营利优势！本站秉持商业联营、资源整合、利益共享的理念， 与合作伙伴携手打造利多的荣景。无论您拥有的是网络资源，或是丰富的人脉，都欢迎您来加入我们的行列，不须负担任何费用， 就可以开拓无上限的营收。绝对是您最聪明的选择！
        </p>
        <h3>一、代理商注册规约</h3>
        <p>为防堵不肖业者滥用本站所提供的代理优惠制度，我们将严格审核每位代理商申请注册时所提供的个人资料（包括：姓名、IP、住址、电邮信箱、电话、支付方式等等）。 </p>
        <p>若经审核发现代理商有任何不良营利企图，或与其他代理商、会员进行合谋套利等行为，本站将关闭该合作代理商之账户、 扣除账户中的本金，并收回该代理商的所有佣金与优惠。同一IP/同一姓名/同一收款账号的会员只能是一个合作代理商的下线， 代理商本身不能成为其他代理商的下线会员。</p>

        <h3>二、权责条款</h3>

        <h4>一、本站对联盟伙伴的权利与义务</h4>
        <p>本站的客服部门会登记与观察合作代理商以及下线会员的投注状况。代理商及会员皆须同意并遵守本站的会员条例、政策及操作程序。 合作代理商可随时登入观察其下线会员的下注状况与活动概况。 本站保留对所有对合作代理商或会员之账户加以拒绝或冻结的权利。 本站有权修改合约书上之任何条例（包括：现有的佣金范围、佣金计划、付款程序、及参考计划条例等等）， 本站会以电邮、网站公告等方法通知合作代理商。若代理商对于任何修改持有异议，可选择终止合约、或洽客服人员提出意见。</p>
        <p>如代理商未提出异议，便视作默认合约修改，必须遵守更改后的相关规定。</p>
        <h4>二、联盟伙伴对本站的权力及义务</h4>
        <p>合作代理商应尽其所能，广泛地宣传、销售及推广本站使代理商本身及本站的利润最大化。合作代理商可在不违反法律的情况下， 以正面形象宣传、销售及推广本站，并有责任义务告知旗下会员所有关于本站的相关优惠条件及产品。 </p>
        <p>合作代理商选择推广本站的手法若需付费，则代理商应自行承担该费用。任何本站的相关信息（包括：标志、报表、游戏画面、图样、文案等），合作代理商不得私自复制、公开、分发有关材料，本站保留法律追诉权。如代理商在业务推广方面需要相关的技术支持， 欢迎随时洽询本站客服人员。</p>
        <h4>三、各项细则</h4>
        <p>各阶层合作代理商不可在未经本站允许下开设双/多个代理账号，也不可从本站之游戏账户或其他相关人士赚取佣金。</p>
        <p>请谨记任何代理商皆不能用代理帐户下注，本站有权终止并封存账号及其所有在游戏中赚取的佣金。</p>
        <p>为确保所有本站会员的账号隐私与权益， 本站不会提供任何会员密码，或会员个人资料。 </p>
        <p>各阶层合作代理商亦不得以任何方式取得会员数据，或任意登入下层会员账号，如发现代理商有侵害本站会员隐私的行为， 本站有权取消代理商之红利，并取消该名代理商之账号。</p>
        <p>合作代理商旗下的会员不得开设多于一个的账户。本站有权要求会员提供有效的身份证明以验证会员的身份，并保留以IP判定会员是否重复注册的权利。如违反上述事项，本站有权终止玩家进行游戏并封存账号及所有于游戏中赚取的佣金。</p>
        <p>如合作代理商旗下的会员因违反条例而被禁止使用本站的游戏，或本站退回存款给会员，本站将不会分配相应的佣金给代理商。 </p>
        <p>如合作代理商旗下会员存款用的信用卡、银行资料须经审核，本站将保留相关佣金直至审核完毕。</p>
        <p>约条件将于本站正式接受合作代理商加入后开始生效。本站及代理商可随时终止此合约。在任何情况下，代理商若欲终止合约，都必须以书面/电邮方式提早于七日内通知本站。代理商的表现将会每3个月审核一次，如代理商已不是现有的合作成员，则本合约书可以在任何时间终止。如代理商违反合约条例，本站有权立即终止合约。</p>
        <p>在没有本站的许可下，代理商不能透露及授权本站的相关机密资料，包括代理商所获得的回馈、佣金报表、计算方式等； 代理商有义务在合约终止后仍执行机密文件及数据的保密。合约终止之后，代理商及本站将不须履行双方的权利及义务。</p>
        <p>终止合约并不会解除代理商于终止合约前所应履行的义务。</p>
        ';            
		}
		//合作協議modal
        $cooperationAgreementHtml .= '
        <div class="modal fade" id="cooperationAgreementModal" tabindex="-1" role="dialog" aria-labelledby="cooperationAgreementlLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title" id="cooperationAgreementLabel">'.$tr['cooperation registration'].'</h4>
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
              </div>
              <div class="modal-body">
                '.$cooperationAgreementContent.'
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">'.$tr['off'].'</button>
              </div>
            </div>
          </div>
        </div>
        ';	
  }


// 切成 3 欄版面
$indexbody_content = $indexbody_content.'
<div class="row">
	<div class="col-md-10 offset-md-1">
'.$showtext_html.$cooperationAgreementHtml.'
	</div>
</div>
<div class="row">
	<div class="col-md-10 offset-md-1">
		<div id="preview"></div>
	</div>
</div>
';

$success_url = ($config['site_style']=='mobile')? './menu_admin.php?gid=agent':'./spread_register.php';

$extend_js .=<<<HTML
<script>
    $(document).on('click', '#registerAgentBtn', function() {
      var pw = $('#password').val();
      if (pw != '') {
        var csrftoken = '{$csrftoken}';
        var agreementAgree = 'N';

        if($('#agreementAgree').prop('checked')) {
          agreementAgree = 'Y';
        }

        var data = {
          'password' : $().crypt({method:'sha1', source:pw}),
          'agreementAgree' : agreementAgree
        };

        $.ajax({
          type: "POST",
          url: "register_agent_action.php",
          data: {
            data: JSON.stringify(data),
            action: 'registerAgent',
            csrftoken: csrftoken
          }
        }).done(function(resp) {
          var res = JSON.parse(resp);
          if (res.status == 'success') {
            alert(res.result);

            if (res.isAutomatic == 'Y') {
              document.location.href="{$success_url}";
            } else {
              location.reload();
            }
          } else {
            alert(res.result);
          }
        }).fail(function() { 
          alert("error"); 
        })
      } else {
        alert('{$tr['register agent step-enter password']}');
      }
    });
  $(document).on('click', '#agreementAgree', function() {
    if( $('#agreementAgree').prop('checked')){
      $('#registerAgentBtn').removeAttr('disabled');
    }else{
      $('#registerAgentBtn').attr('disabled','true');
    }
  });
</script>
HTML;


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
//$tmpl['sidebar_content'] = ['static','aboutus'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");

?>
