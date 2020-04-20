<?php
namespace Eniture\UPSLTLFreightQuotes\App;

/**
 * Class State
 * @package Eniture\UPSLTLFreightQuotes\App
 *
 *
 */
class State extends \Magento\Framework\App\State
{
    /**
     * @return bool
     */
    public function validateAreaCode()
    {
        if (!isset($this->_areaCode)) {
            $this->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        }
    }
}
