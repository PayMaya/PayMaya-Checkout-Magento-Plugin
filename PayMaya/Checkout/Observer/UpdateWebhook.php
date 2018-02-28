<?php

namespace PayMaya\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use PayMaya\Checkout\Model\ConfigPayment;

class UpdateWebhook implements ObserverInterface
{

    protected $configPayment;

    public function __construct(
        ConfigPayment $configPayment
    ){
        $this->configPayment = $configPayment;
    }

    public function execute(Observer $observer)
    {
        $this->configPayment->deleteWebhook();
        $this->configPayment->registerWebhook('success');
        $this->configPayment->registerWebhook('failed');
        return $this;
    }
}