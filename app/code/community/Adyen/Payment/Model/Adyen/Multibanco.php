<?php

class Adyen_Payment_Model_Adyen_Multibanco extends Adyen_Payment_Model_Adyen_Abstract
    implements Mage_Payment_Model_Billing_Agreement_MethodInterface
{
    protected $_code = 'adyen_multibanco';
    protected $_formBlockType = 'adyen/form_multibanco';
    protected $_infoBlockType = 'adyen/info_multibanco';
    protected $_paymentMethod = 'multibanco';
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $info->setAdditionalInformation('delivery_date', date('Y-m-d\TH:i:s.000\Z'));

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);

        if (!is_null($quote) && $quote->getGrandTotal() == 0) {
            $isAvailable = false;
        }

        return $isAvailable;
    }

    /**
     * @return bool
     */
    public function canCreateAdyenSubscription()
    {
        // validate if recurringType is correctly configured
        $recurringType = $this->_getConfigData('recurringtypes', 'adyen_abstract');

        if ($recurringType == 'RECURRING' || $recurringType == 'ONECLICK,RECURRING') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isBillingAgreement()
    {
        return true;
    }
}
