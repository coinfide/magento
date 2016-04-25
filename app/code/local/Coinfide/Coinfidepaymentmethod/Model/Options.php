<?php
class Coinfide_Coinfidepaymentmethod_Model_Options {
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray() {
		$orderStatusCollection = Mage::getModel ( 'sales/order_status' )->getResourceCollection ()->getData ();
		$status = array ();
		$status = array (
				'-1' => 'Please Select..' 
		);
		
		foreach ( $orderStatusCollection as $orderStatus ) {
			if (strpos($orderStatus ['label'],'Coinfide') !== false) {
				$status [] = array (
					'value' => $orderStatus ['status'],
					'label' => $orderStatus ['label'] 
				);
			}
		}
		return $status;
	}
}