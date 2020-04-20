<?php
namespace Eniture\UPSLTLFreightQuotes\Model\Carrier;

/**
 * This class manages all quotes values
 */
class UpsLTLManageAllQuotes
{

    /**
     * stores array of quotes
     * @var array
     */
    private $quotes;
    /**
     * @var
     */
    private $scopeConfig;
    /**
     * @var
     */
    private $registry;

    /**
     * @var
     */
    public $session;
    /**
     * @var
     */
    private $objectManager;
    /**
     * @var array
     */
    private $smallPackagesQuotes;
    /**
     * @var array
     */
    private $ltlPackagesQuotes;

    private $odwData;

    private $resiLabel = '';

    /**
     * @param $scopeConfig
     * @param $registry
     * @param $session
     * @param $objectManager
     */
    public function _init(
        $scopeConfig,
        $registry,
        $session,
        $objectManager
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->registry        = $registry;
        $this->session         = $session;
        $this->objectManager   = $objectManager;
    }

    /**
     * This function accepts all quotes data and sends to its respective module functions to
     * process and return final result array.
     * @param $quotes
     * @return array | bool
     */
    public function getQuotesResultArr($quotes)
    {
        $this->quotes   = $quotes;
        $moduleTypesArr = $this->registry->registry('enitureModuleTypes');
        $quotesArr      = (array)$this->quotes;
        $quotesArr      = $this->removeErrorFromQuotes($quotesArr);

        $this->quotes = $quotesArr;

        $quotesCount = count($quotesArr);

        if ($quotesCount == 1) {
            $servicesArr = $this->upsGetAllQuotes();
            return $servicesArr;
        } elseif ($quotesCount > 1) {
            $smallModulesArr = array_filter($moduleTypesArr, function ($value) {
                return ($value == 'small');
            });

            $ltlModulesArr = array_filter($moduleTypesArr, function ($value) {
                return ($value == 'ltl');
            });

            $this->smallPackagesQuotes    = array_intersect_key($quotesArr, $smallModulesArr);
            $this->ltlPackagesQuotes      = array_intersect_key($quotesArr, $ltlModulesArr);

            if (count($this->smallPackagesQuotes) == 0 || count($this->ltlPackagesQuotes) == 0) {
                return $this->upsGetAllQuotes();
            } else {
                $multishipmentValue = $this->checkMultiPackaging();

                if ($multishipmentValue == 'all') {
                    return $this->upsGetAllQuotes();
                } elseif ($multishipmentValue == 'semi') {
                    $resultQuotes =  $this->upsGetAllQuotes(false, true);
                    return $this->getQuotesForMultiShipment($resultQuotes, $smallModulesArr, $ltlModulesArr);
                } else {
                    $resultQuotesArr =  $this->upsGetAllQuotes(true, true);
                    $smallQuotesArr = array_intersect_key($resultQuotesArr, $smallModulesArr);
                    $ltlQuotesArr = array_intersect_key($resultQuotesArr, $ltlModulesArr);
                    $this->setOdwData($smallQuotesArr, $ltlQuotesArr, []);
                    $minsmallRate = $this->findMininumSmall($smallQuotesArr);
                    return $this->updateLtlQuotes($ltlQuotesArr, $minsmallRate);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * This function returns final quotes to show
     * @param array $resultQuotes
     * @param array $smallModulesArr
     * @param array $ltlModulesArr
     * @return array
     */
    public function getQuotesForMultiShipment($resultQuotes, $smallModulesArr, $ltlModulesArr)
    {
        $smallQuotesArr     = array_intersect_key($resultQuotes, $smallModulesArr);
        $ltlQuotesArr       = array_intersect_key($resultQuotes, $ltlModulesArr);
        $allLtlQuotesArr    = $this->getAllQuotes($ltlQuotesArr);
        $allSmallQuotesArr  = $this->getAllQuotes($smallQuotesArr);
        $commonQuotesArr    = array_intersect_key($allLtlQuotesArr, $allSmallQuotesArr);
        $minimumCommonArr   = $this->getMinimumCommonQuotes($commonQuotesArr, $resultQuotes);
        $this->setOdwData($smallQuotesArr, $ltlQuotesArr, $minimumCommonArr);
        $minimumSmallRate   = $this->getMinimumSmallQuotesRate($minimumCommonArr, $smallQuotesArr);
        $ltlQuotesArray     = $this->getLtlQuoteForMultishipping($minimumCommonArr, $ltlModulesArr, $ltlQuotesArr, $minimumSmallRate);
        return $ltlQuotesArray;
    }

    /**
     * This function returns Ltl quotes for multi shipping
     * @param array $minimumCommonArr
     * @param array $ltlQuotesArr
     * @param float $minimumSmallRate
     * @return array
     */
    public function getLtlQuoteForMultishipping($minimumCommonArr, $ltlModulesArr, $ltlQuotesArr, $minimumSmallRate)
    {
        $ltlQuotesFinalArr = [];

        // if ltl quotes return nothing
        if (!empty($ltlQuotesArr)) {
            foreach ($ltlQuotesArr as $mainkey => $originArr) {
                $ltlRate = $minimumSmallRate;

                foreach ($originArr as $key => $value) {
                    if (!array_key_exists($key, $minimumCommonArr)) {
                        $ltlRate = $ltlRate + $value['rate'];
                    }
                }
                $ltlQuotesFinalArr[$mainkey][] = [
                    'code'  => 'Freight',
                    'title' => 'Freight '.$this->resiLabel,
                    'rate'  => $ltlRate
                ];
            }
        } else {
            if ($minimumSmallRate > 0) {
                foreach ($ltlModulesArr as $key => $value) {
                    $ltlQuotesFinalArr[$key][] = [
                        'code'  => 'Freight',
                        'title' => 'Freight '.$this->resiLabel,
                        'rate'  => $minimumSmallRate
                    ];
                }
            }
        }
        return $ltlQuotesFinalArr;
    }

    /**
     * This function returns minimum in common quotes
     * @param array $commonQuotesArr
     * @param array $resultQuotes
     * @return array
     */
    public function getMinimumCommonQuotes(
        $commonQuotesArr,
        $resultQuotes
    ) {
        if (!empty($commonQuotesArr)) {
            $minimumCommonQuotesArr = $commonQuotesArr;

            foreach ($resultQuotes as $mainkey => $originArr) {
                foreach ($originArr as $key => $value) {
                    if (empty($minimumCommonQuotesArr)) {
                        $minimumCommonQuotesArr[$key] = $value;
                    }
                    if (array_key_exists($key, $minimumCommonQuotesArr)) {
                        if ($value['rate'] == '0') {
                            if (isset($this->quotes[$mainkey][$key]->severity) && $this->quotes[$mainkey][$key]->severity == 'ERROR') {
                                continue;
                            }
                        }
                        if ($value['rate'] < $minimumCommonQuotesArr[$key]['rate']) {
                            $minimumCommonQuotesArr[$key] = $value;
                        }
                    }
                }
            }
        } else {
            $minimumCommonQuotesArr = [];
        }

        return $minimumCommonQuotesArr;
    }

    /**
     * This function returns the minimum from common and small quotes
     * @param array $minimumCommonArr
     * @param array $smallQuotes
     * @return float
     */
    public function getMinimumSmallQuotesRate(
        $minimumCommonArr,
        $smallQuotes
    ) {
        $minimumSmallQuotes = [];
        if (isset($smallQuotes) && !empty($smallQuotes)) {
            foreach ($smallQuotes as $mainkey => $originArr) {
                foreach ($originArr as $key => $value) {
                    if (!array_key_exists($key, $minimumCommonArr)) {
                        if (array_key_exists($key, $minimumSmallQuotes)) {
                            if ($value['rate'] < $minimumSmallQuotes[$key]['rate']) {
                                $minimumSmallQuotes[$key] = $value;
                            }
                        } else {
                            $minimumSmallQuotes[$key] = $value;
                        }
                    }
                }
            }
        }
        $minSmallQuotesArray = array_merge($minimumSmallQuotes, $minimumCommonArr);
        $sumMinSmall = array_sum(array_column($minSmallQuotesArray, 'rate'));
        return $sumMinSmall;
    }

    /**
     * This function put specific array to common array
     * @param array $quotesArr
     * @return array
     */
    public function getAllQuotes($quotesArr)
    {
        $newQuotesArr = [];
        foreach ($quotesArr as $value) {
            foreach ($value as $key => $originArr) {
                $newQuotesArr[$key] = $originArr;
            }
        }
        return $newQuotesArr;
    }

    /**
     * This function returns is quotes have multipackaging or not
     * @return string
     */
    public function checkMultiPackaging()
    {
        $ltlOriginArr = $smallOriginArr = $commonValuesArr = [];
        $multiPackage = 'no';

        foreach ($this->ltlPackagesQuotes as $mainKey => $mainValue) {
            foreach ($mainValue as $key => $value) {
                array_push($ltlOriginArr, $key);
            }
        }

        foreach ($this->smallPackagesQuotes as $mainKey => $mainValue) {
            foreach ($mainValue as $key => $value) {
                array_push($smallOriginArr, $key);
            }
        }

        $commonValuesArr = array_intersect($ltlOriginArr, $smallOriginArr);
        if (!empty($commonValuesArr)) {
            $multiPackage = 'semi';
            if (count($commonValuesArr) == count($ltlOriginArr) && count($commonValuesArr) == count($smallOriginArr)) {
                $multiPackage = 'all';
            }
        }

        return $multiPackage;
    }

    /**
     * This function removes errors from quotes array
     * @param $QuotesArr
     * @return array
     */
    public function removeErrorFromQuotes($QuotesArr)
    {
        $updatedArr = [];

        if (isset($QuotesArr->error) && $QuotesArr->error) {
            $updatedArr = $QuotesArr;
        }
        foreach ($QuotesArr as $mainkey => $mainvalue) {
            if (isset($mainvalue->severity) && $mainvalue->severity == 'ERROR') {
                $updatedArr[$mainkey] = $mainvalue;
            }

            if (isset($mainvalue->error) && $mainvalue->error) {
                $updatedArr[$mainkey] = $mainvalue;
            }

            if ((is_object($mainvalue) || is_array($mainvalue)) && !empty($mainvalue)) {
                foreach ($mainvalue as $key => $value) {
                    if (isset($value->error) && $value->error == 1 && isset($value->dismissedProduct)) {
                        continue;
                    } elseif (isset($value->severity)
                        && $value->severity == 'ERROR'
                        && isset($value->dismissedProduct)
                    ) {
                        continue;
                    } else {
                        $updatedArr[$mainkey][$key] = $value;
                    }
                }
            }
        }
        return $updatedArr;
    }

    /**
     * This funtion add small min small quotes value in Ltl quotes
     * @param array $ltlQuotesArr
     * @param int $minsmallRate
     * @return array
     */
    public function updateLtlQuotes($ltlQuotesArr, $minsmallRate)
    {
        $appName = key($ltlQuotesArr);
        $ltlQuotesArr = reset($ltlQuotesArr);
        $updatedltlQuotesArr = [];
        if (!empty($ltlQuotesArr)) {
            foreach ($ltlQuotesArr as $key => $value) {
                $minsmallRate += $value['rate'];
            }
            $updatedltlQuotesArr[$appName][] = [
                'code'  => 'Freight',
                'title' => 'Freight '.$this->resiLabel,
                'rate'  => $minsmallRate
            ];
        } else {
            foreach ($this->ltlPackagesQuotes as $key => $value) {
                $updatedltlQuotesArr[$key][] = [
                    'code'  => 'Freight',
                    'title' => 'Freight',
                    'rate'  => $minsmallRate
                ];
            }
        }
        return $updatedltlQuotesArr;
    }

    /**
     * This function finds the minimum rates value from small quotes
     * @param array $smallArr
     * @return int
     */
    public function findMininumSmall($smallArr)
    {

        $smallArr = reset($smallArr);
        $counter = 1;
        $minimum = '0';
        foreach ($smallArr as $origin => $data) {
            if ($counter == 1) {
                $minimum =  $data['rate'];
                $counter = 0;
            } else {
                if ($data['rate'] < $minimum) {
                    $minimum =  $data['rate'];
                }
            }
        }
        return $minimum;
    }


    /**
     * This function gets quotes result from all active modules
     * @param boolean $getMinimum
     * @param bool $isMultishipment
     * @return array
     */
    public function upsGetAllQuotes($getMinimum = false, $isMultishipment = false)
    {
        $helpersArr = $this->registry->registry('enitureHelpersCodes');
        $resultArr = [];
        foreach ($this->quotes as $key => $quote) {
            $helperId = $helpersArr[$key];
            $dataHelper = $this->objectManager->get("$helperId\Helper\Data");
            $upsSmpkgResultData = $dataHelper->getQuotesResults($quote, $getMinimum, $isMultishipment, $this->scopeConfig);

            if ($upsSmpkgResultData != false && $upsSmpkgResultData !== null) {
                $resultArr[$key] = $upsSmpkgResultData;
            }
        }
        return $resultArr;
    }

    /**
     * @param array $smallQuotesArr
     * @param array $ltlQuotesArr
     * @param array $minimumCommonArr
     */
    public function setOdwData($smallQuotesArr, $ltlQuotesArr, $minimumCommonArr)
    {
        $smallQuotesArr = !empty($smallQuotesArr) ? reset($smallQuotesArr) : [];
        $ltlQuotesArr   = !empty($ltlQuotesArr) ? reset($ltlQuotesArr) : [];
        $allQuotesArr   = $smallQuotesArr + $ltlQuotesArr;

        if (!empty($allQuotesArr)) {
            foreach ($allQuotesArr as $origin => $data) {
                $this->odwData[$origin] = $minimumCommonArr[$origin] ?? $data;
            }
            $this->setOrderDetailData();
        }
    }

    public function setOrderDetailData()
    {
        $this->addQuotesIndex();
        $orderDetail['residentialDelivery'] = 0;
        $setPkgForOrderDetailReg = $this->registry->registry('setPackageDataForOrderDetail') ?? [];

        if ($setPkgForOrderDetailReg && $this->odwData) {
            foreach ($this->odwData as $origin => $value) {
                foreach ($setPkgForOrderDetailReg[$origin]['item'] as $key => $data) {
                    $setPkgForOrderDetailReg[$origin]['item'][$key]['isHazmatLineItem'] = $value['hazShipment'] ?? 'N';
                }
            }
        }
        $orderDetail['shipmentData'] = array_replace_recursive($setPkgForOrderDetailReg, $this->odwData);
        // set order detail widget data
        $this->session->start();
        $this->session->setSemiOrderDetailSession($orderDetail);
    }

    public function addQuotesIndex()
    {
        $dataArray = [];
        foreach ($this->odwData as $key => $array) {
            $resi = $array['resi']['residential'] ?? false;
            $this->resiLabel = $array['resi']['label'];
            unset($array['resi']);
            $array['residentialDelivery'] = $resi;
            $dataArray[$key] = ['quotes'=> $array];
        }
        $this->odwData = $dataArray;
    }
}
