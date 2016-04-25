<?php
// app/code/local/Coinfide/Coinfidepaymentmethod/controllers/PaymentController.php
class Coinfide_Coinfidepaymentmethod_PaymentController extends Mage_Core_Controller_Front_Action {
	public function gatewayAction() {
		if ($this->getRequest ()->get ( "orderId" )) {
			$arr_querystring = array (
					'flag' => 1,
					'orderId' => $this->getRequest ()->get ( "orderId" ) 
			);
			
			Mage_Core_Controller_Varien_Action::_redirect ( 'coinfidepaymentmethod/payment/response', array (
					'_secure' => false,
					'_query' => $arr_querystring 
			) );
		}
	}
	public function redirectAction() {
		// Config data
		$user = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/username' );
		$password = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/password' );
		
		$url_success = 'checkout/onepage/success';
		$url_fail = 'coinfidepaymentmethod/payment/failed';
		
		$postData = array (
				'username' => "{$user}",
				'password' => "{$password}" 
		);
		
		// SET API URL
		$apiUrl = "";
		if (Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/testmode' ) == 1) {
			$apiUrl = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/testurl' );
		} else {
			$apiUrl = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/produrl' );
		}
		
		// Create the context for the request
		$authToken = "";
		$context = stream_context_create ( array (
				'http' => array (
						'method' => 'POST',
						'header' => "Authorization: {$authToken}\r\n" . "Content-Type: application/json\r\n",
						'content' => json_encode ( $postData ) 
				) 
		) );
		
		// Send the request
		$response = file_get_contents ( rtrim ( $apiUrl, "/" ) . '/auth/token', FALSE, $context );
		
		// Check for errors
		if ($response === FALSE) {
			die ( 'Error' );
		}
		
		$responseData = json_decode ( $response, TRUE );
		$error = '';
		
		if (isset ( $responseData ['errorData'] ) && $responseData ['errorData'] ['errorMessage']) {
			$error = $responseData ['errorData'] ['errorMessage'];
		} else {
			$authToken = $responseData ['accessToken'];
		}
		
		// Process order
		$order = new Mage_Sales_Model_Order ();
		
		$orderId = Mage::getSingleton ( 'checkout/session' )->getLastRealOrderId ();
		$order->loadByIncrementId ( $orderId );
		
		$orderItems = $order->getItemsCollection ()->addAttributeToSelect ( '*' )->addAttributeToFilter ( 'product_type', array (
				'eq' => 'simple' 
		) )->load ();
		
		$issueDate = date ( 'Ymd' );
		$dueDate = $issueDate + 3;
		
		// ------------------
		// Merchant information
		$merchantphone = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/merchantphone' );
		$merchantphonecode = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/merchantphonecode' );
		$merchantemail = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/merchantemail' );
		
		$phone_code = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/phonecode' );
		$countryCode = Mage::getStoreConfig ( 'general/country/default' );
		$store_url = Mage::getBaseUrl ();
		$tax_id = Mage::getStoreConfig ( 'general/store_information/merchant_vat_number' );
		// Customer information
		$email = $order->getCustomerEmail ();
		$payment_firstname = $order->getCustomerFirstname ();
		$payment_lastname = $order->getCustomerLastname ();
		$currency_code = Mage::app ()->getLocale ()->currency ( $order->getOrderCurrencyCode () )->getSymbol ();
		// ------------------
		$shipping = $order->getGrandTotal ();
		$ship_title = $order->getShippingDescription ();
		$ship_amount = $order->getShippingAmount ();
		
		$invoiceDays = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/invoicedays' );
		if ($invoiceDays == null) {
			$dueDate = $issueDate + 3;
		} else {
			$dueDate = $issueDate + $invoiceDays;
		}
		$orders = array ();
		foreach ( $orderItems as $sItem ) {
			
			$customerCountry = $sItem->getOrder ()->getBillingAddress ()->getCountry ();
			$customerPhone = $sItem->getOrder ()->getBillingAddress ()->getTelephone ();
			$payment_city = $sItem->getOrder ()->getBillingAddress ()->getCity ();
			$payment_addr_line1 = $sItem->getOrder ()->getBillingAddress ()->getStreet1 ();
			$payment_addr_line2 = $sItem->getOrder ()->getBillingAddress ()->getStreet2 ();
			$payment_state = $sItem->getOrder ()->getBillingAddress ()->getRegion ();
			$payment_post_code = $sItem->getOrder ()->getBillingAddress ()->getPostcode ();
			
			$shipping_city = $sItem->getOrder ()->getShippingAddress ()->getCity ();
			$shipping_addr_line1 = $sItem->getOrder ()->getShippingAddress ()->getStreet1 ();
			$shipping_addr_line2 = $sItem->getOrder ()->getShippingAddress ()->getStreet2 ();
			$shipping_state = $sItem->getOrder ()->getShippingAddress ()->getRegion ();
			$shipping_post_code = $sItem->getOrder ()->getShippingAddress ()->getPostcode ();
			
			$itemPriceUnit = $sItem->getOriginalPrice ();

			$tax_info = $order->getFullTaxInfo ();
			
			$pItemId = $sItem->getParentItemId ();
			// Get Parent Item Information
			$item = Mage::getModel ( 'sales/order_item' )->load ( "$pItemId" ); // use an item_id here
			$qty = intval ( $sItem->getQtyOrdered () );
			
			$tax_rate = round ( $sItem->getData ( 'tax_percent' ), 0 );
			
			$productId = $sItem->getProductId ();
			$obj = Mage::getModel ( 'catalog/product' );
			$_product = $obj->load ( $productId );
			$itemDescription = $_product->getShortDescription ();

			//$itemName = $sItem->getName();
			$itemName = "";
			
			$itemName .= $_product->getAttributeText('manufacturer');
			$itemName .= ' ' . $_product->getName();
			// end custom
			
			if (isset ( $tax_info [0] ['percent'] )) {
				$tax_rate = $tax_info [0] ['percent'];
				$orders [] = array (
						"type" => "I",
						"name" => "{$itemName}",
						"description" => "{$itemDescription}",
						"priceUnit" => round ( $itemPriceUnit, 2 ),
						"quantity" => $qty,
						"tax" => array (
								"rate" => $tax_rate,
								"name" => "VAT " . $tax_rate . "%" 
						) 
				);
			} else {
				$orders [] = array (
						"type" => "I",
						"name" => "{$itemName}",
						"description" => "{$itemDescription}",
						"priceUnit" => round ( $itemPriceUnit, 2 ),
						"quantity" => $qty 
				);
			}
		}
		$orders [] = array (
				"type" => "S",
				"name" => "{$ship_title}",
				"priceUnit" => round ( $ship_amount, 2 ),
				"quantity" => 1 
		);
		
		$timeNow = date ( "His" );
		
		$currency_code = "EUR";
		
		$orderId = Mage::getSingleton ( 'checkout/session' )->getLastRealOrderId ();
		
		// 0 - excluding tax, 1 - including tax
		$taxInclusive = false;
		$taxBeforeDiscount = true;
		
		if (Mage::getStoreConfig ( 'tax/calculation/price_includes_tax' ) == 0) {
			$taxInclusive = "false";
		} else {
			$taxInclusive = "true";
		}
		if (Mage::getStoreConfig ( 'tax/calculation/apply_after_discount' ) == 0) {
			$taxBeforeDiscount = "true";
		} else {
			$taxBeforeDiscount = "false";
		}
		
		if ($order->getBaseDiscountAmount () > 0) {
			$discount = $order->getBaseDiscountAmount ();
		} else {
			$discount = 0;
		}
		
		$fullLocale = Mage::getStoreConfig ( 'general/locale/code' );
		$locale = substr ( $fullLocale, 0, strpos ( $fullLocale, '_' ) );
		
		$body = array (
				"order" => array (
						"seller" => array (
								"email" => "{$merchantemail}",
								"website" => "{$store_url}",
								"taxpayerIdentificationNumber" => "{$tax_id}",
								"phone" => array (
										"countryCode" => "{$merchantphonecode}",
										"number" => "{$merchantphone}" 
								) 
						),
						"buyer" => array (
								"email" => "{$email}",
								"name" => "{$payment_firstname}",
								"surname" => "{$payment_lastname}",
								"language" => "{$locale}",
								"address" => array (
										"countryCode" => "{$customerCountry}",
										"city" => "{$payment_city}",
										"firstAddressLine" => "{$payment_addr_line1}",
										"secondAddressLine" => "{$payment_addr_line2}",
										"state" => "{$payment_state}",
										"postalCode" => "{$payment_post_code}" 
								) 
						),
						"currencyCode" => "{$currency_code}",
						"amountTotal" => round ( $shipping, 2 ),
						"issueDate" => "{$issueDate}{$timeNow}",
						"dueDate" => "{$dueDate}{$timeNow}",
						"externalOrderId" => "{$orderId}",
						"discountAmount" => $discount * - 1,
						"acceptPaymentsIfOrderExpired" => false,
						"taxBeforeDiscount" => $taxBeforeDiscount,
						"taxInclusive" => $taxInclusive,
						"successUrl" => $store_url . $url_success,
						"failUrl" => $store_url . $url_fail,
						"orderItems" => $orders,
						"shippingAddress" => array (
								"countryCode" => "{$customerCountry}",
								"city" => "{$shipping_city}",
								"firstAddressLine" => "{$shipping_addr_line1}",
								"secondAddressLine" => "{$shipping_addr_line2}",
								"state" => "{$shipping_state}",
								"postalCode" => "{$shipping_post_code}" 
						) 
				) 
		);
		
		if ($tax_rate == "") {
			unset ( $body ["order"] ["orderItems"] ["tax"] );
		}
		
		if (strlen ( $error ) == 0) {
			$context = stream_context_create ( array (
					'http' => array (
							'method' => 'POST',
							'header' => "Authorization: Bearer {$authToken}\r\n" . "Content-Type: application/json\r\n",
							'content' => json_encode ( $body ) 
					) 
			) );
			// Send the request
			$response = file_get_contents ( rtrim ( $apiUrl, "/" ) . '/order/create', FALSE, $context );
			
			// Check for errors
			if ($response === FALSE) {
				die ( 'Error' );
			}
			
			// Decode the response
			$responseData = json_decode ( $response, TRUE );
			
			if (isset ( $responseData ['redirectUrl'] )) {
				$redirect_url = $responseData ['redirectUrl'];
				$responseData ["errorData"] ["errorCode"] = 0;
//			} else {
//				$responseData ["errorData"] ["errorCode"] = 100;
			}
			if (isset ( $responseData ["errorData"] ["errorCode"] ) && $responseData ["errorData"] ["errorCode"] > 0) {
				Mage_Core_Controller_Varien_Action::_redirect ( 'coinfidepaymentmethod/payment/failed/', array (
						'_secure' => false 
				) );
//				$order->setState ( Mage_Sales_Model_Order::STATE_CANCELED, true );
//				$order->setStatus ( "coinfide_error" );
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, "coinfide_error", "Error creating Order. Code: ".$responseData["errorData"]["errorCode"]."; Message: ".$responseData["errorData"]["errorMessage"], false);
				$order->save ();
			} else {
				if (Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/integration_type' ) == 0) {
					Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $redirect_url )->sendResponse ();
				} else {
					$redirect_iframe = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/iframeurl' );
					$param_name = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/iframeurltransfer' );
					
					echo "<form action='{$redirect_iframe}' method='post' name='frm'>";
					
					echo "<input type='hidden' name='" . $param_name . "' value='" . htmlentities ( $redirect_url ) . "?t=iframe&language=" . $locale . "'>";
					
					echo "</form>";
					echo "<script language=\"JavaScript\">";
					echo "document.forms[\"frm\"].submit();";
					echo "</script>";
				}
//				$order->setState ( Mage_Sales_Model_Order::STATE_NEW, true );
//				$order->setStatus ( "coinfide_waiting_for_payment" );
				$newOrderStatus = Mage::getStoreConfig('payment/coinfidepaymentmethod/order_status');
				$order->setState(Mage_Sales_Model_Order::STATE_NEW, $newOrderStatus, 'Order created at Coinfide', false);
				$order->save ();
			}
		} else {
			Mage::log ( $error, null, 'coinfide.log' );
			$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, "coinfide_error", 'Error creating Order', false);
//			$order->setState ( Mage_Sales_Model_Order::STATE_CANCELED, true );
//			$order->setStatus ( "coinfide_error" );
			$order->save ();
		}
	}
	public function responseAction() {
		if ($this->getRequest ()->get ( "flag" ) == "1" && $this->getRequest ()->get ( "orderId" )) {
			$orderId = $this->getRequest ()->get ( "orderId" );
			$order = Mage::getModel ( 'sales/order' )->loadByIncrementId ( $orderId );
			$order->setState ( Mage_Sales_Model_Order::STATE_CANCELED, true );
			$order->save ();
			
			Mage::getSingleton ( 'checkout/session' )->unsQuoteId ();
			Mage_Core_Controller_Varien_Action::_redirect ( 'checkout/onepage/success', array (
					'_secure' => false 
			) );
		} else {
			Mage_Core_Controller_Varien_Action::_redirect ( 'checkout/onepage/error', array (
					'_secure' => false 
			) );
		}
	}
	public function failedAction() {
		$this->loadLayout ();
		$block = $this->getLayout ()->createBlock ( 'Mage_Core_Block_Template', 'coinfidepaymentmethod', array (
				'template' => 'coinfidepaymentmethod/failure.phtml' 
		) );
		$this->getLayout ()->getBlock ( 'content' )->append ( $block );
		$this->renderLayout ();
	}
	public function callbackAction() {
        if (!function_exists('getallheaders')) {
			function getallheaders() { 
				$headers = '';
				foreach ($_SERVER as $name => $value) { 
					if (substr($name, 0, 5) == 'HTTP_') { 
						$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
					}
				}
				return $headers; 
			} 
		}
		
		$inputJSON = file_get_contents ( 'php://input' );
		$input = json_decode ( $inputJSON, TRUE ); // convert JSON into array
		
		$headers = array_change_key_case (getallheaders(), CASE_LOWER);
		$headdersEqual = false;
		$gotErrorProcessing = false;
		
		$receivedChecksum = $headers ['x-body-checksum'];
		$signature = Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/secret' );
		$calculatedChecksum = md5 ( $inputJSON . $signature );
		
		$postOrderStatus = $input ['status'];
		$realId = $input ['externalOrderId'];
		$amount = $input ['amountTotal'];
		
		$order = new Mage_Sales_Model_Order ();
		$order = Mage::getModel ( 'sales/order' )->loadByIncrementId ( $realId );
		
		if ($calculatedChecksum == $receivedChecksum && $input ['status']) {
			
			$order->addStatusHistoryComment ( "Coinfide callback received. Coinfide Order status = \"" . $postOrderStatus . "\"" );
			$order->save ();
			
			$statusArr = array (
					'SE' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_se' ),
					'CA' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_ca' ),
					'PA' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_pa' ),
					'RF' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_rf' ),
					'MP' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_mp' ),
					'MR' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_mr' ),
					'CF' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_cf' ),
					'EX' => Mage::getStoreConfig ( 'payment/coinfidepaymentmethod/order_status_ex' ) 
			);

			$order_status_id = $statusArr [$postOrderStatus];

			//Paid, Mark as Paid
			if (($postOrderStatus == "PA" || $postOrderStatus == "MP") && $order->getState () == Mage_Sales_Model_Order::STATE_NEW) {
				try {
					if (! $order->canInvoice ()) {
						Mage::log ( 'Cannot create an invoice.', null, 'coinfide.log' );
						$gotErrorProcessing = true;
					}
					
					$invoice = Mage::getModel ( 'sales/service_order', $order )->prepareInvoice ();
					
					if (! $invoice->getTotalQty ()) {
						Mage::log ( 'Cannot create an invoice without products. ', null, 'coinfide.log' );
						$gotErrorProcessing = true;
					}
					
					$invoice->setRequestedCaptureCase ( Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE );
					$invoice->register ();
					$transactionSave = Mage::getModel ( 'core/resource_transaction' )->addObject ( $invoice )->addObject ( $invoice->getOrder () );
					
					$transactionSave->save ();
					
					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $order_status_id, 'Order paid at Coinfide', false);
					$order->save();
				} catch ( Mage_Core_Exception $e ) {
					Mage::log ( 'Payment failed. ' . $e, null, 'coinfide.log' );
					$gotErrorProcessing = true;
				}
			//Refunded, Chargebacked, Marked as Refunded
			} elseif (($postOrderStatus == "RF" || $postOrderStatus == "CF" || $postOrderStatus == "MR") && $order->getState () == Mage_Sales_Model_Order::STATE_PROCESSING) {
				try {
					$invoices = array ();
					foreach ( $order->getInvoiceCollection () as $invoice ) {
						if ($invoice->canRefund ()) {
							$invoices [] = $invoice;
						}
					}
					$service = Mage::getModel ( 'sales/service_order', $order );
					
					foreach ( $invoices as $invoice ) {
						$creditmemo = $service->prepareInvoiceCreditmemo ( $invoice );
						$creditmemo->refund ();
					}

					$saveTransaction = Mage::getModel ( 'core/resource_transaction' )->addObject ( $creditmemo )->addObject ( $order )->save ();
					
					$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $order_status_id, 'Order refunded at Coinfide', false);
					$order->save();
				} catch ( Mage_Core_Exception $e ) {
					Mage::log ( 'Refund failed. ' . $e, null, 'coinfide.log' );
					$gotErrorProcessing = true;
				}
			//Canceled
			} elseif (($postOrderStatus == "CA") && $order->getState () == Mage_Sales_Model_Order::STATE_NEW) {
				try {
					$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $order_status_id, 'Order canceled at Coinfide', false);
					$order->save();
				} catch ( Mage_Core_Exception $e ) {
					Mage::log ( 'Cancelation failed. ' . $e, null, 'coinfide.log' );
					$gotErrorProcessing = true;
				}
			//Expired
			} elseif (($postOrderStatus == "EX") && $order->getState () == Mage_Sales_Model_Order::STATE_NEW) {
				try {
					$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, $order_status_id, 'Order expired at Coinfide', false);
					$order->save();
				} catch ( Mage_Core_Exception $e ) {
					Mage::log ( 'Expiration failed. ' . $e, null, 'coinfide.log' );
					$gotErrorProcessing = true;
				}
			}
/*
			if (! $gotErrorProcessing) {
				$order->setStatus ( $statusArr [$postOrderStatus] );
				$order->save ();
			}
*/			echo "OK";
		} else {
			echo "Incorrect data";
		}
	}
}