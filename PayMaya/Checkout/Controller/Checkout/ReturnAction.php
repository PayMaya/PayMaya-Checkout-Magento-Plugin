<?php

namespace PayMaya\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use PayMaya\Checkout\Model\ConfigPayment;

class ReturnAction extends \Magento\Framework\App\Action\Action
{
    protected $configPayment;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        ConfigPayment $configPayment,
        JsonFactory $resultJsonFactory
    ){
        parent::__construct($context);
        $this->configPayment = $configPayment;
        $this->resultJsonFactory = $resultJsonFactory;
        
        // Bypass CSRF
        $this->getRequest()->setParam('ajax', true)->setParam('isAjax', true);
    }

    public function execute()
    {
        $raw_checkout_input = file_get_contents("php://input");
        $checkout = json_decode($raw_checkout_input);

        if(!$checkout){
            $this->configPayment->log("-------------------");
            $this->configPayment->log("Response invalid: " . $raw_checkout_input);
            $this->configPayment->log("-------------------");

            $result = $this->resultJsonFactory->create();
            $result->setData(['message' => 'nop']);
            return $result;
        }

        $this->configPayment->log("-------------------");

        $this->configPayment->log("Checkout ID: " . $checkout->id);
        $this->configPayment->log("Checkout RRN: " . $checkout->requestReferenceNumber);
        if(isset($checkout->requestReferenceNumber)) {
            $incrementId = $checkout->requestReferenceNumber;
            try {
                $order = $this->_objectManager->create(Order::class);
                $order->loadByIncrementId($incrementId);
                $this->configPayment->log( "Checkout Order: " . $order->getPaymayaCheckoutId());
                $this->configPayment->log( "Checkout Nonce: " . $order->getPaymayaNonce());

                $this->configPayment->log( "Webhook Token: " . $_GET['wht'] );

                if(strcmp( $_GET['wht'], $this->configPayment->getWebhookToken()) == 0 && isset($checkout->id)) {

                    $this->configPayment->log( "Checkout Status: " . $checkout->status );
                    $this->configPayment->log( "Checkout Payment Status: " . $checkout->paymentStatus );

                    if($checkout->status == "COMPLETED" && $checkout->paymentStatus == "PAYMENT_SUCCESS") {

                        $order->setStatus(Order::STATE_COMPLETE)
                            ->setState(Order::STATE_COMPLETE);
                        $order->save();

                        $this->configPayment->log("Order " . $checkout->requestReferenceNumber . " set to completed and emptied.");
                    }

                    else {
                        $this->configPayment->log( "** Failed to completed order. **" );
                    }

                    $this->configPayment->log( "Webhook execution completed for " . $checkout->id );
                }

            } catch(\Exception $e){
                $this->configPayment->log( "Order Exception: " . $e->getMessage(), "error" );
            }
        }

        $this->configPayment->log("-------------------");

        $result = $this->resultJsonFactory->create();
        $result->setData(['message' => 'nop']);
        return $result;
    }
}
