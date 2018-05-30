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
class Adyen_Payment_Model_Adyen_Data_PaymentRequest3d extends Adyen_Payment_Model_Adyen_Data_Abstract {
    
	public $merchantAccount;
    public $browserInfo;
    public $md;
    public $paResponse;
    public $shopperIP;
    public $additionalData;
    
    public function __construct() {
        $this->browserInfo = new Adyen_Payment_Model_Adyen_Data_BrowserInfo();
        $this->additionalData = new Adyen_Payment_Model_Adyen_Data_AdditionalData();
    }
	
    public function create(Varien_Object $payment, $merchantAccount)
    {
        $this->merchantAccount = $merchantAccount;
        $this->browserInfo->acceptHeader = $_SERVER['HTTP_ACCEPT'];
        $this->browserInfo->userAgent = $_SERVER['HTTP_USER_AGENT'];
        $this->shopperIP = $_SERVER['REMOTE_ADDR'];
		$this->md = $payment->getAdditionalInformation('md');
		$this->paResponse = $payment->getAdditionalInformation('paResponse');

        if(
            is_array($payment->getAdditionalInformation('mpiResponseData'))
            && !empty($payment->getAdditionalInformation('mpiResponseData'))
            && !empty($payment->getAdditionalInformation(Adyen_Payment_Helper_Data::MPI_IMPLEMENTATION_TYPE))
        ) {
            $mpiResponseData = $payment->getAdditionalInformation('mpiResponseData');
            $mpiImplementationType = $payment->getAdditionalInformation(Adyen_Payment_Helper_Data::MPI_IMPLEMENTATION_TYPE);
            $this->additionalData->addEntry(Adyen_Payment_Helper_Data::MPI_IMPLEMENTATION_TYPE, $mpiImplementationType);
            foreach ($mpiResponseData as $key => $value) {
                $this->additionalData->addEntry($key, $value);
            }
        }
        return $this;
    }
}