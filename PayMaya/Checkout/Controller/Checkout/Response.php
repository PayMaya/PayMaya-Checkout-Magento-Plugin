<?php

namespace PayMaya\Checkout\Controller\Checkout;

class Response extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');
        return $resultRedirect;
    }
}