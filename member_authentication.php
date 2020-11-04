<?php
// ----------------------------------------------------------------------------
// Features:	前台 -- 2階段驗證
// File Name:   member_authentication.php
// Author:		Mavis
// Related:     member_authentication_action.php,member_authentication_lib.php
// Log:
//
// ----------------------------------------------------------------------------

session_start();

// 主機及資料庫設定
require_once dirname(__FILE__) ."/config.php";
// 支援多國語系
require_once dirname(__FILE__) ."/i18n/language.php";
// 自訂函式庫
require_once dirname(__FILE__) ."/lib.php";
// 2fa
require_once dirname(__FILE__) ."/in/PHPGangsta/GoogleAuthenticator.php";

require_once dirname(__FILE__) ."/member_authentication_lib.php";

//var_dump($_SESSION);
//var_dump($_POST);

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------

// 宣告兩階段驗證物件
$ga = new PHPGangsta_GoogleAuthenticator();

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
// 初始化變數
// 功能標題，放在標題列及meta
$function_title 		= $tr['membercenter_member_authentication'] ;
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

// 導覽列
$navigational_hierarchy_html =<<<HTML
	<ul class="breadcrumb">
		<li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
        <li><a href="member.php">{$tr['Member Centre']}</a></li>
		<li class="active">{$function_title}</li>
	</ul>
HTML;

if($config['site_style']=='mobile'){
  	$navigational_hierarchy_html =<<<HTML
		<a href="{$config['website_baseurl']}menu_admin.php?gid=safe"><i class="fas fa-chevron-left"></i></a>
		<span>{$function_title}</span>
		<i></i>
HTML;
}

// 會員有登入
if(isset($_SESSION['member'])){

    $content = '';
    $questions_list = '';

    // 會員ID
    $m_id = $_SESSION['member']->id;
    // 會員帳號
    $m_account = $_SESSION['member']->account;
    // 判斷該會員在2FA有沒有資料、有無開啟2FA驗證
    // 有資料->判斷2FA 開關。
        // 2FA開(1):顯示停用2FA按鈕; 
        // 2FA關(0):顯示啟用2FA QR CODE等資訊
    // 無資料:  顯示開啟2FA QR CODE等資訊
    
    $get_auth_data = sql_member_authentication($m_id);

    // 2FA資料庫沒有該會員資料 或者 2FA 開關被關掉
    if($get_auth_data[0] ==0 OR $get_auth_data[1]->two_fa_status == '0'){
        $content.=<<<HTML
            <div id="accordion">               
            </div>
HTML;
        // 問題
        foreach($disable_question as $q_key => $q_value){
            $questions_list.=<<<HTML
                <option value="$q_key">$q_value</option>
HTML;
        }
                 
        // 產生secret' => '6AEOSPKL3ZFQIKGU' ，  'qrCodeUrl'=>https://chart.googleapis.com/cha
        $twofa_generate_data= generate_secret($ga,$m_account);

        if($config['site_style']=='mobile'){
            $content .=<<<HTML
<div class="authentication">
    <div class="authentication_member">
      <div class="sep_title">
        <span class="step_rounded">1</span> <p>{$tr['Step 1: Scan the QR code or manually enter the verification key']}</p>
      </div>
      <div class="row">
        <div class="col-12">          
          <div class="gold_key">
          <span id="secret_id">{$twofa_generate_data['secret']}</span>
          <button class="btn btn-primary" id="copy_qr" data-clipboard-target="#secret_id">{$tr['button copy']}</button>
          </div>
          <img id='qrcode_id' src="{$twofa_generate_data['qrCodeUrl']}">
          <div>
          <button type="button" id="qr_code_refresh" class="qr_code_authentication">{$tr['Regenerate']}</button>
          </div>                    
        </div>
        <div class="col-10 col-md-4 authentication_text">
          <div>
            <p>{$tr['app download in advance']}<img src="{$cdnfullurl}img/common/goole_authenticator.png" alt="">{$tr['install on the mobile device']}</p>
            <p>{$tr['for iPhone users']}
            <a target="_blank" href="https://itunes.apple.com/tw/app/google-authenticator/id388497605?mt=8">
            <img src="{$cdnfullurl}img/common/app_store.png" alt="">
            {$tr['App Store']}
            </a>                            
            </p>
            <p>{$tr['for android users']}
            <a target="_blank" href="https://android.myapp.com/myapp/detail.htm?apkName=com.google.android.apps.authenticator2">
            <img src="{$cdnfullurl}img/common/android_icon.png" alt=""> {$tr['Application treasure']}
            </a>、
            <a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=zh_TW">
            <img src="{$cdnfullurl}img/common/android_icon2.png" alt=""> {$tr['google']}</a>
            </p>
            </p>
            <p>{$tr['Open the APP']}</p>
            <p>{$tr['add an account by scaning']}</p>
            <p>{$tr['If you have used it']}</p>
            </div>
          </div>
      </div>
      <div class="sep_title">
        <span class="step_rounded">2</span> <p>{$tr['Step 2: Set the answer when deactivating verification']}</p>
      </div>
      <div class="row">
      <div class="col-10 col-md-7 input_authentication">
        <div class="authentication_title">{$tr['please choose a question']}</div>
        <div class="authentication_select"><select name="twofa_question" size="1">{$questions_list}</select></div>
      </div>
      <div class="col-10 col-md-7 input_authentication">
        <div class="authentication_title"><span>*</span>{$tr['Please fill in the answer']}</div>
        <div class="authentication_select"><input type="text" name="twofa_ans" class="form-control" required></div>
      </div>
      <div class="col-10 col-md-7 input_authentication">
        <div class="authentication_title"><span>*</span>{$tr['Enter confirmation code']}</div>
        <div class="authentication_select"><input type="text"  name="verify_code" class="form-control" required></div>
      </div> 
      </div> 
      <div class="row">
          <div class="col-10 col-md-7 authentication_button">
              <button type="button" id="confrim_factor_id" class="authentication_send">{$tr['confirm the factor code']}</button>
              <!--<button type="button" id="cancel_factor" class="btn btn-secondary">{$tr['cancel'] }</button>-->
          </div>
      </div>           
    </div>
</div>            
HTML;
        }else{
            $content .=<<<HTML
<div class="authentication main_content">
  <div class="authentication_member">
        <div class="d-flex align-items-center step_title">
            <span class="step_rounded">1</span> <p>{$tr['Step 1: Scan the QR code or manually enter the verification key']}</p>
        </div> 
      <div class="row">
      <div class="col-5">
          <div class="gold_key">
          <span id="secret_id">{$twofa_generate_data['secret']}</span>
          <button class="btn btn-primary" id="copy_qr" data-clipboard-target="#secret_id">{$tr['button copy']}</button>
          </div>
          <img id='qrcode_id' src="{$twofa_generate_data['qrCodeUrl']}">
          <div>
          <button type="button" id="qr_code_refresh" class="btn btn-secondary qr_code_authentication">{$tr['Regenerate']}</button>
          </div>                    
      </div>

      <div class="col authentication_text">
          <div>
          <p>{$tr['app download in advance']}<img src="{$cdnfullurl}img/common/goole_authenticator.png" alt="">{$tr['install on the mobile device']}</p>
              <p>{$tr['for iPhone users']}
              <a target="_blank" href="https://itunes.apple.com/tw/app/google-authenticator/id388497605?mt=8">
              <img src="{$cdnfullurl}/img/common/app_store.png" alt="">
              {$tr['App Store']}
              </a>                            
              </p>
              <p>{$tr['for android users']}
              <a target="_blank" href="https://android.myapp.com/myapp/detail.htm?apkName=com.google.android.apps.authenticator2">
                  <img src="{$cdnfullurl}/img/common/android_icon.png" alt=""> {$tr['Application treasure']}
              </a>、
              <a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=zh_TW">
                  <img src="{$cdnfullurl}/img/common/android_icon2.png" alt=""> {$tr['google']}</a>
              </p>
              </p>
          <p>{$tr['Open the APP']}</p>
          <p>{$tr['add an account by scaning']}</p>
          <p>{$tr['If you have used it']}</p>
      </div>
  </div>
  </div>
      </div>

      <div class="row">
          <div class="col-12">
            <div class="d-flex align-items-center step_title">
                <span class="step_rounded">2</span> <p>{$tr['Step 2: Set the answer when deactivating verification']}</p>
            </div>
        </div>
        <div class="w-100 step_2">
            <div class="col-12">
            <div class="authentication_title">{$tr['please choose a question']}</div>
            <div class="authentication_select"><select name="twofa_question" size="1">{$questions_list}</select></div>
            </div>
            <div class="col-12">
            <div class="authentication_title"><span>*</span>{$tr['Please fill in the answer']}</div>
            <div class="authentication_select"><input type="text" name="twofa_ans" class="form-control" required></div>
            </div>
            <div class="col-12">
            <div class="authentication_title"><span>*</span>{$tr['Enter confirmation code']}</div>
            <div class="authentication_select"><input type="text"  name="verify_code" class="form-control" required></div>
            </div>
        </div>
    </div>
  <div class="row">
    <div class="col-12">
        <button type="button" id="confrim_factor_id" class="authentication_send">{$tr['confirm the factor code']}</button>
        <!--<button type="button" id="cancel_factor" class="btn btn-secondary">{$tr['cancel'] }</button>-->
    </div>
  </div>              
</div>
HTML;
        }
        
    
    }elseif($get_auth_data[0] >= 1 AND $get_auth_data[1]->two_fa_status == '1'){
        // 有資料而且有開啟2FA，顯示停用2FA
        $content.=<<<HTML
<div class="main_content m_authentication">
<div class="row">
            <div class="col-12">
                <h5 class="text-center m-2">{$tr['You have enabled factor authentication (2FA)']}</h5>
            </div>
        </div>

        <div class="row">
            <div class="col-12 authentication_important">
            <p class="au_text">{$tr['If your mobile phone is lost']}</p>
            <p class="au_text">{$tr['After closing, the original account will be invalid.']}</p>
            <button class="btn btn-danger" id="shut_down_2fa" data-toggle="modal" data-target="#exampleModal">{$tr['disable the fqactor authentication']}</button>
            </div>

            <!-- modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">{$tr['disable the fqactor authentication']}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h5>{$tr['To disable 2FA, please answer the following questions to confirm your own']}</h5>
                        <h6 class="text-left">{$tr['questions']} {$disable_question[json_decode($get_auth_data[1]->two_fa_question,true)]}</h6>
                            <input type="text"  name="twofa_disable_ans" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{$tr['cancel'] }</button>
                            <button type="button" class="btn btn-primary" id="disable_factor">{$tr['confirm the factor code']}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
HTML;
    }

}else{
    header('Location:home.php');
    die();

}

$extend_js=<<<HTML
<script>
    // 當按下重新產生時，產生qr code 及金鑰
    $('#qr_code_refresh').click(function(){
        $.ajax ({
            url: 'member_authentication_action.php?a=refresh',
            type: 'POST',
            dataType: 'json',
            data: {
                i: $m_id
            },
            success: function(response){
                $("#qrcode_id").attr("src", response.qrCodeUrl);
                $("#secret_id").text(response.secret); 
            },
            error: function (error) {
                $("#preview_result").html(error);
            }
        });
    });

    $(document).ready(function() {
      var clipboard = new ClipboardJS('#copy_qr');
      var copysuccess = "{$tr['Successful copy']}";
      var copyerror = "{$tr['Error copy']}";
      
      clipboard.on('success', function(e) {
        alert(copysuccess);//'复制成功'
      e.clearSelection();
    });

    clipboard.on('error', function(e) {
          alert(copyerror);//'复制失败'
      });
    });

    // 啟用
    // 前台只存2FA的資料，IP(whitelis_status、whitelis_ip)不存
    $('#confrim_factor_id').on('click',function(){
        // 問題
        var questions = $("select[name='twofa_question']").val();
        // 啟用答案
        var twofa_ans = $("input[name='twofa_ans']").val().trim();
        // 驗證碼
        var verify_code = $("input[name='verify_code']").val();
        // 金鑰
        var secret_id = $("#secret_id").text();

        $.post('member_authentication_action.php?a=save_factor',{
            questions: questions,
            twofa_ans: twofa_ans,
            verify_code :verify_code,
            secret_id : secret_id,
            i: $m_id
        },function(result){
            $("#preview_result").html(result);
        })
    })

    // 2fa 停用 0
    $('#disable_factor').on('click',function(){
        // 停用答案
        var twofa_disable_ans = $("input[name='twofa_disable_ans']").val();

        $.post('member_authentication_action.php?a=factor_disable',{
            twofa_disable_ans: twofa_disable_ans,
            i: $m_id
        },function(result){
            $("#preview_result").html(result);
        })
    })

    // 按下 enter 後,等於 click 登入按鍵
    $(function() {
        $(document).keyup(function(e) {
            switch(e.which) {
                case 13: // enter key
                    $("#confrim_factor_id").trigger("click");
                    $("#disable_factor").trigger("click");
                break;
            }
        });
    });

</script>
HTML;

// 切成 3 欄版面 3:8:1
$indexbody_content = '';
//功能選單(美工)  功能選單(廣告)
$indexbody_content = $indexbody_content . '
' . $content . '
<div class="row">
    <div class="col-12">
    <div id="preview_result"></div>
    </div>
</div>
';
$extend_head =<<<HTML
<script src="in/js/clipboard.min.js"></script>
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
$tmpl['sidebar_content'] = ['safe','member_authentication'];
// $tmpl['sidebar_content'] = ['safe','member_authentication'];
// menu增加active
$tmpl['menu_active'] =['member.php'];
// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include $config['template_path'] . "template/admin.tmpl.php";
?>
