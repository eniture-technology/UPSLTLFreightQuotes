<?php
namespace Eniture\UPSLTLFreightQuotes\Controller\Dropship;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use \Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class DeleteDropship extends Action
{
    /**
     * @var Data Object
     */
    private $dataHelper;
    /**
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }
    /**
     * Delete Drop Ship from Database
     */
    public function execute()
    {
        $msg = '';
        $deleteDsData = $this->getRequest()->getParams();
        $deleteID = $deleteDsData['delete_id'];

        if ($deleteDsData['action'] == 'delete_dropship') {
            $qry    = $this->dataHelper->deleteWarehouseSecData("warehouse_id='".$deleteID."'");
            $msg = 'Drop ship deleted successfully.';
        }

        $response = ['deleteID' => $deleteID, 'qryResp' => $qry, 'msg'=> $msg];

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }
}
