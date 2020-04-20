<?php


namespace Eniture\UPSLTLFreightQuotes\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class ThirdPartyCountry implements ArrayInterface
{

    /**
     *
     * @return array
     */
    public function toOptionArray()
    {
        return  [
            [
                'value' => 'US',
                'label' => __('United States')
            ],
            [
                'value' => 'CA',
                'label' => __('Canada')
            ],
            [
                'value' => 'GU',
                'label' => __('Guam')
            ],
            [
                'value' => 'MX',
                'label' => __('Mexico')
            ],
            [
                'value' => 'PR',
                'label' => __('Puerto Rico')
            ],
            [
                'value' => 'VI',
                'label' => __('US Virgin Islands')
            ],
        ];
    }
}
