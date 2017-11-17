<?php

class Adyen_Payment_Block_Info_Multibanco extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('adyen/info/multibanco.phtml');
    }

    /**
     * @return array|null
     */
    public function getMultibanco()
    {
        $additionalInformation = $this->getInfo()->getAdditionalInformation();

        if (isset($additionalInformation['comprafacil.entity'])) {
            /** @var Mage_Sales_Model_Order $salesOrder */
            $salesOrder = Mage::getModel('sales/order')->load($this->getInfo()->getParentId());

            $additionalInformation['comprafacil.deadline_date'] = $this->helper('core')->formatDate($salesOrder->getCreatedAtStoreDate());

            if ($additionalInformation['comprafacil.deadline'] > 0) {
                $zendDate = new Zend_Date($salesOrder->getCreatedAtStoreDate());

                $zendDate->addDay($additionalInformation['comprafacil.deadline']);

                $additionalInformation['comprafacil.deadline_date'] = $this->helper('core')->formatDate($zendDate);
            }
        } else {
            $additionalInformation = null;
        }

        return $additionalInformation;
    }
}
