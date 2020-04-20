<?php


namespace Eniture\UPSLTLFreightQuotes\Model\Source;

class UpsLtlAccessLevel implements \Magento\Framework\Option\ArrayInterface
{
    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            [
                'value' => '',
                'label' => __('Select')
            ],
            [
                'value' => 'test',
                'label' => __('Testing')
            ],
            [
                'value' => 'pro',
                'label' => __('Production')
            ],
        ];
    }
}
