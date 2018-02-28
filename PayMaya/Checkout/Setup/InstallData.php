<?php

namespace PayMaya\Checkout\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;

class InstallData implements InstallDataInterface
{
    protected $salesSetupFactory;
    protected $urlBuilder;
    protected $resourceConfig;
    protected $configPayment;
    protected $scopeConfig;

    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        UrlInterface $urlBuilder,
        ConfigInterface $resourceConfig,
        ScopeConfigInterface $scopeConfig
    ){
        $this->salesSetupFactory = $salesSetupFactory;
        $this->urlBuilder = $urlBuilder;
        $this->resourceConfig = $resourceConfig;
        $this->scopeConfig = $scopeConfig;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
        $salesSetup->addAttribute(Order::ENTITY, 'paymaya_checkout_id', ['type' => 'varchar', 'visible' => false, 'required' => false]);
        $salesSetup->addAttribute(Order::ENTITY, 'paymaya_checkout_url', ['type' => 'varchar', 'visible' => false, 'required' => false]);
        $salesSetup->addAttribute(Order::ENTITY, 'paymaya_nonce', ['type' => 'varchar', 'visible' => false, 'required' => false]);


        $base_url = $this->scopeConfig->getValue('web/unsecure/base_url');
        $return_url = rtrim($base_url, '/') . '/paymaya/checkout/return';
        $token = uniqid("pgwh-", true) . uniqid() . uniqid();

        $this->resourceConfig->saveConfig('payment/paymaya_checkout/webhook_success', $return_url, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
        $this->resourceConfig->saveConfig('payment/paymaya_checkout/webhook_failure', $return_url, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
        $this->resourceConfig->saveConfig('payment/paymaya_checkout/webhook_token', $token, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, Store::DEFAULT_STORE_ID);
    }

}