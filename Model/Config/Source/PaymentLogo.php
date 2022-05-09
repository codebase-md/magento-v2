<?php

namespace UnzerDirect\Gateway\Model\Config\Source;

class PaymentLogo implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'visa',
                'label' => __('VISA')
            ],
            [
                'value' => 'mastercard',
                'label' => __('MasterCard')
            ],
            [
                'value' => 'maestro',
                'label' => __('Maestro')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '' => __('All Payment Methods'),
            'creditcard' => __('All Creditcards'),
            'specified' => __('As Specified')
        ];
    }
}
