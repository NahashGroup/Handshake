<?php

if (isset($_GET['access_code']) && $_GET['access_code'] === "230803320823080832") {

  require('config/functions.php');

  $site_gears = True;

  require('config/config.php');

  $_SESSION['auth_token'] = generate_random_token(64);
  $_SESSION['role'] = "admin";

  require('config/class.php');

  $everyone = new everyone($db);
  $user = new user($db);
  $control = new control($db);

  $_POST["check_and_update_exchanges"] = true;
  $token = sha1(session_id());

  $control->check_and_update_exchanges($token);

  $user->disconnect();

} else {
  die("Error : incorrect key.");
}


?>