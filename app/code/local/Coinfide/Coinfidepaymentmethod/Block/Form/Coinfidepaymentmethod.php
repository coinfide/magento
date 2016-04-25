<?php
// app/code/local/Coinfide/Coinfidepaymentmethod/Block/Form/Coinfidepaymentmethod.php
class Coinfide_Coinfidepaymentmethod_Block_Form_Coinfidepaymentmethod extends Mage_Payment_Block_Form
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('coinfidepaymentmethod/form/coinfidepaymentmethod.phtml');
	}
}