<?php
/**
 * Created by PhpStorm.
 * User: helei
 * Date: 16/7/31
 * Time: 上午8:49
 */

namespace Payment\Common\Qq\Data\Charge;

use Payment\Common\PayException;
use Payment\Utils\ArrayUtil;

/**
 * Class WebChargeData
 *
 * @inheritdoc
 * @property string $product_id  扫码支付时,必须设置该参数
 * @property string $openid  trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识
 *
 * @package Payment\Common\Qq\Data\Charge
 */
class QrChargeData extends ChargeBaseData
{

    /**
     * 生成下单的数据
     */
    protected function buildData()
    {
        $signData = [
            // 基本数据
            'appid' => trim($this->appId),
            'mch_id'    => trim($this->mchId),
            'nonce_str' => $this->nonceStr,
            'fee_type'  => $this->feeType,
            'notify_url'    => $this->notifyUrl,
            'trade_type'    => $this->tradeType, //设置APP支付
            'limit_pay' => $this->limitPay,  // 指定不使用信用卡
            // 业务数据
            'body'  => trim($this->subject),
            //'detail' => json_encode($this->body, JSON_UNESCAPED_UNICODE);
            'attach'    => trim($this->return_param),
            'out_trade_no'  => trim($this->order_no),
            'total_fee' => $this->amount,
            'spbill_create_ip'  => trim($this->client_ip),
            'time_start'    => $this->timeStart,
            'time_expire'   => $this->timeout_express,
        ];

        // 移除数组中的空值
        $this->retData = ArrayUtil::paraFilter($signData);
    }

}
