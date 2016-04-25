<?php
// app/code/local/Coinfide/Coinfidepaymentmethod/Helper/Data.php
class Coinfide_Coinfidepaymentmethod_Helper_Data extends Mage_Core_Helper_Abstract
{
  function getPaymentGatewayUrl() 
  {
    return Mage::getUrl('coinfidepaymentmethod/payment/gateway', array('_secure' => false));
  }
}