<?php

namespace PayMaya\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Response extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $status = $this->getRequest()->getParam('status');

        $redirectPath = null;
        $errorMessage = null;

        switch ($status) {
            case 'success':
                $redirectPath = 'checkout/onepage/success';
                break;
            case 'failure':
                $redirectPath = 'checkout/cart';
                $errorMessage = 'Payment failed';
                break;
            case 'cancel':
                $redirectPath = 'checkout/cart';
                $errorMessage = 'Payment canceled';
                break;
            default:
                // Catch for invalid URLs
                $redirectPath = 'checkout/cart';
                $errorMessage = 'Payment status is invalid';
        }

        $resultRedirect->setPath($redirectPath);

        if ($errorMessage) {
            $this->messageManager->addWarningMessage($errorMessage);
            $this->checkoutSession->restoreQuote();
        }

        return $resultRedirect;
    }
}
