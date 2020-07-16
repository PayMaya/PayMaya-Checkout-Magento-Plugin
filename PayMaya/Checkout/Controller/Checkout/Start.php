<?php

namespace PayMaya\Checkout\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\UrlInterface as UrlBuilder;
use Magento\Framework\App\Response\Http;
use PayMaya\Checkout\Model\ConfigPayment;
use PayMaya\PayMayaSDK;
use PayMaya\API\Checkout as PayMayaCheckout;
use PayMaya\Model\Checkout\Buyer as PayMayaBuyer;
use PayMaya\Model\Checkout\Contact as PayMayaContact;
use PayMaya\Model\Checkout\Address as PayMayAddress;
use PayMaya\Model\Checkout\ItemAmount as PayMayaItemAmount;
use PayMaya\Model\Checkout\ItemAmountDetails as PayMayaItemAmountDetails;
use PayMaya\Model\Checkout\Item as PayMayaItem;

class Start extends \Magento\Framework\App\Action\Action
{
    protected $configPayment;
    protected $checkoutSession;
    protected $urlBuilder;
    protected $response;

    public function __construct(
        Context $context,
        ConfigPayment $configPayment,
        CheckoutSession $checkoutSession,
        UrlBuilder $urlBuilder,
        Http $response
    ){
        parent::__construct($context);
        $this->configPayment = $configPayment;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->response = $response;
    }

    public function execute()
    {
        $orderSession = $this->checkoutSession->getLastRealOrder();
        $incrementId = $orderSession->getIncrementId();

        $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId($incrementId);
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $orderItems = $order->getAllItems();
        $order_currency = $order->getOrderCurrencyCode();

        $public_key = $this->configPayment->getPublicKey();
        $secret_key = $this->configPayment->getSecretKey();
        $environment = $this->configPayment->getEnvironment();

        PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);

        $checkout = new PayMayaCheckout();

        $buyer = new PayMayaBuyer();
        $buyer->firstName = $order->getCustomerFirstname();
        $buyer->lastName = $order->getCustomerLastname();

        $contact = new PayMayaContact();
        $contact->phone = $billingAddress->getTelephone();
        $contact->email = $order->getCustomerEmail();
        $buyer->contact = $contact;

        $addressBilling = new PayMayAddress();
        $streets = $billingAddress->getStreet();
        $addressBilling->line1 = isset($streets[0]) ? $streets[0] : '';
        $addressBilling->line2 = isset($streets[1]) ? $streets[1] : '';
        $addressBilling->city = $billingAddress->getCity();
        $addressBilling->state = $billingAddress->getRegionCode();
        $addressBilling->zipCode = $billingAddress->getPostcode();
        $addressBilling->countryCode = $billingAddress->getCountryId();

        $addressShipping = new PayMayAddress();
        $streets = $shippingAddress->getStreet();
        $addressShipping->line1 = isset($streets[0]) ? $streets[0] : '';
        $addressShipping->line2 = isset($streets[1]) ? $streets[1] : '';
        $addressShipping->city = $shippingAddress->getCity();
        $addressShipping->state = $shippingAddress->getRegionCode();
        $addressShipping->zipCode = $shippingAddress->getPostcode();
        $addressShipping->countryCode = $shippingAddress->getCountryId();

        $buyer->billingAddress = $addressBilling;
        $buyer->shippingAddress = $addressShipping;

        $checkout->buyer = $buyer;

        $checkout->items = [];

        foreach($orderItems as $orderItem){
            $orderProduct = $orderItem->getProduct();

            $itemProduct = new PayMayaItemAmount();
            $itemProduct->currency = $order_currency;
            $itemProduct->value = $this->configPayment->formatAmount($orderProduct->getPrice());
            $itemProduct->details = new PayMayaItemAmountDetails();

            $lineItem = new PayMayaItemAmount();
            $lineItem->currency = $order_currency;
            $lineItem->value = $this->configPayment->formatAmount($orderItem->getPrice());
            $lineItem->details = new PayMayaItemAmountDetails();

            $item = new PayMayaItem();
            $item->name = $orderProduct->getName();
            $item->code = $orderProduct->getSku();
            $item->description = "";
            $item->quantity = $orderItem->getQtyOrdered();
            $item->totalAmount = $lineItem;
            $item->amount = $itemProduct;

            $checkout->items[] = $item;
        }

        $totalAmount = new PayMayaItemAmount();
        $totalAmount->currency = $order_currency;
        $totalAmount->value = $this->configPayment->formatAmount($order->getGrandTotal());

        $totalAmountDetails = new PayMayaItemAmountDetails();
        $totalAmountDetails->shippingFee = $order->getShippingAmount();
        $totalAmount->details = $totalAmountDetails;

        $checkout_token = $this->configPayment->createCheckoutToken();

        $checkout->totalAmount = $totalAmount;
        $checkout->requestReferenceNumber = $incrementId;
        $checkout->redirectUrl = [
            "success" => $this->urlBuilder->getUrl('paymaya/checkout/response', ['increment_id' => $incrementId, 'status' => 'success']),
            "failure" => $this->urlBuilder->getUrl('paymaya/checkout/response', ['increment_id' => $incrementId, 'status' => 'failure']),
            "cancel"  => $this->urlBuilder->getUrl('paymaya/checkout/response', ['increment_id' => $incrementId, 'status' => 'cancel']),
        ];

        $checkout->execute();

        $order->setPaymayaCheckoutId($checkout->id)
            ->setPaymayaCheckoutUrl($checkout->url)
            ->setPaymayaNonce($checkout_token);
        $order->save();

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($checkout->url);
        return $resultRedirect;
    }
}
