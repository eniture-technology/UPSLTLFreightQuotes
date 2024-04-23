<?php
namespace Eniture\UPSLTLFreightQuotes\Controller\Warehouse;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Helper\EnConstants;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class UpsLTLOriginAddress extends Action
{
    /**
     * @var Data Object
     */
    private $dataHelper;
    /**
     * @var Object
     */
    private $scopeConfig;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param Data $dataHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    
    public function __construct(
        Context $context,
        Data $dataHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dataHelper  = $dataHelper;
        $this->scopeConfig = $scopeConfig;
        $this->request     = $context->getRequest();
        parent::__construct($context);
    }
    /**
     * Get address from Google API
     */
    public function execute()
    {
        foreach ($this->getRequest()->getParams() as $key => $post) {
            $data[$key] = htmlspecialchars($post, ENT_QUOTES);
        }

        $originZip = isset($data['origin_zip']) ? $data['origin_zip'] : '';

        if ($originZip) {
            $mapResult = $this->googleApiCurl($originZip, EnConstants::GOOGLE_URL);
            $error = $this->errorChecking($mapResult);

            if (empty($error)) {
                $addressArray = $this->addressArray($mapResult);
            } else {
                $addressArray = $error;
            }

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(json_encode($addressArray));
        }
    }
      
    /**
     * GOOGLE API Curl
     * @param $originZip
     * @param $curlUrl
     * @return array
     */
    public function googleApiCurl($originZip, $curlUrl)
    {
        $licnsKey = $this->scopeConfig->getValue('UpsLtlConnSettings/first/upsltlLicnsKey', ScopeInterface::SCOPE_STORE);
        $post = [
            'acessLevel'        => 'address',
            'address'           => $originZip,
            'eniureLicenceKey'  => $licnsKey,
            'ServerName'        => $this->request->getServer('SERVER_NAME'),
        ];

        $response = $this->dataHelper->upsLTLSendCurlRequest($curlUrl, $post, true);
        return $response;
    }
    
    /**
     * Check If Error
     * @param $mapResult
     * @return array
     */
    public function errorChecking($mapResult)
    {
        $error = [];
        if (isset($mapResult['error']) && !empty($mapResult['error'])) {
            $error = ['error' => 'true',
                'msg' => $mapResult['error']]; //License key is invalid for this domain
        }

        if (isset($map_result['results'], $map_result['status']) && (empty($map_result['results'])) && ($map_result['status'] == "ZERO_RESULTS")) {
            $error = ['error' => 'true',
                'msg' => 'Request is not processed, please enter Warehouse information manually.'];
        }

        if (empty($mapResult)) {
            $error = ['error' => 'true',
                'msg' => 'Request is not processed, please enter Warehouse information manually.'];
        }

        if (isset($mapResult['results']) && count($mapResult['results']) == 0) {
            $error = ['error' => 'false',
                'msg' => 'Please select US or CA address.'];
        }
        return $error;
    }
    
    /**
     * Calculate Address
     * @param $mapResult
     * @return array
     */
    public function addressArray($mapResult)
    {
        $city = $state = $country = $firstCity = $cityOption = $cityName = "";
        $postcodeLocalities = 0;
        
        $arrComponents = $mapResult['results'][0]['address_components'];
        $checkPostcodeLocalities = (isset($mapResult['results'][0]['postcode_localities'])) ?
                $mapResult['results'][0]['postcode_localities'] : '';
            
        if ($checkPostcodeLocalities) {
            foreach ($mapResult['results'][0]['postcode_localities'] as $index => $component) {
                $firstCity = ( $index == 0 ) ? $component : $firstCity;
                $cityOption .= '<option value="' . trim($component) . '"> ' . $component . ' </option>';
            }

            $city = '<select id="wh-multi-city" class="city-multiselect city-select" name="wh-multi-city" aria-required="true" aria-invalid="false">
                ' . $cityOption . '</select>';
            $postcodeLocalities = 1;
        } elseif ($arrComponents) {
            foreach ($arrComponents as $index => $component) {
                $type = $component['types'][0];
                if ($city == "" && ( $type == "sublocality_level_1" || $type == "locality" )) {
                    $cityName = trim($component['long_name']);
                }
            }
        }
        
        if ($arrComponents) {
            foreach ($arrComponents as $index => $stateApp) {
                $type = $stateApp['types'][0];
                if ($state == "" && ( $type == "administrative_area_level_1" )) {
                    $stateName = trim($stateApp['short_name']);
                    $state = $stateName;
                }

                if ($country == "" && $type == "country") {
                    $countryName = trim($stateApp['short_name']);
                    $country = $countryName;
                }
            }
        }
        
        return $this->originAddressArray($firstCity, $cityName, $city, $state, $this->getCountryCode($country), $postcodeLocalities, $cityOption);
    }
    
    /**
     * This function returns address array
     * @param $firstCity
     * @param $cityName
     * @param $city
     * @param $state
     * @param $country
     * @param $postcodeLocalities
     * @return array
     */
    public function originAddressArray($firstCity, $cityName, $city, $state, $country, $postcodeLocalities, $cityOptions)
    {
        return [
            'first_city'            => $firstCity,
            'city'                  => $cityName,
            'city_option'           => $city, //It contains the select element too
            'state'                 => $state,
            'country'               => $country,
            'postcode_localities'   => $postcodeLocalities,
            'cityOptions'           => $cityOptions
        ];
    }
    /**
     * @param $country
     * @return String
     */
    public function getCountryCode($country)
    {
        $country = strtoupper($country);
        $countryCode = '';
        switch ($country) {
            case 'USA':
                $countryCode = 'US';
                break;
            case 'CN':
            case 'CA':
            case 'CAN':
                $countryCode = 'CA';
                break;
            default:
                $countryCode = $country;
                break;
        }
        return $countryCode;
    }
}
