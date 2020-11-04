<?php
/*---------------------------------------------------------------------------------------------------------------------
  Features:  針對 deposit.php 的對應動作檔案
  File Name: deposit_action.php
  Author:    WebbLu
  Related:   deposit.php
  Info:
    接受參數 a={your_action}, 對應到 請求方法 + camel action
    e.g. a=pay_service, with get request method
    mapping 到 getPayService()
---------------------------------------------------------------------------------------------------------------------*/

// 主機及資料庫設定
require_once dirname(__FILE__) . "/config.php";

// 支援多國語系
require_once dirname(__FILE__) . "/i18n/language.php";

// 自訂函式庫
require_once dirname(__FILE__) . "/lib.php";
require_once __DIR__ . '/site_api/lib_api.php';
require_once __DIR__ . '/lib_errorcode.php';

// rabbitMQ
require_once __DIR__ . '/Utils/RabbitMQ/Publish.php';
require_once __DIR__ . '/Utils/MessageTransform.php';

RegSession2RedisDB();

use Onlinepay\SDK\PaymentGateway;

// 可補上安全驗證與參數過濾
!isset($_SESSION['member']) and die($tr['login first']);
!in_array($_SESSION['member']->therole, ['M', 'A', 'T']) and die($tr['manager can not use this function']);
$_SESSION['member']->status !== '1' and die("{$tr['account']}{$tr['disabled']}");

Controller::dispatch('deposit', $config);

/*-------------------------------------------------------------------------------------------------------------------*/
class DepositController extends Controller {
    private $config; // 會用到後台的網址位置

    function __construct( $config ){
        $this->config = $config;
    }

    public function postGetPayLink(Request $request, Response $response){
        // 預設帳號的電話、Email、信用卡資訊(暫時留白，因為資料表內沒有該欄位)
        $phone = '';
        $email = '';
        $credit_card_no = '';

        $this->isCsrfValid();
        $account = $_SESSION['member']->account;
        $name = $_SESSION['member']->nickname;

        $sql = <<<SQL
            SELECT mobilenumber, email
            FROM root_member
            WHERE (account = '{$account}')
            LIMIT 1;
        SQL;
        $result = runSQLall($sql, 0);
        if( $result[0] == 1 ){
            $phone = $result[1]->mobilenumber;
            $email = $result[1]->email;
        }


        $onlinepayGateway = new Onlinepay\SDK\PaymentGateway($this->config['gpk2_pay']);
        $payservice = ( ($request->getParam('payservice') == null) ? '' : $request->getParam('payservice') ); // 付款方式
        $provider = ( ($request->getParam('provider') == null) ? '' : $request->getParam('provider') ); // 金流商
        $bankcode = $request->getParam('bank');

        // todo: 加入檢測金額是否符合會員等級限制
        $amount = $request->getParam('amount'); // 儲值金額

        // todo: 這參數金流之後會用到 desktop/mobile
        $device = $this->config['site_style']; // 瀏覽裝置

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || getenv('HTTP_X_FORWARDED_PROTO') === 'https') ? "https://" : "http://";

        $website_domainname = str_replace($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_HOST'], __DIR__);

        $return_url = $protocol . $website_domainname . "/mo_translog_query.php?transpage=m&id={$_SESSION['member']->id}";

        // 判斷後台參數是否有設定，並初始化 $server_url 參數。
        if( isset($this->config['besite_domainname']) && !empty($this->config['besite_domainname']) ){
            $server_url = "https://{$this->config['besite_domainname']}/site_api/gateway.php";
        }
        else{
            die('尚未設定 config 的 besite_domainname 參數！');
        }

        ${"options[bank_swift_code]"} = $bankcode;

        // 後台自己查單以及訂閱 mqtt
        // $server_url = "https://$config[besite_domainname]/site_api/gateway.php?a=deposit";

        $data = compact(
            'account',
            'payservice',
            'amount',
            'name',
            'device',
            'return_url',
            'provider',
            'server_url',
            'phone',
            'email',
            'credit_card_no',
            'options[bank_swift_code]',
        );

        $protalsetting = runSQLall_prepared(<<<SQL
            SELECT *
            FROM "root_protalsetting"
            WHERE ("setttingname" = :settingname) AND
                  ("status" = 1)
        SQL, [':settingname' => 'default']);

        array_walk($protalsetting, function (&$v, $k) use (&$protalsetting) {
            $protalsetting[$v->name] = $v;
            if (is_numeric($k)) {
                unset($protalsetting[$k]);
            }
        },);

        try {
            // 執行建立訂單
            $apiResponse = $onlinepayGateway->postDeposit($data);
            $apiStatus = $apiResponse->status;
            $apiData = $apiResponse->data;

            // 判斷執行後的Response Status
            if( isset($apiStatus->code) && isset($apiStatus->message) ){
                if( $apiStatus->code != 0 ){
                    return $response->withRaw(<<<HTML
                        <script>
                            alert("订单请求失败({$apiStatus->code}:{$apiStatus->message})，如果错误持续发生，请联系客服为您服务");
                            history.go(-1);
                        </script>
                    HTML);
                }
            }
            // 沒有接收到訂單狀態代號 & 訊息
            else{
                return $response->withRaw(<<<HTML
                    <script>
                        alert("订单请求失败，如果错误持续发生，请联系客服为您服务");
                        history.go(-1);
                    </script>
                HTML);
            }

            // 判斷建立訂單後，是否有回傳所需資訊，沒有的話直接跳錯誤訊息
            if( !isset($apiData->order_no) || !isset($apiData->account) || !isset($apiData->amount) || !isset($apiData->description) ){
                return $response->withRaw(<<<HTML
                    <script>
                        alert("订单请求失败，如果错误持续发生，请联系客服为您服务");
                        history.go(-1);
                    </script>
                HTML);
            }

            global $member_grade_config_detail, $tr;

            if (is_null($member_grade_config_detail->pointcardfee_member_rate)) {
                throw new Exception($tr['the deposit fee rate of member grade not set, please contact us'], ErrorCode::SOMETHING_WRONG);
            }

            $fee = round($data['amount'] * $member_grade_config_detail->pointcardfee_member_rate / 100, 2);

            // 紀錄訂單資訊
            $apiDepositOrder = new ApiDepositOrder;
            $apiDepositOrder->custom_transaction_id = $apiData->order_no;
            $apiDepositOrder->account = $data['account'];
            $apiDepositOrder->amount = $data['amount'];
            $apiDepositOrder->currency_type = $protalsetting['member_deposit_currency']->value;
            $apiDepositOrder->request_time = $apiDepositOrder->requestTime();
            $apiDepositOrder->transaction_time = $apiData->created_time;
            $apiDepositOrder->transactioninfo_json = json_encode($apiData);
            $apiDepositOrder->title = $apiData->description;
            $apiDepositOrder->status = 0;
            $apiDepositOrder->agent_ip = $apiDepositOrder->getRemoteIP();
            $apiDepositOrder->script_name = filter_var($_SERVER['SCRIPT_NAME'], FILTER_SANITIZE_ADD_SLASHES) ?? '';
            $apiDepositOrder->site_account_name = $this->config['gpk2_pay']['apiKey'];
            $apiDepositOrder->api_transaction_fee = $fee;
            $apiDepositOrder->add();

            // send to rabbitMQ
            $mq = Publish::getInstance();
            $msg = MessageTransform::getInstance();
            $notifyMsg = $msg->notifyMsg('OnlinePay', $_SESSION['member']->account, date("Y-m-d H:i:s"));
            $notifyResult = $mq->fanoutNotify('msg_notify', $notifyMsg);
            // $notifyResult = $mq->directAdd('direct_test', 'direct_test', $notifyMsg);
        }
        catch (\Exception $e) {
            return $response->withRaw(<<<HTML
                <script>
                    console.log('{$e->getMessage()}');
                    alert("订单请求失败，如果错误持续发生，请联系客服为您服务");
                    history.go(-1);
                </script>
            HTML);
        }

        $response->withJson([
            'status' => [
                'code' => 200,
                'message' => '订单链接取得成功',
                'timestamp' => time()
            ],
            'data' => $apiData,
            'rabmq' => $notifyResult
        ]);

        return $response->withHeader('Location', $apiData->pay_url);
    }

    // 回傳可用支付方式
    public function getGetServices(Request $request, Response $response) {
        $onlinepayGateway = new Onlinepay\SDK\PaymentGateway($this->config['gpk2_pay']);
        $apiResult = $onlinepayGateway->getServiceList();
        $payservices = !$apiResult ? [] : $onlinepayGateway->getServiceList()->data;
        return $response->withJson($payservices);
    }
}

/*-------------------------------------------------------------------------------------------------------------------*/
class Controller {
    protected $container;

    public function __construct($container = null) {
        $container = new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
        $container->config = $GLOBALS['config'];
        $this->container = $container;
    }

    public function __get($property){
        return $this->$property ?? $this->container->$property ?? null;
    }

    public static function dispatch(string $mod = '', $config){
        $request = Request::getInstance();
        $response = Response::getInstance();

        $action = filter_var($_GET['a'] ?? '', FILTER_SANITIZE_STRING);
        $class = $mod ? ucfirst(strtolower($mod)) . 'Controller' : 'BaseController';
        /* ↓ e.g. postGetPayLink */
        $method = strtolower($_SERVER['REQUEST_METHOD']) . str_replace('_', '', ucwords($action, '_'));

        if ( class_exists($class) ) {
            if( $class == 'DepositController' ){
                $controller = new $class($config);
            }
            else{
                $controller = new $class;
            }

            if( method_exists($controller, $method) ){
                $controller->$method($request, $response);
            }
            else{
                $controller->index($request, $response);
            }
        }
        return $response->respond();
    }

    public function index(Request $request, Response $response) {
        return $response->withJson(['msg' => "you see 'hello, world!'"], 200);
    }

    final protected function isCsrfValid() {
        $csrftoken_ret = csrf_action_check();
        if ($csrftoken_ret['code'] != 1) {
            die($csrftoken_ret['messages']);
        }
    }

    protected function getGlobalConfig() {
        return $GLOBALS['config'];
    }
}
/*-------------------------------------------------------------------------------------------------------------------*/
class Request {
    private static $instance = null;
    private $query_params = [];
    private $parsed_body = [];

    private function __construct() {
        foreach ($_GET as $k => $v) {
            $this->query_params[$k] = $v;
            is_numeric($v) and $this->query_params[$k] = (is_int($v)) ? intval($v) : floatval($v);
            is_string($v) and $this->query_params[$k] = filter_var($v, FILTER_SANITIZE_STRING);
        }

        foreach ($_POST as $k => $v) {
            $this->parsed_body[$k] = $v;
            is_numeric($v) and $this->parsed_body[$k] = (is_int($v)) ? intval($v) : floatval($v);
            is_string($v) and $this->parsed_body[$k] = filter_var($v, FILTER_SANITIZE_STRING);
        }
        // array(3) { ["payservice"]=> string(13) "ddm_qrcodepay" ["amount"]=> string(3) "100" ["csrftoken"]=> string(217) "eyJSRU1PVEVfQUREUiI6IjEwLjIyLjExNC4xMjIiLCJQSFBfU0VMRiI6IlwvZ3BrMmRldlwvZGVwb3NpdC5waHAiLCJkYXRhIjpudWxsLCJmaW5nZXJ0cmFja2VyIjoiZGE2NmRhODU0MDNlY2QyODA2NWZkN2YzZmRlNGQwZjQifQ==_ac8a3dd5af2a3df2dafb1d4214712f8117f10a07" }
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function getQueryParams() {
        return self::$instance->query_params;
    }

    public function getParsedBody() {
        return self::$instance->parsed_body;
    }

    public function getParam(string $key)
    {
        $params = $this->getQueryParams() + $this->getParsedBody();
        return $params[$key] ?? '';
    }
}
/*-------------------------------------------------------------------------------------------------------------------*/
class Response {
    private $status_code = 200;
    private $headers = [];
    private $body = '';
    private static $instance;

    private function __construct() {
        ob_start();
    }

    public static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function withHeader($header, $value) {
        self::$instance->headers[$header] = [$value];
        // var_dump(self::$instance->headers);
        return $this;
    }

    public function withStatus($status_code) {
        self::$instance->status_code = $status_code;
        return $this;
    }

    public function withRedirect($url, $status_code)
    {
        self::$instance->headers = [];
        $this->withHeader('Location', $url);
        $this->respond();
        exit;
    }

    public function respond() {
        if (!headers_sent()) {
        foreach ($this->getHeaders() as $name => $values) {
            $first = stripos($name, 'Set-Cookie') === 0 ? false : true;
            foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), $first);
            $first = false;
            }
        }

        // Status
        header(sprintf(
            'HTTP/%s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode()
        ), true, $this->getStatusCode());
        }

        echo self::$instance->body;

        ob_flush();
        ob_end_clean();
        flush();
    }

    public function getHeaders() {
        return self::$instance->headers;
    }

    public function getStatusCode() {
        return self::$instance->status_code;
    }

    public function getBody() {
        return self::$instance->body;
    }

    public function withRaw(string $body) {
        self::$instance->body = $body;
        return $this;
    }

    public function withJson(array $data, int $status_code = null, int $encode_options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) {
        if (!is_null($status_code)) {
        self::$instance->status_code = $status_code;
        }
        $this->withHeader('Content-Type', 'application/json');
        self::$instance->body = json_encode($data, $encode_options);
        return $this;
    }

    public function getProtocolVersion() {
        return '1.1';
    }
}
