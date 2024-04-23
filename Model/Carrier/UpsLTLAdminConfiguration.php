<?php
namespace Eniture\UPSLTLFreightQuotes\Model\Carrier;

/**
 * class for admin configuration that runs first
 */
class UpsLTLAdminConfiguration
{

    /**
     * @var object
     */
    private $registry;
    /**
     * @var Object
     */
    private $scopeConfig;
 
    /**peConfig
     * @param $scopeConfig
     * @param $registry,
     */
    public function _init($scopeConfig, $registry)
    {
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        $this->setCarriersAndHelpersCodesGlobaly();
        $this->myUniqueLineItemAttribute();
    }
    /**
     * This function set unique Line Item Attributes of carriers
     */
    public function myUniqueLineItemAttribute()
    {
        $lineItemAttArr =  [];
        if ($this->registry->registry('UniqueLineItemAttributes') === null) {
            $this->registry->register('UniqueLineItemAttributes', $lineItemAttArr);
        }
    }
    
    /**
     * This function is for set carriers codes and helpers code globaly
     */
    public function setCarriersAndHelpersCodesGlobaly()
    {
        $this->setCodesGlobally('enitureCarrierCodes', 'ENUpsLTL');
        $this->setCodesGlobally('enitureCarrierTitle', 'TForce LTL Freight Quotes');
        $this->setCodesGlobally('enitureHelpersCodes', '\Eniture\UPSLTLFreightQuotes');
        $this->setCodesGlobally('enitureActiveModules', $this->checkModuleIsEnabled());
        $this->setCodesGlobally('enitureModuleTypes', 'ltl');
    }
    /**
     * return if this module is enable or not
     * @return boolean
     */
    public function checkModuleIsEnabled()
    {
        return $this->scopeConfig->getValue("carriers/ENUpsLTL/active", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * This function sets Codes Globally e.g carrier code or helper code
     * @param $globArrayName
     * @param $arrValue
     */
    public function setCodesGlobally($globArrayName, $arrValue)
    {
        if ($this->registry->registry($globArrayName) === null) {
            $codesArray = [];
            $codesArray['upsLTL'] = $arrValue;
            $this->registry->register($globArrayName, $codesArray);
        } else {
            $codesArray = $this->registry->registry($globArrayName);
            $codesArray['upsLTL'] = $arrValue;
            $this->registry->unregister($globArrayName);
            $this->registry->register($globArrayName, $codesArray);
        }
    }
}
