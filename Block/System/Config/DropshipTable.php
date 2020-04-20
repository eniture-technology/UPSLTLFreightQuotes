<?php
namespace Eniture\UPSLTLFreightQuotes\Block\System\Config;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Helper\EnConstants;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DropshipTable extends Field
{
    const DROPSHIP_TEMPLATE = 'system/config/dropship.phtml';
 
    public $dataHelper;

    public $enUrl = EnConstants::EN_URL;
    /**
     * @param Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::DROPSHIP_TEMPLATE);
        }
        return $this;
    }
    /**
     * @param AbstractElement $element
     * @return html
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @return string
     */
    public function getDsAjaxUrl()
    {
        return $this->getbaseUrl().'upsltlfreightquotes/Dropship/';
    }

    /**
     * @param AbstractElement $element
     * @return type
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    /**
     * this function return the current plan active
     * @return mixed
     */
    public function getCurrentPlan()
    {
        return $this->dataHelper->checkAdvancePlan();
    }
}
