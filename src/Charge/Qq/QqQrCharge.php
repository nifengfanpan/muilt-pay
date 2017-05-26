<?php
/**
 * Created by PhpStorm.
 * User: Evcehiack
 * Date: 2017/5/25
 * Time: 23:42
 */

namespace Payment\Charge\Qq;


use Payment\Common\BaseData;
use Payment\Common\Qq\Data\Charge\QrChargeData;
use Payment\Common\Qq\QqBaseStrategy;

class QqQrCharge extends QqBaseStrategy
{


    /**
     * 获取支付对应的数据完成类
     * @return BaseData
     * @author helei
     */
    public function getBuildDataClass()
    {
        // TODO: Implement getBuildDataClass() method.
        $this->config->tradeType = 'NATIVE';// 微信文档这里写错了
        return QrChargeData::class;
    }

    protected function retData(array $ret)
    {
        if ($this->config->returnRaw) {
            return $ret;
        }
        // 扫码支付，返回链接
        return $ret['code_url'];
    }
}