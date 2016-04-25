<?php
class Coinfide_Coinfidepaymentmethod_Model_Integrations {
	/**
	 * Provide available options as a value/label array
	 *
	 * @return array
	 */
	public function toOptionArray() {
		return array (
				array (
						'value' => '0',
						'label' => 'Form Based' 
				),
				array (
						'value' => '1',
						'label' => 'iFrame Based' 
				)
		);
	}
}