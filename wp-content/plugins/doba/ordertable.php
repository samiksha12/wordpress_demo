<?php

function doba_place_order() {
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
				if ( empty( $post[$post_id]['_billing_first_name'] ) ) {
					$first_name = substr($post[$post_id]['_shipping_first_name'],0,20);
				} else {
					$first_name = substr($post[$post_id]['_billing_first_name'],0,20);
				}
				if ( empty( $post[$post_id]['_billing_first_name'] ) ) {
					$last_name = substr($post[$post_id]['_shipping_last_name'],0,20);
				} else {
					$last_name = substr($post[$post_id]['_billing_last_name'],0,20);
				}
				if ( empty( $post[$post_id]['_billing_address1'] ) ) {
					$street = substr($post[$post_id]['_shipping_address1'],0,20);
				} else {
					$street = substr($post[$post_id]['_billing_address1'],0,20);
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

function doba_track_order() {
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
		$result = $wpdb->get_results( $select , ARRAY_A );
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
			$wpdb->get_results(  "Insert into $track_order SET carrier={$carrier}, tracking = {$tracking} , shipment_date = {$shipment_date}, doba_order={$order_id}" );
			$wpdb->get_results(  "Update {$wpdb->prefix}order_table set paid_order = 1 where doba_order= {$order_id}" );
		}
	}
}


class Doba_Order_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'dobaorder',
			'plural' => 'dobaorders',
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
//		$actions = array(
//			'sync' => sprintf( '<a href="?page=sync_item&id=%s">%s</a>', $item['id'], __( 'Sync', 'doba_setup' ) ),
//			'view' => sprintf( '<a href="?page=view_item&id=%s">%s</a>', $item['id'], __( 'View', 'doba_setup' ) ),
//		);

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
			'doba_order' => __( 'Doba Order', 'doba_order' ),
			'wp_order' => __( 'Order', 'doba_order' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id' => array( 'id', true ),
			'wp_order' => array( 'wp_order', false ),
			'doba_order' => array( 'doba_order', false )
		);
		return $sortable_columns;
	}

//	function get_bulk_actions() {
//		$actions = array(
//			'delete' => 'Delete',
//			'sync' => 'Sync'
//		);
//		return $actions;
//	}

//	function process_bulk_action() {
//		global $wpdb;
//		$table_name = $wpdb->prefix . 'doba_detail'; // do not forget about tables prefix
//
//		if ( 'delete' === $this->current_action() ) {
//			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
//			if ( is_array( $ids ) )
//				$ids = implode( ',', $ids );
//
//			if ( !empty( $ids ) ) {
//				$wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
//			}
//		}
//		if ( 'sync' === $this->current_action() ) {
//			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
//			if ( is_array( $ids ) )
//				$ids = implode( ',', $ids );
//
//			if ( !empty( $ids ) ) {
//				sync_item( $ids );
//			}
//		}
//	}

	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'order_table'; // do not forget about tables prefix

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
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

		// prepare query params, as usual current page, order by and order direction
		$paged = isset( $_REQUEST['paged'] ) ? max( 0, intval( $_REQUEST['paged'] ) - 1 ) : 0;
		//$orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'name';
		//$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
		// [REQUIRED] define $items array
		// notice that last argument is ARRAY_A, so we will retrieve array
		$sql = "SELECT * FROM {$wpdb->prefix}order_table";

//		if ( !empty( $_REQUEST['orderby'] ) ) {
//			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
//			$sql .=!empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
//		}
//
//		$sql .= " LIMIT $per_page";
//		$sql .= ' OFFSET ' . $paged;
		$data = $wpdb->get_results( $sql , ARRAY_A );

		usort( $data, array( $this, 'usort_reorder2' ) );
		$total_data = array_slice( $data, (($currentPage - 1) * $per_page ), $per_page );
		$this->items = $total_data;

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page' => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}
	protected function usort_reorder2( $a, $b ) {
		// If no sort, default to title.
		$orderby = !empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'title'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = !empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strcmp( $a[$orderby], $b[$orderby] );
		return ( 'asc' === $order ) ? $result : - $result;
	}

}

function doba_order_table_display() {

	global $wpdb;

	$table = new Doba_Order_Table();

	$table->prepare_items();

	$message = '';
	if ( 'delete' === $table->current_action() ) {
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'doba_order' ), count( $_REQUEST['id'] ) ) . '</p></div>';
	}
	?>
	<div class="wrap">

		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba Order', 'doba_order' ) ?> <a class="add-new-h2"
													   href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=create_order' ); ?>"><?php _e( 'Create Order', 'doba_list' ) ?></a>
		</h2>
		<?php echo $message; ?>

		<form id="doba-table" method="GET">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
			<?php $table->display() ?>
		</form>

	</div>
	<?php
}