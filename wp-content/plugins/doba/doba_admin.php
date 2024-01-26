<?php

function check_credential() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'doba_detail';
	$sql = "Select * from $table_name";
	$result = $wpdb->get_results( $sql, ARRAY_A );
	if ( $result ) {

		$username = $result[0]['username'];
		$password = $result[0]['password'];
		$retailer = $result[0]['retailer_id'];


		doba_list_table_display();
	} else {
		?>
		<div class="wrap">
			<h1>Welcome user,</h1>
			<p>You need to first add authentication detail over <a href="<?= get_admin_url( get_current_blog_id(), 'admin.php?page=doba_form' ) ?>">here</a></p>
		</div>
		<?php
	}
}

function requestDoba( $request, $username, $password, $retailer ) {

	$URL = "https://www.doba.com/api/20110301/xml_retailer_api.php";
	$strRequest = "
<dce>
  <request>
    <authentication>
      <username>$username</username>
      <password>$password</password>
    </authentication>
    <retailer_id>$retailer</retailer_id>
    <action>$request</action>
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
		// print "Curl error: " . curl_error($connection);
	} else {
		$info = curl_getinfo( $connection );
		// print "HTTP Response Code = " . $info["http_code"] . "\n";
	}
	curl_close( $connection );
	$res = new SimpleXMLElement( $strResponse );
	return $res;
}

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Doba_List_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'dobalist',
			'plural' => 'dobalists',
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function column_id( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &person=2
		$actions = array(
			'edit' => sprintf( '<a href="?page=doba_edit&id=%s">%s</a>', $item['id'], __( 'Edit', 'doba_setup' ) ),
			'sync' => sprintf( '<a href="?page=sync_item&id=%s">%s</a>', $item['id'], __( 'Sync', 'doba_setup' ) ),
			'view' => sprintf( '<a href="?page=view_item&id=%s">%s</a>', $item['id'], __( 'View', 'doba_setup' ) ),
		);

		return sprintf( '%s %s', $item['id'], $this->row_actions( $actions )
		);
	}

	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="id[]" value="%s" />', $item['id']
		);
	}

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'id' => __( 'List-Id', 'doba_list' ),
			'name' => __( 'List Name', 'doba_list' ),
			'item_count' => __( 'Item Count', 'doba_list' ),
			'price_setting' => __( 'Price Setting', 'doba_list' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', true ),
			'name' => array( 'name', true ),
			'item_count' => array( 'item_count', false ),
			'price_setting' => array( 'item_count', false )
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
			'sync' => 'Sync'
		);
		return $actions;
	}

	function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'doba_list'; // do not forget about tables prefix

		if ( 'delete' === $this->current_action() ) {
			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
			if ( is_array( $ids ) )
				$ids = implode( ',', $ids );

			if ( !empty( $ids ) ) {
				$wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
			}
		}
		if ( 'sync' === $this->current_action() ) {
			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
			if ( is_array( $ids ) )
				$ids = implode( ',', $ids );

			if ( !empty( $ids ) ) {
				sync_item( $ids );
			}
		}
		
	}

	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'doba_list'; // do not forget about tables prefix

		$per_page = 10; // constant, how much records will be shown per page

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// [OPTIONAL] process bulk action if any
		$this->process_bulk_action();
		$currentPage = $this->get_pagenum();
		// will be used in pagination settings
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

		// prepare query params, as usual current page, order by and order direction
		//$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		//$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
		//$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		$sql = "SELECT * FROM {$wpdb->prefix}doba_list";

//		if ( !empty( $_REQUEST['orderby'] ) ) {
//			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//			$sql .=!empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//		}
//
//		$sql .= " LIMIT $per_page";
//		$sql .= ' OFFSET ' . $paged;
		$data = $wpdb->get_results( $sql , ARRAY_A );

		usort( $data, array( $this, 'usort_reorder' ) );
		$total_data = array_slice( $data, (($currentPage - 1) * $per_page ), $per_page );
		$this->items = $total_data;

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page' => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}

	protected function usort_reorder( $a, $b ) {
		// If no sort, default to title.
		$orderby = !empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'title'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = !empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strcmp( $a[$orderby], $b[$orderby] );
		return ( 'asc' === $order ) ? $result : - $result;
	}

}

function doba_list_table_display() {

	global $wpdb;

	$table = new Doba_List_Table();

	$table->prepare_items();

	$message = '';
	if ( 'delete' === $table->current_action() ) {
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'doba_list' ), count( $_REQUEST['id'] ) ) . '</p></div>';
	}
	?>
	<div class="wrap">

		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba List', 'doba_list' ) ?> <a class="add-new-h2"
													   href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=update_list' ); ?>"><?php _e( 'Update List', 'doba_list' ) ?></a>
		</h2>
		<?php echo $message; ?>

		<form id="doba-table" method="GET">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
			<?php $table->display() ?>
		</form>

	</div>
	<?php
}

function doba_list_update() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'doba_detail';
	$select = "Select * from $table_name";
	$result = $wpdb->get_results(  $select , ARRAY_A );
	$username = $result[0]['username'];
	$password = $result[0]['password'];
	$retailer = $result[0]['retailer_id'];

	$table = $wpdb->prefix . "doba_list";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $table (
        `list_id` int(11) NOT NULL AUTO_INCREMENT,
        `default_list` tinyint(1) NOT NULL,
		`id` int(11) NOT NULL,
		`item_count` int(11) NOT NULL,
		`name` varchar(32) NOT NULL,
		`send_callback` tinyint(1) NOT NULL,
		`price_setting` float(9,2),
    PRIMARY KEY (`list_id`),
	UNIQUE KEY `id` (`id`)
    ) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	$response = requestDoba( 'getListsSummary', $username, $password, $retailer );
	$count = count( $response->response->list );
	for ( $i = 0; $i < $count; $i++ ) {
		$default = $response->response->list[$i]->default;
		$id = $response->response->list[$i]->id;
		$item_count = $response->response->list[$i]->item_count;
		$name = addslashes( $response->response->list[$i]->name );
		$send_callback = $response->response->list[$i]->send_callback;
		$insert = "INSERT INTO {$wpdb->prefix}doba_list(default_list,id,item_count,name,send_callback) values"
				. "($default,$id,$item_count,'$name',$send_callback) ON DUPLICATE KEY UPDATE item_count=$item_count , "
				. "name = '$name' , default_list= $default , send_callback= $send_callback";

		$wpdb->get_results(  $insert  );
	}
	?>
	<div class="wrap">

		<p>Your list is updated <a class="add-new-h2" href="<?= get_admin_url( get_current_blog_id(), 'admin.php?page=doba_admin' ) ?>">back to list</a></p>
	</div>
	<?php
}
function doba_sync(){
	$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
	sync_item($ids);
}
function sync_item( $id ) {
	$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
	if ( is_array( $ids ) )
		$ids = implode( ',', $ids );

	if ( !empty( $ids ) ) {
		if ( strpos( $ids, ',' ) !== false ) {
			$id = explode( ',', $ids );
			foreach ( $id as $i ) {
				request_sync_item( $i );
			}
		} else {
			request_sync_item( $ids );
		}
	}
}

function view_item( ) {
	$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '';


	if ( !empty( $ids ) ) {

		display_sync_table( $ids );
	}
}

function request_sync_item( $list_id ) {

	global $wpdb;
	$list_table = $wpdb->prefix . "doba_list";
	$price_sql = "Select * from $list_table where id = $list_id";
	$price_result = $wpdb->get_results( $price_sql , ARRAY_A );
	$price_found = $price_result[0]['price_setting'];
	//$threshold = $price_result[0]['threshold_value'];

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
		$item_count = $wpdb->get_results(  "select item_count from {$wpdb->prefix}doba_list where id = $list_id" , ARRAY_A );
		$count = $item_count[0]['item_count'];
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
									add_post_meta( $post_id, '_drop_ship_fee', "$drop_ship_fee" );

									$countcategories = count( $product->categories->category );
									$categories = $product->categories->category;
									$term_id = array();
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
										$product_gallery = $wpdb->get_results( "select meta_value from {$wpdb->prefix}postmeta where post_id= $post_id and meta_key='_thumbnail_id'" , ARRAY_A );
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
												$wpdb->query( "Insert into {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)"
																. " values ('{$post_id}','{$attritermtaxaid}')"  );
											}
										} else {
											$searchterm = $wpdb->get_results(  "Select * from {$wpdb->prefix}terms where slug = '{$wp_attriterm_slug}'" , ARRAY_A );
											if ( !empty( $searchterm ) ) {
												$attritermid = $searchterm[0]['term_id'];
												$attritermtaxa = $wpdb->query(  "Select term_taxonomy_id from {$wpdb->prefix}term_taxonomy where term_id={$attritermid}"  );
												foreach ( $attritermtaxa as $taxa ) {
													$attritermtaxaid = $taxa['term_taxonomy_id'];
													$wpdb->query( "Insert into {$wpdb->prefix}term_relationships (object_id, term_taxonomy_id)"
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
	} else {
		?>
		<div class="wrap">

			<p>You need to first add Price detail</p>
		</div>
		<?php
	}
	display_sync_table( $list_id );
}

function uploadRemoteImageAndAttach( $image_url, $parent_id ) {

	$image = $image_url;

	$get = wp_remote_get( $image );


	$type = wp_remote_retrieve_header( $get, 'content-type' );

	if ( !$type )
		return false;

	$body = wp_remote_retrieve_body( $get );

	$mirror = wp_upload_bits( basename( $image ), null, $body );

	$attachment = array(
		'post_title' => basename( $image ),
		'post_mime_type' => $type
	);

	$attach_id = wp_insert_attachment( $attachment, $mirror['file'], $parent_id );

	require_once(ABSPATH . 'wp-admin/includes/image.php');

	$attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );

	wp_update_attachment_metadata( $attach_id, $attach_data );

	return $attach_id;
}

function createAttribute( $lable, $name = '', $type = 'select', $orderby = 'menu_order' ) {
	global $wpdb;
	// Grab the submitted data
	$attribute_label = stripslashes( $lable );
	$attribute_name = $name;
	$attribute_type = 'select';
	$attribute_orderby = 'menu_order';
	// Auto-generate the label or slug if only one of both was provided
	if ( !$attribute_label ) {
		$attribute_label = ucfirst( $attribute_name );
	}
	if ( !$attribute_name ) {
		$attribute_name = wc_sanitize_taxonomy_name( stripslashes( $attribute_label ) );
	}
	$reserved_terms = array( 'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and', 'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage', 'day', 'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name', 'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence', 'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year' );
	if ( in_array( $attribute_name, $reserved_terms ) ) {
		$error = sprintf( __( 'Slug â€œ%sâ€� is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) );
	}
	$attribute = array( 'attribute_label' => $attribute_label, 'attribute_name' => $attribute_name, 'attribute_type' => $attribute_type, 'attribute_orderby' => $attribute_orderby );
	$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
	do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );
	doba_register_new_attribute( $attribute_label, $attribute_name );
//	$action_completed = true;
//	$transient_name = 'wc_attribute_taxonomies';
//	$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );
//	set_transient( $transient_name, $attribute_taxonomies );
}

function doba_register_new_attribute( $label, $attribute_name ) {
	$permalinks = get_option( 'woocommerce_permalinks' );

	$taxonomy_data = array(
		'hierarchical' => true,
		'update_count_callback' => '_update_post_term_count',
		'labels' => array(
			'name' => "$label",
			'singular_name' => "$label",
			'search_items' => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
			'all_items' => sprintf( __( 'All %s', 'woocommerce' ), $label ),
			'parent_item' => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
			'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
			'edit_item' => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
			'update_item' => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
			'add_new_item' => sprintf( __( 'Add New %s', 'woocommerce' ), $label ),
			'new_item_name' => sprintf( __( 'New %s', 'woocommerce' ), $label )
		),
		'show_ui' => false,
		'query_var' => true,
		'rewrite' => array(
			'slug' => empty( $permalinks['attribute_base'] ) ? '' : trailingslashit( $permalinks['attribute_base'] ) . $attribute_name,
			'with_front' => false,
			'hierarchical' => true
		),
		'sort' => false,
		'public' => true,
		'show_in_nav_menus' => false,
		'capabilities' => array(
			'manage_terms' => 'manage_product_terms',
			'edit_terms' => 'edit_product_terms',
			'delete_terms' => 'delete_product_terms',
			'assign_terms' => 'assign_product_terms',
		)
	);

	register_taxonomy( "pa_$attribute_name", array( 'product' ), $taxonomy_data );
}

function display_sync_table( $list_id ) {
	global $wpdb;

	$table = new Doba_Item_Table( $list_id );

	$table->prepare_items();

	$message = '';
	if ( 'delete' === $table->current_action() ) {
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'doba_list' ), count( $_REQUEST['id'] ) ) . '</p></div>';
	}
	?>
	<div class="wrap">

		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba Items', 'doba_items' ) ?> <a class="add-new-h2"
														 href="<?php //echo get_admin_url( get_current_blog_id(), 'admin.php?page=update_list' );       ?>"><?php _e( 'Update List', 'doba_list' ) ?></a>
		</h2>
		<?php echo $message; ?>

		<form id="doba-table" method="GET">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
			<?php $table->display() ?>
		</form>

	</div>
	<?php
}

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Doba_Item_Table extends WP_List_Table {

	public $list_id;

	function __construct( $list_id ) {
		global $status, $page;
		$this->list_id = $list_id;
		parent::__construct( array(
			'singular' => 'dobaitem',
			'plural' => 'dobaitems',
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function column_item_id( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &person=2
		global $wpdb;
		$item_id = $item['item_id'];
		$item_result = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_key = '_item_id' and meta_value = $item_id", ARRAY_A );
		$post_id = $item_result[0]['post_id'];
		$url = get_permalink( $post_id );

		$actions = array(
			'view' => sprintf( '<a href="%s">%s</a>', $url, __( 'View', 'doba_setup' ) ),
		);

		return sprintf( '%s %s', $item['item_id'], $this->row_actions( $actions )
		);
	}

	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="id[]" value="%s" />', $item['id']
		);
	}

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'item_id' => __( 'Item-Id', 'doba_list' ),
			'name' => __( 'Name', 'doba_list' ),
			'price' => __( 'Price', 'doba_list' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'item_id' => array( 'item_id', true ),
			'name' => array( 'name', false ),
			'price' => array( 'price', false ),
		);
		return $sortable_columns;
	}

	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'doba_items'; // do not forget about tables prefix

		$per_page = 5; // constant, how much records will be shown per page

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$currentPage = $this->get_pagenum();
		// [OPTIONAL] process bulk action if any
		$this->process_bulk_action();

		// will be used in pagination settings
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name where list_id = $this->list_id" );

		// prepare query params, as usual current page, order by and order direction
		$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		//$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
		//$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		$sql = "SELECT * FROM {$wpdb->prefix}doba_items where list_id = $this->list_id";

//		if ( !empty( $_REQUEST['orderby'] ) ) {
//			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//			$sql .=!empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//		}

		$data = $wpdb->get_results( $sql , ARRAY_A );

		usort( $data, array( $this, 'usort_reorder1' ) );
		$total_data = array_slice( $data, (($currentPage - 1) * $per_page ), $per_page );
		$this->items = $total_data;

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page' => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}
	protected function usort_reorder1( $a, $b ) {
		// If no sort, default to title.
		$orderby = !empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'title'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = !empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strcmp( $a[$orderby], $b[$orderby] );
		return ( 'asc' === $order ) ? $result : - $result;
	}

}

function doba_price_edit_table_form_page_handler() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'doba_list'; // do not forget about tables prefix

	$message = '';
	$notice = '';
	$default = array(
		'default_list' => '',
		'id' => '',
		'item_count' => '',
		'name' => '',
		'send_callback' => '',
		'price_setting' => '',
	);
	if ( wp_verify_nonce( $_REQUEST['nonce'], basename( __FILE__ ) ) ) {
		$item = shortcode_atts( $default, $_REQUEST );
		$item_valid = validate_list_price_doba( $item );
		if ( $item_valid === true ) {
			if ( $item ) {
				if ( $item['id'] != 0 ) {
					$result = $wpdb->update( $table_name, $item, array( 'id' => $item['id'] ) );
					if ( $result ) {
						$message = __( 'List was successfully updated', 'doba_price_setup' );
					} else {
						$notice = __( 'There was an error while updating list', 'doba_price_setup' );
					}
				}
			}
		} else {
			// if $item_valid not true it contains error message(s)
			$notice = $item_valid;
		}
	} else {
		// if this is not post back we load item to edit or give new one to create
		$item = $default;
		if ( isset( $_REQUEST['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id'] ), ARRAY_A );
			if ( !$item ) {
				$item = $default;
				$notice = __( 'Item not found', 'doba_price_setup' );
			}
		}
	}
	add_meta_box( 'doba_price_list_form_meta_box', 'Doba List data', 'doba_price_list_table_form_meta_box_handler', 'doba', 'normal', 'default' );
	?>
	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba List data', 'doba_list__setup' ) ?> <a class="add-new-h2"
																   href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=doba_admin' ); ?>"><?php _e( 'back to list', 'doba_price_setup' ) ?></a>
		</h2>
		<?php if ( !empty( $notice ) ): ?>
			<div id="notice" class="error"><p><?php echo $notice ?></p></div>
		<?php endif; ?>
		<?php if ( !empty( $message ) ): ?>
			<div id="message" class="updated"><p><?php echo $message ?></p></div>
		<?php endif; ?>
		<form id="form" method="POST">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
			<?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
			<input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

			<div class="metabox-holder" id="poststuff">
				<div id="post-body">
					<div id="post-body-content">
						<?php /* And here we call our custom meta box */ ?>
						<?php do_meta_boxes( 'doba', 'normal', $item ); ?>
						<input type="submit" value="<?php _e( 'Save', 'doba_list_setup' ) ?>" id="submit" class="button-primary" name="submit">
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
}

function doba_price_list_table_form_meta_box_handler( $item ) {
	?>
	<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
		<tbody>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="list_id"><?php _e( 'List id', 'doba_price_setup' ) ?></label>
				</th>
				<td>
					<input id="list_id" name="id" type="text" style="width: 95%" value="<?php echo esc_attr( $item['id'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'List Id', 'doba_list_setup' ) ?>" >
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="default_list"><?php _e( 'Default List', 'doba_price_setup' ) ?></label>
				</th>
				<td>
					<input id="default_list" name="default_list" type="text" style="width: 95%" value="<?php echo esc_attr( $item['default_list'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Default List', 'doba_list_setup' ) ?>" >
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="item_count"><?php _e( 'Item_count', 'doba_price_setup' ) ?></label>
				</th>
				<td>
					<input id="item_count" name="item_count" type="text" style="width: 95%" value="<?php echo esc_attr( $item['item_count'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Item Count', 'doba_list_setup' ) ?>" >
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="name"><?php _e( 'Name', 'doba_list_setup' ) ?></label>
				</th>
				<td>
					<input id="name" name="name" type="text" style="width: 95%" value="<?php echo esc_attr( $item['name'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Name', 'doba_list_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="send_callback"><?php _e( 'Send Callback', 'doba_list_setup' ) ?></label>
				</th>
				<td>
					<input id="send_callback" name="send_callback" type="text" style="width: 95%" value="<?php echo esc_attr( $item['send_callback'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Send Callback', 'doba_list_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="price_setting"><?php _e( 'Price Setting', 'doba_list_setup' ) ?></label>
				</th>
				<td>
					<input id="price_setting" name="price_setting" type="text" style="width: 95%" value="<?php echo esc_attr( $item['price_setting'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Price Setting', 'doba_list_setup' ) ?>" required>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

function validate_list_price_doba( $item ) {
	$messages = array();
	if ( empty( $item['price_setting'] ) )
		$messages[] = __( 'Price is required, use only numbers', 'doba_price_setup' );
	if ( !is_numeric( $item['price_setting'] ) )
		$messages[] = __( 'Price is in wrong format,please use only numbers', 'doba_price_setup' );
	if ( empty( $messages ) )
		return true;
	return implode( '<br />', $messages );
}
