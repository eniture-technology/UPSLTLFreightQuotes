<?php
namespace Eniture\UPSLTLFreightQuotes\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TestConnection extends Field
{
    const BUTTON_TEMPLATE = 'system/config/testconnection.phtml';

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
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }
    /**
     * @param AbstractElement $element
     * @return type
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
    public function getAjaxCheckUrl()
    {
        return $this->getbaseUrl().'upsltlfreightquotes/Test/TestConnection/';
    }
    /**
     * @param AbstractElement $element
     * @return html
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->addData(
            [
                'id'            => 'upsLtlTestConnBtn',
                'button_label'  => 'Test Connection',
            ]
        );
        return $this->_toHtml();
    }
    /**
     * Show UPS LTL Plan Notice
     * @return string
     */
    public function upsLtlPlanNotice()
    {
        return $this->dataHelper->upsLtlSetPlanNotice();
    }

    public function upsLtlConnMsg()
    {
        return '<div class="message message-notice notice upsltl-conn-setting-note"><div data-ui-id="messages-message-notice">Note! You must have a UPS account to use this application. If you do not have one, contact UPS at 800-463-3339 or <a target="_blank" href="https://www.ups.com/doapp/signup">register online</a>.</div></div>';
    }
}
