<?php

namespace PayMaya\Checkout\Controller\Adminhtml\Checkout;

use \Magento\Backend\App\Action\Context;
use PayMaya\Checkout\Model\ConfigPayment;

class Delete extends \Magento\Backend\App\Action
{

    protected $configPayment;

    public function __construct(
        Context $context,
        ConfigPayment $configPayment
    ) {
        parent::__construct($context);
        $this->configPayment = $configPayment;
    }

    public function execute()
    {
        $this->configPayment->deleteLog();
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('paymaya/checkout/index');
        return $resultRedirect;
    }
}

