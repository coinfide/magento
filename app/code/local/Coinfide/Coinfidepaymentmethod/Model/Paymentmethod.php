<?php
// app/code/local/Coinfide/Coinfidepaymentmethod/Model/Paymentmethod.php
class Coinfide_Coinfidepaymentmethod_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
	protected $_code  = 'coinfidepaymentmethod';
	protected $_formBlockType = 'coinfidepaymentmethod/form_coinfidepaymentmethod';
	protected $_infoBlockType = 'coinfidepaymentmethod/info_coinfidepaymentmethod';

	
	public function initialize($action, $stateObject)
	{
		if(($status = $this->getConfigData('order_status'))) {
			$stateObject->setStatus($status);
			$state = $this->_getAssignedState($status);
			$stateObject->setState($state);
			$stateObject->setIsNotified(true);
		}
		return $this;
	}
	
	public function assignData($data)
	{
		$info = $this->getInfoInstance();
		 
		if ($data->getCustomFieldOne())
		{
			$info->setCustomFieldOne($data->getCustomFieldOne());
		}
		 
		if ($data->getCustomFieldTwo())
		{
			$info->setCustomFieldTwo($data->getCustomFieldTwo());
		}

		return $this;
	}

	public function validate()
	{
		parent::validate();
		$info = $this->getInfoInstance();
		 
		/*if (!$info->getCustomFieldOne())
		{
			$errorCode = 'invalid_data';
			$errorMsg = $this->_getHelper()->__("CustomFieldOne is a required field.\n");
		}
		 
		if (!$info->getCustomFieldTwo())
		{
			$errorCode = 'invalid_data';
			$errorMsg .= $this->_getHelper()->__('CustomFieldTwo is a required field.');
		}

		if ($errorMsg)
		{
			Mage::throwException($errorMsg);
		}
*/
		return $this;
	}

	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('coinfidepaymentmethod/payment/redirect', array('_secure' => false));
	}
	
	/**
	 * Payment method description for checkout
	 * 
	 * @return string
	 */
	public function getDescription()
	{
		return (string)Mage::getStoreConfig('payment/coinfidepaymentmethod/description');
	}
}