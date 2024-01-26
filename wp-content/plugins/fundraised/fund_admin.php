<?php
$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'total_fund';

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=fund_admin&tab=total_fund" class="nav-tab <?php echo $active_tab == 'total_fund' ? 'nav-tab-active' : ''; ?>">Total Fund</a>
    <a href="?page=fund_admin&tab=organization_fund" class="nav-tab <?php echo $active_tab == 'organization_fund' ? 'nav-tab-active' : ''; ?>">Organization Fund</a>
	<a href="?page=fund_admin&tab=individual_fund" class="nav-tab <?php echo $active_tab == 'individual_fund' ? 'nav-tab-active' : ''; ?>">Individual Fund</a>
</h2>
<?php
if ( $active_tab == 'total_fund' ) {

	table_display();
} elseif ( $active_tab == 'organization_fund' ) {

	table_org_display();
} elseif ( $active_tab == 'individual_fund' ) {
	table_indiv_display();
} else {
	echo 'Incorrect request';
}

function total_fund() {
	global $wpdb;
	$postid = array();
	$post = array();
	$total = 0;
	$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
	foreach ( $getorder as $orders ) {
		$post_id = $orders['ID'];
		$item = '';
		$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
		foreach ( $postmeta as $meta ) {
			$postid[$meta['meta_key']] = $meta['meta_value'];
		}
		$post[$post_id] = $postid;
		if ( !empty( $post[$post_id]['customer-id'] ) ) {
			$total += floatval( $post[$post_id]['_order_total'] );
		}
	}
	echo 'Total Fund raised: ' . $total;
}

function organization_fund() {
	global $wpdb;
	$postid = array();
	$post = array();
	$total = 0;
	$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
	foreach ( $getorder as $orders ) {
		$post_id = $orders['ID'];
		$item = '';
		$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
		foreach ( $postmeta as $meta ) {
			$postid[$meta['meta_key']] = $meta['meta_value'];
		}
		$post[$post_id] = $postid;
		if ( !empty( $post[$post_id]['customer-id'] ) ) {
			$usermeta = get_users( array( 'meta_key' => 'customerId', 'meta_value' => "{$post[$post_id]['customer-id']}" ) );
			$userid = $usermeta[0]->ID;
			$user_org = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='organization' && meta_value='yes' && user_id ='$userid'", ARRAY_A );
			$user_auth = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='auth' && meta_value='1' && user_id ='$userid'", ARRAY_A );
			if ( !empty( $user_org ) && !empty( $user_auth ) ) {
				$total += floatval( $post[$post_id]['_order_total'] );
			}
		}
	}
	echo 'Total Fund raised from organization: ' . $total;
}

function individual_fund() {
	global $wpdb;
	$postid = array();
	$post = array();
	$total = 0;
	$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
	foreach ( $getorder as $orders ) {
		$post_id = $orders['ID'];
		$item = '';
		$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
		foreach ( $postmeta as $meta ) {
			$postid[$meta['meta_key']] = $meta['meta_value'];
		}
		$post[$post_id] = $postid;
		if ( !empty( $post[$post_id]['customer-id'] ) ) {
			$usermeta = get_users( array( 'meta_key' => 'customerId', 'meta_value' => "{$post[$post_id]['customer-id']}" ) );
			$userid = $usermeta[0]->ID;
			$user_org = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='individual' && meta_value='yes' && user_id ='$userid'", ARRAY_A );
			$user_auth = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='auth' && meta_value='1' && user_id ='$userid'", ARRAY_A );
			if ( !empty( $user_org ) && !empty( $user_auth ) ) {
				$total += floatval( $post[$post_id]['_order_total'] );
			}
		}
	}
	echo 'Total Fund raised from Individual: ' . $total;
}

function table_display() {
	$exampleListTable = new Fund_Table();
	$exampleListTable->prepare_items();
	?>
	<div class="wrap">
		<div id="icon-users" class="icon32"></div>
		<h2>Table For Total Fund</h2>

		<?php total_fund(); ?>
		<?php $exampleListTable->display(); ?>
	</div>
	<?php
}

function table_org_display() {
	$exampleListTable = new Fund_Table();
	$exampleListTable->organization_items();
	?>
	<div class="wrap">
		<div id="icon-users" class="icon32"></div>
		<h2>Table For Total Fund</h2>

		<?php organization_fund(); ?>
		<?php $exampleListTable->display(); ?>
	</div>
	<?php
}

function table_indiv_display() {
	$exampleListTable = new Fund_Table();
	$exampleListTable->individual_items();
	?>
	<div class="wrap">
		<div id="icon-users" class="icon32"></div>
		<h2>Table For Total Fund</h2>

		<?php individual_fund(); ?>
		<?php $exampleListTable->display(); ?>
	</div>
	<?php
}

class Fund_Table extends WP_List_Table {

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data = $this->table_data();
		usort( $data, array( $this, 'usort_reorder3' ) );
		$perPage = 10;
		$currentPage = $this->get_pagenum();
		$totalItems = count( $data );

		$total_data = array_slice( $data, (($currentPage - 1) * $perPage ), $perPage );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $total_data;
		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page' => $perPage,
			'total_pages' => ceil( $totalItems / $perPage )
		) );
	}

	public function organization_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data = $this->org_data();
		usort( $data, array( $this, 'usort_reorder3' ) );
		$perPage = 10;
		$currentPage = $this->get_pagenum();
		$totalItems = count( $data );

		$total_data = array_slice( $data, (($currentPage - 1) * $perPage ), $perPage );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $total_data;
		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page' => $perPage,
			'total_pages' => ceil( $totalItems / $perPage )
		) );
	}

	public function individual_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$data = $this->indiv_data();
		usort( $data, array( $this, 'usort_reorder3' ) );
		$perPage = 10;
		$currentPage = $this->get_pagenum();
		$totalItems = count( $data );

		$total_data = array_slice( $data, (($currentPage - 1) * $perPage ), $perPage );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $total_data;
		$this->set_pagination_args( array(
			'total_items' => $totalItems,
			'per_page' => $perPage,
			'total_pages' => ceil( $totalItems / $perPage )
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	public function get_columns() {
		$columns = array(
			'order_id' => 'Order ID',
			'customer_name' => 'Customer Name',
			'org_name' => 'Funded For',
			'order_total' => 'Order Price',
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'order_id' => array( 'order_id', true ),
			'customer_name' => array( 'customer_name', true ),
			'org_name' => array( 'org_name', true ),
			'order_total' => array( 'order_total', true ),
		);
		return $sortable_columns;
	}

	protected function usort_reorder3( $a, $b ) {
		// If no sort, default to title.
		$orderby = !empty( $_REQUEST['orderby'] ) ? wp_unslash( $_REQUEST['orderby'] ) : 'title'; // WPCS: Input var ok.
		// If no order, default to asc.
		$order = !empty( $_REQUEST['order'] ) ? wp_unslash( $_REQUEST['order'] ) : 'asc'; // WPCS: Input var ok.
		// Determine sort order.
		$result = strcmp( $a[$orderby], $b[$orderby] );
		return ( 'asc' === $order ) ? $result : - $result;
	}

	public function table_data() {
		global $wpdb;
		$postid = array();
		$post = array();
		$total = 0;
		$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
		foreach ( $getorder as $orders ) {
			$post_id = $orders['ID'];
			$item = '';
			$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
			$postid['id'] = $post_id;
			foreach ( $postmeta as $meta ) {

				$postid[$meta['meta_key']] = $meta['meta_value'];
			}
			$post[$post_id] = $postid;
			if ( empty( $post[$post_id]['customer-id'] ) ) {
				unset( $post[$post_id] );
			}
			else {

				$total += floatval( $post[$post_id]['_order_total'] );
				$usermeta = get_users( array( 'meta_key' => 'customerId', 'meta_value' => "{$post[$post_id]['customer-id']}" ) );
				$userid = $usermeta[0]->ID;
				$user_orgname = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}usermeta where meta_key='org_name' && user_id ='$userid'", ARRAY_A );
				$post[$post_id]['org_name'] = $user_orgname[0]['meta_value'];
			}
		}
		$postid = array();
		foreach ( $post as $p ) {
			$postid[] = array(
				'order_id' => $p['id'],
				'customer_name' => $p['_billing_first_name'] . $p['_billing_last_name'],
				'order_total' => $p['_order_total'],
				'org_name' => $p['org_name']
			);
		}

		return $postid;
	}

	public function org_data() {
		global $wpdb;
		$postid = array();
		$post = array();
		$total = 0;
		$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
		foreach ( $getorder as $orders ) {
			$post_id = $orders['ID'];
			$item = '';
			$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
			$postid['id'] = $post_id;
			foreach ( $postmeta as $meta ) {

				$postid[$meta['meta_key']] = $meta['meta_value'];
			}
			$post[$post_id] = $postid;
			if ( empty( $post[$post_id]['customer-id'] ) ) {
				unset( $post[$post_id] );
			}
			else{

				$usermeta = get_users( array( 'meta_key' => 'customerId', 'meta_value' => "{$post[$post_id]['customer-id']}" ) );
				$userid = $usermeta[0]->ID;
				$user_org = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='organization' && meta_value='yes' && user_id ='$userid'", ARRAY_A );
				$user_auth = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='auth' && meta_value='1' && user_id ='$userid'", ARRAY_A );
				if ( !empty( $user_org ) && !empty( $user_auth ) ) {
					$total += floatval( $post[$post_id]['_order_total'] );
					$user_orgname = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}usermeta where meta_key='org_name' && user_id ='$userid'", ARRAY_A );
					$post[$post_id]['org_name'] = $user_orgname[0]['meta_value'];
				}else{
					unset( $post[$post_id] );
				}
			}
		}
		$postid = array();
		foreach ( $post as $p ) {
			$postid[] = array(
				'order_id' => $p['id'],
				'customer_name' => $p['_billing_first_name'] . $p['_billing_last_name'],
				'order_total' => $p['_order_total'],
				'org_name' => $p['org_name']
			);
		}

		return $postid;
	}

	public function indiv_data() {
		global $wpdb;
		$postid = array();
		$post = array();
		$total = 0;
		$getorder = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts where  post_type ='shop_order' && post_status = 'wc-completed'", ARRAY_A );
		foreach ( $getorder as $orders ) {
			$post_id = $orders['ID'];
			$item = '';
			$postmeta = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta where post_id ='$post_id'", ARRAY_A );
			$postid['id'] = $post_id;
			foreach ( $postmeta as $meta ) {

				$postid[$meta['meta_key']] = $meta['meta_value'];
			}
			$post[$post_id] = $postid;
			if ( empty( $post[$post_id]['customer-id'] ) ) {
				unset( $post[$post_id] );
			}
			else {

				$usermeta = get_users( array( 'meta_key' => 'customerId', 'meta_value' => "{$post[$post_id]['customer-id']}" ) );
				$userid = $usermeta[0]->ID;
				$user_org = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='individual' && meta_value='yes' && user_id ='$userid'", ARRAY_A );
				$user_auth = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}usermeta where meta_key='auth' && meta_value='1' && user_id ='$userid'", ARRAY_A );
				if ( !empty( $user_org ) && !empty( $user_auth ) ) {
					$total += floatval( $post[$post_id]['_order_total'] );
					$user_orgname = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}usermeta where meta_key='org_name' && user_id ='$userid'", ARRAY_A );
					$post[$post_id]['org_name'] = $user_orgname[0]['meta_value'];
				}else{
					unset( $post[$post_id] );
				}
			}
		}
		$postid = array();
		foreach ( $post as $p ) {
			$postid[] = array(
				'order_id' => $p['id'],
				'customer_name' => $p['_billing_first_name'] . $p['_billing_last_name'],
				'order_total' => $p['_order_total'],
				'org_name' => $p['org_name']
			);
		}
		
		return $postid;
	}

}
?>