<?php

$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
$strRequest = "
<dce>
  <request>
    <authentication>
      <username>goventura2</username>
      <password>welcome1234</password>
    </authentication>
    <retailer_id>5387384</retailer_id>
    <action>getProductDetail</action>
    <items>
      <item>35020808</item>
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
	print "HTTP Response Code = " . $info["http_code"] . "\n";
}
curl_close( $connection );
//print_r($strResponse);
$res = new SimpleXMLElement( $strResponse );
$allitems = $res->response->products;
foreach ( $allitems as $products ) {
	foreach ( $products->product as $product ) {
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
				print_r($term_id);
			} else {
				if ( $c == 1 ) {
//					$cat = wp_insert_term(
//							"$category", // the term 
//							'product_cat', // the taxonomy
//							array(
//						'description' => '',
//						'slug' => "$slug",
//							)
//					);
//
//
//					$term_id[] = (int) $cat['term_id'];
				} else {
					$cat1 = $categories[$c - 1]->name;
					$cat1 = addslashes( $cat );
					$cat1 = strtolower( $cat );
					$catslug = preg_replace( '/\_/', '-', $cat1 );
					$catslug = preg_replace( "/[^A-Za-z 0-9?!\-]/", '', $catslug );
					$catslug = preg_replace( '/\s+/', '-', $catslug );
					$catslug = preg_replace( '/-+/', '-', $catslug );
					$parent = '';
					$parentterm = term_exists( "$catslug", 'product_cat' );
					print_r($parentterm);
					if ( $parentterm ) {
						$parent = (int)$parentterm['term_id'];
						
					}
//					$cat = wp_insert_term(
//							"$category", // the term 
//							'product_cat', // the taxonomy
//							array(
//						'description' => '',
//						'slug' => "$slug",
//						'parent' => "$parent"
//							)
//					);
					
				}
			}
		}

//		wp_set_object_terms( $post_id, $term_id, 'product_cat' );
	}
}
