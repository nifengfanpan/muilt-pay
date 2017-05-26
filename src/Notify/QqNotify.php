<?php
/**
 * Created by PhpStorm.
 * User: Evcehiack
 * Date: 2017/5/26
 * Time: 8:36
 */

namespace Payment\Notify;


use Payment\Common\PayException;
use Payment\Common\QqConfig;
use Payment\Config;
use Payment\Utils\ArrayUtil;
use Payment\Utils\DataParser;

class QqNotify extends NotifyStrategy
{

    /**
     * QqNotify constructor.
     * @param array $config
     * @throws PayException
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        try {
            $this->config = new QqConfig($config);
        } catch (PayException $e) {
            throw $e;
        }
    }
    /**
     * 获取移除通知的数据  并进行简单处理（如：格式化为数组）
     *
     * 如果获取数据失败，返回false
     *
     * @return array|false
     * @author helei
     */
    public function getNotifyData()
    {
        // TODO: Implement getNotifyData() method.
        // php://input 带来的内存压力更小
        $data = @file_get_contents('php://input');// 等同于微信提供的：$GLOBALS['HTTP_RAW_POST_DATA']
        // 将xml数据格式化为数组
        $arrData = DataParser::toArray($data);
        if (empty($arrData)) {
            return false;
        }

        // 移除值中的空格  xml转化为数组时，CDATA 数据会被带入额外的空格。
        $arrData = ArrayUtil::paraFilter($arrData);

        return $arrData;
    }

    /**
     * 检查异步通知的数据是否合法
     *
     * 如果检查失败，返回false
     *
     * @param array $data 由 $this->getNotifyData() 返回的数据
     * @return boolean
     * @author helei
     */
    public function checkNotifyData(array $data)
    {
        // TODO: Implement checkNotifyData() method.
        if ($data['return_code'] != 'SUCCESS' || $data['result_code'] != 'SUCCESS') {
            // $arrData['return_msg']  返回信息，如非空，为错误原因
            // $data['result_code'] != 'SUCCESS'  表示业务失败
            return false;
        }

        // 检查返回数据签名是否正确
        return $this->verifySign($data);
    }

    /**
     * 向客户端返回必要的数据
     * @param array $data 回调机构返回的回调通知数据
     * @return array|false
     * @author helei
     */
    protected function getRetData(array $data)
    {
        // TODO: Implement getRetData() method.
        if ($this->config->returnRaw) {
            $data['channel'] = Config::WX_CHARGE;
            return $data;
        }

        // 将金额处理为元
        $totalFee = bcdiv($data['total_fee'], 100, 2);
        $cashFee = bcdiv($data['cash_fee'], 100, 2);

        $retData = [
            'bank_type' => $data['bank_type'],
            'cash_fee' => $cashFee,
            'device_info' => $data['device_info'],
            'fee_type' => $data['fee_type'],
            'is_subscribe' => $data['is_subscribe'],
            'buyer_id'   => $data['openid'],
            'order_no'   => $data['out_trade_no'],
            'pay_time'   => date('Y-m-d H:i:s', strtotime($data['time_end'])),// 支付完成时间
            'amount'   => $totalFee,
            'trade_type' => $data['trade_type'],
            'transaction_id'   => $data['transaction_id'],
            'trade_state'   => strtolower($data['return_code']),
            'channel'   => Config::WX_CHARGE,
        ];

        // 检查是否存在用户自定义参数
        if (isset($data['attach']) && ! empty($data['attach'])) {
            $retData['return_param'] = $data['attach'];
        }

        return $retData;

    }



    /**
     * 根据返回结果，回答支付机构。是否回调通知成功
     * @param boolean $flag 每次返回的bool值
     * @param string $msg 通知信息，错误原因
     * @return mixed
     * @author helei
     */
    protected function replyNotify($flag, $msg = 'OK')
    {
        // TODO: Implement replyNotify() method.
        // 默认为成功
        $result = [
            'return_code'   => 'SUCCESS',
            'return_msg'    => 'OK',
        ];
        if (! $flag) {
            // 失败
            $result = [
                'return_code'   => 'FAIL',
                'return_msg'    => $msg,
            ];
        }

        return DataParser::toXml($result);
    }


    /**
     * @param $data
     * @return bool
     */
    private function verifySign(array  $retData)
    {
        $retSign = $retData['sign'];
        $values = ArrayUtil::removeKeys($retData, ['sign', 'sign_type']);

        $values = ArrayUtil::paraFilter($values);

        $values = ArrayUtil::arraySort($values);

        $signStr = ArrayUtil::createLinkstring($values);

        $signStr .= '&key=' . $this->config->md5Key;
        switch ($this->config->signType) {
            case 'MD5':
                $sign = md5($signStr);
                break;
            case 'HMAC-SHA256':
                $sign = hash_hmac('sha256', $signStr, $this->config->md5Key);
                break;
            default:
                $sign = '';
        }
        return strtoupper($sign) === $retSign;
    }
}