<?php
namespace Eniture\UPSLTLFreightQuotes\Controller\Test;

use Eniture\UPSLTLFreightQuotes\Helper\Data;
use Eniture\UPSLTLFreightQuotes\Helper\EnConstants;
use \Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class TestConnection extends Action
{
    /**
     * @var Helper Object
     */
    private $dataHelper;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;
    /**
     * @param Context $context
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        Data $dataHelper
    ) {
        $this->request    = $context->getRequest();
        $this->dataHelper = $dataHelper;
        parent::__construct($context);
    }
    /**
     * Test Connection Credentials
     */
    public function execute()
    {
        foreach ($this->getRequest()->getPostValue() as $key => $data) {
            $credentials[$key] = filter_var($data, FILTER_SANITIZE_STRING);
        }

        $post = [
            'carrierName'    => 'ups',
            'carrier_mode'   => 'test',
            'platform'       => 'magento2',
            'AccountNumber'  => $credentials['accountNumber'],
            'UserName'       => $credentials['username'],
            'Password'       => $credentials['password'],
            'APIKey'         => $credentials['authenticationKey'],
            'licence_key'    => $credentials['pluginLicenseKey'],
            'accessLevel'    => $credentials['accessLevel'],
            'server_name'    => $this->request->getServer('SERVER_NAME'),
        ];
        $url = EnConstants::TEST_CONN_URL;
        $response = $this->dataHelper->upsLTLSendCurlRequest($url, $post);
        $result   = $this->upsLTLLtlTestConnResponse($response);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($result);
    }

    /**
     * @param type $data
     * @return array
     */
    function upsLTLLtlTestConnResponse($data)
    {
        $response = [];
        $successMsg = 'The test resulted in a successful connection.';
        $errorMsg = 'The credentials entered did not result in a successful test. Confirm your credentials and try again.';

        if (isset($data->error)) {
            $response['Error'] =  $data->error;
            if (isset($data->error->Code)) {
                $response['Error'] =  $data->error->Description;
            }
        } elseif (isset($data->success)) {
            $response['Success'] =  $successMsg;
        }

        return json_encode($response);
    }
}
