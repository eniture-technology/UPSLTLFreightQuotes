<?php
namespace Eniture\UPSLTLFreightQuotes\Model\Source;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Source class for Warehouse and Dropship
 */
class DropshipOptions extends AbstractSource
{
    /**
     * @var Data
     */
    public $dataHelper;
    /**
     * @var array
     */
    public $options = [];

    /**
     * DropshipOptions constructor.
     *
     * @param Data $dataHelper
     */
    public function __construct(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param int|string $value
     * @return bool|string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions(false);

        foreach ($options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        $get_dropship = $this->dataHelper->fetchWarehouseSecData('dropship');
        
        if (isset($get_dropship) && count($get_dropship) > 0) {
            foreach ($get_dropship as $manufacturer) {
                ( isset($manufacturer['nickname']) && $manufacturer['nickname'] == '' ) ? $nickname = '' : $nickname = html_entity_decode($manufacturer['nickname'], ENT_QUOTES).' - ';
                $city       = $manufacturer['city'];
                $state      = $manufacturer['state'];
                $zip        = $manufacturer['zip'];
                $dropship   = $nickname.$city.', '.$state.', '.$zip;
                $this->options[] = [
                        'label' => __($dropship),
                        'value' => $manufacturer['warehouse_id'],
                    ];
            }
        }
        return $this->options;
    }
}
