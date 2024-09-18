<?php
namespace Eniture\UPSLTLFreightQuotes\Model\Carrier;

use Magento\Store\Model\ScopeInterface;

/**
 * class that generated request data
 */
class UpsLTLGenerateRequestData
{
    /**
     * @var Object
     */
    private $registry;
    /**
     * @var Object
     */
    private $moduleManager;
    /**
     * @var string
     */
    public $FedexOneRatePricing = '0';
    /**
     * @var object
     */
    private $request;
    /**
     * @var object
     */
    private $scopeConfig;

    private $appConfigData = [];
    /**
     * @var object
     */
    private $dataHelper;

    /**
     * constructor of class that accepts request object
     * @param $scopeConfig
     * @param $registry
     * @param $moduleManager
     * @param $request
     */
    public function _init(
        $scopeConfig,
        $registry,
        $moduleManager,
        $request,
        $dataHelper
    ) {
        $this->registry        = $registry;
        $this->scopeConfig     = $scopeConfig;
        $this->moduleManager   = $moduleManager;
        $this->request         = $request;
        $quoteSett = $this->scopeConfig->getValue("UpsLtlQuoteSetting/third", ScopeInterface::SCOPE_STORE);
        $connSett  = $this->scopeConfig->getValue("UpsLtlConnSettings/first", ScopeInterface::SCOPE_STORE);
        $quoteSett = $quoteSett ?? [];
        $connSett  = $connSett ?? [];
        $this->appConfigData   = array_merge($quoteSett, $connSett);
        $this->dataHelper      = $dataHelper;
    }

    /**
     * function that generates Ups array
     * @return array
     */
    public function generateEnitureArray()
    {
        $getDistance = 0;
        $upsLtlArr= [
            'licenseKey'                => $this->getConfigData('upsltlLicnsKey'),
            'serverName'                => $this->request->getServer('SERVER_NAME'),
            'carrierMode'               => 'pro',
            'quotestType'               => 'ltl', // ltl / small
            'version'                   => '1.1.1',
            'returnQuotesOnExceedWeight'=> $this->getConfigData('weightExeeds'),
            'api'                       => $this->getApiInfoArr(),
            'getDistance'               => $getDistance,
        ];
        return  $upsLtlArr;
    }

    /**
     * @param $request
     * @param $originArr
     * @param $itemsArr
     * @param $cart
     * @return array|bool
     */
    public function generateRequestArray($request, $originArr, $itemsArr, $cart)
    {
        if (count($originArr['originAddress']) > 1) {
            foreach ($originArr['originAddress'] as $wh) {
                $whIDs[] = $wh['locationId'];
            }
            if (count(array_unique($whIDs)) > 1) {
                foreach ($originArr['originAddress'] as $id => $wh) {
                    if (isset($wh['InstorPickupLocalDelivery'])) {
                        $originArr['originAddress'][$id]['InstorPickupLocalDelivery'] = [];
                    }
                }
            }
        }
        $carriers = $this->registry->registry('enitureCarriers');
        $carriers['upsLTL'] = $originArr;
        $receiverAddress = $this->getReceiverData($request);

        $autoResidential = $liftgateWithAuto = '0';
        if ($this->autoResidentialDelivery()) {
            $autoResidential = '1';
            $liftgateWithAuto = $this->getConfigData('RADforLiftgate') ?? '0';

            if ($this->registry->registry('radForLiftgate') === null) {
                $this->registry->register('radForLiftgate', $liftgateWithAuto);
            }
        }
        $smartPost = $this->registry->registry('fedexSmartPost');



        $requestArr = [
            'apiVersion'                    => '3.0',
            'platform'                      => 'magento2',
            'binPackagingMultiCarrier'      => $this->binPackSuspend(),
            'autoResidentials'              => $autoResidential,
            'liftGateWithAutoResidentials'  => $liftgateWithAuto,
            'FedexOneRatePricing'           => $smartPost,
            'FedexSmartPostPricing'         => $smartPost,
            'requestKey'                    => $cart->getQuote()->getId(),
            'carriers'                      => $carriers,
            'receiverAddress'               => $receiverAddress,
            'commdityDetails'               => $itemsArr
        ];

        return  $requestArr;
    }

    /**
     * @return string
     */
    public function binPackSuspend()
    {
        $return = "0";
        if ($this->moduleManager->isEnabled('Eniture_StandardBoxSizes')) {
            $return = $this->scopeConfig->getValue("binPackaging/suspend/value", ScopeInterface::SCOPE_STORE) == "no" ? "1" : "0";
        }
        return $return;
    }

    /**
     * @return int
     */
    public function autoResidentialDelivery()
    {
        $autoDetectResidential = 0;
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $suspndPath = "resaddressdetection/suspend/value";
            $autoResidential = $this->scopeConfig->getValue($suspndPath, ScopeInterface::SCOPE_STORE);
            if ($autoResidential != null && $autoResidential == 'no') {
                $autoDetectResidential = 1;
            }
        }
        return $autoDetectResidential;
    }

    /**
     * function that returns API array
     * @return array
     */
    public function getApiInfoArr()
    {
        if ($this->autoResidentialDelivery()) {
            $residential = 'N';
        } else {
            $residential = ($this->getConfigData('residentialDlvry')) ? 'Y' : 'N';
        }

        $liftGate       = ($this->getConfigData('liftGate') ||
                            $this->getConfigData('OfferLiftgateAsAnOption')) ? 'Y' : 'N';

        $shipperRelation = $this->getConfigData('shipperRelation');
        $accountType = $this->getConfigData('upsltlAccountType');

        $endPoint = $this->getConfigData('tforceEndPoint');

        $apiArray = [
            'paymentCode'            => '10',
            'paymentDescription'     => 'PREPAID',
            'paymentType'            => $shipperRelation ?? 'Shipper',
            'handlingUnitWeight'     => $this->getConfigData('handlingUnitWeight'),
            'maxWeightPerHandlingUnit'     => $this->getConfigData('maxWeightPerUnit'),
            'serviceCode'            => '308',
            'serviceCodeDescription' => 'TForce Freight LTL',
            'timeInTransitIndicator' => $this->getConfigData('dlrvyEstimates') ? 'Y' : 'N',
            'accessorial'            => ['liftgateDelivery' => $liftGate, 'residentialDelivery' => $residential],
            'dimWeightBaseAccount'   => (isset($accountType) && $accountType == 'dimension') ? '1' : '0' ,
        ];

        if(empty($endPoint) || $endPoint == '1'){
            $apiArray['APIKey'] = $this->getConfigData('upsltlAuthenticationKey') ?? '';
            $apiArray['AccountNumber'] = $this->getConfigData('upsltlAccountNumber') ?? '';
            $apiArray['UserName'] = $this->getConfigData('upsltlUsername') ?? '';
            $apiArray['Password'] = $this->getConfigData('upsltlPassword') ?? '';
        }else{
            $apiArray['requestForTForceQuotes'] = '1';
            $apiArray['clientId'] = $this->getConfigData('tforceClientId') ?? '';
            $apiArray['clientSecret'] = $this->getConfigData('tforceClientSecret') ?? '';
            $apiArray['UserName'] = $this->getConfigData('tforceUsername') ?? '';
            $apiArray['Password'] = $this->getConfigData('tforcePassword') ?? '';
        }

        if ($shipperRelation == 'ThirdParty') {
            $apiArray['payerAddress'] = [
                'payerName'        => 'name',
                'payerAddressLine' => 'addressLine',
                'payerCountryCode' => $this->getConfigData('thirdPartyCountry'),
                'payerZip'         => $this->getConfigData('thirdPartyPostalCode'),
                'payerState'       => $this->getConfigData('thirdPartyState'),
                'payerCity'        => $this->getConfigData('thirdPartyCity')
            ];
        }

        if ($this->moduleManager->isEnabled('Eniture_PalletPackaging')) {
            $apiArray['standardPackaging'] = $this->scopeConfig->getValue("palletPackaging/suspend/value", ScopeInterface::SCOPE_STORE) == "no" ? "1" : "0";
            $palletsData = $this->getSavedPallets();
            if(is_array($palletsData) && count($palletsData) > 0){
                $apiArray['pallets'] = $palletsData;
            }else{
                $apiArray['pallets'] = [];
            }
        }

        return  $apiArray;
    }

    /**
     * function return service data
     * @param $index
     * @return string
     */
    public function getConfigData($index)
    {
        return $this->appConfigData[$index] ?? '';
    }

    /**
     * This function returns Receiver Data Array
     * @param object $request
     * @return array
     */
    public function getReceiverData($request)
    {
        $addressTypePath = "resaddressdetection/addressType/value";
        $addressType = $this->scopeConfig->getValue($addressTypePath, ScopeInterface::SCOPE_STORE);
        $receiverDataArr = [
            'addressLine'           => $request->getDestStreet(),
            'receiverCity'          => $request->getDestCity(),
            'receiverState'         => $request->getDestRegionCode(),
            'receiverZip'           => preg_replace('/\s+/', '', $request->getDestPostcode()),
            'receiverCountryCode'   => $request->getDestCountryId(),
            'defaultRADAddressType' => $addressType ?? 'residential', //get value from RAD
        ];

        return  $receiverDataArr;
    }

    /**
     * Return pallets array
     * @return array
     */
    public function getSavedPallets()
    {
        $savedPallets = [];
        if ($this->moduleManager->isEnabled('Eniture_PalletPackaging')) {
            $palletPackagingHelper = $this->dataHelper->getPalletPackagingHelper();
            $savedPallets = $palletPackagingHelper->fillPalletsData();
        }
        return $savedPallets;
    }
}
