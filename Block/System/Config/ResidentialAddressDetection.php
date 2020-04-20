<?php
namespace Eniture\UPSLTLFreightQuotes\Block\System\Config;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ResidentialAddressDetection
 *
 * @package Eniture\UPSLTLFreightQuotes\Block\System\Config
 */
class ResidentialAddressDetection extends Field
{
    /**
     * RAD_TEMPLATE
     */
    const RAD_TEMPLATE = 'system/config/resaddressdetection.phtml';

    /**
     * @var Manager
     */
    public $moduleManager;
    /**
     * @var string
     */
    public $enable = 'no';
    /**
     * @var ObjectManagerInterface
     */
    public $objectManager;
    /**
     * @var
     */
    public $licenseKey;
    /**
     * @var
     */
    public $radUseSuspended;
    /**
     * @var
     */
    public $trialMsg;
    /**
     * @var
     */
    public $resAddDetectData;

    /**
     * @var Data
     */
    public $dataHelper;

    /**
     * @var
     */
    private $scopeConfig;

    /**
     * @var string
     */
    public $addressType;

    /**
     * ResidentialAddressDetection constructor.
     *
     * @param Context $context
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Data $dataHelper,
        $data = []
    ) {
        $this->objectManager   = $objectManager;
        $this->moduleManager   = $moduleManager;
        $this->dataHelper      = $dataHelper;
        $this->scopeConfig     = $context->getScopeConfig();
        $this->checkRADModule();
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::RAD_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param AbstractElement $element
     * @return mixed
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }


    public function getHtml()
    {
        return $this->_toHtml();
    }

    /**
     *
     */
    public function checkRADModule()
    {
        if ($this->moduleManager->isEnabled('Eniture_ResidentialAddressDetection')) {
            $this->enable           = 'yes';
            $this->licenseKey       = $this->scopeConfig->getValue("UpsLtlConnSettings/first/upsltlLicnsKey", ScopeInterface::SCOPE_STORE);
            $dataHelper             = $this->objectManager->get("Eniture\ResidentialAddressDetection\Helper\Data");
            $this->resAddDetectData = $dataHelper->resAddDetectDataHandling($this->licenseKey);
            $this->radUseSuspended  = $dataHelper->radUseSuspended();
            $this->addressType      = $dataHelper->getAddressType();
            if ($dataHelper->checkModuleTrial()) {
                $this->trialMsg = 'The LTL Freight Quotes module must have active paid license to continue to use this feature.';
            }
        }
    }

    /**
     * @return string
     */
    public function suspendRADUrl()
    {
        return $this->getbaseUrl().'ResidentialAddressDetection/RAD/SuspendedRAD/';
    }

    /**
     * @return string
     */
    public function autoRenewRADPlanUrl()
    {
        return $this->getbaseUrl().'ResidentialAddressDetection/RAD/AutoRenewPlan/';
    }

    /**
     * @return string
     */
    public function addressTypeUrl()
    {
        return $this->getbaseUrl().'ResidentialAddressDetection/RAD/DefaultAddressType/';
    }
    /**
     * Show UPS LTL Plan Notice
     * @return string
     */
    public function upsLtlPlanNotice()
    {
        return $this->dataHelper->upsLtlSetPlanNotice();
    }
}
