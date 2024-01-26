<?php

function updateinventory() {
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
						$price_calc = floatval($product->price);
						$price = floatval(($price_calc * (($price_found + 100) / 100)));
						$stock = $product->stock;
						$qty_avail = $product->qty_avail;
						$visible = '';
						if ( $stock == 'in-stock' && $qty_avail > $threshold ) {
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
//							print_r( $post_id['post_id'] );
							foreach ( $post_id as $postid ) {
								$drop_ship = $wpdb->get_results( "select * from {$wpdb->prefix}postmeta where meta_key='_drop_ship_fee' and post_id = {$postid['post_id']} limit 1" , ARRAY_A );
//								print_r($drop_ship);
								$drop_ship_fee = $drop_ship[0]['meta_value'];
								$price = floatval($price + $drop_ship_fee);
								$postupdate = $wpdb->query(  "update {$wpdb->prefix}posts set post_status = '$visible' where ID = {$postid['post_id']}"  );

								$updateprice = $wpdb->query(  "Update {$wpdb->prefix}postmeta set meta_value='$msrp' where meta_key='_msrp' and post_id='{$postid['post_id']}'"  );
								$updateregular = $wpdb->query( "Update {$wpdb->prefix}postmeta set meta_value='$price' where meta_key='_regular_price' and post_id='{$postid['post_id']}'"  );
								$updatestatus = $wpdb->query( "Update {$wpdb->prefix}postmeta set meta_value='$stock' where meta_key='_stock_status' and post_id='{$postid['post_id']}'"  );
								$updateqty = $wpdb->query( "Update {$wpdb->prefix}postmeta set meta_value='$qty_avail' where meta_key='_stock' and post_id='{$postid['post_id']}'"  );
								$updateqty1 = $wpdb->query("Update {$wpdb->prefix}postmeta set meta_value='$price' where meta_key='_price' and post_id='{$postid['post_id']}'"  );
							}
						}
					}
				}
			}
		}
	}
}
