<?php

namespace PayMaya\Checkout\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'paymaya_checkout';

    protected $_code = self::CODE;

    protected $_supportedCurrencyCodes = array('PHP');

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if(!$this->getConfigData('public_key')
            || !$this->getConfigData('secret_key')
        ){
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}