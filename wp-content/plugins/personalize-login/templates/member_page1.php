<?php
global $current_user;
get_currentuserinfo();
$user_meta = get_user_meta($current_user->ID);
//print_r($current_user);
$attachment_id= $user_meta['logo_image'][0];
$image_attributes = wp_get_attachment_image_src( $attachment_id );
$attachment_id1= $user_meta['farm_image'][0];
$image_attributes1 = wp_get_attachment_image_src( $attachment_id1 );
?>


<!DOCTYPE html>
<html>
<title>Welcome Page</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
body,h1,h2,h3,h4,h5,h6 {font-family: "Raleway", sans-serif}
</style>
<body class="w3-light-grey w3-content" style="max-width:1600px">

<!-- Sidebar/menu -->
<nav class="w3-sidebar w3-collapse w3-white w3-animate-left" style="z-index:3;width:300px;" id="mySidebar"><br>
  <div class="w3-container">
    <a href="#" onclick="w3_close()" class="w3-hide-large w3-right w3-jumbo w3-padding w3-hover-grey" title="close menu">
      <i class="fa fa-remove"></i>
    </a>
    <?php if ($image_attributes) { ?>
        <img src="<?php echo $image_attributes[0]; ?>" width="<?php echo $image_attributes[1]; ?>" height="<?php echo $image_attributes[2]; ?>">
    <?php } ?><br><br>
    <h4><b>PORTFOLIO</b></h4>

    
  </div>
  <div class="w3-bar-block">
    <a href="#portfolio" onclick="w3_close()" class="w3-bar-item w3-button w3-padding w3-text-teal"><i class="fa fa-th-large fa-fw w3-margin-right"></i>PORTFOLIO</a> 
    <a href="#about" onclick="w3_close()" class="w3-bar-item w3-button w3-padding"><i class="fa fa-user fa-fw w3-margin-right"></i>ABOUT</a> 
    <a href="#contact" onclick="w3_close()" class="w3-bar-item w3-button w3-padding"><i class="fa fa-envelope fa-fw w3-margin-right"></i>CONTACT</a>
  </div>
  <div class="w3-panel w3-large">
    <a href="http://www.facebook.com/<?= $user_meta['fb'][0] ?>"><i class="fa fa-facebook-official w3-hover-opacity"></i></a>
    <a href="http://www.instagram.com/<?= $user_meta['insta'][0] ?>"><i class="fa fa-instagram w3-hover-opacity"></i></a>
    <a href="http://www.twitter.com/<?= $user_meta['twit'][0] ?>"><i class="fa fa-twitter w3-hover-opacity"></i></a>
  </div>
</nav>

<!-- Overlay effect when opening sidebar on small screens -->
<div class="w3-overlay w3-hide-large w3-animate-opacity" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

<!-- !PAGE CONTENT! -->
<div class="w3-main" style="margin-left:300px">

  <!-- Header -->
  <header id="portfolio">
    <a href="#"><img src="/w3images/avatar_g2.jpg" style="width:65px;" class="w3-circle w3-right w3-margin w3-hide-large w3-hover-opacity"></a>
    <span class="w3-button w3-hide-large w3-xxlarge w3-hover-text-grey" onclick="w3_open()"><i class="fa fa-bars"></i></span>
    <div class="w3-container">
    <h1><b>Organization Detail</b></h1>
    <div class="w3-section w3-bottombar w3-padding-16">
      <?php if ($image_attributes1) { ?>
        <img src="<?php echo $image_attributes1[0]; ?>" width="<?php echo $image_attributes1[1]; ?>" height="<?php echo $image_attributes1[2]; ?>">
    <?php } ?>
    </div>
    </div>
  </header>
  

  <div class="w3-container w3-padding-large"  id="about" style="margin-bottom:32px">
    <h4><b>About Organization</b></h4>
      <p><?= $user_meta['description'][0] ?></p><br>
        <p><?= $user_meta['opentime'][0] ?></p>
        <p>Categories: <?php if($user_meta['bread'][0]){
            echo $user_meta['bread'][0].",";
        }
        if($user_meta['beverages'][0]) {
            echo $user_meta['beverages'][0] .",";
        }
        if($user_meta['dairy'][0]){
            echo $user_meta['dairy'][0].",";
        }
        if($user_meta['flour'][0]){
            echo $user_meta['flour'][0].",";
        }
        if($user_meta['handicraft'][0]){ 
            echo $user_meta['handicraft'][0] .",";
        }if($user_meta['icecream'][0]){
            echo $user_meta['icecream'][0] .",";
        }
        if($user_meta['meat'][0]){
            echo $user_meta['meat'][0] .",";
        }
        if($user_meta['vegetables'][0]){ 
            echo $user_meta['vegetables'][0].",";
            
        } 
        if($user_meta['other'][0]){
            echo $user_meta['other'][0];
        }
        
?></p>
    <hr>
    
   
    
   
  </div>
  
  <!-- Contact Section -->
  <div class="w3-container w3-padding-large w3-grey">
    <h4 id="contact"><b>Contact Me</b></h4>
    <div class="w3-row-padding w3-center w3-padding-24" style="margin:0 -16px">
      <div class="w3-third w3-dark-grey">
        <p><i class="fa fa-envelope w3-xxlarge w3-text-light-grey"></i></p>
        <p><?= $current_user->user_url ?></p>
      </div>
      <div class="w3-third w3-teal">
        <p><i class="fa fa-map-marker w3-xxlarge w3-text-light-grey"></i></p>
        <p><?= $user_meta['address'][0] ?></p>
      </div>
      <div class="w3-third w3-dark-grey">
        <p><i class="fa fa-phone w3-xxlarge w3-text-light-grey"></i></p>
        <p>512312311</p>
      </div>
    </div>
    <hr class="w3-opacity">
    <!-- <form action="/action_page.php" target="_blank">
      <div class="w3-section">
        <label>Name</label>
        <input class="w3-input w3-border" type="text" name="Name" required>
      </div>
      <div class="w3-section">
        <label>Email</label>
        <input class="w3-input w3-border" type="text" name="Email" required>
      </div>
      <div class="w3-section">
        <label>Message</label>
        <input class="w3-input w3-border" type="text" name="Message" required>
      </div>
      <button type="submit" class="w3-button w3-black w3-margin-bottom"><i class="fa fa-paper-plane w3-margin-right"></i>Send Message</button>
    </form> -->
  </div>

 

<!-- End page content -->
</div>

<script>
// Script to open and close sidebar
function w3_open() {
    document.getElementById("mySidebar").style.display = "block";
    document.getElementById("myOverlay").style.display = "block";
}
 
function w3_close() {
    document.getElementById("mySidebar").style.display = "none";
    document.getElementById("myOverlay").style.display = "none";
}
</script>

</body>
</html>


