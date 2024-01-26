<?php
ini_set( 'display_errors', 1 );

function schedule_sync_item() {
	require_once 'doba_admin.php';
	global $wpdb;
	$price_table = $wpdb->prefix . "price_table";
	$price_sql = "Select * from $price_table where is_active = 1";
	$price_result = $wpdb->get_results(  $price_sql , ARRAY_A );
	$price_found = $price_result[0]['price_percent'];
	$threshold = $price_result[0]['threshold_value'];

	if ( $price_found ) {

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
							$drop_ship_fee = $product->supplier->drop_ship_fee;
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
							$price_calc = $items->price;
							$price_per = $price_found / 100;
							$price = ($price_calc * $price_per) + $price_calc;
							$price = $price + $drop_ship_fee;
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

												$cat = wp_insert_term(
														"$category", // the term 
														'product_cat', // the taxonomy
														array(
													'description' => '',
													'slug' => "$slug",
														)
												);


												$term_id[] = (int) $cat['term_id'];
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
													$wpdb->query(  $insertterm  );
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
														$wpdb->query(  $inserttermtaxa  );
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
	} else {
		?>
		<div class="wrap">

			<p>You need to first add Price detail and activate it. You can add it over here <a href="<?= get_admin_url( get_current_blog_id(), 'admin.php?page=doba_price' ) ?>">here</a></p>
		</div>
		<?php
	}
}
