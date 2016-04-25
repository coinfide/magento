<?php
$installStatus = version_compare(Mage::getVersion(), '1.4.2.0', '>');

if ($installStatus) {
    /* @var $installer Mage_Sales_Model_Entity_Setup */
    $installer = $this;

    $installer->startSetup();
    $statusTable = $installer->getTable('sales/order_status');
    $statusStateTable = $installer->getTable('sales/order_status_state');
    $statusLabelTable = $installer->getTable('sales/order_status_label');

    $paymentStatuses = array();
    $paymentStatuses[] = array(
        	'status' => 'coinfide_waiting_for_payment',
        	'label' => 'Coinfide Waiting for Payment');
    $paymentStatuses[] = array(
        	'status' => 'coinfide_canceled',
        	'label' => 'Coinfide Canceled');
    $paymentStatuses[] = array(
        	'status' => 'coinfide_paid',
        	'label' => 'Coinfide Paid');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_refunded',
    		'label' => 'Coinfide Refunded');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_chargebacked',
    		'label' => 'Coinfide Chargebacked');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_expired',
    		'label' => 'Coinfide Expired');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_error',
    		'label' => 'Coinfide Error');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_partially_refunded',
    		'label' => 'Coinfide Partial Refund');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_partially_chargebacked',
    		'label' => 'Coinfide Partial Chargeback');
    $paymentStatuses[] = array(
    		'status' => 'coinfide_error',
    		'label' => 'Coinfide Error');

    foreach ($paymentStatuses as $paymentStatus) {
        try {
            $installer->getConnection()->insertArray($statusTable, array('status', 'label'), array($paymentStatus));
        } catch (Exception $e) {
            //none
        }
    }

    $paymentStatusToState = array();
    $paymentStatusToState[] = array(
        'status' => 'coinfide_waiting_for_payment',
        'state' => 'new',
        'is_default' => 0
    );

    $paymentStatusToState[] = array(
        'status' => 'coinfide_canceled',
        'state' => 'canceled',
        'is_default' => 0
    );

    $paymentStatusToState[] = array(
        'status' => 'coinfide_paid',
        'state' => 'processing',
        'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_refunded',
    		'state' => 'canceled',
    		'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_chargebacked',
    		'state' => 'canceled',
    		'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_expired',
    		'state' => 'canceled',
    		'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_error',
    		'state' => 'canceled',
    		'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_partially_refunded',
    		'state' => 'processing',
    		'is_default' => 0
    );
    $paymentStatusToState[] = array(
    		'status' => 'coinfide_partially_chargebacked',
    		'state' => 'processing',
    		'is_default' => 0
    );

    foreach ($paymentStatusToState as $statusToState) {
        try {
            $installer->getConnection()->insertArray(
                $statusStateTable,
                array('status', 'state', 'is_default'),
                array($statusToState)
            );
        } catch (Exception $e) {
            //none
        }
    }

    $installer->endSetup();
} else {
    // statuses for old Magento versions are in config.xml
}