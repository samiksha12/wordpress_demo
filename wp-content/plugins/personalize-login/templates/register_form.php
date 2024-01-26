<?php

function registration_form( $username, $email, $full_name, $position, $descrip, $address, $oname, $oemail, $contact, $password, $auth ) {
	echo '
    <style>
    div {
        margin-bottom:2px;
    }
     tr{
	 border-bottom: 0px solid #eee !important;
	 }
    input{
        margin-bottom:4px;
    }
    </style>
    ';

	echo '
    <div class="container-div">
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data">
        <div>
    <fieldset> 
	<legend><label class="form-label">Organization Details</label></legend>
	<div>
    <label for="oname" class="form-label">Organization/Company Name</label>
    <input type="text" class="form-input" id="oname" name="oname" value="' . ( isset( $_POST['oname'] ) ? $oname : null ) . '">
    </div>
	<div>
    <label for="oemail" class="form-label">Company Email Address</label>
    <input type="text" class="form-input" id="oemail" name="oemail" value="' . ( isset( $_POST['oemail'] ) ? $oemail : null ) . '">
    </div>
    <div>
    <label for="descrip" class="form-label">Comment</label>
    <textarea cols="50"  name="descrip" placeholder="Please tell us about your fundraiser">' . ( isset( $_POST['descrip'] ) ? $descrip : null ) . '</textarea>
    </div>
	</fieldset>
        
	</div>
	<div>
	<fieldset>
	<legend><label class="form-label">Contact Details</label></legend>
	<div>
    <label for="fname" class="form-label">POC name for the Organization</label>
    <input type="text" class="form-input" id="fname" name="fname" value="' . ( isset( $_POST['fname'] ) ? $full_name : null ) . '">
    </div>
    <div>
    <label for="position" class="form-label">POC Position</label>
    <input type="text" class="form-input" id="position" name="position" value="' . ( isset( $_POST['position'] ) ? $position : null ) . '">
    </div>
	<div>
    <label for="username" class="form-label">Username <strong>*</strong></label>
    <input type="text" class="form-input" name="username" value="' . ( isset( $_POST['username'] ) ? $username : null ) . '">
    </div>
	<div>
    <label for="password" class="form-label">Password <strong>*</strong></label>
    <input type="password" class="form-input" name="password" value="' . null . '">
    </div>
	<div>
    <label for="password" class="form-label">Confirm Password <strong>*</strong></label>
    <input type="password" class="form-input" name="password" value="' . null . '">
    </div>
	<div>
    <label for="address" class="form-label">POC Address</label>
    <textarea cols="50" name="address">' . ( isset( $_POST['address'] ) ? $address : null ) . '</textarea>
    </div>
    <div>
    <label for="contact" class="form-label">POC Contact number</label>
    <input type="text" class="form-input" id="contact" name="contact" value="' . ( isset( $_POST['contact'] ) ? $contact : null ) . '">
    </div>
	<div>
    <label for="email" class="form-label">POC Email <strong>*</strong></label>
    <input type="text" class="form-input" name="email" value="' . ( isset( $_POST['email'] ) ? $email : null ) . '">
    </div>
	</fieldset>
	</div>
	<div>
	<input type="checkbox" name="validauth" value="auth"' . ( isset( $_POST['validauth'] ) ? 'checked' : null ) . '>Click the box below if you are authorized to raise funds for the Organization/ Group listed above
	</div>
       

    <input type="submit" name="submit" value="Register" class="form-button"/>
    </form>
    </div>
    ';
}

function registration_validation( $username, $full_name, $oemail, $email, $password ) {

	global $reg_errors;
	$reg_errors = new WP_Error;
	if ( empty( $username ) || empty( $email ) || empty( $full_name ) || empty( $password ) ) {
		$reg_errors->add( 'field', 'Required form field is missing' );
	}
	if ( 4 > strlen( $username ) ) {
		$reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
	}
	if ( username_exists( $username ) ) {
		$reg_errors->add( 'user_name', 'Sorry, that username already exists!' );
	}
	if ( !validate_username( $username ) ) {
		$reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
	}

	if ( !is_email( $email ) ) {
		$reg_errors->add( 'email_invalid', 'Email is not valid' );
	}
	if ( !is_email( $oemail ) ) {
		$reg_errors->add( 'email_invalid', 'Organization Email is not valid' );
	}
	if ( email_exists( $email ) ) {
		$reg_errors->add( 'email', 'Email Already in use' );
	}
	if ( is_wp_error( $reg_errors ) && !empty( $reg_errors->errors ) ) {
		echo '<fieldset class="error-field"><legend><label><i class="fa fa-bell"></i> Please Check the Following</label></legend>';
		foreach ( $reg_errors->get_error_messages() as $error ) {

			echo '<div class="login-error">';
			echo '<strong>ERROR</strong>:';
			echo $error . '<br/>';
			echo '</div><p>&nbsp;</p>';
		}
		echo '</fieldset><p>&nbsp;</p>';
	}
}

function randomString( $length = 6, $user ) {
//	$str = "";
//	$characters = array_merge( range( 'A', 'Z' ), range( 'a', 'z' ), range( '0', '9' ) );
//	$max = count( $characters ) - 1;
//	for ( $i = 0; $i < $length; $i++ ) {
//		$rand = mt_rand( 0, $max );
//		$str .= $characters[$rand];
//	}
	$str = substr( md5( uniqid( $user, true ) ), $length, $length );
	return $str;
}

function complete_registration() {

	global $wpdb, $reg_errors, $username, $email, $full_name, $descrip, $address, $oname, $oemail, $contact, $auth, $password, $position;
	if ( 1 > count( $reg_errors->get_error_messages() ) ) {
		$userdata = array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass' => $password,
			'first_name' => $full_name,
			'nickname' => $full_name,
			'description' => $descrip,
		);
		$customerId = randomString( 8, $username );
		$user = wp_insert_user( $userdata );
		$usertable = $wpdb->prefix."users";
		$wpdb->query("update $usertable set user_pass=md5($password) where id=$user");
		add_user_meta( $user, 'address', $address );
		add_user_meta( $user, 'org_name', $oname );
		add_user_meta( $user, 'org_email', $oemail );
		add_user_meta( $user, 'position', $position );
		add_user_meta( $user, 'auth', $auth );
		add_user_meta( $user, 'contact', $contact );
		add_user_meta( $user, 'organization', 'yes' );

		$role = new WP_User( $user );
		$role->set_role( 'subscriber' );
		if ( $auth ) {
			add_user_meta( $user, 'customerId', $customerId );
			$to = $email;
			$subject = 'Customer Id For Doafundraiser';
			$body = 'Your customer Id number is: ' . $customerId;
			$headers = array(
				'From: Samiksha Sapkota <sapkota.samiksha@gmail.com>',
			);

			wp_mail( $to, $subject, $body, $headers );
		}
		echo '<fieldset class="error-field"><legend><label><i class="fa fa-check"></i> Successfully registered</label></legend>';
		echo '<div class="login-error">';
		echo 'Registration complete. Goto <a href="' . get_permalink( get_page_by_path( 'member-login' ) ) . '">Login</a>';
		echo '</div>';
		echo '</fieldset><p>&nbsp;</p>';

		$_POST = array();
	}
}

function custom_registration_function() {
	if ( isset( $_POST['submit'] ) ) {
		registration_validation(
				$_POST['username'], $_POST['fname'], $_POST['oemail'], $_POST['email'], $_POST['password']
		);

		// sanitize user form input
		global $username, $password, $email, $full_name, $position, $descrip, $address, $oname, $oemail, $contact, $auth;
		$username = sanitize_user( $_POST['username'] );
		$email = sanitize_email( $_POST['email'] );
		$full_name = sanitize_text_field( $_POST['fname'] );
		$position = sanitize_text_field( $_POST['position'] );
		$descrip = esc_textarea( $_POST['descrip'] );
		$oemail = $_POST['oemail'];
		$oname = $_POST['oname'];
		$contact = $_POST['contact'];
		$address = esc_textarea( $_POST['address'] );
		$password = esc_attr( $_POST['password'] );
		$auth = (isset( $_POST['validauth'] ) ? 1 : 0);
		// call @function complete_registration to create the user
		// only when no WP_error is found
		complete_registration(
				$username, $email, $full_name, $position, $descrip, $address, $oname, $oemail, $contact, $password, $auth
		);
	}

	registration_form(
			$username, $email, $full_name, $position, $descrip, $address, $oname, $oemail, $contact, $password, $auth
	);
}

?>
