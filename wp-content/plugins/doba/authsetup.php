<?php
if ( !class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Doba_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'dobaauth',
			'plural' => 'dobaauths',
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function column_username( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &person=2
		$actions = array(
			'edit' => sprintf( '<a href="?page=doba_form&id=%s">%s</a>', $item['id'], __( 'Edit', 'doba_setup' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __( 'Delete', 'doba_setup' ) ),
		);

		return sprintf( '%s %s', $item['username'], $this->row_actions( $actions )
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
			'username' => __( 'Username', 'doba_setup' ),
			'retailer_id' => __( 'Retailer Id', 'doba_setup' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'username' => array( 'username', true ),
			'retailer_id' => array( 'retailer_id', false )
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete'
		);
		return $actions;
	}

	function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'doba_detail'; // do not forget about tables prefix

		if ( 'delete' === $this->current_action() ) {
			$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
			if ( is_array( $ids ) )
				$ids = implode( ',', $ids );

			if ( !empty( $ids ) ) {
				$wpdb->query( "DELETE FROM $table_name WHERE id IN($ids)" );
			}
		}
	}

	function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'doba_detail'; // do not forget about tables prefix

		$per_page = 5; // constant, how much records will be shown per page

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// here we configure table headers, defined in our methods
		$this->_column_headers = array( $columns, $hidden, $sortable );

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
		$sql = "SELECT * FROM {$wpdb->prefix}doba_detail";

		if ( !empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .=!empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . $paged;
		$this->items = $wpdb->get_results( $sql, ARRAY_A );

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page' => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}

}

function doba_table_display() {

	global $wpdb;

	$table = new Doba_Table();

	$table->prepare_items();

	$message = '';
	if ( 'delete' === $table->current_action() ) {
		$ids = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : array();
		$count=0;
		if ( is_array( $ids ) ){
			$count = count( $ids );
		}else{
			$count = isset( $_REQUEST['id'] ) ? 1 : 0;
		}
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'doba_setup' ), $count ) . '</p></div>';
	}
	?>
	<div class="wrap">

	    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	    <h2><?php _e( 'Doba Auth Setup', 'doba_setup' ) ?> <a class="add-new-h2"
														   href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=doba_form' ); ?>"><?php _e( 'Add new', 'doba_setup' ) ?></a>
	    </h2>
	<?php echo $message; ?>

	    <form id="doba-table" method="GET">
	        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
	<?php $table->display() ?>
	    </form>

	</div>
	<?php
}

function doba_table_form_page_handler() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'doba_detail'; // do not forget about tables prefix

	$message = '';
	$notice = '';

	// this is default $item which will be used for new records
	$default = array(
		'id' => 0,
		'username' => '',
		'password' => '',
		'retailer_id' => '',
	);

	// here we are verifying does this request is post back and have correct nonce
	if ( wp_verify_nonce( $_REQUEST['nonce'], basename( __FILE__ ) ) ) {
		// combine our default item with request params
		$item = shortcode_atts( $default, $_REQUEST );
		// validate data, and if all ok save item to database
		// if id is zero insert otherwise update
		$item_valid = validate_doba( $item );
		if ( $item_valid === true ) {
			if ( $item['id'] == 0 ) {
				$result = $wpdb->insert( $table_name, $item );
				$item['id'] = $wpdb->insert_id;
				if ( $result ) {
					$message = __( 'Item was successfully saved', 'doba_setup' );
				} else {
					$notice = __( 'There was an error while saving item', 'doba_setup' );
				}
			} else {
				$result = $wpdb->update( $table_name, $item, array( 'id' => $item['id'] ) );
				if ( $result ) {
					$message = __( 'Item was successfully updated', 'doba_setup' );
				} else {
					$notice = __( 'There was an error while updating item', 'doba_setup' );
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
				$notice = __( 'Item not found', 'doba_setup' );
			}
		}
	}

	// here we adding our custom meta box
	add_meta_box( 'doba_form_meta_box', 'Doba Auth data', 'doba_table_form_meta_box_handler', 'doba', 'normal', 'default' );
	?>
	<div class="wrap">
	    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	    <h2><?php _e( 'Doba Auth data', 'doba_setup' ) ?> <a class="add-new-h2"
														  href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=authsetup' ); ?>"><?php _e( 'back to list', 'doba_setup' ) ?></a>
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
	                    <input type="submit" value="<?php _e( 'Save', 'doba_setup' ) ?>" id="submit" class="button-primary" name="submit">
	                </div>
	            </div>
	        </div>
	    </form>
	</div>
	<?php
}

function doba_table_form_meta_box_handler( $item ) {
	?>

	<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
	    <tbody>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="username"><?php _e( 'Username', 'doba_setup' ) ?></label>
				</th>
				<td>
					<input id="username" name="username" type="text" style="width: 95%" value="<?php echo esc_attr( $item['username'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Your Username', 'doba_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="password"><?php _e( 'Password', 'doba_setup' ) ?></label>
				</th>
				<td>
					<input id="password" name="password" type="password" style="width: 95%" value="<?php echo esc_attr( $item['password'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Your Password', 'doba_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="retailer_id"><?php _e( 'Retailer Id', 'custom_table_example' ) ?></label>
				</th>
				<td>
					<input id="retailer_id" name="retailer_id" type="number" style="width: 95%" value="<?php echo esc_attr( $item['retailer_id'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Your Retailer-Id', 'doba_setup' ) ?>" required>
				</td>
			</tr>
	    </tbody>
	</table>
	<?php
}

function validate_doba( $item ) {
	$messages = array();

	if ( empty( $item['username'] ) )
		$messages[] = __( 'Username is required', 'doba_setup' );
	if ( empty( $item['password'] ) )
		$messages[] = __( 'Password is required', 'doba_setup' );
	if ( !is_numeric( $item['retailer_id'] ) )
		$messages[] = __( 'Retailer Id in wrong format', 'doba_setup' );


	if ( empty( $messages ) )
		return true;
	return implode( '<br />', $messages );
}
