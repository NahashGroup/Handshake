

<div id="popup_error" class="overlay">
        <div class="popup_error">
          <h2>Error</h2>
          <a class="close" href="">&times;</a>
          <div class="content"> <?php if(isset($_SESSION['error_log'])){ echo encode_html($_SESSION['error_log']); } ?> </div>
        </div>
</div>

<div id="popup_success" class="overlay">
        <div class="popup_success">
          <h2>Success</h2>
          <a class="close" href="">&times;</a>
          <div class="content"> <?php if(isset($_SESSION['success_log'])){ echo encode_html($_SESSION['success_log']); } ?> </div>
        </div>
</div>



<div class="bottomnav">
      <footer>
        <div class="footer-left">
          <p>&copy;<?php if(isset($site_name)){ echo date("Y") . " " . encode_html($site_name); } ?></p>
        </div>
        <div class="footer-center">
          <p><?php echo encode_html($everyone->global_exchanges_stats()); ?></p>
        </div>
      </footer>
    </div>
  </body>
</html>