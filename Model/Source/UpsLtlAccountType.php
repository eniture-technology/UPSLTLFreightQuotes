<?php

namespace Eniture\UPSLTLFreightQuotes\Model\Source;

class UpsLtlAccountType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            [
                'value' => 'freight',
                'label' => __('Freight class')
            ],
            [
                'value' => 'dimension',
                'label' => __('Dimensions')
            ],
        ];
    }
}
