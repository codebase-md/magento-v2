<?php

namespace UnzerDirect\Gateway\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CaptureOrderShipmentAfter implements ObserverInterface
{
    const SHIPMENT_AUTO_CAPTURE_XML_PATH     = 'payment/unzerdirect_gateway/autocapture_shipment';

    /**
     * @var UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter
     */
    protected $adapter;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter $adapter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->adapter = $adapter;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $autocapture = $this->scopeConfig->getValue(self::SHIPMENT_AUTO_CAPTURE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if($autocapture) {
            $shipment = $observer->getEvent()->getShipment();
            /** @var \Magento\Sales\Model\Order $order */
            $order = $shipment->getOrder();

            $payment = $order->getPayment();
            if (strpos($payment->getMethod(), 'unzerdirect') !== false) {
                $parts = explode('-', $payment->getLastTransId());
                $order = $payment->getOrder();
                $transaction = $parts[0];

                try {
                    $this->adapter->capture($order, $transaction, $order->getGrandTotal());
                } catch (LocalizedException $e) {
                    //throw new LocalizedException(__($e->getMessage()));
                }
            }
        }
    }
}