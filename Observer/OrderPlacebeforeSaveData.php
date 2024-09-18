<?php

namespace Eniture\UPSLTLFreightQuotes\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class OrderPlacebeforeSaveData
 *
 * @package Eniture\UPSLTLFreightQuotes\Observer
 */
class OrderPlacebeforeSaveData implements ObserverInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $coreSession;

    /**
     * OrderPlacebeforeSaveData constructor.
     *
     * @param SessionManagerInterface $coreSession
     */
    public function __construct(
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $isMulti = '0';
            $multiShip = false;
            $order = $observer->getEvent()->getOrder();
            $quote = $order->getQuote();

            if (isset($quote)) {
                $isMulti = $quote->getIsMultiShipping();
            }
            $method = $order->getShippingMethod();
            if (strpos($method, 'ENUpsLTL') !== false) {
                $semiOrderDetailData = $this->coreSession->getSemiOrderDetailSession();
                $orderDetailData = $this->coreSession->getUpsLTLOrderDetailSession();
                if ($orderDetailData != null && $semiOrderDetailData == null) {
                    if (count($orderDetailData['shipmentData']) > 1) {
                        $multiShip = true;
                    }
                    $orderDetailData = $this->getData($order, $method, $orderDetailData, $multiShip);
                } elseif ($semiOrderDetailData) {
                    $orderDetailData = $semiOrderDetailData;
                    $this->coreSession->unsSemiOrderDetailSession();
                }
                $order->setData('order_detail_data', json_encode($orderDetailData));
                $order->save();
                if (!$isMulti) {
                    $this->coreSession->unsUpsLTLOrderDetailSession();
                }
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
    }

    private function getData($order, $method, $orderDetailData, $multiShip)
    {
        $lg = $resi = false;
        $shippingMethod = empty($method) ? [] : explode('_', $method);
        /*These Lines are added for compatibility only*/
        $lgArray = ['always' => 1, 'asOption' => '', 'residentialLiftgate' => ''];
        $orderDetailData['residentialDelivery'] = 0;
        /*These Lines are added for compatibility only*/

        $arr = empty($method) ? [] : explode('xx', $method);
        if (in_array('LG', $arr)) {
            $orderDetailData['liftGateDelivery'] = $lgArray;
            $lg = true;
        }
        if (in_array('R', $arr)) {
            $orderDetailData['residentialDelivery'] = 1;
            $resi = true;
        }
        foreach ($orderDetailData['shipmentData'] as $key => $value) {
            if ($multiShip) {
                if ($lg) {
                    $orderDetailData['shipmentData'][$key]['quotes'] = $value['quotes']['liftgate'];
                } else {
                    $orderDetailData['shipmentData'][$key]['quotes'] = $value['quotes']['simple'];
                }
            } else {
                $orderDetailData['shipmentData'][$key]['quotes'] = [

                    'code' => $shippingMethod[1],
                    'title' => str_replace("TForce LTL Freight Quotes - ", "", $order->getShippingDescription()),
                    'rate' => number_format((float)$order->getShippingAmount(), 2, '.', '')
                ];
            }
        }
        return $orderDetailData;
    }
}
