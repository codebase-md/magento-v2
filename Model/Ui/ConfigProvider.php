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

    const XML_PATH_CARD_LOGO = 'payment/unzerdirect_gateway/cardlogos';

    protected $scopeConfig;

    protected $assetRepo;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Asset\Repository $assetRepo
    ){
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
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
}
