<?php
namespace UnzerDirect\Gateway\Model\Adapter;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\ResourceInterface;
use QuickPay\QuickPay;
use Zend_Locale;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;

/**
 * Class UnzerDirectAdapter
 */
class UnzerDirectAdapter
{
    const PUBLIC_KEY_XML_PATH      = 'payment/unzerdirect_gateway/apikey';
    const TRANSACTION_FEE_XML_PATH = 'payment/unzerdirect_gateway/transaction_fee';
    const AUTOCAPTURE_XML_PATH = 'payment/unzerdirect_gateway/autocapture';
    const TEXT_ON_STATEMENT_XML_PATH = 'payment/unzerdirect_gateway/text_on_statement';
    const PAYMENT_METHODS_XML_PATH = 'payment/unzerdirect_gateway/payment_methods';
    const SPECIFIED_PAYMENT_METHOD_XML_PATH = 'payment/unzerdirect_gateway/payment_method_specified';
    const BRANDING_ID_XML_PATH = 'payment/unzerdirect_gateway/branding_id';
    const TEST_MODE_XML_PATH = 'payment/unzerdirect_gateway/testmode';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $dir;

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $moduleResource;

    /**
     * @var Item
     */
    protected $taxItem;

    /**
     * UnzerDirectAdapter constructor.
     *
     * @param LoggerInterface $logger
     * @param UrlInterface $url
     * @param ScopeConfigInterface $scopeConfig
     * @param ResolverInterface $resolver
     */
    public function __construct(
        LoggerInterface $logger,
        UrlInterface $url,
        ScopeConfigInterface $scopeConfig,
        ResolverInterface $resolver,
        OrderRepositoryInterface $orderRepository,
        BuilderInterface $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        ResourceInterface $moduleResource,
        DirectoryList $dir,
        Item $taxItem
    )
    {
        $this->logger = $logger;
        $this->url = $url;
        $this->scopeConfig = $scopeConfig;
        $this->resolver = $resolver;
        $this->orderRepository = $orderRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->transactionRepository = $transactionRepository;
        $this->moduleResource = $moduleResource;
        $this->dir = $dir;
        $this->taxItem = $taxItem;

        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler($this->dir->getRoot().'/var/log/unzerdirect.log'));
    }

    /**
     * create payment link
     *
     * @param array $attributes
     * @return array|bool
     */
    public function CreatePaymentLink($order, $area = 'frontend')
    {
        try {
            $response = [];
            $this->logger->debug('CREATE PAYMENT');

            $api_key = $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $client = new QuickPay(":{$api_key}");

            $form = [
                'order_id' => $order->getIncrementId(),
                'currency' => $order->getOrderCurrency()->ToString(),
            ];

            if ($textOnStatement = $this->scopeConfig->getValue(self::TEXT_ON_STATEMENT_XML_PATH)) {
                $form['text_on_statement'] = $textOnStatement;
            }

            if($order->getPayment()->getMethod() != \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_PAYPAL) {
                $shippingAddress = $order->getShippingAddress();

                $taxItems = $this->taxItem->getTaxItemsByOrderId($order->getId());
                $shippingVatRate = 0;
                if (is_array($taxItems)) {
                    if (!empty($taxItems)) {
                        foreach ($taxItems as $item) {
                            if ($item['taxable_item_type'] === 'shipping') {
                                $shippingVatRate = $item['tax_percent'];
                            }
                        }
                    }
                }

                if ($shippingAddress) {
                    $form['shipping_address'] = [];
                    $form['shipping_address']['name'] = $shippingAddress->getFirstName() . " " . $shippingAddress->getLastName();
                    $form['shipping_address']['street'] = $shippingAddress->getStreetLine(1);
                    $form['shipping_address']['city'] = $shippingAddress->getCity();
                    $form['shipping_address']['zip_code'] = $shippingAddress->getPostcode();
                    $form['shipping_address']['region'] = $shippingAddress->getRegionCode();
                    $form['shipping_address']['country_code'] = Zend_Locale::getTranslation($shippingAddress->getCountryId(), 'Alpha3ToTerritory');
                    $form['shipping_address']['phone_number'] = $shippingAddress->getTelephone();
                    $form['shipping_address']['email'] = $shippingAddress->getEmail();
                    $form['shipping_address']['house_number'] = '';
                    $form['shipping_address']['house_extension'] = '';
                    $form['shipping_address']['mobile_number'] = '';
                }

                $form['shipping'] = [
                    'amount' => $order->getShippingInclTax() * 100,
                    'vat_rate' => $shippingVatRate ? $shippingVatRate / 100 : 0
                ];

                $billingAddress = $order->getBillingAddress();
                $form['invoice_address'] = [];
                $form['invoice_address']['name'] = $billingAddress->getFirstName() . " " . $billingAddress->getLastName();
                $form['invoice_address']['street'] = implode(' ', $billingAddress->getStreet());
                $form['invoice_address']['city'] = $billingAddress->getCity();
                $form['invoice_address']['zip_code'] = $billingAddress->getPostcode();
                $form['invoice_address']['region'] = $billingAddress->getRegionCode();
                $form['invoice_address']['country_code'] = Zend_Locale::getTranslation($billingAddress->getCountryId(), 'Alpha3ToTerritory');
                $form['invoice_address']['phone_number'] = $billingAddress->getTelephone();
                $form['invoice_address']['email'] = $billingAddress->getEmail();
                $form['invoice_address']['house_number'] = '';
                $form['invoice_address']['house_extension'] = '';
                $form['invoice_address']['mobile_number'] = '';

                //Build basket array
                $form['basket'] = [];
                foreach ($order->getAllVisibleItems() as $item) {
                    $form['basket'][] = [
                        'qty' => (int)$item->getQtyOrdered(),
                        'item_no' => $item->getSku(),
                        'item_name' => $item->getName(),
                        'item_price' => $item->getBasePriceInclTax() * 100,
                        'vat_rate' => $item->getTaxPercent() ? $item->getTaxPercent() / 100 : 0
                    ];
                }
            }

            $form['shopsystem'] = [];
            $form['shopsystem']['name'] = 'Magento 2';
            $form['shopsystem']['version'] = $this->moduleResource->getDbVersion('UnzerDirect_Gateway');

            $payments = $client->request->post('/payments', $form);

            $paymentArray = $payments->asArray();

            $this->logger->debug(json_encode($paymentArray));

            if(!empty($paymentArray['error_code'])){
                $response['message'] = $paymentArray['message'];
                return $response;
            }

            $paymentId = $paymentArray['id'];

            if($order->getPayment()->getMethod() == \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_KLARNA) {
                $paymentMethods = 'klarna-payments';
            } elseif($order->getPayment()->getMethod() == \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_MOBILEPAY) {
                $paymentMethods = 'mobilepay';
            } elseif($order->getPayment()->getMethod() == \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_VIPPS){
                $paymentMethods = 'vipps';
            } elseif($order->getPayment()->getMethod() == \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_PAYPAL) {
                $paymentMethods = 'paypal';
            } elseif($order->getPayment()->getMethod() == \UnzerDirect\Gateway\Model\Ui\ConfigProvider::CODE_VIABILL) {
                $paymentMethods = 'viabill';
            } else {
                $paymentMethods = $this->getPaymentMethods();
            }

            $parameters = [
                "amount"             => $order->getTotalDue() * 100,
                "continueurl"        => $this->url->getUrl('unzerdirect/payment/returns'),
                "cancelurl"          => $this->url->getUrl('unzerdirect/payment/cancel'),
                "callbackurl"        => $this->url->getUrl('unzerdirect/payment/callback'),
                "customer_email"     => $order->getCustomerEmail(),
                "autocapture"        => $this->scopeConfig->isSetFlag(self::AUTOCAPTURE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "payment_methods"    => $paymentMethods,
                "branding_id"        => $this->scopeConfig->getValue(self::BRANDING_ID_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "language"           => $this->getLanguage(),
                "auto_fee"           => $this->scopeConfig->isSetFlag(self::TRANSACTION_FEE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "testmode"           => $this->scopeConfig->isSetFlag(self::TEST_MODE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            if($area == 'adminhtml'){
                $parameters['continueurl'] = $this->url->getBaseUrl().'unzerdirect/payment/returns?area=admin';
                $parameters['cancelurl'] = $this->url->getBaseUrl().'unzerdirect/payment/cancel?area=admin';
                $parameters['callbackurl'] = $this->url->getBaseUrl().'unzerdirect/payment/callback';
            }

            //Create payment link and return payment id
            $paymentLink = $client->request->put(sprintf('/payments/%s/link', $paymentId), $parameters)->asArray();

            $this->logger->debug(json_encode($paymentLink));

            if(!empty($paymentLink['error_code'])){
                $response['message'] = $paymentLink['message'];

                return $response;
            }

            $response['url'] = $paymentLink['url'];

            return $response;
        } catch (\Exception $e) {

            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    /**
     * @param $quote
     * @param bool $shippingMethod
     * @return array|bool
     */
    public function CreateMobilePayPaymentLink($quote, $shippingMethod = false){
        try {
            $response = [];
            $this->logger->debug('CREATE PAYMENT MOBILEPAY');

            $api_key = $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $client = new QuickPay(":{$api_key}");

            $form = [
                'order_id' => $quote->getReservedOrderId(),
                'currency' => $quote->getQuoteCurrencyCode(),
            ];

            if ($textOnStatement = $this->scopeConfig->getValue(self::TEXT_ON_STATEMENT_XML_PATH)) {
                $form['text_on_statement'] = $textOnStatement;
            }

            $form['shopsystem'] = [];
            $form['shopsystem']['name'] = 'Magento 2';
            $form['shopsystem']['version'] = $this->moduleResource->getDbVersion('UnzerDirect_Gateway');

            //Build basket array
            $form['basket'] = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                $form['basket'][] = [
                    'qty'        => (int) $item->getQty(),
                    'item_no'    => $item->getSku(),
                    'item_name'  => $item->getName(),
                    'item_price' => $item->getBasePriceInclTax() * 100,
                    'vat_rate'   => $item->getTaxPercent() / 100,
                ];
            }
            //$form['invoice_address_selection'] = 1;
            //$form['shipping_address_selection'] = 1;
            if($shippingMethod){
                $form['shipping']['method'] = $shippingMethod['code'];
                $form['shipping']['amount'] = $shippingMethod['price'] * 100;
            }

            $payments = $client->request->post('/payments', $form);

            $paymentArray = $payments->asArray();

            $this->logger->debug(json_encode($paymentArray));

            if(!empty($paymentArray['error_code'])){
                $response['message'] = $paymentArray['message'];
                return $response;
            }

            $paymentId = $paymentArray['id'];

            $parameters = [
                "amount"             => $quote->getGrandTotal() * 100,
                "continueurl"        => $this->url->getUrl('unzerdirect/payment/returns'),
                "cancelurl"          => $this->url->getUrl('unzerdirect/payment/cancel'),
                "callbackurl"        => $this->url->getUrl('unzerdirect/payment/callback'),
                "invoice_address_selection" => 1,
                "shipping_address_selection" => 1,
                "customer_email"     => $quote->getCustomerEmail(),
                "autocapture"        => $this->scopeConfig->isSetFlag(self::AUTOCAPTURE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "payment_methods"    => 'mobilepay',
                "branding_id"        => $this->scopeConfig->getValue(self::BRANDING_ID_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "language"           => $this->getLanguage(),
                "auto_fee"           => $this->scopeConfig->isSetFlag(self::TRANSACTION_FEE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "testmode"           => $this->scopeConfig->isSetFlag(self::TEST_MODE_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                //"acquirer"           => 'mobilepay'
            ];

            //Create payment link and return payment id
            $paymentLink = $client->request->put(sprintf('/payments/%s/link', $paymentId), $parameters)->asArray();

            $this->logger->debug(json_encode($paymentLink));

            if(!empty($paymentLink['error_code'])){
                $response['message'] = $paymentLink['message'];

                return $response;
            }

            $response['url'] = $paymentLink['url'];

            return $response;
        } catch (\Exception $e) {

            $this->logger->critical($e->getMessage());
        }

        return true;
    }

    /**
     * Capture payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function capture($order, $transaction, $ammount)
    {
        $this->logger->debug("Capture payment");

        $api_key = $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());
        $client = new QuickPay(":{$api_key}");

        $form = [
            'id' => $transaction,
            'amount' => $ammount * 100,
        ];

        $payments = $client->request->post("/payments/{$transaction}/capture", $form);
        $paymentArray = $payments->asArray();

        $this->logger->debug(json_encode($paymentArray));

        if(isset($paymentArray['operations'])){
            foreach($paymentArray['operations'] as $operation){
                if($operation['type'] == 'capture' && !empty($operation['qp_status_code'])){
                    if(in_array($operation['qp_status_code'], $this->errorCodes)){
                        throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: '.$operation['qp_status_msg']));
                    }
                }
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: Error.'));
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function cancel($order, $transaction)
    {
        $api_key = $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());
        $client = new QuickPay(":{$api_key}");

        $form = [
            'id' => $transaction,
        ];

        $payments = $client->request->post("/payments/{$transaction}/cancel", $form);
        $paymentArray = $payments->asArray();

        $this->logger->debug(json_encode($paymentArray));

        if(isset($paymentArray['operations'])){
            foreach($paymentArray['operations'] as $operation){
                if($operation['type'] == 'cancel' && !empty($operation['qp_status_code'])){
                    if(in_array($operation['qp_status_code'], $this->errorCodes)){
                        throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: '.$operation['qp_status_msg']));
                    }
                }
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: Error.'));
        }

        return $this;
    }

    /**
     * Refund payment
     *
     * @param array $attributes
     * @return array|bool
     */
    public function refund($order, $transaction, $ammount)
    {
        $this->logger->debug("Refund payment");

        $api_key = $this->scopeConfig->getValue(self::PUBLIC_KEY_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $order->getStoreId());
        $client = new QuickPay(":{$api_key}");

        $form = [
            'id' => $transaction,
            'amount' => $ammount * 100,
        ];

        $payments = $client->request->post("/payments/{$transaction}/refund", $form);
        $paymentArray = $payments->asArray();

        $this->logger->debug(json_encode($paymentArray));

        if(isset($paymentArray['operations'])){
            foreach($paymentArray['operations'] as $operation){
                if($operation['type'] == 'refund' && !empty($operation['qp_status_code'])){
                    if(in_array($operation['qp_status_code'], $this->errorCodes)){
                        throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: '.$operation['qp_status_msg']));
                    }
                }
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('UnzerDirect: Error.'));
        }

        return $this;
    }

    /**
     * Get language code from locale
     *
     * @return mixed
     */
    private function getLanguage()
    {
        $locale = $this->resolver->getLocale();

        //Map both norwegian locales to no
        $map = [
            'nb' => 'no',
            'nn' => 'no',
        ];

        $language = explode('_', $locale)[0];

        if (isset($map[$language])) {
            return $map[$language];
        }

        return $language;
    }

    /**
     * Get payment methods
     *
     * @return string
     */
    public function getPaymentMethods()
    {
        $payment_methods = $this->scopeConfig->getValue(self::PAYMENT_METHODS_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        /**
         * Get specified payment methods
         */
        if ($payment_methods === 'specified') {
            $payment_methods = $this->scopeConfig->getValue(self::SPECIFIED_PAYMENT_METHOD_XML_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $payment_methods;
    }

    /**
     * @param null $order
     * @param $transactionId
     * @param $type
     */
    public function createTransaction($order = null, $transactionId, $type)
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();

            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = '';
            if($type == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH){
                $message = __('The authorized amount is %1.', $formatedPrice);
            } elseif($type == \Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE) {
                $message = __('The captured amount is %1.', $formatedPrice);
            }

            if($payment->getLastTransId()){
                $parent_id = $payment->getLastTransId();
            } else {
                $parent_id = null;
            }

            $payment->setLastTransId($transactionId);
            $payment->setTransactionId($transactionId);
            /*$payment->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]
            );*/

            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($transactionId)
                ->setAdditionalInformation(
                    [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()]
                )
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build($type);
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId($parent_id);

            // update totals
            $amount = $order->getGrandTotal();
            $amount = $payment->formatAmount($amount, true);
            $payment->setBaseAmountAuthorized($amount);

            $payment->save();
            $order->save();

        } catch (Exception $e) {
            //log errors here
        }
    }
}
