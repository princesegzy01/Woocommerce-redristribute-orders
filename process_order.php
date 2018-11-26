<?php
require_once('../../../wp-load.php');

// Order Array 
$order = isset($_POST['order'])? $_POST['order']:'';
$status = isset($_POST['status'])? $_POST['status']:0; 
$_status = isset($_POST['order_status'])? $_POST['order_status']:'';

// Update Distributor Order(s)
global $wpdb, $woocommerce;    

$table = "{$wpdb->prefix}routed_order_items";
$_order = new WC_Order($order[0]['orderid']);

/** 
 * Process and Decline Order
 * @var [type]
 */
if($status == 1){ // Process Order
	foreach ($order as $key => $item) {  
		$instock = ($item['instock'] == "true")? 1:0;

		$stats = $wpdb->query( 
			$wpdb->prepare( 
				"UPDATE {$table} SET in_stock = %d, notes = %s
				WHERE order_id = %d AND item_id = %d",
			        $instock, 
			        strip_tags( stripslashes($item['notes'])), 
			        $item['orderid'], 
			        $item['itemid'])
		); 
	}

	if($_order->status == 'on-hold'){
		$_order->update_status('processing'); 
	}
	echo "Order[{$order[0]['orderid']}] Status Changed";
}else if ($status == -1) { // Decline Order 
	// $stats = $wpdb->query( 
	// 	$wpdb->prepare( 
	// 		"UPDATE {$table} SET status = -1
	// 			WHERE order_id = %d AND distributor = %d", 
	// 	        $order[0]['orderid'], 
	// 	        $order[0]['userid'])
	// );
 
	$stats = $wpdb->query( 
		$wpdb->prepare( 
			"DELETE FROM {$table} 
				WHERE order_id = %d AND distributor = %d", 
		        $order[0]['orderid'], 
		        $order[0]['userid'])
	); 

	$_order->update_status('on-hold'); 

	$headers = 'From: DrugStoc <mailer@drugstoc.ng>'."\r\n";
	$headers .= "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8"."\r\n"; 
	$headers .= 'Bcc: adhamyehia@gmail.com'."\r\n"; 
	  
	$rt = @mail("adhamyehia@gmail.com", "Order[{$order[0]['orderid']}] Declined", 
		wordwrap("<p>Hi, </p><p>Order [{$order[0]['orderid']}] has been DECLINED by Distributor.</p><p>Please re-route</p><p>Thank You,<br>DrugStoc Team.</p>", 200, "\n", true), 
		wordwrap($headers, 75, "\n", true) ); 

	echo "Order[{$order[0]['orderid']}] Declined";
}

//Change Order Status
if($_status != ''){ 
	$_order->update_status($_status);  
	echo "Done";
}

