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
<?php if ($image_attributes) { ?>
    <img class="size-full wp-image-469 alignleft" src="<?php echo $image_attributes[0]; ?>" alt="" width="177" height="89" /><span style="font-size: 24pt; color: #000000;">GREBY MEJERI</span>
<?php } ?>
<?php if ($image_attributes1) { ?>
    <img class="wp-image-450 size-full" src="<?php echo $image_attributes1[0]; ?>" width="1018" height="329" />
<?php } ?>


<?= $user_meta['description'][0] ?><br><br>



&nbsp;

Opening Time : <?= $user_meta['opentime'][0] ?><br><br>

Categories Available :
<table style="width: 496px;" border="0">
    <tbody>
        <tr>
            <td style="width: 229px;"><?php if ($user_meta['bread'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Bread<br>
                <?php if ($user_meta['beverages'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Beverages<br>
                <?php if ($user_meta['dairy'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Cheese and other dairy products<br>
                <?php if ($user_meta['flour'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Flour, different types<br>
                <?php if ($user_meta['handicraft'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Handicraft and art</td>
            <td style="width: 253px;">
                <?php if ($user_meta['icecream'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Ice cream<br>
                <?php if ($user_meta['meat'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Meat<br>
                <?php if ($user_meta['milk'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Milk<br>
                <?php if ($user_meta['vegetables'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Vegetables<br>
                <?php if ($user_meta['other'][0]) { ?> <i class="fa fa-check"></i> <?php } ?>Other</td>
        </tr>
    </tbody>
</table>
Contact Details :<br><br>

Name : <?= $user_meta['first_name'][0] ?> <?= $user_meta['last_name'][0] ?><br><br>

Address : <?= $user_meta['address'][0] ?><br><br>

Email : <?= $current_user->user_email ?><br><br>

Website : <?= $current_user->user_url ?><br><br>

Facebook: <?= $user_meta['fb'][0] ?><br><br>

Twitter: <?= $user_meta['twit'][0] ?><br><br>

Instagram: <?= $user_meta['insta'][0] ?><br><br>

