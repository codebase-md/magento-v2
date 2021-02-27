<?php

namespace UnzerDirect\Gateway\Block\Order\Payment;

class Info extends \Magento\Payment\Block\Info{
    protected function _prepareSpecificInformation($transport = null)
    {
        if($this->getMethod()->getCode() == 'unzerdirect_gateway'){
            $payment = $this->getInfo();
            $additional = $payment->getAdditionalInformation();
            if(isset($additional['method_title'])){
                unset($additional['method_title']);
            }
            $transport = $additional;
        }

        return parent::_prepareSpecificInformation($transport);
    }
}