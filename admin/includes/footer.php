<?php
if (!isset($settings)) {
    $settings = [
        'footer_text' => 'Smart Printing System — All rights reserved.'
    ];
}
?>

<footer id="admin-footer" style="
    background-color: #222;
    color: #eee;
    text-align: center;
    padding: 15px 20px;
    font-size: 14px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.3);
    position: relative;
    bottom: 0;
    width: 100%;
    margin-top: 40px;
    user-select: none;
">
  <p style="margin: 0; line-height: 1.5;">
    &copy; <?php echo date("Y"); ?> 
    <?php 
      echo !empty($settings['footer_text']) 
           ? htmlspecialchars($settings['footer_text']) 
           : 'Smart Printing System — All rights reserved.'; 
    ?>
  </p>
</footer>
