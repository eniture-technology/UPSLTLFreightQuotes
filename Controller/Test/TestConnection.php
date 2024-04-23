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
        $credentials = $this->getRequest()->getParams();
        $post = [
            'carrierName'    => 'ups',
            'carrier_mode'   => 'test',
            'platform'       => 'magento2',
            'licence_key'    => $credentials['pluginLicenseKey'],
            'server_name'    => $this->request->getServer('SERVER_NAME'),
        ];

        if($credentials['endPoint'] == 'legacy'){
            $post['AccountNumber'] = $credentials['accountNumber'] ?? '';
            $post['UserName'] = $credentials['username'] ?? '';
            $post['Password'] = $credentials['password'] ?? '';
            $post['APIKey'] = $credentials['authenticationKey'] ?? '';
        }else{
            $post['requestForTForceQuotes'] = '1';
            $post['clientId'] = $credentials['clientId'] ?? '';
            $post['clientSecret'] = $credentials['clientSecret'] ?? '';
            $post['UserName'] = $credentials['username'] ?? '';
            $post['Password'] = $credentials['password'] ?? '';
        }
        
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

        if (isset($data->severity) && $data->severity == 'SUCCESS' || isset($data->q->Rate)) {
            $response['Success'] =  $successMsg;
        }else if (isset($data->severity) && $data->severity == 'ERROR') {
            $message = (isset($data->Message) && !empty($data->Message)) ? $data->Message : $errorMsg;
            $response['Error'] =  $message;
        } elseif (isset($data->error) || isset($data->error->Description)) {
            $message = (isset($data->error->Description) && !empty($data->error->Description)) ? $data->error->Description : $data->error;
            $response['Error'] =  $message;
        } else {
            $response['Error'] =  $errorMsg;
        }

        return json_encode($response);
    }
}
