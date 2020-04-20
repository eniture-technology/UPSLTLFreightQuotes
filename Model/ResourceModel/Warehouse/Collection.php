<?php

namespace Eniture\UPSLTLFreightQuotes\Model\ResourceModel\Warehouse;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Define resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Eniture\UPSLTLFreightQuotes\Model\Warehouse', 'Eniture\UPSLTLFreightQuotes\Model\ResourceModel\Warehouse');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
