<div id="password-lost-form" class="container-div">
    <?php if ($attributes['lost_password_sent']) : ?>
        <p class="login-info">
            <?php _e('Check your email for a link to reset your password.', 'personalize-login'); ?>
        </p>
    <?php endif; ?>
    <?php if (count($attributes['errors']) > 0) : ?>
        <fieldset class="error-field"><legend><label><i class="fa fa-bell"></i> Please Check the Following</label></legend>
        <?php foreach ($attributes['errors'] as $error) : ?>
            <p>
                
                <?php echo $error; ?>
            </p>
        <?php endforeach; ?>
        </fieldset>
    <?php endif; ?>
    <?php if ($attributes['show_title']) : ?>
        <h3><?php _e('Forgot Your Password?', 'personalize-login'); ?></h3>
    <?php endif; ?>

    <p>
        <?php
        _e(
                "Enter your email address and we'll send you a link you can use to pick a new password.", 'personalize_login'
        );
        ?>
    </p>

    <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
        <p class="form-row">
            <label class="form-label" for="user_login"><?php _e('Email', 'personalize-login'); ?>
                <input type="text" class="form-input" name="user_login" id="user_login">
                </p>

                <p class="lostpassword-submit">
                    <input type="submit" name="submit" class="form-button"
                           value="<?php _e('Reset Password', 'personalize-login'); ?>"/>
                </p>
    </form>
</div>