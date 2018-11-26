<?php
/**
 * Plugin Name: Redistributor Orders
 * Description: Display and manage orders for redistributor.
 * Version: 1.0.0
 * Author: Sodimu Segun & Caleb Chinga | Drugstoc
 * Text Domain: cpac
 * Domain Path: /languages
 * License: GPL2
 */

/*  Copyright 2014  REDISTRIBUTOR_ORDERS  (email : info@drugstoc.biz)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
defined('ABSPATH') or die("No script kiddies please!");   

/**
 * Display and Manage Redistributor Orders on DrugStoc
 */
if(!class_exists('RedistributorOrders')){ 

	class RedistributorOrders  
	{
		// Plugin to all necessary actions and filters
		function __construct()
		{
			// register_activation_hook( __FILE__, 'redistributor_orders_install' ); // Not Needed

			add_action( 'admin_head', array( $this, 'rd_order_scripts' ) ); 
			add_action( 'admin_menu', array( $this, 'register_redistributor_order') ); 
		} 

		// Enqueue all scripts/styles
		function rd_order_scripts(){  
		    wp_enqueue_style( 'ds-datatable-css', "//cdn.datatables.net/1.10.4/css/jquery.dataTables.min.css");  
		    wp_enqueue_script('jquery'); 
		    wp_enqueue_script('ds-datatable-js', "//cdnjs.cloudflare.com/ajax/libs/datatables/1.10.3/js/jquery.dataTables.min.js",  array('jquery', 'jquery-ui'));

		    $user = wp_get_current_user();
			if($user && in_array('shop_manager', $user->roles)){
				wp_enqueue_script('ds-rd-order-js', plugins_url("/redistributor-orders/js/redistributor.js"),  array('jquery' ), '1.0.0', true); 
			}	 
		}

		// Create Route Order Admin Menu 
		function register_redistributor_order(){
		    add_menu_page( 
		    	'Redistributor Orders', 
		    	'Redistributor Orders', 
		    	'read', 
		    	'ds_rd_order', 
		    	array( $this,'redistributor_order_list'), 
		    	'dashicons-list-view' 
			);

			add_submenu_page(
				'ds_rd_order',
				'Order Details',
				'Review Order Items',
				'read',
				'rd_order_items',
				array( $this, 'rd_order_details')
			);   
		}

		// Get Current Distributor 
		function get_distributor(){  
			if(current_user_can('ds_nhc_items')){
				return array( 'name' => "NHC", 'slug' => "nhc_price");
		 	}else if (current_user_can('ds_elfimo_items')) {
		 		return array( 'name' => "Elfimo Pharma", 'slug' => "elfimo_price"); 
		 	} // or both?
		} 

		// List all Orders
		function redistributor_order_list(){
			global $wpdb;    

			$distributor = $this->get_distributor();
			$user_ID = get_current_user_id(); 
			?>

			<h3>Your Order History <?php //echo $distributor['name']; ?></h3>
			<i><h4>List of Routed Drugstoc Orders</h4></i><br/>
			<table class="wp-list-table widefat fixed posts">
			<thead>
				<tr>
					<th scope="col" id="cb" class="manage-column column-cb check-column" style=""></th>
					<th scope="col" id="order_status" class="manage-column column-order_status" style=""><span class="status_head tips">Status</span></th>
					<th scope="col" id="order_title" class="manage-column column-order_title sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=ID&order=asc"><span>Order</span><span class="sorting-indicator"></span></a></th>
					<th scope="col" id="order_items" class="manage-column column-order_items" style="">Purchased</th>
					<th scope="col" id="customer_message" class="manage-column column-customer_message" style=""><span class="notes_head tips">Customer Message</span></th>
					<th scope="col" id="order_notes" class="manage-column column-order_notes" style=""><span class="order-notes_head tips">Order Notes</span></th>
					<th scope="col" id="order_date" class="manage-column column-order_date sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=date&order=asc"><span>Date</span><span class="sorting-indicator"></span></a></th>
					<th scope="col" id="order_total" class="manage-column column-order_total sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=order_total&order=asc"><span>Total</span><span class="sorting-indicator"></span></a></th>
				</tr>
			</thead>
			<tbody>
			<?php 
			$routed_orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}routed_order_items WHERE distributor = $user_ID group by order_id ORDER BY created_at DESC");
			$total_orders = 0;

			foreach ($routed_orders as $customer_order) {
				$order = new WC_Order($customer_order->order_id);  
				if($order->status != 'trash'){
					// User
					$user = get_user_by( 'id', $order->customer_user );
					$user_primary_distributor = get_user_meta($user->ID,'primary_distributor',true);
				?>
					<tr>
						<td><input type="checkbox" name="post[]" value="<?php echo esc_html( $order->get_order_number() );?>"></td>
						<td><?php echo $order->status; ?></td>
						<td><a href="<?php echo menu_page_url('rd_order_items',false).'&order='.$order->id;?>"><b><?php echo esc_html( $order->get_order_number() );?><b/> by <?php echo esc_html( $user->display_name );?></a></td>
						<td><?php echo count($order->get_items());?></td> 
						<td><?php echo (isset($order->customer_message)? $order->customer_message:'None');?></td>
						<td><?php echo (isset($order->customer_note)? $order->customer_note:'None');?></td>
						<td><?php echo $order->order_date;?></td>
						<td><?php echo $order->get_formatted_order_total();?></td> 
					</tr>
				<?php
					$total_orders += $order->get_order_total(); 
				}
			}?> 
			</tbody>	
			<tfoot> 
				<tr>
					<th scope="col" id="cb" class="manage-column column-cb check-column" style=""></th>
					<th scope="col" id="order_status" class="manage-column column-order_status" style=""><span class="status_head tips">Status</span></th>
					<th scope="col" id="order_title" class="manage-column column-order_title sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=ID&order=asc"><span>Order</span><span class="sorting-indicator"></span></a></th>
					<th scope="col" id="order_items" class="manage-column column-order_items" style="">Purchased</th>
					<th scope="col" id="customer_message" class="manage-column column-customer_message" style=""><span class="notes_head tips">Customer Message</span></th>
					<th scope="col" id="order_notes" class="manage-column column-order_notes" style=""><span class="order-notes_head tips">Order Notes</span></th>
					<th scope="col" id="order_date" class="manage-column column-order_date sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=date&order=asc"><span>Date</span><span class="sorting-indicator"></span></a></th>
					<th scope="col" id="order_total" class="manage-column column-order_total sortable desc" style=""><a href="http://drugstoc.biz/wp-admin/edit.php?post_type=shop_order&orderby=order_total&order=asc"><span>Total</span><span class="sorting-indicator"></span></a></th>
				</tr> 
			</tfoot>
			</table>
		<?php
		}

		// Order Details
		function rd_order_details($id){
			if(isset($_GET['order']) && $_GET['order'] > 0){ 
				$orderid = $_GET['order'];  

				global $wpdb, $woocommerce;

				$order = new WC_Order($orderid);
				$distributor =  $this->get_distributor();

				$user = get_user_by( 'id', $order->customer_user ); 

				$order_totals = $wpdb->get_results("SELECT SUM(line_total) as 'total' FROM {$wpdb->prefix}routed_order_items WHERE order_id = {$orderid} ");  
				$order_items2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}routed_order_items WHERE order_id = {$orderid} ");

				$order_total = $order_totals[0]->total; ?>
				<div id="order_data" class="panel">
				<h2>Order Details (#<?php echo $order->id;?>) </h2>
				<p class="order_number"><i><b>Carefully reveiw all items ordered</b></i></p> 
				<div class="order_data_column_container">
					<div class="order_data_column">
						<table>  
							<tr>
								<td align="right" valign="middle" class="form-field"><h4>General Details&nbsp;&nbsp;</h4></td>
								<td>
									<p class="form-field"><label for="order_date">Order date:</label>
										<span><?php echo date('d M Y h:m:s A', strtotime($order->order_date));?></span> 
									</p>
									<p class="form-field form-field-wide">
										<label>Order status:</label> 
										<span><?php echo $order->status; ?></span>
										<?php if($order->status == 'on-hold'){?> 
	                                        <a title="Mark Processing" id="m_process" class="button tips processing" href="<?php //echo wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_processing&order_id='.$order->id ), 'woocommerce_mark_order_processing' );?> ">Mark Processing</a>
	                                    <?php }else if($order->status == 'processing' || $order->status == 'on-hold'){?>    
	                                        <a title="Mark Complete" id="m_complete" class="button tips complete" href="<?php //echo wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_complete&order_id='.$order->id), 'woocommerce_mark_order_processing' );?>">Mark Complete</a>
	                                    <?php }?>
									</p>
									<p class="form-field form-field-wide">
										<label for="customer_user">Customer: </label>
										<?php echo esc_html( $user->display_name );?> 
									</p>  
									<p class="form-field form-field-wide"><label>Customer Phone Number: </label><span><?php echo get_user_meta($user->ID,'phonenumber',true);?></span></p>
									<p class="form-field form-field-wide"><label>Primary Distributor: </label><span><?php echo get_user_meta($user->ID,'primary_distributor',true);?></span></p>
									<p class="form-field form-field-wide"><label>Order Total: </label><h3><?php echo "&#8358;".number_format($order_total, 2); ?></h3></p>
									<!-- <p class="form-field form-field-wide"><label>Order Total: </label><h3><?php //echo $order->get_formatted_order_total() ;?></h3></p> -->
									<p style="border-color:2px"><label>DrugStoc Commission :</label> <span style="background-color:white; padding:5px"><?php echo "&#8358;".number_format(($order_total * 0.05), 2); ?></span></p> 
								</td>	
							</tr> 
							<tr>
								<td align="right" valign="top" class="form-field"><h4>Shipping Address&nbsp;&nbsp;</h4></td>
								<td><?php echo $order->get_formatted_shipping_address();?></td>
							</tr>
						</table> 
						<br/><br/>
					</div> 
				</div>
				<div class="clear"></div>
				</div> 
				<p>
					<button id="process_order" class="button custom-button">Process Order</button>
					<button id="decline_order" class="button custom-button">Decline Order</button>
				</p>
				<div id="woocommerce-order-items" class="postbox " >  
					<div class="handlediv" title="Click to toggle"><br /></div> 
					<h3 class="hndle"><span id="orderitems">&nbsp;Order Items</span></h3> 
					<div class="inside">
						<table class="redistributor">
							<thead>
								<tr> 
									<th>NAFDAC-No</th>	
									<th>Item Name</th>
	 								<th>Unit Price</th>
	 								<th>Quantity</th>
									<th>Total</th>  
									<th title="DrugStoc Commission per line item">Commission</th>
									<th>Notes</th>
									<th><b>In stock</b></th>
								</tr>
							</thead>
							<tbody>
							<?php   
							foreach ($order_items2 as $key => $item) { 
		
								$product = new WC_Product( $item->item_id );  

					 			?><tr class="item" data-item-id="<?php echo $item->item_id; ?>"> 
									<td data-item-id="<?php echo $item->item_id; ?>" ><?php echo $product->get_attribute('pa_nafdac-no'); ?></td>
									<td class="name"><a target="_blank" href="<?php echo get_permalink($item->item_id);?>"><?php echo $product->get_title();?></a></td>
									<td>&#8358;<?php echo $product->price; ?></td>
									<td class="quantity"><?php echo $item->item_qty; ?></td>
									<td class="line_cost">&#8358;<?php echo number_format_i18n ($item->line_total);?></td>
									<td class="commission">&#8358;<?php echo number_format($item->line_total*0.05, 2); ?></td>
									<td><textarea class="notes" rows="2" cols="20"><?php echo $item->notes;?></textarea></td>
									<td>
									<?php if($item->in_stock == 1) {?>
										<input type="checkbox" class="_case" checked name="case[]" style="border: 2px solid green" title="In stock" data-item-id="<?php echo $item->item_id; ?>"/>
									<?php }else{ ?>
										<input type="checkbox" class="_case" name="case[]" style="border: 2px solid red" title="Out of stock" data-item-id="<?php echo $item->item_id; ?>"/>
									<?php }?>
									</td>
								</tr>
							<?php   
							} ?> 
							</tbody> 
							<tfoot>
								<tr> 
									<th>NAFDAC-No</th>
									<th>Item Name</th>
	 								<th>Unit Price</th>
	 								<th>Quantity</th>
									<th>Total</th>  
									<th title="DrugStoc Commission per line item">Commission</th>
									<th>Notes</th>
									<th><b>In stock</b></th>
								</tr>
							</foot>
						</table>  
			 	  	</div>
	 				<p style="float: left; clear: right"> 
						<button id="process_order2" class="button custom-button">Process Order</button>
						<button id="decline_order2" class="button custom-button">Decline Order</button>
					</p>
	 		 	    </div>
	 		 	    <script type="text/javascript">
	 		 	    (function ($) {    
	 		 	  		var data = [];
	 		 	  		// Process Order Button
					 	jQuery("#process_order, #process_order2").click(function(e){
							e.preventDefault();

							jQuery('tr.item input[type=checkbox]').each(function (i, v){
								var row = {
									orderid: "<?php echo $order->id;?>",
									itemid: jQuery(this).data("itemId"),
									instock: jQuery(this).prop("checked"),
									notes: jQuery.trim(jQuery(v).parents('tr.item').find('.notes').val())
								};
								data.push(row);
							});

							console.log("Review Order:");
							console.log(data);

							$.ajax({
								type: "POST",
								url: '<?php echo plugins_url("redistributor-orders/process_order.php",false);?>',
								data: { order : data, status: 1 },
								beforeSend: function(){
									$("#orderitems").html("  Processing Order ...");
								},
								success: function(msg){
									data = [];
									alert('Order Review Complete!');
									console.log(msg);
									$("#orderitems").html("&nbsp;Order Items"); 
								}
							}); 
						});

					 	// Decline Order Button
						jQuery("#decline_order, #decline_order2").click(function(e){
							e.preventDefault();   

							var r = confirm("You about to decline an Order. \nAre you sure?");
						    if (r == true) {
						        // Send Order ID and User ID
								data.push({orderid:"<?php echo $order->id;?>", userid: "<?php echo get_current_user_id(); ?>"});
								
								$.ajax({
									type: "POST",
									url: '<?php echo plugins_url("redistributor-orders/process_order.php",false);?>',
									data: { order : data, status: -1 },
									beforeSend: function(){
										$("#orderitems").html("  Declining Order ... ");
									},
									success: function(msg){ 
										data = [];
										alert('Order Decline Complete!'); 
										$("#orderitems").html("&nbsp;Order Items"); 
										window.location = "<?php echo menu_page_url('ds_rd_order',false)?>";
									}
								});
						    } else {
						        return false;
						    }	
						});

						// Mark Complete Button 
						jQuery("#m_complete").click(function(e){
							e.preventDefault();     
							var row = {
								orderid: "<?php echo $order->id;?>",
							}; 
							$.ajax({
								type: "POST",
								url: '<?php echo plugins_url("redistributor-orders/process_order.php",false);?>',
								data: { order: row, order_status : 'complete' }, 
								success: function(msg){ 
									alert('Order Status Updated!');
									jQuery("#m_complete").remove(); 
								}
							});
						});

						// Mark Process Button
						jQuery("#m_process").click(function(e){
							e.preventDefault();    
							var row = {
								orderid: "<?php echo $order->id;?>",
							};
							$.ajax({
								type: "POST",
								url: '<?php echo plugins_url("redistributor-orders/process_order.php",false);?>',
								data: {  order: row, order_status : 'processing' }, 
								success: function(msg){ 
									alert('Order Status Updated!');
									jQuery("#m_process").prop("title", "Mark Complete");
								}
							});
						});

	 		 	    }(jQuery))</script>
 		 	  <?php 
			}else{?>
				<h3>Please select an Order to review!</h3>
			<?php } 
		} 
	} // >> EOC
}

// Launch Plugin
$redist = new RedistributorOrders;

