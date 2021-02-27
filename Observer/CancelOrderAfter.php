<?php

namespace UnzerDirect\Gateway\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CancelOrderAfter implements ObserverInterface
{
    /**
     * @var UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter
     */
    protected $adapter;

    public function __construct(
        \UnzerDirect\Gateway\Model\Adapter\UnzerDirectAdapter $adapter
    )
    {
        $this->adapter = $adapter;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $payment = $order->getPayment();
        if ($payment->getMethod() === \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE) {
            $parts = explode('-', $payment->getLastTransId());
            $order = $payment->getOrder();
            $transaction = $parts[0];

            try {
                $this->adapter->cancel($order, $transaction);
            } catch (LocalizedException $e) {
                throw new LocalizedException(__($e->getMessage()));
            }
        }
    }
}