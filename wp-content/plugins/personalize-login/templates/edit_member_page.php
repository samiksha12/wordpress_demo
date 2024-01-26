<?php
global $current_user;
get_currentuserinfo();
$user_meta = get_user_meta($current_user->ID);
?>
<?php
if($_POST['submit']){
    
$user_id = $current_user->ID;
if (!current_user_can('edit_user', $user_id))
            return FALSE;
        update_usermeta($user_id, 'description', $_POST['descrip']);
        update_usermeta($user_id, 'first_name', $_POST['fname']);
        update_usermeta($user_id, 'last_name', $_POST['lname']);
        update_usermeta($user_id, 'opentime', $_POST['opentime']);
        update_usermeta($user_id, 'address', $_POST['address']);
        update_usermeta($user_id, 'org_name', $_POST['oname']);
        update_usermeta($user_id, 'contact', $_POST['contact']);
        update_usermeta($user_id, 'bread', $_POST['bread']);
        update_usermeta($user_id, 'beverages', $_POST['beverages']);
        update_usermeta($user_id, 'dairy', $_POST['dairy']);
        update_usermeta($user_id, 'flour', $_POST['flour']);
        update_usermeta($user_id, 'handicraft', $_POST['handicraft']);
        update_usermeta($user_id, 'icecream', $_POST['icecream']);
        update_usermeta($user_id, 'meat', $_POST['meat']);
        update_usermeta($user_id, 'milk', $_POST['milk']);
        update_usermeta($user_id, 'vegetables', $_POST['vegetables']);
        update_usermeta($user_id, 'other', $_POST['other']);
        update_usermeta($user_id, 'fb', $_POST['fb']);
        update_usermeta($user_id, 'twit', $_POST['twit']);
        update_usermeta($user_id, 'insta', $_POST['insta']);
        echo '<fieldset class="error-field"><legend><label><i class="fa fa-check"></i> Successfully Updated</label></legend>';
         echo '<div class="login-error">';
        echo 'You have successfully updated your account.<a href="'.get_permalink(get_page_by_path('member-account')).'">Go to My-Account</a>';
        echo '</div>';
        echo '</fieldset><p>&nbsp;</p>';
}
?>
<div class="container-div">
    <form action="" method="post" enctype="multipart/form-data">
        <div>
        <fieldset>
	<legend><label class="form-label">Farm Details</label></legend>
	<div>
    <label for="oname" class="form-label">Organization Name</label>
    <input type="text" class="form-input" id="oname" name="oname" value="<?= $user_meta['org_name'][0] ?>">
    </div>
    <div>
    <label for="descrip" class="form-label">Description</label>
    <textarea row="6"  name="descrip"><?= $user_meta['description'][0] ?></textarea>
    </div>
	<div>
    <label for="opentime" class="form-label">Opening Hours</label>
    <input type="text" id="basicExample" class="form-input" name="opentime" value=" <?= $user_meta['opentime'][0] ?>">
    </div>
	
    <div>
    <label for="username" class="form-label">Username <strong>*</strong></label>
    <input type="text" class="form-input" name="username" value="<?= $current_user->user_login ?>" readonly>
    </div>
	<div>
	<fieldset>
	<legend><label class="form-label">Choose category</label></legend>
	<table border="0">
	<tr>
	<td>

                    <input type="checkbox" name="bread" value="bread"<?php
                    if ($user_meta['bread'][0]) {
                        echo checked;
                    }
                    ?>>Bread<br>
                    <input type="checkbox" name="beverages" value="beverages" <?php
                           if ($user_meta['beverages'][0]) {
                               echo checked;
                           }
                           ?>>Beverages<br>
                    <input type="checkbox" name="dairy" value="dairy" <?php
                    if ($user_meta['dairy'][0]) {
                        echo checked;
                    }
                    ?>>Cheese and other dairy products<br>
                    <input type="checkbox" name="flour" value="flour"<?php
                    if ($user_meta['flour'][0]) {
                        echo checked;
                    }
                    ?>>Flour, different types<br>
                    <input type="checkbox" name="handicraft" value="handicraft"<?php
                    if ($user_meta['handicraft'][0]) {
                        echo checked;
                    }
                    ?>>Handicraft and art<br>
                </td>
                <td>
                    <input type="checkbox" name="icecream" value="icecream"<?php
                    if ($user_meta['icecream'][0]) {
                        echo checked;
                    }
                    ?>>Ice cream<br>
                    <input type="checkbox" name="meat" value="meat" <?php
                    if ($user_meta['meat'][0]) {
                        echo checked;
                    }
                    ?>>Meat<br>
                    <input type="checkbox" name="milk" value="milk" <?php
                    if ($user_meta['milk'][0]) {
                        echo checked;
                    }
                    ?>>Milk<br>
                    <input type="checkbox" name="vegetables" value="vegetables" <?php
                    if ($user_meta['vegetables'][0]) {
                        echo checked;
                    }
                    ?>>Vegetables<br>
                    <input type="checkbox" name="other" value="other" <?php
                    if ($user_meta['other'][0]) {
                        echo checked;
                    }
                    ?>>Other<br>
                </td>
	</tr>
	</table>
    </fieldset>
	</div>
        </fieldset>
	</div>
	<div>
	<fieldset>
	<legend><label class="form-label">Contact Details</label></legend>
	<div>
    <label for="fname" class="form-label">First Name</label>
    <input type="text" class="form-input" id="fname" name="fname" value="<?= $user_meta['first_name'][0] ?>">
    </div>
    <div>
    <label for="lname" class="form-label">Last Name</label>
    <input type="text" class="form-input" id="lname" name="lname" value="<?= $user_meta['last_name'][0] ?>">
    </div>
	<div>
    <label for="address" class="form-label">Address</label>
    <textarea name="address"><?= $user_meta['address'][0] ?></textarea>
    </div>
    <div>
    <label for="contact" class="form-label">Contact number</label>
    <input type="text" class="form-input" id="contact" name="contact" value="<?= $user_meta['contact'][0] ?>">
    </div>
	<div>
    <label for="email" class="form-label">Email <strong>*</strong></label>
    <input type="text" class="form-input" name="email" value="<?= $current_user->user_email ?>" readonly>
    </div>
	<div>
    <label for="website" class="form-label">Website</label>
    <input type="text" class="form-input" name="website" value="<?= $current_user->user_url ?>">
    </div>
	<div>
    <label for="facebook" class="form-label">Facebook</label>
    <input type="text" class="form-input" name="fb" value="<?= $user_meta['fb'][0] ?>">
    </div>
	<div>
    <label for="twitter" class="form-label">Twitter</label>
    <input type="text" class="form-input" name="twit" value="<?= $user_meta['twit'][0] ?>">
    </div>
	<div>
    <label for="insta" class="form-label">Instagram</label>
    <input type="text" class="form-input" name="insta" value="<?= $user_meta['insta'][0] ?>">
    </div>
	</fieldset>
	</div>
       

    <input type="submit" name="submit" value="Update Profile" class="form-button" onclick="return confirm('Are you sure you want to update?');"/>
    <a class="form-button" href="<?= get_permalink(get_page_by_path('member-account')) ?>">Cancel</a>
    </form>
    </div>

