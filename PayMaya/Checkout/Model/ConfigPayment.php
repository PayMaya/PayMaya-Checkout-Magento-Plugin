<?php

namespace PayMaya\Checkout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Filesystem\DirectoryList;
use PayMaya\PayMayaSDK;
use PayMaya\API\Webhook;

class ConfigPayment
{
    const SANDBOX_MODE = 'SANDBOX';
    const PRODUCTION_MODE = 'PRODUCTION';

    protected $scopeConfig;
    protected $messageManager;
    protected $directoryList;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        MessageManager $messageManager,
        DirectoryList $directoryList
    ){
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
        $this->directoryList = $directoryList;
    }

    public function getPublicKey(){
        return $this->scopeConfig->getValue('payment/' . Payment::CODE . '/public_key', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getSecretKey(){
        return $this->scopeConfig->getValue('payment/' . Payment::CODE . '/secret_key', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getWebhookSuccess(){
        return $this->scopeConfig->getValue('payment/' . Payment::CODE . '/webhook_success', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getWebhookFailure(){
        return $this->scopeConfig->getValue('payment/' . Payment::CODE . '/webhook_failure', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getWebhookToken(){
        return $this->scopeConfig->getValue('payment/' . Payment::CODE . '/webhook_token', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getEnvironment(){
        $is_sandbox = $this->scopeConfig->getValue('payment/' . Payment::CODE . '/sandbox_mode', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        return $is_sandbox ? self::SANDBOX_MODE : self::PRODUCTION_MODE;
    }

    public function registerWebhook($type = 'success'){
        $public_key = $this->getPublicKey();
        $secret_key = $this->getSecretKey();
        $environment = $this->getEnvironment();
        $url = ($type== "success") ? $this->getWebhookSuccess() : $this->getWebhookFailure();
        $token = $this->getWebhookToken();
        $webhook_name = ($type== "success") ? Webhook::CHECKOUT_SUCCESS : Webhook::CHECKOUT_FAILURE;

        if($public_key && $secret_key){
            PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);
            $webhook = new Webhook();
            $webhook->name = $webhook_name;
            $webhook->callbackUrl = $url . '?wht=' . $token;
            $register = json_decode($webhook->register());
            if(isset($register->error)) {
                $this->messageManager->addError("There was an error saving your webhook. (" . $register->error->code . " : " . $register->error->message .")");
                return false;
            }
            return true;
        }
        return false;
    }

    public function deleteWebhook(){
        $public_key = $this->getPublicKey();
        $secret_key = $this->getSecretKey();
        $environment = $this->getEnvironment();
        if($public_key && $secret_key){
            PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);
            $webhooks = Webhook::retrieve();
            for($i = 0; $i < count($webhooks); $i++) {
                $webhook = new Webhook();
                $webhook->id = $webhooks[$i]->id;
                $webhook->delete();
            }
        }
    }

    public function formatAmount($amount){
        return number_format($amount, 2, ".", "");
    }

    public function createCheckoutToken()
    {
        return uniqid("paymaya-pg-", true);
    }

    public function log($message, $type = "info"){
        $log_file = $this->getLogFile();
        $message = date('Y-m-d H:i:s') . " " . strtoupper($type) . " " . $message . "\r\n";
        @file_put_contents($log_file, $message, FILE_APPEND);
    }

    public function getLog(){
        $log_file = $this->getLogFile();
        if(!file_exists($log_file))
            return "";

        return file_get_contents($log_file);
    }

    public function deleteLog(){
        $log_file = $this->getLogFile();
        @unlink($log_file);
        return true;
    }

    public function getLogFile(){
        $log_path = $this->directoryList->getPath('log');
        $log_file = $log_path . '/paymaya_checkout.log';
        return $log_file;
    }
}
