<?php

/**
 * Plugin Name:       Personalize Login
 * Description:       A plugin that replaces the WordPress login flow with a custom page.
 * Version:           1.0.0
 * Author:            Samiksha Sapkota
 * Text Domain:       personalize-login
 */
class Personalize_Login_Plugin {

	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) );
		add_shortcode( 'custom-register-individual-form', array( $this, 'render_register_individual_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_jquery' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_timestylesheets' ) );
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) );
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
		add_action( 'wp_footer', array( $this, 'activate_timepicker' ) );
		add_action( 'show_user_profile', array( $this, 'extra_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'extra_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_profile_fields' ) );
		add_shortcode( 'account-info', array( $this, 'render_custom_member_account' ) );
		add_shortcode( 'edit-account-info', array( $this, 'render_edit_member_account' ) );
		add_action( 'after_setup_theme', array( $this, 'remove_admin_bar' ) );
		add_action( 'woocommerce_after_order_notes', array( $this, 'customise_checkout_field' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'customise_checkout_field_update_order_meta' ) );
		add_filter( 'woocommerce_email_order_meta_keys', array($this,'custom_order_meta_keys' ));
	}

	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function plugin_activated() {
		// Information needed for creating the plugin's pages
		$page_definitions = array(
			'member-login' => array(
				'title' => __( 'Sign In', 'personalize-login' ),
				'content' => '[custom-login-form]'
			),
			'member-account' => array(
				'title' => __( 'Your Account', 'personalize-login' ),
				'content' => '[account-info]'
			),
			'edit-account' => array(
				'title' => __( 'Edit Account', 'personalize-login' ),
				'content' => '[edit-account-info]'
			),
			'member-register' => array(
				'title' => __( 'Register', 'personalize-login' ),
				'content' => '[custom-register-form]'
			),
			'member-individual' => array(
				'title' => __( 'Register', 'personalize-login' ),
				'content' => '[custom-register-individual-form]'
			),
			'member-password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'personalize-login' ),
				'content' => '[custom-password-lost-form]'
			),
			'member-password-reset' => array(
				'title' => __( 'Pick a New Password', 'personalize-login' ),
				'content' => '[custom-password-reset-form]'
			)
		);

		foreach ( $page_definitions as $slug => $page ) {
			// Check that the page doesn't exist already
			$query = new WP_Query( 'pagename=' . $slug );
			if ( !$query->have_posts() ) {
				// Add the page using the data from the array above
				wp_insert_post(
						array(
							'post_content' => $page['content'],
							'post_name' => $slug,
							'post_title' => $page['title'],
							'post_status' => 'publish',
							'post_type' => 'page',
							'ping_status' => 'closed',
							'comment_status' => 'closed',
						)
				);
			}
		}
	}
	

	public function customise_checkout_field( $checkout ) {
		global $wpdb;
		$data = array(
			''=>'Select Organization for Fundraising',
		);
		$table = $wpdb->prefix."usermeta";
		$customerId = $wpdb->get_results( "Select user_id,meta_value from $table where meta_key='customerId' ", ARRAY_A );
		
		if(!empty($customerId)){
			foreach($customerId as $customer){
				
				$data[$customer['meta_value']]=get_user_meta($customer['user_id'],'org_name',true);
			}
		}
		 echo '<div id="bv_custom_checkout_field"><h2>FundRaising</h2>';
		woocommerce_form_field( 'customer-id', array(
			'type' => 'select',
			'class' => array(
				'my-field-class form-row-wide'
			),
			'label' => __( 'Customer Id number' ),
			'placeholder' => __( 'Customer Id for fundraising' ),
			'options'=>$data,
				), get_user_meta( get_current_user_id(), 'customer-id' , true  ) );
		echo '</div>';
	}

	public function customise_checkout_field_update_order_meta( $order_id ) {
		if ( !empty( $_POST['customer-id'] ) ) {
			update_post_meta( $order_id, 'customer-id', sanitize_text_field( $_POST['customer-id'] ) );
		}
	}

	public function custom_order_meta_keys( $keys ) {
		$keys[] = 'customer-id'; // This will look for a custom field called 'customer-id' and add it to emails
		return $keys;
	}

//redirect shortcode to the render function
	public function render_login_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		$show_title = $attributes['show_title'];

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );

			foreach ( $error_codes as $code ) {
				$errors [] = $this->get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';

		// Render the login form using an external template
		return $this->get_template_html( 'login_form', $attributes );
	}

//get login_form
	private function get_template_html( $template_name, $attributes = null ) {
		if ( !$attributes ) {
			$attributes = array();
		}

		ob_start();

		do_action( 'personalize_login_before_' . $template_name );

		require( 'templates/' . $template_name . '.php');

		do_action( 'personalize_login_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	function redirect_to_custom_login() {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;

			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user( $redirect_to );
				exit;
			}

			// The rest are redirected to the login page

			$login_url = get_permalink( get_page_by_path( 'member-login' ) );
			if ( !empty( $redirect_to ) ) {
				$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

//redirect after logged in
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			wp_redirect( get_permalink( get_page_by_path( 'my-account' ) ) );
		}
	}

	//remove admin bar
	public function remove_admin_bar() {
		if ( !current_user_can( 'administrator' ) && !is_admin() ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	//render to member-account page
	public function render_custom_member_account() {

		add_filter( 'show_admin_bar', '__return_false' );
		return $this->get_template_html( 'member_page' );
	}

	public function render_edit_member_account() {

		add_filter( 'show_admin_bar', '__return_false' );
		return $this->get_template_html( 'edit_member_page' );
	}

	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case 'empty_username':
				return __( 'You do have an email address, right?', 'personalize-login' );

			case 'empty_password':
				return __( 'You need to enter a password to login.', 'personalize-login' );

			case 'invalid_username':
				return __(
						"We don't have any users with that email address. Maybe you used a different one when signing up?", 'personalize-login'
				);

			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'personalize-login' );

			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'personalize-login' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'personalize-login' );

			case 'password_length':
				return __( "Password is too short, need 12 characters", 'personalize-login' );
			case 'incorrect_password':
				$err = __(
						"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?", 'personalize-login'
				);
				return sprintf( $err, get_permalink( get_page_by_path( 'member-password-lost' ) ) );
			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'personalize-login' );
			default:
				break;
		}

		return __( 'An unknown error occurred. Please try again later.', 'personalize-login' );
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {


		$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
		$redirect_url = add_query_arg( 'logged_out', 'true', $redirect_url );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = home_url();

		if ( !isset( $user->ID ) ) {
			return $redirect_url;
		}

		if ( user_can( $user, 'manage_options' ) ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $requested_redirect_to;
			}
		} else {
			// Non-admin users always go to their account page after login
			$redirect_url = get_permalink( get_page_by_path( 'my-account' ) );
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}

	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} elseif ( !get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'personalize-login' );
		} else {
			//return $this->get_template_html( 'register_form1', $attributes );
			include plugin_dir_path( __FILE__ ) . 'templates/register_form.php';
			custom_registration_function();
		}
	}
	public function render_register_individual_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} elseif ( !get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'personalize-login' );
		} else {
			//return $this->get_template_html( 'register_form1', $attributes );
			include plugin_dir_path( __FILE__ ) . 'templates/individual_form.php';
			custom_register_function();
		}
	}

	public function extra_profile_fields( $user ) {
		?>
		<h3>Extra profile information</h3>
		<table class="form-table">
			<tr>
				<th><label for="image">Farm Image</label></th>

				<td>
					<?php
					$image_id = esc_attr( get_the_author_meta( 'farm_image', $user->ID ) );
					$image_attributes = wp_get_attachment_image_src( $image_id, 'full' );
					?> 
					<img src="<?php echo $image_attributes[0]; ?>">

				</td>
			</tr>
		</table>
		<table class="form-table">
			<tr>
				<th><label for="oname">Organization Name</label></th>
				<td>
					<input type="text" name="oname" id="oname" value="<?php echo esc_attr( get_the_author_meta( 'org_name', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Organization name.</span>
				</td>
			</tr>
			<tr>
				<th><label for="image">Company Logo</label></th>

				<td>
					<?php
					$image_id = esc_attr( get_the_author_meta( 'logo_image', $user->ID ) );
					$image_attributes = wp_get_attachment_image_src( $image_id );
					?> 
					<img src="<?php echo $image_attributes[0]; ?>" style="height:150px;">

				</td>
			</tr>

			<tr>
				<th><label for="opentime">Company opening time</label></th>
				<td>
					<input type="text" name="opentime" id="opentime" value="<?php echo esc_attr( get_the_author_meta( 'opentime', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter Company opening time.</span>
				</td>
			</tr>

			<tr>
				<th><label for="address">Address</label></th>
				<td>
					<input type="text" name="address" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Address.</span>
				</td>
			</tr>
			<tr>
				<th><label for="contact">Contact Number</label></th>
				<td>
					<input type="text" name="contact" id="contact" value="<?php echo esc_attr( get_the_author_meta( 'contact', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Contact number.</span>
				</td>
			</tr>
			<tr>
				<th><label>Choose category</label></th>
				<td>

					<input type="checkbox" name="bread" value="bread"<?php
			if ( get_the_author_meta( 'bread', $user->ID ) ) {
				echo checked;
			}
					?>>Bread<br>
					<input type="checkbox" name="beverages" value="beverages" <?php
			if ( get_the_author_meta( 'beverages', $user->ID ) ) {
				echo checked;
			}
					?>>Beverages<br>
					<input type="checkbox" name="dairy" value="dairy" <?php
			if ( get_the_author_meta( 'dairy', $user->ID ) ) {
				echo checked;
			}
					?>>Cheese and other dairy products<br>
					<input type="checkbox" name="flour" value="flour"<?php
			if ( get_the_author_meta( 'flour', $user->ID ) ) {
				echo checked;
			}
					?>>Flour, different types<br>
					<input type="checkbox" name="handicraft" value="handicraft"<?php
			if ( get_the_author_meta( 'handicraft', $user->ID ) ) {
				echo checked;
			}
					?>>Handicraft and art<br>
				</td>
				<td>
					<input type="checkbox" name="icecream" value="icecream"<?php
			if ( get_the_author_meta( 'icecream', $user->ID ) ) {
				echo checked;
			}
					?>>Ice cream<br>
					<input type="checkbox" name="meat" value="meat" <?php
			if ( get_the_author_meta( 'meat', $user->ID ) ) {
				echo checked;
			}
					?>>Meat<br>
					<input type="checkbox" name="milk" value="milk" <?php
			if ( get_the_author_meta( 'milk', $user->ID ) ) {
				echo checked;
			}
					?>>Milk<br>
					<input type="checkbox" name="vegetables" value="vegetables" <?php
			if ( get_the_author_meta( 'vegetables', $user->ID ) ) {
				echo checked;
			}
					?>>Vegetables<br>
					<input type="checkbox" name="other" value="other" <?php
			if ( get_the_author_meta( 'other', $user->ID ) ) {
				echo checked;
			}
					?>>Other<br>
				</td>


			</tr>
			<tr>
				<th><label for="fb">Faceook</label></th>
				<td>
					<input type="text" name="fb" id="fb" value="<?php echo esc_attr( get_the_author_meta( 'fb', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Facebook username.</span>
				</td>
			</tr>
			<tr>
				<th><label for="twit">Twitter</label></th>
				<td>
					<input type="text" name="twit" id="twit" value="<?php echo esc_attr( get_the_author_meta( 'twit', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Twitter username.</span>
				</td>
			</tr>
			<tr>
				<th><label for="insta">Instagram</label></th>
				<td>
					<input type="text" name="insta" id="insta" value="<?php echo esc_attr( get_the_author_meta( 'insta', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description">Please enter your Instagram username.</span>
				</td>
			</tr>
		</table>
		<?php
	}

	function save_custom_user_profile_fields( $user_id ) {

		if ( !current_user_can( 'edit_user', $user_id ) )
			return FALSE;

		update_usermeta( $user_id, 'opentime', $_POST['opentime'] );
		update_usermeta( $user_id, 'address', $_POST['address'] );
		update_usermeta( $user_id, 'org_name', $_POST['oname'] );
		update_usermeta( $user_id, 'contact', $_POST['contact'] );
		update_usermeta( $user_id, 'bread', $_POST['bread'] );
		update_usermeta( $user_id, 'beverages', $_POST['beverages'] );
		update_usermeta( $user_id, 'dairy', $_POST['dairy'] );
		update_usermeta( $user_id, 'flour', $_POST['flour'] );
		update_usermeta( $user_id, 'handicraft', $_POST['handicraft'] );
		update_usermeta( $user_id, 'icecream', $_POST['icecream'] );
		update_usermeta( $user_id, 'meat', $_POST['meat'] );
		update_usermeta( $user_id, 'milk', $_POST['milk'] );
		update_usermeta( $user_id, 'vegetables', $_POST['vegetables'] );
		update_usermeta( $user_id, 'other', $_POST['other'] );
		update_usermeta( $user_id, 'fb', $_POST['fb'] );
		update_usermeta( $user_id, 'twit', $_POST['twit'] );
		update_usermeta( $user_id, 'insta', $_POST['insta'] );
	}

	public function redirect_to_custom_register() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
			} else {
				wp_redirect( get_permalink( get_page_by_path( 'member-register' ) ) );
			}
			exit;
		}
	}

	function wp_enqueue_jquery() {

		//wp_enqueue_script('jquery');
		global $wp_scripts;
//        wp_enqueue_script('bootstrap_js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'));
		wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'templates/jquery.timepicker.js', array( 'jquery' ), '1.0.0', false );
		wp_enqueue_script( 'my_custom_script1', plugin_dir_url( __FILE__ ) . 'templates/jquery.timepicker.min.js', array( 'jquery' ), '1.0.0', false );
	}

	function add_timestylesheets() {
		wp_enqueue_style( 'custom_css', plugin_dir_url( __FILE__ ) . 'templates/customCss.css' );
		// change this path to load your own custom stylesheet
		$css_path = plugin_dir_url( __FILE__ ) . 'templates/jquery.timepicker.css';

		// registers your stylesheet
		wp_register_style( 'timepickerStyles', $css_path );
		//wp_register_style('bootstrap_css', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css');
		wp_register_style( 'font_awesome_css', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
		// loads your stylesheet
		wp_enqueue_style( 'timepickerStyles' );
		wp_enqueue_style( 'font_awesome_css' );
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}

			wp_redirect( get_permalink( get_page_by_path( 'member-password-lost' ) ) );
			exit;
		}
	}

	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} else {
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] [] = $this->get_error_message( $error_code );
				}
			}
			return $this->get_template_html( 'password_lost_form', $attributes );
		}
	}

	public function do_password_lost() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = (get_permalink( get_page_by_path( 'member-password-lost' ) ));
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				$redirect_url = (get_permalink( get_page_by_path( 'member-login' ) ));
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		// Create new message
		$msg = __( 'Hello!', 'personalize-login' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'personalize-login' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'personalize-login' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'personalize-login' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'personalize-login' ) . "\r\n";

		return $msg;
	}

	public function redirect_to_custom_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( !$user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
					$redirect_url = add_query_arg( 'login', 'expiredkey', $redirect_url );

					wp_redirect( $redirect_url );
				} else {
					$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
					$redirect_url = add_query_arg( 'login', 'invalidkey', $redirect_url );

					wp_redirect( $redirect_url );
				}
				exit;
			}

			$redirect_url = get_permalink( get_page_by_path( 'member-password-reset' ) );
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'personalize-login' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );

					foreach ( $error_codes as $code ) {
						$errors [] = $this->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return $this->get_template_html( 'password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'personalize-login' );
			}
		}
	}

	public function do_password_reset() {

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( !$user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
					$redirect_url = add_query_arg( 'login', 'expiredkey', $redirect_url );

					wp_redirect( $redirect_url );
				} else {
					$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
					$redirect_url = add_query_arg( 'login', 'invalidkey', $redirect_url );

					wp_redirect( $redirect_url );
				}
				exit;
			}


			if ( isset( $_POST['pass1'] ) ) {

				if ( 10 > strlen( $_POST['pass1'] ) ) {
					$redirect_url = get_permalink( get_page_by_path( 'member-password-reset' ) );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_length', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}
				if ( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match
					$redirect_url = get_permalink( get_page_by_path( 'member-password-reset' ) );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				if ( empty( $_POST['pass1'] ) ) {
					// Password is empty
					$redirect_url = get_permalink( get_page_by_path( 'member-password-reset' ) );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				//echo $user;
				// Parameter checks OK, reset password
				reset_password( $user, $_POST['pass1'] );
				$redirect_url = get_permalink( get_page_by_path( 'member-login' ) );
				$redirect_url = add_query_arg( 'password', 'changed', $redirect_url );
				wp_redirect( $redirect_url );
				exit;
			} else {
				echo "Invalid request.";
			}
			//wp_redirect(get_permalink(get_page_by_path('member-login?password=changed')));
//            exit;
		}
	}

	function activate_timepicker() {
		?>

		<script>
			(function ($) {

				$('#basicExample').timepicker();



			})(jQuery);

		</script>
		<?php
	}

	function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely, 
		// the default WordPress authentication) functions have found errors
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if ( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );

				$login_url = get_permalink( get_page_by_path( 'member-login' ) );
				$login_url = add_query_arg( 'login', $error_codes, $login_url );

				wp_redirect( $login_url );
				exit;
			}
		}

		return $user;
	}

}

// Initialize the plugin
$personalize_login_pages_plugin = new Personalize_Login_Plugin();


// Create the custom pages at plugin activation
register_activation_hook( __FILE__, array( 'Personalize_Login_Plugin', 'plugin_activated' ) );

