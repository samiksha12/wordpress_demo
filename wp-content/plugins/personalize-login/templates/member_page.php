<?php
global $current_user;
get_currentuserinfo();
$user_meta = get_user_meta($current_user->ID);
//print_r($current_user);
$attachment_id = $user_meta['logo_image'][0];
$image_attributes = wp_get_attachment_image_src($attachment_id);
$attachment_id1 = $user_meta['farm_image'][0];
$image_attributes1 = wp_get_attachment_image_src($attachment_id1, 'full');
?>
<div class="contain">
<?php if ($image_attributes) { ?>
<img class="size-full wp-image-469 alignleft farm-logo" src="<?php echo $image_attributes[0]; ?>" alt="" /><br><br><span class="head-span"><?= $user_meta['org_name'][0] ?></span><span class="spanright"><a href="<?= get_permalink(get_page_by_path('edit-account')) ?>"><i class="fa fa-pencil"></i>Edit</a></span>
<?php } ?>

<?php if ($image_attributes1) { ?>
    <img class="wp-image-450 size-full farm-img" src="<?php echo $image_attributes1[0]; ?>" width="1018" height="329" />
<?php } ?>

<br><br>
<?= $user_meta['description'][0] ?><br><br>





<b>Opening Time : </b><?= $user_meta['opentime'][0] ?><br><br>

<b>Categories Available :</b>
<table style="width: 496px;" border="0">
    <tbody>
        <tr>
            <td style="width: 35px;">
                <?php if ($user_meta['bread'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['beverages'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['dairy'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['flour'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['handicraft'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
            </td>
            <td style="width: 229px;">Bread<br>
                Beverages<br>
                Cheese and other dairy products<br>
                Flour, different types<br>
                Handicraft and art</td>
            <td style="width: 35px;">
                <?php if ($user_meta['icecream'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['meat'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['milk'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['vegetables'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
                <?php if ($user_meta['other'][0]) { ?> <i class="fa fa-check"></i><br> <?php }else { echo '&nbsp;<br>';} ?>
            </td>
            <td style="width: 253px;">
                Ice cream<br>
                Meat<br>
                Milk<br>
                Vegetables<br>
                Other</td>
        </tr>
    </tbody>
</table>
<b>Contact Details :</b> <?= $user_meta['contact'][0] ?><br><br>

<b>Name : </b><?= $user_meta['first_name'][0] ?> <?= $user_meta['last_name'][0] ?><br><br>

<b>Address :</b> <?= $user_meta['address'][0] ?><br><br>

<b>Email : </b><?= $current_user->user_email ?><br><br>

<b>Website : </b><?= $current_user->user_url ?><br><br>

<b>Facebook: </b><?= $user_meta['fb'][0] ?><br><br>

<b>Twitter: </b><?= $user_meta['twit'][0] ?><br><br>

<b>Instagram: </b><?= $user_meta['insta'][0] ?><br><br>

</div>
