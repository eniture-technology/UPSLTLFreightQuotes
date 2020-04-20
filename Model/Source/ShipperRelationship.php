<?php


namespace Eniture\UPSLTLFreightQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ShipperRelationship implements ArrayInterface
{

    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
                    [
                        'value' => 'Shipper',
                        'label' => __('Shipper')
                    ],
                    [
                        'value' => 'ThirdParty',
                        'label' => __('Third Party')
                    ]
        ];
    }
}
