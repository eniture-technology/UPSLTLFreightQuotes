<?php
namespace Eniture\UPSLTLFreightQuotes\Block\System\Config;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Helper\EnConstants;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class UserGuide extends Field
{
    const GUIDE_TEMPLATE = 'system/config/userguide.phtml';

    public $docUrl = EnConstants::EN_URL.'#documentation';
 
    private $dataHelper;
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
        $this->dataHelper      = $dataHelper;
        parent::__construct($context, $data);
    }
    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::GUIDE_TEMPLATE);
        }
        return $this;
    }
  
    /**
     * @param AbstractElement $element
     * @return html
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Show TForce LTL Plan Notice
     * @return string
     */
    public function upsLtlPlanNotice()
    {
        $planRefreshUrl = $this->getPlanRefreshUrl();
        return $this->dataHelper->upsLtlSetPlanNotice($planRefreshUrl);
    }

    /**
     * @return url
     */
    public function getPlanRefreshUrl()
    {
        return $this->getbaseUrl().'upsltlfreightquotes/Test/PlanRefresh/';
    }
}
