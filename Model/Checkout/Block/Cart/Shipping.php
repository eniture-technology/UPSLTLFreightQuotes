<?php
/**
 * Eniture
 *
 * @package EnableCity
 * @author Eniture
 * @license https://eniture.com
 */
 
namespace Eniture\UPSLTLFreightQuotes\Model\Checkout\Block\Cart;
 
use Magento\Checkout\Block\Cart\LayoutProcessor;
use Magento\Checkout\Block\Checkout\AttributeMerger;
use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Checkout cart shipping block plugin
 */
class Shipping extends LayoutProcessor
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param AttributeMerger $merger
     * @param CountryCollection $countryCollection
     * @param Collection $regionCollection
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AttributeMerger $merger,
        CountryCollection $countryCollection,
        Collection $regionCollection
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($merger, $countryCollection, $regionCollection);
    }
 
    /**
     * Show City in Shipping Estimation on cart page
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCityActive()
    {
        if ($this->scopeConfig->getValue('carriers/ENUpsLTL/active')) {
            return true;
        }
    }
}
