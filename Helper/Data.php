<?php
namespace Eniture\UPSLTLFreightQuotes\Helper;

use Eniture\UPSLTLFreightQuotes\Model\WarehouseFactory;
use Magento\Framework\App\Cache\Manager;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Data
 * @package Eniture\UPSLTLFreightQuotes\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var Modulemanager Object
     */
    private $moduleManager;
    /**
     * @var Conn Object
     */
    private $connection;
    /**
     * @var Warehouse Table
     */
    private $WHtableName;
    /**
     * @var
     */
    private $shippingConfig;
    /**
     * @var bool
     */
    public $canAddWh = 1;
    /**
     * @var
     */
    private $warehouseFactory;
    /**
     * @var
     */
    private $curl;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var bool
     */
    private $isResi = false;
    /**
     * @var SessionManagerInterface
     */
    public $coreSession;
    /**
     * @var string
     */
    private $resiLabel;
    /**
     * @var string
     */
    private $lgLabel;
    /**
     * @var string
     */
    private $resiLgLabel;
    /**
     * @var Manager
     */
    private $cacheManager;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var string
     */
    private $labelAs;
    /**
     * @var string
     */
    private $dlrvyEstimates;
    /**
     * @var string
     */
    private $residentialDlvry;
    /**
     * @var string
     */
    private $liftGate;
    /**
     * @var string
     */
    private $OfferLiftgateAsAnOption;
    /**
     * @var string
     */
    private $RADforLiftgate;
    /**
     * @var string
     */
    private $hndlngFee;
    /**
     * @var string
     */
    private $symbolicHndlngFee;

    /**
     * @param Context $context
     * @param ResourceConnection $resource
     * @param Config $shippingConfig
     * @param WarehouseFactory $warehouseFactory
     * @param Curl $curl
     * @param Registry $registry
     * @param SessionManagerInterface $coreSession
     * @param Manager $cacheManager
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Config $shippingConfig,
        WarehouseFactory $warehouseFactory,
        Curl $curl,
        Registry $registry,
        SessionManagerInterface $coreSession,
        Manager $cacheManager,
        ObjectManagerInterface $objectmanager
    ) {
        $this->moduleManager      = $context->getModuleManager();
        $this->connection         = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->WHtableName        = $resource->getTableName('warehouse');
        $this->shippingConfig     = $shippingConfig;
        $this->warehouseFactory   = $warehouseFactory;
        $this->curl               = $curl;
        $this->registry           = $registry;
        $this->coreSession        = $coreSession;
        $this->cacheManager       = $cacheManager;
        $this->objectManager = $objectmanager;
        parent::__construct($context);
    }
    
    /**
     * @param string $location
     * @return array
     */
    function fetchWarehouseSecData($location)
    {
        $whCollection       = $this->warehouseFactory->create()->getCollection()->addFilter('location', ['eq' => $location]);
        $warehouseSecData   = $this->purifyCollectionData($whCollection);

        return $warehouseSecData;
    }
    /**
     * @param $whCollection
     * @return array
     */
    public function purifyCollectionData($whCollection)
    {
        $warehouseSecData = [];
        foreach ($whCollection as $wh) {
            $warehouseSecData[] = $wh->getData();
        }
        return $warehouseSecData;
    }
    /**
     * @param $warehouseId
     * @return array
     */
    public function fetchWarehouseWithID($location, $warehouseId)
    {
        $whFactory = $this->warehouseFactory->create();
        $dsCollection  = $whFactory->getCollection()
                        ->addFilter('location', ['eq' => $location])
                        ->addFilter('warehouse_id', ['eq' => $warehouseId]);

        $dropshipSecData   = $this->purifyCollectionData($dsCollection);

        return $dropshipSecData;
    }

    /**
     * @param $data
     * @param $whereClause
     * @return int
     */
    public function updateWarehousData($data, $whereClause)
    {
        return $this->connection->update("$this->WHtableName", $data, "$whereClause");
    }

    /**
     * @param $data
     * @param $id
     * @return array
     */
    public function insertWarehouseData($data, $id)
    {
        $insertQry = $this->connection->insert("$this->WHtableName", $data);
        if ($insertQry == 0) {
            $lastid = $id;
        } else {
            $lastid = $this->connection->lastInsertId();
        }
        return ['insertId' => $insertQry, 'lastId' => $lastid];
    }

    /**
     * @param $data
     * @return int
     */
    public function deleteWarehouseSecData($data)
    {
        return $this->connection->delete("$this->WHtableName", $data);
    }


    /**
     * Data Array
     */

    public function upsLTLOriginArray($inputData)
    {
        $dataArr = [
            'city'       => $inputData['city'],
            'state'      => $inputData['state'],
            'zip'        => $inputData['zip'],
            'country'    => $inputData['country'],
            'location'   => $inputData['location'],
            'nickname'   => $inputData['nickname'] ?? '',
        ];
                $pickupDelvryArr = [
                    'enable_store_pickup'           => ($inputData['instore-enable'] === 'on') ? 1 : 0,
                    'miles_store_pickup'            => $inputData['is-within-miles'],
                    'match_postal_store_pickup'     => $inputData['is-postcode-match'],
                    'checkout_desc_store_pickup'    => $inputData['is-checkout-descp'],
                    'suppress_other'                => ($inputData['ld-sup-rates'] === 'on') ? 1 : 0,
                ];
                $dataArr['in_store'] = json_encode($pickupDelvryArr);

                $localDelvryArr = [
                    'enable_local_delivery'         => ($inputData['ld-enable']=== 'on') ? 1 : 0,
                    'miles_local_delivery'          => $inputData['ld-within-miles'],
                    'match_postal_local_delivery'   => $inputData['ld-postcode-match'],
                    'checkout_desc_local_delivery'  => $inputData['ld-checkout-descp'],
                    'fee_local_delivery'            => $inputData['ld-fee'],
                    'suppress_other'                => ($inputData['ld-sup-rates'] === 'on')?1:0,
                ];
                $dataArr['local_delivery'] = json_encode($localDelvryArr);

                return $dataArr;
    }
    
    /**
     *
     */
    function quoteSettingsData()
    {
        $quoteSett = $this->getConfigData("UpsLtlQuoteSetting/third");
        
        $this->labelAs = $quoteSett['labelAs'] ?? '';
        $this->dlrvyEstimates = $quoteSett['dlrvyEstimates'] ?? '0';
        $this->residentialDlvry = $quoteSett['residentialDlvry'] ?? '';
        $this->liftGate = $quoteSett['liftGate'] ?? '';
        $this->OfferLiftgateAsAnOption = $quoteSett['OfferLiftgateAsAnOption'] ?? '';
        $this->RADforLiftgate = $quoteSett['RADforLiftgate'] ?? '';
        $this->hndlngFee = $quoteSett['hndlngFee'] ?? '';
        $this->symbolicHndlngFee = $quoteSett['symbolicHndlngFee'] ?? '';

        $this->labelAs      = !empty(trim($this->labelAs)) ? $this->labelAs : 'Freight';
        $this->resiLabel    = ' with residential delivery';
        $this->lgLabel      = ' with lift gate delivery';
        $this->resiLgLabel  = ' with residential delivery and lift gate delivery';
    }
    
    /**
     * validate Input Post
     * @param $sPostData
     * @return mixed
     */
    public function upsLTLValidatedPostData($sPostData)
    {
        $dataArray = ['city', 'state', 'zip', 'country'];
        foreach ($sPostData as $key => $tag) {
            $preg = '/[#$%@^&_*!()+=\-\[\]\';,.\/{}|":<>?~\\\\]/';
            $check_characters = (in_array($key, $dataArray)) ? preg_match($preg, $tag) : '';

            if ($check_characters != 1) {
                if ($key === 'city' || $key === 'nickname' || $key === 'in_store' || $key === 'local_delivery') {
                    $data[$key] = $tag;
                } else {
                    $data[$key] = preg_replace('/\s+/', '', $tag);
                }
            } else {
                $data[$key] = 'Error';
            }
        }

        return $data;
    }
        
    /**
     *
     * @param array $getWarehouse
     * @param array $validateData
     * @return string
     */
    function checkUpdateInstrorePickupDelivery($getWarehouse, $validateData)
    {
        $update = 'no';
        $newData = $oldData = [];

        if (empty($getWarehouse)) {
            return $update;
        }

        $getWarehouse = reset($getWarehouse);
        unset($getWarehouse['warehouse_id']);
        unset($getWarehouse['nickname']);
        unset($validateData['nickname']);

        foreach ($getWarehouse as $key => $value) {
            if (empty($value) || is_null($value)) {
                $newData[$key] = 'empty';
            } else {
                $oldData[$key] = trim($value);
            }
        }

        $whData = array_merge($newData, $oldData);
        $diff1 = array_diff($whData, $validateData);
        $diff2 = array_diff($validateData, $whData);

        if ((is_array($diff1) && !empty($diff1)) || (is_array($diff2) && !empty($diff2))) {
            $update = 'yes';
        }

        return $update;
    }
    /**
     * @param array $quotesarray
     * @param array $instoreLd
     * @return array
     */
    public function instoreLocalDeliveryQuotes($quotesarray, $instoreLd)
    {
        $data = $this->registry->registry('shipmentOrigin');
        if (count($data) > 1) {
            return $quotesarray;
        }

        foreach ($data as $array) {
            $warehouseData = $this->getWarehouseData($array);


            /* Quotes array only to be made empty if Suppress other rates is ON and Instore Pickup or Local Delivery also carries some quotes. Else if Instore Pickup or Local Delivery does not have any quotes i.e Postal code or within miles does not match then the Quotes Array should be returned as it is. */
            if (isset($warehouseData['suppress_other']) && $warehouseData['suppress_other']) {
                if ((isset($instoreLd->inStorePickup->status) && $instoreLd->inStorePickup->status == 1) ||
                    (isset($instoreLd->localDelivery->status) && $instoreLd->localDelivery->status == 1)) {
                    $quotesarray=[];
                }
            }
            if (isset($instoreLd->inStorePickup->status) && $instoreLd->inStorePickup->status == 1) {
                $quotesarray[] = [
                    'serviceType' => 'IN_STORE_PICKUP',
                    'code' => 'INSP',
                    'rate' => 0,
                    'transitTime' => '',
                    'title' => $warehouseData['inStoreTitle'],
                    'serviceName' => 'upsLtlServices'
                ];
            }

            if (isset($instoreLd->localDelivery->status) && $instoreLd->localDelivery->status == 1) {
                $quotesarray[] = [
                    'serviceType' => 'LOCAL_DELIVERY',
                    'code' => 'LOCDEL',
                    'rate' => $warehouseData['fee_local_delivery'],
                    'transitTime' => '',
                    'title' => $warehouseData['locDelTitle'],
                    'serviceName' => 'upsLtlServices'
                ];
            }
        }
        return $quotesarray;
    }
    /**
     * @param array $data
     * @return array
     */
    public function getWarehouseData($data)
    {
        $return = [];
        $whCollection = $this->fetchWarehouseWithID($data['location'], $data['locationId']);
        
        if(!empty($whCollection[0]['in_store']) && is_string($whCollection[0]['in_store'])){
            $instore = json_decode($whCollection[0]['in_store'], true);
        }else{
            $instore = [];
        }

        if(!empty($whCollection[0]['local_delivery']) && is_string($whCollection[0]['local_delivery'])){
            $locDel  = json_decode($whCollection[0]['local_delivery'], true);
        }else{
            $locDel = [];
        }

        if ($instore) {
            $inStoreTitle = $instore['checkout_desc_store_pickup'];
            if (empty($inStoreTitle)) {
                $inStoreTitle = "In-store pick up";
            }
            $return['inStoreTitle'] = $inStoreTitle;
            $return['suppress_other'] = $instore['suppress_other']=='1' ? true : false;
        }

        if ($locDel) {
            $locDelTitle = $locDel['checkout_desc_local_delivery'];
            if (empty($locDelTitle)) {
                $locDelTitle = "Local delivery";
            }
            $return['locDelTitle'] = $locDelTitle;
            $return['fee_local_delivery'] = $locDel['fee_local_delivery'];
            $return['suppress_other'] = $locDel['suppress_other']=='1' ? true : false;
        }
        return $return;
    }
    /**
     * This function send request and return response
     * $isAssocArray Parameter When TRUE, then returned objects will
     * be converted into associative arrays, otherwise its an object
     * @param $url
     * @param $postData
     * @param $isAssocArray
     * @return string
     */
    public function upsLTLSendCurlRequest($url, $postData, $isAssocArray = false)
    {
        $fieldString = http_build_query($postData);
        try {
            $this->curl->post($url, $fieldString);
            $output = $this->curl->getBody();
            if(!empty($output) && is_string($output)){
                $result = json_decode($output, $isAssocArray);
            }else{
                $result = ($isAssocArray) ? [] : '';
            }
        } catch (\Throwable $e) {
            $result = [];
        }
        
        return $result;
    }

    /**
     * @param object $quotes
     * @param bool $getMinimum
     * @param bool $isMultishipmentQuantity
     * @param object $scopeConfig
     * @return array
     */
    public function getQuotesResults($quotes, $getMinimum, $isMultishipmentQuantity, $scopeConfig)
    {
        $this->quoteSettingsData();

        if ($isMultishipmentQuantity) {
            return $this->getOriginsMinimumQuotes($quotes);
        }

        $allQuotes = $odwArr = $hazShipmentArr = $palletPackagingArr = [];
        $count = 0;
        foreach ($quotes as $origin => $quote) {
            if (isset($quote->severity)) {
                return [];
            }

            if ($count == 0) { //To be checked only once
                $isRad = $quote->autoResidentialsStatus ?? '';
                $this->getAutoResidentialTitle($isRad);

                $instoreLdData = $quote->InstorPickupLocalDelivery ?? false;
                unset($quote->InstorPickupLocalDelivery);

                $lgQuotes = ($this->liftGate || $this->OfferLiftgateAsAnOption ||
                            ($this->isResi && $this->RADforLiftgate)) ?  true : false;
            }

            $originQuotes = [];

            if (isset($quote->q)) {
                $code = 'UPSFREIGHT';

                if (isset($quote->hazardousStatus)) {
                    $hazShipmentArr[$origin] = $quote->hazardousStatus == 'y' ?  'Y' : 'N';
                }

                $palletPackaging = $this->setPalletPackagingData($quote, $origin);
                $palletPackagingArr[] = $palletPackaging;

                foreach ($quote as $key => $data) {
                    if (isset($data->serviceType)) {
                        $dlvryEsti = $data->transitTime ?? '';
                        $append = (!empty($dlvryEsti) && $this->dlrvyEstimates == '1') ? ' (Intransit Days: '.$dlvryEsti.')' : '';

                        $accss = $this->getAccessorial();
                        $price = $this->calculatePrice($data);
                        $title = $this->getTitle();
                        $originQuotes['simple']['code']  = $code.$accss;
                        $originQuotes['simple']['rate']  = $price;
                        $originQuotes['simple']['title'] = $title.$append;

                        if ($lgQuotes) {
                            $lgAccss = $this->getAccessorial(true);
                            $lgPrice = $this->calculatePrice($data, true);
                            $lgTitle = $this->getTitle(true);
                            $originQuotes['liftgate']['code']  = $code.$lgAccss;
                            $originQuotes['liftgate']['rate']  = $lgPrice;
                            $originQuotes['liftgate']['title'] = $lgTitle.$append;
                        }

                    }
                }
            }

            if (!empty($originQuotes)) {
                $allQuotes['simple'][] = $originQuotes['simple'];
                $lgQuotes ? $allQuotes['liftgate'][] = $originQuotes['liftgate'] : '';
                $odwArr[$origin]['quotes'] = $originQuotes;
            }
            $count++;
        }

        $multiShipment = $count > 1 ? true : false;
        $odwArr = $multiShipment ? $odwArr : [];
        $this->setOrderDetailWidgetData($odwArr, $hazShipmentArr);

        $this->coreSession->start();
        $this->coreSession->setUpsLtlPalletPackaging($palletPackagingArr);

        $allQuotes = $this->getFinalQuotesArray($allQuotes, $multiShipment);

        if (!$multiShipment && isset($instoreLdData) && !empty($instoreLdData)) {
            $allQuotes = $this->instoreLocalDeliveryQuotes($allQuotes, $instoreLdData);
        }
        return $allQuotes;
    }

    public function getFinalQuotesArray($quotes, $multiShipment)
    {
        //Not to show option as liftgate when Address is Residential and RADforLiftgate in ON
        if ($this->liftGate || ($this->isResi && $this->RADforLiftgate)) {
            unset($quotes['simple']);
        }
        $quotesArr = [];
        foreach ($quotes as $key => $value) {
            if ($multiShipment) {
                $code = $title = '';
                $rate = 0;
                foreach ($value as $key2 => $data) {
                    $code = $data['code'];
                    $rate += $data['rate'];
                    $title = $data['title'];
                }
                $quotesArr[] = ['code'=>$code,
                                'rate' => $rate,
                                'title' => $this->getMultishipTitle($title)];
            } else {
                $quotesArr[] = reset($value);
            }
        }
        return $quotesArr;
    }

    /**
     * @param array $servicesArr
     */
    public function setOrderDetailWidgetData(array $servicesArr, $hazShipmentArr)
    {
        $setPkgForOrderDetailReg = $this->registry->registry('setPackageDataForOrderDetail') ?? [];
        $planNumber = $this->upsLtlPlanName('ENUpsLTL')['planNumber'];

        if ($planNumber > 1 && $setPkgForOrderDetailReg && $hazShipmentArr) {
            foreach ($hazShipmentArr as $origin => $value) {
                foreach ($setPkgForOrderDetailReg[$origin]['item'] as $key => $data) {
                    $setPkgForOrderDetailReg[$origin]['item'][$key]['isHazmatLineItem'] = $value;
                }
            }
        }
        $orderDetail['shipmentData'] = array_replace_recursive($setPkgForOrderDetailReg, $servicesArr);
        // set order detail widget data
        $this->coreSession->start();
        $this->coreSession->setUpsLTLOrderDetailSession($orderDetail);
    }

    /**
     * @param bool $lgOption
     * @return string
     */
    public function getAccessorial($lgOption = false)
    {
        $accss = '';
        if ($this->residentialDlvry == '1' || $this->isResi) {
            $accss .= '+R';
        }
        if (($lgOption || $this->liftGate) || ($this->RADforLiftgate && $this->isResi)) {
            $accss .= '+LG';
        }

        return $accss;
    }

    /**
     * @param object $data
     * @param bool $lgOption
     * @return float
     */
    public function calculatePrice($data, $lgOption = false, $getCost = false)
    {
        $lgCost = $lgOption ? 0 : $this->getLiftgateCost($data, $getCost);
        $basePrice = (float) $data->totalNetCharge->Amount;
        $basePrice = $basePrice - $lgCost;
        $basePrice = $this->calculateHandlingFee($basePrice);
        return $basePrice;
    }

    /**
     * @param $quotes
     * @return float
     */
    public function getLiftgateCost($quotes, $getCost = false)
    {
        $lgCost = 0;
        if (!(($this->isResi && $this->RADforLiftgate) || $this->liftGate) || $getCost) {
            if (isset($quotes->surcharges)) {
                foreach ($quotes->surcharges as $key => $value) {
                    if (isset($value->Type->Code) && $value->Type->Code == 'LIFTGATE') {
                        $lgCost = $value->Factor->Value;
                    }else if(isset($value->code) && $value->code == 'LIFD'){
                        $lgCost = $value->value;
                    }
                }
            }
        }
        return $lgCost;
    }

    /**
     * Calculate Handling Fee
     * @param $cost
     * @return float
     */
    public function calculateHandlingFee($cost)
    {
        $hndlngFeeMarkup = $this->hndlngFee;
        $symbolicHndlngFee = $this->symbolicHndlngFee;

        if (!empty($hndlngFeeMarkup) && strlen($hndlngFeeMarkup) > 0) {
            if ($symbolicHndlngFee == '%') {
                $prcntVal = $hndlngFeeMarkup / 100 * $cost;
                $grandTotal = $prcntVal + $cost;
            } else {
                $grandTotal = $hndlngFeeMarkup + $cost;
            }
        } else {
            $grandTotal = $cost;
        }
        return $grandTotal;
    }

    public function getTitle($lgOption = false)
    {
        $title = '';
        if ($lgOption || $this->RADforLiftgate) {
            if ($lgOption && !$this->liftGate) {
//                $title =  $this->lgLabel;
                $title =  $this->isResi ? $this->resiLgLabel : $this->lgLabel;
            }

            if ($this->liftGate && $this->isResi) {
                $title = $this->resiLabel;
            }

            if ($this->RADforLiftgate && $this->isResi) {
                $title = $this->resiLgLabel;
            }
        } elseif ($this->isResi) {
            $title = $this->resiLabel;
        }
        return $this->labelAs.$title;
    }

    /**
     * @param string $title
     * @return string
     *
     * This function returns the proper label for Multishipemnt removing transit days and LabelAs given by user
     */
    public function getMultishipTitle($title)
    {
        $title = (strpos($title, '(') !== false) ? strstr($title, '(', true) : $title;
        return str_replace($this->labelAs, "Freight", $title);
    }

    /**
     * @param string $resi
     * @return string
     */
    public function getAutoResidentialTitle($resi)
    {
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $isRadSuspend = $this->getConfigData("resaddressdetection/suspend/value");
            if ($this->residentialDlvry == "1") {
                $this->residentialDlvry = $isRadSuspend == "no" ? '0' : '1';
            } else {
                $this->residentialDlvry = $isRadSuspend == "no" ? '0' : $this->residentialDlvry;
            }

            if ($this->residentialDlvry == null || $this->residentialDlvry == '0') {
                if ($resi == 'r') {
                    $this->isResi = true;
                }
            }
        }
    }

    /**
     * @param array $quotes
     * @param array $allConfigServices
     * @param object $scopeConfig
     * @return array
     */
    public function getOriginsMinimumQuotes($quotes)
    {
        $minIndexArr = $palletPackagingArr = [];
        $resiArr = ['residential' => false, 'label' => ''];
        $hazShipment = $resi = '';
        $counter = 0;
        $plan = $this->upsLtlPlanName('ENUpsLTL')['planNumber'];

        foreach ($quotes as $origin => $quote) {
            if (isset($quote->severity)) {
                return [];
            }

            $palletPackaging = $this->setPalletPackagingData($quote, $origin);
            $palletPackagingArr[] = $palletPackaging;

            if ($counter == 0) { //To be checked only once
                $isRad = $quote->autoResidentialsStatus ?? '';
                $this->getAutoResidentialTitle($isRad);
                $resi = $this->isResi ? $this->resiLabel : '';
                if ($this->residentialDlvry || $this->isResi) {
                    $resiArr = ['residential' => true, 'label' => $resi];
                }
            }

            if (isset($quote->q)) {
                $hazShipment = 'N';
                if ($plan > 1 && isset($quote->hazardousStatus) && $quote->hazardousStatus == 'y') {
                    $hazShipment = 'Y';
                }

                foreach ($quote as $key => $data) {
                    if (isset($data->serviceType)) {
                        $currentArray = ['code' => 'UPSFREIGHT',
                            'rate' => $this->calculatePrice($data, false, true),
                            'title' => $this->labelAs . ' ' . $resi,
                            'resi' => $resiArr,
                            'hazShipment' =>$hazShipment];

                        $counter++;
                    }
                }
            }
            $minIndexArr[$origin] = $currentArray;
        }

        $this->coreSession->start();
        $this->coreSession->setSemiPalletPackaging($palletPackagingArr);
        return $minIndexArr;
    }

    /**
     * @param string $confPath
     * @return string|int|null
     */
    public function getConfigData($confPath)
    {
        return $this->scopeConfig->getValue($confPath, ScopeInterface::SCOPE_STORE);
    }
    /**
     * @return array
     */
    function getActiveCarriersForENCount()
    {
        return $this->shippingConfig->getActiveCarriers();
    }
    
    /**
     * @return string
     */
    public function upsLtlSetPlanNotice($planRefreshUrl = '')
    {
        $planPackage = $this->upsLtlPlanName('ENUpsLTL');
        if (is_null($planPackage['storeType'])) {
            $planPackage = [];
        }
        $planMsg = $this->displayPlanMessages($planPackage, $planRefreshUrl);
        return $planMsg;
    }

    /**
     * @param type $planPackage
     * @return type
     */
    public function displayPlanMessages($planPackage, $planRefreshUrl = '')
    {
        $planRefreshLink = '';
        if (!empty($planRefreshUrl)) {
            $planRefreshLink = ', <a href="javascript:void(0)" id="ups-ltl-plan-refresh-link" planRefAjaxUrl = '.$planRefreshUrl.' onclick="upsLTLPlanRefresh(this)" >click here</a> to update the license info. Afterward, sign out of Magento and then sign back in';
            $planMsg = __('The subscription to the TForce LTL Freight Quotes module is inactive. If you believe the subscription should be active and you recently changed plans (e.g. upgraded your plan), your firewall may be blocking confirmation from our licensing system. To resolve the situation, <a href="javascript:void(0)" id="plan-refresh-link" planRefAjaxUrl = '.$planRefreshUrl.' onclick="upsLTLPlanRefresh(this)" >click this link</a> and then sign in again. If this does not resolve the issue, log in to eniture.com and verify the license status.');
        }else{
            $planMsg = __('The subscription to the TForce LTL Freight Quotes module is inactive. Please log into eniture.com and update your license.');
        }

        if (isset($planPackage) && !empty($planPackage)) {
            if (!empty($planPackage['planNumber']) && $planPackage['planNumber'] != '-1') {
                $planMsg = __('The TForce LTL Freight Quotes from Eniture Technology is currently on the '.$planPackage['planName'].' and will renew on '.$planPackage['expiryDate'].'. If this does not reflect changes made to the subscription plan'.$planRefreshLink.'.');
            }
        }

        return $planMsg;
    }

    /**
     * Get Plan
     * @return array
     */
    public function upsLtlPlanName($carrierId)
    {
        $appData = $this->getConfigData("eniture/".$carrierId);

        $plan       = $appData["plan"] ?? '';
        $storeType  = $appData["storetype"] ?? '';
        $expireDays = $appData["expireday"] ?? '';
        $expiryDate = $appData["expiredate"] ?? '';
        $planName = "";

        switch ($plan) {
            case 3:
                $planName = "Advanced Plan";
                break;
            case 2:
                $planName = "Standard Plan";
                break;
            case 1:
                $planName = "Basic Plan";
                break;
            case 0:
                $planName = "Trial Plan";
                break;
        }
        $packageArray = [
            'planNumber' => $plan,
            'planName' => $planName,
            'expireDays' => $expireDays,
            'expiryDate' => $expiryDate,
            'storeType' => $storeType
        ];
        return $packageArray;
    }
    /**
     * @return int
     */
    public function whPlanRestriction()
    {
        $planNumber = $this->upsLtlPlanName('ENUpsLTL')['planNumber'];
        $warehouses = $this->fetchWarehouseSecData('warehouse');

        if ($planNumber < 2 && count($warehouses)) {
            $this->canAddWh = 0;
        }
        return $this->canAddWh;
    }

    /**
     * @return int
     */
    public function checkAdvancePlan()
    {
        $advncPlan = 1;
        $planNumber = $this->upsLtlPlanName('ENUpsLTL')['planNumber'];

        if ($planNumber != 3) {
            $advncPlan = 0;
        }
        return $advncPlan;
    }

    /**
     * Function to clear cache
     */
    public function clearCache()
    {
        $types = $this->cacheManager->getAvailableTypes();
        $this->cacheManager->flush($types);
        $this->cacheManager->clean($types);
    }

    /**
     * Function that returns Pallet Packaging helper object
     */
    public function getPalletPackagingHelper()
    {
        return $this->objectManager->get("Eniture\PalletPackaging\Helper\Data");
    }

    /**
     * Function to set Pallet Packaging data
     * @param type $quote
     * @param type $key
     * @return array
     */
    public function setPalletPackagingData($quote, $key)
    {
        $palletPackaging = [];
        if (isset($quote->standardPackagingData)) {
            $palletPackaging[$key]['upsLtlServices'] = $quote->standardPackagingData ;
        }
        return $palletPackaging;
    }
}
