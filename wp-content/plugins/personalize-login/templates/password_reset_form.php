<div id="password-reset-form" class="container-div">
    <?php if ( $attributes['show_title'] ) : ?>
        <h3><?php _e( 'Pick a New Password', 'personalize-login' ); ?></h3>
    <?php endif; ?>
 
    <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />
         
        <?php if ( count( $attributes['errors'] ) > 0 ) : ?>
        <fieldset class="error-field"><legend><label><i class="fa fa-bell"></i> Please Check the Following</label></legend>
            <?php foreach ( $attributes['errors'] as $error ) : ?>
        <p class="login-error">
                    <?php echo $error; ?>
                </p>
            <?php endforeach; ?>
        </fieldset>
        <?php endif; ?>
 
        <p>
            <label class="form-label" for="pass1"><?php _e( 'New password', 'personalize-login' ) ?></label>
            <input class="form-input" type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
        </p>
        <p>
            <label class="form-label" for="pass2"><?php _e( 'Repeat new password', 'personalize-login' ) ?></label>
            <input class="form-input" type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
        </p>
         
        <p class="description"><?php echo wp_get_password_hint(); ?></p>
         
        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button"
                   class="form-button" value="<?php _e( 'Reset Password', 'personalize-login' ); ?>" />
        </p>
    </form>
</div>