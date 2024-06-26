<?php

namespace Eniture\UPSLTLFreightQuotes\Controller\Warehouse;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Model\WarehouseFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class SaveWarehouse extends Action
{
    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var WarehouseFactory
     */
    private $warehouseFactory;

    /**
     * @param Context $context
     * @param Data $dataHelper
     * @param WarehouseFactory $warehouseFactory
     */
    public function __construct(
        Context $context,
        Data $dataHelper,
        WarehouseFactory $warehouseFactory
    ) {
        $this->dataHelper = $dataHelper;
        $this->warehouseFactory = $warehouseFactory->create();
        parent::__construct($context);
    }
    /**
     * Save and Update Warehouse Data
     */
    public function execute()
    {
        $insertQry = ['insertId' => 0, 'lastId' => 0];
        $updateQry = 0;
        $msg = 'Warehouse already exists.';
        $updateInspld = 'no';
        $saveWhData = [];
        foreach ($this->getRequest()->getParams() as $key => $post) {
            $saveWhData[$key] = htmlspecialchars($post, ENT_QUOTES);
        }

        $inputDataArr = $this->dataHelper->upsLTLOriginArray($saveWhData);
        $validateData = $this->dataHelper->upsLTLValidatedPostData($inputDataArr);
        $city  = $validateData['city'];
        $state = $validateData['state'];
        $zip   = $validateData['zip'];

        if ($city != 'Error') {
            $warehouseId  = isset($saveWhData['originId']) ? intval($saveWhData['originId']) : "";
            $getWarehouse = $this->checkWarehouseList($city, $state, $zip);

            if (!empty($getWarehouse)) {
                $whId = reset($getWarehouse)['warehouse_id'];
                if ($warehouseId == $whId) {
                    // check any change in InspLd data
                    $updateInspld = $this->dataHelper->checkUpdateInstrorePickupDelivery($getWarehouse, $validateData);
                }
            }

            if ($warehouseId && (empty($getWarehouse) || $updateInspld == 'yes')) {
                $updateQry = $this->dataHelper->updateWarehousData($validateData, "warehouse_id='".$warehouseId."'");
                $msg = 'Warehouse updated successfully.';
            } else {
                if (empty($getWarehouse)) {
                    $insertQry = $this->dataHelper->insertWarehouseData($validateData, $warehouseId);
                    $msg = 'New warehouse added successfully.';
                }
            }
            $lastId = ($updateQry) ? $warehouseId : $insertQry['lastId'];
        } else {
            $lastId = '';
            $msg = 'City name is invalid';
        }
        $canAddWh = $this->dataHelper->whPlanRestriction();
        $warehouseList = $this->warehouseListData($validateData, $insertQry, $updateQry, $lastId, $canAddWh, $msg);

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($warehouseList));
    }

    /**
     * @param $validateData
     * @param $insertQry
     * @param $updateQry
     * @param $lastId
     * @param $canAddWh
     * @param $msg
     * @return array
     */
    public function warehouseListData($validateData, $insertQry, $updateQry, $lastId, $canAddWh, $msg)
    {
        return [
            'id'             => $lastId,
            'origin_city'    => $validateData['city'],
            'origin_state'   => $validateData['state'],
            'origin_zip'     => $validateData['zip'],
            'origin_country' => $validateData['country'],
            'insert_qry'     => $insertQry['insertId'],
            'update_qry'     => $updateQry,
            'canAddWh'       => $canAddWh,
            'msg'            => $msg
        ];
    }

    /**
     * @param string $city
     * @param string $state
     * @param string $zip
     * @return array
     */
    public function checkWarehouseList($city, $state, $zip)
    {
        $whCollection = $this->warehouseFactory->getCollection()
            ->addFilter('location', ['eq' => 'warehouse'])
            ->addFilter('city', ['eq' => $city])
            ->addFilter('state', ['eq' => $state])
            ->addFilter('zip', ['eq' => $zip]);

        return $this->dataHelper->purifyCollectionData($whCollection);
    }
}
