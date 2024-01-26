<?php

function create_order() {
	$path = preg_replace( '/wp-content.*$/', '', __DIR__ );


	include_once $path . '/wp-config.php';
	include_once $path . '/wp-load.php';
	include_once $path . '/wp-includes/wp-db.php';
	include_once $path . '/wp-includes/pluggable.php';
	global $wpdb;
	$ordertable = $wpdb->prefix . "order_table";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $ordertable (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `wp_order` text NOT NULL,
		`doba_order` text NOT NULL,
		`paid_order` tinyint(4) DEFAULT 0,
    PRIMARY KEY (`id`)
    ) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	$getorder = $wpdb->get_results(  "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'" , ARRAY_A );

//	$street = "4217 W 3rd St, Ste B";
//	$city = "Los Angeles";
//	$state = "CA";
//	$postal = "90020";
//	$country = "US";

	$unique_iden = array();
	$arr = array();
	$order = array();
	$postid = array();
	$post = array();
	$ord_id = array();
	foreach ( $getorder as $orders ) {
		$post_id = $orders['ID'];
		$item = '';
		$postmeta = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'" , ARRAY_A );
		foreach ( $postmeta as $meta ) {
			$postid[$meta['meta_key']] = $meta['meta_value'];
		}
		$post[$post_id] = $postid;
		$order_item = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}woocommerce_order_items where order_id ='$post_id'" , ARRAY_A );

		foreach ( $order_item as $order_items ) {
			$order_id = $order_items['order_item_id'];
			$ord_id[] = $order_id;
			$orderitemmeta = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta where order_item_id ='$order_id'" , ARRAY_A );

			foreach ( $orderitemmeta as $itemmeta ) {
				$order[$itemmeta['meta_key']][] = $itemmeta['meta_value'];
			}
			$ord_post_id[$post_id] = $ord_id;
			$ord_id = array();

			$arr[$post_id] = $order;
			$order = array();

			$count = count( $arr[$post_id]['_product_id'] );

			for ( $j = 0; $j < $count; $j++ ) {

				$item_id = $wpdb->get_results(  "SELECT meta_value FROM {$wpdb->prefix}postmeta where post_id = {$arr[$post_id]['_product_id'][$j]} and meta_key='_item_id'" , ARRAY_A );

				if ( !empty( $item_id ) ) {
					// output data of each row
					foreach ( $item_id as $id ) {
						$item_id = $id['meta_value'];
						$item .= "<item>";
						$item .= "<item_id>$item_id</item_id>";
						$item .= "<quantity>{$arr[$post_id][_qty][$j]}</quantity>";
						$item .= "</item>";
					}
				}
			}

			$item_id = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}order_table where wp_order = {$post_id}" , ARRAY_A );

			if ( empty( $item_id ) ) {
				$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
				$table_name = $wpdb->prefix . 'doba_detail';
				$select = "Select * from $table_name";
				$result = $wpdb->get_results(  $select , ARRAY_A );
				$username = $result[0]['username'];
				$password = $result[0]['password'];
				$retailer = $result[0]['retailer_id'];
				$first_name = "";
				$last_name = "";
				$street = "";
				$city = "";
				$state= "";
				$postal="";
				$country = "";
				if ( empty( $post[$post_id][_billing_first_name] ) ) {
					$first_name = $post[$post_id][_shipping_first_name];
				} else {
					$first_name = $post[$post_id][_billing_first_name];
				}
				if ( empty( $post[$post_id][_billing_first_name] ) ) {
					$last_name = $post[$post_id][_shipping_last_name];
				} else {
					$last_name = $post[$post_id][_billing_last_name];
				}
				$strRequest = "
<dce>
  <request>
    <authentication>
      <username>$username</username>
      <password>$password</password>
    </authentication>
    <retailer_id>$retailer</retailer_id>
    <action>createOrder</action>
    <shipping_firstname>{$first_name}</shipping_firstname>
    <shipping_lastname>{$last_name}</shipping_lastname>
    <shipping_street>$street</shipping_street>
    <shipping_city>$city</shipping_city>
    <shipping_state>$state</shipping_state>
    <shipping_postal>$postal</shipping_postal>
    <shipping_country>$country</shipping_country>
    <ip_address>{$post[$post_id][_customer_ip_address]}</ip_address>
    <items>
      $item
    </items>
  </request>
</dce>
";
				$connection = curl_init();
				curl_setopt( $connection, CURLOPT_URL, $URL );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $strRequest );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				set_time_limit( 108000 );
				$strResponse = curl_exec( $connection );
				if ( curl_errno( $connection ) ) {
					print "Curl error: " . curl_error( $connection );
				} else {
					$info = curl_getinfo( $connection );
					//print "HTTP Response Code = ".$info["http_code"]."\n";
				}
				curl_close( $connection );
				$res = new SimpleXMLElement( $strResponse );
				if ( $res->response->outcome == "failure" ) {
					echo $res->response->error->message;
				} else {
					$order_doba = $res->response->order_id;
					$wpdb->get_results(  "Insert into {$wpdb->prefix}order_table SET wp_order={$post_id}, doba_order = {$order_doba}"  );
				}
			}
		}
	}
}

function update_inventory() {
	$path = preg_replace( '/wp-content.*$/', '', __DIR__ );


	include_once $path . '/wp-config.php';
	include_once $path . '/wp-load.php';
	include_once $path . '/wp-includes/wp-db.php';
	include_once $path . '/wp-includes/pluggable.php';
	global $wpdb;
	$table_name = $wpdb->prefix . 'doba_detail';
	$select = "Select * from $table_name";
	$result = $wpdb->get_results(  $select , ARRAY_A );
	$username = $result[0]['username'];
	$password = $result[0]['password'];
	$retailer = $result[0]['retailer_id'];
//	$price_sql = "Select * from {$wpdb->prefix}price_table where is_active = 1";
//	$price_result = $wpdb->get_results(  $price_sql , ARRAY_A );
//	$price_found = $price_result[0]['price_percent'];
//	$threshold = $price_result[0]['threshold_value'];
	$list_table = $wpdb->get_results(  "Select * from {$wpdb->prefix}doba_list" , ARRAY_A );
	foreach ( $list_table as $list ) {
		$price_found = $list['price_setting'];
		if ( $price_found ) {

			$list_id = $list['id'];
			$count = $list['item_count'];
			$icount = ceil( $count / 10 );
			$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
			for ( $a = 1; $a <= $icount; $a++ ) {
				$strRequest1 = "
<dce>
  <request>
    <authentication>
      <username>$username</username>
      <password>$password</password>
    </authentication>
    <retailer_id>$retailer</retailer_id>
    <action>getProductInventory</action>
    <limit>10</limit>
    <page>$a</page>
    <list_ids>
      <list_ids>$list_id</list_ids>
    </list_ids>
  </request>
</dce>
";

				$connection1 = curl_init();
				curl_setopt( $connection1, CURLOPT_URL, $URL );
				curl_setopt( $connection1, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection1, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection1, CURLOPT_POST, 1 );
				curl_setopt( $connection1, CURLOPT_POSTFIELDS, $strRequest1 );
				curl_setopt( $connection1, CURLOPT_RETURNTRANSFER, 1 );
				set_time_limit( 108000 );
				$strResponse1 = curl_exec( $connection1 );
				if ( curl_errno( $connection1 ) ) {
					print "Curl error: " . curl_error( $connection1 );
				} else {
					$info = curl_getinfo( $connection1 );
					//print "==============================================\n";
				}
				curl_close( $connection1 );
				$res = new SimpleXMLElement( $strResponse1 );

				$allitems = $res->response->products;
				$i = 0;
				foreach ( $allitems as $products ) {

					foreach ( $products->item as $product ) {
//						print_r($product);
						$item_id = $product->item_id;
						$msrp = $product->msrp;
						$price_calc = floatval( $product->price );
						$price = floatval( ($price_calc * (($price_found + 100) / 100) ) );
						$stock = $product->stock;
						$qty_avail = $product->qty_avail;
						$visible = '';
						if ( $stock == 'in-stock' ) {
							$stock = 'instock';
							$visible = 'publish';
						} else {
							$stock = 'instock';
							$visible = 'draft';
						}
						if ( $stock == 'out-of-stock' ) {
							$stock = 'outofstock';
							$visible = 'draft';
						}
						if ( $stock == 'discontinued' ) {
							$stock = 'outofstock';
							$visible = 'draft';
						}

						$updated = $wpdb->query(  "UPDATE {$wpdb->prefix}doba_items set msrp = '$msrp' , "
										. "price ='$price', qty_avail='$qty_avail',"
										. "stock = '$stock' where item_id='$item_id'"  );
//						print_r($updated);
						if ( $updated ) {
							$post_id = $wpdb->get_results(  "select post_id from {$wpdb->prefix}postmeta where meta_key='_item_id' and meta_value=$item_id" , ARRAY_A );
//							print_r($post_id);
							foreach ( $post_id as $postid ) {
								$drop_ship = $wpdb->get_results(  "select meta_value from {$wpdb->prefix}postmeta where meta_key='_drop_ship_fee' and post_id = {$postid['post_id']} limit 1" , ARRAY_A );
								$drop_ship_fee = $drop_ship[0]['meta_value'];
								$price = floatval( $price + $drop_ship_fee );
								$postupdate = $wpdb->query(  "update {$wpdb->prefix}posts set post_status = '$visible' where ID = {$postid['post_id']}" ) ;

								$updateprice = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$msrp' where meta_key='_msrp' and post_id='{$postid['post_id']}'"  );
								$updateregular = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$price' where meta_key='_regular_price' and post_id='{$postid['post_id']}'"  );
								$updatestatus = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$stock' where meta_key='_stock_status' and post_id='{$postid['post_id']}'"  );
								$updateqty = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$qty_avail' where meta_key='_stock' and post_id='{$postid['post_id']}'"  );
								$updateqty1 = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$price' where meta_key='_price' and post_id='{$postid['post_id']}'"  );
							}
						}
					}
				}
			}
		}
	}
}

function create_track() {
	$path = preg_replace( '/wp-content.*$/', '', __DIR__ );


	include_once $path . '/wp-config.php';
	include_once $path . '/wp-load.php';
	include_once $path . '/wp-includes/wp-db.php';
	include_once $path . '/wp-includes/pluggable.php';
	global $wpdb;
	$track_order = $wpdb->prefix . "track_order";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $track_order (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `carrier` text NOT NULL,
		`tracking` text NOT NULL,
		`shipment_date` text NOT NULL,
		`doba_order` text NOT NULL,
    PRIMARY KEY (`id`)
    ) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	$item_id = $wpdb->get_results(  "SELECT * FROM {$wpdb->prefix}order_table where paid_order=0" , ARRAY_A );
	foreach ( $item_id as $orderid ) {
		$order_id = $orderid['doba_order'];
		$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";

		$table_name = $wpdb->prefix . 'doba_detail';
		$select = "Select * from $table_name";
		$result = $wpdb->get_results(  $select , ARRAY_A );
		$username = $result[0]['username'];
		$password = $result[0]['password'];
		$retailer = $result[0]['retailer_id'];

		//trackorder

		$strRequest = "
<dce>
  <request>
    <authentication>
      <username>$username</username>
      <password>$password</password>
    </authentication>
    <retailer_id>$retailer</retailer_id>
    <action>getOrderDetail</action>
    <order_ids>
      <order_id>{$order_id}</order_id>
    </order_ids>
  </request>
</dce>
";
		$connection = curl_init();
		curl_setopt( $connection, CURLOPT_URL, $URL );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $connection, CURLOPT_POST, 1 );
		curl_setopt( $connection, CURLOPT_POSTFIELDS, $strRequest );
		curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
		set_time_limit( 108000 );
		$strResponse = curl_exec( $connection );
		if ( curl_errno( $connection ) ) {
			print "Curl error: " . curl_error( $connection );
		} else {
			$info = curl_getinfo( $connection );
			//print "HTTP Response Code = " . $info["http_code"] . "\n";
		}
		curl_close( $connection );

//echo $strResponse;
		$res = new SimpleXMLElement( $strResponse );
		if ( $res->response->outcome == "failure" ) {
			echo $res->response->error->message;
		} else {
			$carrier = $res->response->orders->order->supplier_orders->supplier_order->shipments->shipment->carrier;
			$tracking = $res->response->orders->order->supplier_orders->supplier_order->shipments->shipment->tracking;
			$shipment_date = $res->response->orders->order->supplier_orders->supplier_order->shipments->shipment->shipment_date;
			$wpdb->get_results(  "Insert into $track_order SET carrier={$carrier}, tracking = {$tracking} , shipment_date = {$shipment_date}, doba_order={$order_id}"  );
			$wpdb->get_results(  "Update {$wpdb->prefix}order_table set paid_order = 1 where doba_order= {$order_id}"  );
		}
	}
}

function create_item() {
	$path = preg_replace( '/wp-content.*$/', '', __DIR__ );


	include_once $path . '/wp-config.php';
	include_once $path . '/wp-load.php';
	include_once $path . '/wp-includes/wp-db.php';
	include_once $path . '/wp-includes/pluggable.php';
	require_once 'doba_admin.php';
	global $wpdb;
	$items_table = $wpdb->prefix . "doba_items";
	$charset_collate = $wpdb->get_charset_collate();
	$create_item = "CREATE TABLE IF NOT EXISTS $items_table (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `list_id` int(11) DEFAULT NULL,
  `custom_price` float(9,2) DEFAULT NULL,
  `est_avail` datetime DEFAULT NULL,
  `in_specified_list` tinyint(4) DEFAULT NULL,
  `in_warehouse` tinyint(4) DEFAULT NULL,
  `item_dim1` float(9,2) DEFAULT NULL,
  `item_dim2` float(9,2) DEFAULT NULL,
  `item_dim3` float(9,2) DEFAULT NULL,
  `item_sku` varchar(32) DEFAULT NULL,
  `item_weight` float(9,2) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `map` float(9,2) DEFAULT NULL,
  `mpn` varchar(50) DEFAULT NULL,
  `msrp` float(9,2) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `original_price` float(9,2) DEFAULT NULL,
  `prepay_price` float(9,2) DEFAULT NULL,
  `price` float(9,2) DEFAULT NULL,
  `qty_avail` float DEFAULT NULL,
  `stock` enum('in-stock','out-of-stock','discontinued') DEFAULT NULL,
  `supplier_id` varchar(50) DEFAULT NULL,
  `upc` varchar(50) DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_id` (`item_id`)
) $charset_collate ;
";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $create_item );
	$table_name = $wpdb->prefix . 'doba_detail';
	$select = "Select * from $table_name";
	$result = $wpdb->get_results(  $select , ARRAY_A );
	$username = $result[0]['username'];
	$password = $result[0]['password'];
	$retailer = $result[0]['retailer_id'];

	$current_user = get_current_user_id();
	$list_table = $wpdb->get_results(  "Select * from {$wpdb->prefix}doba_list" , ARRAY_A );
	foreach ( $list_table as $list ) {
		$price_found = $list['price_setting'];
		if ( $price_found ) {

			$list_id = $list[id];
			$count = $list[item_count];
			$icount = ceil( $count / 10 );

			$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
			for ( $a = 1; $a <= $icount; $a++ ) {
				$strRequest = "
<dce>
  <request>
    <authentication>
      <username>$username</username>
      <password>$password</password>
    </authentication>
    <retailer_id>$retailer</retailer_id>
    <action>getProductDetail</action>
	<limit>10</limit>
	<page>$a</page>
    	<list_ids>
      <list_ids>$list_id</list_ids>
    </list_ids>
  </request>
</dce>
";

				$connection = curl_init();
				curl_setopt( $connection, CURLOPT_URL, $URL );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYPEER, 0 );
				curl_setopt( $connection, CURLOPT_SSL_VERIFYHOST, 0 );
				curl_setopt( $connection, CURLOPT_POST, 1 );
				curl_setopt( $connection, CURLOPT_POSTFIELDS, $strRequest );
				curl_setopt( $connection, CURLOPT_RETURNTRANSFER, 1 );
				set_time_limit( 108000 );
				$strResponse = curl_exec( $connection );
				if ( curl_errno( $connection ) ) {
					print "Curl error: " . curl_error( $connection );
				} else {
					$info = curl_getinfo( $connection );
					//print "=======================\n";
				}
				curl_close( $connection );

				$res = new SimpleXMLElement( $strResponse );
//		print_r($res);
				$currentdate = date( "Y-m-d H:i:s" );
				$gmdate = gmdate( "Y-m-d H:i:s" );
				$allitems = $res->response->products;

				foreach ( $allitems as $products ) {
					foreach ( $products->product as $product ) {
						foreach ( $product->items->item as $items ) {
							//product data
							$product_id = $product->product_id;

							$post_content = $product->description;
							$post_additional = $product->additional_details;
							$content = $post_content . $post_additional;
							$posts_content = str_replace( "\"", "&quot;", $content );
							$post_content = addslashes( $posts_content );
							$post_title = $product->title;
							$posts_title = str_replace( "\"", "&quot;", $post_title );
							$title = addslashes( $posts_title );
							$post_brand = $product->brand;
							$post_brand = str_replace( "\"", "&quot;", $post_brand );
							$post_brand = addslashes( $post_brand );
							$status = $product->status;
							$sku = $product->product_sku;
							$name = strtolower( $title );
							$post_name = preg_replace( '/\_/', '-', $name );
							$post_name = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $post_name );
							$post_name = preg_replace( '/\s+/', '-', $post_name );
							$post_name = preg_replace( '/-+/', '-', $post_name );
							$postsname = $post_name . '-' . $sku;
							$weight = $product->ship_weight;
							$length = $product->ship_length;
							$width = $product->ship_width;
							$height = $product->ship_height;
							$itcount = count( $items );
							$guid = site_url( "/$postsname/" );
							//supplier data
							$supplier = $product->supplier->name;
							$supplier = str_replace( "\"", "&quot;", $supplier );
							$supplier = addslashes( $supplier );
							$drop_ship_fee = floatval( $product->ship_cost );
							//1st item data 
							$item_id = $items->item_id;
							$item_supplier = $items->supplier_id;
							$item_name = $items->name;
							$item_name = str_replace( "\"", "&quot;", $item_name );
							$item_name = addslashes( $item_name );
							$item_sku = $items->item_sku;
							$mpn = $items->mpn;
							$upc = $items->upc;
							$map = $items->map;
							$price_calc = floatval( $items->price );

							$price = floatval( ($price_calc * (($price_found + 100) / 100) ) );
							$price = floatval( $price + $drop_ship_fee );
							$post_excerpt = '$ ' . $drop_ship_fee . ' per shipping included';
							$original_price = $items->original_price;
							$prepay_price = $items->prepay_price;
							$custom_price = $items->custom_price;
							$est_avail = $items->est_avail;
							$in_specified_list = $items->in_specified_list;
							$in_warehouse = $items->in_warehouse;
							$item_dim1 = $items->item_dim1;
							$item_dim2 = $items->item_dim2;
							$item_dim3 = $items->item_dim3;
							$item_weight = $items->item_weight;
							$last_update = $items->last_update;
							$msrp = $items->msrp;
							$qty_avail = $items->qty_avail;
							$stock = $items->stock;
							$visible = '';
							$uniqueItems = array();
							if ( $stock == 'in-stock' ) {
								$stock = 'instock';
								$visible = 'publish';
							} else {
								$stock = 'instock';
								$visible = 'draft';
							}
							if ( $stock == 'out-of-stock' ) {
								$stock = 'outofstock';
								$visible = 'draft';
							}
							if ( $stock == 'discontinued' ) {
								$stock = 'outofstock';
								$visible = 'draft';
							}
							$user = $current_user;
							$last_sync = date( "Y-m-d H:i:s" );
							$post_id = '';
							//doba_item_table insert
							$itemexist = $wpdb->get_results(  "select item_id from $items_table where item_id=$item_id" , ARRAY_A );
							if ( empty( $itemexist ) ) {
								$insert_item = "insert into $items_table(item_id,product_id,list_id,"
										. "custom_price,"
										. "est_avail,"
										. "in_specified_list,"
										. "in_warehouse,"
										. "item_dim1,"
										. "item_dim2,"
										. "item_dim3,"
										. "item_sku,"
										. "item_weight,"
										. "last_update,"
										. "map,"
										. "mpn,"
										. "msrp,"
										. "name,"
										. "original_price,"
										. "prepay_price,"
										. "price,"
										. "qty_avail,"
										. "stock,"
										. "supplier_id,"
										. "upc,"
										. "last_sync) values('$item_id','$product_id','$list_id','$custom_price',"
										. "'$est_avail',"
										. "'$in_specified_list',"
										. "'$in_warehouse',"
										. "'$item_dim1',"
										. "'$item_dim2',"
										. "'$item_dim3',"
										. "'$item_sku',"
										. "'$item_weight',"
										. "'$last_update',"
										. "'$map',"
										. "'$mpn',"
										. "'$msrp',"
										. "'$item_name',"
										. "'$original_price',"
										. "'$prepay_price',"
										. "'$price',"
										. "'$qty_avail',"
										. "'$stock',"
										. "'$supplier',"
										. "'$upc',"
										. "'$last_sync')";

								$wpdb->get_results(  $insert_item  );
								$inserted_id = $wpdb->insert_id;
								if ( $inserted_id ) {
									//wp_post for 1st data
									$post = array(
										'post_author' => "$current_user",
										'post_date' => "$currentdate",
										'post_date_gmt' => "$gmdate",
										'post_content' => "$post_content",
										'post_title' => "$title",
										'post_excerpt' => "$post_excerpt",
										'post_status' => "$visible",
										'post_modified' => "$currentdate",
										'post_modified_gmt' => "$gmdate",
										'post_parent' => '0',
										'guid' => $guid,
										'post_type' => "product",
									);
									$post_id = wp_insert_post( $post );
									if ( $post_id ) {
										add_post_meta( $post_id, 'total_sales', '0' );
										add_post_meta( $post_id, '_downloadable', 'no' );
										add_post_meta( $post_id, '_virtual', 'no' );
										add_post_meta( $post_id, '_regular_price', "$price" );
										add_post_meta( $post_id, '_sale_price', '' );
										add_post_meta( $post_id, '_tax_status', 'taxable' );
										add_post_meta( $post_id, '_tax_class', '' );
										add_post_meta( $post_id, '_visibility', 'visible' );
										add_post_meta( $post_id, '_purchase_note', '' );
										add_post_meta( $post_id, '_featured', 'no' );
										add_post_meta( $post_id, '_weight', "$weight" );
										add_post_meta( $post_id, '_length', "$length" );
										add_post_meta( $post_id, '_width', "$width" );
										add_post_meta( $post_id, '_height', "$height" );
										add_post_meta( $post_id, '_sku', "$sku" );
										add_post_meta( $post_id, '_variation_description', '' );
										add_post_meta( $post_id, '_sale_price_dates_from', '' );
										add_post_meta( $post_id, '_sale_price_dates_to', '' );
										add_post_meta( $post_id, '_price', "$price" );
										add_post_meta( $post_id, '_msrp', "$msrp" );
										add_post_meta( $post_id, '_sold_individually', '' );
										add_post_meta( $post_id, '_manage_stock', 'yes' );
										add_post_meta( $post_id, '_backorders', 'no' );
										add_post_meta( $post_id, '_stock_status', "$stock" );
										add_post_meta( $post_id, '_stock', "$qty_avail" );
										add_post_meta( $post_id, '_brand_name', "$post_brand" );
										add_post_meta( $post_id, '_supplier_name', "$supplier" );
										add_post_meta( $post_id, '_item_id', "$item_id" );
										add_post_meta( $post_id, '_prepay_price', "$prepay_price" );
										add_post_meta( $post_id, '_drop_ship_fee', "$drop_ship_fee" );

										$countcategories = count( $product->categories->category );
									$categories = $product->categories->category;
									$term_id = '';
									for ( $c = 1; $c < $countcategories; $c++ ) {
										$category = addslashes( $categories[$c]->name );

										$categoryslug = strtolower( $category );
										$slug = preg_replace( '/\_/', '-', $categoryslug );
										$slug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $slug );
										$slug = preg_replace( '/\s+/', '-', $slug );
										$slug = preg_replace( '/-+/', '-', $slug );


										$term = term_exists( "$category", 'product_cat' );

										if ( $term ) {
											$term_id[] = (int) $term['term_id'];
										} else {
											if ( $c == 1 ) {

												$cat = wp_insert_term(
														"$category", // the term 
														'product_cat', // the taxonomy
														array(
													'description' => '',
													'slug' => "$slug",
														)
												);


												$term_id[] = (int) $cat['term_id'];
											} else {
												$cat1 = addslashes($categories[$c - 1]->name);
//												echo "cat1".$cat1;
												$cat1lower = strtolower( $cat1 );
												$catslug = preg_replace( '/\_/', '-', $cat1lower );
												$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
												$catslug = preg_replace( '/\s+/', '-', $catslug );
												$catslug = preg_replace( '/-+/', '-', $catslug );
												$parent = 0;
												$parentterm = term_exists( "$cat1", 'product_cat' );
//												print_r($parentterm);
												if ( $parentterm ) {
													$parent = (int) $parentterm['term_id'];
												}
												$cat = wp_insert_term(
														"$category", // the term 
														'product_cat', // the taxonomy
														array(
													'description' => '',
													'slug' => "$slug",
													'parent' => "$parent"
														)
												);
												
												$term_id[] = (int) $cat['term_id'];
											}
										}
									}

									wp_set_object_terms( $post_id, $term_id, 'product_cat' );

										$image_url = $product->images->image;
										foreach ( $image_url as $image ) {
											$attach_id = uploadRemoteImageAndAttach( $image->url, $post_id );
											$product_gallery = $wpdb->get_results(  "select meta_value from {$wpdb->prefix}postmeta where post_id= $post_id and meta_key='_thumbnail_id'" , ARRAY_A );
											if ( $product_gallery[0]['meta_value'] ) {
												$gallery = get_post_meta( $post_id, '_product_image_gallery', TRUE );
												$gallery_post = $gallery . ',' . $attach_id;
												update_post_meta( $post_id, '_product_image_gallery', $gallery_post );
											} else {
												update_post_meta( $post_id, '_thumbnail_id', $attach_id );
												update_post_meta( $post_id, '_product_image_gallery', '' );
											}
										}

										$attributes = $items->attributes->attribute;


										foreach ( $attributes as $attribute ) {
											$wp_attribute = (string) $attribute->name;
											$wp_attrislug = sanitize_title( $wp_attribute );
											$wp_attriterm = (string) $attribute->value;
											$wp_attriterm = addslashes( $wp_attriterm );
											$wp_attriterm_slug = sanitize_title( $wp_attriterm );
											$attributename = "pa_$wp_attrislug";
											if ( !in_array( $wp_attrislug, $uniqueItems ) ) {
												$uniqueItems[] = $wp_attrislug;
											}
											$attritermid = '';
											$attritermtaxaid = '';
											$searchattribute = "Select * from {$wpdb->prefix}woocommerce_attribute_taxonomies where attribute_name = '{$wp_attrislug}'";
											$search = $wpdb->get_results(  $searchattribute  );
											if ( empty( $search ) ) {
												createAttribute( $wp_attribute, $wp_attrislug );
												$insertattriterm = "Insert into {$wpdb->prefix}terms (name, slug)"
														. " values ('{$wp_attriterm}','{$wp_attriterm_slug}')";
												$insert = $wpdb->query(  $insertattriterm  );
												if ( $insert ) {
													$attritermid = $wpdb->insert_id;
													$insertterm = "Insert into {$wpdb->prefix}termmeta (term_id, meta_key, meta_value)"
															. " values ('{$attritermid}','order_{$attributename}', 0)";
													$wpdb->query(  $insertterm ) ;
													$inserttermtaxa = "Insert into {$wpdb->prefix}term_taxonomy (term_id, taxonomy)"
															. " values ('{$attritermid}','{$attributename}')";
													$wpdb->query(  $inserttermtaxa  );
													$attritermtaxaid = $wpdb->insert_id;
													$wpdb->query(  "Insert into {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)"
																	. " values ('{$post_id}','{$attritermtaxaid}')"  );
												}
											} else {
												$searchterm = $wpdb->get_results(  "Select * from {$wpdb->prefix}terms where slug = '{$wp_attriterm_slug}'" , ARRAY_A );
												if ( !empty( $searchterm ) ) {
													$attritermid = $searchterm[0]['term_id'];
													$attritermtaxa = $wpdb->query(  "Select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where term_id={$attritermid}"  );
													foreach ( $attritermtaxa as $taxa ) {
														$attritermtaxaid = $taxa['term_taxonomy_id'];
														$wpdb->query(  "Insert into {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)"
																		. " values ('{$post_id}','{$attritermtaxaid}')"  );
													}
												} else {
													$insert_term = $wpdb->query(  "Insert into {$wpdb->prefix}terms (name, slug)"
																	. " values ('{$wp_attriterm}','{$wp_attrislug}')"  );
													if ( $insert_term ) {
														$attritermid = $wpdb->insert_id;
														$insertterm = "Insert into {$wpdb->prefix}termmeta (term_id, meta_key, meta_value)"
																. " values ('{$attritermid}','order_{$attributename}', 0)";
														$wpdb->query(  $insertterm  );
														$inserttermtaxa = "Insert into {$wpdb->prefix}term_taxonomy (term_id, taxonomy)"
																. " values ('{$attritermid}','{$attributename}')";
														$wpdb->query(  $inserttermtaxa );
														$attritermtaxaid = $wpdb->insert_id;
														$wpdb->query(  "Insert into {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)"
																		. " values ('{$post_id}','{$attritermtaxaid}')"  );
													}
												}
											}
											update_post_meta( $post_id, "_attribute_$attributename", $wp_attrislug );
										}
									}
								} else {
									$wpdb->show_errors();
								}
							}
						}
					}
				}
			}
		}
	}
}
