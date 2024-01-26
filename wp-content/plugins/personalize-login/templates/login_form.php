<div class="container-div">
    <!-- Show errors if there are any -->
    <?php if (count($attributes['errors']) > 0) : ?>
    <fieldset class="error-field"><legend><label><i class="fa fa-bell"></i> Please Check the Following</label></legend>
        <?php foreach ($attributes['errors'] as $error) : ?>
            <p class="login-error">
                
                <?php echo $error; ?>
            </p>
        <?php endforeach; ?>
    </fieldset>
    <?php endif; ?>
    <!-- Show logged out message if user just logged out -->
    <?php if ($attributes['logged_out']) : ?>
    <fieldset class="error-field"><legend><label><i class="fa fa-bell"></i> Signed Out</label></legend>
        <p class="login-error">
            <?php _e('You have signed out. Would you like to sign in again?', 'personalize-login'); ?>
        </p>
        </fieldset>
    <?php endif; ?>
    <?php if ($attributes['password_updated']) : ?>
    <fieldset class="error-field"><legend><label><i class="fa fa-check"></i> Signed Out</label></legend>
        <p class="login-error">
            <?php _e('Your password has been changed. You can sign in now.', 'personalize-login'); ?>
        </p>
    </fieldset>
    <?php endif; ?>
        
    <div class="u-columns row" id="customer_login">

	<div class="u-column1 col-md-6 col-lg-6">
		<h2><?php _e( 'login', 'woocommerce' ); ?></h2>
        <form method="post" action="<?php echo wp_login_url(); ?>">
            <p class="login-username">
                <label for="user_login" class="form-label"><?php _e('Email', 'personalize-login'); ?></label>
                <input type="text" class="form-input" name="log" id="user_login">
            </p>
            <p class="login-password">
                <label for="user_pass" class="form-label"><?php _e('Password', 'personalize-login'); ?></label>
                <input type="password" class="form-input" name="pwd" id="user_pass">
            </p>
            <p class="login-submit">
                <input type="submit" class="form-button" value="<?php _e('Sign In', 'personalize-login'); ?>">
            </p>
        </form>
        <a class="form-link" href="<?php echo get_permalink( get_page_by_path( 'member-password-lost' ) ); ?>">
            <?php _e('Forgot your password?', 'personalize-login'); ?>
        </a><br>
        <a class="form-link" href="<?php echo get_permalink(get_page_by_path('member-register')) ?>">
            <?php _e('New User?', 'personalize-login'); ?>
        </a>
    </div>
		<div class="u-column2 col-md-6 col-lg-6">

		<h2><?php _e( 'Register', 'woocommerce' ); ?></h2>

		<a class="form-link" href="<?php echo get_permalink(get_page_by_path('member-register')) ?>">
            <?php _e('Register for Organization Fundraising', 'personalize-login'); ?>
        </a><br>
		<a class="form-link" href="<?php echo get_permalink(get_page_by_path('member-individual')) ?>">
            <?php _e('Register for Individual Fundraising', 'personalize-login'); ?>
        </a><br>
		<a class="form-link" href="<?php echo get_permalink(get_page_by_path('member-register')) ?>">
            <?php _e('Register for Shopping', 'personalize-login'); ?>
        </a><br>

	</div>
	</div>
   
        
</div>


