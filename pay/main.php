<?php

  require_once __DIR__ . '/common/lib_deposition.php';

  /**
   *
   */
  interface IPaymentGateway
  {
    /**
     * [sendPayment description]
     * @param  array $data [description]
     * @return [array]       ['payment_form' => '跳轉的html', 'trancaction_id' => '第三方支付的單號']
     */
    public function createPayment(array $data);

    public function notifyHandler($data);

    public function checkPaymentStatus($merchantOrderNo, $amt);
  }


  /**
   *
   */
  class PaymentGatewayFactory
  {
    /**
     * [getPaymentGateway description]
     * @param  [type] $paymentGatewayName [description]
     * @param  array  $config             [from root_deposit_onlinepayment table] => [payname, name, hashiv, hashkey, merchantid, merchantname, pay_channel]
     * @return [IPaymentGateway]          [description]
     */
    static function getPaymentGateway($paymentGatewayName, $configObj)
    {
      require_once __DIR__ . '/' . $paymentGatewayName . '/' . 'PaymentGateway.php';

      $class_name = 'Payment\\' . $paymentGatewayName . '\PaymentGateway';

      return new $class_name($configObj);
    }

  }


?>
