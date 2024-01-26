<?php

function create_price_table() {
	global $wpdb;
	$table = $wpdb->prefix . "price_table";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $table (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `price_percent` int(11) NOT NULL,
		`threshold_value` int(11) NOT NULL,
		`is_active` tinyint(1) NOT NULL,
    PRIMARY KEY (`id`)
    ) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Doba_Price_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'price',
			'plural' => 'prices',
		) );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function column_price_percent( $item ) {
		// links going to /admin.php?page=[your_plugin_page][&other_params]
		// notice how we used $_REQUEST['page'], so action will be done on curren page
		// also notice how we use $this->_args['singular'] so in this example it will
		// be something like &person=2
		$actions = array(
			'edit' => sprintf( '<a href="?page=doba_price&id=%s">%s</a>', $item['id'], __( 'Edit', 'doba_price_setup' ) ),
			'delete' => sprintf( '<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __( 'Delete', 'doba_price_setup' ) ),
		);

		return sprintf( '%s %s', $item['price_percent'], $this->row_actions( $actions )
		);
	}

	function column_cb( $item ) {
		return sprintf(
				'<input type="checkbox" name="id[]" value="%s" />', $item['id']
		);
	}
	function column_is_active($item){
		$active = '';
		if ($item['is_active'] == 1){
			$active = "Active";
		}
		if ($item['is_active'] == 0){
			$active = "Inactive";
		}
		return sprintf('%s',$active);
	}

	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
			'price_percent' => __( 'Price', 'doba_price_setup' ),
			'threshold_value' => __( 'Threshold value', 'doba_price_setup' ),
			'is_active' => __( 'Active', 'doba_price_setup' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'price_percent' => array( 'price_percent', true ),
			'threshold_value' => array( 'threshold_value', true ),
			'is_active' => array( 'is_active', false )
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
		$table_name = $wpdb->prefix . 'price_table'; // do not forget about tables prefix

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
		$table_name = $wpdb->prefix . 'price_table'; // do not forget about tables prefix

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
		$sql = "SELECT * FROM $table_name";

		if ( !empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .=!empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . $paged;
		$this->items = $wpdb->get_results(  $sql , ARRAY_A );

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page' => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}

}

function doba_price_table_display() {
	global $wpdb;

	$table = new Doba_Price_Table();
	$table->prepare_items();
	$message = '';
	if ( 'delete' === $table->current_action() ) {
		$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'doba_price_setup' ), count( $_REQUEST['id'] ) ) . '</p></div>';
	}
	?>
	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba Price Setup', 'doba_price_setup' ) ?> <a class="add-new-h2"
																	 href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=doba_price' ); ?>"><?php _e( 'Add new', 'doba_price_setup' ) ?></a>
		</h2>
		<?php echo $message; ?>
		<form id="doba-price-table" method="GET">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
			<?php $table->display() ?>
		</form>
	</div>

	<?php
}

function doba_price_table_form_page_handler() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'price_table'; // do not forget about tables prefix

	$message = '';
	$notice = '';
	$default = array(
		'id' => 0,
		'price_percent' => '',
		'threshold_value' => '',
		'is_active' => '',
	);

	if ( wp_verify_nonce( $_REQUEST['nonce'], basename( __FILE__ ) ) ) {
		$item = shortcode_atts( $default, $_REQUEST );
		$item_valid = validate_price_doba( $item );
		if ( $item_valid === true ) {
			if ( $item['id'] == 0 ) {
				$active = $item['is_active'];
				if($active == 1){
				$updateother = "update $table_name set is_active = 0";
				$wpdb->query( $updateother );
				}
				$result = $wpdb->insert( $table_name, $item );
				$item['id'] = $wpdb->insert_id;
				if ( $result ) {

					$message = __( 'Item was successfully saved', 'doba_price_setup' );
				} else {
					$notice = __( 'There was an error while saving item', 'doba_price_setup' );
				}
			} else {
				
				$result = $wpdb->update( $table_name, $item, array( 'id' => $item['id'] ) );
				$id = $item['id'];
				$active = $item['is_active'];
				if($active == 1){
				$updateother = "update $table_name set is_active = 0 where id != $id";
				$wpdb->query( $updateother );
				}
				if ( $result ) {
					$message = __( 'Item was successfully updated', 'doba_price_setup' );
				} else {
					$notice = __( 'There was an error while updating item', 'doba_price_setup' );
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
	add_meta_box( 'doba_price_form_meta_box', 'Doba Price data', 'doba_price_table_form_meta_box_handler', 'doba', 'normal', 'default' );
	?>
	<div class="wrap">
		<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
		<h2><?php _e( 'Doba Price data', 'doba_price__setup' ) ?> <a class="add-new-h2"
																	 href="<?php echo get_admin_url( get_current_blog_id(), 'admin.php?page=pricesetup' ); ?>"><?php _e( 'back to list', 'doba_price_setup' ) ?></a>
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
						<input type="submit" value="<?php _e( 'Save', 'doba_price_setup' ) ?>" id="submit" class="button-primary" name="submit">
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
}

function doba_price_table_form_meta_box_handler( $item ) {
	?>
	<table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
		<tbody>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="price"><?php _e( 'Price', 'doba_price_setup' ) ?></label>
				</th>
				<td>
					<input id="price" name="price_percent" type="text" style="width: 95%" value="<?php echo esc_attr( $item['price_percent'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Price in Percent', 'doba_price_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="threshold"><?php _e( 'Threshold', 'doba_price_setup' ) ?></label>
				</th>
				<td>
					<input id="threshold" name="threshold_value" type="text" style="width: 95%" value="<?php echo esc_attr( $item['threshold_value'] ) ?>"
						   size="50" class="code" placeholder="<?php _e( 'Threshold value in number', 'doba_price_setup' ) ?>" required>
				</td>
			</tr>
			<tr class="form-field">
				<th valign="top" scope="row">
					<label for="is_active"><?php _e( 'Active', 'doba_price_setup' ) ?></label>
				</th>
				<td>

					<input id="is_active" name="is_active" type="checkbox" value="0" onchange="if(this.value == 0){this.value = 1 }else{this.value = 0}" <?php if ( $item['is_active'] ): echo checked;
	endif; ?>>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

function validate_price_doba( $item ) {
	$messages = array();
	if ( empty( $item['price_percent'] ) )
		$messages[] = __( 'Price is required', 'doba_price_setup' );
	if ( !is_numeric( $item['price_percent'] ) )
		$messages[] = __( 'Price is in wrong format', 'doba_price_setup' );
	if ( empty( $item['threshold_value'] ) )
		$messages[] = __( 'Threshold value is required', 'doba_price_setup' );
	if ( !is_numeric( $item['threshold_value'] ) )
		$messages[] = __( 'Threshold value is in wrong format', 'doba_price_setup' );
	if ( empty( $messages ) )
		return true;
	return implode( '<br />', $messages );
}





