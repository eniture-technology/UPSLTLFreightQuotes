<?php

namespace Eniture\UPSLTLFreightQuotes\Model\ResourceModel\Enituremodules;

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
        $this->_init('Eniture\UPSLTLFreightQuotes\Model\Enituremodules', 'Eniture\UPSLTLFreightQuotes\Model\ResourceModel\Enituremodules');
        $this->_map['fields']['page_id'] = 'main_table.page_id';
    }
}
