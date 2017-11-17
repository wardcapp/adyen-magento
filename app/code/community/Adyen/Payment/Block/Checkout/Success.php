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
class Adyen_Payment_Block_Checkout_Success extends Mage_Checkout_Block_Onepage_Success
{
	private $order;
	
	
	/*
	 * check if payment method is boleto
	 */
	public function isBoletoPayment()
	{
		$this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
		
		if ($this->order->getPayment() && $this->order->getPayment()->getMethod() == "adyen_boleto") {
            return true;
        }
		
		return false;
	}
	
	/*
	 * get the boleto pdf url from order
	 */
	public function getUrlBoletoPDF()
	{
		$result = "";
		
		// if isBoletoPayment is not called first load the order
		if($this->order == null) {
            $this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        }
				
		if ($this->order->getPayment()->getMethod() == "adyen_boleto") {
            $result = $this->order->getAdyenBoletoPdf();
        }
				
		return $result;
	}

    /*
     * check if payment method is multibanco
     */
    public function isMultibancoPayment()
    {
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());

        if ($this->order->getPayment() && $this->order->getPayment()->getMethod() == 'adyen_multibanco') {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getMultibanco()
    {
        $this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());

        $additionalInformation = $this->order->getPayment()->getAdditionalInformation();

        $additionalInformation['comprafacil.deadline_date'] = $this->helper('core')->formatDate($this->order->getCreatedAtStoreDate());

        if ($additionalInformation['comprafacil.deadline'] > 0) {
            $zendDate = new Zend_Date($this->order->getCreatedAtStoreDate());

            $zendDate->addDay($additionalInformation['comprafacil.deadline']);

            $additionalInformation['comprafacil.deadline_date'] = $this->helper('core')->formatDate($zendDate);
        }

        return $additionalInformation;
    }
}
