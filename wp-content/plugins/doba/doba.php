<?php

/**
 * Plugin Name:       Doba Integrate
 * Description:       A plugin that integrates doba to wordpress.
 * Version:           1.0.0
 * Author:            Samiksha Sapkota
 */
class Doba {

	private $my_plugin_screen_name;
	private static $instance;

	static function GetInstance() {

		if ( !isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$plugin = plugin_basename( __FILE__ );
		
		add_filter( "plugin_action_links_$plugin", array( $this, 'your_plugin_settings_link' ) );
		
	}

	public function PluginMenu() {
		$this->my_plugin_screen_name = add_menu_page(
				'Doba', 'Doba', 'manage_options', 'doba_admin', array( $this, 'doba_admin' ), 'dashicons-admin-links', 9
		);

		add_submenu_page( 'doba_admin', 'Inventory', 'Inventory', 'manage_options', 'doba_admin', array( $this, 'doba_admin' ) );
		add_submenu_page( 'doba_admin', 'Doba Order', 'Doba Order', 'manage_options', 'order', array( $this, 'doba_order_table' ) );
		add_submenu_page( 'doba_admin', 'Inventory Update', 'Inventory Update', 'manage_options', 'inventory', array( $this, 'doba_inventory_update' ) );
		add_submenu_page( 'doba_admin', 'Setting', 'Setting', 'manage_options', 'authsetup', array( $this, 'doba_table' ) );
		//add_submenu_page( 'doba_admin', 'Price Setting', 'Price Setting', 'manage_options', 'pricesetup', array( $this, 'doba_price_table' ) );
		//add_submenu_page( 'doba_admin', 'Item Add', 'Item Add', 'manage_options', 'additem', array( $this, 'doba_item_add' ) );
//		add_submenu_page( 'doba_admin', 'category', 'category', 'manage_options', 'categoryexample', array( $this, 'doba_item_add' ) );
		add_submenu_page( null, 'Create Order', 'Create Order', 'manage_options', 'create_order', array( $this, 'doba_cron_job' ) );
		add_submenu_page( null, 'Add Auth', 'Add Auth', 'manage_options', 'doba_form', array( $this, 'doba_table_add' ) );
		//add_submenu_page( null, 'Add Price', 'Add Price', 'manage_options', 'doba_price', array( $this, 'doba_price_add' ) );
		add_submenu_page( null, 'Edit List', 'Edit Price', 'manage_options', 'doba_edit', array( $this, 'doba_price_edit' ) );
		add_submenu_page( null, 'Update List', 'Update List', 'manage_options', 'update_list', array( $this, 'doba_list_update' ) );
		add_submenu_page( null, 'Sync Item', 'Sync Item', 'manage_options', 'sync_item', array( $this, 'sync_list_item' ) );
		add_submenu_page( null, 'View Item', 'View Item', 'manage_options', 'view_item', array( $this, 'view_list_item' ) );
	}

	public function InitPlugin() {

		add_action( 'admin_menu', array( $this, 'PluginMenu' ) );
		
	}
	


	public function plugin_activated() {
		global $wpdb;
		// creates my_table in database if not exists
		$table = $wpdb->prefix . "doba_detail";
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table (
        `id` mediumint(9) NOT NULL AUTO_INCREMENT,
        `username` text NOT NULL,
		`password` text NOT NULL,
		`retailer_id` text NOT NULL,
    UNIQUE (`id`)
    ) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
	}
	


	public function doba_admin() {
		require_once 'doba_admin.php';
		check_credential();
	}
	public function doba_item_add(){
		require_once 'newitem.php';
		schedule_sync_item();
//		require_once 'category.php';
	}

	public function doba_inventory_update() {
		require_once 'inventoryupdate.php';
		updateinventory();
	}


	public function doba_table() {
		require_once 'authsetup.php';
		doba_table_display();
	}

	public function doba_order_table() {
		require_once 'ordertable.php';
		doba_order_table_display();
	}

	public function doba_cron_job() {
		require_once 'ordertable.php';
		doba_place_order();
		doba_order_table_display();
	}
	
	public function doba_create_order(){
		require_once 'ordertable.php';
		doba_place_order();
	}

	public function doba_table_add() {
		require_once 'authsetup.php';
		doba_table_form_page_handler();
	}

	public function doba_price_table() {
		require_once 'pricesetup.php';
		create_price_table();
		doba_price_table_display();
	}

//	public function doba_price_add() {
//		require_once 'pricesetup.php';
//		doba_price_table_form_page_handler();
//	}
	
	public function doba_price_edit() {
		require_once 'doba_admin.php';
		doba_price_edit_table_form_page_handler();
	}

	public function doba_list_update() {
		require_once 'doba_admin.php';
		doba_list_update();
	}

	public function sync_list_item() {
		require_once 'doba_admin.php';
		doba_sync();
	}

	public function view_list_item() {
		require_once 'doba_admin.php';
		view_item();
	}

	public function your_plugin_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=authsetup' ) . '">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

}

$doba = Doba::GetInstance();
$doba->InitPlugin();

register_activation_hook( __FILE__, array( 'Doba', 'plugin_activated' ) );
