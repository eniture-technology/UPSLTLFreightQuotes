<?php

namespace Eniture\UPSLTLFreightQuotes\Block\System\Config;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

class PalletPackagingTab extends Field
{
    /**
     * Pallet Packging Templete
     */
    const PALLET_PACKAGING_TAB_TEMPLATE = 'system/config/palletpackagingtab.phtml';

    /**
     * @var Manager
     */
    private $moduleManager;
    /**
     * @var string
     */
    public $enable = 'no';
    /**
     * @var
     */
    public $palletData;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var
     */
    public $licenseKey;
    /**
     * @var Context
     */
    public $context;
    /**
     * @var
     */
    public $ltlTrialMsg;
    /**
     * @var
     */
    public $palletUseSuspended;
    /**
     * @var
     */
    public $getPalletPackaging;

    /**
     * @param Context $context
     * @param Manager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Manager $moduleManager,
        ObjectManagerInterface $objectManager,
        Data $dataHelper,
        $data = []
    ) {
        $this->moduleManager    = $moduleManager;
        $this->objectManager    = $objectManager;
        $this->context          = $context;
        $this->dataHelper       = $dataHelper;
        $this->checkBinPackagingModule();
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::PALLET_PACKAGING_TAB_TEMPLATE);
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
     * This function returns the HTML, used in the Block
     * @return html
     */

    public function getHtml()
    {
        return $this->_toHtml();
    }

    /**
     * checkBinPackagingModule
     */
    public function checkBinPackagingModule()
    {
        if ($this->moduleManager->isEnabled('Eniture_PalletPackaging')) {
            $scopeConfig            = $this->context->getScopeConfig();
            $configPath             = "UpsLtlConnSettings/first/upsltlLicnsKey";
            $this->licenseKey       = $scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
            $this->enable           = 'yes';
            $dataHelper             = $this->objectManager->get("Eniture\PalletPackaging\Helper\Data");
            $this->palletData       = $dataHelper->palletPackagingDataHandling($this->licenseKey);
            $this->ltlTrialMsg    = $dataHelper->checkLTLModuleTrial();
            $this->palletUseSuspended  = $dataHelper->palletUseSuspended();
            $this->getPalletPackaging      = $dataHelper->getPalletPackaging();
        }
    }

    /**
     * @return string
     */
    public function savePalletUrl()
    {
        return $this->getbaseUrl().'PalletPackaging/Pallet/SavePallet/';
    }

    /**
     * @return string
     */
    public function deletePalletUrl()
    {
        return $this->getbaseUrl().'PalletPackaging/Pallet/DeletePallet/';
    }

    /**
     * @return string
     */
    public function editPalletUrl()
    {
        return $this->getbaseUrl().'PalletPackaging/Pallet/EditPallet/';
    }

    /**
     * @return string
     */
    public function palletAvailableUrl()
    {
        return $this->getbaseUrl().'PalletPackaging/Pallet/PalletAvailability/';
    }

    /**
     * @return string
     */
    public function suspendPalletUrl()
    {
        return $this->getbaseUrl().'PalletPackaging/Pallet/SuspendedPalletPackaging/';
    }

    /**
     * @return string
     */
    public function autoRenewPalletPlanUrl()
    {
        return $this->getbaseUrl().'/PalletPackaging/Pallet/AutoRenewPlan/';
    }
}
