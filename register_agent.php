
<?php
// ----------------------------------------------------------------------------
// Features: 前台 - 會員填完個人資料後，註冊成為代理商。
// File Name: register_agent.php
// Editor: Damocles
// Related: register_agent_action.php
// Log:
// ----------------------------------------------------------------------------

// 主機及資料庫設定
require_once dirname(__FILE__)."/config.php";

// 支援多國語系
require_once dirname(__FILE__)."/i18n/language.php";

// 自訂函式庫
require_once dirname(__FILE__)."/lib.php";

// uisetting函式庫
require_once dirname(__FILE__) ."/lib_uisetting.php";

// 只要 session 活著,就要同步紀錄該 user account in redis server db 1
RegSession2RedisDB();
// ----------------------------------------------------------------------------


// 初始化變數
// 功能標題，放在標題列及meta
//申請成為加盟聯營股東
$function_title = $tr['register agent title'];

// 擴充 head 內的 css or js
$extend_head = '';

// 放在結尾的 js
$extend_js = '';

// body 內的主要內容
$indexbody_content = '';

// 系統訊息選單
$messages = '';

// 導覽列
if ($config['site_style'] == 'mobile') {
    $navigational_hierarchy_html = <<<HTML
        <a href="{$config['website_baseurl']}home.php"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
        <span>{$function_title}</span>
        <i></i>
    HTML;
} else {
    $navigational_hierarchy_html = <<<HTML
        <ul class="breadcrumb">
            <li><a href="home.php"><span class="glyphicon glyphicon-home"></span></a></li>
            <li class="active">{$function_title}</li>
        </ul>
    HTML;
}
// ----------------------------------------------------------------------------
sys_protalsetting_list(1);

//把會員資料附加語系、是否顯示、是否必填、資料表欄位名稱...等屬性，用於後續傳值寫入
function getRegisterDataColSetting($memberData)
{
    global $tr;
    global $protalsetting;

    $arr = [
        'realname' => [
            'col_name' => $tr['real name'],
            'placeholder_text' => $tr['real name notice'],
            'isshow' => $protalsetting['agent_register_name_show'],
            'ismust' => $protalsetting['agent_register_name_must'],
            'member_data' => $memberData->realname,
            'inputid' => 'realname'
        ],
        'mobilenumber' => [
            'col_name' => $tr['cellphone'],
            'placeholder_text' => $tr['cellphone notice'],//'須為中國手機號碼 ex: 15327049261 共11碼'
            'isshow' => $protalsetting['agent_register_mobile_show'],
            'ismust' => $protalsetting['agent_register_mobile_must'],
            'member_data' => $memberData->mobilenumber,
            'inputid' => 'mobilenumber'
        ],
        'email' => [
            'col_name' => $tr['email'],
            'placeholder_text' => '(abc@aa.com)',
            'isshow' => $protalsetting['agent_register_mail_show'],
            'ismust' => $protalsetting['agent_register_mail_must'],
            'member_data' => $memberData->email,
            'inputid' => 'email'
        ],
        'birthday' => [
            'col_name' => $tr['brithday'],
            'placeholder_text' => $tr['brithday'],
            'isshow' => $protalsetting['agent_register_birthday_show'],
            'ismust' => $protalsetting['agent_register_birthday_must'],
            'member_data' => $memberData->birthday,
            'inputid' => 'birthday'
        ],
        'sex' => [
            'col_name' => $tr['gender'],
            'isshow' => $protalsetting['agent_register_sex_show'],
            'ismust' => $protalsetting['agent_register_sex_must'],
            'member_data' => $memberData->sex,
            'inputid' => 'sex'
        ],
        'wechat' => [
            'col_name' => $protalsetting["custom_sns_rservice_1"] ?? $tr['sns1'],
            'placeholder_text' => $protalsetting["custom_sns_rservice_1"] ?? $tr['sns1'],
            'isshow' => $protalsetting['agent_register_wechat_show'],
            'ismust' => $protalsetting['agent_register_wechat_must'],
            'member_data' => $memberData->wechat,
            'inputid' => 'wechat'
        ],
        'qq' => [
            'col_name' => $protalsetting["custom_sns_rservice_2"] ?? $tr['sns2'],
            'placeholder_text' => $protalsetting["custom_sns_rservice_2"] ?? $tr['sns2'],
            'isshow' => $protalsetting['agent_register_qq_show'],
            'ismust' => $protalsetting['agent_register_qq_must'],
            'member_data' => $memberData->qq,
            'inputid' => 'qq'
        ]
    ];
    return $arr;
}

function getRegisterDataBankDataColSetting($memberData)
{
    global $protalsetting;
    $arr = [
        'isshow' => $protalsetting['agent_bank_information_show'],
        'ismust' => $protalsetting['agent_bank_information_must'],
        'bankname' => $memberData->bankname,
        'bankaccount' => $memberData->bankaccount,
        'bankprovince' => $memberData->bankprovince,
        'bankcounty' => $memberData->bankcounty
    ];
    return $arr;
}

// 代理商審查流程 (查詢看看該會原是否已經有代理商審查紀錄)
function checkAgentReview()
{
    $sql = <<<SQL
        SELECT *
        FROM "root_agent_review"
        WHERE ("account" = '{$_SESSION["member"]->account}')
            AND ("status" = '2')
        ORDER BY "id" DESC
        LIMIT 1;
    SQL;
    return runSQL($sql);
}

function combineAgentReviewHtml($data)
{
    global $tr;

    $agentReviewStatus = [
        $tr['withdraw status cancel'],
        $tr['agree to become agent'],
        $tr['already apply for agent'],
        $tr['withdraw status process'],
        $tr['withdraw status reject']
    ];

    $status = (in_array($data->status, $agentReviewStatus)) ? $review_agent_status[$data->status] : $tr['withdraw status delete'];

    $html = <<<HTML
        <div class="panel panel-success">
            <div class="panel-heading">{$tr['member to agent note']}{$tr['cancel apply contact customer service']}</div>
            <table class="table">
            <thead>
                <tr>
                    <th>{$tr['member to agent seq']}</th>
                    <th>{$tr['member to agent account']}</th>
                    <th>{$tr['member to agent apply date']}</th>
                    <th>{$tr['withdrawal apply status']}</th>
                    <th>{$tr['member to agent process result']}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$data->id}</td>
                    <td>{$data->account}</td>
                    <td>{$data->applicationtime_tz}</td>
                    <td>{$review_agent_status[$data->status]}</td>
                    <td>{$check_review_agent_ret[1]->notes}</td>
                </tr>
            </tbody>
            </table>
        </div>
    HTML;

    return $html;
}

// 輸出性別的HTML，但如果有值的話，則只會顯示值(沒有下拉式選單)
function combineSexHtml($data)
{
    global $tr;
    $sex_selection = ['0', '1'];
    if ( in_array($data['member_data'], $sex_selection) ) {
        switch ($data['member_data']) {
            case '0':
                $sex = $tr['female'];
                break;
            case '1':
                $sex = $tr['male'];
                break;
            default:
                $sex = $tr['sex-undefined'];
        }
        $html = <<<HTML
            <p>{$sex}</p>
        HTML;
    } else {
        $html = <<<HTML
            <select class="form-control" id="{$data['inputid']}">
                <!-- <option value="2">{$tr['gender unknown']}</option> -->
                <option value="1">{$tr['male']}</option>
                <option value="0">{$tr['female']}</option>
            </select>
        HTML;
    }
    return $html;
}

// 產生輸入欄位HTML，但如果有值的話，則只會顯示值(沒有輸入框)
function combineInputHtml($data)
{
    $is_required = ( ( isset($data['ismust']) && ($data['ismust'] == 'on') ) ? 'required' : '' );
    $class_required = ( ( isset($data['ismust']) && ($data['ismust'] == 'on') ) ? 'class_required' : '' );
    if (trim($data['member_data']) == '') {
        $html = <<<HTML
            <div class="form_content w-100">
                <input type="text" class="form-control {$class_required}" id="{$data['inputid']}" placeholder="ex：{$data['placeholder_text']}" {$is_required}>
            </div>
        HTML;
    } else {
        $html = <<<HTML
            <div class="w-100">
                <p>{$data['member_data']}</p>
            </div>
        HTML;
    }
    return $html;
}

// 產生填寫欄位標籤
function combineMemberDataHtml($data)
{
    global $tr;
    $result = [
        'html' => '',
        'isComplete' => true, // 決定是否產生個人信息的儲存按鈕
        'requireSave' => false
    ];

    foreach ($data as $key => $value) {
        if ($value['isshow'] == 'on') {

            $isRequired = ''; // 預設值
            if ( ($value['isshow'] == 'on') && ($value['ismust'] == 'on') ) {
                // 欄位有顯示 & 必填屬性的話，html加上*
                $isRequired = <<<HTML
                    <i class="required">*</i>
                HTML;

                // 這邊因為性別欄位的值可能為0，故不使用empty
                if ( (trim($value['member_data']) == '') || is_null($value['member_data']) ) {
                    $result['isComplete'] = false;
                }
            }

            // 判斷顯示的欄位是否有未填寫的
            if ( ($value['isshow'] == 'on') && ( (trim($value['member_data']) == '') || is_null($value['member_data']) ) ) {
                $result['requireSave'] = true;
            }

            // 輸入欄位HTML，如果參數值有設定的話，則會輸出純文字(沒有輸入框)
            switch ($key) {
                case 'sex':
                    $content = combineSexHtml($value);
                    $result['html'] .= <<<HTML
                        <div class="form-group d-flex align-items-center">
                            <div class="title">{$isRequired}{$value['col_name']}：</div>
                            <div class="w-100">{$content}</div>
                        </div><br>
                    HTML;
                    break;
                default:
                    $content = combineInputHtml($value);
                    $result['html'] .= <<<HTML
                        <div class="form-group d-flex align-items-center">
                            <div class="title">{$isRequired}{$value['col_name']}：</div>
                            {$content}
                        </div><br>
                    HTML;
            }
        }
    }

    // 沒有需要填寫的欄位
    if ( empty($result['html']) ) {
        $result['html'] = <<<HTML
            <p>{$tr['No need to fill in the field']}</p>
        HTML;
    }

    return $result;
}

// 產生銀行HTML
function combineBankDataHtml($data)
{
    global $tr;

    $result = [
        'html' => '',
        'isComplete' => true // 決定是否出現儲存銀行資訊
    ];

    $bankdataCol = [
        'bankname' => $tr['bank name'],
        'bankaccount' => $tr['bank account'],
        'bankprovince' => $tr['bank province'],
        'bankcounty' => $tr['bank country']
    ];

    if ($data['isshow'] == 'on') {
        $isRequired = ($data['isshow'] == 'on' && $data['ismust'] == 'on') ? '<i class="required">*</i>' : '';

        foreach ($bankdataCol as $key => $value) {
            $content = combineInputHtml([
                'inputid' => $key,
                'placeholder_text' => $value,
                'member_data' => $data[$key],
                'ismust' => $data['ismust'] // 這邊要用於產生html的required
            ]);

            if ( ($data['isshow'] == 'on') && ($data['ismust'] == 'on') && empty($data[$key]) ) {
                $result['isComplete'] = false;
            }

            $result['html'] .= <<<HTML
                <div class="form-group d-flex align-items-center">
                    <div class="title">{$isRequired}{$value}：</div>
                    <div class="w-100">{$content}</div>
                </div><br>
            HTML;
        }
    }

    // 不需要填寫
    if ( empty($result['html']) ) {
        $result['html'] = <<<HTML
            <p>{$tr['No need to fill in the field']}</p>
        HTML;
    }

    return $result;
}

//銀行卡 icon show
function combineiconhtml($data, $btn)
{
    global $tr;

    $icon = <<<HTML
        <i class="far fa-check-circle"></i>{$tr['completed']}
    HTML;

    // 銀行卡
    $html['html'] = '';
    if ($data['isshow'] == 'on') {
        if ( ($data['isshow'] == 'on') && ($data['ismust'] == 'on') ) {
            $html['html'] = $btn;
        } else {
            $html['html'] = <<<HTML
                <button type="button bd-highlight" class="btn btn-success"><i class="fas fa-chevron-down"></i></button>
            HTML;
        }
    }
    return $html;
}

// 非法訪問
if ( !isset($_SESSION['member']) || ($_SESSION['member']->therole != 'M') ) {
    $msg = $tr['permission error']; // '不合法的访问权限。'
    memberlogtodb('guest', 'member', 'warning', $msg, 'guest', $msg, 'f', 'authority');
    die(<<<HTML
        <script>
            document.location.href = "./member_management.php";
        </script>
    HTML);
}

//全民代理打開時跳轉至專頁
if ( isset($protalsetting['national_agent_isopen']) && ($protalsetting['national_agent_isopen'] === 'on') ) {
    die(<<<HTML
        <script>
            document.location.href = "./allagent_register.php";
        </script>
    HTML);
// 代理商申請非開啟狀態時，跳轉回到上一頁
} else if ( isset($protalsetting['agent_register_switch']) && ($protalsetting['agent_register_switch'] !== 'on') ) {
    die(<<<HTML
        <script>
            alert("{$tr['register agent closed']}");
            history.go(-1);
        </script>
    HTML);
}

$sql = <<<SQL
    SELECT *
    FROM "root_member"
    JOIN "root_member_wallets"
        ON ("root_member"."id" = "root_member_wallets"."id")
    WHERE ("root_member"."id" = '{$_SESSION["member"]->id}');
SQL;
$result = runSQLALL($sql);

if ( !empty($result[0]) ) {
    $account_and_wallet_data = $result[1]; // 使用者帳號跟錢包的資料
    $agent_review_isopen = ( isset($protalsetting['agent_review_isopen']) ) ? $protalsetting['agent_review_isopen'] : 'on'; // agent_review_isopen
    if ($agent_review_isopen == 'on') {
        if ( !checkAgentReview() ) { // 代理商審查流程
            // 個人信息
            $outPutMemberData = getRegisterDataColSetting($account_and_wallet_data);
            $memberDataContent = combineMemberDataHtml($outPutMemberData);

            // 個人信息-是否完成必填欄位
            if (!$memberDataContent['isComplete']) { // 未達成
                $memberDataContentBtn = <<<HTML
                    <button type="button bd-highlight" class="btn btn-danger">
                        <i class="glyphicon glyphicon-remove"></i>{$tr['unaccomplished']}<i class="fas fa-chevron-down"></i>
                    </button>
                HTML;
            } else { // 已達成
                $memberDataContentBtn = <<<HTML
                    <button type="button bd-highlight" class="btn btn-success">
                        <i class="far fa-check-circle"></i>{$tr['completed']}
                        <i class="fas fa-chevron-down"></i>
                    </button>
                HTML;
            }

            // 個人信息-是否要有儲存個人信息按鈕
            if ($memberDataContent['requireSave']) {
                $memberDataSaveBtn = <<<HTML
                    <button type="button" class="btn btn-primary btn-lg btn-block" id="member_data_save_btn">{$tr['save profile']}</button>
                HTML;
            } else {
                $memberDataSaveBtn = '';
            }


            // 綁定銀行
            $outPutBankData = getRegisterDataBankDataColSetting($account_and_wallet_data);
            $bankDataContent = combineBankDataHtml($outPutBankData);

            // 綁定銀行-預設值
            $bankDataContentBtn = <<<HTML
                <button type="button  bd-highlight" class="btn btn-success">
                    <i class="far fa-check-circle"></i>{$tr['completed']}<i class="fas fa-chevron-down"></i>
                </button>
            HTML; // 已達成
            $bankDataSaveBtn = <<<HTML
                <button type="button" class="btn btn-primary btn-lg btn-block" id="bank_data_save_btn">{$tr['save bank setting']}</button>
            HTML; // 儲存銀行卡

            // 綁定銀行卡
            if (!$bankDataContent['isComplete']) {
                $bankDataContentBtn = <<<HTML
                    <button type="button  bd-highlight" class="btn btn-danger">
                        <i class="glyphicon glyphicon-remove"></i>{$tr['unaccomplished']}
                        <i class="fas fa-chevron-down"></i>
                    </button>
                HTML; // 未達成
                $bankDataSaveBtn = <<<HTML
                    <button type="button" class="btn btn-primary btn-lg btn-block" id="bank_data_save_btn">{$tr['save bank setting']}</button>
                HTML; // 儲存銀行卡
            }
            // 綁定銀行卡 - 非必填 必填 判斷 icon
            $bankdatacontenticon = combineiconhtml($outPutBankData,$bankDataContentBtn);

            $agentRegisterCashBtn = <<<HTML
                <button type="button  bd-highlight" class="btn btn-success">
                    <i class="far fa-check-circle"></i>{$tr['completed']}<i class="fas fa-chevron-down"></i>
                </button>
            HTML; // 已達成
            $goDepositBtn = '';


            // 代理加盟金
            if ($system_config['agency_registration_gcash'] > $account_and_wallet_data->gcash_balance) {
                $agentRegisterCashBtn = <<<HTML
                    <button type="button  bd-highlight" class="btn btn-danger">
                        <i class="glyphicon glyphicon-remove"></i>{$tr['unaccomplished']}
                        <i class="fas fa-chevron-down"></i>
                    </button>
                HTML; // 未達成
                $goDepositBtn = <<<HTML
                    <a class="btn btn-primary btn-lg btn-block" role="button" href="deposit.php">{$tr['go to deposit page']}</a>
                HTML; // 前往入款
            }

            $depositCurrencyWarning = '';
            if ($protalsetting['member_deposit_currency'] == 'gtoken') {
                $depositCurrencyWarning = <<<HTML
                    <div class="alert alert-danger" role="alert">
                        {$tr['To convert cash points, please go to']}
                        <a href="./wallets.php" class="alert-link"> {$tr['membercenter_withdrawapplication']} </a>{$tr['to apply']}
                    </div>
                HTML;
                //目前入款方式目标帐户皆为游戏币钱包，如有疑问请联络客服
            }

            $dateyearrange_start = date("Y") - 100;
            $dateyearrange_end = date("Y") - 14;
            $datedefauleyear = date("Y") - rand(25,55);

            $extend_js = <<<HTML
                <script>
                    $(function() {
                        // 密碼輸入事件
                        $('#password').keyup(function(e) {
                            if ( (e.keyCode >= 65) || (e.keyCode <= 90) ) {
                                $('#password').trigger('click');
                            }
                        });
                        // 生日
                        $('#birthday').datetimepicker({
                            defaultDate:'{$datedefauleyear}/01/01',
                            minDate: '{$dateyearrange_start}/01/01',
                            maxDate: '{$dateyearrange_end}/01/01',
                            timepicker:false,
                            format:'Y/m/d',
                            lang:'en'
                        });
                        // member icon show
                        var membericon = $('.membericon').find('.required').length;
                        var memberlist = $('#collapseone').find('input').length;
                        if (membericon == 0) {
                            $('.membericon_bt button').html('<i class="fas fa-chevron-down"></i>');
                        }
                        if (memberlist == 0) {
                            $('#headingone').hide();
                        }
                        //brank card
                        var brankcard = $('#collapsetwo').find('input').length;
                        if (brankcard == 0) {
                            $('#headingtwo').hide();
                        }
                    });
                    // 個人信息儲存
                    $(document).on('click', '#member_data_save_btn', function() {
                        var data = getEditMemberData(); // console.log(data); return false;
                        if (data == false) { // 這邊data可能會回傳欄位資訊或false(代表有錯誤發生)，故這邊要判斷是否繼續執行
                            return false;
                        }
                        $.ajax({
                            url: 'register_agent_action.php',
                            type: 'POST',
                            data: {
                                data: JSON.stringify(data),
                                action: 'editMemberData',
                                csrftoken: "{$csrftoken}"
                            }
                        }).done(function(resp) {
                            var res = JSON.parse(resp);
                            if (res.status == 'success') {
                                alert(res.result);
                                location.reload();
                            } else {
                                alert(res.result);
                            }
                        }).fail(function() {
                            alert("error");
                        });
                    });
                    // 綁定銀行卡
                    $(document).on('click', '#bank_data_save_btn', function() {
                        var data = getEditBankData();
                        if (data == false) { // 這邊data可能會回傳欄位資訊或false(代表有錯誤發生)，故這邊要判斷是否繼續執行
                            return false;
                        }
                        $.ajax({
                            type: 'POST',
                            url: 'register_agent_action.php',
                            data: {
                                data: JSON.stringify(data),
                                action: 'editBankData',
                                csrftoken: '{$csrftoken}'
                            }
                        }).done(function(resp) {
                            var res = JSON.parse(resp);
                            if (res.status == 'success') {
                                alert(res.result);
                                location.reload();
                            } else {
                                alert(res.result);
                            }
                        }).fail(function() {
                            alert("error");
                        })
                    });
                    // 同意條款
                    $(document).on('click', '#agreementagree', function() {
                        if( $('#agreementagree').prop('checked') ){
                            $('#registeragentbtn').removeAttr('disabled');
                        } else {
                            $('#registeragentbtn').attr('disabled','true');
                        }
                    });
                    // 取得個人信息的欄位資料
                    function getEditMemberData() {
                        var data = {};
                        var obj_key = [
                            'realname',
                            'mobilenumber',
                            'email',
                            'birthday',
                            'sex',
                            'wechat',
                            'qq'
                        ];
                        var dom_id = [
                            'realname',
                            'mobilenumber',
                            'email',
                            'birthday',
                            'sex',
                            'wechat',
                            'qq'
                        ];
                        var require_msg = "{$tr['confirm all information again']}";
                        for (var i=0; i<obj_key.length; i++) {
                            var dom = $('#' + dom_id[i]);
                            if (dom.length == 1) {
                                // 判斷欄位是否為必填 & 欄位是否有填寫
                                if ( dom.prop('required') && (dom.val().trim() == '') ) {
                                    alert(require_msg);
                                    return false;
                                } else {
                                    data[ obj_key[i] ] = dom.val();
                                }
                            }
                        }
                        return data;
                    }
                    // 取得綁定銀行的欄位資料
                    function getEditBankData() {
                        var data = {};
                        var obj_key = [
                            'bankname',
                            'bankaccount',
                            'bankprovince',
                            'bankcounty'
                        ];
                        var dom_id = [
                            'bankname',
                            'bankaccount',
                            'bankprovince',
                            'bankcounty'
                        ];
                        var require_msg = "{$tr['confirm all information again']}";
                        // 判斷欄位是否為必填 & 欄位是否有填寫
                        for (var i=0; i<obj_key.length; i++) {
                            var dom = $('#' + dom_id[i]);
                            if ( dom.prop('required') && (dom.val().trim() == '') ) {
                                alert(require_msg);
                                return false;
                            } else {
                                data[ obj_key[i] ] = dom.val();
                            }
                        }
                        return data;
                    }
                </script>
            HTML;

            $submitBtn = <<<HTML
                <button type="button" class="send_btn" disabled>{$tr['not meet the require to be agent']}</button>
            HTML;
            $cooperationAgreementHtml = '';
            if ($memberDataContent['isComplete'] && $bankDataContent['isComplete'] && ($system_config['agency_registration_gcash'] <= $account_and_wallet_data->gcash_balance) ) {
                $cooperationAgreementHtml = <<<HTML
                    <div class="row">
                        <div class="col-12">
                            <div class="checkbox register_checkbox">
                                <label class="d-flex align-items-center">
                                    <input type="checkbox" class="user_check" id="agreementagree" checked>
                                    {$tr['i agreed below']}
                                    <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#cooperationAgreementModal">
                                        {$tr['cooperation registration']}
                                    </button>
                                </label>
                            </div>
                        </div>
                    </div>
                HTML; // 合作协议

                if ( isset($ui_data['copy']['partner'][$_SESSION['lang']]) && ($ui_data['copy']['partner'][$_SESSION['lang']] != '') ) {
                    $cooperationAgreementContent = htmlspecialchars_decode($ui_data['copy']['partner'][$_SESSION['lang']]);
                } else {
                    $cooperationAgreementContent = '
                        <h2 id="01"> 合作协议</h2>
                        <p> 本站与GT DEMO进行技术合作，为柬埔寨合法注册之博采公司。我们采用最为多元、先进、公正的系统，在众多博彩网站中， 我们自豪能为会员提供最优惠的回馈、为代理商创造强劲的营利优势！本站秉持商业联营、资源整合、利益共享的理念， 与合作伙伴携手打造利多的荣景。无论您拥有的是网络资源，或是丰富的人脉，都欢迎您来加入我们的行列，不须负担任何费用， 就可以开拓无上限的营收。绝对是您最聪明的选择！
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

                $cooperationAgreementHtml .= <<<HTML
                    <div class="modal fade" id="cooperationAgreementModal" tabindex="-1" role="dialog" aria-labelledby="cooperationAgreementlLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title" id="cooperationAgreementLabel">{$tr['cooperation registration']}</h4>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span aria-hidden="true">&times;</span>
                                        <span class="sr-only">Close</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    {$cooperationAgreementContent}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="close_bt" data-dismiss="modal">{$tr['off']}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                HTML; // 合作协议

                $submitBtn = <<<HTML
                    <button type="button" class="btn btn-primary send_btn" id="registeragentbtn">{$tr['register agent title']}</button>
                HTML;

                $success_url = ($config['site_style'] == 'mobile') ? './menu_admin.php?gid=agent' : './spread_register.php';
                $extend_js .= <<<HTML
                    <script>
                        // 按下申請成為代理商
                        $(document).on('click', '#registeragentbtn', function() {
                            var pw = $('#password').val();
                            if (pw != '') {
                                var csrftoken = '{$csrftoken}';
                                var agreementAgree = ( $('#agreementagree').prop('checked') ) ? 'Y' : 'N';
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
                                            window.location.href="{$success_url}";
                                        } else {
                                            location.reload();
                                        }
                                    } else {
                                        alert(res.result);
                                    }
                                }).fail(function() {
                                    alert("error");
                                });
                            } else {
                                alert('{$tr["register agent step-enter password"]}');
                            }
                        });
                    </script>
                HTML;
            }
            //請完善以下會員及帳務資料
            $indexbody_content = <<<HTML
                <div class="register_agent_accordion" id="accordionexample">
                    <div class="application_button collapsed" data-toggle="collapse" data-target="#collapseone" aria-expanded="false" aria-controls="collapseone" id="headingone">
                        <div class="d-flex bd-highlight membericon_bt">
                        <p class="mr-auto p-2 bd-highlight">{$tr['member profile']}</p>
                        {$memberDataContentBtn}
                        </div>
                    </div>
                    <div id="collapseone" class="collapse membericon" aria-labelledby="headingone" data-parent="#accordionexample">
                        <div class="card-body">
                            {$memberDataContent['html']}
                            <div class="form-group row">
                                <div class="col-12">
                                    {$memberDataSaveBtn}
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- 銀行卡 -->
                    <div class="application_button collapsed"  data-toggle="collapse" data-target="#collapsetwo" aria-expanded="false" aria-controls="collapsetwo" id="headingtwo">
                        <div class="d-flex bd-highlight">
                        <p class="mr-auto p-2 bd-highlight">{$tr['link bank setting']}</p>
                        {$bankdatacontenticon['html']}
                        </div>
                    </div>
                    <div id="collapsetwo" class="collapse" aria-labelledby="headingtwo" data-parent="#accordionexample">
                        <div class="card-body">
                        <form>
                            {$bankDataContent['html']}
                            <div class="form-group row">
                            <div class="col-12">
                                {$bankDataSaveBtn}
                            </div>
                            </div>
                        </form>
                        </div>
                    </div>
                    <div class="application_button collapsed"  data-toggle="collapse" data-target="#collapsethree" aria-expanded="false" aria-controls="collapsethree"  id="headingthree">
                        <div class="d-flex bd-highlight">
                        <p class="mr-auto p-2 bd-highlight">{$tr['agent franchise fee']}</p>
                            {$agentRegisterCashBtn}
                        </div>
                    </div>
                    <div id="collapsethree" class="collapse" aria-labelledby="headingthree" data-parent="#accordionexample">
                        <div class="card-body">
                        <form>
                            <div class="form-group row">
                            <div class="col-12">
                                {$depositCurrencyWarning}
                            </div>
                            </div>
                            <div class="form-group d-flex align-items-center">
                            <div class="title">{$tr['GCASH balance']}</div>
                            <div class="w-100">
                                <p>\${$account_and_wallet_data->gcash_balance}</p>
                            </div>
                            </div>
                            <div class="form-group d-flex align-items-center">
                            <div class="title">{$tr['agant amount']}：</div>
                            <div class="w-100">
                                <p>\${$system_config['agency_registration_gcash']}</p>
                            </div>
                            </div>
                            <br>
                            <div class="form-group row">
                            <div class="col-12">
                                {$goDepositBtn}
                            </div>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
                <br>
                <div class="application_p">{$tr['register agent step-enter password']}</div>
                    <form class="register_agent_accordion">
                        <div class="form-group application_input">
                            <div class="d-flex align-items-center">
                            <div class="title title_left"><i class="required">*</i>{$tr['member password']}：</div>
                            <div class="form_content w-100">
                            <input type="password" class="form-control" id="password" placeholder="{$tr['please enter password']}">
                            </div>
                            </div>
                        </div>
                        <br>
                    </form>
                {$cooperationAgreementHtml}
                {$submitBtn}
            HTML;
        } else {
            $msg = $tr['member to agent in process']; // '代理申请审核中'
            memberlogtodb($_SESSION['member']->account, 'member', 'warning', $msg, $_SESSION['member']->account, $msg, 'f', 'authority');

            $indexbody_content = <<<HTML
                <div class="alert alert-danger m-mt-10" role="alert">
                <a href="./contactus.php" class="alert-link">{$tr['member to agent note']}</a>
                </div>
            HTML;
            // 代理申请审核中，目如有疑问请联络客服。
        }
    } else {
        $msg = $tr['register agent closed'];//'申请成为代理商目前关闭中'
        memberlogtodb($_SESSION['member']->account, 'member', 'warning', $msg, $_SESSION['member']->account, $msg, 'f', 'authority');

        $indexbody_content = <<<HTML
            <div class="alert alert-danger" role="alert">
            <a href="./contactus.php" class="alert-link">{$tr['register agent closed']},{$tr['If you have any questions, please contact us.']}</a>
            </div>
        HTML;
        //申请成为代理商目前关闭中，如有疑问请联络客服
    }
} else { //会员资料查询错误
    $msg = $tr['data error,or account not found'];//'会员资料查询错误'
    memberlogtodb($_SESSION['member']->account, 'member', 'error', $msg, $_SESSION['member']->account, $msg, 'f', 'authority');

    $indexbody_content = <<<HTML
        <div class="alert alert-danger" role="alert">
            {$tr['data error,or account not found']}
        </div>
    HTML;
}

$indexbody_content = <<<HTML
    <div class="main_content main_content_register">
        {$indexbody_content}
    </div>
HTML;

// ----------------------------------------------------------------------------
// 準備填入的內容
// ----------------------------------------------------------------------------
// 將內容塞到 html meta 的關鍵字, SEO 加強使用
$tmpl['html_meta_description'] = $config['companyShortName'];
$tmpl['html_meta_author'] = $config['companyShortName'];
$tmpl['html_meta_title'] = $function_title.'-'.$config['companyShortName'];

// 系統訊息顯示
$tmpl['message'] = $messages;
// 擴充再 head 的內容 可以是 js or css
$tmpl['extend_head'] = $extend_head;
// 擴充於檔案末端的 Javascript
$tmpl['extend_js'] = $extend_js;
// 主要內容 -- title
$tmpl['paneltitle_content'] = $navigational_hierarchy_html;
// 主要內容 -- content
$tmpl['panelbody_content'] = $indexbody_content;
// banner標題
$tmpl['banner'] = ['membercenter_register_agent'];
// menu增加active
$tmpl['menu_active'] =['register_agent.php'];

// ----------------------------------------------------------------------------
// 填入內容結束。底下為頁面的樣板。以變數型態塞入變數顯示。
// ----------------------------------------------------------------------------
include($config['template_path']."template/admin.tmpl.php");
?>
