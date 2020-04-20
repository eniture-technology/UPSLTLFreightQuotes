<?php
/**
 * UPS Small Package
 * @package     UPS Small Package
 * @author      Eniture Technology
 */
namespace Eniture\UPSLTLFreightQuotes\Model\Carrier;

/**
 * Class for set carriers globally
 */
class UPSLTLSetCarriersGlobaly
{
    /**
     * @var
     */
    public $dataHelper;
    /**
     * @var
     */
    public $registry;

    /**
     * constructor of class
     * @param $dataHelper
     */
    public function _init($dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }
    
    /**
     * function for magange carriers globally
     * @param $upsArr
     * @return boolean
     */
    public function manageCarriersGlobaly($upsArr, $registry)
    {
        $this->registry = $registry;
        if ($this->registry->registry('enitureCarriers') === null) {
            $enitureCarriersArray = [];
            $enitureCarriersArray['upsLTL'] = $upsArr;
            $this->registry->register('enitureCarriers', $enitureCarriersArray);
        } else {
            $carriersArr = $this->registry->registry('enitureCarriers');
            $carriersArr['upsLTL'] = $upsArr;
            $this->registry->unregister('enitureCarriers');
            $this->registry->register('enitureCarriers', $carriersArr);
        }
        
        $activeEnitureModulesCount = $this->getActiveEnitureModulesCount();

        if (count($this->registry->registry('enitureCarriers')) < $activeEnitureModulesCount) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * function that return count of active eniture modules
     * @return int
     */
    public function getActiveEnitureModulesCount()
    {
        $activeModules = array_keys($this->dataHelper->getActiveCarriersForENCount());
        $activeEnitureModulesArr = array_filter($activeModules, function ($moduleName) {
            if (substr($moduleName, 0, 2) == 'EN') {
                return true;
            }
                return false;
        });
            
        return count($activeEnitureModulesArr);
    }
}
