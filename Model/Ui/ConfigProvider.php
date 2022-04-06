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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Locale\Resolver $localeResolver
    ){
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
        $this->localeResolver = $localeResolver;
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
                    'paymentLogo' => $this->getUnzerDirectCardLogo()
                ],
                self::CODE_KLARNA => [
                    'paymentLogo' => $this->getKlarnaLogo()
                ],
                self::CODE_APPLEPAY => [
                    'paymentLogo' => $this->getApplePayLogo()
                ],
                self::CODE_PAYPAL => [
                    'paymentLogo' => $this->getPaypalLogo()
                ],
                self::CODE_GOOGLEPAY => [
                    'paymentLogo' => $this->getGooglePayLogo()
                ],
                self::CODE_SOFORT => [
                    'paymentLogo' => $this->getSofortLogo()
                ],
                self::CODE_INVOICE => [
                    'paymentLogo' => $this->getInvoiceLogo()
                ]
            ]
        ];
    }

    public function getUnzerDirectCardLogo(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $cards = explode(',', $this->scopeConfig->getValue(self::XML_PATH_CARD_LOGO, $storeScope));

        $items = [];

        if(count($cards)) {
            foreach ($cards as $card) {
                if($card) {
                    $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/logo/{$card}.png");
                }
            }
        }

        return $items;
    }

    public function getKlarnaLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/klarna.png");

        return $items;
    }

    public function getApplePayLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/applepay.png");

        return $items;
    }

    public function getPaypalLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/paypal.png");

        return $items;
    }

    public function getGooglePayLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/googlepay.png");

        return $items;
    }

    public function getSofortLogo(){
        $items = [];

        $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/sofort.svg");

        return $items;
    }

    public function getInvoiceLogo(){
        $items = [];

        $locale = $this->getCurrentLocale();
        if($locale == 'de'){
            $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/invoice_de.svg");
        } else {
            $items[] = $this->assetRepo->getUrl("UnzerDirect_Gateway::images/invoice_en.svg");
        }

        return $items;
    }

    public function getCurrentLocale(){
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }
}
