<?php
namespace Eniture\UPSLTLFreightQuotes\Model;

use Magento\Framework\Model\AbstractModel;

class Warehouse extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\UPSLTLFreightQuotes\Model\ResourceModel\Warehouse');
    }
}
