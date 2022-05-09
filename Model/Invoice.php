<?php

namespace UnzerDirect\Gateway\Model;

/**
 * Pay In Store payment method model
 */
class Invoice extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var int
     */
    protected static $_cartAmountMin = 10;

    /**
     * @var int
     */
    protected static $_cartAmountMax = 3500;

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'unzerdirect_invoice';

    /**
     * @var string
     */
    protected $_title = 'Unzer Direct Invoice';

    /**
     * @var string[]
     */
    protected $_allowCurrencyCode = array(
        'EUR', 'CHF'
    );

    /**
     * Availability option
     *
     * @var bool
     */

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canCapture              = true;

    /**
     * @var bool
     */
    protected $_canRefund               = true;

    /**
     * @var bool
     */
    protected $_isGateway               = true;

    /**
     * @var bool
     */
    protected $_canUseForMultishipping  = false;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @param $lan
     * @return mixed
     */
    public function calcLanguage($lan)
    {
        $map_codes = array (
            'nb' => 'no',
            'nn' => 'no'
        );

        $splitted = explode('_', $lan);
        $lang = $splitted[0];
        if ( isset ( $map_codes[$lang] ) ) return $map_codes[$lang];
        return $lang;
    }

    /**
     * @param $quote
     * @return false
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if($quote) {
            if (!in_array($quote->getBaseCurrencyCode(), $this->_allowCurrencyCode)) {
                return false;
            }

            if($quote->getGrandTotal() < self::$_cartAmountMin
                || $quote->getGrandTotal() > self::$_cartAmountMax) {
                return false;
            }
        }
        return parent::isAvailable($quote);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $adapter = $objectManager->get(\UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter::class);
        $parts = explode('-',$payment->getTransactionId());
        $order = $payment->getOrder();
        $transaction = $parts[0];

        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        try {
            $adapter->capture($order, $transaction, $amount);
        } catch (LocalizedException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $adapter = $objectManager->get(\UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter::class);
        $parts = explode('-',$payment->getTransactionId());
        $order = $payment->getOrder();
        $transaction = $parts[0];

        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }

        try {
            $adapter->refund($order, $transaction, $amount);
        } catch (LocalizedException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $adapter = $objectManager->get(\UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter::class);
        $parts = explode('-',$payment->getTransactionId());
        $order = $payment->getOrder();
        $transaction = $parts[0];

        if($transaction) {
            try {
                $adapter->cancel($order, $transaction);
            } catch (LocalizedException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions(){
        return trim($this->getConfigData('instructions'));
    }
}
