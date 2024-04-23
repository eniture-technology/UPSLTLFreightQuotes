<?php

namespace Eniture\UPSLTLFreightQuotes\Model\Carrier;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Helper\EnConstants;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class UpsLTLShipping extends AbstractCarrier implements CarrierInterface
{
    public $_code = EnConstants::APP_CODE;

    private $isFixed = true;

    private $rateResultFactory;

    private $rateMethodFactory;

    private $scopeConfig;

    private $dataHelper;

    private $registry;

    private $moduleManager;

    private $session;

    private $productLoader;

    private $mageVersion;

    private $objectManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var Cart
     */
    private $cart;
    /**
     * @var UpsLTLGenerateRequestData
     */
    private $generateReqData;
    /**
     * @var UpsLTLManageAllQuotes
     */
    private $manageAllQuotes;
    /**
     * @var UpsLTLShipmentPackage
     */
    private $shipmentPkg;
    /**
     * @var UpsLTLAdminConfiguration
     */
    private $adminConfig;
    /**
     * @var UPSLTLSetCarriersGlobaly
     */
    private $setGlobalCarrier;

    private $qty = 0;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param Cart $cart
     * @param Data $dataHelper
     * @param Registry $registry
     * @param Manager $moduleManager
     * @param UrlInterface $urlInterface
     * @param SessionManagerInterface $session
     * @param ProductFactory $productLoader
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface $objectManager
     * @param UpsLTLGenerateRequestData $generateReqData
     * @param UpsLTLManageAllQuotes $manAllQuotes
     * @param UpsLTLShipmentPackage $shipmentPkg
     * @param UpsLTLAdminConfiguration $adminConfig
     * @param UPSLTLSetCarriersGlobaly $setGlobalCarrier
     * @param RequestInterface $httpRequest
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Cart $cart,
        Data $dataHelper,
        Registry $registry,
        Manager $moduleManager,
        UrlInterface $urlInterface,
        SessionManagerInterface $session,
        ProductFactory $productLoader,
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager,
        UpsLTLGenerateRequestData $generateReqData,
        UpsLTLManageAllQuotes $manAllQuotes,
        UpsLTLShipmentPackage $shipmentPkg,
        UpsLTLAdminConfiguration $adminConfig,
        UPSLTLSetCarriersGlobaly $setGlobalCarrier,
        RequestInterface $httpRequest,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
        $this->moduleManager = $moduleManager;
        $this->urlInterface = $urlInterface;
        $this->session = $session;
        $this->productLoader = $productLoader;
        $this->mageVersion = $productMetadata->getVersion();
        $this->objectManager = $objectManager;
        $this->generateReqData = $generateReqData;
        $this->manageAllQuotes = $manAllQuotes;
        $this->shipmentPkg = $shipmentPkg;
        $this->adminConfig = $adminConfig;
        $this->setGlobalCarrier = $setGlobalCarrier;
        $this->request = $httpRequest;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->initClasses();
    }

    /**
     * Initialize class objects
     */
    public function initClasses()
    {
        $this->adminConfig->_init($this->scopeConfig, $this->registry);

        $this->generateReqData->_init($this->scopeConfig, $this->registry, $this->moduleManager, $this->request, $this->dataHelper);

        $this->manageAllQuotes->_init($this->scopeConfig, $this->registry, $this->session, $this->objectManager);

        $this->shipmentPkg->_init($this->scopeConfig, $this->dataHelper, $this->productLoader, $this->request);

        $this->setGlobalCarrier->_init($this->dataHelper);
    }

    /**
     * @param RateRequest $request
     * @return boolean|object
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if (empty($request->getDestPostcode()) || empty($request->getDestCountryId()) ||
            empty($request->getDestCity()) || empty($request->getDestRegionId())) {
            return false;
        }

        $getQuotesFromSession = $this->quotesFromSession();
        if (null !== $getQuotesFromSession) {
            return $getQuotesFromSession;
        }

        $ItemsList = $request->getAllItems();
        $receiverZipCode = $request->getDestPostcode();
        $package = $this->getShipmentPackageRequest($ItemsList, $receiverZipCode, $request);

        $upsLtlArr = $this->generateReqData->generateEnitureArray();
        $upsLtlArr['originAddress'] = $package['origin'];

        $resp = $this->setGlobalCarrier->manageCarriersGlobaly($upsLtlArr, $this->registry);

        if (!$resp) {
            return false;
        }

        $requestArr = $this->generateReqData->generateRequestArray($request, $upsLtlArr, $package['items'], $this->cart);
        if (empty($requestArr)) {
            return false;
        }
        $url = EnConstants::QUOTES_URL;

        $quotes = $this->dataHelper->upsLTLSendCurlRequest($url, $requestArr);

        // Debug point will print data if en_print_query=1
        if ($this->printQuery()) {
            $printData = ['url' => $url,
                'buildQuery' => http_build_query($requestArr),
                'request' => $requestArr,
                'quotes' => $quotes];
            print_r('<pre>');
            print_r($printData);
            print_r('</pre>');
            return;
        }

        $quotesResult = $this->manageAllQuotes->getQuotesResultArr($quotes);

        $this->session->setEnShippingQuotes($quotesResult);

        $upsLtlQuotes = (!empty($quotesResult)) ? $this->setCarrierRates($quotesResult) : '';

        return $upsLtlQuotes;
    }

    /**
     * @return object | null
     */
    public function quotesFromSession()
    {
        $currentAction = $this->urlInterface->getCurrentUrl();
        $currentAction = strtolower($currentAction);

        if (strpos($currentAction, 'totals-information') !== false
            || strpos($currentAction, 'shipping-information') !== false
            || strpos($currentAction, 'payment-information') !== false) {
            $availableSessionQuotes = $this->session->getEnShippingQuotes(); // FROM SESSION
            $availableQuotes = (!empty($availableSessionQuotes)) ? $this->setCarrierRates($availableSessionQuotes) : null;
        } else {
            $availableQuotes = null;
        }
        return $availableQuotes;
    }

    /**
     * This function returns package array
     * @param $items
     * @param $receiverZipCode
     * @param $request
     * @return array
     */
    public function getShipmentPackageRequest($items, $receiverZipCode, $request)
    {
        $package = [];
        foreach ($items as $item) {
            $_product = $this->productLoader->create()->load($item->getProductId());
            $productType = $item->getRealProductType() ?? $_product->getTypeId();

            if ($productType == 'configurable') {
                $this->qty = $item->getQty();
            }

            if ($productType == 'simple') {
                $productQty = ($this->qty > 0) ? $this->qty : $item->getQty();
                $this->qty = 0;
                $originAddress = $this->shipmentPkg->upsLTLOriginAddress($request, $_product, $receiverZipCode);

                $package['origin'][$_product->getId()] = $originAddress;

                $orderWidget[$originAddress['senderZip']]['origin'] = $originAddress;

                $weight = $_product->getWeight();
                $length = $this->getDims($_product, 'length');
                $width = $this->getDims($_product, 'width');
                $height = $this->getDims($_product, 'height');
                $setHzAndIns = $this->setHzAndIns($_product);
                $lineItemClass = $this->getLineItemClass($_product);
                $lineItem = [
                    'lineItemClass' => $lineItemClass,
                    'freightClass' => $this->isLTL($_product),
                    'lineItemId' => $_product->getId(),
                    'lineItemName' => $_product->getName(),
                    'piecesOfLineItem' => (int)$productQty,
                    'lineItemPrice' => $_product->getPrice(),
                    'lineItemWeight' => number_format((float) $weight, 2, '.', ''),
                    'lineItemLength' => number_format((float) $length, 2, '.', ''),
                    'lineItemWidth' => number_format((float) $width, 2, '.', ''),
                    'lineItemHeight' => number_format((float) $height, 2, '.', ''),
                    'isHazmatLineItem' => $setHzAndIns['hazmat'],
                    'product_insurance_active' => $setHzAndIns['insurance'],
                    'shipBinAlone' => $_product->getData('en_own_package'),
                    'vertical_rotation' => $_product->getData('en_vertical_rotation'),
                    'shipPalletAlone' => $_product->getData('en_own_pallet'),
                    'vertical_rotation_pallet' => $_product->getData('en_vertical_rotation_pallet'),
                ];

                $package['items'][$_product->getId()] = $lineItem;
                $orderWidget[$originAddress['senderZip']]['item'][] = $package['items'][$_product->getId()];
            }
        }

        if (isset($orderWidget) && !empty($orderWidget)) {
            $uniqueOrigins = [];
            foreach ($orderWidget as $data) {
                $uniqueOrigins [] = $data['origin'];
            }
            $this->setDataInRegistry($uniqueOrigins, $orderWidget);
        }
        return $package;
    }

    /**
     * @param $origin
     * @param $orderWidget
     */
    public function setDataInRegistry($origin, $orderWidget)
    {
        // set order detail widget data
        if ($this->registry->registry('setPackageDataForOrderDetail') === null) {
            $this->registry->register('setPackageDataForOrderDetail', $orderWidget);
        }

        // set shipment origin globally for instore pickup and local delivery
        if ($this->registry->registry('shipmentOrigin') === null) {
            $this->registry->register('shipmentOrigin', $origin);
        }
    }

    /**
     * @param $_product
     * @return string
     */
    private function isLTL($_product)
    {
        $freightClass = '';
        $path = 'upsLtlQuoteSetting/third/weightExeeds';
        $weightConfigExceedOpt = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);

        if ($this->registry->registry('weightConfigExceedOpt') === null) {
            $this->registry->register('weightConfigExceedOpt', $weightConfigExceedOpt);
        }

        $isEnableLtl = $_product->getData('en_ltl_check');
        if (($isEnableLtl) || ($_product->getWeight() > 150 && $weightConfigExceedOpt)) {
            $freightClass = 'ltl';
        } else {
            $freightClass = '';
        }

        return $freightClass;
    }

    /**
     * @param $_product
     * @return float|int|string
     */
    private function getLineItemClass($_product)
    {
        $lineItemClass = $_product->getData('en_freight_class');
        switch ($lineItemClass) {
            case 77:
                $lineItemClass = 77.5;
                break;
            case 92:
                $lineItemClass = 92.5;
                break;
            case 1:
                $lineItemClass = 'DensityBased';
                break;
            default:
                break;
        }
        return $lineItemClass;
    }

    /**
     * @param $_product
     * @param $dimOf
     * @return float
     */
    private function getDims($_product, $dimOf)
    {
        $dimValue = $_product->getData('ts_dimensions_'.$dimOf);
        if($dimValue != null){
            return $dimValue;
        }

        return $_product->getData('en_'.$dimOf);
    }

    /**
     * @param object $_product
     * @return array
     */
    private function setHzAndIns($_product)
    {

        $hazmat = ($_product->getData('en_hazmat')) ? 'Y' : 'N';
        $insurance = $_product->getData('en_insurance');
        if ($insurance && $this->registry->registry('en_insurance') === null) {
            $this->registry->register('en_insurance', $insurance);
        }
        return ['hazmat' => $hazmat,
            'insurance' => $insurance
        ];
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['UPSFREIGHT' => 'Freight'];
    }

    /**
     * @param array $quotes
     * @return Object
     */
    public function setCarrierRates($quotes)
    {
        if (empty($quotes)) {
            //To show error
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            return $error;
        } else {
            $carriersArray = $this->registry->registry('enitureCarrierCodes');
            $carriersTitle = $this->registry->registry('enitureCarrierTitle');
            $result = $this->rateResultFactory->create();

            foreach ($quotes as $carrierKey => $quote) {
                foreach ($quote as $key => $carrier) {
                    if (isset($carrier['code'])) {
                        $carrierCode = $carriersArray[$carrierKey] ?? $this->_code;
                        $carrierTitle = $carriersTitle[$carrierKey] ?? $this->getConfigData('title');
                        $method = $this->rateMethodFactory->create();

                        $method->setCarrier($carrierCode);
                        $method->setCarrierTitle($carrierTitle);
                        $method->setMethod($carrier['code']);
                        $method->setMethodTitle($carrier['title']);
                        $method->setPrice($carrier['rate']);
                        $method->setCost($carrier['rate']);
                        $result->append($method);
                    }
                }
            }
            return $result;
        }
    }

    public function printQuery()
    {
        $printQuery = 0;
        $query = [];
        $url = parse_url($this->request->getServer('HTTP_REFERER'), PHP_URL_QUERY);
        if(is_string($url)){
            parse_str($url, $query);
        }

        if (!empty($query)) {
            $printQuery = ($query['en_print_query']) ?? 0;
        }
        return $printQuery;
    }
}
