<?php
require_once(Mage::getBaseDir()."/vendor/autoload.php");

class Adyen_Payment_Model_Recurringcron {

    
    /**
     * Cronjob for installments payment
     * 
     */
    public function processNextInstallment()
    {
        $todayDate = date('Y-m-d');
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        //Fetching adyen installment orders
        $sql = "select sales_billing_agreement.reference_id, adyen_order_installments.amount, customer_email, remote_ip, order_currency_code,sales_flat_order.customer_id, sales_flat_order.customer_firstname,sales_flat_order.customer_lastname,adyen_order_installments.increment_id,adyen_order_installments.id, adyen_order_installments.attempt, number_installment, sales_flat_order.created_at, adyen_order_installments.payment_date  from adyen_order_installments
        LEFT JOIN sales_flat_order  ON sales_flat_order.entity_id = adyen_order_installments.order_id 
        LEFT JOIN sales_billing_agreement_order ON sales_flat_order.entity_id = sales_billing_agreement_order.order_id
        LEFT JOIN sales_billing_agreement ON sales_billing_agreement_order.agreement_id = sales_billing_agreement.agreement_id
        WHERE (state = 'complete' OR sales_flat_order.status = 'shipped') AND attempt < 3 AND adyen_order_installments.due_date <= '".$todayDate."' AND adyen_order_installments.done = 0 AND sales_flat_order.status != 'adyen_installment_failed' ";
        $res = $connection->fetchAll($sql);

        if (!empty($res)) {
            foreach ($res as $data) {
                $postFields = array(
                        "amount" => array (
                                    'value' => Mage::helper('adyen')->formatAmount($data['amount'], $data['order_currency_code']),
                                    'currency' => $data['order_currency_code']
                                ),
                        "reference" => $data['increment_id'],
                        "merchantAccount" => Mage::getStoreConfig('payment/adyen_abstract/merchantAccount'),
                        "shopperEmail" => $data['customer_email'],
                        "shopperReference"  => $data['customer_id'],
                        "selectedRecurringDetailReference" => $data['reference_id'],
                                "recurring" => array(
                                    "contract" => "RECURRING"
                                ),
                        "shopperInteraction" => "ContAuth"
                    );

                $postJson = json_encode($postFields);

                $webServiceNameTest = Mage::getStoreConfig('payment/adyen_abstract/ws_username_test');  
                $webServicePasswordTest = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/adyen_abstract/ws_password_test'));
                $webServiceNameLive = Mage::getStoreConfig('payment/adyen_abstract/ws_username_live');
                $webServicePasswordLive = Mage::helper('core')->decrypt(Mage::getStoreConfig('payment/adyen_abstract/ws_password_live'));
                $testLiveMode = Mage::getStoreConfig('payment/adyen_abstract/demoMode');

                $client = new \Adyen\Client();
                
                if ($testLiveMode == 'Y') {
                    $client->setUsername("$webServiceNameTest");
                    $client->setPassword("$webServicePasswordTest");
                    $client->setEnvironment(\Adyen\Environment::TEST);
                } else {
                    $client->setUsername("$webServiceNameLive");
                    $client->setPassword("$webServicePasswordLive");
                    $client->setEnvironment(\Adyen\Environment::LIVE);
                }
                

                $service = new \Adyen\Service\Payment($client);
                $params = json_decode($postJson, true);

                $result = $service->authorise($params);
 
                if (isset($result['resultCode'])) {  

                    //Saving api response 
                    $connWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $sqlResponse = " update adyen_order_installments set response= '".json_encode($result)."' where id='".$data['id']."' ";
                    $connWrite->query($sqlResponse);                      
                    
                    //Different responses
                    if ($result['resultCode'] == 'Authorised') {
                        $paymentDate = now();
                        //increment attempt
                        $sql = "update adyen_order_installments set done = 1 , attempt = attempt + 1, payment_date = '".$paymentDate."' where  id='".$data['id']."'";
                        $connWrite->query($sql);

                        if ($data['number_installment'] == 2) {
                            if ($_SERVER['SERVER_NAME'] == 'staging.evematelas.fr') {
                                $this->sendEmail($data, 48);
                            } else {
                                $this->sendEmail($data, 46);
                            }
                        } else if ($data['number_installment'] == 3) {
                            if ($_SERVER['SERVER_NAME'] == 'staging.evematelas.fr') {
                                $this->sendEmail($data, 49);
                            } else {
                                $this->sendEmail($data, 47);
                            }
                        }

                    } else if ($result['resultCode'] == 'Refused') {

                        //increment attempt
                        $sql = "update adyen_order_installments set attempt = attempt + 1 where  id='".$data['id']."'";
                        $res = $connWrite->query($sql);
                        if ($res) {
                            $data['attempt'] = $data['attempt'] + 1;
                        }
                        //Sending emails
                        if ($data['attempt'] == 1) {
                            if ($_SERVER['SERVER_NAME'] == 'staging.evematelas.fr') {
                               //first attempt fails
                                $this->sendEmail($data, 45); 
                            } else {
                                //first attempt fails
                                $this->sendEmail($data, 43); 
                            }                              
                        } else if ($data['attempt'] == 2) {
                            if ($_SERVER['SERVER_NAME'] == 'staging.evematelas.fr') {
                                //if second attempt fails
                                $this->sendEmail($data, 46); 
                            } else {
                                //second attempt fails
                                $this->sendEmail($data, 44); 
                            }
                        } else if ($data['attempt'] == 3) {
                            //setting order status
                            $sql = "update sales_flat_order set status = 'adyen_installment_failed' where increment_id ='".$data['increment_id']."' ";
                            $connWrite->query($sql); 
                            //if third attempt fails
                             if ($_SERVER['SERVER_NAME'] == 'staging.evematelas.fr') {
                                $this->sendEmail($data, 47); 
                            } else {
                                $this->sendEmail($data, 45);
                            }
                        }

                    } else {
                        Mage::log('Payment Failed '. $data['increment_id']);
                        return;
                    }
                }    

                 
            }
        }        
    }//end processNextInstallment


    public function sendEmail($data, $templateId) {
        setlocale(LC_TIME, "fr_FR");
        //send email to admin ,info about failure
        // Get Store ID     
        $store = Mage::app()->getStore()->getId();

        //Getting the Store E-Mail Sender Name.
        $senderName = Mage::getStoreConfig('trans_email/ident_general/name');

        //Getting the Store General E-Mail.
        $senderEmail = Mage::getStoreConfig('trans_email/ident_general/email');

        $customerName = $data['customer_firstname']." ".$data['customer_lastname'];
        $customerEmail = $data['customer_email'];

        $installmentDetails = $this->getInstallmentDetails($data['increment_id'], $data['attempt']);
        $failedInstallmentDetails = $this->getFailedInstallmentDetails($data['increment_id'], $data['number_installment']);
        $secondInstallmentAmount = Mage::getModel('directory/currency')->formatTxt($installmentDetails[1]['amount'], array('display' => Zend_Currency::NO_SYMBOL)); 
        $secondInstallmentDate = strftime("%a %d %b", strtotime($installmentDetails[1]['payment_date']));
        $thirdInstallmentAmount = Mage::getModel('directory/currency')->formatTxt($installmentDetails[2]['amount'], array('display' => Zend_Currency::NO_SYMBOL)); 
        $thirdInstallmentDueDate = strftime("%a %d %b", strtotime($installmentDetails[2]['due_date']));
        $thirdInstallmentDate = strftime("%a %d %b", strtotime($installmentDetails[2]['payment_date']));
        $failedInstallmentAmount = Mage::getModel('directory/currency')->formatTxt($failedInstallmentDetails[0]['amount'], array('display' => Zend_Currency::NO_SYMBOL)); 
        $failedInstallmentDate = strftime("%a %d %b", strtotime($failedInstallmentDetails[0]['due_date']));

        //Variables.
        $emailTemplateVariables = array();
        $emailTemplateVariables['customername'] = $customerName;
        $emailTemplateVariables['customeremail'] = $customerEmail;
        $emailTemplateVariables['orderId'] = $data['increment_id'];
        $emailTemplateVariables['response'] = json_encode($result); 
        $emailTemplateVariables['secondInstallmentAmount'] =  $secondInstallmentAmount.' &euro;';
        $emailTemplateVariables['secondInstallmentDate'] = utf8_encode($secondInstallmentDate);                                           
        $emailTemplateVariables['thirdInstallmentAmount'] =  $thirdInstallmentAmount.' &euro;';
        $emailTemplateVariables['thirdInstallmentDueDate'] = utf8_encode($thirdInstallmentDueDate);
        $emailTemplateVariables['thirdInstallmentDate'] = utf8_encode($thirdInstallmentDate);
        $emailTemplateVariables['failedAmount'] = $failedInstallmentAmount.' &euro;';
        $emailTemplateVariables['failedDate'] = utf8_encode($failedInstallmentDate);
        $emailTemplateVariables['orderDate'] = utf8_encode(strftime("%a %d %b", strtotime($data['created_at'])));
        $recepientEmails = array($customerEmail);
        
        $translate  = Mage::getSingleton('core/translate');
        $sender = array('name' => $senderName,
                    'email' => $senderEmail);
     
        // Send Transactional Email (transactional email template ID from admin)
        Mage::getModel('core/email_template')
            ->addBcc(array('shruti@ranium.in', 'anup@ranium.in', 'serviceclient@evematelas.fr'))
            ->sendTransactional($templateId, $sender, $recepientEmails, '', $emailTemplateVariables, $store);
                
        $translate->setTranslateInline(true);  
    }


    public function getInstallmentDetails($increment_id) {
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "select * from adyen_order_installments where increment_id = '".$increment_id."' ";
        $res = $conn->fetchAll($sql);
        return $res;
    }

    public function getFailedInstallmentDetails($increment_id, $installmentNo) {
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = "select * from adyen_order_installments where increment_id = '".$increment_id."' and number_installment = '".$installmentNo."'";
        $res = $conn->fetchAll($sql);
        return $res;
    }

     
}//end class