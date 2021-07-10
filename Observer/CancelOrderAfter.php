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
        if (strpos($payment->getMethod(), 'unzerdirect') !== false) {
            $parts = explode('-', $payment->getLastTransId());
            $order = $payment->getOrder();
            $transaction = $parts[0];

            if($transaction) {
                try {
                    $this->adapter->cancel($order, $transaction);
                } catch (LocalizedException $e) {
                    throw new LocalizedException(__($e->getMessage()));
                }
            }
        }
    }
}