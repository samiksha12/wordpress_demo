<?php
/**
 * Plugin Name:       Fundraised Plugin
 * Description:       A plugin that will give complete report of fundraised.
 * Version:           1.0.0
 * Author:            Samiksha Sapkota
 * Text Domain:       fundraised
 */

class Fundraised{
	private $my_plugin_screen_name;
	private static $instance;

	static function GetInstance() {

		if ( !isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	public function FundMenu() {
		$this->my_plugin_screen_name = add_menu_page(
				'Fundraised', 'Fundraised', 'manage_options', 'fund_admin', array( $this, 'fund_admin' ), 'dashicons-admin-links', 10
		);
		
	}
	
	public function InitPlugin() {

		add_action( 'admin_menu', array( $this, 'FundMenu' ) );
		
	}
	
	public function fund_admin() {
		require_once 'fund_admin.php';
		
	}
	
	
}
$fund = Fundraised::GetInstance();
$fund->InitPlugin();