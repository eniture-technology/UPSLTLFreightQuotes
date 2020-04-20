<?php

namespace Eniture\UPSLTLFreightQuotes\Model;

use Magento\Framework\ObjectManagerInterface;

class EnituremodulesFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create new country model
     *
     * @param array $arguments
     * @return object
     */
    public function create($arguments = [])
    {
        return $this->objectManager->create('Eniture\UPSLTLFreightQuotes\Model\Enituremodules', $arguments, false);
    }
}
