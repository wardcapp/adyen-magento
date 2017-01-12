<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Adyen_Data_ModificationRequest extends Adyen_Payment_Model_Adyen_Data_Abstract {

    public $anyType2anyTypeMap;
    public $authorisationCode;
    public $merchantAccount;
    public $merchantReference;
    public $modificationAmount;
    public $originalReference;
    public $additionalData;

    public function create(Varien_Object $payment, $amount, $merchantAccount, $pspReference = null)
    {
        $order = $payment->getOrder();
        $currency = $order->getOrderCurrencyCode();
        $incrementId = $order->getIncrementId();

        $this->anyType2anyTypeMap = null;
        $this->authorisationCode = null;
        $this->merchantAccount = $merchantAccount;
        $this->reference = $incrementId;
        if($amount) {
            $this->modificationAmount = new Adyen_Payment_Model_Adyen_Data_Amount();
            $this->modificationAmount->value = Mage::helper('adyen')->formatAmount($amount, $currency);
            $this->modificationAmount->currency = $currency;
        }
        $this->originalReference = $pspReference;




        // add aditionalData
        $count = 0;
        $currency = $order->getOrderCurrencyCode();
        $additional_data_sign = array();
        $helper = Mage::helper('adyen');
        $openinvoiceType = $this->_getConfigData('openinvoicetypes', 'adyen_openinvoice');

        foreach ($order->getItemsCollection() as $item) {
            //skip dummies
            if ($item->isDummy()) continue;

            ++$count;
            $linename = "line".$count;
            $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $item->getName();
            $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $helper->formatAmount($item->getPrice(), $currency);
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = ($item->getTaxAmount() > 0 && $item->getPriceInclTax() > 0) ? $helper->formatAmount($item->getPriceInclTax(), $currency) - $helper->formatAmount($item->getPrice(), $currency):$helper->formatAmount($item->getTaxAmount(), $currency);

            // Calculate vat percentage
            $id = $item->getProductId();
            $product = $this->_loadProductById($id);
            $taxRate = $helper->getTaxRate($order, $product->getTaxClassId());
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatPercentage'] = $helper->getMinorUnitTaxPercent($taxRate);


            // test for refunding only the second item
//            if($count == 1) {
//                $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = (int) "0";
//            } else {
//                $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = (int) $item->getQtyOrdered();
//            }

            $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = (int) $item->getQtyOrdered();

            // afterpay_default_nl ?
            if(($order->getPayment()->getMethod() == "adyen_openinvoice" && $openinvoiceType == "afterpay_default") || ($order->getPayment()->getMethodInstance()->getInfoInstance()->getCcType() == "afterpay_default")) {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }


        }

        //discount cost
        if($order->getDiscountAmount() > 0 || $order->getDiscountAmount() < 0)
        {
            $linename = "line".++$count;
            $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $helper->__('Total Discount');
            $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $helper->formatAmount($order->getDiscountAmount(), $currency);
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = "0";
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatPercentage'] = "0";
            $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
            if(($order->getPayment()->getMethod() == "adyen_openinvoice" && $openinvoiceType == "afterpay_default") || ($order->getPayment()->getMethodInstance()->getInfoInstance()->getCcType() == "afterpay_default")) {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }

        }

        //shipping cost
        if($order->getShippingAmount() > 0 || $order->getShippingTaxAmount() > 0)
        {
            $linename = "line".++$count;
            $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $order->getShippingDescription();
            $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $helper->formatAmount($order->getShippingAmount(), $currency);
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = $helper->formatAmount($order->getShippingTaxAmount(), $currency);

            // Calculate vat percentage
            $taxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class', $order->getStoreId());
            $taxRate = $helper->getTaxRate($order, $taxClass);
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatPercentage'] = $helper->getMinorUnitTaxPercent($taxRate);

            $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;

            if(($order->getPayment()->getMethod() == "adyen_openinvoice" && $openinvoiceType == "afterpay_default") || ($order->getPayment()->getMethodInstance()->getInfoInstance()->getCcType() == "afterpay_default")) {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }
        }

        if($order->getPaymentFeeAmount() > 0) {
            $linename = "line".++$count;
            $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $helper->__('Payment Fee');
            $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $helper->formatAmount($order->getPaymentFeeAmount(), $currency);
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = "0";
            $additional_data_sign['openinvoicedata.' . $linename . '.itemVatPercentage'] = "0";
            $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;

            if(($order->getPayment()->getMethod() == "adyen_openinvoice" && $openinvoiceType == "afterpay_default") || ($order->getPayment()->getMethodInstance()->getInfoInstance()->getCcType() == "afterpay_default")) {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }
        }

        // Klarna wants tax cost provided in the lines of the products so overal tax cost is not needed anymore
//        $linename = "line".++$count;
//        $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
//        $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $helper->__('Tax');
//        $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $helper->formatAmount($order->getTaxAmount(), $currency);
//        $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = "0";
//        $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
//        $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";

        // general for invoicelines
        $additional_data_sign['openinvoicedata.refundDescription'] = "Refund / Correction for ".$incrementId;
        $additional_data_sign['openinvoicedata.numberOfLines'] = $count;


        // signature is first alphabatical keys seperate by : and then | and then the values seperate by :
        foreach($additional_data_sign as $key => $value) {
            // add to fields
            $adyFields[$key] = $value;

            $kv = new Adyen_Payment_Model_Adyen_Data_AdditionalDataKVPair();
            $kv->key = new SoapVar($key, XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
            $kv->value = new SoapVar($value, XSD_STRING, "string", "http://www.w3.org/2001/XMLSchema");
            $this->additionalData[] = $kv;
        }


//        print_r($this->additionalData);die();

        return $this;
    }

    /**
     * @desc Give Default settings
     * @example $this->_getConfigData('demoMode','adyen_abstract')
     * @since 0.0.2
     * @param string $code
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null) {
        return Mage::helper('adyen')->_getConfigData($code, $paymentMethodCode, $storeId);
    }

    protected function _loadProductById($id)
    {
        return Mage::getModel('catalog/product')->load($id);
    }


}