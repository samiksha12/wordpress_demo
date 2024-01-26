<?php

$servername = "db.maskeyconsultancy.com";
$username = "mcs3i";
$password = "dbs@mcs@dream";
$dbname = "maskeytest";

// Create connection
$conn = new mysqli( $servername, $username, $password, $dbname );
// Check connection
if ( $conn->connect_error ) {
	die( "Connection failed: " . $conn->connect_error );
}
$parent_id = array();
//getting the product using list_id from category table
$getList = $conn->query( "select list_id from wpbd_category" );
while ( $listrow = $getList->fetch_assoc() ) {
	$listid = $listrow['list_id'];
	$getCount = "select item_count from wpbd_category where list_id='$listid'";
	$getResult = $conn->query( $getCount );
	$k = '';
	while ( $row1 = $getResult->fetch_assoc() ) {
		$k .=$row1['item_count'];
	}
	$icount = ceil( $k / 10 );


	$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
	for ( $a = 1; $a <= $icount; $a++ ) {
		$strRequest = "
<dce>
  <request>
    <authentication>
      <username>info@caesarpay.com</username>
      <password>Tlwuvpdl123$$$</password>
    </authentication>
    <retailer_id>5400679</retailer_id>
    <action>getProductDetail</action>
	<limit>10</limit>
	<page>$a</page>
    	<list_ids>
      <list_ids>$listid</list_ids>
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
			print "=======================\n";
		}
		curl_close( $connection );

		$res = new SimpleXMLElement( $strResponse );
		$productid = $res->response->products->product;
		for ( $p = 0; $p < count( $productid ); $p++ ) {
			$product_id = $productid[$p]->product_id;
			$strRequest1 = "
<dce>
  <request>
    <authentication>
      <username>info@caesarpay.com</username>
      <password>Tlwuvpdl123$$$</password>
    </authentication>
    <retailer_id>5400679</retailer_id>
    <action>getProductDetail</action>
    <products>
      <product>{$product_id}</product>
    </products>
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
				print "HTTP Response Code = " . $info["http_code"] . "\n";
			}
			curl_close( $connection1 );

			$res1 = new SimpleXMLElement( $strResponse1 );
			$currentdate = date( "Y-m-d H:i:s" );
			$gmdate = gmdate( "Y-m-d H:i:s" );
			$allitems = $res1->response->products;
			foreach ( $allitems as $products ) {
				foreach ( $products->product as $product ) {
					$uniqueItems = array();
					$post_content = $product->description;
					$post_additional = $product->additional_details;
					$content = $post_content . $post_additional;
					$posts_content = str_replace( "\"", "&quot;", $content );
					$post_contents = addslashes( $posts_content );
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
					$postname = preg_replace( '/\_/', '-', $postsname );
					$postname = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $postname );
					$postname = preg_replace( '/\s+/', '-', $postname );
					$postname = preg_replace( '/-+/', '-', $postname );
					$price = floatval( $product->items->item->price );
					$weight = $product->ship_weight;
					$length = $product->ship_length;
					$width = $product->ship_width;
					$height = $product->ship_height;

					$wholesale = $product->items->item->price;
					$count = count( $product->items->item );
					$stockstatus = $product->items->item->stock;
					if ( $stockstatus == 'in-stock' ) {
						$stockstatus = 'instock';
					}
					if ( $stockstatus == 'out-of-stock' ) {
						$stockstatus = 'outofstock';
					}
					if ( $stockstatus == 'discontinued' ) {
						$stockstatus = 'outofstock';
					}
					$supplier = $product->supplier->name;
					$supplier = str_replace( "\"", "&quot;", $supplier );
					$supplier = addslashes( $supplier );
					$stock = $product->items->item->qty_avail;
					$item_id = $product->items->item->item_id;
					$image_url = $product->images;
					$guid = "http://localhost/caesar/index.php/product/$postname/";
					$searchunique = $conn->query( "Select * from wpbd_posts where unique_identifier='$item_id'" );
					if ( $searchunique->num_rows > 0 ) {
						continue;
					} else {
						if ( $count > 1 ) {

							$insertgroup = ("INSERT INTO wpbd_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count,unique_identifier)
      VALUES ('1', '$currentdate', '$gmdate', '$post_contents', '$title', '','publish', 'open', 'closed','','$postname', '', '', '$currentdate','$gmdate','', '0', '$guid', '', 'product','','0','0')");
							$query = $conn->query( $insertgroup );
							if ( $query == TRUE ) {
								$last_id = $conn->insert_id;
								$sql4 = "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values "
										. "('$last_id', 'total_sales', '0'),"
										. "('$last_id', '_downloadable', 'no'),"
										. "('$last_id', '_virtual', 'no'),"
										. "('$last_id', '_regular_price', '$price'),"
										. "('$last_id', '_sale_price', ''),"
										. "('$last_id', '_tax_status', 'taxable'),"
										. "('$last_id', '_tax_class', ''),"
										. "('$last_id', '_visibility', 'visible'),"
										. "('$last_id', '_purchase_note', ''),"
										. "('$last_id', '_featured', 'no'),"
										. "('$last_id', '_weight', '$weight'),"
										. "('$last_id', '_length', '$length'),"
										. "('$last_id', '_width', '$width'),"
										. "('$last_id', '_height', '$height'),"
										. "('$last_id', '_sku', '$sku'),"
										. "('$last_id', '_variation_description', ''),"
										. "('$last_id', '_product_attributes', ''),"
										. "('$last_id', '_default_attributes', ''),"
										. "('$last_id', '_sale_price_dates_from', ''),"
										. "('$last_id', '_sale_price_dates_to', ''),"
										. "('$last_id', '_price', '$price'),"
										. "('$last_id', '_sold_individually', ''),"
										. "('$last_id', '_manage_stock', 'no'),"
										. "('$last_id', '_backorders', 'no'),"
										. "('$last_id', '_stock_status', '$stockstatus'),"
										. "('$last_id', '_stock', ''),"
										. "('$last_id', '_thumbnail_id', ''),"
										. "('$last_id', '_product_image_gallery', ''),"
										. "('$last_id', '_product_version', ''),"
										. "('$last_id', '_whole_sale_price', '$wholesale'),"
										. "('$last_id', '_doba_productid', '$product_id'),"
										. "('$last_id', '_brand_name', '$post_brand'),"
										. "('$last_id', '_supplier_name', '$supplier'),"
										. "('$last_id', '_item_id', '$item_id');";
								$query1 = $conn->query( $sql4 );
								foreach ( $image_url as $image ) {
									foreach ( $image->image as $images ) {
										foreach ( $images->url as $url ) {
											$insertimage = $conn->query( "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values ('$last_id','_wp_attached_file','$url')" );
										}
									}
								}
								$countcategories = count( $product->categories->category );
								$categories = $product->categories->category;
								for ( $c = 1; $c < $countcategories; $c++ ) {
									$category = $categories[$c]->name;
									$category = addslashes( $category );
									$categoryslug = strtolower( $category );
									$slug = preg_replace( '/\_/', '-', $categoryslug );
									$slug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $slug );
									$slug = preg_replace( '/\s+/', '-', $slug );
									$slug = preg_replace( '/-+/', '-', $slug );

									$searchcategory = $conn->query( "select * from wpbd_terms where slug = '$slug'" );
									if ( $searchcategory->num_rows > 0 ) {
										while ( $categorysearched = $searchcategory->fetch_assoc() ) {
											$searchtermtaxa = $conn->query( "Select term_taxonomy_id from wpbd_term_taxonomy where term_id={$categorysearched['term_id']} and taxonomy='product_cat'" );
											if ( $searchtermtaxa->num_rows > 0 ) {
												while ( $texasearched = $searchtermtaxa->fetch_assoc() ) {
													$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','" . $texasearched['term_taxonomy_id'] . "','0')" );
												}
											} else {
												$insertedtaxaid = '';
												$insertcategory = $conn->query( "Insert into wpbd_terms(name, slug) values ('$category','$slug')" );
												$insertedid = $conn->insert_id;
												$insertterm = $conn->query( "Insert into wpbd_termmeta(term_id, meta_key, meta_value) values"
														. "('{$insertedid}','order','0'),"
														. "('{$insertedid}','display_type',''),"
														. "('{$insertedid}','thumbnail_id','0');" );
												if ( $c == 1 ) {
													$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy) values ('$insertedid','product_cat')" );
													$insertedtaxaid = $conn->insert_id;
												} else {
													$cat = $categories[$c - 1]->name;
													$cat = addslashes( $cat );
													$cat = strtolower( $cat );
													$catslug = preg_replace( '/\_/', '-', $cat );
													$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
													$catslug = preg_replace( '/\s+/', '-', $catslug );
													$catslug = preg_replace( '/-+/', '-', $catslug );
													$parent = '';
													$parentterm = $conn->query( "SELECT * FROM wpbd_terms join wpbd_term_taxonomy on wpbd_terms.term_id = wpbd_term_taxonomy.term_id where wpbd_terms.slug = '$catslug' and wpbd_term_taxonomy.taxonomy = 'product_cat'" );
													while ( $parenttermid = $parentterm->fetch_assoc() ) {
														$parent = $parenttermid['term_id'];
													}
													$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy,parent) values ('$insertedid','product_cat','{$parent}')" );
													$insertedtaxaid = $conn->insert_id;
												}
												$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','$insertedtaxaid','0')" );
											}
										}
									} else {
										$insertedtaxaid = '';
										$insertcategory = $conn->query( "Insert into wpbd_terms(name, slug) values ('$category','$slug')" );
										$insertedid = $conn->insert_id;
										$insertterm = $conn->query( "Insert into wpbd_termmeta(term_id, meta_key, meta_value) values"
												. "('{$insertedid}','order','0'),"
												. "('{$insertedid}','display_type',''),"
												. "('{$insertedid}','thumbnail_id','0');" );
										if ( $c == 1 ) {
											$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy) values ('$insertedid','product_cat')" );
											$insertedtaxaid = $conn->insert_id;
										} else {
											$cat = $categories[$c - 1]->name;
											$cat = addslashes( $cat );
											$cat = strtolower( $cat );
											$catslug = preg_replace( '/\_/', '-', $cat );
											$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
											$catslug = preg_replace( '/\s+/', '-', $catslug );
											$catslug = preg_replace( '/-+/', '-', $catslug );
											$parent = '';
											$parentterm = $conn->query( "SELECT * FROM wpbd_terms join wpbd_term_taxonomy on wpbd_terms.term_id = wpbd_term_taxonomy.term_id where wpbd_terms.slug = '$catslug' and wpbd_term_taxonomy.taxonomy = 'product_cat'" );
											while ( $parenttermid = $parentterm->fetch_assoc() ) {
												$parent = $parenttermid['term_id'];
											}
											$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy,parent) values ('$insertedid','product_cat','{$parent}')" );
											$insertedtaxaid = $conn->insert_id;
										}
										$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','$insertedtaxaid','0')" );
									}
								}//category added
								$variation = array();
								$rawvariation = array();
								$uniqueItems = array();


								$ID = $last_id;
								$searchvariable = $conn->query( "Select term_id from wpbd_terms where slug='variable'" );

								while ( $rowsearched = $searchvariable->fetch_assoc() ) {
									$searchtaxavariable = $conn->query( "Select term_taxonomy_id from wpbd_term_taxonomy where term_id={$rowsearched['term_id']}" );

									while ( $rowsearchedtaxa = $searchtaxavariable->fetch_assoc() ) {
										$inserttermvariable = "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
												. " values ('{$ID}','{$rowsearchedtaxa['term_taxonomy_id']}')";
										$conn->query( $inserttermvariable );
									}
								}

								$items = $product->items->item;
								$i = 1;
								for ( $it = 0; $it < $count; $it++ ) {
									$attributes = $items[$it]->attributes->attribute;
									$variant_id = $items[$it]->item_id;
									$itemnames = $items[$it]->name;
									$itemnames = addslashes( $itemnames );
									$itemname = $items[$it]->name;
									$itemname = strtolower( $itemname );
									$itemname = preg_replace( '/\_/', '-', $itemname );
									$itemname = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $itemname );
									$itemname = preg_replace( '/\s+/', '-', $itemname );
									$itemname = preg_replace( '/-+/', '-', $itemname );
									$searchname = $conn->query( "Select * from wpbd_woocommerce_attribute_taxonomies where attribute_name = 'name'" );
									if ( $searchname->num_rows > 0 ) {


										$searched = $conn->query( "Select * from wpbd_terms where slug = '$itemname'" );
										if ( $searched->num_rows > 0 ) {
											while ( $rowsearch = $searched->fetch_assoc() ) {
												$attritermid = $rowsearch['term_id'];
												$attritermtaxa = $conn->query( "Select term_taxonomy_id from wpbd_term_taxonomy where term_id={$attritermid}" );
												if ( $attritermtaxa->num_rows > 0 ) {
													while ( $rowsearchterm = $attritermtaxa->fetch_assoc() ) {
														$attritermtaxaid = $rowsearchterm['term_taxonomy_id'];
														$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
																. " values ('{$ID}','{$attritermtaxaid}')" );
													}
												} else {
													$inserttermtaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
															. " values ('{$attritermid}','pa_{$itemname}')" );
													$attritermtaxaid = $conn->insert_id;
													$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
															. " values ('{$ID}','{$attritermtaxaid}')" );
												}
											}
										} else {
											$insertattriterm = "Insert into wpbd_terms (name, slug)"
													. " values ('{$itemnames}','{$itemname}')";
											$inserted = $conn->query( $insertattriterm );

											if ( $inserted == TRUE ) {
												$attritermid = $conn->insert_id;
												$inserttermmeta = $conn->query( "Insert into wpbd_termmeta (term_id, meta_key, meta_value)"
														. " values ('{$attritermid}','order_pa_name', 0)" );
												$inserttermtaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
														. " values ('{$attritermid}','pa_name')" );
												$attritermtaxaid = $conn->insert_id;
												$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
														. " values ('{$ID}','{$attritermtaxaid}')" );
											} else {
												echo "Error: " . $insertattriterm . "<br>" . $conn->error;
											}
										}
									} else {
										$insertattribute = "Insert into wpbd_woocommerce_attribute_taxonomies (attribute_name, attribute_label, attribute_type, attribute_orderby, attribute_public)"
												. " values ('name','Name','select','menu_order', 0 )";
										$inserted = $conn->query( $insertattribute );

										if ( $inserted == TRUE ) {
											$insertattriterm = "Insert into wpbd_terms (name, slug)"
													. " values ('{$itemnames}','{$itemname}')";
											$insertedterm = $conn->query( $insertattriterm );

											if ( $insertedterm == TRUE ) {
												$attritermid = $conn->insert_id;
												$inserttermmeta = "Insert into wpbd_termmeta (term_id, meta_key, meta_value)"
														. " values ('{$attritermid}','order_pa_name', 0)";
												$insertedtermmeta = $conn->query( $inserttermmeta );
												$inserttermtaxa = "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
														. " values ('{$attritermid}','pa_name')";
												$insertedtermtaxa = $conn->query( $inserttermtaxa );
												$attritermtaxaid = $conn->insert_id;
												$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
														. " values ('{$ID}','{$attritermtaxaid}')" );
											} else {
												echo "Error: " . $insertattriterm . "<br>" . $conn->error;
											}
										} else {
											echo "Error: " . $insertattribute . "<br>" . $conn->error;
										}
									}




									$key = array_search( $itemname, array_column( $uniqueItems, 'name' ) );
									if ( is_numeric( $key ) ) {
										
									} else {
										if ( empty( $uniqueItems ) ) {
											$uniqueItems[] = array( "name" => $itemname, "variation" => "1", "slug" => "pa_name" );
										}
									}
									$lastID = '';
									$getlastID = $conn->query( "Select ID from wpbd_posts order by ID desc limit 1" );

									while ( $rowID = $getlastID->fetch_assoc() ) {
										$lastId = $rowID['ID'];
										$lastID = $lastId + 1;
									}


									$variant_title = "Variation #{$lastID} of {$title}";
									if ( $i == 1 ) {
										$variant_name = "product-$ID-variation";
									} else {
										$variant_name = "product-$ID-variation-$i";
									}
									$postinserted = '';
									$postinsert = $conn->query( "INSERT INTO wpbd_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count, unique_identifier)
      VALUES ('1', '$currentdate', '$gmdate', '', '$variant_title', '','publish', 'open', 'closed','','$variant_name', '', '', '$currentdate','$gmdate','', '$ID', '$guid', '1', 'product_variation','','0', '$variant_id')" );
									$postinserted = $conn->insert_id;
									foreach ( $attributes as $attribute ) {
										$wp_attribute = $attribute->name;
										$wp_attriterm = $attribute->value;
										$wp_attribute = strtolower( $wp_attribute );
										$wp_attribute_term = preg_replace( '/\_/', '-', $wp_attribute );
										$wp_attribute_term = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $wp_attribute_term );
										$wp_attribute_term = preg_replace( '/\s+/', '-', $wp_attribute_term );
										$wp_attribute_term = preg_replace( '/-+/', '-', $wp_attribute_term );
										$wp_attriterm = strtolower( $wp_attriterm );
										$wp_attriterm_term = preg_replace( '/\_/', '-', $wp_attriterm );
										$wp_attriterm_term = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $wp_attriterm_term );
										$wp_attriterm_term = preg_replace( '/\s+/', '-', $wp_attriterm_term );
										$wp_attriterm_term = preg_replace( '/-+/', '-', $wp_attriterm_term );

										$key = array_search( $wp_attribute_term, array_column( $uniqueItems, 'name' ) );
										if ( is_numeric( $key ) ) {
											
										} else {
											$uniqueItems[] = array( "name" => $wp_attribute_term, "variation" => "0", "slug" => "pa_$wp_attribute_term" );
										}

										$attritermid = '';
										$attritermtaxaid = '';
										$searchattribute = "Select * from wpbd_woocommerce_attribute_taxonomies where attribute_name = '{$wp_attribute_term}'";
										$search = $conn->query( $searchattribute );
										if ( $search->num_rows > 0 ) {
											$searchterm = "Select * from wpbd_terms where slug = '{$wp_attriterm_term}'";
											$searched = $conn->query( $searchterm );
											if ( $searched->num_rows > 0 ) {
												while ( $rowsearch = $searched->fetch_assoc() ) {
													$attritermid = $rowsearch['term_id'];
													$attritermtaxa = $conn->query( "Select term_taxonomy_id from wpbd_term_taxonomy where term_id={$attritermid}" );
													if ( $attritermtaxa->num_rows > 0 ) {
														while ( $rowsearchterm = $attritermtaxa->fetch_assoc() ) {
															$attritermtaxaid = $rowsearchterm['term_taxonomy_id'];
															$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
																	. " values ('{$ID}','{$attritermtaxaid}')" );
														}
													} else {
														$inserttermtaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
																. " values ('{$attritermid}','pa_{$wp_attribute_term}')" );
														$attritermtaxaid = $conn->insert_id;
														$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
																. " values ('{$ID}','{$attritermtaxaid}')" );
													}
												}
											} else {
												$insertattriterm = "Insert into wpbd_terms (name, slug)"
														. " values ('{$wp_attriterm}','{$wp_attriterm_term}')";
												$inserted = $conn->query( $insertattriterm );

												if ( $inserted == TRUE ) {
													$attritermid = $conn->insert_id;
													$inserttermmeta = $conn->query( "Insert into wpbd_termmeta (term_id, meta_key, meta_value)"
															. " values ('{$attritermid}','order_pa_{$wp_attribute_term}', 0)" );
													$inserttermtaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
															. " values ('{$attritermid}','pa_{$wp_attribute_term}')" );
													$attritermtaxaid = $conn->insert_id;
													$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
															. " values ('{$ID}','{$attritermtaxaid}')" );
												} else {
													echo "Error: " . $insertattriterm . "<br>" . $conn->error;
												}
											}
										} else {
											$insertattribute = "Insert into wpbd_woocommerce_attribute_taxonomies (attribute_name, attribute_label, attribute_type, attribute_orderby, attribute_public)"
													. " values ('{$wp_attribute_term}','{$wp_attribute}','select','menu_order', 0 )";
											$inserted = $conn->query( $insertattribute );

											if ( $inserted == TRUE ) {
												$insertattriterm = "Insert into wpbd_terms (name, slug)"
														. " values ('{$wp_attriterm}','{$wp_attriterm_term}')";
												$insertedterm = $conn->query( $insertattriterm );

												if ( $insertedterm == TRUE ) {
													$attritermid = $conn->insert_id;
													$inserttermmeta = "Insert into wpbd_termmeta (term_id, meta_key, meta_value)"
															. " values ('{$attritermid}','order_pa_{$wp_attribute_term}', 0)";
													$insertedtermmeta = $conn->query( $inserttermmeta );
													$inserttermtaxa = "Insert into wpbd_term_taxonomy (term_id, taxonomy)"
															. " values ('{$attritermid}','pa_{$wp_attribute_term}')";
													$insertedtermtaxa = $conn->query( $inserttermtaxa );
													$attritermtaxaid = $conn->insert_id;
													$attrirelation = $conn->query( "Insert into wpbd_term_relationships (object_id, term_taxonomy_id)"
															. " values ('{$ID}','{$attritermtaxaid}')" );
												} else {
													echo "Error: " . $insertattriterm . "<br>" . $conn->error;
												}
											} else {
												echo "Error: " . $insertattribute . "<br>" . $conn->error;
											}
										}
										$raw = $conn->query( "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values ('$postinserted','attribute_pa_$wp_attribute_term','')" );
									}
									$raw1 = $conn->query( "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values ('$postinserted','attribute_pa_name','$itemname')" );
									$stockstatus = $items[$it]->stock;
									if ( $stockstatus == 'in-stock' ) {
										$stockstatus = 'instock';
									}
									if ( $stockstatus == 'out-of-stock' ) {
										$stockstatus = 'outofstock';
									}
									if ( $stockstatus == 'discontinued' ) {
										$stockstatus = 'outofstock';
									}
									$price = floatval( $items[$it]->price );
									$stock = $items[$it]->qty_avail;
									$insertpostmeta = "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values "
											. "('$postinserted', 'total_sales', '0'),"
											. "('$postinserted', '_downloadable', 'no'),"
											. "('$postinserted', '_virtual', 'no'),"
											. "('$postinserted', '_regular_price', '$price'),"
											. "('$postinserted', '_sale_price', ''),"
											. "('$postinserted', '_tax_status', 'taxable'),"
											. "('$postinserted', '_tax_class', ''),"
											. "('$postinserted', '_visibility', 'visible'),"
											. "('$postinserted', '_purchase_note', ''),"
											. "('$postinserted', '_featured', 'no'),"
											. "('$postinserted', '_weight', '$weight'),"
											. "('$postinserted', '_length', '$length'),"
											. "('$postinserted', '_width', '$width'),"
											. "('$postinserted', '_height', '$height'),"
											. "('$postinserted', '_sku', '$sku'),"
											. "('$postinserted', '_variation_description', ''),"
											. "('$postinserted', '_sale_price_dates_from', ''),"
											. "('$postinserted', '_sale_price_dates_to', ''),"
											. "('$postinserted', '_price', '$price'),"
											. "('$postinserted', '_sold_individually', ''),"
											. "('$postinserted', '_manage_stock', 'yes'),"
											. "('$postinserted', '_backorders', 'no'),"
											. "('$postinserted', '_stock_status', '$stockstatus'),"
											. "('$postinserted', '_stock', '$stock'),"
											. "('$postinserted', '_thumbnail_id', ''),"
											. "('$postinserted', '_product_image_gallery', ''),"
											. "('$postinserted', '_product_version', ''),"
											. "('$postinserted', '_whole_sale_price', '$wholesale'),"
											. "('$postinserted', '_doba_productid', '$product_id'),"
											. "('$postinserted', '_brand_name', '$post_brand'),"
											. "('$postinserted', '_supplier_name', '$supplier'),"
											. "('$postinserted', '_item_id', '$variant_id');";
									$insertedpostmeta = $conn->query( $insertpostmeta );
								}//variant added
								$countvar = count( $uniqueItems );
								$product_attribute = "a:$countvar:{";
								for ( $u = 0; $u < $countvar; $u++ ) {

									$countvariation = strlen( $uniqueItems[$u]['slug'] );
									$str = $uniqueItems[$u]['slug'];
									$product_attribute .= "s:$countvariation:\"$str\";";
									$product_attribute .= "a:6:{";
									$product_attribute .= "s:4:\"name\";s:$countvariation:\"$str\";";
									$product_attribute .= "s:5:\"value\";s:0:\"\";";
									$product_attribute .= "s:8:\"position\";s:1:\"0\";";
									$product_attribute .= "s:10:\"is_visible\";i:1;";
									if ( $uniqueItems[$u]['variation'] == 0 ) {
										$product_attribute .= "s:12:\"is_variation\";i:0;";
									} else {
										$product_attribute .= "s:12:\"is_variation\";i:1;";
									}
									$product_attribute .= "s:11:\"is_taxonomy\";i:1;";
									$product_attribute .= "}";
								}
								$product_attribute .= "}";
								$updatepostmeta = $conn->query( "Update wpbd_postmeta set meta_value = '{$product_attribute}' where meta_key='_product_attributes' and post_id={$last_id}" );
								$uniqueItems = array();
							} else {
								echo "Error: " . $insertgroup . "<br>" . $conn->error;
							}
						} else {
							$sql3 = ("INSERT INTO wpbd_posts (post_author, post_date, post_date_gmt, post_content, post_title, post_excerpt, post_status, comment_status, ping_status, post_password, post_name, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count, unique_identifier)
      VALUES ('1', '$currentdate', '$gmdate', '$post_contents', '$title', '','publish', 'open', 'closed','','$postname', '', '', '$currentdate','$gmdate','', '0', '$guid', '', 'product','','0','$item_id')");
							$query = $conn->query( $sql3 );
							if ( $query == TRUE ) {
								$last_id = $conn->insert_id;
								$sql4 = "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values "
										. "('$last_id', 'total_sales', '0'),"
										. "('$last_id', '_downloadable', 'no'),"
										. "('$last_id', '_virtual', 'no'),"
										. "('$last_id', '_regular_price', '$price'),"
										. "('$last_id', '_sale_price', ''),"
										. "('$last_id', '_tax_status', 'taxable'),"
										. "('$last_id', '_tax_class', ''),"
										. "('$last_id', '_visibility', 'visible'),"
										. "('$last_id', '_purchase_note', ''),"
										. "('$last_id', '_featured', 'no'),"
										. "('$last_id', '_weight', '$weight'),"
										. "('$last_id', '_length', '$length'),"
										. "('$last_id', '_width', '$width'),"
										. "('$last_id', '_height', '$height'),"
										. "('$last_id', '_sku', '$sku'),"
										. "('$last_id', '_variation_description', ''),"
										. "('$last_id', '_product_attributes', ''),"
										. "('$last_id', '_default_attributes', ''),"
										. "('$last_id', '_sale_price_dates_from', ''),"
										. "('$last_id', '_sale_price_dates_to', ''),"
										. "('$last_id', '_price', '$price'),"
										. "('$last_id', '_sold_individually', ''),"
										. "('$last_id', '_manage_stock', 'yes'),"
										. "('$last_id', '_backorders', 'no'),"
										. "('$last_id', '_stock_status', '$stockstatus'),"
										. "('$last_id', '_stock', '$stock'),"
										. "('$last_id', '_thumbnail_id', ''),"
										. "('$last_id', '_product_image_gallery', ''),"
										. "('$last_id', '_product_version', ''),"
										. "('$last_id', '_whole_sale_price', '$wholesale'),"
										. "('$last_id', '_doba_productid', '$product_id'),"
										. "('$last_id', '_brand_name', '$post_brand'),"
										. "('$last_id', '_supplier_name', '$supplier'),"
										. "('$last_id', '_item_id', '$item_id');";
								$query1 = $conn->query( $sql4 );
								foreach ( $image_url as $image ) {
									foreach ( $image->image as $images ) {
										foreach ( $images->url as $url ) {

											$insertimage = $conn->query( "INSERT INTO wpbd_postmeta (post_id, meta_key, meta_value) values ('$last_id','_wp_attached_file','$url')" );
										}
									}
								}
								$countcategories = count( $product->categories->category );
								$categories = $product->categories->category;
								for ( $c = 1; $c < $countcategories; $c++ ) {
									$category = $categories[$c]->name;
									$category = addslashes( $category );
									$categoryslug = strtolower( $category );
									$slug = preg_replace( '/\_/', '-', $categoryslug );
									$slug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $slug );
									$slug = preg_replace( '/\s+/', '-', $slug );
									$slug = preg_replace( '/-+/', '-', $slug );

									$searchcategory = $conn->query( "select * from wpbd_terms where slug = '$slug'" );
									if ( $searchcategory->num_rows > 0 ) {
										while ( $categorysearched = $searchcategory->fetch_assoc() ) {
											$searchtermtaxa = $conn->query( "Select term_taxonomy_id from wpbd_term_taxonomy where term_id={$categorysearched['term_id']} and taxonomy='product_cat'" );
											if ( $searchtermtaxa->num_rows > 0 ) {
												while ( $texasearched = $searchtermtaxa->fetch_assoc() ) {
													$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','" . $texasearched['term_taxonomy_id'] . "','0')" );
												}
											} else {
												$insertedtaxaid = '';
												$insertcategory = $conn->query( "Insert into wpbd_terms(name, slug) values ('$category','$slug')" );
												$insertedid = $conn->insert_id;
												$insertterm = $conn->query( "Insert into wpbd_termmeta(term_id, meta_key, meta_value) values"
														. "('{$insertedid}','order','0'),"
														. "('{$insertedid}','display_type',''),"
														. "('{$insertedid}','thumbnail_id','0');" );
												if ( $c == 1 ) {
													$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy) values ('$insertedid','product_cat')" );
													$insertedtaxaid = $conn->insert_id;
												} else {
													$cat = $categories[$c - 1]->name;
													$cat = addslashes( $cat );
													$cat = strtolower( $cat );
													$catslug = preg_replace( '/\_/', '-', $cat );
													$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
													$catslug = preg_replace( '/\s+/', '-', $catslug );
													$catslug = preg_replace( '/-+/', '-', $catslug );
													$parent = '';
													$parentterm = $conn->query( "SELECT * FROM wpbd_terms join wpbd_term_taxonomy on wpbd_terms.term_id = wpbd_term_taxonomy.term_id where wpbd_terms.slug = '$catslug' and wpbd_term_taxonomy.taxonomy = 'product_cat'" );
													while ( $parenttermid = $parentterm->fetch_assoc() ) {
														$parent = $parenttermid['term_id'];
													}
													$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy,parent) values ('$insertedid','product_cat','{$parent}')" );
													$insertedtaxaid = $conn->insert_id;
												}
												$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','$insertedtaxaid','0')" );
											}
										}
									} else {
										$insertedtaxaid = '';
										$insertcategory = $conn->query( "Insert into wpbd_terms(name, slug) values ('$category','$slug')" );
										$insertedid = $conn->insert_id;
										$insertterm = $conn->query( "Insert into wpbd_termmeta(term_id, meta_key, meta_value) values"
												. "('{$insertedid}','order','0'),"
												. "('{$insertedid}','display_type',''),"
												. "('{$insertedid}','thumbnail_id','0');" );
										if ( $c == 1 ) {
											$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy) values ('$insertedid','product_cat')" );
											$insertedtaxaid = $conn->insert_id;
										} else {
											$cat = $categories[$c - 1]->name;
											$cat = addslashes( $cat );
											$cat = strtolower( $cat );
											$catslug = preg_replace( '/\_/', '-', $cat );
											$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
											$catslug = preg_replace( '/\s+/', '-', $catslug );
											$catslug = preg_replace( '/-+/', '-', $catslug );
											$parent = '';
											$parentterm = $conn->query( "SELECT * FROM wpbd_terms join wpbd_term_taxonomy on wpbd_terms.term_id = wpbd_term_taxonomy.term_id where wpbd_terms.slug = '$catslug' and wpbd_term_taxonomy.taxonomy = 'product_cat'" );
											while ( $parenttermid = $parentterm->fetch_assoc() ) {
												$parent = $parenttermid['term_id'];
											}
											$inserttaxa = $conn->query( "Insert into wpbd_term_taxonomy (term_id, taxonomy,parent) values ('$insertedid','product_cat','{$parent}')" );
											$insertedtaxaid = $conn->insert_id;
										}
										$sql6 = $conn->query( "Insert into wpbd_term_relationships(object_id , term_taxonomy_id, term_order) values ('$last_id','$insertedtaxaid','0')" );
									}
								}

							} else {
								echo "Error: " . $sql3 . "<br>" . $conn->error;
							}
						}
					}
				}
			}
		}
	}
}
