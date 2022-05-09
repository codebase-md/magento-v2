<?php
namespace UnzerDirect\Gateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'unzerdirect_gateway';
    const CODE_KLARNA = 'unzerdirect_klarna';
    const CODE_APPLEPAY = 'unzerdirect_applepay';
    const CODE_PAYPAL = 'unzerdirect_paypal';
    const CODE_GOOGLEPAY = 'unzerdirect_googlepay';
    const CODE_SOFORT = 'unzerdirect_sofort';
    const CODE_INVOICE = 'unzerdirect_invoice';
    const CODE_DIRECT_DEBIT = 'unzerdirect_direct_debit';

    const XML_PATH_CARD_LOGO = 'payment/unzerdirect_gateway/cardlogos';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $localeResolver;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Locale\Resolver $localeResolver,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Escaper $escaper
    ){
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
        $this->localeResolver = $localeResolver;
        $this->paymentHelper = $paymentHelper;
        $this->escaper = $escaper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'redirectUrl' => 'unzerdirect/payment/redirect',
                    'paymentLogo' => $this->getUnzerDirectCardLogo(),
                    'instructions' => $this->getInstructions(self::CODE)
                ],
                self::CODE_KLARNA => [
                    'paymentLogo' => $this->getKlarnaLogo(),
                    'instructions' => $this->getInstructions(self::CODE_KLARNA)
                ],
                self::CODE_APPLEPAY => [
                    'paymentLogo' => $this->getApplePayLogo(),
                    'instructions' => $this->getInstructions(self::CODE_APPLEPAY)
                ],
                self::CODE_PAYPAL => [
                    'paymentLogo' => $this->getPaypalLogo(),
                    'instructions' => $this->getInstructions(self::CODE_PAYPAL)
                ],
                self::CODE_GOOGLEPAY => [
                    'paymentLogo' => $this->getGooglePayLogo(),
                    'instructions' => $this->getInstructions(self::CODE_GOOGLEPAY)
                ],
                self::CODE_SOFORT => [
                    'paymentLogo' => $this->getSofortLogo(),
                    'instructions' => $this->getInstructions(self::CODE_SOFORT)
                ],
                self::CODE_INVOICE => [
                    'paymentLogo' => $this->getInvoiceLogo(),
                    'instructions' => $this->getInstructions(self::CODE_INVOICE)
                ],
                self::CODE_DIRECT_DEBIT => [
                    'paymentLogo' => $this->getDirectDebitLogo(),
                    'instructions' => $this->getInstructions(self::CODE_DIRECT_DEBIT)
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getUnzerDirectCardLogo(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cards = explode(',', $this->scopeConfig->getValue(self::XML_PATH_CARD_LOGO, $storeScope) ?? '');

        $items = [];

        if(count($cards)) {
            foreach ($cards as $card) {
                if($card) {
                    $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/logo/{$card}.svg");
                }
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    public function getKlarnaLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/klarna.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getApplePayLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/apple-pay.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getPaypalLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/paypal.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getGooglePayLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/google-pay.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getSofortLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/sofort.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getInvoiceLogo(){
        $items = [];

        /*$locale = $this->getCurrentLocale();
        if($locale == 'de'){
            $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/invoice_de.svg");
        } else {
            $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/invoice_en.svg");
        }*/
        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/unzer.svg");

        return $items;
    }

    /**
     * @return array
     */
    public function getDirectDebitLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/unzer.svg");

        return $items;
    }

    /**
     * @return false|string
     */
    public function getCurrentLocale(){
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }

    /**
     * @param $code
     * @return string
     */
    protected function getInstructions($code){
        return nl2br($this->escaper->escapeHtml($this->paymentHelper->getMethodInstance($code)->getInstructions()));
    }
}
