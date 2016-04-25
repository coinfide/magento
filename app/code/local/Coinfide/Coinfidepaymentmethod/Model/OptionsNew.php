<?php
class Coinfide_Coinfidepaymentmethod_Model_OptionsNew {
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray() {
		$orderStatusCollection = Mage::getModel ( 'sales/order_status' )->getResourceCollection ()->getData ();
		$status = array ();
		
		$status = Mage::getResourceModel('sales/order_status_collection')
		->addStateFilter('new')
		->toOptionHash();
		return $status;
	}
}