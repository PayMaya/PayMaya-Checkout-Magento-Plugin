<?php

namespace PayMaya\Checkout\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use PayMaya\Checkout\Model\ConfigPayment;
use Magento\Backend\Model\UrlInterface;

class Index extends \Magento\Backend\Block\Template
{

    protected $configPayment;
    protected $urlBuilder;

    public function __construct(
        Context $context,
        ConfigPayment $configPayment,
        UrlInterface $urlBuilder,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->configPayment = $configPayment;
        $this->urlBuilder = $urlBuilder;
        $this->_template = 'index.phtml';
    }

    protected function _toHtml(){
        $this->addData([
            'payment_log' => $this->configPayment->getLog(),
            'delete_url' => $this->urlBuilder->getUrl('paymaya/checkout/delete'),
        ]);
        return parent::_toHtml();
    }
}
