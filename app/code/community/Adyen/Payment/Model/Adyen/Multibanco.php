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

    /**
     * @param Adyen_Payment_Model_Billing_Agreement $billingAgreement
     * @param array $data
     *
     * @return $this
     */
    public function parseRecurringContractData(Adyen_Payment_Model_Billing_Agreement $billingAgreement, array $data)
    {
        $billingAgreement->setMethodCode($this->getCode())
            ->setReferenceId($data['recurringDetailReference'])
            ->setCreatedAt($data['creationDate']);

        $creationDate = str_replace(' ', '-', $data['creationDate']);

        $billingAgreement->setCreatedAt($creationDate);

        $billingAgreement->setAgreementLabel(Mage::helper('adyen')->__('Multibanco, %s', $data['multibanco_name']));

        return $this;
    }

    /**
     * @param Adyen_Payment_Model_Billing_Agreement $billingAgreement
     * @param Mage_Sales_Model_Quote_Payment $paymentInfo
     *
     * @return $this
     */
    public function initBillingAgreementPaymentInfo(Adyen_Payment_Model_Billing_Agreement $billingAgreement, Mage_Sales_Model_Quote_Payment $paymentInfo)
    {
        try {
            $paymentInfo->setMethod('adyen_multibanco')
                ->setAdditionalInformation(array(
                    'recurring_detail_reference' => $billingAgreement->getReferenceId(),
                    'delivery_date' => date('Y-m-d\TH:i:s.000\Z'),
                ));
        } catch (Exception $e) {
            Adyen_Payment_Exception::logException($e);
        }

        return $this;
    }

    /**
     * Ability to set the code, for dynamic payment methods.
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * @desc CustomerInteraction is set by the recurring_payment_type or controlled by Adyen_Subscription module
     * @param $customerInteraction
     */
    public function setCustomerInteraction($customerInteraction)
    {
        $this->_customerInteraction = (bool)$customerInteraction;
    }

    /**
     * @return Adyen_Payment_Model_Billing_Agreement
     */
    public function getBillingAgreement()
    {

        return Mage::getModel('adyen/billing_agreement')->getCollection()
            ->addFieldToFilter('reference_id', $this->getInfoInstance()->getAdditionalInformation('recurring_detail_reference'))
            ->getFirstItem();
    }
}
